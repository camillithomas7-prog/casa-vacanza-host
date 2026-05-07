<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json');
requireAdmin();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$endpoint = $body['endpoint'] ?? null;
if (!$endpoint) {
    http_response_code(400);
    echo json_encode(['error' => 'missing endpoint']);
    exit;
}

q('DELETE FROM push_subscriptions WHERE endpoint = ?', [$endpoint]);
echo json_encode(['ok' => true]);
