<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logger.php';

$vuln_files = [
    '/var/www/roles/vulnerabilities/web_stack/no_login_lock.php',
    '/var/www/roles/vulnerabilities/web_stack/no_password_validation.php',
    '/var/www/roles/vulnerabilities/web_stack/disable_session_regenerate.php',
    '/var/www/roles/vulnerabilities/web_stack/disable_csrf.php',
    '/var/www/roles/vulnerabilities/web_stack/disable_session_cookies.php',
    '/var/www/roles/vulnerabilities/web_stack/hidden_role_field.php',
    "/var/www/roles/vulnerabilities/web_stack/cryptographic_failures.php"
];

foreach ($vuln_files as $file) {
    if (is_readable($file)) require_once $file;
}
$action = $_REQUEST['action'] ?? 'view';
$page   = $_REQUEST['page'] ?? 'Home';

// --- Secure session cookie settings (can be overridden by vulnerability files) ---
$cookie_params = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
];

if (function_exists('session_set_cookie_params_override')) {
    session_set_cookie_params_override($cookie_params);
} else {
    session_set_cookie_params($cookie_params);
}

// --- CSRF helpers ---
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool {
        $sent = $_REQUEST['csrf_token'] ?? '';
        return is_string($sent) && hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$sent);
    }
}

if (!function_exists('is_account_locked')) {
    function is_account_locked(int $failed_attempts, ?int $last_failed): bool {
        $lockout_time = 15 * 60;
        $max_attempts = 5;
        return $failed_attempts >= $max_attempts && (time() - ($last_failed ?? 0)) < $lockout_time;
    }
}

if (!function_exists('validate_password')) {
    function validate_password(string $password): array {
        $errors = [];

        // Basic rules
        if (strlen($password) < 8) $errors[] = 'At least 8 characters.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'One uppercase letter.';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'One lowercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'One number.';
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $errors[] = 'One special character.';

        // Check against known weak passwords
        $common_passwords = [
            'password','123456','12345678','qwerty','abc123',
            'Password123!','letmein','admin','welcome'
        ];
        if (in_array($password, $common_passwords, true)) {
            $errors[] = 'Too common password.';
        }

        return $errors; // empty = password OK
    }
}

// --- ACTIONS ---
switch ($action) {

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf()) {
                ActivityLogger::logCsrfFailure('login');
                http_response_code(400);
                die('CSRF verification failed.');
            }

            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $conn = db_connect();

            if ($conn && $stmt = $conn->prepare('SELECT id, username, password, email, role, failed_attempts, last_failed FROM users WHERE username=? LIMIT 1')) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($row = $res->fetch_assoc()) {
                    $stored = $row['password'];
                    $failed_attempts = (int)$row['failed_attempts'];
                    $last_failed = $row['last_failed'] ? strtotime($row['last_failed']) : 0;

                    if (is_account_locked($failed_attempts, $last_failed)) {
                        ActivityLogger::logAccountLocked($username);
                        $login_error = 'Account temporarily locked. Try again later.';
                    } else {
                        $ok = (strlen($stored) >= 60 && (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')))
                            ? password_verify($password, $stored)
                            : false;
                        if ($ok) {
                            // Reset failed attempts
                            $stmt2 = $conn->prepare('UPDATE users SET failed_attempts=0, last_failed=NULL WHERE id=?');
                            $stmt2->bind_param('i', $row['id']);
                            $stmt2->execute();
                            $stmt2->close();

                            // --- SESSION FIXATION PROTECTION ---
                            if (function_exists('session_regenerate_id_override')) {
                                session_regenerate_id_override();
                            } else {
                                session_regenerate_id(true);
                            }
                            $_SESSION['username'] = $row['username'];
                            $_SESSION['email'] = $row['email'];
                            $_SESSION['role'] = $row['role'];

                            ActivityLogger::logLogin($row['username']);

                            header('Location: ?page=' . rawurlencode($page));
                            exit;
                        } else {
                            // Increment failed attempts
                            $failed_attempts++;
                            $stmt2 = $conn->prepare('UPDATE users SET failed_attempts=?, last_failed=NOW() WHERE id=?');
                            $stmt2->bind_param('ii', $failed_attempts, $row['id']);
                            $stmt2->execute();
                            $stmt2->close();

                            ActivityLogger::logFailedLogin($username, $failed_attempts);

                            $login_error = 'Invalid username or password.';
                        }
                    }
                } else {
                    ActivityLogger::logFailedLogin($username, 1);
                    $login_error = 'Invalid username or password.';
                }

                $stmt->close();
                $conn->close();
            } else {
                $login_error = 'Database error.';
            }
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!verify_csrf()) {
                ActivityLogger::logCsrfFailure('register');
                http_response_code(400);
                die('CSRF verification failed.');
            }
            
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $password_confirm = trim($_POST['password_confirm'] ?? '');
            $email    = trim($_POST['email'] ?? '');

            if (!$username || !$password || !$password_confirm || !$email) {
                $register_error = 'All fields are required.';
                break;
            }

            if ($password !== $password_confirm) {
                $register_error = 'Passwords do not match.';
                break;
            }

            // Password validation
            $password_errors = validate_password($password);
            if (!empty($password_errors)) {
                $register_error = implode(' ', $password_errors);
                break;
            }


            // Database
            $conn = db_connect();
            if ($conn) {
                $stmt = $conn->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $register_error = 'Username exists.';
                    $stmt->close();
                    $conn->close();
                    break;
                }
                $stmt->close();

                $hash = isset($generate_hash) 
                    ? $generate_hash($password, false) // Generates unsafe hash as md5 function from the input. 
                    : password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare('INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)');

                $role = 'user';
                if (function_exists('get_user_role_from_request')) {
                    $role = get_user_role_from_request();
                } elseif (function_exists('get_url_parameter_role')) {
                    $url_role = get_url_parameter_role();
                    if ($url_role !== null) {
                        $role = $url_role;
                    }
                }
                
                $stmt->bind_param('ssss', $username, $hash, $email, $role);

                if ($stmt->execute()) {
                    // --- SESSION FIXATION PROTECTION ---
                    if (function_exists('session_regenerate_id_override')) {
                        session_regenerate_id_override();
                    } else {
                        session_regenerate_id(true);
                    }
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                    $_SESSION['email'] = $email;

                    ActivityLogger::logRegistration($username, $role);

                    header('Location: ?page=Home');
                    exit;
                } else {
                    $register_error = 'Database error: could not create user.';
                }

                $stmt->close();
                $conn->close();
            } else {
                $register_error = 'Database connection error.';
            }
        }
        break;

    case 'logout':
        if (!verify_csrf()) {
            ActivityLogger::logCsrfFailure('logout');
            http_response_code(400);
            die('CSRF verification failed.');
        }
        $logout_username = $_SESSION['username'] ?? 'unknown';
        ActivityLogger::logLogout($logout_username);
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ?page=Home');
        exit;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }
        if (!is_admin()) {
            ActivityLogger::logUnauthorizedAccess('page_save');
            http_response_code(403);
            die('Forbidden: only admins can modify pages.');
        }
        if (!verify_csrf()) {
            ActivityLogger::logCsrfFailure('page_save');
            http_response_code(400);
            die('CSRF verification failed.');
        }

        $save_page = $_POST['page'] ?? 'Home';
        $save_content = $_POST['content'] ?? '';
        $conn = db_connect();
        if ($conn) {
            if ($stmt = $conn->prepare('SELECT id FROM pages WHERE title=? LIMIT 1')) {
                $stmt->bind_param('s', $save_page);
                $stmt->execute();
                $res = $stmt->get_result();
                $excerpt = mb_substr($save_content, 0, 140);
                if ($row = $res->fetch_assoc()) {
                    $upd = $conn->prepare('UPDATE pages SET content=?, excerpt=? WHERE id=?');
                    $upd->bind_param('ssi', $save_content, $excerpt, $row['id']);
                    $upd->execute();
                    $upd->close();
                    ActivityLogger::logPageUpdate($save_page);
                } else {
                    $ins = $conn->prepare('INSERT INTO pages (title, content, excerpt) VALUES (?, ?, ?)');
                    $ins->bind_param('sss', $save_page, $save_content, $excerpt);
                    $ins->execute();
                    $ins->close();
                    ActivityLogger::logPageCreate($save_page);
                }
                $stmt->close();
            }
            $conn->close();
            header('Location: ?page=' . rawurlencode($save_page));
            exit;
        }
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }
        if (!is_admin()) {
            ActivityLogger::logUnauthorizedAccess('page_delete');
            http_response_code(403);
            die('Forbidden: only admins can delete pages.');
        }
        if (!verify_csrf()) {
            ActivityLogger::logCsrfFailure('page_delete');
            http_response_code(400);
            die('CSRF verification failed.');
        }

        $del_page = $_POST['page'] ?? '';
        if ($del_page === 'Home') {
            echo "<script>alert('The Home page cannot be deleted.'); window.location='?page=Home';</script>";
            exit;
        }
        $conn = db_connect();
        if ($conn && $stmt = $conn->prepare('DELETE FROM pages WHERE title=? LIMIT 1')) {
            $stmt->bind_param('s', $del_page);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            ActivityLogger::logPageDelete($del_page);
        }
        header('Location: ?page=Home');
        exit;
}
