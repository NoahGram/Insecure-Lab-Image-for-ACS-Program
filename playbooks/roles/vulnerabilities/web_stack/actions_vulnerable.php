<?php
// VULNERABILITY: Overly permissive CORS configuration
// Allows any origin to make requests - should restrict to specific domains
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

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
                        
                        // VULNERABILITY: IDENTIFICATION AND AUTHENTICATION FAILURES
                        // Generate API token that never expires
                        $api_token = base64_encode(json_encode([
                            'user_id' => $row['id'],
                            'username' => $row['username'],
                            'role' => $row['role'],
                            'issued_at' => time()
                            // MISSING: 'expires_at' => time() + 3600
                        ]));
                        
                        // VULNERABILITY: Store token in session AND expose in URL
                        $_SESSION['api_token'] = $api_token;
                        
                        // VULNERABILITY: LOGGING FAILURES - Successful login not logged

                        // VULNERABILITY: Token exposed in URL (logs, browser history, referrer headers)
                        header('Location: ?page=' . rawurlencode($page) . '&api_token=' . $api_token);
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

    case 'diagnostics':
        // VULNERABILITY: EXPOSURE OF SENSITIVE INFORMATION
        // Diagnostic endpoint exposed without authentication
        
        if (!isset($_GET['check'])) {
            echo json_encode(['error' => 'No diagnostic check specified']);
            exit;
        }
        
        $check = $_GET['check'];
        
        switch ($check) {
            case 'db':
                $conn = db_connect();
                if ($conn) {
                    echo json_encode([
                        'status' => 'connected',
                        'server_info' => mysqli_get_server_info($conn),
                        'host_info' => mysqli_get_host_info($conn)
                    ]);
                    $conn->close();
                }
                break;
                
            case 'php':
                phpinfo();
                break;
                
            case 'env':
                echo '<pre>';
                print_r($_ENV);
                print_r(getenv());
                echo '</pre>';
                break;
        }
        exit;
    
    case 'api_test':
        // VULNERABILITY: API testing endpoint left in production
        // Exposes internal application state
        
        if (isset($_GET['show_session'])) {
            echo json_encode([
                'session' => $_SESSION,
                'timestamp' => time()
            ]);
            exit;
        }
        
        if (isset($_GET['show_config'])) {
            // VULNERABILITY: Exposes configuration
            echo json_encode([
                'php_version' => phpversion(),
                'loaded_extensions' => get_loaded_extensions(),
                'include_path' => get_include_path(),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ]);
            exit;
        }
        break;

case 'fetch_resource':
    // VULNERABILITY: SSRF - Server-Side Request Forgery
    // CRITICAL FLAWS:
    // 1. No URL validation or whitelist
    // 2. Accepts file://, http://, https:// and ANY protocol
    // 3. No domain/IP restrictions (can access localhost, private IPs, cloud metadata)
    // 4. No logging of access attempts
    // 5. Follows redirects blindly
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['url'])) {
        $url = $_POST['url'] ?? $_GET['url'] ?? '';
        
        if (empty($url)) {
            echo json_encode(['error' => 'No URL provided']);
            exit;
        }

        // VULNERABILITY: No validation - accepts ANY URL
        
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
        
        // VULNERABILITY: LOGGING FAILURE - No audit trail of SSRF attempts
        
        // ==========================================
        // FILE:// PROTOCOL - Local File Access
        // ==========================================
        if ($scheme === 'file') {
            // VULNERABILITY: Can read ANY local file the web server can access
            $filepath = str_replace('file://', '', $url);
            
            if (file_exists($filepath)) {
                $content = @file_get_contents($filepath);
                echo json_encode([
                    'success' => true,
                    'url' => $url,
                    'http_code' => 200,
                    'content' => $content,
                    'length' => strlen($content),
                    'protocol' => 'file'
                ]);
            } else {
                echo json_encode([
                    'error' => 'File not found or permission denied',
                    'url' => $url
                ]);
            }
            exit;
        }
        
        // ==========================================
        // HTTP/HTTPS - Remote Resource Fetching
        // ==========================================
        
        // VULNERABILITY: No IP/domain blacklist
        // Should block: localhost, 127.0.0.1, 0.0.0.0, 169.254.169.254, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // VULNERABILITY: Follows redirects
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);         // VULNERABILITY: Too many redirects allowed
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // VULNERABILITY: No SSL verification
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_NONE); // VULNERABILITY: Accepts HTTP/0.9
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // If HTTP request succeeded
        if ($response !== false && $http_code > 0) {
            echo json_encode([
                'success' => true,
                'url' => $url,
                'http_code' => $http_code,
                'content' => $response,
                'length' => strlen($response),
                'protocol' => 'http'
            ]);
            exit;
        }
        
        // ==========================================
        // RAW TCP - Port Scanning & Banner Grabbing
        // ==========================================
        
        // VULNERABILITY: If HTTP fails, tries raw TCP connection
        
        $socket = @fsockopen($host, $port, $errno, $errstr, 3);
        
        if ($socket) {
            stream_set_timeout($socket, 2);
            $banner = stream_get_contents($socket, 4096); // Simplified - read up to 4KB
            fclose($socket);
            
            // Clean banner for display
            $banner_display = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '.', $banner);
            
            echo json_encode([
                'success' => true,
                'url' => $url,
                'port_open' => true,
                'port' => $port,
                'banner' => base64_encode($banner),
                'banner_text' => $banner_display,
                'banner_hex' => bin2hex(substr($banner, 0, 256)),
                'length' => strlen($banner),
                'protocol' => 'raw_tcp',
                'note' => 'Non-HTTP service detected - raw TCP banner shown'
            ]);
            exit;
        }
        
        // ==========================================
        // CONNECTION FAILED
        // ==========================================
        echo json_encode([
            'error' => "Connection failed: $errstr (errno: $errno)",
            'url' => $url,
            'port_open' => false,
            'port' => $port,
            'curl_error' => $error
        ]);
        exit;
    }
    break;
}