<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$b = row('SELECT b.*, a.name AS apartment_name, a.address AS apartment_address, a.city AS apartment_city, a.check_in_time, a.check_out_time, a.rules AS apartment_rules, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.document AS customer_document FROM bookings b JOIN apartments a ON b.apartment_id = a.id JOIN customers c ON b.customer_id = c.id WHERE b.id = ?', [$_GET['id'] ?? '']);
if (!$b) { http_response_code(404); exit('Non trovato'); }
$due = (float)$b['total'] - (float)$b['paid'];
?><!doctype html><html lang="it"><head><meta charset="utf-8"><title>Contratto <?= e($b['code']) ?></title>
<style>
body{font-family:Inter,system-ui,sans-serif;max-width:780px;margin:30px auto;padding:30px;color:#1a1b25;line-height:1.5}
h1{font-size:28px;margin:0 0 4px;color:#f04e00}
h2{font-size:14px;text-transform:uppercase;letter-spacing:.06em;color:#737486;margin:24px 0 8px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 24px}
.row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee}
.total{font-size:20px;font-weight:700;color:#f04e00}
.sig{margin-top:60px;display:grid;grid-template-columns:1fr 1fr;gap:24px}
.sig div{border-top:1px solid #ccc;padding-top:8px;font-size:12px;color:#737486}
.badge{display:inline-block;padding:4px 10px;background:#fff7ec;color:#f04e00;border-radius:99px;font-size:12px;font-weight:600}
@media print{body{margin:0;padding:20px}}
</style></head><body>
<div style="display:flex;justify-content:space-between;align-items:center">
  <div><h1>Contratto di soggiorno</h1><div style="color:#737486">Codice prenotazione: <strong><?= e($b['code']) ?></strong></div></div>
  <span class="badge"><?= e(cfg('site.name')) ?></span>
</div>

<h2>Cliente</h2>
<div class="grid">
  <div class="row"><span>Nome</span><strong><?= e($b['customer_name']) ?></strong></div>
  <div class="row"><span>Email</span><strong><?= e($b['customer_email'] ?: '—') ?></strong></div>
  <div class="row"><span>Telefono</span><strong><?= e($b['customer_phone'] ?: '—') ?></strong></div>
  <div class="row"><span>Documento</span><strong><?= e($b['customer_document'] ?: '—') ?></strong></div>
</div>

<h2>Soggiorno</h2>
<div class="grid">
  <div class="row"><span>Appartamento</span><strong><?= e($b['apartment_name']) ?></strong></div>
  <div class="row"><span>Indirizzo</span><strong><?= e($b['apartment_address'] ?: $b['apartment_city']) ?></strong></div>
  <div class="row"><span>Check-in</span><strong><?= fmtDate($b['check_in']) ?> · <?= e($b['check_in_time']) ?></strong></div>
  <div class="row"><span>Check-out</span><strong><?= fmtDate($b['check_out']) ?> · <?= e($b['check_out_time']) ?></strong></div>
  <div class="row"><span>Notti</span><strong><?= (int)$b['nights'] ?></strong></div>
  <div class="row"><span>Ospiti</span><strong><?= (int)$b['guests'] ?></strong></div>
</div>

<h2>Importi</h2>
<div class="grid">
  <div class="row"><span>Pernottamento</span><strong><?= fmtMoney((float)$b['base_price']) ?></strong></div>
  <div class="row"><span>Pulizie</span><strong><?= fmtMoney((float)$b['cleaning_fee']) ?></strong></div>
  <div class="row"><span>Tassa soggiorno</span><strong><?= fmtMoney((float)$b['city_tax']) ?></strong></div>
  <div class="row"><span>Sconto</span><strong><?= $b['discount'] > 0 ? '-' . fmtMoney((float)$b['discount']) : '—' ?></strong></div>
  <div class="row"><span>Acconto versato</span><strong><?= fmtMoney((float)$b['paid']) ?></strong></div>
  <div class="row"><span>Saldo</span><strong><?= fmtMoney(max(0, $due)) ?></strong></div>
  <div class="row" style="grid-column:1/-1"><span>Totale</span><span class="total"><?= fmtMoney((float)$b['total']) ?></span></div>
</div>

<?php if ($b['apartment_rules']): ?><h2>Regole della casa</h2><p style="white-space:pre-line;color:#494a5a"><?= e($b['apartment_rules']) ?></p><?php endif; ?>

<div class="sig"><div>Firma cliente</div><div>Firma host</div></div>
<script>window.print && setTimeout(()=>window.print(), 400)</script>
</body></html>
