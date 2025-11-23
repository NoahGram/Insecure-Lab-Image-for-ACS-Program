<?php
// VULNERABILITY: SSRF - Server-Side Request Forgery
// Add the fetch_resource action handler

if ($action === 'fetch_resource') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['url'])) {
        $url = $_POST['url'] ?? $_GET['url'] ?? '';
        
        if (empty($url)) {
            echo json_encode(['error' => 'No URL provided']);
            exit;
        }

        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
        
        // FILE:// PROTOCOL - Local File Access
        if ($scheme === 'file') {
            $filepath = str_replace('file://', '', $url);
            
            if (file_exists($filepath)) {
                $content = @file_get_contents($filepath);
                echo json_encode([
                    'success' => true,
                    'url' => $url,
                    'http_code' => 200,
                    'content' => $content,
                    'length' => strlen($content),
                    'protocol' => 'file'
                ]);
            } else {
                echo json_encode([
                    'error' => 'File not found or permission denied',
                    'url' => $url
                ]);
            }
            exit;
        }
        
        // HTTP/HTTPS - Remote Resource Fetching
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_NONE);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $http_code > 0) {
            echo json_encode([
                'success' => true,
                'url' => $url,
                'http_code' => $http_code,
                'content' => $response,
                'length' => strlen($response),
                'protocol' => 'http'
            ]);
            exit;
        }
        
        // RAW TCP - Port Scanning & Banner Grabbing
        $socket = @fsockopen($host, $port, $errno, $errstr, 3);
        
        if ($socket) {
            stream_set_timeout($socket, 2);
            $banner = stream_get_contents($socket, 4096);
            fclose($socket);
            
            $banner_display = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '.', $banner);
            
            echo json_encode([
                'success' => true,
                'url' => $url,
                'port_open' => true,
                'port' => $port,
                'banner' => base64_encode($banner),
                'banner_text' => $banner_display,
                'banner_hex' => bin2hex(substr($banner, 0, 256)),
                'length' => strlen($banner),
                'protocol' => 'raw_tcp',
                'note' => 'Non-HTTP service detected - raw TCP banner shown'
            ]);
            exit;
        }
        
        echo json_encode([
            'error' => "Connection failed: $errstr (errno: $errno)",
            'url' => $url,
            'port_open' => false,
            'port' => $port,
            'curl_error' => $error
        ]);
        exit;
    }
}