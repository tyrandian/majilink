<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['POST']);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['resident', 'admin']);
$data = request_json();
require_fields($data, ['county', 'location_text', 'litres', 'preferred_date', 'payment_method']);
if (!in_array($data['payment_method'], ['on_delivery', 'upfront'], true)) { json_response(['error' => 'Invalid payment method'], 422); }
$query = $pdo->prepare('INSERT INTO delivery_requests (resident_id, county, location_text, administrative_unit_id, latitude, longitude, litres, preferred_date, notes, payment_method) VALUES (:resident_id, :county, :location_text, :administrative_unit_id, :latitude, :longitude, :litres, :preferred_date, :notes, :payment_method)');
$query->execute(['resident_id' => $user['id'], 'county' => $data['county'], 'location_text' => $data['location_text'], 'administrative_unit_id' => $data['administrative_unit_id'] ?? null, 'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null, 'litres' => $data['litres'], 'preferred_date' => $data['preferred_date'], 'notes' => $data['notes'] ?? null, 'payment_method' => $data['payment_method']]);
json_response(['delivery_id' => (int) $pdo->lastInsertId(), 'status' => 'open', 'payment_method' => $data['payment_method'], 'payment_status' => $data['payment_method'] === 'upfront' ? 'pending' : 'pending'], 201);
