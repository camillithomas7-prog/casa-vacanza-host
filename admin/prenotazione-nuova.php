<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/pricing.php';
require_once __DIR__ . '/../lib/cleaning.php';
requireAdmin();

$apartments = rows('SELECT id, name, base_price FROM apartments ORDER BY name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $apt = row('SELECT * FROM apartments WHERE id = ?', [$_POST['apartment_id']]);
    if (!$apt) { flash('Appartamento non trovato', 'error'); redirect('/admin/prenotazione-nuova.php'); }
    $rules = rows('SELECT * FROM price_rules WHERE apartment_id = ?', [$apt['id']]);
    $couponPct = 0; $couponCode = null;
    if (!empty($_POST['coupon'])) {
        $c = row('SELECT * FROM coupons WHERE code = ? AND active = 1', [$_POST['coupon']]);
        if ($c && $c['type'] === 'percent') { $couponPct = (float)$c['value']; $couponCode = $c['code']; }
    }
    $guests = max(1, (int)$_POST['guests']);
    $quote = computeQuote($apt, $rules, $_POST['from'], $_POST['to'], $guests, $couponPct);

    $cid = newId();
    $country = !empty($_POST['country']) ? strtoupper(substr($_POST['country'], 0, 2)) : null;
    q('INSERT INTO customers (id, name, email, phone, country) VALUES (?, ?, ?, ?, ?)',
        [$cid, $_POST['name'], $_POST['email'] ?: null, $_POST['phone'] ?: null, $country]);

    $seq = ((int)val('SELECT COUNT(*) FROM bookings')) + 1;
    $bid = newId();
    q('INSERT INTO bookings (id, code, apartment_id, customer_id, check_in, check_out, nights, guests, base_price, cleaning_fee, city_tax, discount, total, paid, coupon_code, status, source, notes, currency)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$bid, bookingCode($seq), $apt['id'], $cid, $_POST['from'], $_POST['to'], $quote['nights'], $guests,
         $quote['nightlyTotal'], $quote['cleaningFee'], $quote['cityTax'], $quote['discount'], $quote['total'],
         (float)($_POST['deposit'] ?? 0), $couponCode, $_POST['status'] ?? 'confirmed', $_POST['source'] ?? 'direct',
         $_POST['notes'] ?? null, cfg('site.currency') ?: 'EUR']);

    if (!empty($_POST['deposit']) && (float)$_POST['deposit'] > 0) {
        q('INSERT INTO payments (id, booking_id, amount, type, method) VALUES (?, ?, ?, ?, ?)',
            [newId(), $bid, (float)$_POST['deposit'], 'deposit', 'cash']);
    }
    logActivity('create', 'booking', $bid, bookingCode($seq));
    try { ensureCleaningSession($bid); } catch (Throwable $e) {}
    flash('Prenotazione creata');
    redirect('/admin/prenotazione.php?id=' . $bid);
}

$preselect = $_GET['apt'] ?? ($apartments[0]['id'] ?? '');

$title = 'Nuova prenotazione';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<form method="post" class="space-y-5" x-data="newBooking()">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
  <div class="flex items-center justify-between">
    <h1 class="font-display text-2xl sm:text-3xl font-bold">Nuova prenotazione</h1>
    <button class="btn-primary"><i data-lucide="save" class="size-[18px]"></i> Crea</button>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="card p-4 sm:p-5 space-y-3">
      <h3 class="font-display font-bold">Cliente</h3>
      <label class="block"><span class="label">Nome e cognome</span><input class="input" name="name" required></label>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Email</span><input class="input" type="email" name="email"></label>
        <label class="block"><span class="label">Telefono</span><input class="input" name="phone"></label>
      </div>
      <label class="block"><span class="label">Paese</span>
        <select class="input" name="country">
          <option value="">— Seleziona —</option>
          <?php foreach (countryList('it') as $c): ?>
            <option value="<?= e($c['code']) ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block"><span class="label">Note interne</span><textarea class="input min-h-[100px]" name="notes"></textarea></label>
    </div>

    <div class="card p-4 sm:p-5 space-y-3">
      <h3 class="font-display font-bold">Soggiorno</h3>
      <label class="block"><span class="label">Appartamento</span>
        <select class="input" name="apartment_id" required x-model="apartment_id" @change="quote()">
          <?php foreach ($apartments as $a): ?><option value="<?= e($a['id']) ?>" <?= $a['id'] === $preselect ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Check-in</span><input class="input" type="date" name="from" x-model="from" @change="quote()" required></label>
        <label class="block"><span class="label">Check-out</span><input class="input" type="date" name="to" x-model="to" @change="quote()" required></label>
        <label class="block"><span class="label">Ospiti</span><input class="input" type="number" min="1" name="guests" x-model.number="guests" @input="quote()"></label>
        <label class="block"><span class="label">Coupon</span><input class="input" name="coupon" x-model="coupon" @input.debounce.500="quote()"></label>
        <label class="block"><span class="label">Stato</span>
          <select class="input" name="status">
            <option value="pending">In attesa</option><option value="confirmed" selected>Confermata</option>
            <option value="checked_in">Check-in</option><option value="completed">Completata</option>
          </select>
        </label>
        <label class="block"><span class="label">Sorgente</span>
          <select class="input" name="source">
            <option value="direct">Diretta</option><option value="airbnb">Airbnb</option>
            <option value="booking">Booking</option><option value="other">Altro</option>
          </select>
        </label>
      </div>
      <label class="block"><span class="label">Acconto già ricevuto (€)</span><input class="input" type="number" step="0.01" name="deposit" value="0"></label>
    </div>
  </div>

  <template x-if="q && q.nights > 0">
    <div class="card p-4 sm:p-5">
      <h3 class="font-display font-bold mb-3">Riepilogo prezzi</h3>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
        <div class="rounded-xl bg-ink-50 dark:bg-ink-900 p-3"><div class="text-xs text-ink-500">Notti</div><div class="text-lg" x-text="q.nights"></div></div>
        <div class="rounded-xl bg-ink-50 dark:bg-ink-900 p-3"><div class="text-xs text-ink-500">Pernottamento</div><div class="text-lg" x-text="fmt(q.nightlyTotal)"></div></div>
        <div class="rounded-xl bg-ink-50 dark:bg-ink-900 p-3"><div class="text-xs text-ink-500">Sconto</div><div class="text-lg" x-text="q.discount > 0 ? '-' + fmt(q.discount) : '—'"></div></div>
        <div class="rounded-xl bg-ink-50 dark:bg-ink-900 p-3"><div class="text-xs text-ink-500">Totale</div><div class="text-lg font-display font-bold" x-text="fmt(q.total)"></div></div>
      </div>
    </div>
  </template>
</form>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function newBooking() {
  return {
    apartment_id: <?= json_encode($preselect) ?>, from: '', to: '', guests: 2, coupon: '',
    q: null,
    fmt(n) { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n || 0); },
    async quote() {
      if (!this.from || !this.to) return;
      const r = await fetch('/api/quote.php', { method: 'POST', headers: { 'content-type': 'application/json' },
        body: JSON.stringify({ apartment_id: this.apartment_id, from: this.from, to: this.to, guests: this.guests, coupon: this.coupon }) });
      this.q = await r.json();
    }
  };
}
</script>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
