<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

$parentId = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? (int) $_GET['parent_id'] : null;
$type = $_GET['type'] ?? null;
$allowedTypes = ['county', 'constituency', 'ward', 'location', 'sub_location', 'village'];
if ($type !== null && !in_array($type, $allowedTypes, true)) {
    json_response(['error' => 'Invalid administrative unit type'], 422);
}

$sql = 'SELECT id, parent_id, unit_type, code, name FROM administrative_units WHERE parent_id ' . ($parentId === null ? 'IS NULL' : '= :parent_id');
$params = $parentId === null ? [] : ['parent_id' => $parentId];
if ($type !== null) {
    $sql .= ' AND unit_type = :unit_type';
    $params['unit_type'] = $type;
}
$sql .= ' ORDER BY name';
try {
    $query = $pdo->prepare($sql);
    $query->execute($params);
} catch (PDOException $exception) {
    if (($exception->errorInfo[1] ?? null) === 1146) {
        json_response(['error' => 'Administrative hierarchy is not installed. Run database/migrations/001_administrative_hierarchy.sql.'], 503);
    }
    throw $exception;
}
json_response(['locations' => $query->fetchAll()]);
