<?php
$cookie_params = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
];

session_set_cookie_params($cookie_params);

session_start();