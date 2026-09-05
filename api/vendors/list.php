<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo);
$county = $_GET['county'] ?? null;
$params = [];
$sql = 'SELECT id, business_name, phone, county, service_radius_km, verified, status FROM vendors WHERE status = "active" AND ' . visibility_clause($pdo, $user, 'administrative_unit_id', 'county', $params);
if ($county) { $sql .= ' AND county = :county'; $params['county'] = $county; }
$sql .= ' ORDER BY verified DESC, business_name';
$query = $pdo->prepare($sql); $query->execute($params);
json_response(['vendors' => $query->fetchAll()]);
