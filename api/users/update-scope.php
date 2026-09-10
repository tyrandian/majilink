<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['POST', 'PATCH']);
require_once __DIR__ . '/../../src/bootstrap.php';
require_auth($pdo, ['admin']);
$data = request_json();
require_fields($data, ['user_id']);

$scopeId = $data['administrative_unit_id'] ?? null;
if ($scopeId !== null) {
    $scopeQuery = $pdo->prepare('SELECT id FROM administrative_units WHERE id = :id');
    $scopeQuery->execute(['id' => $scopeId]);
    if (!$scopeQuery->fetchColumn()) {
        json_response(['error' => 'Administrative unit does not exist'], 422);
    }
}

$query = $pdo->prepare('UPDATE users SET administrative_unit_id = :administrative_unit_id WHERE id = :user_id');
$query->execute(['administrative_unit_id' => $scopeId, 'user_id' => $data['user_id']]);
json_response(['updated' => $query->rowCount() > 0]);
