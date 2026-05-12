<?php
// Trigger reminder pulizie. Callable da:
//  - cron Hostinger (curl https://.../api/cron-cleaning-reminders.php?key=TOKEN)
//  - pageview admin/pulizie.php (best-effort)
// Idempotente: si basa su cleaning_sessions.reminder_sent_at per non rispedire.
header('Content-Type: application/json');
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/cleaning.php';

$key = $_GET['key'] ?? '';
if (!$key || !hash_equals(cleanerToken(), $key)) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid key']); exit;
}
$n = sendCleaningReminders();
echo json_encode(['ok' => true, 'reminded' => $n, 'at' => date('c')]);
