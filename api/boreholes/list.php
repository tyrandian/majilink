<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['GET']);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo);
$county = $_GET['county'] ?? null;
$params = [];
$sql = 'SELECT id, name, county, location_text, latitude, longitude, status, created_at FROM boreholes WHERE ' . visibility_clause($pdo, $user, 'administrative_unit_id', 'county', $params);
if ($county) { $sql .= ' AND county = :county'; $params['county'] = $county; }
$sql .= ' ORDER BY name'; $query = $pdo->prepare($sql); $query->execute($params);
json_response(['boreholes' => $query->fetchAll()]);
