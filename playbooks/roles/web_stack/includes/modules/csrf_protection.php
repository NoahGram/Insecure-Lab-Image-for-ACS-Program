<?php
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
    
function verify_csrf(): bool
{
    $sent = $_REQUEST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (empty($stored)) {
        return false;
    }
    return is_string($sent) && hash_equals((string) $stored, (string) $sent);
}

function enforce_csrf(string $action = ''): void
{
    if (!verify_csrf()) {
        if (!empty($action)) {
            ActivityLogger::logCsrfFailure($action);
        }
        http_response_code(400);
        header_remove('Set-Cookie');
        die('CSRF verification failed.');
    }
}