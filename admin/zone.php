<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

// Migrazione lazy + seed iniziale
try {
    db()->exec("CREATE TABLE IF NOT EXISTS zones (
      id VARCHAR(32) PRIMARY KEY,
      name VARCHAR(120) NOT NULL,
      slug VARCHAR(120) NOT NULL,
      kind VARCHAR(20) NOT NULL DEFAULT 'zone',
      description TEXT,
      image VARCHAR(500),
      position INT NOT NULL DEFAULT 0,
      active TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ((int)val('SELECT COUNT(*) FROM zones') === 0) {
        $seed = [
            ['Naama Bay',  'naama-bay',  'zone',     '/assets/sharm/zone_naama_bay.jpg',  1],
            ['Hadaba',     'hadaba',     'zone',     '/assets/sharm/zone_hadaba.jpg',     2],
            ['Sharks Bay', 'sharks-bay', 'zone',     '/assets/sharm/zone_sharks_bay.jpg', 3],
            ['Nabq Bay',   'nabq-bay',   'villaggio','/assets/sharm/zone_nabq_bay.jpg',   4],
            ['Old Market', 'old-market', 'zone',     '/assets/sharm/zone_old_market.jpg', 5],
        ];
        foreach ($seed as [$n, $s, $k, $img, $p]) {
            q('INSERT IGNORE INTO zones (id, name, slug, kind, image, position) VALUES (?, ?, ?, ?, ?, ?)',
                [newId(), $n, $s, $k, $img, $p]);
        }
    }
} catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        q('DELETE FROM zones WHERE id = ?', [$_POST['zone_id']]);
        flash('Zona eliminata');
    }
    if ($action === 'toggle') {
        q('UPDATE zones SET active = 1 - active WHERE id = ?', [$_POST['zone_id']]);
    }
    if ($action === 'reorder') {
        $i = 0;
        foreach (explode(',', $_POST['order'] ?? '') as $id) {
            if ($id) q('UPDATE zones SET position = ? WHERE id = ?', [++$i, $id]);
        }
    }
    redirect('/admin/zone.php');
}

$zones = rows('SELECT z.*, (SELECT COUNT(*) FROM apartments a WHERE a.city = z.name AND a.active = 1) AS apt_count
               FROM zones z ORDER BY z.position ASC, z.name ASC');

$title = 'Zone & villaggi';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-end justify-between flex-wrap gap-3">
    <div class="min-w-0">
      <h1 class="font-display text-2xl sm:text-3xl font-bold">Zone & villaggi</h1>
      <p class="text-ink-500 mt-1 text-sm sm:text-base">Le destinazioni mostrate sulla home nella sezione "Scegli la tua zona". Carica una foto per ogni zona o villaggio.</p>
    </div>
    <a href="/admin/zone-edit.php" class="btn-primary"><i data-lucide="plus" class="size-[16px]"></i> Nuova zona/villaggio</a>
  </div>

  <?php if (!$zones): ?>
    <div class="card p-10 text-center">
      <div class="h-14 w-14 mx-auto rounded-2xl bg-ink-100 dark:bg-ink-800 flex items-center justify-center text-ink-400 mb-3"><i data-lucide="map-pin" class="size-[24px]"></i></div>
      <p class="text-ink-500">Nessuna zona ancora. Inizia creandone una.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <?php foreach ($zones as $z): ?>
        <div class="card overflow-hidden card-hover relative group <?= !$z['active'] ? 'opacity-60' : '' ?>">
          <a href="/admin/zone-edit.php?id=<?= e($z['id']) ?>" class="block">
            <div class="aspect-[4/3] bg-ink-100 dark:bg-ink-800 relative overflow-hidden">
              <?php if ($z['image']): ?>
                <img src="<?= e($z['image']) ?>" alt="<?= e($z['name']) ?>" class="absolute inset-0 h-full w-full object-cover">
              <?php else: ?>
                <div class="absolute inset-0 flex items-center justify-center text-ink-400"><i data-lucide="image" class="size-[32px]"></i></div>
              <?php endif; ?>
              <span class="absolute top-3 left-3 badge-soft capitalize"><?= e($z['kind']) ?></span>
              <?php if (!$z['active']): ?><span class="absolute top-3 right-3 badge-warning">Nascosta</span><?php endif; ?>
            </div>
          </a>
          <div class="p-4">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="font-display font-bold truncate"><?= e($z['name']) ?></div>
                <div class="text-xs text-ink-500 mt-0.5"><?= (int)$z['apt_count'] ?> <?= $z['apt_count'] == 1 ? 'appartamento' : 'appartamenti' ?></div>
              </div>
              <div class="flex gap-1 shrink-0">
                <a href="/admin/zone-edit.php?id=<?= e($z['id']) ?>" class="btn-ghost p-2" title="Modifica"><i data-lucide="pencil" class="size-[14px]"></i></a>
                <form method="post" class="inline">
                  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="zone_id" value="<?= e($z['id']) ?>">
                  <button class="btn-ghost p-2" title="<?= $z['active'] ? 'Nascondi' : 'Mostra' ?>"><i data-lucide="<?= $z['active'] ? 'eye' : 'eye-off' ?>" class="size-[14px]"></i></button>
                </form>
                <form method="post" class="inline" onsubmit="return confirm('Eliminare la zona <?= e($z['name']) ?>? Gli appartamenti collegati non verranno cancellati.')">
                  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="zone_id" value="<?= e($z['id']) ?>">
                  <button class="btn-ghost text-red-600 p-2" title="Elimina"><i data-lucide="trash-2" class="size-[14px]"></i></button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card p-4 sm:p-5 bg-amber-50/50 border-amber-200 dark:bg-amber-500/5">
    <div class="flex items-start gap-3">
      <i data-lucide="info" class="size-[18px] text-amber-600 mt-0.5 shrink-0"></i>
      <div class="text-sm text-ink-700 dark:text-ink-300">
        Le zone create qui appaiono automaticamente nella home (sezione <i>Scegli la tua zona</i>) e nel filtro "Dove" del motore di ricerca.
        Per collegare un appartamento a una zona, scrivi nel campo <b>Zona di Sharm</b> dell'appartamento esattamente lo stesso nome usato qui.
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
