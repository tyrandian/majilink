<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['GET']);
require_once __DIR__ . '/../../src/bootstrap.php';
require_auth($pdo, ['admin']);

$query = $pdo->query(
    'SELECT u.id, u.name, u.phone, u.email, u.role, u.county, u.administrative_unit_id,
            a.unit_type AS scope_type, a.name AS scope_name
     FROM users u LEFT JOIN administrative_units a ON a.id = u.administrative_unit_id
     ORDER BY u.name'
);
json_response(['users' => $query->fetchAll()]);
