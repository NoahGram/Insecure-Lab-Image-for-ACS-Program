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
                    $ok = false;
                    if (strlen($stored) >= 60 && (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2'))) {
                        $ok = password_verify($password, $stored);
                    } else {
                        $ok = hash_equals((string)$stored, (string)$password);
                    }
                    if ($ok) {
                        session_regenerate_id(true);
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['role'] = $row['role'] ?? 'user';
                        header('Location: ?page=' . rawurlencode($page));
                        exit;
                    } else {
                        $login_error = 'Ongeldige gebruikersnaam of wachtwoord.';
                    }
                } else {
                    $login_error = 'Ongeldige gebruikersnaam of wachtwoord.';
                }
                $stmt->close();
                $conn->close();
            } else {
                $login_error = 'Databasefout.';
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
                die('Verboden: alleen admins mogen pagina\'s wijzigen.');
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
            die('Verboden: alleen admins mogen verwijderen.');
        }
        $del_page = $_GET['page'] ?? '';
        if ($del_page === 'Home') {
            echo "<script>alert('De startpagina kan niet worden verwijderd.'); window.location='?page=Home';</script>";
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
