<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$apt_count = (int)val('SELECT COUNT(*) FROM apartments WHERE active = 1');
$bookings = rows('SELECT b.*, a.name as apartment_name, c.name as customer_name FROM bookings b JOIN apartments a ON b.apartment_id = a.id JOIN customers c ON b.customer_id = c.id WHERE b.status != "cancelled"');
$expenses = rows('SELECT * FROM expenses');

$revenue = array_sum(array_column($bookings, 'total'));
$paid = array_sum(array_column($bookings, 'paid'));
$due = $revenue - $paid;
$exp_total = array_sum(array_column($expenses, 'amount'));
$profit = $revenue - $exp_total;

$months = [];
for ($i = 11; $i >= 0; $i--) {
    $k = date('Y-m', strtotime("-$i months"));
    $months[$k] = ['rev' => 0, 'exp' => 0];
}
foreach ($bookings as $b) {
    $k = date('Y-m', strtotime($b['check_in']));
    if (isset($months[$k])) $months[$k]['rev'] += (float)$b['total'];
}
foreach ($expenses as $e) {
    $k = date('Y-m', strtotime($e['date']));
    if (isset($months[$k])) $months[$k]['exp'] += (float)$e['amount'];
}
$series = [];
foreach ($months as $m => $v) $series[] = ['month' => substr($m, 2), 'rev' => $v['rev'], 'exp' => $v['exp'], 'profit' => $v['rev'] - $v['exp']];

$by_apt = [];
foreach ($bookings as $b) {
    $by_apt[$b['apartment_id']] = $by_apt[$b['apartment_id']] ?? ['name' => $b['apartment_name'], 'total' => 0];
    $by_apt[$b['apartment_id']]['total'] += (float)$b['total'];
}
usort($by_apt, fn($a, $b) => $b['total'] <=> $a['total']);
$top = array_slice($by_apt, 0, 5);

$upcoming = rows('SELECT b.*, a.name as apartment_name, c.name as customer_name FROM bookings b JOIN apartments a ON b.apartment_id = a.id JOIN customers c ON b.customer_id = c.id WHERE b.status IN ("confirmed","checked_in") AND b.check_in >= CURRENT_DATE ORDER BY b.check_in ASC LIMIT 6');
$recent = rows('SELECT b.*, a.name as apartment_name, c.name as customer_name FROM bookings b JOIN apartments a ON b.apartment_id = a.id JOIN customers c ON b.customer_id = c.id ORDER BY b.created_at DESC LIMIT 6');
$pending_count = (int)val('SELECT COUNT(*) FROM bookings WHERE status = "pending"');

$title = 'Dashboard';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-6">
  <div>
    <h1 class="font-display text-3xl font-bold">Dashboard</h1>
    <p class="text-ink-500 mt-1">Panoramica completa del tuo gestionale.</p>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([
      ['Fatturato', fmtMoney($revenue), count($bookings) . ' prenotazioni', 'trending-up', 'bg-emerald-500'],
      ['Saldo da incassare', fmtMoney($due), $due > 0 ? 'da sollecitare' : 'tutto incassato', 'clock', 'bg-amber-500'],
      ['Spese totali', fmtMoney($exp_total), count($expenses) . ' voci', 'wallet', 'bg-rose-500'],
      ['Utile netto', fmtMoney($profit), 'ricavi − spese', 'trending-up', 'bg-brand-500'],
    ] as $s): ?>
      <div class="card p-5 flex items-center gap-4">
        <div class="h-12 w-12 rounded-2xl flex items-center justify-center text-white <?= $s[4] ?>"><i data-lucide="<?= $s[3] ?>" class="size-[20px]"></i></div>
        <div>
          <div class="text-xs font-medium uppercase tracking-wide text-ink-500"><?= e($s[0]) ?></div>
          <div class="text-2xl font-display font-bold mt-0.5"><?= e($s[1]) ?></div>
          <div class="text-xs text-ink-500 mt-0.5"><?= e($s[2]) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([
      ['Appartamenti attivi', $apt_count, 'building-2', 'bg-sky-500'],
      ['Prossimi check-in', count($upcoming), 'bookmark-check', 'bg-violet-500'],
      ['Prenotazioni totali', count($bookings), 'bookmark-check', 'bg-indigo-500'],
      ['In attesa di conferma', $pending_count, 'alert-triangle', 'bg-amber-500'],
    ] as $s): ?>
      <div class="card p-5 flex items-center gap-4">
        <div class="h-12 w-12 rounded-2xl flex items-center justify-center text-white <?= $s[3] ?>"><i data-lucide="<?= $s[2] ?>" class="size-[20px]"></i></div>
        <div>
          <div class="text-xs font-medium uppercase tracking-wide text-ink-500"><?= e($s[0]) ?></div>
          <div class="text-2xl font-display font-bold mt-0.5"><?= e($s[1]) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="card p-5 lg:col-span-2">
      <h2 class="font-display font-bold text-lg mb-3">Andamento mensile</h2>
      <canvas id="lineChart" height="100"></canvas>
    </div>
    <div class="card p-5">
      <h2 class="font-display font-bold text-lg mb-3">Top appartamenti</h2>
      <?php if (!$top): ?><div class="text-sm text-ink-500">Nessun dato.</div><?php else: ?>
        <canvas id="pieChart" height="200"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-display text-lg font-bold">Prossimi arrivi</h2>
        <a href="/admin/calendario.php" class="text-sm text-brand-600">Calendario →</a>
      </div>
      <?php if (!$upcoming): ?><p class="text-sm text-ink-500">Nessun arrivo programmato.</p><?php else: ?>
        <ul class="divide-y divide-ink-100 dark:divide-ink-800">
          <?php foreach ($upcoming as $b): ?>
            <li class="py-3 flex items-center justify-between">
              <div>
                <div class="font-medium"><?= e($b['customer_name']) ?></div>
                <div class="text-xs text-ink-500"><?= e($b['apartment_name']) ?> · <?= fmtDate($b['check_in']) ?> → <?= fmtDate($b['check_out']) ?></div>
              </div>
              <a href="/admin/prenotazione.php?id=<?= e($b['id']) ?>" class="text-sm text-brand-600">Apri</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="card p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-display text-lg font-bold">Ultime prenotazioni</h2>
        <a href="/admin/prenotazioni.php" class="text-sm text-brand-600">Tutte →</a>
      </div>
      <ul class="divide-y divide-ink-100 dark:divide-ink-800">
        <?php foreach ($recent as $b): ?>
          <li class="py-3 flex items-center justify-between">
            <div>
              <div class="font-medium"><?= e($b['customer_name']) ?> <span class="text-xs text-ink-500 font-normal">· <?= e($b['code']) ?></span></div>
              <div class="text-xs text-ink-500"><?= e($b['apartment_name']) ?> · <?= fmtMoney((float)$b['total']) ?></div>
            </div>
            <a href="/admin/prenotazione.php?id=<?= e($b['id']) ?>" class="text-sm text-brand-600">Apri</a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const series = <?= json_encode($series) ?>;
const top = <?= json_encode($top) ?>;
const eur = v => new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(v || 0);
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: series.map(s => s.month),
    datasets: [
      { label: 'Ricavi', data: series.map(s => s.rev), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', tension: 0.3, fill: true },
      { label: 'Spese', data: series.map(s => s.exp), borderColor: '#ef4444', tension: 0.3 },
      { label: 'Utile', data: series.map(s => s.profit), borderColor: '#ff6a0a', tension: 0.3 },
    ]
  },
  options: { plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + eur(c.parsed.y) } } } }
});
if (top.length) {
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: { labels: top.map(t => t.name), datasets: [{ data: top.map(t => t.total), backgroundColor: ['#ff6a0a','#f04e00','#9c300d','#ff8a32','#ffb56c'] }] },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { callbacks: { label: c => c.label + ': ' + eur(c.parsed) } } } }
  });
}
</script>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
