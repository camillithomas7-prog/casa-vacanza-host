<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

if (!tableExists('apartments')) {
    $title = 'Setup richiesto';
    require __DIR__ . '/partials/head.php';
    echo '<div class="max-w-xl mx-auto p-8 mt-20 card text-center"><h1 class="font-display text-2xl font-bold">Database non inizializzato</h1><p class="mt-2 text-ink-500">Esegui <code class="px-2 py-1 rounded bg-ink-100 dark:bg-ink-800 font-mono text-sm">/setup.php</code> per creare le tabelle e caricare i dati demo.</p><a href="/setup.php" class="btn-primary mt-4">Esegui setup</a></div>';
    require __DIR__ . '/partials/site-footer.php';
    exit;
}

$apartments = rows('SELECT a.* FROM apartments a WHERE a.active = 1 ORDER BY a.created_at DESC LIMIT 6');
$cities = array_filter(array_unique(array_column($apartments, 'city')));

$title = 'Casa Vacanza · Affitti brevi premium';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<main class="animate-fade-in">
  <section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-brand-50 via-white to-white dark:from-ink-900 dark:via-ink-950 dark:to-ink-950"></div>
    <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-brand-200/40 blur-3xl"></div>
    <div class="absolute -left-32 top-40 h-72 w-72 rounded-full bg-brand-300/30 blur-3xl"></div>
    <div class="relative max-w-7xl mx-auto px-5 py-20 md:py-28">
      <div class="max-w-3xl">
        <span class="badge bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800 text-ink-600 dark:text-ink-300"><i data-lucide="sparkles" class="size-[14px] text-brand-500"></i> Selezionati a mano</span>
        <h1 class="font-display text-4xl md:text-6xl font-bold mt-5 leading-tight">
          La tua prossima vacanza<br><span class="bg-gradient-to-r from-brand-500 to-brand-700 bg-clip-text text-transparent">inizia da qui.</span>
        </h1>
        <p class="text-lg text-ink-600 dark:text-ink-300 mt-5 max-w-xl">
          Appartamenti curati e gestiti direttamente, con check-in semplice e prezzi trasparenti.
        </p>
      </div>
      <form action="/appartamenti.php" method="get" class="card p-2 flex flex-col md:flex-row gap-2 md:gap-1 md:items-center mt-10">
        <div class="flex-1 flex items-center gap-2 px-3 py-2">
          <i data-lucide="map-pin" class="size-[18px] text-brand-500"></i>
          <select name="city" class="bg-transparent flex-1 outline-none">
            <option value="">Tutte le città</option>
            <?php foreach ($cities as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="hidden md:block w-px h-8 bg-ink-100 dark:bg-ink-800"></div>
        <div class="flex-1 flex items-center gap-2 px-3 py-2">
          <i data-lucide="calendar" class="size-[18px] text-brand-500"></i>
          <input type="date" name="from" class="bg-transparent flex-1 outline-none">
        </div>
        <div class="flex-1 flex items-center gap-2 px-3 py-2">
          <i data-lucide="calendar" class="size-[18px] text-brand-500"></i>
          <input type="date" name="to" class="bg-transparent flex-1 outline-none">
        </div>
        <div class="hidden md:block w-px h-8 bg-ink-100 dark:bg-ink-800"></div>
        <div class="flex items-center gap-2 px-3 py-2">
          <i data-lucide="users" class="size-[18px] text-brand-500"></i>
          <input type="number" name="guests" min="1" value="2" class="bg-transparent w-16 outline-none">
          <span class="text-sm text-ink-500">ospiti</span>
        </div>
        <button class="btn-primary md:px-5"><i data-lucide="search" class="size-[18px]"></i> Cerca</button>
      </form>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-5 py-16">
    <div class="flex items-end justify-between mb-8">
      <div>
        <h2 class="font-display text-3xl font-bold">Le nostre case</h2>
        <p class="text-ink-500 mt-1">Una selezione delle proprietà più richieste.</p>
      </div>
      <a href="/appartamenti.php" class="btn-outline hidden sm:inline-flex">Vedi tutto</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($apartments as $a) {
        $cover = $a['cover_image'] ?: (rows('SELECT url FROM photos WHERE apartment_id = ? ORDER BY position ASC LIMIT 1', [$a['id']])[0]['url'] ?? '');
        $reviews = rows('SELECT rating FROM reviews WHERE apartment_id = ? AND approved = 1', [$a['id']]);
        $rating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;
        ?>
        <a href="/appartamento.php?slug=<?= e($a['slug']) ?>" class="card overflow-hidden group hover:shadow-lg transition-all p-0 block">
          <div class="aspect-[4/3] overflow-hidden bg-ink-100 dark:bg-ink-900 relative">
            <img src="<?= e($cover) ?>" alt="<?= e($a['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition">
            <?php if ($rating): ?><span class="absolute top-3 right-3 badge bg-white/90 text-ink-900"><i data-lucide="star" class="size-[12px] fill-yellow-400 text-yellow-400"></i> <?= number_format($rating, 1) ?></span><?php endif; ?>
          </div>
          <div class="p-4">
            <div class="flex items-center justify-between gap-2">
              <div class="font-semibold truncate"><?= e($a['name']) ?></div>
              <div class="text-sm text-ink-500 flex items-center gap-1"><i data-lucide="map-pin" class="size-[14px]"></i> <?= e($a['city']) ?></div>
            </div>
            <div class="flex gap-3 text-sm text-ink-500 mt-2">
              <span class="flex items-center gap-1"><i data-lucide="users" class="size-[14px]"></i> <?= (int)$a['guests'] ?></span>
              <span class="flex items-center gap-1"><i data-lucide="bed-double" class="size-[14px]"></i> <?= (int)$a['bedrooms'] ?></span>
              <span class="flex items-center gap-1"><i data-lucide="bath" class="size-[14px]"></i> <?= (int)$a['bathrooms'] ?></span>
            </div>
            <div class="mt-3 flex items-baseline gap-1">
              <span class="font-display text-xl font-bold"><?= fmtMoney((float)$a['base_price']) ?></span>
              <span class="text-sm text-ink-500">/notte</span>
            </div>
          </div>
        </a>
      <?php } ?>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-5 py-16">
    <div class="grid md:grid-cols-3 gap-6">
      <?php foreach ([
        ['shield-check', 'Pagamenti sicuri', 'Acconto e saldo gestiti in modo trasparente, ricevuta sempre disponibile.'],
        ['key-round', 'Check-in semplice', 'Istruzioni chiare e self check-in dove disponibile, supporto WhatsApp 7/7.'],
        ['star', 'Recensioni reali', 'Solo clienti verificati, niente sorprese all\'arrivo.'],
      ] as $f): ?>
        <div class="card p-6">
          <div class="h-12 w-12 rounded-2xl bg-brand-50 dark:bg-brand-900/30 flex items-center justify-center mb-4"><i data-lucide="<?= $f[0] ?>" class="text-brand-500"></i></div>
          <div class="font-display font-bold text-xl"><?= e($f[1]) ?></div>
          <p class="text-ink-500 mt-2 text-sm"><?= e($f[2]) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/partials/site-footer.php';
