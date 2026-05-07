<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/services.php';

$transfers = rows("SELECT * FROM services WHERE type = 'transfer' AND active = 1 ORDER BY position ASC");
$selectedSlug = $_GET['slug'] ?? ($transfers[0]['slug'] ?? '');
$selected = null;
foreach ($transfers as $t) if ($t['slug'] === $selectedSlug) $selected = $t;
if (!$selected && $transfers) $selected = $transfers[0];

$title = 'Transfer aeroporto';
$metaDesc = 'Transfer privato dall\'aeroporto di Sharm El Sheikh a tutti i villaggi e appartamenti. Auto e minibus disponibili 24/7.';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<section class="relative isolate -mt-[68px] pt-[68px] overflow-hidden">
  <div class="absolute inset-0 -z-10 gradient-mesh"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-b from-transparent to-white dark:to-ink-950"></div>
  <div class="container-wide pt-12 md:pt-16 pb-8">
    <div class="badge-brand mb-3"><i data-lucide="plane-takeoff" class="size-[12px]"></i> Transfer</div>
    <h1 class="font-serif text-5xl md:text-6xl font-semibold tracking-tight text-balance">Dal volo al letto.</h1>
    <p class="text-ink-600 dark:text-ink-300 mt-3 text-lg max-w-xl text-pretty">Transfer privato dall'aeroporto di Sharm El Sheikh a qualsiasi villaggio o appartamento. Auto fino a 4 persone o minibus 7 posti.</p>
  </div>
</section>

<section class="container-wide py-10 grid lg:grid-cols-3 gap-8">
  <div class="lg:col-span-2 space-y-3">
    <h2 class="font-display font-bold text-xl mb-2">Tratte disponibili</h2>
    <?php foreach ($transfers as $t): ?>
      <a href="?slug=<?= e($t['slug']) ?>" class="card p-5 flex items-center justify-between gap-4 card-hover <?= $selected && $selected['id'] === $t['id'] ? 'ring-2 ring-brand-500' : '' ?>">
        <div class="flex items-center gap-4">
          <span class="h-12 w-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center"><i data-lucide="plane-takeoff" class="size-[20px]"></i></span>
          <div>
            <div class="font-display font-bold text-lg"><?= e($t['name']) ?></div>
            <div class="text-sm text-ink-500"><?= e($t['from_location']) ?> → <?= e($t['to_location']) ?> · fino a <?= (int)$t['vehicle_capacity'] ?> persone</div>
          </div>
        </div>
        <div class="text-right">
          <div class="font-display font-bold text-xl tabular-nums"><?= fmtMoney((float)$t['price_per_group']) ?></div>
          <div class="text-xs text-ink-500">a tratta</div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($selected): ?>
    <aside id="booking-form" class="lg:sticky lg:top-24 self-start scroll-mt-24" x-data="transferForm()">
      <div class="card-elev p-6">
        <div class="font-display font-bold mb-1"><?= e($selected['name']) ?></div>
        <div class="text-sm text-ink-500 mb-4"><?= fmtMoney((float)$selected['price_per_group']) ?> · fino a <?= (int)$selected['vehicle_capacity'] ?> persone</div>

        <template x-if="done">
          <div class="text-center py-6">
            <div class="h-16 w-16 mx-auto rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3"><i data-lucide="check" class="size-[32px]"></i></div>
            <div class="font-display text-xl font-bold">Transfer prenotato!</div>
            <p class="font-mono text-base mt-1" x-text="done"></p>
          </div>
        </template>

        <form x-show="!done" @submit.prevent="submit" class="space-y-3">
          <div class="grid grid-cols-2 gap-2 rounded-xl border border-ink-200 dark:border-ink-700/80 overflow-hidden">
            <label class="block p-3 border-r border-ink-200 dark:border-ink-700/80">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Data arrivo</span>
              <input type="date" required class="w-full bg-transparent outline-none text-sm font-medium mt-1" x-model="from">
            </label>
            <label class="block p-3">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Ora volo</span>
              <input type="time" required class="w-full bg-transparent outline-none text-sm font-medium mt-1" x-model="time">
            </label>
          </div>
          <label class="block p-3 rounded-xl border border-ink-200 dark:border-ink-700/80">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Numero volo</span>
            <input class="w-full bg-transparent outline-none text-sm font-medium mt-1" placeholder="es. KP551" x-model="flight">
          </label>
          <label class="block p-3 rounded-xl border border-ink-200 dark:border-ink-700/80">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Indirizzo destinazione</span>
            <input class="w-full bg-transparent outline-none text-sm font-medium mt-1" placeholder="hotel o appartamento" x-model="dropoff">
          </label>
          <label class="block p-3 rounded-xl border border-ink-200 dark:border-ink-700/80">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Passeggeri</span>
            <input type="number" min="1" max="<?= (int)$selected['vehicle_capacity'] ?>" class="w-full bg-transparent outline-none text-sm font-medium mt-1" x-model.number="participants">
          </label>
          <div class="space-y-2 pt-2">
            <input required placeholder="Nome e cognome" class="input" x-model="name">
            <div class="grid grid-cols-2 gap-2">
              <input type="email" required placeholder="Email" class="input" x-model="email">
              <input required placeholder="Telefono" class="input" x-model="phone">
            </div>
          </div>
          <div x-show="err" x-text="err" class="text-sm text-red-600 p-2 rounded-lg bg-red-50"></div>
          <button :disabled="busy" class="btn-primary w-full h-12 text-base">
            <span x-show="!busy">Prenota transfer · <?= fmtMoney((float)$selected['price_per_group']) ?></span>
            <span x-show="busy">Invio…</span>
          </button>
          <p class="text-[11px] text-ink-500 text-center">Pagamento direttamente al conducente o online.</p>
        </form>
      </div>
    </aside>
  <?php endif; ?>
</section>

<?php if ($selected): ?>
<script>
function transferForm() {
  return {
    from: '', time: '', flight: '', dropoff: '', participants: 2, name: '', email: '', phone: '',
    busy: false, done: null, err: '',
    async submit() {
      this.busy = true; this.err = '';
      try {
        const r = await fetch('/api/service-booking.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ service_id: <?= json_encode($selected['id']) ?>, from: this.from, pickup_time: this.time, flight_number: this.flight, dropoff_location: this.dropoff, pickup_location: 'Aeroporto SSH', participants: this.participants, name: this.name, email: this.email, phone: this.phone }) });
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
        <span class="font-display text-xl font-bold"><?= fmtMoney((float)$selected['price_per_group']) ?></span>
        <span class="text-xs text-ink-500">a tratta</span>
      </div>
      <div class="text-xs text-ink-500 mt-0.5 truncate"><?= e($selected['name']) ?></div>
    </div>
    <a href="#booking-form" class="btn-primary h-12 px-5 shrink-0">Prenota <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</div>
<div class="lg:hidden h-20"></div>
<?php endif; ?>
<?php require __DIR__ . '/partials/site-footer.php';
