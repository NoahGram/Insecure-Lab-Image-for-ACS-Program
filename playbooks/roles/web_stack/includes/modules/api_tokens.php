<?php
define('JWT_SECRET', 'change_this_to_a_long_random_secret_key');

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data)
{
    return base64_decode(strtr($data, '-_', '+/'));
}


function jwt_token_builder(array $user_row): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload = [
        'user_id' => $user_row['id'],
        'username' => $user_row['username'],
        'email' => $user_row['email'] ?? '',
        'role' => $user_row['role'],
        'issued_at' => time(),
        'exp' => time() + 3600 // Expires in 1 hour
    ];
    $header_enc = base64url_encode(json_encode($header));
    $payload_enc = base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header_enc.$payload_enc", JWT_SECRET, true);
    $signature_enc = base64url_encode($signature);
    return "$header_enc.$payload_enc.$signature_enc";
}

function verify_secure_api_token($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    list($header_enc, $payload_enc, $signature_enc) = $parts;
    $header = json_decode(base64url_decode($header_enc), true);
    $payload = json_decode(base64url_decode($payload_enc), true);

    if (!$header || !$payload || !isset($payload['user_id'])) {
        return false;
    }

    // Verify Algorithm
    if (($header['alg'] ?? '') !== 'HS256') {
        return false;
    }

    // Verify Expiration
    if (!isset($payload['exp']) || time() >= $payload['exp']) {
        return false;
    }

    $expected_signature = base64url_encode(
        hash_hmac('sha256', "$header_enc.$payload_enc", JWT_SECRET, true)
    );

    if (!hash_equals($expected_signature, $signature_enc)) {
        return false;
    }

    $_SESSION['username'] = $payload['username'];
    $_SESSION['email'] = $payload['email'] ?? '';
    $_SESSION['role'] = $payload['role'] ?? 'user';
    $_SESSION['user_id'] = $payload['user_id'];
    $_SESSION['login_time'] = $payload['issued_at'];
    $_SESSION['authenticated_via'] = 'api_token';

    return true;
}

// Get the Authorization header from apache_request_headers or $_SERVER
$auth_header = null;
if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
    }
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
}

if ($auth_header && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    verify_secure_api_token($matches[1]);
}
