<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['vendor', 'admin']);
$data = request_json();
require_fields($data, ['delivery_id', 'status']);
if (!in_array($data['status'], ['open', 'assigned', 'en_route', 'delivered', 'cancelled'], true)) { json_response(['error' => 'Invalid delivery status'], 422); }
$query = $pdo->prepare('UPDATE delivery_requests d LEFT JOIN vendors v ON v.id = d.vendor_id SET d.status = :status, d.vendor_id = COALESCE(d.vendor_id, (SELECT id FROM vendors WHERE user_id = :user_id LIMIT 1)) WHERE d.id = :id AND (:is_admin = 1 OR d.vendor_id IS NULL OR v.user_id = :user_id)');
$query->execute(['status' => $data['status'], 'user_id' => $user['id'], 'id' => $data['delivery_id'], 'is_admin' => $user['role'] === 'admin' ? 1 : 0]);
json_response(['updated' => $query->rowCount() > 0]);
