<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$items = rows('SELECT a.*, (SELECT url FROM photos WHERE apartment_id = a.id ORDER BY position ASC LIMIT 1) AS photo, (SELECT COUNT(*) FROM bookings WHERE apartment_id = a.id) AS bookings_count FROM apartments a ORDER BY a.created_at DESC');

$title = 'Appartamenti';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="font-display text-3xl font-bold">Appartamenti</h1>
      <p class="text-ink-500 mt-1">Gestisci la tua flotta di proprietà.</p>
    </div>
    <a href="/admin/appartamento-edit.php" class="btn-primary"><i data-lucide="plus" class="size-[18px]"></i> Nuovo appartamento</a>
  </div>

  <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($items as $a): $cover = $a['cover_image'] ?: $a['photo']; ?>
      <div class="card overflow-hidden p-0">
        <div class="aspect-[16/10] bg-ink-100 dark:bg-ink-900 relative">
          <?php if ($cover): ?><img src="<?= e($cover) ?>" class="h-full w-full object-cover" alt=""><?php endif; ?>
          <div class="absolute top-3 left-3 flex gap-2">
            <?php if ($a['active']): ?><span class="badge bg-emerald-100 text-emerald-700">Attivo</span><?php else: ?><span class="badge bg-ink-100 text-ink-600">Disattivato</span><?php endif; ?>
            <?php if ($a['under_maintenance']): ?><span class="badge bg-amber-100 text-amber-700">Manutenzione</span><?php endif; ?>
          </div>
        </div>
        <div class="p-4">
          <div class="font-semibold truncate"><?= e($a['name']) ?></div>
          <div class="text-sm text-ink-500"><?= e($a['city'] ?: '—') ?></div>
          <div class="flex items-center justify-between mt-3">
            <span class="text-sm"><?= fmtMoney((float)$a['base_price']) ?>/notte</span>
            <span class="text-xs text-ink-500"><?= (int)$a['bookings_count'] ?> pren.</span>
          </div>
          <div class="grid grid-cols-2 gap-2 mt-3">
            <a href="/admin/appartamento-edit.php?id=<?= e($a['id']) ?>" class="btn-outline text-sm py-2"><i data-lucide="edit" class="size-[14px]"></i> Modifica</a>
            <a href="/appartamento.php?slug=<?= e($a['slug']) ?>" target="_blank" class="btn-outline text-sm py-2"><i data-lucide="eye" class="size-[14px]"></i> Vedi</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <div class="card p-10 text-center">
      <div class="font-semibold text-lg">Nessun appartamento</div>
      <div class="text-ink-500 mt-1">Crea il primo per iniziare.</div>
      <a href="/admin/appartamento-edit.php" class="btn-primary mt-4 inline-flex"><i data-lucide="plus" class="size-[18px]"></i> Nuovo</a>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
