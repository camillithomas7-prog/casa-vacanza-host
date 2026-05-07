<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/webpush.php';

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
function sendPushToAll(array $payload): array {
    $keys = vapidKeys();
    $subject = setting('push_subject') ?: ('mailto:' . (cfg('site.email') ?: 'admin@casavacanza.it'));
    try {
        $wp = new WebPush($keys['public'], $keys['private'], $subject);
    } catch (Throwable $e) {
        error_log('WebPush init failed: ' . $e->getMessage());
        return ['sent' => 0, 'errors' => 1];
    }

    $subs = rows('SELECT * FROM push_subscriptions');
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $sent = 0; $errors = 0;
    foreach ($subs as $sub) {
        try {
            $res = $wp->send($sub, $payloadJson);
            if ($res['ok']) {
                $sent++;
            } else {
                $errors++;
                if ($res['status'] === 404 || $res['status'] === 410) {
                    q('DELETE FROM push_subscriptions WHERE id = ?', [$sub['id']]);
                } else {
                    error_log("WebPush send failed (status={$res['status']}): {$res['body']}");
                }
            }
        } catch (Throwable $e) {
            $errors++;
            error_log('WebPush send exception: ' . $e->getMessage());
        }
    }
    return ['sent' => $sent, 'errors' => $errors];
}
