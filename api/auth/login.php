<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['POST']);
require_once __DIR__ . '/../../src/bootstrap.php';
$data = request_json();
require_fields($data, ['phone', 'password']);
$query = $pdo->prepare('SELECT * FROM users WHERE phone = :phone');
$query->execute(['phone' => $data['phone']]);
$user = $query->fetch();
if (!$user || !password_verify($data['password'], $user['password_hash'])) {
    json_response(['error' => 'Invalid credentials'], 401);
}
unset($user['password_hash']);
$user['id'] = (int) $user['id'];
json_response(['user' => $user, 'token' => issue_token($pdo, $user['id'])]);
