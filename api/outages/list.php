<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$params = [];
$user = require_auth($pdo);
$sql = 'SELECT o.id, o.county, o.area, o.description, o.status, o.created_at, o.updated_at, u.name AS reporter_name FROM outage_reports o JOIN users u ON u.id = o.reporter_id WHERE ' . visibility_clause($pdo, $user, 'o.administrative_unit_id', 'o.county', $params);
if (isset($_GET['county'])) { $sql .= ' AND o.county = :county'; $params['county'] = $_GET['county']; }
if (isset($_GET['status'])) { $sql .= ' AND o.status = :status'; $params['status'] = $_GET['status']; }
$sql .= ' ORDER BY o.created_at DESC'; $query = $pdo->prepare($sql); $query->execute($params);
json_response(['outages' => $query->fetchAll()]);
