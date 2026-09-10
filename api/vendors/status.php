<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['POST', 'PATCH']);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['admin']);
$data = request_json();
require_fields($data, ['vendor_id', 'status']);
if (!in_array($data['status'], ['active', 'paused', 'suspended'], true)) { json_response(['error' => 'Invalid vendor status'], 422); }
$query = $pdo->prepare('UPDATE vendors SET status = :status, verified = :verified WHERE id = :id');
$query->execute(['status' => $data['status'], 'verified' => isset($data['verified']) ? (int) (bool) $data['verified'] : 0, 'id' => $data['vendor_id']]);
json_response(['updated' => $query->rowCount() > 0]);
