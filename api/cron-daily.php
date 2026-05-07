<?php
/**
 * Daily cron endpoint. Configure on Hostinger (Avanzate → Cron Jobs):
 *   0 8 * * *  curl -s "https://YOUR-DOMAIN/api/cron-daily.php?token=SECRET_TOKEN" > /dev/null
 *
 * Set SECRET_TOKEN in settings via setting() or hardcode in config.
 * The token guards against external triggering.
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/notify.php';

header('Content-Type: text/plain; charset=utf-8');

$token = setting('cron_token');
if (!$token) {
    // First run: create a token
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
$report = [];

// Check-in oggi
$checkins = rows("SELECT b.*, c.name AS cust_name, a.name AS apt_name
                  FROM bookings b
                  JOIN customers c ON c.id = b.customer_id
                  JOIN apartments a ON a.id = b.apartment_id
                  WHERE b.check_in = ? AND b.status IN ('confirmed','pending')", [$today]);
foreach ($checkins as $b) {
    notify(
        'checkin_today',
        'Check-in oggi: ' . $b['cust_name'],
        $b['apt_name'] . ' · ' . $b['guests'] . ' ospiti, ' . $b['nights'] . ' notti',
        '/admin/prenotazione.php?id=' . $b['id']
    );
    $report[] = "Check-in: {$b['cust_name']} → {$b['apt_name']}";
}

// Check-out oggi
$checkouts = rows("SELECT b.*, c.name AS cust_name, a.name AS apt_name
                   FROM bookings b
                   JOIN customers c ON c.id = b.customer_id
                   JOIN apartments a ON a.id = b.apartment_id
                   WHERE b.check_out = ? AND b.status IN ('confirmed','pending')", [$today]);
foreach ($checkouts as $b) {
    notify(
        'checkout_today',
        'Check-out oggi: ' . $b['cust_name'],
        $b['apt_name'] . ' · ricorda di pulire e di richiedere recensione',
        '/admin/prenotazione.php?id=' . $b['id']
    );
    $report[] = "Check-out: {$b['cust_name']} ← {$b['apt_name']}";
}

// Pagamenti scaduti (booking confermato senza pagamento entro 3 gg da check-in)
$pending = rows("SELECT b.*, c.name AS cust_name, a.name AS apt_name,
                        (SELECT COALESCE(SUM(amount),0) FROM payments p WHERE p.booking_id = b.id) AS paid
                   FROM bookings b
                   JOIN customers c ON c.id = b.customer_id
                   JOIN apartments a ON a.id = b.apartment_id
                  WHERE b.status = 'confirmed'
                    AND b.check_in BETWEEN ? AND DATE_ADD(?, INTERVAL 3 DAY)", [$today, $today]);
foreach ($pending as $b) {
    if ((float)$b['paid'] < (float)$b['total'] * 0.5) {
        notify(
            'payment_pending',
            'Pagamento mancante: ' . $b['cust_name'],
            'Check-in il ' . $b['check_in'] . ' senza acconto registrato',
            '/admin/prenotazione.php?id=' . $b['id']
        );
        $report[] = "Pagamento: {$b['cust_name']} mancante";
    }
}

echo "Cron eseguito alle " . date('Y-m-d H:i:s') . "\n";
echo count($report) . " notifiche generate\n";
foreach ($report as $r) echo "  · $r\n";
