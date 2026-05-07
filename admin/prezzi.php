<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$apartments = rows('SELECT * FROM apartments ORDER BY name ASC');
if (!$apartments) { $title='Prezzi'; require __DIR__.'/../partials/head.php'; require __DIR__.'/../partials/admin-shell-top.php'; echo '<div class="card p-10 text-center">Crea prima un appartamento.</div>'; require __DIR__.'/../partials/admin-shell-bottom.php'; exit; }

$aptId = $_GET['apt'] ?? $apartments[0]['id'];
$apt = row('SELECT * FROM apartments WHERE id = ?', [$aptId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        q('INSERT INTO price_rules (id, apartment_id, name, start_date, end_date, price_per_night, min_nights, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [newId(), $aptId, $_POST['name'], $_POST['start_date'], $_POST['end_date'], (float)$_POST['price_per_night'], (int)$_POST['min_nights'], (int)$_POST['priority']]);
        flash('Regola aggiunta');
    }
    if ($action === 'delete') {
        q('DELETE FROM price_rules WHERE id = ?', [$_POST['rule_id']]);
    }
    if ($action === 'copy') {
        $rules_src = rows('SELECT * FROM price_rules WHERE apartment_id = ?', [$aptId]);
        foreach ($rules_src as $r) {
            q('INSERT INTO price_rules (id, apartment_id, name, start_date, end_date, price_per_night, min_nights, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [newId(), $_POST['target'], $r['name'], $r['start_date'], $r['end_date'], $r['price_per_night'], $r['min_nights'], $r['priority']]);
        }
        flash(count($rules_src) . ' regole copiate');
    }
    redirect('/admin/prezzi.php?apt=' . urlencode($aptId));
}

$rules = rows('SELECT * FROM price_rules WHERE apartment_id = ? ORDER BY start_date ASC', [$aptId]);

$title = 'Prezzi avanzati';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-display text-3xl font-bold">Prezzi avanzati</h1>
      <p class="text-ink-500 mt-1">Tariffe stagionali, weekend, alta stagione, override per intervalli.</p>
    </div>
    <form method="get"><select name="apt" onchange="this.form.submit()" class="input">
      <?php foreach ($apartments as $a): ?><option value="<?= e($a['id']) ?>" <?= $a['id'] === $aptId ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
    </select></form>
  </div>

  <form method="post" class="card p-5 grid sm:grid-cols-7 gap-3">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="add">
    <input class="input sm:col-span-2" placeholder="Nome (es. Agosto)" name="name" required>
    <input class="input" type="date" name="start_date" required>
    <input class="input" type="date" name="end_date" required>
    <input class="input" type="number" step="0.01" placeholder="€/notte" name="price_per_night" value="<?= e((string)$apt['base_price']) ?>" required>
    <input class="input" type="number" placeholder="Min notti" name="min_nights" value="1">
    <input class="input" type="number" placeholder="Priorità" name="priority" value="1">
    <button class="btn-primary sm:col-span-7"><i data-lucide="plus" class="size-[16px]"></i> Aggiungi regola</button>
  </form>

  <div class="card p-0 overflow-x-auto">
    <table class="table-base">
      <thead><tr><th>Nome</th><th>Dal</th><th>Al</th><th>€/notte</th><th>Min notti</th><th>Priorità</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rules as $r): ?>
          <tr>
            <td class="font-medium"><?= e($r['name']) ?></td>
            <td><?= fmtDateShort($r['start_date']) ?></td>
            <td><?= fmtDateShort($r['end_date']) ?></td>
            <td><?= fmtMoney((float)$r['price_per_night']) ?></td>
            <td><?= (int)$r['min_nights'] ?></td>
            <td><?= (int)$r['priority'] ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Eliminare?')">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="rule_id" value="<?= e($r['id']) ?>">
                <button class="btn-ghost text-red-600"><i data-lucide="trash-2" class="size-[14px]"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$rules): ?><div class="p-8 text-center text-sm text-ink-500">Nessuna regola attiva. Verrà usato il prezzo base (<?= fmtMoney((float)$apt['base_price']) ?>).</div><?php endif; ?>
  </div>

  <?php if (count($apartments) > 1 && $rules): ?>
    <div class="card p-5">
      <h3 class="font-display font-bold mb-3">Copia regole verso un altro appartamento</h3>
      <div class="flex gap-2 flex-wrap">
        <?php foreach ($apartments as $a): if ($a['id'] === $aptId) continue; ?>
          <form method="post" onsubmit="return confirm('Copiare le regole?')">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="copy">
            <input type="hidden" name="target" value="<?= e($a['id']) ?>">
            <button class="btn-outline text-sm"><i data-lucide="copy" class="size-[14px]"></i> <?= e($a['name']) ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
