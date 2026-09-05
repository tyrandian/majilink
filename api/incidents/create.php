<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['resident', 'operator', 'admin', 'country_manager', 'county_manager', 'constituency_manager', 'ward_manager']);
$data = request_json();
require_fields($data, ['county', 'category', 'description']);
$categories = ['dirty_water', 'poisonous_water', 'low_pressure', 'outage', 'leak', 'other'];
if (!in_array($data['category'], $categories, true)) json_response(['error' => 'Invalid incident category'], 422);
$query = $pdo->prepare('INSERT INTO incidents (reporter_id, administrative_unit_id, county, category, description) VALUES (:reporter_id, :unit_id, :county, :category, :description)');
$query->execute(['reporter_id' => $user['id'], 'unit_id' => $data['administrative_unit_id'] ?? null, 'county' => $data['county'], 'category' => $data['category'], 'description' => $data['description']]);
json_response(['incident_id' => (int) $pdo->lastInsertId(), 'status' => 'reported'], 201);
