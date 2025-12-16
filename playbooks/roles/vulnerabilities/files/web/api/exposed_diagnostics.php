<?php
if ($action === 'diagnostics') {
    if (!isset($_GET['check'])) {
        echo json_encode(['error' => 'No diagnostic check specified']);
        exit;
    }
    
    $check = $_GET['check'];
    
    switch ($check) {
        case 'db':
            $conn = db_connect();
            if ($conn) {
                echo json_encode([
                    'status' => 'connected',
                    'server_info' => mysqli_get_server_info($conn),
                    'host_info' => mysqli_get_host_info($conn)
                ]);
                $conn->close();
            }
            break;
            
        case 'php':
            phpinfo();
            break;
            
        case 'env':
            echo '<pre>';
            print_r($_ENV);
            print_r(getenv());
            echo '</pre>';
            break;
    }
    exit;
}