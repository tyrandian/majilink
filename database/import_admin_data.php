<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
$settings = $config['db'];
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $settings['host'], $settings['port'], $settings['name'], $settings['charset']);
$pdo = new PDO($dsn, $settings['user'], $settings['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$countyUrl = 'https://raw.githubusercontent.com/mbithuka/Counties/main/restructured_data.json';
$subLocationUrl = 'https://raw.githubusercontent.com/kelvinsnir/Kenya-Counties-json/main/sublocation.json';
$source = static function (string $url): mixed {
    $json = file_get_contents($url);
    if ($json === false) {
        throw new RuntimeException("Unable to download {$url}");
    }
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    return $data;
};

$insert = $pdo->prepare(
    'INSERT INTO administrative_units (parent_id, unit_type, code, name, source)
     VALUES (:parent_id, :unit_type, :code, :name, :source)
     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), code = COALESCE(VALUES(code), code)'
);
$unit = static function (?int $parentId, string $type, string $name, ?string $code = null) use ($insert, $pdo): int {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '') {
        throw new InvalidArgumentException('Administrative unit names cannot be empty');
    }
    $insert->execute(['parent_id' => $parentId, 'unit_type' => $type, 'code' => $code, 'name' => $name, 'source' => 'public administrative datasets']);
    return (int) $pdo->lastInsertId();
};
$normalise = static fn (string $value): string => strtolower(preg_replace('/[^a-z0-9]+/', '', $value));

$pdo->beginTransaction();
try {
    $countyIds = [];
    $constituencyIds = [];
    $hierarchy = $source($countyUrl);
    foreach ($hierarchy as $countyName => $countyData) {
        $countyId = $unit(null, 'county', ucwords(strtolower(str_replace('-', ' ', $countyName))), (string) ($countyData['County'] ?? ''));
        $countyIds[$normalise($countyName)] = $countyId;
        foreach (($countyData['Constituencies'] ?? []) as $constituencyName => $constituencyData) {
            $constituencyId = $unit($countyId, 'constituency', ucwords(strtolower($constituencyName)));
            $constituencyIds[$normalise($countyName) . ':' . $normalise($constituencyName)] = $constituencyId;
            foreach (($constituencyData['Ward'] ?? []) as $wardName) {
                $unit($constituencyId, 'ward', ucwords(strtolower((string) $wardName)));
            }
        }
    }

    $subLocations = $source($subLocationUrl);
    foreach ($subLocations as $record) {
        if (!is_array($record)) {
            continue;
        }
        $countyName = (string) ($record['county'] ?? $record['county_name'] ?? '');
        $constituencyName = (string) ($record['subcounty'] ?? $record['sub_county'] ?? $record['constituency'] ?? '');
        $locationName = (string) ($record['location'] ?? $record['location_name'] ?? '');
        $subLocationName = (string) ($record['sublocation'] ?? $record['sub_location'] ?? $record['sub_location_name'] ?? '');
        $countyId = $countyIds[$normalise($countyName)] ?? null;
        $constituencyId = $countyId ? ($constituencyIds[$normalise($countyName) . ':' . $normalise($constituencyName)] ?? null) : null;
        if ($constituencyId && $locationName !== '') {
            $locationId = $unit($constituencyId, 'location', $locationName);
            if ($subLocationName !== '') {
                $unit($locationId, 'sub_location', $subLocationName);
            }
        }
    }

    $villageFile = __DIR__ . '/data/villages.csv';
    if (is_file($villageFile) && ($handle = fopen($villageFile, 'rb')) !== false) {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($headers, $row);
            if (!is_array($record) || trim((string) ($record['village'] ?? '')) === '') {
                continue;
            }
            $countyKey = $normalise((string) ($record['county'] ?? ''));
            $constituencyKey = $countyKey . ':' . $normalise((string) ($record['constituency'] ?? ''));
            $parentId = $constituencyIds[$constituencyKey] ?? null;
            if ($parentId && trim((string) ($record['location'] ?? '')) !== '') {
                $parentId = $unit($parentId, 'location', (string) $record['location']);
            }
            if ($parentId && trim((string) ($record['sub_location'] ?? '')) !== '') {
                $parentId = $unit($parentId, 'sub_location', (string) $record['sub_location']);
            }
            if ($parentId) {
                $unit($parentId, 'village', (string) $record['village']);
            }
        }
        fclose($handle);
    }

    $pdo->commit();
    echo "Imported counties, constituencies, wards, locations, and sub-locations.\n";
    echo "Village records require the official village CSV import described in database/data/villages.csv.example.\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
