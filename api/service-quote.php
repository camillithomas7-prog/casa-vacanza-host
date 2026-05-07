<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/services.php';

header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true) ?: [];

$s = row('SELECT * FROM services WHERE id = ?', [$body['service_id'] ?? '']);
if (!$s) { http_response_code(404); echo json_encode(['error' => 'Servizio non trovato']); exit; }

$couponPct = 0;
if (!empty($body['coupon'])) {
    $c = row('SELECT * FROM coupons WHERE code = ? AND active = 1', [$body['coupon']]);
    if ($c && $c['type'] === 'percent') $couponPct = (float)$c['value'];
}

if (isRental($s['type'])) {
    if (empty($body['from']) || empty($body['to'])) { echo json_encode(['days' => 0, 'total' => 0]); exit; }
    echo json_encode(computeRentalQuote($s, $body['from'], $body['to'], $couponPct));
} elseif (isExperience($s['type'])) {
    $p = max(1, (int)($body['participants'] ?? 1));
    echo json_encode(computeExperienceQuote($s, $p, $couponPct));
} else {
    $p = max(1, (int)($body['passengers'] ?? $body['participants'] ?? 1));
    echo json_encode(computeTransferQuote($s, $p, $couponPct));
}
