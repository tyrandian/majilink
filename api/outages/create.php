<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['POST']);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['resident', 'operator', 'admin']);
$data = request_json();
require_fields($data, ['county', 'area', 'description']);
$query = $pdo->prepare('INSERT INTO outage_reports (reporter_id, county, area, administrative_unit_id, latitude, longitude, description) VALUES (:reporter_id, :county, :area, :administrative_unit_id, :latitude, :longitude, :description)');
$query->execute(['reporter_id' => $user['id'], 'county' => $data['county'], 'area' => $data['area'], 'administrative_unit_id' => $data['administrative_unit_id'] ?? null, 'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null, 'description' => $data['description']]);
json_response(['outage_id' => (int) $pdo->lastInsertId(), 'status' => 'reported'], 201);
