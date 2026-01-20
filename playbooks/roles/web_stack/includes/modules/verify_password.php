<?php
function verify_password($password, $stored)
{
    $ok = (strlen($stored) >= 60 && (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2b$') || str_starts_with($stored, '$argon2')))
                                ? password_verify($password, $stored)
                                : false;
    return $ok;
}