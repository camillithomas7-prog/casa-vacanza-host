<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$items = rows('SELECT a.*, (SELECT url FROM photos WHERE apartment_id = a.id ORDER BY position ASC LIMIT 1) AS photo, (SELECT COUNT(*) FROM bookings WHERE apartment_id = a.id) AS bookings_count, (SELECT COALESCE(SUM(total),0) FROM bookings WHERE apartment_id = a.id AND status != "cancelled") AS revenue FROM apartments a ORDER BY a.created_at DESC');

$title = 'Appartamenti';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-serif text-3xl font-semibold tracking-tight">Appartamenti</h1>
      <p class="text-ink-500 mt-1">Gestisci la tua flotta di proprietà · <?= count($items) ?> totali</p>
    </div>
    <a href="/admin/appartamento-edit.php" class="btn-primary"><i data-lucide="plus" class="size-[18px]"></i> Nuovo appartamento</a>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($items as $a): $cover = $a['cover_image'] ?: $a['photo']; ?>
      <div class="card overflow-hidden p-0 card-hover group">
        <div class="aspect-[16/10] bg-ink-100 dark:bg-ink-900 relative">
          <?php if ($cover): ?><img src="<?= e($cover) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo" alt=""><?php else: ?>
            <div class="h-full w-full flex items-center justify-center text-ink-300"><i data-lucide="image" class="size-[32px]"></i></div>
          <?php endif; ?>
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
          <div class="absolute top-3 left-3 flex gap-2">
            <?php if ($a['active']): ?><span class="badge bg-white/95 text-emerald-700 backdrop-blur"><span class="size-1.5 rounded-full bg-emerald-500"></span> Attivo</span><?php else: ?><span class="badge bg-white/95 text-ink-600 backdrop-blur"><span class="size-1.5 rounded-full bg-ink-400"></span> Disattivato</span><?php endif; ?>
            <?php if ($a['under_maintenance']): ?><span class="badge bg-amber-500 text-white">Manutenzione</span><?php endif; ?>
          </div>
          <div class="absolute bottom-3 right-3 badge bg-white/95 text-ink-900 backdrop-blur tabular-nums"><?= fmtMoney((float)$a['base_price']) ?>/notte</div>
        </div>
        <div class="p-5">
          <div class="font-display font-bold text-lg truncate"><?= e($a['name']) ?></div>
          <div class="text-sm text-ink-500 flex items-center gap-1 mt-0.5"><i data-lucide="map-pin" class="size-[14px]"></i> <?= e($a['city'] ?: '—') ?></div>
          <div class="grid grid-cols-3 gap-2 mt-4 pt-4 border-t border-ink-100 dark:border-ink-800/80">
            <div><div class="text-[10px] font-semibold uppercase tracking-wider text-ink-500">Prenotazioni</div><div class="font-display font-bold tabular-nums"><?= (int)$a['bookings_count'] ?></div></div>
            <div><div class="text-[10px] font-semibold uppercase tracking-wider text-ink-500">Fatturato</div><div class="font-display font-bold tabular-nums text-sm"><?= fmtMoney((float)$a['revenue']) ?></div></div>
            <div><div class="text-[10px] font-semibold uppercase tracking-wider text-ink-500">Capacità</div><div class="font-display font-bold tabular-nums"><?= (int)$a['guests'] ?> osp.</div></div>
          </div>
          <div class="grid grid-cols-2 gap-2 mt-4">
            <a href="/admin/appartamento-edit.php?id=<?= e($a['id']) ?>" class="btn-outline text-sm py-2"><i data-lucide="edit" class="size-[14px]"></i> Modifica</a>
            <a href="/appartamento.php?slug=<?= e($a['slug']) ?>" target="_blank" class="btn-ghost text-sm py-2 border border-ink-200 dark:border-ink-700/80"><i data-lucide="external-link" class="size-[14px]"></i> Vedi</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <div class="card p-14 text-center">
      <div class="h-16 w-16 mx-auto rounded-2xl bg-brand-50 dark:bg-brand-500/15 text-brand-600 flex items-center justify-center mb-4"><i data-lucide="building-2" class="size-[28px]"></i></div>
      <div class="font-display font-bold text-xl">Nessun appartamento</div>
      <div class="text-ink-500 mt-1">Crea il primo per iniziare a gestire prenotazioni.</div>
      <a href="/admin/appartamento-edit.php" class="btn-primary mt-6 inline-flex"><i data-lucide="plus" class="size-[18px]"></i> Crea primo appartamento</a>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
