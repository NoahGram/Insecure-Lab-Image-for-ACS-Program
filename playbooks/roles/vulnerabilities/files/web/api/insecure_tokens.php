<?php
// VULNERABILITY: Non-expiring API tokens exposed in URLs

// Check if API token is provided in URL
if (isset($_GET['api_token']) && !empty($_GET['api_token'])) {
    $provided_token = $_GET['api_token'];
    
    // Decode the token (insecure: base64 is not encryption!)
    $token_data = json_decode(base64_decode($provided_token), true);
    
    if ($token_data && isset($token_data['user_id'])) {
        // VULNERABILITY: No expiration check!
        // if (isset($token_data['expires_at']) && time() > $token_data['expires_at']) {
        //     die('Token expired');
        // }
        
        // Restore session from token
        $_SESSION['username'] = $token_data['username'];
        $_SESSION['email'] = $token_data['email'] ?? '';
        $_SESSION['role'] = $token_data['role'] ?? 'user';
        $_SESSION['user_id'] = $token_data['user_id'];
        $_SESSION['login_time'] = $token_data['issued_at'];
        $_SESSION['authenticated_via'] = 'api_token';
    }
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
                : hash_equals((string) $stored, (string) $password);

            if ($ok) {
                // Generate insecure API token (no expiration)
                $api_token = base64_encode(json_encode([
                    'user_id' => $row['id'],
                    'username' => $row['username'],
                    'role' => $row['role'],
                    'issued_at' => time()
                    // MISSING: 'expires_at' => time() + 3600
                ]));

                $_SESSION['api_token'] = $api_token;
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'] ?? 'user';
                $_SESSION['login_time'] = time();

                // VULNERABILITY: Token exposed in URL
                header('Location: ?page=' . rawurlencode($page) . '&api_token=' . $api_token);
                exit;
            }
        }
        $stmt->close();
        $conn->close();
    }
}