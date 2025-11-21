<?php
session_start();

$editable_url_parameters = '/var/www/roles/vulnerabilities/web_stack/editable_url_parameters.php';

// If the file exists, require it
if (is_readable($editable_url_parameters)) {
    require_once $editable_url_parameters;
}


function h($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_admin() {
    if (function_exists('get_url_parameter_role')) {
        $role = get_url_parameter_role(); 
        if ($role !== null) {
            return $role === 'admin';
        }
    }
    return ($_SESSION['role'] ?? '') === 'admin';
}
