<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['POST']);
require_once __DIR__ . '/../../src/bootstrap.php';
$admin = require_auth($pdo, ['admin', 'country_manager']);
$data = request_json();
require_fields($data, ['name', 'phone', 'password', 'role', 'county', 'administrative_unit_id']);
$roles = ['country_manager', 'county_manager', 'constituency_manager', 'ward_manager'];
if (!in_array($data['role'], $roles, true)) { json_response(['error' => 'Invalid manager role'], 422); }
if ($admin['role'] !== 'admin' && $data['role'] === 'country_manager') { json_response(['error' => 'Only admins can create country managers'], 403); }
$userQuery = $pdo->prepare('INSERT INTO users (name, phone, email, password_hash, role, county, administrative_unit_id) VALUES (:name, :phone, :email, :password_hash, :role, :county, :unit_id)');
$userQuery->execute(['name' => $data['name'], 'phone' => $data['phone'], 'email' => $data['email'] ?? null, 'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT), 'role' => $data['role'], 'county' => $data['county'], 'unit_id' => $data['administrative_unit_id']]);
$userId = (int) $pdo->lastInsertId();
$assignment = $pdo->prepare('INSERT INTO manager_assignments (user_id, scope_unit_id, phone, email) VALUES (:user_id, :scope_unit_id, :phone, :email)');
$assignment->execute(['user_id' => $userId, 'scope_unit_id' => $data['administrative_unit_id'], 'phone' => $data['phone'], 'email' => $data['email'] ?? null]);
json_response(['manager_id' => $userId, 'role' => $data['role'], 'administrative_unit_id' => (int) $data['administrative_unit_id']], 201);