<?php
// VULNERABILITY: API testing endpoint left in production
// Exposes internal application state

if ($action === 'api_test') {
    if (isset($_GET['show_session'])) {
        echo json_encode([
            'session' => $_SESSION,
            'timestamp' => time()
        ]);
        exit;
    }
    
    if (isset($_GET['show_config'])) {
        echo json_encode([
            'php_version' => phpversion(),
            'loaded_extensions' => get_loaded_extensions(),
            'include_path' => get_include_path(),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ]);
        exit;
    }
}