<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/notify.php';

header('Content-Type: application/json');
$keys = vapidKeys();
echo json_encode(['publicKey' => $keys['public']]);
