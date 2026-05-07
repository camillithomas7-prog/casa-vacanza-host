<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    if (($_POST['action'] ?? '') === 'mark_all') {
        q('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
        flash('Tutte segnate come lette');
    }
    redirect('/admin/notifiche.php');
}

$notifications = rows('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100');
$logs = rows('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 30');

$title = 'Notifiche';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-center justify-between">
    <h1 class="font-display text-3xl font-bold">Notifiche & attività</h1>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="mark_all"><button class="btn-outline text-sm"><i data-lucide="check" class="size-[14px]"></i> Segna tutte come lette</button></form>
  </div>
  <div class="grid lg:grid-cols-2 gap-5">
    <div class="card p-5">
      <h3 class="font-display font-bold mb-3">Notifiche</h3>
      <ul class="divide-y divide-ink-100 dark:divide-ink-800">
        <?php foreach ($notifications as $n): ?>
          <li class="py-3 <?= $n['is_read'] ? 'opacity-60' : '' ?>">
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="font-medium"><?= e($n['title']) ?></div>
                <div class="text-sm text-ink-500"><?= e($n['body']) ?></div>
                <div class="text-xs text-ink-400 mt-1"><?= fmtDateTime($n['created_at']) ?></div>
              </div>
              <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>" class="text-sm text-brand-600 shrink-0">Apri</a><?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
        <?php if (!$notifications): ?><li class="text-sm text-ink-500 py-4 text-center">Nessuna notifica.</li><?php endif; ?>
      </ul>
    </div>
    <div class="card p-5">
      <h3 class="font-display font-bold mb-3">Attività recente</h3>
      <ul class="divide-y divide-ink-100 dark:divide-ink-800">
        <?php foreach ($logs as $l): ?>
          <li class="py-2 text-sm">
            <span class="font-mono text-xs px-1.5 py-0.5 bg-ink-100 dark:bg-ink-800 rounded"><?= e($l['action']) ?></span>
            <span class="text-ink-500"><?= e($l['entity']) ?></span>
            <?php if ($l['details']): ?> · <?= e(mb_substr($l['details'], 0, 80)) ?><?php endif; ?>
            <div class="text-xs text-ink-400"><?= fmtDateTime($l['created_at']) ?></div>
          </li>
        <?php endforeach; ?>
        <?php if (!$logs): ?><li class="text-sm text-ink-500 py-4 text-center">Nessuna attività.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
