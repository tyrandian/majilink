<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['GET']);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo);
$query = $pdo->prepare('SELECT b.id, b.period_start, b.period_end, b.litres, b.amount, b.status, b.due_date, w.name AS utility_name FROM bills b LEFT JOIN water_utilities w ON w.id = b.utility_id WHERE b.user_id = :user_id ORDER BY b.period_end DESC');
$query->execute(['user_id' => $user['id']]);
json_response(['bills' => $query->fetchAll()]);
