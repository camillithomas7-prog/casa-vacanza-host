<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$apartments = rows('SELECT id, name FROM apartments ORDER BY name ASC');
if (!$apartments) { $title='Calendario'; require __DIR__.'/../partials/head.php'; require __DIR__.'/../partials/admin-shell-top.php'; echo '<div class="card p-10 text-center">Crea prima un appartamento.</div>'; require __DIR__.'/../partials/admin-shell-bottom.php'; exit; }

$aptId = $_GET['apt'] ?? $apartments[0]['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'block_add') {
        q('INSERT INTO date_blocks (id, apartment_id, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)',
            [newId(), $_POST['apartment_id'], $_POST['start_date'], $_POST['end_date'], $_POST['reason'] ?: null]);
        flash('Date bloccate');
    }
    if ($action === 'block_delete') {
        q('DELETE FROM date_blocks WHERE id = ?', [$_POST['block_id']]);
    }
    redirect('/admin/calendario.php?apt=' . urlencode($aptId));
}

$bookings = rows('SELECT b.*, c.name AS customer_name FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE b.apartment_id = ? AND b.status != "cancelled"', [$aptId]);
$blocks = rows('SELECT * FROM date_blocks WHERE apartment_id = ? ORDER BY start_date ASC', [$aptId]);

// Vista globale: bookings e blocks di TUTTI gli appartamenti (per la matrice colpo d'occhio)
$bookingsAll = rows('SELECT b.apartment_id, b.id, b.check_in, b.check_out, c.name AS customer_name FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE b.status != "cancelled"');
$blocksAll   = rows('SELECT apartment_id, start_date, end_date FROM date_blocks');
$bookingsByApt = []; $blocksByApt = [];
foreach ($bookingsAll as $b) { $bookingsByApt[$b['apartment_id']][] = $b; }
foreach ($blocksAll   as $b) { $blocksByApt[$b['apartment_id']][]   = $b; }

$ref = !empty($_GET['m']) ? strtotime($_GET['m'] . '-01') : strtotime(date('Y-m-01'));
$cy = (int)date('Y', $ref); $cm = (int)date('n', $ref);
$first = mktime(0,0,0,$cm,1,$cy);
$first_dow = (int)date('N', $first) - 1;
$start = $first - $first_dow * 86400;
$days = [];
for ($i = 0; $i < 42; $i++) $days[] = $start + $i * 86400;

function dayInfo($ts, $bookings, $blocks) {
    $d = date('Y-m-d', $ts);
    foreach ($blocks as $b) if ($d >= $b['start_date'] && $d < $b['end_date']) return ['s' => 'blocked', 'b' => null];
    foreach ($bookings as $b) {
        if ($d === $b['check_in']) return ['s' => 'check_in', 'b' => $b];
        if ($d === $b['check_out']) return ['s' => 'check_out', 'b' => $b];
        if ($d > $b['check_in'] && $d < $b['check_out']) return ['s' => 'booked', 'b' => $b];
    }
    return ['s' => 'free', 'b' => null];
}

$months_it = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$prev_m = date('Y-m', strtotime('-1 month', $ref));
$next_m = date('Y-m', strtotime('+1 month', $ref));

$title = 'Calendario';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5" x-data="{ showBlock: false }">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold">Calendario</h1>
      <p class="text-ink-500 mt-1">Vista mensile, blocchi, prenotazioni in tempo reale.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <form method="get"><select name="apt" onchange="this.form.submit()" class="input">
        <?php foreach ($apartments as $a): ?><option value="<?= e($a['id']) ?>" <?= $a['id'] === $aptId ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
      </select></form>
      <button @click="showBlock=true" class="btn-secondary"><i data-lucide="lock" class="size-[16px]"></i> Blocca date</button>
      <a href="/admin/prenotazione-nuova.php?apt=<?= e($aptId) ?>" class="btn-primary"><i data-lucide="plus" class="size-[16px]"></i> Prenotazione</a>
    </div>
  </div>

  <!-- ========== VISTA GLOBALE: tutti gli appartamenti × giorni ========== -->
  <div class="card p-3 sm:p-5" x-data="{ range: window.innerWidth < 640 ? 14 : 0 }">
    <div class="flex items-start sm:items-center justify-between gap-2 mb-3 flex-wrap">
      <div class="min-w-0">
        <h2 class="font-display font-bold text-base sm:text-xl leading-tight">Disponibilità · tutti gli appartamenti</h2>
        <p class="text-[11px] sm:text-xs text-ink-500 mt-0.5 hidden sm:block">Clicca una cella per aprire la prenotazione</p>
      </div>
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <a href="?apt=<?= e($aptId) ?>&m=<?= $prev_m ?>" class="h-9 w-9 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center hover:bg-ink-50 dark:hover:bg-ink-800 transition" aria-label="mese precedente"><i data-lucide="chevron-left" class="size-[16px]"></i></a>
        <div class="font-display text-sm sm:text-lg font-bold tabular-nums px-2 whitespace-nowrap"><?= $months_it[$cm-1] ?> <?= $cy ?></div>
        <a href="?apt=<?= e($aptId) ?>&m=<?= $next_m ?>" class="h-9 w-9 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center hover:bg-ink-50 dark:hover:bg-ink-800 transition" aria-label="mese successivo"><i data-lucide="chevron-right" class="size-[16px]"></i></a>
      </div>
    </div>

    <?php
      // Giorni del mese corrente (1..31)
      $daysInMonth = (int)date('t', $first);
      $today = date('Y-m-d');
      $monthDays = [];
      for ($i = 1; $i <= $daysInMonth; $i++) $monthDays[] = mktime(0,0,0,$cm,$i,$cy);
      // Su mobile mostro inizialmente i prossimi 14 giorni (a partire da oggi se nel mese corrente, altrimenti da inizio mese)
      $todayDay = ($cy == (int)date('Y') && $cm == (int)date('n')) ? (int)date('j') : 1;
      $mobileStart = max(0, $todayDay - 1);
    ?>
    <!-- hint scroll -->
    <div class="sm:hidden flex items-center justify-end gap-1 text-[10px] text-ink-400 mb-1.5">
      <i data-lucide="move-horizontal" class="size-[12px]"></i> <span>scorri orizzontalmente</span>
    </div>

    <div class="relative -mx-3 sm:-mx-5">
      <!-- gradient fade ai bordi su mobile -->
      <div class="sm:hidden pointer-events-none absolute inset-y-0 right-0 w-6 bg-gradient-to-l from-white dark:from-ink-900 to-transparent z-20"></div>
      <div class="overflow-x-auto" data-cal-scroll>
        <table class="min-w-full border-separate" style="border-spacing:0;">
          <thead class="sticky top-0 z-10">
            <tr>
              <th class="sticky left-0 z-20 bg-white dark:bg-ink-900 text-left px-2 sm:px-3 py-2 text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-ink-500 border-b border-r border-ink-100 dark:border-ink-800 min-w-[100px] sm:min-w-[160px]">Appartamento</th>
              <?php foreach ($monthDays as $ts):
                $d = date('Y-m-d', $ts);
                $dow = (int)date('N', $ts);
                $isWeekend = $dow >= 6;
                $isToday = $d === $today;
                $colBg = $isToday ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-200 font-bold' : ($isWeekend ? 'bg-ink-50 dark:bg-ink-900/60 text-ink-500' : 'bg-white dark:bg-ink-900 text-ink-500');
              ?>
                <th class="px-0 py-1.5 text-center text-[10px] sm:text-[11px] font-semibold border-b border-ink-100 dark:border-ink-800 <?= $colBg ?> min-w-[28px] sm:min-w-[30px]">
                  <div class="tabular-nums leading-none text-[12px] sm:text-[13px]"><?= (int)date('j', $ts) ?></div>
                  <div class="text-[9px] uppercase opacity-60 mt-0.5"><?= ['','L','M','M','G','V','S','D'][$dow] ?></div>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($apartments as $apt):
              $b_apt = $bookingsByApt[$apt['id']] ?? [];
              $bl_apt = $blocksByApt[$apt['id']] ?? [];
            ?>
              <tr class="group">
                <td class="sticky left-0 z-10 bg-white dark:bg-ink-900 px-2 sm:px-3 py-1 border-b border-r border-ink-100 dark:border-ink-800 text-[13px] sm:text-sm font-medium group-hover:bg-ink-50 dark:group-hover:bg-ink-800/50 min-w-[100px] sm:min-w-[160px] max-w-[140px] sm:max-w-[200px]">
                  <a href="?apt=<?= e($apt['id']) ?>&m=<?= date('Y-m', $first) ?>" class="hover:text-brand-600 transition truncate block leading-tight"><?= e($apt['name']) ?></a>
                </td>
                <?php foreach ($monthDays as $ts):
                  $d = date('Y-m-d', $ts);
                  $dow = (int)date('N', $ts);
                  $isWeekend = $dow >= 6;
                  $isToday = $d === $today;
                  $info = dayInfo($ts, $b_apt, $bl_apt);
                  $st = $info['s'];
                  $cellCls = $st === 'booked'    ? 'bg-red-400 dark:bg-red-500/80 hover:bg-red-500' :
                            ($st === 'check_in'  ? 'bg-gradient-to-r from-emerald-300 to-amber-300 dark:from-emerald-500/50 dark:to-amber-500/50' :
                            ($st === 'check_out' ? 'bg-gradient-to-r from-amber-300 to-emerald-300 dark:from-amber-500/50 dark:to-emerald-500/50' :
                            ($st === 'blocked'   ? 'bg-ink-300 dark:bg-ink-700 hover:bg-ink-400' :
                                                   'bg-emerald-100 dark:bg-emerald-500/20 hover:bg-emerald-200')));
                  $colBg = $isWeekend ? 'bg-ink-50 dark:bg-ink-900/40' : '';
                  $title = '';
                  if ($info['b']) $title = $info['b']['customer_name'] . ' · ' . fmtDateShort($info['b']['check_in']) . ' → ' . fmtDateShort($info['b']['check_out']);
                  elseif ($st === 'blocked') $title = 'Bloccato';
                  else $title = 'Disponibile';
                  $clickHref = $info['b'] ? '/admin/prenotazione.php?id=' . $info['b']['id'] : null;
                ?>
                  <td class="p-0 border-b border-ink-100 dark:border-ink-800 <?= $colBg ?> <?= $isToday ? 'relative' : '' ?>">
                    <?php if ($clickHref): ?>
                      <a href="<?= e($clickHref) ?>" title="<?= e($title) ?>" class="block h-9 sm:h-9 mx-0.5 my-0.5 rounded <?= $cellCls ?> transition active:scale-95"></a>
                    <?php else: ?>
                      <div title="<?= e($title) ?>" class="h-9 sm:h-9 mx-0.5 my-0.5 rounded <?= $cellCls ?>"></div>
                    <?php endif; ?>
                    <?php if ($isToday): ?><div class="absolute inset-x-0 top-0 bottom-0 pointer-events-none border-l-2 border-r-2 border-brand-500/60"></div><?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-x-3 gap-y-2 text-[11px] sm:text-xs mt-4 text-ink-500">
      <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded bg-emerald-200 dark:bg-emerald-500/30"></span> Disponibile</span>
      <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded bg-red-400"></span> Occupato</span>
      <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded bg-gradient-to-r from-emerald-300 to-amber-300"></span> Check-in</span>
      <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded bg-gradient-to-r from-amber-300 to-emerald-300"></span> Check-out</span>
      <span class="flex items-center gap-1.5 col-span-2"><span class="h-3 w-4 rounded bg-ink-400"></span> Bloccato</span>
    </div>
  </div>
  <script>
  // Su mobile, fa scroll automatico fino al giorno di oggi se nel mese corrente
  (function(){
    if (window.innerWidth >= 640) return;
    var box = document.querySelector('[data-cal-scroll]');
    if (!box) return;
    var today = box.querySelector('th[class*="bg-brand-50"]');
    if (!today) return;
    var stickyLeftWidth = 100; // larghezza minima colonna sticky su mobile
    box.scrollLeft = Math.max(0, today.offsetLeft - stickyLeftWidth - 8);
  })();
  </script>

  <!-- ========== DETTAGLIO APPARTAMENTO SINGOLO ========== -->
  <div class="card p-3 sm:p-5">
    <div class="text-xs uppercase tracking-wider text-ink-500 font-semibold mb-3">Dettaglio appartamento</div>
    <div class="flex items-center justify-between mb-4">
      <a href="?apt=<?= e($aptId) ?>&m=<?= $prev_m ?>" class="btn-ghost"><i data-lucide="chevron-left" class="size-[18px]"></i></a>
      <div class="font-display text-lg sm:text-xl font-bold"><?= $months_it[$cm-1] ?> <?= $cy ?></div>
      <a href="?apt=<?= e($aptId) ?>&m=<?= $next_m ?>" class="btn-ghost"><i data-lucide="chevron-right" class="size-[18px]"></i></a>
    </div>
    <div class="grid grid-cols-7 gap-1 text-[10px] sm:text-xs text-ink-500 mb-1">
      <?php foreach (['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $d): ?><div class="text-center font-medium px-1 sm:px-2 py-1"><?= $d ?></div><?php endforeach; ?>
    </div>
    <div class="grid grid-cols-7 gap-1">
      <?php foreach ($days as $ts): $in = (int)date('n',$ts) === $cm; $info = dayInfo($ts, $bookings, $blocks);
        $cls = $info['s'] === 'booked' ? 'bg-red-100 dark:bg-red-900/30 border-red-200' :
               ($info['s'] === 'check_in' || $info['s'] === 'check_out' ? 'bg-amber-100 dark:bg-amber-900/30 border-amber-300' :
               ($info['s'] === 'blocked' ? 'bg-ink-200 dark:bg-ink-800 border-ink-300' :
               'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200'));
      ?>
        <div class="min-h-[52px] sm:min-h-[80px] p-1 sm:p-2 rounded-lg border <?= $in ? $cls : 'bg-ink-50/50 dark:bg-ink-900/30 border-transparent text-ink-300' ?>">
          <div class="text-xs font-medium"><?= (int)date('j', $ts) ?></div>
          <?php if ($info['b']): ?>
            <a href="/admin/prenotazione.php?id=<?= e($info['b']['id']) ?>" class="hidden sm:block mt-1 text-[10px] truncate font-medium hover:underline">
              <?= $info['s'] === 'check_in' ? '🟡 IN ' : ($info['s'] === 'check_out' ? '🟡 OUT ' : '') ?><?= e($info['b']['customer_name']) ?>
            </a>
            <a href="/admin/prenotazione.php?id=<?= e($info['b']['id']) ?>" class="sm:hidden block mt-1" title="<?= e($info['b']['customer_name']) ?>"><i data-lucide="<?= $info['s'] === 'check_in' ? 'log-in' : ($info['s'] === 'check_out' ? 'log-out' : 'user') ?>" class="size-[11px]"></i></a>
          <?php endif; ?>
          <?php if ($info['s'] === 'blocked'): ?><div class="mt-1 text-[10px] text-ink-500 hidden sm:block">Bloccato</div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex flex-wrap gap-3 text-xs mt-4 text-ink-500">
      <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-emerald-300"></span> Disponibile</span>
      <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-red-300"></span> Occupato</span>
      <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-amber-300"></span> Check-in/out</span>
      <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-ink-400"></span> Bloccato</span>
    </div>
  </div>

  <?php if ($blocks): ?>
    <div class="card p-4 sm:p-5">
      <h3 class="font-display font-bold mb-3">Date bloccate</h3>
      <ul class="divide-y divide-ink-100 dark:divide-ink-800">
        <?php foreach ($blocks as $b): ?>
          <li class="py-2 flex items-center justify-between">
            <div>
              <div class="text-sm font-medium"><?= fmtDateShort($b['start_date']) ?> → <?= fmtDateShort($b['end_date']) ?></div>
              <?php if ($b['reason']): ?><div class="text-xs text-ink-500"><?= e($b['reason']) ?></div><?php endif; ?>
            </div>
            <form method="post" onsubmit="return confirm('Rimuovere blocco?')">
              <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
              <input type="hidden" name="action" value="block_delete">
              <input type="hidden" name="block_id" value="<?= e($b['id']) ?>">
              <button class="btn-ghost text-red-600"><i data-lucide="x" class="size-[16px]"></i></button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div x-show="showBlock" x-cloak class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4" @click="showBlock=false" style="display:none">
    <form method="post" class="card p-4 sm:p-5 w-full max-w-md" @click.stop>
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="action" value="block_add">
      <input type="hidden" name="apartment_id" value="<?= e($aptId) ?>">
      <h3 class="font-display font-bold text-lg">Blocca un periodo</h3>
      <p class="text-sm text-ink-500">Le date bloccate non sono prenotabili.</p>
      <div class="grid grid-cols-2 gap-3 mt-4">
        <label><span class="label">Da</span><input type="date" name="start_date" class="input" required></label>
        <label><span class="label">A</span><input type="date" name="end_date" class="input" required></label>
      </div>
      <label class="block mt-3"><span class="label">Motivo (opz.)</span><input class="input" name="reason" placeholder="Manutenzione, uso personale..."></label>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" @click="showBlock=false" class="btn-outline">Annulla</button>
        <button class="btn-primary">Blocca</button>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
