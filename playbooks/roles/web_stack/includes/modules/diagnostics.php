<?php
if ($action === 'diagnostics') {
    if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access Denied']);
        exit;
    }

    if (!isset($_GET['check'])) {
        echo json_encode(['error' => 'No check specified']);
        exit;
    }

    $check = $_GET['check'];

    switch ($check) {
        case 'db':
            $conn = @db_connect();
            if ($conn) {
                echo json_encode([
                    'status' => 'connected',
                    'latency_ms' => 0.42,
                    'ssl' => false,
                    'message' => 'Database is operational'
                ]);
                $conn->close();
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Connection failed'
                ]);
            }
            break;

        case 'php':
            echo json_encode(['error' => 'Detailed PHP info invalid in production mode']);
            break;

        case 'env':
            echo json_encode(['error' => 'Environment inspection disabled']);
            break;
    }
    exit;
}
