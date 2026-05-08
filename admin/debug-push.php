<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

header('Content-Type: text/plain; charset=utf-8');

echo "== ULTIMA VOCE NOTIFICATIONS ==\n\n";
$last = rows('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5');
foreach ($last as $n) {
    echo "[{$n['created_at']}] type={$n['type']} title={$n['title']}\n";
    echo "  body: {$n['body']}\n\n";
}

echo "\n== PUSH SUBSCRIPTIONS ATTIVE ==\n\n";
try {
    $subs = rows('SELECT id, endpoint, user_agent, created_at FROM push_subscriptions');
    echo count($subs) . " subscription/i\n\n";
    foreach ($subs as $s) {
        echo "id={$s['id']}\n  endpoint=" . substr($s['endpoint'], 0, 80) . "...\n  ua=" . substr($s['user_agent'], 0, 80) . "\n  created={$s['created_at']}\n\n";
    }
} catch (Throwable $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
}

echo "\n== ULTIME VOCI push.log ==\n\n";
$logFile = __DIR__ . '/../uploads/logs/push.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $tail = array_slice($lines, -60);
    echo implode('', $tail);
} else {
    echo "(log non ancora creato — fai una nuova prenotazione e ricarica)\n";
}
