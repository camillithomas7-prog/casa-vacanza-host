<?php
/**
 * Cron endpoint - safe to run multiple times per day (deduplicates).
 * Hostinger cron suggested schedule: every hour ("0 * * * *").
 *
 * Notifies:
 *   - Check-in TOMORROW (advance heads-up at evening)
 *   - Check-in TODAY
 *   - Check-out TOMORROW
 *   - Check-out TODAY
 *   - Pagamento mancante a 3 giorni dal check-in
 * Each notification is sent at most ONCE per day per booking
 * (deduplication by type + booking link + DATE(created_at)).
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/notify.php';

header('Content-Type: text/plain; charset=utf-8');

$token = setting('cron_token');
if (!$token) {
    $token = bin2hex(random_bytes(16));
    setSetting('cron_token', $token);
    echo "First run: token initialized.\n";
    echo "Configure cron URL with ?token=$token\n";
    exit;
}

if (($_GET['token'] ?? '') !== $token) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$report = [];

/** Returns true if a same-type notification for that link already exists today. */
function alreadySentToday(string $type, string $link): bool {
    $hit = val('SELECT 1 FROM notifications WHERE type = ? AND link = ? AND DATE(created_at) = CURDATE() LIMIT 1',
        [$type, $link]);
    return $hit !== null && $hit !== false;
}

function notifyOnce(string $type, string $title, string $body, string $link): bool {
    if (alreadySentToday($type, $link)) return false;
    notify($type, $title, $body, $link);
    return true;
}

// Check-in DOMANI (heads-up serale)
foreach (rows("SELECT b.*, c.name AS cust_name, c.phone AS cust_phone, a.name AS apt_name
               FROM bookings b
               JOIN customers c ON c.id = b.customer_id
               JOIN apartments a ON a.id = b.apartment_id
               WHERE b.check_in = ? AND b.status IN ('confirmed','pending')", [$tomorrow]) as $b) {
    $link = '/admin/prenotazione.php?id=' . $b['id'];
    if (notifyOnce('checkin_tomorrow',
        'Domani arriva: ' . $b['cust_name'],
        $b['apt_name'] . ' · ' . $b['guests'] . ' ospiti · prepara le chiavi',
        $link)) $report[] = "Check-in domani: {$b['cust_name']}";
}

// Check-out DOMANI
foreach (rows("SELECT b.*, c.name AS cust_name, a.name AS apt_name
               FROM bookings b
               JOIN customers c ON c.id = b.customer_id
               JOIN apartments a ON a.id = b.apartment_id
               WHERE b.check_out = ? AND b.status IN ('confirmed','pending','checked_in')", [$tomorrow]) as $b) {
    $link = '/admin/prenotazione.php?id=' . $b['id'];
    if (notifyOnce('checkout_tomorrow',
        'Domani parte: ' . $b['cust_name'],
        $b['apt_name'] . ' · prepara le pulizie per dopo il check-out',
        $link)) $report[] = "Check-out domani: {$b['cust_name']}";
}

// Check-in OGGI
foreach (rows("SELECT b.*, c.name AS cust_name, a.name AS apt_name
               FROM bookings b
               JOIN customers c ON c.id = b.customer_id
               JOIN apartments a ON a.id = b.apartment_id
               WHERE b.check_in = ? AND b.status IN ('confirmed','pending')", [$today]) as $b) {
    $link = '/admin/prenotazione.php?id=' . $b['id'];
    if (notifyOnce('checkin_today',
        'Oggi arriva: ' . $b['cust_name'],
        $b['apt_name'] . ' · ' . $b['guests'] . ' ospiti, ' . $b['nights'] . ' notti',
        $link)) $report[] = "Check-in oggi: {$b['cust_name']}";
}

// Check-out OGGI
foreach (rows("SELECT b.*, c.name AS cust_name, a.name AS apt_name
               FROM bookings b
               JOIN customers c ON c.id = b.customer_id
               JOIN apartments a ON a.id = b.apartment_id
               WHERE b.check_out = ? AND b.status IN ('confirmed','pending','checked_in')", [$today]) as $b) {
    $link = '/admin/prenotazione.php?id=' . $b['id'];
    if (notifyOnce('checkout_today',
        'Oggi parte: ' . $b['cust_name'],
        $b['apt_name'] . ' · ricorda pulizie e richiesta recensione',
        $link)) $report[] = "Check-out oggi: {$b['cust_name']}";
}

// Pagamento mancante (booking confermato vicino al check-in senza acconto)
foreach (rows("SELECT b.*, c.name AS cust_name, a.name AS apt_name,
                      (SELECT COALESCE(SUM(amount),0) FROM payments p WHERE p.booking_id = b.id) AS paid
               FROM bookings b
               JOIN customers c ON c.id = b.customer_id
               JOIN apartments a ON a.id = b.apartment_id
               WHERE b.status = 'confirmed'
                 AND b.check_in BETWEEN ? AND DATE_ADD(?, INTERVAL 3 DAY)", [$today, $today]) as $b) {
    if ((float)$b['paid'] < (float)$b['total'] * 0.5) {
        $link = '/admin/prenotazione.php?id=' . $b['id'];
        if (notifyOnce('payment_pending',
            'Pagamento mancante: ' . $b['cust_name'],
            'Check-in il ' . $b['check_in'] . ' senza acconto registrato',
            $link)) $report[] = "Pagamento: {$b['cust_name']} mancante";
    }
}

echo "Cron eseguito " . date('Y-m-d H:i:s') . "\n";
echo count($report) . " nuove notifiche\n";
foreach ($report as $r) echo "  · $r\n";
