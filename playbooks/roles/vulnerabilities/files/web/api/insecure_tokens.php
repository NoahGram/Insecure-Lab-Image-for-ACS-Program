<?php
if (isset($_GET['api_token']) && !empty($_GET['api_token'])) {
    $provided_token = $_GET['api_token'];

    $token_data = json_decode(base64_decode($provided_token), true);

    if ($token_data && isset($token_data['user_id'])) {

        $_SESSION['username'] = $token_data['username'];
        $_SESSION['email'] = $token_data['email'] ?? '';
        $_SESSION['role'] = $token_data['role'] ?? 'user';
        $_SESSION['user_id'] = $token_data['user_id'];
        $_SESSION['login_time'] = $token_data['issued_at'];
        $_SESSION['authenticated_via'] = 'api_token';
    }
}

if (!function_exists('generate_insecure_api_token')) {
    function generate_insecure_api_token(array $user_row): string
    {
        return base64_encode(json_encode([
            'user_id' => $user_row['id'],
            'username' => $user_row['username'],
            'email' => $user_row['email'] ?? '',
            'role' => $user_row['role'],
            'issued_at' => time()
        ]));
    }
}