<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/services.php';
require_once __DIR__ . '/../lib/notify.php';

header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true) ?: [];

foreach (['service_id', 'name', 'phone'] as $r) {
    if (empty($body[$r])) { http_response_code(400); echo json_encode(['error' => "Campo $r mancante"]); exit; }
}

$s = row('SELECT * FROM services WHERE id = ?', [$body['service_id']]);
if (!$s) { http_response_code(404); echo json_encode(['error' => 'Servizio non trovato']); exit; }

$couponPct = 0; $couponCode = null;
if (!empty($body['coupon'])) {
    $c = row('SELECT * FROM coupons WHERE code = ? AND active = 1', [$body['coupon']]);
    if ($c && $c['type'] === 'percent') { $couponPct = (float)$c['value']; $couponCode = $c['code']; }
}

if (isRental($s['type'])) {
    if (empty($body['from']) || empty($body['to'])) { http_response_code(400); echo json_encode(['error' => 'Date mancanti']); exit; }
    $q = computeRentalQuote($s, $body['from'], $body['to'], $couponPct);
    $start = $body['from']; $end = $body['to'];
    $participants = 1;
} elseif (isExperience($s['type'])) {
    if (empty($body['from'])) { http_response_code(400); echo json_encode(['error' => 'Data mancante']); exit; }
    $participants = max(1, (int)($body['participants'] ?? 1));
    $q = computeExperienceQuote($s, $participants, $couponPct);
    $q['days'] = 1; $q['nightlyTotal'] = $q['base']; $q['extras'] = 0;
    $start = $body['from']; $end = $body['from'];
} else {
    if (empty($body['from'])) { http_response_code(400); echo json_encode(['error' => 'Data mancante']); exit; }
    $participants = max(1, (int)($body['participants'] ?? 1));
    $q = computeTransferQuote($s, $participants, $couponPct);
    $q['days'] = 1; $q['nightlyTotal'] = $q['base']; $q['extras'] = 0;
    $start = $body['from']; $end = null;
}

$customerId = newId();
q('INSERT INTO customers (id, name, email, phone) VALUES (?, ?, ?, ?)',
    [$customerId, $body['name'], $body['email'] ?? null, $body['phone']]);

$seq = ((int)val('SELECT COUNT(*) FROM service_bookings')) + 1;
$bookingId = newId();
$code = serviceBookingCode($seq, 'SV');

q('INSERT INTO service_bookings (id, code, service_id, customer_id, start_date, end_date, pickup_time, participants, pickup_location, dropoff_location, flight_number, base_price, extras, discount, total, coupon_code, status, source, currency, notes)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
   [$bookingId, $code, $s['id'], $customerId, $start, $end, $body['pickup_time'] ?? null, $participants,
    $body['pickup_location'] ?? null, $body['dropoff_location'] ?? null, $body['flight_number'] ?? null,
    $q['nightlyTotal'] ?? $q['base'] ?? 0, $q['extras'] ?? 0, $q['discount'] ?? 0, $q['total'],
    $couponCode, 'pending', 'direct', cfg('site.currency') ?: 'EUR', $body['notes'] ?? null]);

notify('new_service_booking', 'Nuovo servizio richiesto', "{$body['name']} ha richiesto {$s['name']}", "/admin/servizi-prenotazioni.php?id=$bookingId");

echo json_encode(['ok' => true, 'code' => $code, 'id' => $bookingId]);
