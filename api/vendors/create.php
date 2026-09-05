<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['vendor', 'admin']);
$data = request_json();
require_fields($data, ['business_name', 'phone', 'county']);
$query = $pdo->prepare('INSERT INTO vendors (user_id, business_name, phone, county, service_radius_km) VALUES (:user_id, :business_name, :phone, :county, :radius)');
$query->execute(['user_id' => $user['id'], 'business_name' => $data['business_name'], 'phone' => $data['phone'], 'county' => $data['county'], 'radius' => $data['service_radius_km'] ?? 15]);
json_response(['vendor_id' => (int) $pdo->lastInsertId()], 201);
