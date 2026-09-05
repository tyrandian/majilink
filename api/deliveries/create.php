<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['resident', 'admin']);
$data = request_json();
require_fields($data, ['county', 'location_text', 'litres', 'preferred_date']);
$query = $pdo->prepare('INSERT INTO delivery_requests (resident_id, county, location_text, administrative_unit_id, latitude, longitude, litres, preferred_date, notes) VALUES (:resident_id, :county, :location_text, :administrative_unit_id, :latitude, :longitude, :litres, :preferred_date, :notes)');
$query->execute(['resident_id' => $user['id'], 'county' => $data['county'], 'location_text' => $data['location_text'], 'administrative_unit_id' => $data['administrative_unit_id'] ?? null, 'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null, 'litres' => $data['litres'], 'preferred_date' => $data['preferred_date'], 'notes' => $data['notes'] ?? null]);
json_response(['delivery_id' => (int) $pdo->lastInsertId(), 'status' => 'open'], 201);
