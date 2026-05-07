<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/services.php';

$slug = $_GET['slug'] ?? '';
$s = row('SELECT * FROM services WHERE slug = ? AND active = 1', [$slug]);
if (!$s || !isRental($s['type'])) {
    http_response_code(404);
    $title = 'Non trovato';
    require __DIR__ . '/partials/head.php';
    require __DIR__ . '/partials/site-header.php';
    echo '<div class="container-narrow card p-14 mt-20 text-center"><h1 class="font-serif text-3xl">Servizio non trovato</h1><a href="/noleggi.php" class="btn-primary mt-6 inline-flex">Vedi noleggi</a></div>';
    require __DIR__ . '/partials/site-footer.php';
    exit;
}

$gallery = parseFeatures($s['gallery']);
if (!$gallery && $s['cover_image']) $gallery = [$s['cover_image']];
$features = parseFeatures($s['features']);

$title = $s['name'];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<div class="container-wide pt-8 pb-4">
  <a href="/noleggi.php" class="text-sm text-ink-500 hover:text-brand-600 inline-flex items-center gap-1"><i data-lucide="chevron-left" class="size-[14px]"></i> Tutti i noleggi</a>
  <div class="mt-4">
    <div class="badge-brand mb-3"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[12px]"></i> <?= e(serviceTypeLabel($s['type'])) ?></div>
    <h1 class="font-serif text-4xl md:text-6xl font-semibold tracking-tight max-w-3xl text-balance"><?= e($s['name']) ?></h1>
    <?php if ($s['resort_name']): ?>
      <div class="text-sm text-ink-500 mt-2 flex items-center gap-1.5"><i data-lucide="map-pin" class="size-[14px]"></i> Disponibile presso <strong class="text-ink-900 dark:text-white"><?= e($s['resort_name']) ?></strong> · <?= e($s['resort_address']) ?></div>
    <?php endif; ?>
  </div>
</div>

<div class="container-wide pb-4">
  <div class="grid <?= count($gallery) >= 2 ? 'grid-cols-2' : 'grid-cols-1' ?> gap-2.5 rounded-3xl overflow-hidden h-[300px] sm:h-[420px]">
    <?php foreach (array_slice($gallery, 0, 4) as $url): ?>
      <div class="bg-ink-100 dark:bg-ink-900"><img src="<?= e($url) ?>" class="h-full w-full object-cover"></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="container-wide pb-20">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2 space-y-8">
      <div>
        <h2 class="font-serif text-3xl font-semibold tracking-tight mb-3">Descrizione</h2>
        <p class="text-ink-700 dark:text-ink-300 whitespace-pre-line leading-relaxed text-pretty"><?= e($s['description']) ?></p>
      </div>

      <?php if ($features): ?>
        <div>
          <h2 class="font-serif text-3xl font-semibold tracking-tight mb-4">Caratteristiche</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($features as $f): ?>
              <div class="flex items-center gap-3 p-3 rounded-xl bg-ink-50/60 dark:bg-ink-900/40 border border-ink-100/60 dark:border-ink-800/60">
                <span class="h-8 w-8 rounded-lg bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800/80 flex items-center justify-center text-brand-500"><i data-lucide="check" class="size-[14px]"></i></span>
                <span class="text-sm font-medium"><?= e($f) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="card p-6">
        <h2 class="font-serif text-2xl font-semibold tracking-tight mb-4">Tariffe</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <?php
          $rates = [
            ['Giornaliera', $s['daily_price']],
            ['Settimanale', $s['weekly_price']],
            ['2 settimane', $s['biweekly_price']],
            ['3 settimane', $s['triweekly_price']],
            ['Mensile', $s['monthly_price']],
          ];
          foreach ($rates as $r): if (!$r[1]) continue; ?>
            <div class="flex items-center justify-between p-3 rounded-xl bg-ink-50/60 dark:bg-ink-900/40">
              <span class="text-ink-500"><?= e($r[0]) ?></span>
              <span class="font-display font-bold tabular-nums"><?= fmtMoney((float)$r[1]) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($s['security_deposit']): ?>
          <div class="text-xs text-ink-500 mt-4">Cauzione richiesta: <strong><?= fmtMoney((float)$s['security_deposit']) ?></strong> (rimborsata alla riconsegna).</div>
        <?php endif; ?>
        <?php if ($s['license_required']): ?><div class="text-xs text-ink-500 mt-1">Patente di guida obbligatoria.</div><?php endif; ?>
        <?php if ($s['min_age']): ?><div class="text-xs text-ink-500 mt-1">Età minima: <?= (int)$s['min_age'] ?> anni.</div><?php endif; ?>
      </div>
    </div>

    <aside id="booking-form" class="lg:sticky lg:top-24 self-start scroll-mt-24" x-data="rentalForm()">
      <div class="card-elev p-6">
        <div class="flex items-baseline gap-1 mb-4">
          <span class="font-display text-3xl font-bold"><?= fmtMoney((float)$s['daily_price']) ?></span>
          <span class="text-sm text-ink-500">/giorno</span>
        </div>
        <template x-if="done">
          <div class="text-center py-6 animate-fade-in">
            <div class="h-16 w-16 mx-auto rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3"><i data-lucide="check" class="size-[32px]"></i></div>
            <div class="font-display text-xl font-bold">Richiesta inviata!</div>
            <p class="font-mono text-base mt-1" x-text="done"></p>
            <p class="text-xs text-ink-500 mt-3">Ti contatteremo a breve.</p>
          </div>
        </template>
        <form x-show="!done" @submit.prevent="submit" class="space-y-3">
          <div class="grid grid-cols-2 gap-2 rounded-xl border border-ink-200 dark:border-ink-700/80 overflow-hidden">
            <label class="block p-3 border-r border-ink-200 dark:border-ink-700/80">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Ritiro</span>
              <input type="date" required class="w-full bg-transparent outline-none text-sm font-medium mt-1" x-model="from" @change="quote()">
            </label>
            <label class="block p-3">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Riconsegna</span>
              <input type="date" required class="w-full bg-transparent outline-none text-sm font-medium mt-1" x-model="to" @change="quote()">
            </label>
          </div>
          <label class="block p-3 rounded-xl border border-ink-200 dark:border-ink-700/80">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Codice sconto</span>
            <input class="w-full bg-transparent outline-none text-sm font-medium mt-1" placeholder="opzionale" x-model="coupon" @input.debounce.500="quote()">
          </label>

          <template x-if="q && q.days > 0">
            <div class="rounded-xl bg-ink-50 dark:bg-ink-900/40 p-4 text-sm space-y-2 animate-slide-up">
              <div class="flex justify-between"><span class="text-ink-500"><span x-text="q.days"></span> giorni</span><span class="font-medium tabular-nums" x-text="fmt(q.nightlyTotal)"></span></div>
              <template x-if="q.discount > 0">
                <div class="flex justify-between text-emerald-600"><span x-text="q.discountLabel"></span><span class="tabular-nums" x-text="'-' + fmt(q.discount)"></span></div>
              </template>
              <template x-if="q.extras > 0">
                <div class="flex justify-between"><span class="text-ink-500">Extra</span><span class="tabular-nums" x-text="fmt(q.extras)"></span></div>
              </template>
              <div class="flex justify-between font-display font-bold text-base pt-2 mt-1 border-t border-ink-200 dark:border-ink-700/80"><span>Totale</span><span class="tabular-nums" x-text="fmt(q.total)"></span></div>
            </div>
          </template>

          <div class="space-y-2 pt-2">
            <input required placeholder="Nome e cognome" class="input" x-model="name">
            <div class="grid grid-cols-2 gap-2">
              <input type="email" required placeholder="Email" class="input" x-model="email">
              <input required placeholder="Telefono" class="input" x-model="phone">
            </div>
            <input placeholder="Note: numero patente, hotel, ecc." class="input" x-model="notes">
          </div>

          <div x-show="err" x-text="err" class="text-sm text-red-600 p-2 rounded-lg bg-red-50"></div>

          <button :disabled="busy" class="btn-primary w-full h-12 text-base">
            <span x-show="!busy">Richiedi noleggio</span>
            <span x-show="busy">Invio…</span>
          </button>
          <p class="text-[11px] text-ink-500 text-center">Conferma e dettagli pickup via WhatsApp.</p>
        </form>
      </div>
    </aside>
  </div>
</div>
<script>
function rentalForm() {
  return {
    from: '', to: '', coupon: '', name: '', email: '', phone: '', notes: '',
    q: null, busy: false, done: null, err: '',
    fmt(n) { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n || 0); },
    async quote() {
      if (!this.from || !this.to) return;
      try {
        const r = await fetch('/api/service-quote.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ service_id: <?= json_encode($s['id']) ?>, from: this.from, to: this.to, coupon: this.coupon }) });
        this.q = await r.json();
      } catch (e) {}
    },
    async submit() {
      this.busy = true; this.err = '';
      try {
        const r = await fetch('/api/service-booking.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ service_id: <?= json_encode($s['id']) ?>, from: this.from, to: this.to, coupon: this.coupon, name: this.name, email: this.email, phone: this.phone, notes: this.notes }) });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error || 'Errore');
        this.done = d.code;
      } catch (e) { this.err = e.message; } finally { this.busy = false; }
    }
  };
}
</script>

<!-- STICKY MOBILE CTA -->
<div class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 dark:bg-ink-950/95 backdrop-blur-xl border-t border-ink-100 dark:border-ink-800/80 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_-12px_rgba(0,0,0,.15)]">
  <div class="flex items-center justify-between gap-3">
    <div class="min-w-0">
      <div class="flex items-baseline gap-1">
        <span class="font-display text-xl font-bold"><?= fmtMoney((float)$s['daily_price']) ?></span>
        <span class="text-xs text-ink-500">/giorno</span>
      </div>
      <div class="text-xs text-ink-500 mt-0.5 truncate"><?= e($s['name']) ?></div>
    </div>
    <a href="#booking-form" class="btn-primary h-12 px-5 shrink-0"><?= e(t('rent.detail.book')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</div>
<div class="lg:hidden h-20"></div>

<?php require __DIR__ . '/partials/site-footer.php';
