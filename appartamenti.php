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
<div class="max-w-7xl mx-auto px-5 py-10">
  <h1 class="font-display text-3xl md:text-4xl font-bold">I nostri appartamenti</h1>
  <p class="text-ink-500 mt-2">Trova la casa giusta per il tuo soggiorno.</p>

  <form method="get" class="card p-2 flex flex-col md:flex-row gap-2 md:gap-1 md:items-center mt-6">
    <div class="flex-1 flex items-center gap-2 px-3 py-2">
      <i data-lucide="map-pin" class="size-[18px] text-brand-500"></i>
      <select name="city" class="bg-transparent flex-1 outline-none">
        <option value="">Tutte le città</option>
        <?php foreach ($cities as $c): ?><option value="<?= e($c) ?>" <?= ($_GET['city'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="flex-1 flex items-center gap-2 px-3 py-2">
      <i data-lucide="calendar" class="size-[18px] text-brand-500"></i>
      <input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>" class="bg-transparent flex-1 outline-none">
    </div>
    <div class="flex-1 flex items-center gap-2 px-3 py-2">
      <i data-lucide="calendar" class="size-[18px] text-brand-500"></i>
      <input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>" class="bg-transparent flex-1 outline-none">
    </div>
    <div class="flex items-center gap-2 px-3 py-2">
      <i data-lucide="users" class="size-[18px] text-brand-500"></i>
      <input type="number" name="guests" min="1" value="<?= (int)($_GET['guests'] ?? 2) ?>" class="bg-transparent w-16 outline-none">
      <span class="text-sm text-ink-500">ospiti</span>
    </div>
    <button class="btn-primary md:px-5"><i data-lucide="search" class="size-[18px]"></i> Cerca</button>
  </form>

  <?php if (!empty($_GET['from']) && !empty($_GET['to'])): ?>
    <div class="text-sm text-ink-500 mt-4">Disponibilità per <?= nightsBetween($_GET['from'], $_GET['to']) ?> notti — <?= count($apartments) ?> risultati</div>
  <?php endif; ?>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
    <?php foreach ($apartments as $a):
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
    <?php endforeach; ?>
  </div>

  <?php if (!$apartments): ?>
    <div class="card p-10 text-center mt-8">
      <div class="font-semibold text-lg">Nessun appartamento disponibile</div>
      <div class="text-ink-500 mt-1">Prova ad ampliare le date o i filtri di ricerca.</div>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/site-footer.php';
