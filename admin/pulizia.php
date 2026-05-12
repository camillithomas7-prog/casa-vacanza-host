<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/cleaning.php';
requireAdmin();

$id = $_GET['id'] ?? '';
$s = row("SELECT s.*, a.name AS apartment_name, a.address AS apartment_address,
          b.code AS booking_code, b.check_in, b.check_out, c.name AS customer_name, c.phone AS customer_phone
          FROM cleaning_sessions s
          JOIN apartments a ON a.id = s.apartment_id
          LEFT JOIN bookings b ON b.id = s.booking_id
          LEFT JOIN customers c ON c.id = b.customer_id
          WHERE s.id = ?", [$id]);
if (!$s) { flash('Sessione pulizia non trovata', 'error'); redirect('/admin/pulizie.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    if (($_POST['action'] ?? '') === 'delete') {
        q('DELETE FROM cleaning_sessions WHERE id = ?', [$s['id']]);
        flash('Sessione eliminata');
        redirect('/admin/pulizie.php');
    }
}

$items = rows('SELECT * FROM cleaning_session_items WHERE session_id = ? ORDER BY position ASC', [$s['id']]);

$title = 'Pulizia ' . $s['apartment_name'];
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <a href="/admin/pulizie.php" class="text-sm text-ink-500 hover:text-brand-600 inline-flex items-center gap-1"><i data-lucide="chevron-left" class="size-[14px]"></i> Tutte le pulizie</a>

  <div class="card p-5 sm:p-6">
    <div class="flex items-start justify-between gap-3 flex-wrap">
      <div>
        <div class="text-xs text-ink-500 uppercase tracking-wider"><?= fmtDate($s['scheduled_date']) ?></div>
        <h1 class="font-display text-2xl sm:text-3xl font-bold mt-1"><?= e($s['apartment_name']) ?></h1>
        <?php if ($s['apartment_address']): ?><div class="text-sm text-ink-500 mt-1"><?= e($s['apartment_address']) ?></div><?php endif; ?>
      </div>
      <span class="<?= $s['status'] === 'done' ? 'badge-success' : ($s['status'] === 'in_progress' ? 'bg-amber-100 text-amber-700' : 'badge-soft') ?> capitalize px-3 py-1 rounded-full text-sm"><?= str_replace('_',' ',e($s['status'])) ?></span>
    </div>

    <?php if ($s['customer_name']): ?>
      <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
        <div><div class="text-[10px] text-ink-500 uppercase">Ospite</div><div class="font-medium truncate"><?= e($s['customer_name']) ?></div></div>
        <div><div class="text-[10px] text-ink-500 uppercase">Booking</div><div class="font-mono text-xs"><?= e($s['booking_code']) ?></div></div>
        <?php if ($s['check_in']): ?><div><div class="text-[10px] text-ink-500 uppercase">Check-in</div><div><?= fmtDate($s['check_in']) ?></div></div><?php endif; ?>
        <?php if ($s['check_out']): ?><div><div class="text-[10px] text-ink-500 uppercase">Check-out</div><div><?= fmtDate($s['check_out']) ?></div></div><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card p-5 sm:p-6">
    <h2 class="font-display font-bold mb-3">Checklist</h2>
    <?php if (!$items): ?>
      <div class="text-sm text-ink-500">Nessuna voce.</div>
    <?php else: ?>
      <ul class="space-y-2">
        <?php foreach ($items as $it): ?>
          <li class="flex items-start gap-3 p-2.5 rounded-xl <?= $it['checked'] ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-ink-50/60 dark:bg-ink-800/30' ?>">
            <span class="mt-0.5 h-5 w-5 rounded-md flex items-center justify-center shrink-0 <?= $it['checked'] ? 'bg-emerald-500 text-white' : 'border border-ink-300 dark:border-ink-700' ?>">
              <?php if ($it['checked']): ?><i data-lucide="check" class="size-[14px]"></i><?php endif; ?>
            </span>
            <div class="flex-1 min-w-0">
              <div class="text-sm <?= $it['checked'] ? 'line-through text-ink-500' : '' ?>"><?= e($it['label_snapshot']) ?></div>
              <?php if ($it['checked_at']): ?><div class="text-[10px] text-ink-400 mt-0.5">Spuntato <?= fmtDateTime($it['checked_at']) ?></div><?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <?php if (!empty($s['cleaner_notes'])): ?>
    <div class="card p-5 sm:p-6 border-amber-200 bg-amber-50/60 dark:bg-amber-500/5">
      <h3 class="font-display font-bold mb-2 flex items-center gap-2"><i data-lucide="message-square" class="size-[18px] text-amber-700"></i> Note della signora</h3>
      <div class="whitespace-pre-line text-sm text-ink-700 dark:text-ink-200"><?= e($s['cleaner_notes']) ?></div>
    </div>
  <?php endif; ?>

  <form method="post" onsubmit="return confirm('Eliminare questa sessione di pulizia?')" class="card p-4 border-red-200">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="delete">
    <button class="btn-ghost text-red-600 text-sm"><i data-lucide="trash-2" class="size-[14px]"></i> Elimina sessione</button>
  </form>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
