<?php
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
         || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

$cookie_params = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $is_https,
    'httponly' => true,
    'samesite' => 'Lax'
];

session_set_cookie_params($cookie_params);

session_start();