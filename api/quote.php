<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/pricing.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$apartmentId = $body['apartment_id'] ?? '';
$from = $body['from'] ?? ''; $to = $body['to'] ?? '';
$guests = max(1, (int)($body['guests'] ?? 1));
$coupon = trim($body['coupon'] ?? '');

$apt = row('SELECT * FROM apartments WHERE id = ?', [$apartmentId]);
if (!$apt) { http_response_code(404); echo json_encode(['error' => 'Appartamento non trovato']); exit; }
if (!$from || !$to || strtotime($to) <= strtotime($from)) { echo json_encode(['nights' => 0, 'total' => 0]); exit; }

$rules = rows('SELECT * FROM price_rules WHERE apartment_id = ?', [$apartmentId]);
$couponPct = 0;
if ($coupon) {
    $c = row('SELECT * FROM coupons WHERE code = ? AND active = 1', [$coupon]);
    if ($c && $c['type'] === 'percent') $couponPct = (float)$c['value'];
}
echo json_encode(computeQuote($apt, $rules, $from, $to, $guests, $couponPct));
