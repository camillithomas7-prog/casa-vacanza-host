<?php
// Manifest PWA dinamico per il ruolo "cleaner" (signora delle pulizie).
// Quando la signora aggiunge alla schermata Home questo link, l'app installata
// parte direttamente da /pulizie.php?t=TOKEN — non dalla home pubblica.
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/cleaning.php';

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-store');

$token = $_GET['t'] ?? '';
$valid = $token && hash_equals(cleanerToken(), $token);
if (!$valid) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid token']);
    exit;
}

$startUrl = '/pulizie.php?t=' . rawurlencode($token);

echo json_encode([
    'id'               => '/pulizie-app?t=' . substr($token, 0, 8), // identità stabile e separata dalla PWA principale
    'name'             => 'Pulizie · Casa Vacanza',
    'short_name'       => 'Pulizie',
    'description'      => 'Le tue pulizie del giorno + checklist',
    'start_url'        => $startUrl,
    'scope'            => '/',
    'display'          => 'standalone',
    'orientation'      => 'portrait',
    'background_color' => '#f7f7f8',
    'theme_color'      => '#10b981',
    'lang'             => 'it',
    'icons' => [
        ['src' => '/assets/logo-192.png?v=2', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/assets/logo-256.png?v=2', 'sizes' => '256x256', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/assets/logo-512.png?v=2', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
