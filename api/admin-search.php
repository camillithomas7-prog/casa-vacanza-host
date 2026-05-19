<?php
require_once __DIR__ . '/../lib/auth.php';
header('Content-Type: application/json');

if (!currentUser()) { http_response_code(401); echo json_encode(['error' => 'unauth']); exit; }

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) { echo json_encode(['results' => []]); exit; }
$like = '%' . $q . '%';

$out = [];

// Appartamenti — match su name, city, description, slug, owner_name
try {
    $apts = rows(
        'SELECT id, name, slug, city, base_price, cover_image
         FROM apartments
         WHERE name LIKE ? OR city LIKE ? OR description LIKE ? OR slug LIKE ? OR owner_name LIKE ?
         ORDER BY (name LIKE ?) DESC, name ASC
         LIMIT 15',
        [$like, $like, $like, $like, $like, $like]
    );
    foreach ($apts as $a) {
        $out[] = [
            'type' => 'apartment',
            'label' => $a['name'],
            'sub' => trim(($a['city'] ?: '—') . ' · €' . (int)$a['base_price'] . '/notte'),
            'cover' => $a['cover_image'],
            'url' => '/admin/appartamento-edit.php?id=' . $a['id'],
        ];
    }
} catch (Throwable $e) {}

// Clienti
try {
    $cs = rows(
        'SELECT id, name, email, phone FROM customers
         WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
         ORDER BY name ASC LIMIT 8',
        [$like, $like, $like]
    );
    foreach ($cs as $c) {
        $out[] = [
            'type' => 'customer',
            'label' => $c['name'],
            'sub' => trim(($c['email'] ?: '') . ($c['phone'] ? ' · ' . $c['phone'] : '')),
            'cover' => null,
            'url' => '/admin/prenotazioni.php?customer=' . urlencode($c['id']),
        ];
    }
} catch (Throwable $e) {}

// Prenotazioni — match su id, nome cliente, nome appartamento
try {
    $bks = rows(
        'SELECT b.id, b.check_in, b.check_out, b.total, b.status, c.name AS cname, a.name AS aname
         FROM bookings b
         LEFT JOIN customers c ON c.id = b.customer_id
         LEFT JOIN apartments a ON a.id = b.apartment_id
         WHERE b.id LIKE ? OR c.name LIKE ? OR a.name LIKE ?
         ORDER BY b.check_in DESC LIMIT 8',
        [$like, $like, $like]
    );
    foreach ($bks as $b) {
        $out[] = [
            'type' => 'booking',
            'label' => ($b['cname'] ?: 'Cliente?') . ' · ' . ($b['aname'] ?: '?'),
            'sub' => $b['check_in'] . ' → ' . $b['check_out'] . ' · €' . (int)$b['total'] . ' · ' . $b['status'],
            'cover' => null,
            'url' => '/admin/prenotazione.php?id=' . $b['id'],
        ];
    }
} catch (Throwable $e) {}

echo json_encode(['results' => $out]);
