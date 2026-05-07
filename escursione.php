<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/services.php';

$slug = $_GET['slug'] ?? '';
$s = row('SELECT * FROM services WHERE slug = ? AND active = 1', [$slug]);
if (!$s || !isExperience($s['type'])) {
    http_response_code(404);
    $title = 'Non trovato';
    require __DIR__ . '/partials/head.php';
    require __DIR__ . '/partials/site-header.php';
    echo '<div class="container-narrow card p-14 mt-20 text-center"><h1 class="font-serif text-3xl">Escursione non trovata</h1><a href="/escursioni.php" class="btn-primary mt-6 inline-flex">Tutte le escursioni</a></div>';
    require __DIR__ . '/partials/site-footer.php';
    exit;
}

$gallery = parseFeatures($s['gallery']);
if (!$gallery && $s['cover_image']) $gallery = [$s['cover_image']];
$includes = parseFeatures($s['includes']);
$excludes = parseFeatures($s['excludes']);
$days = $s['schedule_days'] ? array_map('trim', explode(',', $s['schedule_days'])) : [];

$title = $s['name'];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<div class="container-wide pt-8 pb-4">
  <a href="/escursioni.php" class="text-sm text-ink-500 hover:text-brand-600 inline-flex items-center gap-1"><i data-lucide="chevron-left" class="size-[14px]"></i> Tutte le escursioni</a>
  <div class="mt-4">
    <div class="badge-brand mb-3"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[12px]"></i> <?= e(serviceTypeLabel($s['type'])) ?></div>
    <h1 class="font-serif text-4xl md:text-6xl font-semibold tracking-tight max-w-3xl text-balance"><?= e($s['name']) ?></h1>
    <div class="flex flex-wrap items-center gap-4 text-sm text-ink-500 mt-3">
      <?php if ($s['duration_hours']): ?><span class="flex items-center gap-1"><i data-lucide="clock" class="size-[14px]"></i> <?= rtrim(rtrim(number_format((float)$s['duration_hours'], 1), '0'), '.') ?> ore</span><?php endif; ?>
      <?php if ($s['group_size_max']): ?><span class="flex items-center gap-1"><i data-lucide="users" class="size-[14px]"></i> Max <?= (int)$s['group_size_max'] ?> persone</span><?php endif; ?>
      <?php if ($s['meeting_point']): ?><span class="flex items-center gap-1"><i data-lucide="map-pin" class="size-[14px]"></i> <?= e($s['meeting_point']) ?></span><?php endif; ?>
    </div>
  </div>
</div>

<div class="container-wide pb-4">
  <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 rounded-3xl overflow-hidden h-[300px] sm:h-[420px]">
    <?php foreach (array_slice($gallery, 0, 3) as $i => $url): ?>
      <div class="bg-ink-100 dark:bg-ink-900 <?= $i === 0 ? 'col-span-2 row-span-2' : '' ?>"><img src="<?= e($url) ?>" class="h-full w-full object-cover"></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="container-wide pb-20">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2 space-y-8">
      <div>
        <h2 class="font-serif text-3xl font-semibold tracking-tight mb-3">L'esperienza</h2>
        <p class="text-ink-700 dark:text-ink-300 whitespace-pre-line leading-relaxed text-pretty"><?= e($s['description']) ?></p>
      </div>

      <?php if ($includes || $excludes): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <?php if ($includes): ?>
            <div class="card p-5">
              <div class="font-display font-bold mb-3 flex items-center gap-2 text-emerald-600"><i data-lucide="check-circle-2" class="size-[16px]"></i> Incluso</div>
              <ul class="space-y-1.5">
                <?php foreach ($includes as $i): ?><li class="flex items-start gap-2 text-sm"><i data-lucide="check" class="size-[14px] text-emerald-500 mt-0.5 shrink-0"></i> <?= e($i) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <?php if ($excludes): ?>
            <div class="card p-5">
              <div class="font-display font-bold mb-3 flex items-center gap-2 text-ink-500"><i data-lucide="x-circle" class="size-[16px]"></i> Non incluso</div>
              <ul class="space-y-1.5">
                <?php foreach ($excludes as $i): ?><li class="flex items-start gap-2 text-sm"><i data-lucide="x" class="size-[14px] text-ink-400 mt-0.5 shrink-0"></i> <?= e($i) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($days): ?>
        <div>
          <h2 class="font-serif text-2xl font-semibold tracking-tight mb-3">Giorni disponibili</h2>
          <div class="flex flex-wrap gap-2">
            <?php foreach (['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $d):
              $on = in_array($d, $days, true);
            ?>
              <span class="px-3.5 py-2 rounded-xl text-sm font-medium <?= $on ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'bg-ink-100 text-ink-400 dark:bg-ink-800 line-through' ?>"><?= $d ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <aside id="booking-form" class="lg:sticky lg:top-24 self-start scroll-mt-24" x-data="experienceForm()">
      <div class="card-elev p-6">
        <div class="flex items-baseline gap-1 mb-4">
          <span class="font-display text-3xl font-bold"><?= fmtMoney((float)$s['price_per_person']) ?></span>
          <span class="text-sm text-ink-500">/persona</span>
        </div>
        <template x-if="done">
          <div class="text-center py-6 animate-fade-in">
            <div class="h-16 w-16 mx-auto rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3"><i data-lucide="check" class="size-[32px]"></i></div>
            <div class="font-display text-xl font-bold">Richiesta inviata!</div>
            <p class="font-mono text-base mt-1" x-text="done"></p>
          </div>
        </template>
        <form x-show="!done" @submit.prevent="submit" class="space-y-3">
          <label class="block p-3 rounded-xl border border-ink-200 dark:border-ink-700/80">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Data</span>
            <input type="date" required class="w-full bg-transparent outline-none text-sm font-medium mt-1" x-model="from" @change="quote()">
          </label>
          <label class="block p-3 rounded-xl border border-ink-200 dark:border-ink-700/80">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Partecipanti</span>
            <input type="number" min="<?= (int)($s['group_size_min'] ?: 1) ?>" max="<?= (int)($s['group_size_max'] ?: 30) ?>" class="w-full bg-transparent outline-none text-sm font-medium mt-1" x-model.number="participants" @input="quote()">
          </label>
          <label class="block p-3 rounded-xl border border-ink-200 dark:border-ink-700/80">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Hotel / Appartamento</span>
            <input class="w-full bg-transparent outline-none text-sm font-medium mt-1" placeholder="es. Naama Bay Sea View" x-model="pickup">
          </label>
          <template x-if="q && q.total > 0">
            <div class="rounded-xl bg-ink-50 dark:bg-ink-900/40 p-4 text-sm space-y-2">
              <div class="flex justify-between"><span class="text-ink-500"><span x-text="q.participants"></span> × persona</span><span class="font-medium tabular-nums" x-text="fmt(q.base)"></span></div>
              <template x-if="q.discount > 0">
                <div class="flex justify-between text-emerald-600"><span x-text="q.discountLabel"></span><span class="tabular-nums" x-text="'-' + fmt(q.discount)"></span></div>
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
          </div>
          <div x-show="err" x-text="err" class="text-sm text-red-600 p-2 rounded-lg bg-red-50"></div>
          <button :disabled="busy" class="btn-primary w-full h-12 text-base">
            <span x-show="!busy">Prenota escursione</span>
            <span x-show="busy">Invio…</span>
          </button>
        </form>
      </div>
    </aside>
  </div>
</div>
<script>
function experienceForm() {
  return {
    from: '', participants: <?= (int)($s['group_size_min'] ?: 1) ?>, pickup: '', name: '', email: '', phone: '',
    q: null, busy: false, done: null, err: '',
    fmt(n) { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n || 0); },
    init() { this.quote(); },
    async quote() {
      try {
        const r = await fetch('/api/service-quote.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ service_id: <?= json_encode($s['id']) ?>, participants: this.participants }) });
        this.q = await r.json();
      } catch (e) {}
    },
    async submit() {
      this.busy = true; this.err = '';
      try {
        const r = await fetch('/api/service-booking.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ service_id: <?= json_encode($s['id']) ?>, from: this.from, participants: this.participants, pickup_location: this.pickup, name: this.name, email: this.email, phone: this.phone }) });
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
        <span class="font-display text-xl font-bold"><?= fmtMoney((float)$s['price_per_person']) ?></span>
        <span class="text-xs text-ink-500">/persona</span>
      </div>
      <div class="text-xs text-ink-500 mt-0.5 truncate"><?= e($s['name']) ?></div>
    </div>
    <a href="#booking-form" class="btn-primary h-12 px-5 shrink-0"><?= e(t('exc.detail.book')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</div>
<div class="lg:hidden h-20"></div>

<?php require __DIR__ . '/partials/site-footer.php';
