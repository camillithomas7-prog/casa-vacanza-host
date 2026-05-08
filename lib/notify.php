<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/webpush.php';

/**
 * Idempotently ensure the push_subscriptions table exists.
 * Cached per request so it runs at most once.
 */
function ensurePushSchema(): void {
    static $done = false;
    if ($done) return;
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
          id VARCHAR(32) PRIMARY KEY,
          endpoint TEXT NOT NULL,
          p256dh VARCHAR(255) NOT NULL,
          auth VARCHAR(255) NOT NULL,
          user_agent VARCHAR(500),
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uniq_endpoint (endpoint(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        error_log('ensurePushSchema failed: ' . $e->getMessage());
    }
    $done = true;
}

/**
 * Create an in-app notification AND send a push to all subscribed devices.
 * Use this instead of inserting directly into `notifications`.
 */
function notify(string $type, string $title, string $body, ?string $link = null): string {
    $id = newId();
    q('INSERT INTO notifications (id, type, title, body, link) VALUES (?, ?, ?, ?, ?)',
        [$id, $type, $title, $body, $link]);

    sendPushToAll([
        'id' => $id,
        'type' => $type,
        'title' => $title,
        'body' => $body,
        'link' => $link,
    ]);
    return $id;
}

/**
 * Save a setting value. Used for one-shot writes (VAPID keys etc).
 */
function setSetting(string $key, string $value): void {
    q('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$key, $value]);
}

/**
 * Lazily load (or create on first use) VAPID keys.
 */
function vapidKeys(): array {
    $pub = setting('vapid_public');
    $priv = setting('vapid_private');
    if (!$pub || !$priv) {
        $keys = WebPush::generateVapidKeys();
        setSetting('vapid_public', $keys['public']);
        setSetting('vapid_private', $keys['private']);
        return $keys;
    }
    return ['public' => $pub, 'private' => $priv];
}

/**
 * Push a JSON payload to every saved subscription.
 * Drops subscriptions that return 404/410 (gone).
 */
function pushLog(string $line): void {
    try {
        $dir = __DIR__ . '/../uploads/logs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($dir . '/push.log', date('Y-m-d H:i:s') . ' ' . $line . "\n", FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {}
}

function sendPushToAll(array $payload): array {
    pushLog("=== sendPushToAll called: type={$payload['type']} title={$payload['title']}");
    try {
        ensurePushSchema();
    } catch (Throwable $e) {
        pushLog('ensurePushSchema failed: ' . $e->getMessage());
        return ['sent' => 0, 'errors' => 1];
    }

    try {
        $keys = vapidKeys();
    } catch (Throwable $e) {
        pushLog('vapidKeys failed: ' . $e->getMessage());
        return ['sent' => 0, 'errors' => 1];
    }

    $subject = setting('push_subject') ?: ('mailto:' . (cfg('site.email') ?: 'admin@casavacanza.it'));
    try {
        $wp = new WebPush($keys['public'], $keys['private'], $subject);
    } catch (Throwable $e) {
        pushLog('WebPush init failed: ' . $e->getMessage());
        return ['sent' => 0, 'errors' => 1];
    }

    try {
        $subs = rows('SELECT * FROM push_subscriptions');
    } catch (Throwable $e) {
        pushLog('rows() push_subscriptions failed: ' . $e->getMessage());
        return ['sent' => 0, 'errors' => 1];
    }

    pushLog("subs count: " . count($subs));
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $sent = 0; $errors = 0;
    foreach ($subs as $sub) {
        try {
            $endpointShort = substr($sub['endpoint'], 0, 60);
            $res = $wp->send($sub, $payloadJson);
            pushLog("send to {$endpointShort}... status={$res['status']} body=" . substr($res['body'], 0, 120));
            if ($res['ok']) {
                $sent++;
            } else {
                $errors++;
                if ($res['status'] === 404 || $res['status'] === 410) {
                    q('DELETE FROM push_subscriptions WHERE id = ?', [$sub['id']]);
                    pushLog("subscription gone, deleted: id={$sub['id']}");
                }
            }
        } catch (Throwable $e) {
            $errors++;
            pushLog('send exception: ' . $e->getMessage());
        }
    }
    pushLog("=== done: sent=$sent errors=$errors");
    return ['sent' => $sent, 'errors' => $errors];
}
