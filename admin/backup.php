<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$data = [
  'exported_at' => date('c'),
  'apartments' => rows('SELECT * FROM apartments'),
  'photos' => rows('SELECT * FROM photos'),
  'customers' => rows('SELECT * FROM customers'),
  'bookings' => rows('SELECT * FROM bookings'),
  'payments' => rows('SELECT * FROM payments'),
  'documents' => rows('SELECT * FROM documents'),
  'expenses' => rows('SELECT * FROM expenses'),
  'price_rules' => rows('SELECT * FROM price_rules'),
  'date_blocks' => rows('SELECT * FROM date_blocks'),
  'message_templates' => rows('SELECT * FROM message_templates'),
  'reviews' => rows('SELECT * FROM reviews'),
  'coupons' => rows('SELECT * FROM coupons'),
  'settings' => rows('SELECT * FROM settings'),
];
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="casa-vacanza-backup-' . date('Y-m-d') . '.json"');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
