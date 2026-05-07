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

$upcoming = rows('SELECT b.*, a.name as apartment_name, a.cover_image as apartment_cover, c.name as customer_name, c.phone as customer_phone FROM bookings b JOIN apartments a ON b.apartment_id = a.id JOIN customers c ON b.customer_id = c.id WHERE b.status IN ("confirmed","checked_in") AND b.check_in >= CURRENT_DATE ORDER BY b.check_in ASC LIMIT 5');
$recent = rows('SELECT b.*, a.name as apartment_name, c.name as customer_name FROM bookings b JOIN apartments a ON b.apartment_id = a.id JOIN customers c ON b.customer_id = c.id ORDER BY b.created_at DESC LIMIT 6');
$pending_count = (int)val('SELECT COUNT(*) FROM bookings WHERE status = "pending"');

$revRecent = array_sum(array_slice(array_column($series, 'rev'), -6));
$revPrev = array_sum(array_slice(array_column($series, 'rev'), -12, 6));
$revTrend = $revPrev > 0 ? round((($revRecent - $revPrev) / $revPrev) * 100) : 0;

$title = 'Dashboard';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5 sm:space-y-6">
  <div class="flex items-end justify-between flex-wrap gap-3">
    <div class="min-w-0">
      <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-semibold tracking-tight">Buongiorno 👋</h1>
      <p class="text-ink-500 mt-1 text-sm sm:text-base text-pretty">Ecco com'è andata la tua attività di recente.</p>
    </div>
    <div class="flex gap-2 flex-wrap w-full sm:w-auto">
      <a href="/admin/prenotazione-nuova.php" class="btn-secondary text-sm flex-1 sm:flex-none"><i data-lucide="plus" class="size-[16px]"></i> <span class="truncate">Nuova prenotazione</span></a>
      <a href="/admin/appartamento-edit.php" class="btn-outline text-sm flex-1 sm:flex-none"><i data-lucide="building-2" class="size-[16px]"></i> <span class="truncate">Nuovo appartamento</span></a>
    </div>
  </div>

  <!-- KPI -->
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
      $kpis = [
        ['Fatturato', fmtMoney($revenue), count($bookings) . ' prenotazioni', 'trending-up', 'from-emerald-400 to-emerald-600', $revTrend],
        ['Saldo da incassare', fmtMoney($due), $due > 0 ? 'da sollecitare' : 'tutto incassato', 'clock', 'from-amber-400 to-amber-600', null],
        ['Spese totali', fmtMoney($exp_total), count($expenses) . ' voci', 'wallet', 'from-rose-400 to-rose-600', null],
        ['Utile netto', fmtMoney($profit), 'ricavi − spese', 'sparkles', 'from-brand-400 to-brand-600', null],
      ];
      foreach ($kpis as $i => $k):
    ?>
      <div class="card p-4 sm:p-5 card-hover relative overflow-hidden animate-slide-up" style="animation-delay:<?= $i * 60 ?>ms">
        <div class="flex items-start justify-between gap-2">
          <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-gradient-to-br <?= $k[4] ?> text-white flex items-center justify-center shadow-md shrink-0"><i data-lucide="<?= $k[3] ?>" class="size-[20px]"></i></div>
          <?php if ($k[5] !== null): ?>
            <span class="badge-soft <?= $k[5] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' ?> tabular-nums shrink-0"><i data-lucide="<?= $k[5] >= 0 ? 'trending-up' : 'trending-down' ?>" class="size-[12px]"></i> <?= ($k[5] >= 0 ? '+' : '') . $k[5] ?>%</span>
          <?php endif; ?>
        </div>
        <div class="mt-3 sm:mt-4 min-w-0">
          <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500 truncate"><?= e($k[0]) ?></div>
          <div class="text-2xl sm:text-[28px] font-display font-bold tracking-tight mt-0.5 tabular-nums truncate"><?= e($k[1]) ?></div>
          <div class="text-xs text-ink-500 mt-1 truncate"><?= e($k[2]) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([
      ['Appartamenti attivi', $apt_count, 'building-2', 'text-sky-500 bg-sky-500/10'],
      ['Prossimi check-in', count($upcoming), 'log-in', 'text-violet-500 bg-violet-500/10'],
      ['Prenotazioni totali', count($bookings), 'bookmark-check', 'text-indigo-500 bg-indigo-500/10'],
      ['In attesa di conferma', $pending_count, 'alert-triangle', 'text-amber-500 bg-amber-500/10'],
    ] as $s): ?>
      <div class="card p-4 flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl flex items-center justify-center <?= $s[3] ?>"><i data-lucide="<?= $s[2] ?>" class="size-[18px]"></i></div>
        <div>
          <div class="text-xs text-ink-500"><?= e($s[0]) ?></div>
          <div class="font-display font-bold text-xl tabular-nums"><?= (int)$s[1] ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- CHART -->
  <div class="grid lg:grid-cols-3 gap-5">
    <div class="card p-6 lg:col-span-2">
      <div class="flex items-end justify-between mb-4">
        <div>
          <h2 class="font-serif text-xl font-semibold tracking-tight">Andamento mensile</h2>
          <p class="text-xs text-ink-500 mt-0.5">Ultimi 12 mesi · ricavi vs spese vs utile</p>
        </div>
        <div class="flex gap-3 text-xs">
          <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Ricavi</span>
          <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Spese</span>
          <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-brand-500"></span> Utile</span>
        </div>
      </div>
      <div class="relative h-[220px] sm:h-[260px] lg:h-[300px]"><canvas id="lineChart"></canvas></div>
    </div>
    <div class="card p-4 sm:p-6">
      <h2 class="font-serif text-xl font-semibold tracking-tight">Top appartamenti</h2>
      <p class="text-xs text-ink-500 mt-0.5 mb-4">per fatturato</p>
      <?php if (!$top): ?><div class="text-sm text-ink-500">Nessun dato.</div><?php else: ?>
        <div class="relative h-[180px] sm:h-[220px] mb-4"><canvas id="pieChart"></canvas></div>
        <ul class="space-y-1.5 text-sm">
          <?php foreach ($top as $i => $t): ?>
            <li class="flex items-center justify-between">
              <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background:<?= ['#ff6a0a','#f04e00','#9c300d','#ff8a32','#ffb56c'][$i] ?>"></span><span class="truncate"><?= e($t['name']) ?></span></span>
              <span class="font-semibold tabular-nums"><?= fmtMoney((float)$t['total']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- LISTE -->
  <div class="grid lg:grid-cols-2 gap-5">
    <div class="card p-4 sm:p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-serif text-xl font-semibold tracking-tight">Prossimi arrivi</h2>
        <a href="/admin/calendario.php" class="text-sm text-brand-600 font-medium hover:underline">Calendario →</a>
      </div>
      <?php if (!$upcoming): ?>
        <div class="text-center py-8">
          <div class="h-14 w-14 mx-auto rounded-2xl bg-ink-100 dark:bg-ink-800 flex items-center justify-center text-ink-400 mb-3"><i data-lucide="calendar-x" class="size-[24px]"></i></div>
          <p class="text-sm text-ink-500">Nessun arrivo programmato.</p>
        </div>
      <?php else: ?>
        <ul class="space-y-2.5">
          <?php foreach ($upcoming as $b):
            $days = floor((strtotime($b['check_in']) - strtotime('today')) / 86400);
            $parts = explode(' ', trim($b['customer_name']));
            $initials = mb_strtoupper(mb_substr($parts[0] ?? '·', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
          ?>
            <li>
              <a href="/admin/prenotazione.php?id=<?= e($b['id']) ?>" class="flex items-center gap-2 sm:gap-3 p-2.5 sm:p-3 rounded-xl hover:bg-ink-50 dark:hover:bg-ink-900/40 transition group">
                <span class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center font-semibold text-xs sm:text-sm shrink-0"><?= e($initials) ?></span>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm truncate"><?= e($b['customer_name']) ?></div>
                  <div class="text-xs text-ink-500 truncate"><?= e($b['apartment_name']) ?> · <?= fmtDate($b['check_in']) ?> → <?= fmtDate($b['check_out']) ?></div>
                </div>
                <span class="badge-soft tabular-nums shrink-0 text-[10px] sm:text-xs"><?= $days <= 0 ? 'oggi' : ($days == 1 ? 'domani' : "tra {$days}gg") ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="card p-4 sm:p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-serif text-xl font-semibold tracking-tight">Ultime prenotazioni</h2>
        <a href="/admin/prenotazioni.php" class="text-sm text-brand-600 font-medium hover:underline">Tutte →</a>
      </div>
      <ul class="space-y-2.5">
        <?php foreach ($recent as $b):
          $statusMap = ['pending' => ['warning', 'In attesa'], 'confirmed' => ['info', 'Confermata'], 'checked_in' => ['success', 'Check-in'], 'completed' => ['soft', 'Completata'], 'cancelled' => ['danger', 'Cancellata']];
          $st = $statusMap[$b['status']] ?? ['soft', $b['status']];
        ?>
          <li>
            <a href="/admin/prenotazione.php?id=<?= e($b['id']) ?>" class="flex items-center gap-2 sm:gap-3 p-2.5 sm:p-3 rounded-xl hover:bg-ink-50 dark:hover:bg-ink-900/40 transition">
              <div class="flex-1 min-w-0">
                <div class="flex items-baseline gap-2 min-w-0">
                  <span class="font-medium text-sm truncate"><?= e($b['customer_name']) ?></span>
                  <span class="text-[10px] font-mono text-ink-400 hidden sm:inline shrink-0"><?= e($b['code']) ?></span>
                </div>
                <div class="text-xs text-ink-500 truncate"><?= e($b['apartment_name']) ?> · <?= fmtMoney((float)$b['total']) ?></div>
              </div>
              <span class="badge-<?= $st[0] ?> shrink-0 text-[10px] sm:text-xs"><?= e($st[1]) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function() {
  if (typeof Chart === 'undefined') { console.error('Chart.js non caricato'); return; }
  const series = <?= json_encode($series) ?>;
  const top = <?= json_encode($top) ?>;
  const eur = v => new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(v || 0);
  const isDark = () => document.documentElement.classList.contains('dark');
  const grid = () => isDark() ? '#2f303d' : '#eeeef1';
  const tick = () => isDark() ? '#737486' : '#9293a2';

  const lineEl = document.getElementById('lineChart');
  if (lineEl) {
    const lineCtx = lineEl.getContext('2d');
    const gradRev = lineCtx.createLinearGradient(0, 0, 0, 300);
    gradRev.addColorStop(0, 'rgba(16,185,129,.25)'); gradRev.addColorStop(1, 'rgba(16,185,129,0)');
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: series.map(s => s.month),
        datasets: [
          { label: 'Ricavi', data: series.map(s => s.rev), borderColor: '#10b981', backgroundColor: gradRev, tension: 0.4, fill: true, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6 },
          { label: 'Spese', data: series.map(s => s.exp), borderColor: '#ef4444', tension: 0.4, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6 },
          { label: 'Utile', data: series.map(s => s.profit), borderColor: '#ff6a0a', tension: 0.4, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6 },
        ]
      },
      options: { responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1a1b25', borderRadius: 12, padding: 12, displayColors: true, boxPadding: 4, callbacks: { label: c => '  ' + c.dataset.label + ': ' + eur(c.parsed.y) } } },
        scales: { x: { grid: { display: false }, ticks: { color: tick() } }, y: { grid: { color: grid() }, ticks: { color: tick(), callback: v => '€' + (v/1000 >= 1 ? (v/1000) + 'k' : v) } } }
      }
    });
  }

  const pieEl = document.getElementById('pieChart');
  if (pieEl && top.length) {
    new Chart(pieEl, {
      type: 'doughnut',
      data: { labels: top.map(t => t.name), datasets: [{ data: top.map(t => t.total), backgroundColor: ['#ff6a0a','#f04e00','#9c300d','#ff8a32','#ffb56c'], borderWidth: 0, borderRadius: 8, spacing: 4 }] },
      options: { cutout: '70%', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => '  ' + c.label + ': ' + eur(c.parsed) } } } }
    });
  }
});
</script>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
