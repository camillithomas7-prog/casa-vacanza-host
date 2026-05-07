<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$apartments = rows('SELECT id, name FROM apartments ORDER BY name ASC');
$year = (int)($_GET['year'] ?? date('Y'));
$aptFilter = $_GET['apt'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        q('INSERT INTO expenses (id, apartment_id, category, amount, date, description) VALUES (?, ?, ?, ?, ?, ?)',
            [newId(), $_POST['apartment_id'] ?: null, $_POST['category'], (float)$_POST['amount'], $_POST['date'], $_POST['description'] ?: null]);
        flash('Spesa registrata');
    }
    if ($action === 'delete') {
        q('DELETE FROM expenses WHERE id = ?', [$_POST['expense_id']]);
    }
    redirect('/admin/spese.php?year=' . $year . '&apt=' . urlencode($aptFilter));
}

$where = ['date >= ? AND date < ?']; $params = ["$year-01-01", ($year + 1) . "-01-01"];
if ($aptFilter !== 'all') { $where[] = 'apartment_id = ?'; $params[] = $aptFilter; }
$expenses = rows("SELECT e.*, a.name AS apartment_name FROM expenses e LEFT JOIN apartments a ON e.apartment_id = a.id WHERE " . implode(' AND ', $where) . " ORDER BY date DESC", $params);

$bWhere = ['status != "cancelled"', 'check_in >= ?', 'check_in < ?']; $bParams = ["$year-01-01", ($year + 1) . "-01-01"];
if ($aptFilter !== 'all') { $bWhere[] = 'apartment_id = ?'; $bParams[] = $aptFilter; }
$bookings = rows("SELECT * FROM bookings WHERE " . implode(' AND ', $bWhere), $bParams);

$totalExp = array_sum(array_column($expenses, 'amount'));
$totalRev = array_sum(array_column($bookings, 'total'));
$totalPaid = array_sum(array_column($bookings, 'paid'));

$monthly = [];
for ($m = 1; $m <= 12; $m++) $monthly[str_pad($m,2,'0',STR_PAD_LEFT)] = ['rev' => 0, 'exp' => 0];
foreach ($bookings as $b) { $m = date('m', strtotime($b['check_in'])); $monthly[$m]['rev'] += (float)$b['total']; }
foreach ($expenses as $e) { $m = date('m', strtotime($e['date'])); $monthly[$m]['exp'] += (float)$e['amount']; }

$byCat = [];
foreach ($expenses as $e) { $byCat[$e['category']] = ($byCat[$e['category']] ?? 0) + (float)$e['amount']; }
arsort($byCat);

$cats = ['pulizie','bollette','manutenzione','internet','acqua','gas','elettricita','tasse','commissioni','straordinarie'];

$title = 'Spese & bilancio';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-end justify-between flex-wrap gap-3">
    <div class="min-w-0">
      <h1 class="font-display text-2xl sm:text-3xl font-bold">Spese & bilancio</h1>
      <p class="text-ink-500 mt-1 text-sm sm:text-base">Tracciatura completa di costi e ricavi.</p>
    </div>
    <form method="get" class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
      <select name="apt" class="input flex-1 sm:flex-none min-w-0">
        <option value="all" <?= $aptFilter === 'all' ? 'selected' : '' ?>>Tutti</option>
        <?php foreach ($apartments as $a): ?><option value="<?= e($a['id']) ?>" <?= $aptFilter === $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
      </select>
      <select name="year" class="input flex-1 sm:flex-none">
        <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 2; $y++): ?>
          <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button class="btn-outline text-sm">Filtra</button>
      <a href="/admin/export-spese.php?year=<?= $year ?>&apt=<?= e($aptFilter) ?>" class="btn-outline text-sm"><i data-lucide="download" class="size-[16px]"></i> CSV</a>
    </form>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([
      ['Fatturato', fmtMoney($totalRev), 'bg-emerald-500'],
      ['Incassato', fmtMoney($totalPaid), 'bg-sky-500'],
      ['Spese', fmtMoney($totalExp), 'bg-rose-500'],
      ['Utile', fmtMoney($totalRev - $totalExp), 'bg-brand-500'],
    ] as $s): ?>
      <div class="card p-4 sm:p-5">
        <div class="text-xs font-medium uppercase tracking-wide text-ink-500"><?= e($s[0]) ?></div>
        <div class="text-2xl font-display font-bold mt-1"><?= e($s[1]) ?></div>
        <div class="h-1 rounded-full mt-2 <?= $s[2] ?>"></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card p-4 sm:p-5">
    <h3 class="font-display font-bold mb-3">Andamento <?= $year ?></h3>
    <div class="relative h-[220px] sm:h-[260px] lg:h-[300px]"><canvas id="barChart"></canvas></div>
  </div>

  <form method="post" class="card p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="add">
    <select class="input" name="apartment_id"><option value="">Generico</option>
      <?php foreach ($apartments as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
    </select>
    <select class="input" name="category"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?></select>
    <input class="input" type="date" name="date" value="<?= date('Y-m-d') ?>" required>
    <input class="input" type="number" step="0.01" name="amount" placeholder="Importo €" required>
    <input class="input sm:col-span-2 lg:col-span-1" name="description" placeholder="Descrizione">
    <button class="btn-primary sm:col-span-2 lg:col-span-1 h-12"><i data-lucide="plus" class="size-[16px]"></i> Aggiungi</button>
  </form>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="card p-4 sm:p-5 lg:col-span-2 overflow-x-auto">
      <h3 class="font-display font-bold mb-3">Voci</h3>
      <table class="table-base">
        <thead><tr><th>Data</th><th>Categoria</th><th>Appartamento</th><th>Importo</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($expenses as $e): ?>
            <tr>
              <td><?= fmtDateShort($e['date']) ?></td>
              <td class="capitalize"><?= e($e['category']) ?></td>
              <td><?= e($e['apartment_name'] ?: '—') ?></td>
              <td><?= fmtMoney((float)$e['amount']) ?></td>
              <td>
                <form method="post" onsubmit="return confirm('Eliminare?')">
                  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="expense_id" value="<?= e($e['id']) ?>">
                  <button class="btn-ghost text-red-600"><i data-lucide="trash-2" class="size-[14px]"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$expenses): ?><div class="p-6 text-sm text-ink-500 text-center">Nessuna spesa registrata.</div><?php endif; ?>
    </div>
    <div class="card p-4 sm:p-5">
      <h3 class="font-display font-bold mb-3">Per categoria</h3>
      <?php if (!$byCat): ?><div class="text-sm text-ink-500">Nessun dato.</div><?php else: ?>
        <ul class="space-y-2">
          <?php foreach ($byCat as $c => $v): ?>
            <li class="flex justify-between text-sm">
              <span class="capitalize"><?= e($c) ?></span>
              <span class="font-semibold"><?= fmtMoney($v) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function() {
  if (typeof Chart === 'undefined') return;
  const data = <?= json_encode(array_values($monthly)) ?>;
  const labels = <?= json_encode(array_keys($monthly)) ?>;
  const el = document.getElementById('barChart');
  if (!el) return;
  new Chart(el, {
    type: 'bar',
    data: { labels, datasets: [
      { label: 'Ricavi', data: data.map(d => d.rev), backgroundColor: '#10b981', borderRadius: 8 },
      { label: 'Spese', data: data.map(d => d.exp), backgroundColor: '#ef4444', borderRadius: 8 },
      { label: 'Utile', data: data.map(d => d.rev - d.exp), backgroundColor: '#ff6a0a', borderRadius: 8 },
    ]},
    options: { responsive: true, maintainAspectRatio: false,
      plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + new Intl.NumberFormat('it-IT', { style:'currency', currency:'EUR' }).format(c.parsed.y) } } } }
  });
});
</script>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
