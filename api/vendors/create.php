<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_manager($pdo);
$data = request_json();
require_fields($data, ['business_name', 'phone', 'county']);
$scopeId = isset($data['administrative_unit_id']) ? (int) $data['administrative_unit_id'] : null;
if (!manager_can_manage_unit($pdo, $user, $scopeId)) { json_response(['error' => 'Managers may only add vendors within their assigned management level'], 403); }
$query = $pdo->prepare('INSERT INTO vendors (user_id, business_name, phone, county, administrative_unit_id, service_radius_km) VALUES (:user_id, :business_name, :phone, :county, :administrative_unit_id, :radius)');
$query->execute(['user_id' => $user['id'], 'business_name' => $data['business_name'], 'phone' => $data['phone'], 'county' => $data['county'], 'administrative_unit_id' => $scopeId, 'radius' => $data['service_radius_km'] ?? 15]);
json_response(['vendor_id' => (int) $pdo->lastInsertId()], 201);
