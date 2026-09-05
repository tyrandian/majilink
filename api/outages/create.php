<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['resident', 'operator', 'admin']);
$data = request_json();
require_fields($data, ['county', 'area', 'description']);
$query = $pdo->prepare('INSERT INTO outage_reports (reporter_id, county, area, administrative_unit_id, description) VALUES (:reporter_id, :county, :area, :administrative_unit_id, :description)');
$query->execute(['reporter_id' => $user['id'], 'county' => $data['county'], 'area' => $data['area'], 'administrative_unit_id' => $data['administrative_unit_id'] ?? null, 'description' => $data['description']]);
json_response(['outage_id' => (int) $pdo->lastInsertId(), 'status' => 'reported'], 201);
