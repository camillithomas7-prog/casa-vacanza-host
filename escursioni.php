<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/services.php';

$cat = $_GET['cat'] ?? '';
$expTypes = SERVICE_GROUPS['experience'];
$where = ['active = 1', "type IN ('" . implode("','", $expTypes) . "')"];
$params = [];
if ($cat && in_array($cat, $expTypes, true)) { $where[] = 'type = ?'; $params[] = $cat; }

$items = rows('SELECT * FROM services WHERE ' . implode(' AND ', $where) . ' ORDER BY position ASC', $params);

$title = t('nav.excursions');
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
$_lp = currentLang() !== 'it' ? '?lang=' . urlencode(currentLang()) : '';
$_lpa = currentLang() !== 'it' ? '&lang=' . urlencode(currentLang()) : '';
?>
<section class="relative isolate -mt-[68px] pt-[68px] overflow-hidden">
  <div class="absolute inset-0 -z-10 gradient-mesh"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-b from-transparent to-white dark:to-ink-950"></div>
  <div class="container-wide pt-12 md:pt-16 pb-8">
    <div class="badge-brand mb-3"><i data-lucide="compass" class="size-[12px]"></i> <?= e(t('nav.excursions')) ?></div>
    <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl font-semibold tracking-tight text-balance"><?= e(t('exc.title')) ?></h1>
    <p class="text-ink-600 dark:text-ink-300 mt-3 text-lg max-w-xl text-pretty"><?= e(t('exc.sub')) ?></p>

    <div class="mt-8 flex flex-wrap gap-2">
      <a href="/escursioni.php<?= $_lp ?>" class="px-4 py-2 rounded-xl text-sm font-medium <?= !$cat ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900' : 'bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 hover:bg-ink-50' ?>"><?= e(t('rent.filter.all')) ?></a>
      <?php foreach ($expTypes as $t):
        $count = (int)val('SELECT COUNT(*) FROM services WHERE type = ? AND active = 1', [$t]);
        if ($count === 0) continue;
      ?>
        <a href="/escursioni.php?cat=<?= e($t) ?><?= $_lpa ?>" class="px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 <?= $cat === $t ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900' : 'bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 hover:bg-ink-50' ?>">
          <i data-lucide="<?= e(serviceTypeIcon($t)) ?>" class="size-[14px]"></i> <?= e(serviceTypeShort($t)) ?> <span class="text-xs opacity-60">· <?= $count ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="container-wide py-10">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($items as $i => $s): ?>
      <a href="/escursione.php?slug=<?= e($s['slug']) ?><?= currentLang() !== 'it' ? '&lang=' . urlencode(currentLang()) : '' ?>" class="group block animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
        <div class="aspect-[4/5] rounded-2xl overflow-hidden bg-ink-100 dark:bg-ink-900 relative shadow-card">
          <?php if ($s['cover_image']): ?><img src="<?= e($s['cover_image']) ?>" alt="<?= e($s['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo"><?php endif; ?>
          <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/0 to-black/15"></div>
          <div class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[12px]"></i> <?= e(serviceTypeShort($s['type'])) ?></div>
          <?php if ($s['duration_hours']): ?>
            <div class="absolute top-3 right-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="clock" class="size-[12px]"></i> <?= rtrim(rtrim(number_format((float)$s['duration_hours'], 1), '0'), '.') ?> h</div>
          <?php endif; ?>
          <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
            <div class="font-display font-bold text-xl"><?= e($s['name']) ?></div>
            <div class="flex items-baseline gap-1 mt-1">
              <span class="font-display font-bold text-2xl tabular-nums"><?= fmtMoney((float)$s['price_per_person']) ?></span>
              <span class="text-xs text-white/80"><?= e(t('common.per_person')) ?></span>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (!$items): ?><div class="card p-14 text-center"><div class="font-display font-bold text-xl"><?= e(t('exc.no_items')) ?></div></div><?php endif; ?>
</section>

<?php require __DIR__ . '/partials/site-footer.php';
