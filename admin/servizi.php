<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/services.php';
requireAdmin();

$group = $_GET['group'] ?? 'all';
$where = ['1=1']; $params = [];
if ($group !== 'all' && isset(SERVICE_GROUPS[$group])) {
    $types = SERVICE_GROUPS[$group];
    $where[] = "type IN ('" . implode("','", $types) . "')";
}

$items = rows('SELECT * FROM services WHERE ' . implode(' AND ', $where) . ' ORDER BY position ASC, name ASC', $params);
$counts = ['all' => (int)val('SELECT COUNT(*) FROM services')];
foreach (SERVICE_GROUPS as $g => $types) {
    $counts[$g] = (int)val("SELECT COUNT(*) FROM services WHERE type IN ('" . implode("','", $types) . "')");
}

$title = 'Servizi extra';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-serif text-3xl font-semibold tracking-tight">Servizi extra</h1>
      <p class="text-ink-500 mt-1">Veicoli, escursioni e transfer · <?= count($items) ?> totali</p>
    </div>
    <a href="/admin/servizio-edit.php" class="btn-primary"><i data-lucide="plus" class="size-[18px]"></i> Nuovo servizio</a>
  </div>

  <div class="card p-2 flex items-center gap-1 overflow-x-auto">
    <?php foreach ([
      'all' => ['Tutti', 'box'],
      'rental' => ['Noleggi', 'key-round'],
      'experience' => ['Escursioni', 'compass'],
      'transfer' => ['Transfer', 'plane-takeoff'],
    ] as $g => $info): $active = $group === $g; ?>
      <a href="?group=<?= e($g) ?>" class="text-sm px-3.5 py-2 rounded-lg flex items-center gap-2 font-medium transition <?= $active ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md' : 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800' ?>">
        <i data-lucide="<?= $info[1] ?>" class="size-[14px]"></i> <?= e($info[0]) ?>
        <span class="text-xs opacity-70">· <?= (int)$counts[$g] ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($items as $s): ?>
      <div class="card overflow-hidden p-0 card-hover">
        <div class="aspect-[16/10] bg-ink-100 dark:bg-ink-900 relative">
          <?php if ($s['cover_image']): ?><img src="<?= e($s['cover_image']) ?>" class="h-full w-full object-cover" alt=""><?php else: ?>
            <div class="h-full w-full flex items-center justify-center text-ink-300"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[32px]"></i></div>
          <?php endif; ?>
          <div class="absolute top-3 left-3 flex gap-2">
            <span class="badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[12px]"></i> <?= e(serviceTypeShort($s['type'])) ?></span>
            <?php if ($s['active']): ?><span class="badge bg-emerald-500 text-white">Attivo</span><?php else: ?><span class="badge bg-ink-200 text-ink-600">Off</span><?php endif; ?>
          </div>
        </div>
        <div class="p-5">
          <div class="font-display font-bold text-lg truncate"><?= e($s['name']) ?></div>
          <?php if ($s['resort_name']): ?>
            <div class="text-xs text-ink-500 mt-0.5 flex items-center gap-1 truncate"><i data-lucide="map-pin" class="size-[12px]"></i> <?= e($s['resort_name']) ?></div>
          <?php elseif (isTransfer($s['type'])): ?>
            <div class="text-xs text-ink-500 mt-0.5 truncate"><?= e($s['from_location']) ?> → <?= e($s['to_location']) ?></div>
          <?php elseif (isExperience($s['type']) && $s['duration_hours']): ?>
            <div class="text-xs text-ink-500 mt-0.5"><i data-lucide="clock" class="size-[12px] inline"></i> <?= rtrim(rtrim(number_format((float)$s['duration_hours'], 1), '0'), '.') ?>h</div>
          <?php endif; ?>
          <div class="flex items-baseline justify-between mt-3 pt-3 border-t border-ink-100 dark:border-ink-800/80">
            <?php if (isRental($s['type'])): ?>
              <span class="font-display font-bold tabular-nums"><?= fmtMoney((float)$s['daily_price']) ?></span><span class="text-xs text-ink-500">/giorno</span>
            <?php elseif (isExperience($s['type'])): ?>
              <span class="font-display font-bold tabular-nums"><?= fmtMoney((float)$s['price_per_person']) ?></span><span class="text-xs text-ink-500">/persona</span>
            <?php else: ?>
              <span class="font-display font-bold tabular-nums"><?= fmtMoney((float)$s['price_per_group']) ?></span><span class="text-xs text-ink-500">a tratta</span>
            <?php endif; ?>
          </div>
          <div class="grid grid-cols-2 gap-2 mt-3">
            <a href="/admin/servizio-edit.php?id=<?= e($s['id']) ?>" class="btn-outline text-sm py-2"><i data-lucide="edit" class="size-[14px]"></i> Modifica</a>
            <?php if (isRental($s['type'])): ?>
              <a href="/noleggio.php?slug=<?= e($s['slug']) ?>" target="_blank" class="btn-ghost text-sm py-2 border border-ink-200 dark:border-ink-700/80"><i data-lucide="external-link" class="size-[14px]"></i> Vedi</a>
            <?php elseif (isExperience($s['type'])): ?>
              <a href="/escursione.php?slug=<?= e($s['slug']) ?>" target="_blank" class="btn-ghost text-sm py-2 border border-ink-200 dark:border-ink-700/80"><i data-lucide="external-link" class="size-[14px]"></i> Vedi</a>
            <?php else: ?>
              <a href="/transfer.php?slug=<?= e($s['slug']) ?>" target="_blank" class="btn-ghost text-sm py-2 border border-ink-200 dark:border-ink-700/80"><i data-lucide="external-link" class="size-[14px]"></i> Vedi</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <div class="card p-14 text-center">
      <div class="font-display font-bold text-xl">Nessun servizio</div>
      <a href="/admin/servizio-edit.php" class="btn-primary mt-6 inline-flex"><i data-lucide="plus" class="size-[18px]"></i> Crea primo servizio</a>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
