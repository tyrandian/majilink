<?php
declare(strict_types=1);

function current_user(PDO $pdo): ?array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return null;
    }

    $hash = hash('sha256', trim($matches[1]));
    try {
        $query = $pdo->prepare(
            'SELECT u.id, u.name, u.phone, u.email, u.role, u.county, u.administrative_unit_id, u.created_at
             FROM api_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = :token_hash AND t.expires_at > UTC_TIMESTAMP()'
        );
        $query->execute(['token_hash' => $hash]);
    } catch (PDOException $exception) {
        if (($exception->errorInfo[1] ?? null) !== 1054) {
            throw $exception;
        }
        $query = $pdo->prepare(
            'SELECT u.id, u.name, u.phone, u.email, u.role, u.county, u.created_at
             FROM api_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = :token_hash AND t.expires_at > UTC_TIMESTAMP()'
        );
        $query->execute(['token_hash' => $hash]);
    }
    $user = $query->fetch() ?: null;
    if ($user !== null && !array_key_exists('administrative_unit_id', $user)) {
        $user['administrative_unit_id'] = null;
    }
    return $user;
}

function visibility_clause(PDO $pdo, array $user, string $unitColumn, string $countyColumn, array &$params): string
{
    if ($user['role'] === 'admin') {
        return '1=1';
    }

    $params['scope_county'] = $user['county'];
    $unitId = $user['administrative_unit_id'] ?? null;
    if (!$unitId) {
        return "{$unitColumn} IS NULL AND {$countyColumn} = :scope_county OR {$unitColumn} IS NOT NULL AND {$countyColumn} = :scope_county";
    }

    try {
        $query = $pdo->prepare(
            'WITH RECURSIVE descendants AS (
                SELECT id FROM administrative_units WHERE id = :unit_id
                UNION ALL
                SELECT child.id FROM administrative_units child JOIN descendants parent ON child.parent_id = parent.id
            ) SELECT id FROM descendants'
        );
        $query->execute(['unit_id' => $unitId]);
        $ids = array_map('intval', $query->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException) {
        $ids = [];
    }

    if ($ids === []) {
        return "{$countyColumn} = :scope_county";
    }
    $placeholders = [];
    foreach ($ids as $index => $id) {
        $key = "scope_unit_{$index}";
        $placeholders[] = ":{$key}";
        $params[$key] = $id;
    }
    return "({$unitColumn} IN (" . implode(', ', $placeholders) . ") OR ({$unitColumn} IS NULL AND {$countyColumn} = :scope_county))";
}

function require_auth(PDO $pdo, array $roles = []): array
{
    $user = current_user($pdo);
    if ($user === null) {
        json_response(['error' => 'Authentication required'], 401);
    }
    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        json_response(['error' => 'Insufficient permissions'], 403);
    }
    return $user;
}

function manager_roles(): array
{
    return ['country_manager', 'county_manager', 'constituency_manager', 'ward_manager', 'admin'];
}

function require_manager(PDO $pdo): array
{
    return require_auth($pdo, manager_roles());
}

function manager_can_manage_unit(PDO $pdo, array $user, ?int $unitId): bool
{
    if ($user['role'] === 'admin' || $user['role'] === 'country_manager') return true;
    if ($unitId === null || !($user['administrative_unit_id'] ?? null)) return false;
    $query = $pdo->prepare(
        'WITH RECURSIVE descendants AS (
            SELECT id FROM administrative_units WHERE id = :scope_id
            UNION ALL
            SELECT child.id FROM administrative_units child JOIN descendants parent ON child.parent_id = parent.id
        ) SELECT 1 FROM descendants WHERE id = :unit_id LIMIT 1'
    );
    $query->execute(['scope_id' => $user['administrative_unit_id'], 'unit_id' => $unitId]);
    return (bool) $query->fetchColumn();
}

function issue_token(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $query = $pdo->prepare(
        'INSERT INTO api_tokens (user_id, token_hash, expires_at)
         VALUES (:user_id, :token_hash, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY))'
    );
    $query->execute(['user_id' => $userId, 'token_hash' => hash('sha256', $token)]);
    return $token;
}
