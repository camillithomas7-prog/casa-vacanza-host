<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        q('INSERT INTO coupons (id, code, type, value, max_uses, valid_from, valid_until, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
            [newId(), strtoupper($_POST['code']), $_POST['type'], (float)$_POST['value'],
             $_POST['max_uses'] !== '' ? (int)$_POST['max_uses'] : null,
             $_POST['valid_from'] ?: null, $_POST['valid_until'] ?: null]);
        flash('Coupon creato');
    }
    if ($action === 'delete') q('DELETE FROM coupons WHERE id = ?', [$_POST['coupon_id']]);
    redirect('/admin/coupon.php');
}

$coupons = rows('SELECT * FROM coupons ORDER BY code ASC');

$title = 'Coupon';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <h1 class="font-display text-2xl sm:text-3xl font-bold">Coupon & sconti</h1>

  <form method="post" class="card p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-2">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="add">
    <input class="input uppercase" name="code" placeholder="CODICE" required>
    <select class="input" name="type"><option value="percent">%</option><option value="fixed">€ fisso</option></select>
    <input class="input" type="number" step="0.01" name="value" placeholder="Valore" required>
    <input class="input" type="number" name="max_uses" placeholder="Max usi">
    <input class="input" type="date" name="valid_from">
    <input class="input" type="date" name="valid_until">
    <button class="btn-primary col-span-full lg:col-span-1"><i data-lucide="plus" class="size-[16px]"></i> Crea</button>
  </form>

  <div class="card p-0 overflow-x-auto">
    <table class="table-base">
      <thead><tr><th>Codice</th><th>Sconto</th><th>Validità</th><th>Usi</th><th>Stato</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($coupons as $c): ?>
          <tr>
            <td class="font-mono"><?= e($c['code']) ?></td>
            <td><?= $c['type'] === 'percent' ? $c['value'] . '%' : '€' . $c['value'] ?></td>
            <td class="text-sm"><?= e($c['valid_from'] ?: '—') ?> → <?= e($c['valid_until'] ?: '—') ?></td>
            <td><?= (int)$c['uses'] ?><?= $c['max_uses'] ? ' / ' . (int)$c['max_uses'] : '' ?></td>
            <td><?= $c['active'] ? '<span class="badge bg-emerald-100 text-emerald-700">Attivo</span>' : '<span class="badge bg-ink-100 text-ink-500">Off</span>' ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Eliminare?')">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="coupon_id" value="<?= e($c['id']) ?>">
                <button class="btn-ghost text-red-600"><i data-lucide="trash-2" class="size-[14px]"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$coupons): ?><div class="p-8 text-sm text-ink-500 text-center">Nessun coupon.</div><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
