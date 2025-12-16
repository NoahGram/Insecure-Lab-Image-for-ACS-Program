<?php
if ($action === 'api_test') {
    if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access Denied']);
        exit;
    }

    if (isset($_GET['show_session'])) {
        echo json_encode([
            'session_status' => session_status(),
            'session_id_hash' => hash('sha256', session_id()),
            'user' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'timestamp' => time()
        ]);
        exit;
    }

    if (isset($_GET['show_config'])) {
        echo json_encode([
            'app_version' => '1.2.0',
            'php_version_major' => PHP_MAJOR_VERSION,
            'extensions_count' => count(get_loaded_extensions()),
            'upload_limit' => ini_get('upload_max_filesize'),
            'mode' => 'production'
        ]);
        exit;
    }
}
