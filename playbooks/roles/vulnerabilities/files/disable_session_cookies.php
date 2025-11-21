<?php
// Vulnerability: Disable SameSite cookie restriction to allow cross-site requests
function session_set_cookie_params_override(array $options = []): bool {
    $options['samesite'] = 'None';
    $options['secure'] = false;
    return session_set_cookie_params($options);
}
