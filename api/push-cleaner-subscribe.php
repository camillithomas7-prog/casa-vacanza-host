<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/cleaning.php';
require_once __DIR__ . '/../lib/notify.php';

header('Content-Type: application/json');
ensurePushSchema();

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$token    = $body['token']            ?? '';
$endpoint = $body['endpoint']         ?? null;
$p256dh   = $body['keys']['p256dh']   ?? null;
$auth     = $body['keys']['auth']     ?? null;

if (!$token || !hash_equals(cleanerToken(), $token)) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid token']); exit;
}
if (!$endpoint || !$p256dh || !$auth) {
    http_response_code(400);
    echo json_encode(['error' => 'missing fields']); exit;
}

$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

$existing = row('SELECT id FROM push_subscriptions WHERE endpoint = ?', [$endpoint]);
if ($existing) {
    q("UPDATE push_subscriptions SET p256dh = ?, auth = ?, user_agent = ?, role = 'cleaner' WHERE id = ?",
        [$p256dh, $auth, $ua, $existing['id']]);
    echo json_encode(['ok' => true, 'id' => $existing['id'], 'updated' => true]);
} else {
    $id = newId();
    q("INSERT INTO push_subscriptions (id, endpoint, p256dh, auth, user_agent, role) VALUES (?, ?, ?, ?, ?, 'cleaner')",
        [$id, $endpoint, $p256dh, $auth, $ua]);
    echo json_encode(['ok' => true, 'id' => $id, 'created' => true]);
}
