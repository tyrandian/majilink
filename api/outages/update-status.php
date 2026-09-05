<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['operator', 'admin']);
$data = request_json();
require_fields($data, ['outage_id', 'status']);
if (!in_array($data['status'], ['reported', 'under_review', 'confirmed', 'resolved', 'rejected'], true)) { json_response(['error' => 'Invalid outage status'], 422); }
$query = $pdo->prepare('UPDATE outage_reports SET status = :status, reviewed_by = :reviewed_by WHERE id = :id');
$query->execute(['status' => $data['status'], 'reviewed_by' => $user['id'], 'id' => $data['outage_id']]);
json_response(['updated' => $query->rowCount() > 0]);
