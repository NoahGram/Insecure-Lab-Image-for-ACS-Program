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

// Override the login redirect to include API token in URL
// This hooks into the existing login flow in actions.php
if (!function_exists('generate_insecure_api_token')) {
    function generate_insecure_api_token(array $user_row): string {
        return base64_encode(json_encode([
            'user_id' => $user_row['id'],
            'username' => $user_row['username'],
            'email' => $user_row['email'] ?? '',
            'role' => $user_row['role'],
            'issued_at' => time()
            // MISSING: 'expires_at' => time() + 3600
        ]));
    }
}