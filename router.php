<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$publicRoot = __DIR__ . '/frontend/public';

function sendFile(string $path) {
    $mimeTypes = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'json' => 'application/json',
        'txt' => 'text/plain; charset=utf-8',
    ];
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
    readfile($path);
    exit;
}

$normalizedPath = rawurldecode($uri);
$staticFile = realpath($publicRoot . $normalizedPath);
if ($staticFile && is_file($staticFile) && str_starts_with($staticFile, realpath($publicRoot))) {
    sendFile($staticFile);
}

if ($normalizedPath === '/' || $normalizedPath === '/index.html') {
    sendFile($publicRoot . '/index.html');
}

if (str_starts_with($normalizedPath, '/backend/php/')) {
    $script = __DIR__ . $normalizedPath;
    if (!file_exists($script) || !is_file($script)) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    require $script;
    exit;
}

header('HTTP/1.1 404 Not Found');
echo 'Not found';
