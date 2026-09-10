<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['GET']);
require_once __DIR__ . '/../../src/bootstrap.php';
json_response(['user' => require_auth($pdo)]);
