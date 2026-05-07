<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

$where = ['active = 1'];
$params = [];
if (!empty($_GET['city'])) { $where[] = 'city = ?'; $params[] = $_GET['city']; }
if (!empty($_GET['guests'])) { $where[] = 'guests >= ?'; $params[] = (int)$_GET['guests']; }
if (!empty($_GET['min'])) { $where[] = 'base_price >= ?'; $params[] = (float)$_GET['min']; }
if (!empty($_GET['max'])) { $where[] = 'base_price <= ?'; $params[] = (float)$_GET['max']; }

$apartments = rows('SELECT * FROM apartments WHERE ' . implode(' AND ', $where) . ' ORDER BY base_price ASC', $params);

if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $f = $_GET['from']; $t = $_GET['to'];
    if (strtotime($t) > strtotime($f)) {
        $apartments = array_filter($apartments, function($a) use ($f, $t) {
            $overlap = (int)val('SELECT COUNT(*) FROM bookings WHERE apartment_id = ? AND status != "cancelled" AND check_in < ? AND check_out > ?', [$a['id'], $t, $f]);
            return $overlap === 0;
        });
    }
}

$cities = array_filter(array_unique(array_column(rows('SELECT DISTINCT city FROM apartments WHERE active = 1'), 'city')));

$title = 'Appartamenti';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<section class="relative isolate -mt-[68px] pt-[68px] overflow-hidden">
  <div class="absolute inset-0 -z-10 gradient-mesh"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-b from-transparent to-white dark:to-ink-950"></div>
  <div class="container-wide pt-12 md:pt-16 pb-8">
    <div class="max-w-3xl">
      <div class="badge-brand mb-3">La collezione</div>
      <h1 class="font-serif text-5xl md:text-6xl font-semibold tracking-tight text-balance">Trova la casa giusta.</h1>
      <p class="text-ink-600 dark:text-ink-300 mt-3 text-lg max-w-xl text-pretty">Filtra per zona di Sharm, date, ospiti e prezzo. Ogni casa è ispezionata personalmente.</p>
    </div>

    <form method="get" class="mt-8 card-elev p-2 grid grid-cols-1 md:grid-cols-[1.4fr_1fr_1fr_0.9fr_auto] gap-1">
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent">
        <i data-lucide="map-pin" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1">
          <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Dove</div>
          <select name="city" class="w-full bg-transparent outline-none text-sm font-medium">
            <option value="">Tutte le zone</option>
            <?php foreach ($cities as $c): ?><option value="<?= e($c) ?>" <?= ($_GET['city'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </div>
      </label>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="calendar" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Check-in</div>
          <input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="calendar" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Check-out</div>
          <input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="users" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Ospiti</div>
          <input type="number" name="guests" min="1" value="<?= (int)($_GET['guests'] ?? 2) ?>" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <button class="btn-primary h-full px-6"><i data-lucide="search" class="size-[18px]"></i> <span class="hidden sm:inline">Cerca</span></button>
    </form>

    <div class="flex items-center gap-2 flex-wrap mt-5 text-sm">
      <span class="text-ink-500">Filtri rapidi:</span>
      <?php foreach ([['guests=2','2 ospiti'],['guests=4','4+ ospiti'],['max=120','sotto i €120']] as $q): $url = '/appartamenti.php?' . $q[0]; ?>
        <a href="<?= e($url) ?>" class="badge-soft hover:bg-brand-100 dark:hover:bg-brand-500/15 hover:text-brand-700 dark:hover:text-brand-300 transition cursor-pointer"><?= e($q[1]) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="container-wide pb-16">
  <?php if (!empty($_GET['from']) && !empty($_GET['to'])): ?>
    <div class="text-sm text-ink-500 mb-4">Disponibilità per <?= nightsBetween($_GET['from'], $_GET['to']) ?> notti — <strong class="text-ink-900 dark:text-white"><?= count($apartments) ?> risultati</strong></div>
  <?php else: ?>
    <div class="text-sm text-ink-500 mb-4"><strong class="text-ink-900 dark:text-white"><?= count($apartments) ?></strong> case disponibili</div>
  <?php endif; ?>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($apartments as $a):
      $cover = $a['cover_image'] ?: (rows('SELECT url FROM photos WHERE apartment_id = ? ORDER BY position ASC LIMIT 1', [$a['id']])[0]['url'] ?? '');
      $morePics = rows('SELECT url FROM photos WHERE apartment_id = ? ORDER BY position ASC LIMIT 4', [$a['id']]);
      $reviews = rows('SELECT rating FROM reviews WHERE apartment_id = ? AND approved = 1', [$a['id']]);
      $rating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;
    ?>
      <a href="/appartamento.php?slug=<?= e($a['slug']) ?>" class="group block animate-slide-up">
        <div class="aspect-[4/5] sm:aspect-[5/6] rounded-2xl overflow-hidden bg-ink-100 dark:bg-ink-900 relative shadow-card">
          <img src="<?= e($cover) ?>" alt="<?= e($a['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo">
          <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/0 to-black/15"></div>
          <?php if ($rating): ?>
            <span class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="star" class="size-[12px] fill-amber-400 text-amber-400"></i> <?= number_format($rating, 1) ?></span>
          <?php endif; ?>
          <div class="absolute top-3 right-3 h-9 w-9 rounded-full bg-white/15 backdrop-blur-md flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition">
            <i data-lucide="arrow-up-right" class="size-[16px]"></i>
          </div>
          <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
            <div class="text-xs uppercase tracking-wider text-white/70"><?= e($a['city'] ?: $a['country']) ?></div>
            <div class="font-display font-bold text-xl mt-0.5 truncate"><?= e($a['name']) ?></div>
          </div>
        </div>
        <div class="mt-4 flex items-center justify-between">
          <div class="flex gap-3 text-sm text-ink-500 dark:text-ink-400">
            <span class="flex items-center gap-1"><i data-lucide="users" class="size-[14px]"></i> <?= (int)$a['guests'] ?></span>
            <span class="flex items-center gap-1"><i data-lucide="bed-double" class="size-[14px]"></i> <?= (int)$a['bedrooms'] ?></span>
            <span class="flex items-center gap-1"><i data-lucide="bath" class="size-[14px]"></i> <?= (int)$a['bathrooms'] ?></span>
          </div>
          <div class="text-right">
            <span class="font-display font-bold text-lg"><?= fmtMoney((float)$a['base_price']) ?></span>
            <span class="text-xs text-ink-500">/notte</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$apartments): ?>
    <div class="card p-14 text-center mt-8">
      <div class="h-16 w-16 mx-auto rounded-2xl bg-brand-50 dark:bg-brand-500/15 text-brand-600 flex items-center justify-center mb-4"><i data-lucide="search-x" class="size-[28px]"></i></div>
      <div class="font-display font-bold text-xl">Nessun appartamento corrisponde</div>
      <div class="text-ink-500 mt-1 max-w-md mx-auto">Prova ad ampliare le date o rimuovere alcuni filtri.</div>
      <a href="/appartamenti.php" class="btn-outline mt-6 inline-flex">Reset filtri</a>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/site-footer.php';
