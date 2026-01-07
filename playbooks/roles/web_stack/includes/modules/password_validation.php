<?php

function validate_password(string $password, string $password_confirm = null): array
{
    $errors = [];

    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
        return $errors; 
    }

    if (strlen($password) < 8)
        $errors[] = 'At least 8 characters.';
    if (!preg_match('/[A-Z]/', $password))
        $errors[] = 'One uppercase letter.';
    if (!preg_match('/[a-z]/', $password))
        $errors[] = 'One lowercase letter.';
    if (!preg_match('/[0-9]/', $password))
        $errors[] = 'One number.';
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password))
        $errors[] = 'One special character.';

    $common_passwords = [
        'password',
        '123456',
        '12345678',
        'qwerty',
        'abc123',
        'Password123!',
        'letmein',
        'admin',
        'welcome'
    ];

    if (in_array($password, $common_passwords, true)) {
        $errors[] = 'Password is too common.';
    }

    return $errors; 
}