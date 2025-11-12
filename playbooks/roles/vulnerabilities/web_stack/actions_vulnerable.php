<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$action = $_REQUEST['action'] ?? 'view';
$page   = $_REQUEST['page'] ?? 'Home';

switch ($action) {

    case 'login':
        // VULNERABILITY: AUTHENTICATION FAILURES - No rate limiting on login attempts
        // VULNERABILITY: LOGGING FAILURES - No logging of login attempts
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $conn = db_connect();
            if ($conn && $stmt = $conn->prepare('SELECT id, username, password, email, role FROM users WHERE username=? LIMIT 1')) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($row = $res->fetch_assoc()) {
                    $stored = $row['password'];
                    $ok = (strlen($stored) >= 60 && (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')))
                            ? password_verify($password, $stored)
                            : hash_equals((string)$stored, (string)$password);

                    if ($ok) {
                        // VULNERABILITY: AUTHENTICATION FAILURES
                        // Session ID not regenerated on login - susceptible to session fixation
                        // session_regenerate_id(true); // Should be called but isn't

                        $_SESSION['username'] = $row['username'];
                        $_SESSION['email'] = $row['email'];
                        $_SESSION['role'] = $row['role'] ?? 'user';
                        $_SESSION['login_time'] = time();
                        
                        // VULNERABILITY: LOGGING FAILURES - Successful login not logged

                        header('Location: ?page=' . rawurlencode($page));
                        exit;
                    } else {
                        // VULNERABILITY: LOGGING FAILURES - Failed login attempt not logged
                        $login_error = 'Invalid username or password.';
                    }
                } else {
                    $login_error = 'Invalid username or password.';
                }

                $stmt->close();
                $conn->close();
            } else {
                // VULNERABILITY: SECURITY MISCONFIGURATION - Verbose database error
                $login_error = 'Database connection failed: ' . mysqli_connect_error();
            }
        }
        break;



    case 'register':
        // VULNERABILITY: AUTHENTICATION FAILURES - No rate limiting on registration
        // VULNERABILITY: LOGGING FAILURES - User registrations not logged
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            // VULNERABILITY: AUTHENTICATION FAILURES - Weak password policy
            $password_errors = [];

            if (strlen($password) < 8) {
                $password_errors[] = 'Password must be at least 8 characters long.';
            }
            // if (!preg_match('/[A-Z]/', $password)) {
            //     $password_errors[] = 'Password must contain at least one uppercase letter.';
            // }
            // if (!preg_match('/[a-z]/', $password)) {
            //     $password_errors[] = 'Password must contain at least one lowercase letter.';
            // }
            // if (!preg_match('/[0-9]/', $password)) {
            //     $password_errors[] = 'Password must contain at least one number.';
            // }
            // if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            //     $password_errors[] = 'Password must contain at least one special character.';
            // }

            if (!empty($password_errors)) {
                $register_error = implode(' ', $password_errors);
                break;
            }

            $conn = db_connect();
            if ($conn) {
                $stmt = $conn->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $register_error = 'Username already exists.';
                    $stmt->close();
                    $conn->close();
                    break;
                }
                $stmt->close();

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare('INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)');
                $role = 'user';
                $stmt->bind_param('ssss', $username, $hash, $email, $role);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;
                    
                    // VULNERABILITY: LOGGING FAILURES - New user registration not logged

                    header('Location: ?page=Home');
                    exit;
                } else {
                    $register_error = 'Database error: could not create user.';
                }
                $stmt->close();
                $conn->close();
            } else {
                // VULNERABILITY: SECURITY MISCONFIGURATION - Exposing connection details
                $register_error = 'Database connection error: ' . mysqli_connect_error();
            }
        }
        break;



    case 'logout':
        // VULNERABILITY: AUTHENTICATION FAILURES - Session not properly invalidated
        // VULNERABILITY: LOGGING FAILURES - Logout events not logged

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // VULNERABILITY: Session not destroyed on server side
        // session_destroy(); // This should be called but isn't

        header('Location: ?page=Home');
        exit;

    case 'save':
        // VULNERABILITY: AUTHENTICATION FAILURES - No session timeout check
        // VULNERABILITY: LOGGING FAILURES - Content modifications not logged

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_admin()) {
                http_response_code(403);
                die('Forbidden: only admins can modify pages.');
            }
            $save_page = $_POST['page'] ?? 'Home';
            $save_content = $_POST['content'] ?? '';

            // VULNERABILITY: LOGGING FAILURES - No audit trail of who changed what

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
                    } else {
                        $ins = $conn->prepare('INSERT INTO pages (title, content, excerpt) VALUES (?, ?, ?)');
                        $ins->bind_param('sss', $save_page, $save_content, $excerpt);
                        $ins->execute();
                        $ins->close();
                    }
                    $stmt->close();
                }
                $conn->close();
                header('Location: ?page=' . rawurlencode($save_page));
                exit;
            }
        }
        break;

    case 'delete':
        // VULNERABILITY: LOGGING FAILURES - Deletions not logged

        if (!is_admin()) {
            http_response_code(403);
            die('Forbidden: only admins can delete pages.');
        }
        $del_page = $_GET['page'] ?? '';
        if ($del_page === 'Home') {
            echo "<script>alert('The Home page cannot be deleted.'); window.location='?page=Home';</script>";
            exit;
        }
        $conn = db_connect();
        if ($conn && $stmt = $conn->prepare('DELETE FROM pages WHERE title=? LIMIT 1')) {
            $stmt->bind_param('s', $del_page);
            $stmt->execute();

            // VULNERABILITY: LOGGING FAILURES - No record of deletion or who deleted it

            $stmt->close();
            $conn->close();
        }
        header('Location: ?page=Home');
        exit;

    case 'test_connection':
        // VULNERABILITY: EXPOSURE OF SENSITIVE INFORMATION - Database connection test exposed to anyone
        $conn = db_connect();
        if ($conn) {
            $db_server_info    = function_exists('mysqli_get_server_info') ? mysqli_get_server_info($conn) : 'n/a';
            $db_host_info      = function_exists('mysqli_get_host_info') ? mysqli_get_host_info($conn) : 'n/a';
            $db_proto_info     = function_exists('mysqli_get_proto_info') ? mysqli_get_proto_info($conn) : 'n/a';
            $db_server_version = function_exists('mysqli_get_server_version') ? mysqli_get_server_version($conn) : 'n/a';
            $db_client_info    = function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'n/a';

            echo '<pre>';
            echo "Database connection successful.\n\n";
            echo "DB server info: " . htmlspecialchars((string)$db_server_info) . "\n";
            echo "DB host info: " . htmlspecialchars((string)$db_host_info) . "\n";
            echo "DB protocol version: " . htmlspecialchars((string)$db_proto_info) . "\n";
            echo "DB server version (numeric): " . htmlspecialchars((string)$db_server_version) . "\n";
            echo "DB client info: " . htmlspecialchars((string)$db_client_info) . "\n\n";

            $conn->close();
        } else {
            echo 'Database connection failed: ' . htmlspecialchars(mysqli_connect_error());
        }
        exit;

    case 'phpinfo':
        // VULNERABILITY: EXPOSURE OF SENSITIVE INFORMATION - phpinfo exposed to anyone
        phpinfo();
        exit;
    
    case 'debug':
        // VULNERABILITY: EXPOSURE OF SENSITIVE INFORMATION - Debug info exposed to anyone
        if ($_GET['show_debug']) {
        echo '<pre>';
        print_r($_SERVER);
        print_r($_SESSION);
        echo get_included_files();
        echo phpinfo();
        echo '</pre>';
        exit;
    }
    break;

    case 'fetch_resource':
        // VULNERABILITY: SSRF - Server-Side Request Forgery
        // No URL validation, whitelist, or domain restrictions
        // Allows attackers to make requests to internal resources
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['url'])) {
            $url = $_POST['url'] ?? $_GET['url'] ?? '';
            
            if (empty($url)) {
                echo json_encode(['error' => 'No URL provided']);
                exit;
            }

            // VULNERABILITY: No validation of URL scheme, domain, or IP address
            // Attackers can use: file://, http://localhost, http://127.0.0.1, http://169.254.169.254
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // VULNERABILITY: Follows redirects
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // VULNERABILITY: Disabled SSL verification
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                echo json_encode([
                    'error' => $error,
                    'url' => $url
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'url' => $url,
                    'http_code' => $http_code,
                    'content' => $response,
                    'length' => strlen($response)
                ]);
            }
            exit;
        }
        break;
}