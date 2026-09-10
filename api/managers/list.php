<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
require_http_method(['GET']);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo);

$query = $pdo->prepare(
    'SELECT u.id, u.name, u.phone, u.email, u.role, u.county, u.administrative_unit_id,
            a.name AS scope_name, a.unit_type AS scope_type
     FROM users u LEFT JOIN administrative_units a ON a.id = u.administrative_unit_id
     WHERE u.role IN ("country_manager", "county_manager", "constituency_manager", "ward_manager", "admin")
     AND (u.county = :county OR u.role IN ("country_manager", "admin"))
     ORDER BY FIELD(u.role, "admin", "country_manager", "county_manager", "constituency_manager", "ward_manager"), u.name'
);
$query->execute(['county' => $user['county']]);
json_response(['managers' => $query->fetchAll()]);
