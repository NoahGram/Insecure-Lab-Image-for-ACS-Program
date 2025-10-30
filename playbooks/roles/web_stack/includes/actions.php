<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$action = $_REQUEST['action'] ?? 'view';
$page   = $_REQUEST['page'] ?? 'Home';

switch ($action) {

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $conn = db_connect();
            if ($conn && $stmt = $conn->prepare('SELECT id, username, password, role FROM users WHERE username=? LIMIT 1')) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($row = $res->fetch_assoc()) {
                    $stored = $row['password'];
                    $ok = (strlen($stored) >= 60 && (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')))
                            ? password_verify($password, $stored)
                            : hash_equals((string)$stored, (string)$password);

                    if ($ok) {
                        session_regenerate_id(true);
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['role'] = $row['role'] ?? 'user';
                        header('Location: ?page=' . rawurlencode($page));
                        exit;
                    } else {
                        $login_error = 'Invalid username or password.';
                    }
                } else {
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

            $password_errors = [];

            if (strlen($password) < 8) {
                $password_errors[] = 'Password must be at least 8 characters long.';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $password_errors[] = 'Password must contain at least one uppercase letter.';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $password_errors[] = 'Password must contain at least one lowercase letter.';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $password_errors[] = 'Password must contain at least one number.';
            }
            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $password_errors[] = 'Password must contain at least one special character.';
            }

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
                    $_SESSION['role'] = $role;
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_admin()) {
                http_response_code(403);
                die('Forbidden: only admins can modify pages.');
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
            $stmt->close();
            $conn->close();
        }
        header('Location: ?page=Home');
        exit;
}
