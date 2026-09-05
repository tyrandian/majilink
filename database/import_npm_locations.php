<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
$settings = $config['db'];
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $settings['host'], $settings['port'], $settings['name'], $settings['charset']);
$pdo = new PDO($dsn, $settings['user'], $settings['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$dataPath = __DIR__ . '/data/kenya-locations.json';
if (!is_file($dataPath)) {
    throw new RuntimeException('Run node database/export_npm_locations.mjs first.');
}
$payload = json_decode((string) file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR);
$insert = $pdo->prepare(
    'INSERT INTO administrative_units (parent_id, unit_type, code, name, source)
     VALUES (:parent_id, :unit_type, :code, :name, :source)
     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), code = COALESCE(VALUES(code), code), source = VALUES(source)'
);
$ids = [];
$pdo->beginTransaction();
try {
    foreach ($payload['units'] as $unit) {
        $parentId = $unit['parent_key'] === null ? null : ($ids[$unit['parent_key']] ?? null);
        if ($unit['parent_key'] !== null && $parentId === null) continue;
        $insert->execute(['parent_id' => $parentId, 'unit_type' => $unit['unit_type'], 'code' => $unit['code'], 'name' => $unit['name'], 'source' => $unit['source']]);
        $ids[$unit['key']] = (int) $pdo->lastInsertId();
    }
    $pdo->commit();
    echo 'Imported ' . count($ids) . " Kenya administrative units from kenya-locations.\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
