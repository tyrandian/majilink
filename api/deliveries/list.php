<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['GET']);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo);
$sql = 'SELECT d.*, v.business_name AS vendor_name FROM delivery_requests d LEFT JOIN vendors v ON v.id = d.vendor_id';
$params = [];
if ($user['role'] === 'resident') { $sql .= ' WHERE d.resident_id = :user_id'; $params['user_id'] = $user['id']; }
elseif ($user['role'] === 'vendor') { $sql .= ' WHERE d.status IN ("open", "assigned", "en_route") AND d.county = :scope_county AND (d.vendor_id IS NULL OR v.user_id = :user_id)'; $params['scope_county'] = $user['county']; $params['user_id'] = $user['id']; }
elseif ($user['role'] === 'operator') { $sql .= ' WHERE ' . visibility_clause($pdo, $user, 'd.administrative_unit_id', 'd.county', $params); }
if (isset($_GET['status'])) { $sql .= str_contains($sql, 'WHERE') ? ' AND' : ' WHERE'; $sql .= ' d.status = :status'; $params['status'] = $_GET['status']; }
$sql .= ' ORDER BY d.created_at DESC';
$query = $pdo->prepare($sql); $query->execute($params);
json_response(['deliveries' => $query->fetchAll()]);
