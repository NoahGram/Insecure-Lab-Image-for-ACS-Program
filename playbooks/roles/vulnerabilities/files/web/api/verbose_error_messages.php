<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (!function_exists('verbose_database_error')) {
    function verbose_database_error(): string
    {
        return 'Database connection failed: ' . mysqli_connect_error() .
            ' (Host: ' . ini_get('mysqli.default_host') .
            ', Port: ' . ini_get('mysqli.default_port') . ')';
    }
}