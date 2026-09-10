<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['POST']);
require_once __DIR__ . '/../../src/bootstrap.php';

$data = request_json();
require_fields($data, ['name', 'phone', 'password', 'county']);
if (strlen((string) $data['password']) < 8) {
    json_response(['error' => 'Password must be at least 8 characters'], 422);
}

$query = $pdo->prepare('INSERT INTO users (name, phone, email, password_hash, role, county, administrative_unit_id) VALUES (:name, :phone, :email, :password_hash, :role, :county, :administrative_unit_id)');
try {
    $query->execute([
        'name' => $data['name'], 'phone' => $data['phone'], 'email' => $data['email'] ?? null,
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT), 'role' => 'resident', 'county' => $data['county'], 'administrative_unit_id' => $data['administrative_unit_id'] ?? null,
    ]);
} catch (PDOException $exception) {
    if ($exception->errorInfo[1] ?? null === 1062) {
        json_response(['error' => 'Phone or email is already registered'], 409);
    }
    throw $exception;
}
$userId = (int) $pdo->lastInsertId();
json_response(['user' => ['id' => $userId, 'name' => $data['name'], 'phone' => $data['phone'], 'role' => 'resident', 'county' => $data['county']], 'token' => issue_token($pdo, $userId)], 201);
