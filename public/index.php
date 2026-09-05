<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/response.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($requestPath === '/') {
    require __DIR__ . '/app.html';
    exit;
}

if (str_starts_with($requestPath, '/api/')) {
    $endpoint = realpath(__DIR__ . '/..' . $requestPath);
    $apiRoot = realpath(__DIR__ . '/../api');
    if ($endpoint !== false && $apiRoot !== false && str_starts_with($endpoint, $apiRoot . DIRECTORY_SEPARATOR) && is_file($endpoint)) {
        require $endpoint;
        exit;
    }
}

$asset = realpath(__DIR__ . $requestPath);
$publicRoot = realpath(__DIR__);
if ($asset !== false && $publicRoot !== false && str_starts_with($asset, $publicRoot . DIRECTORY_SEPARATOR) && is_file($asset)) {
    $mimeTypes = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'svg' => 'image/svg+xml',
    ];
    $extension = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
        readfile($asset);
        exit;
    }
}

json_response(['error' => 'Not found'], 404);
