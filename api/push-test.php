<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/notify.php';

header('Content-Type: application/json');
requireAdmin();

$result = sendPushToAll([
    'id' => 'test-' . time(),
    'type' => 'test',
    'title' => 'Notifica di prova',
    'body' => 'Le notifiche funzionano correttamente! Riceverai così le nuove prenotazioni.',
    'link' => '/admin/notifiche.php',
]);

echo json_encode($result);
