<?php
declare(strict_types=1);

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function request_json(): array
{
    $body = file_get_contents('php://input');
    if ($body === false || trim($body) === '') {
        json_response(['error' => 'A JSON request body is required'], 400);
    }

    try {
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        json_response(['error' => 'Request body must contain valid JSON'], 400);
    }

    if (!is_array($data)) {
        json_response(['error' => 'JSON body must be an object'], 400);
    }

    return $data;
}

function require_fields(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            json_response(['error' => "Missing required field: {$field}"], 422);
        }
    }
}
