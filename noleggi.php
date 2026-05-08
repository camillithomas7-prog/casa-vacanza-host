<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/services.php';

if (!featureEnabled('rentals')) { redirect('/' . (currentLang() !== 'it' ? '?lang=' . currentLang() : '')); }

$type = $_GET['type'] ?? '';
$rentalTypes = SERVICE_GROUPS['rental'];
$where = ['active = 1', "type IN ('" . implode("','", $rentalTypes) . "')"];
$params = [];
if ($type && in_array($type, $rentalTypes, true)) { $where[] = 'type = ?'; $params[] = $type; }

$items = rows('SELECT * FROM services WHERE ' . implode(' AND ', $where) . ' ORDER BY position ASC, daily_price ASC', $params);

$title = t('nav.rentals');
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
$_lp = currentLang() !== 'it' ? '?lang=' . urlencode(currentLang()) : '';
$_lpa = currentLang() !== 'it' ? '&lang=' . urlencode(currentLang()) : '';
?>
<section class="relative isolate -mt-[68px] pt-[68px] overflow-hidden">
  <div class="absolute inset-0 -z-10 gradient-mesh"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-b from-transparent to-white dark:to-ink-950"></div>
  <div class="container-wide pt-12 md:pt-16 pb-8">
    <div class="badge-brand mb-3"><i data-lucide="key-round" class="size-[12px]"></i> <?= e(t('nav.rentals')) ?></div>
    <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl font-semibold tracking-tight text-balance"><?= e(t('rent.title')) ?></h1>
    <p class="text-ink-600 dark:text-ink-300 mt-3 text-lg max-w-xl text-pretty"><?= e(t('rent.sub')) ?></p>

    <div class="mt-8 flex flex-wrap gap-2">
      <a href="/noleggi.php<?= $_lp ?>" class="px-4 py-2 rounded-xl text-sm font-medium <?= !$type ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900' : 'bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 hover:bg-ink-50' ?>"><?= e(t('rent.filter.all')) ?></a>
      <?php foreach ($rentalTypes as $t):
        $count = (int)val('SELECT COUNT(*) FROM services WHERE type = ? AND active = 1', [$t]);
      ?>
        <a href="/noleggi.php?type=<?= e($t) ?><?= $_lpa ?>" class="px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 <?= $type === $t ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900' : 'bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 hover:bg-ink-50' ?>">
          <i data-lucide="<?= e(serviceTypeIcon($t)) ?>" class="size-[14px]"></i> <?= e(serviceTypeShort($t)) ?> <span class="text-xs opacity-60">· <?= $count ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="container-wide py-10">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($items as $i => $s): ?>
      <a href="/noleggio.php?slug=<?= e($s['slug']) ?><?= currentLang() !== 'it' ? '&lang=' . urlencode(currentLang()) : '' ?>" class="group block animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
        <div class="aspect-[4/3] rounded-2xl overflow-hidden bg-ink-100 dark:bg-ink-900 relative shadow-card">
          <?php if ($s['cover_image']): ?><img src="<?= e($s['cover_image']) ?>" alt="<?= e($s['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo"><?php endif; ?>
          <div class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[12px]"></i> <?= e(serviceTypeShort($s['type'])) ?></div>
          <?php if ($s['resort_name']): ?>
            <div class="absolute top-3 right-3 badge bg-brand-500 text-white backdrop-blur"><?= e(t('rent.at_resort')) ?></div>
          <?php endif; ?>
        </div>
        <div class="mt-4">
          <div class="font-display font-bold text-lg truncate"><?= e($s['name']) ?></div>
          <?php if ($s['resort_name']): ?>
            <div class="text-sm text-ink-500 mt-0.5 flex items-center gap-1"><i data-lucide="map-pin" class="size-[12px]"></i> <?= e($s['resort_name']) ?></div>
          <?php endif; ?>
          <div class="flex items-baseline justify-between mt-3">
            <div>
              <span class="font-display font-bold text-xl tabular-nums"><?= fmtMoney((float)$s['daily_price']) ?></span>
              <span class="text-xs text-ink-500"><?= e(t('common.per_day')) ?></span>
            </div>
            <?php if ($s['weekly_price']): ?><span class="text-xs text-ink-500"><?= t('rent.from_weekly', ['p' => fmtMoney((float)$s['weekly_price'])]) ?></span><?php endif; ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <div class="card p-14 text-center">
      <div class="font-display font-bold text-xl"><?= e(t('rent.no_items')) ?></div>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/site-footer.php';
