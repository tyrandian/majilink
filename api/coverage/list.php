<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo);
$params = [];
$where = visibility_clause($pdo, $user, 'c.administrative_unit_id', 'u.county', $params);
$query = $pdo->prepare('SELECT c.id, c.connected_households, c.total_households, ROUND((c.connected_households / NULLIF(c.total_households, 0)) * 100, 2) AS connected_percent, ROUND(100 - ((c.connected_households / NULLIF(c.total_households, 0)) * 100), 2) AS unconnected_percent, c.source_type, u.name AS utility_name, a.name AS area_name FROM water_coverage c JOIN water_utilities u ON u.id = c.utility_id JOIN administrative_units a ON a.id = c.administrative_unit_id WHERE ' . $where . ' ORDER BY a.name');
$query->execute($params);
json_response(['coverage' => $query->fetchAll()]);
