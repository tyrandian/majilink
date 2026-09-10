<?php
declare(strict_types=1);

require_once __DIR__ . '/response.php';

/** Validate the request before configuration, authentication, or database access. */
function require_http_method(array $methods): void
{
    $allowed = implode(', ', [...$methods, 'OPTIONS']);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: ' . $allowed);

    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($method === 'OPTIONS') {
        header('Allow: ' . $allowed);
        http_response_code(204);
        exit;
    }
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . $allowed);
        json_response(['error' => 'Method not allowed'], 405);
    }
}
