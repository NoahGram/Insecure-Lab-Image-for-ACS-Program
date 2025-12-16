<?php
if ($action === 'fetch_resource') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $url = $_POST['url'] ?? '';

    if (empty($url)) {
        echo json_encode(['error' => 'No URL provided']);
        exit;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['error' => 'Invalid URL format']);
        exit;
    }

    $parsed = parse_url($url);
    $scheme = strtolower($parsed['scheme'] ?? '');
    $host = $parsed['host'] ?? '';

    if (!in_array($scheme, ['http', 'https'], true)) {
        echo json_encode(['error' => 'Protocol not allowed. Only HTTP/S supported.']);
        exit;
    }

    $ip = gethostbyname($host);

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        echo json_encode(['error' => 'Could not resolve host']);
        exit;
    }

    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        echo json_encode(['error' => 'Access to internal/private resources is forbidden']);
        exit;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response !== false) {
        echo json_encode([
            'success' => true,
            'url' => $url,
            'http_code' => $http_code,
            'content' => $response,
            'length' => strlen($response),
            'protocol' => $scheme
        ]);
    } else {
        echo json_encode([
            'error' => "Request failed: $error",
            'url' => $url
        ]);
    }
    exit;
}
