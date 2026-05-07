<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json');
requireAdmin();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$endpoint = $body['endpoint'] ?? null;
$p256dh = $body['keys']['p256dh'] ?? null;
$auth = $body['keys']['auth'] ?? null;

if (!$endpoint || !$p256dh || !$auth) {
    http_response_code(400);
    echo json_encode(['error' => 'missing fields']);
    exit;
}

$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

$existing = row('SELECT id FROM push_subscriptions WHERE endpoint = ?', [$endpoint]);
if ($existing) {
    q('UPDATE push_subscriptions SET p256dh = ?, auth = ?, user_agent = ? WHERE id = ?',
        [$p256dh, $auth, $ua, $existing['id']]);
    echo json_encode(['ok' => true, 'id' => $existing['id'], 'updated' => true]);
} else {
    $id = newId();
    q('INSERT INTO push_subscriptions (id, endpoint, p256dh, auth, user_agent) VALUES (?, ?, ?, ?, ?)',
        [$id, $endpoint, $p256dh, $auth, $ua]);
    echo json_encode(['ok' => true, 'id' => $id, 'created' => true]);
}
