<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
$user = require_auth($pdo, ['resident', 'admin']);
$data = request_json();
require_fields($data, ['delivery_id', 'amount', 'method']);
if (!in_array($data['method'], ['mpesa', 'card', 'cash', 'on_delivery'], true)) json_response(['error' => 'Invalid payment method'], 422);
$delivery = $pdo->prepare('SELECT id, payment_method FROM delivery_requests WHERE id = :id AND resident_id = :user_id');
$delivery->execute(['id' => $data['delivery_id'], 'user_id' => $user['id']]);
if (!$delivery->fetch()) json_response(['error' => 'Delivery request not found'], 404);
$payment = $pdo->prepare('INSERT INTO payments (user_id, delivery_id, amount, method, status) VALUES (:user_id, :delivery_id, :amount, :method, :status)');
$payment->execute(['user_id' => $user['id'], 'delivery_id' => $data['delivery_id'], 'amount' => $data['amount'], 'method' => $data['method'], 'status' => $data['method'] === 'on_delivery' ? 'pending' : 'initiated']);
json_response(['payment_id' => (int) $pdo->lastInsertId(), 'status' => $data['method'] === 'on_delivery' ? 'pending' : 'initiated', 'message' => 'Connect the payment provider webhook to complete online payment.'], 201);
