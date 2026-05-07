<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$where = ['1=1']; $params = [];
if (!empty($_GET['status'])) { $where[] = 'b.status = ?'; $params[] = $_GET['status']; }
if (!empty($_GET['q'])) {
    $where[] = '(b.code LIKE ? OR c.name LIKE ?)';
    $params[] = '%' . $_GET['q'] . '%';
    $params[] = '%' . $_GET['q'] . '%';
}
$items = rows("SELECT b.*, a.name AS apartment_name, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
               FROM bookings b JOIN apartments a ON b.apartment_id = a.id JOIN customers c ON b.customer_id = c.id
               WHERE " . implode(' AND ', $where) . " ORDER BY b.created_at DESC", $params);

$statuses = [
  '' => 'Tutte', 'pending' => 'In attesa', 'confirmed' => 'Confermate',
  'checked_in' => 'Check-in', 'completed' => 'Completate', 'cancelled' => 'Cancellate',
];

function statusBadge($s) {
  $map = ['pending'=>['yellow','In attesa'],'confirmed'=>['blue','Confermata'],'checked_in'=>['green','Check-in'],'completed'=>['gray','Completata'],'cancelled'=>['red','Cancellata']];
  $x = $map[$s] ?? ['gray', $s];
  $cls = ['gray'=>'bg-ink-100 text-ink-700','green'=>'bg-emerald-100 text-emerald-700','red'=>'bg-red-100 text-red-700','yellow'=>'bg-amber-100 text-amber-800','blue'=>'bg-sky-100 text-sky-700'][$x[0]] ?? '';
  return '<span class="badge ' . $cls . '">' . e($x[1]) . '</span>';
}

$title = 'Prenotazioni';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-display text-3xl font-bold">Prenotazioni</h1>
      <p class="text-ink-500 mt-1">Tutte le prenotazioni dirette e dai canali esterni.</p>
    </div>
    <a href="/admin/prenotazione-nuova.php" class="btn-primary"><i data-lucide="plus" class="size-[18px]"></i> Nuova</a>
  </div>

  <div class="card p-3 flex items-center gap-2 overflow-x-auto">
    <?php foreach ($statuses as $s => $l): $active = ($_GET['status'] ?? '') === $s; ?>
      <a href="<?= $s ? '?status=' . e($s) : '/admin/prenotazioni.php' ?>" class="text-sm px-3 py-1.5 rounded-lg whitespace-nowrap <?= $active ? 'bg-brand-500 text-white' : 'hover:bg-ink-100 dark:hover:bg-ink-800' ?>"><?= e($l) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="card p-0 overflow-x-auto">
    <table class="table-base">
      <thead><tr><th>Codice</th><th>Cliente</th><th>Appartamento</th><th>Date</th><th>Totale</th><th>Saldo</th><th>Stato</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($items as $b): $due = (float)$b['total'] - (float)$b['paid']; ?>
          <tr>
            <td class="font-mono text-xs"><?= e($b['code']) ?></td>
            <td>
              <div class="font-medium"><?= e($b['customer_name']) ?></div>
              <div class="text-xs text-ink-500"><?= e($b['customer_email'] ?: $b['customer_phone']) ?></div>
            </td>
            <td><?= e($b['apartment_name']) ?></td>
            <td class="text-sm"><?= fmtDateShort($b['check_in']) ?> → <?= fmtDateShort($b['check_out']) ?></td>
            <td><?= fmtMoney((float)$b['total']) ?></td>
            <td><?= $due > 0 ? '<span class="text-amber-600">' . fmtMoney($due) . '</span>' : '<span class="text-emerald-600">Saldata</span>' ?></td>
            <td><?= statusBadge($b['status']) ?></td>
            <td><a href="/admin/prenotazione.php?id=<?= e($b['id']) ?>" class="text-brand-600 text-sm">Apri</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$items): ?><div class="p-10 text-center text-ink-500">Nessuna prenotazione.</div><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
