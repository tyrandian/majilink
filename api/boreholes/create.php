<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['POST']);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['admin']);
$data = request_json();
require_fields($data, ['name', 'county', 'location_text', 'status']);
if (!in_array($data['status'], ['working', 'limited', 'offline', 'unknown'], true)) { json_response(['error' => 'Invalid borehole status'], 422); }
$query = $pdo->prepare('INSERT INTO boreholes (name, county, location_text, administrative_unit_id, latitude, longitude, status, operator_id) VALUES (:name, :county, :location_text, :administrative_unit_id, :latitude, :longitude, :status, :operator_id)');
$query->execute(['name' => $data['name'], 'county' => $data['county'], 'location_text' => $data['location_text'], 'administrative_unit_id' => $data['administrative_unit_id'] ?? null, 'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null, 'status' => $data['status'], 'operator_id' => $user['id']]);
json_response(['borehole_id' => (int) $pdo->lastInsertId()], 201);
