<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$b = row('SELECT b.*, a.name AS apartment_name FROM bookings b JOIN apartments a ON b.apartment_id = a.id WHERE b.id = ?', [$_GET['id'] ?? '']);
if (!$b) { http_response_code(404); exit('Non trovato'); }
$origin = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$target = $origin . '/checkin.php?code=' . urlencode($b['code']);
$qr = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($target);
?><!doctype html><html><head><meta charset="utf-8"><title>QR <?= e($b['code']) ?></title>
<style>body{font-family:Inter,sans-serif;display:flex;flex-direction:column;align-items:center;padding:40px;color:#1a1b25}h1{color:#f04e00}img{margin:20px;border:1px solid #eee;border-radius:16px;padding:8px}</style>
</head><body>
<h1><?= e($b['apartment_name']) ?></h1>
<div>Codice <?= e($b['code']) ?></div>
<img src="<?= e($qr) ?>" alt="QR">
<a href="<?= e($target) ?>"><?= e($target) ?></a>
</body></html>
