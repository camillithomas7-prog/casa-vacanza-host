<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/pricing.php';
require_once __DIR__ . '/../lib/notify.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$req = ['apartment_id', 'from', 'to', 'name', 'phone'];
foreach ($req as $r) if (empty($body[$r])) { http_response_code(400); echo json_encode(['error' => "Campo $r mancante"]); exit; }

$apt = row('SELECT * FROM apartments WHERE id = ?', [$body['apartment_id']]);
if (!$apt) { http_response_code(404); echo json_encode(['error' => 'Appartamento non trovato']); exit; }

$from = $body['from']; $to = $body['to'];
if (strtotime($to) <= strtotime($from)) { http_response_code(400); echo json_encode(['error' => 'Date non valide']); exit; }

if (isWeeklyOnly()) {
    $nights = (int)round((strtotime($to) - strtotime($from)) / 86400);
    if (!in_array($nights, [7, 14, 21, 30], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Prenotazioni disponibili solo per 7, 14, 21 notti o 1 mese (30 notti).']);
        exit;
    }
}

$overlap = (int)val('SELECT COUNT(*) FROM bookings WHERE apartment_id = ? AND status != "cancelled" AND check_in < ? AND check_out > ?', [$apt['id'], $to, $from]);
$blocked = (int)val('SELECT COUNT(*) FROM date_blocks WHERE apartment_id = ? AND start_date < ? AND end_date > ?', [$apt['id'], $to, $from]);
if ($overlap || $blocked) { http_response_code(409); echo json_encode(['error' => 'Date non disponibili']); exit; }

$rules = rows('SELECT * FROM price_rules WHERE apartment_id = ?', [$apt['id']]);
$couponPct = 0; $couponCode = null;
if (!empty($body['coupon'])) {
    $c = row('SELECT * FROM coupons WHERE code = ? AND active = 1', [$body['coupon']]);
    if ($c && $c['type'] === 'percent') { $couponPct = (float)$c['value']; $couponCode = $c['code']; }
}
$guests = max(1, (int)($body['guests'] ?? 1));
$quote = computeQuote($apt, $rules, $from, $to, $guests, $couponPct);

$customerId = newId();
$country = !empty($body['country']) ? strtoupper(substr($body['country'], 0, 2)) : null;
q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)',
    [$customerId, $body['name'], $body['email'] ?? null, $body['phone'], $country]);

$seq = ((int)val('SELECT COUNT(*) FROM bookings')) + 1;
$bookingId = newId();
q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, city_tax, discount, total, coupon_code, status, source, currency)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [$bookingId, bookingCode($seq), $apt['id'], $customerId, $from, $to, $quote['nights'], $guests,
     $quote['nightlyTotal'], $quote['cleaningFee'], $quote['cityTax'], $quote['discount'], $quote['total'],
     $couponCode, 'pending', 'direct', cfg('site.currency') ?: 'EUR']);

notify('new_booking', 'Nuova prenotazione', "{$body['name']} ha richiesto {$apt['name']} dal $from al $to", "/admin/prenotazione.php?id=$bookingId");

echo json_encode(['ok' => true, 'code' => bookingCode($seq), 'id' => $bookingId]);
