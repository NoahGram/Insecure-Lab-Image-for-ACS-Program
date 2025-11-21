<?php
// Vulnerability: Hidden role field in registration
// Allows users to set their own role via hidden form field

if (!function_exists('get_user_role_from_request')) {
    function get_user_role_from_request(): string {
        // Check if role is provided in request (from hidden field or parameter)
        $role = $_REQUEST['role'] ?? 'user';
        
        // Validate against allowed roles
        $allowed_roles = ['user', 'admin'];
        if (!in_array($role, $allowed_roles, true)) {
            $role = 'user';
        }
        
        return $role;
    }
}
