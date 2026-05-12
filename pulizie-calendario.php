<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/cleaning.php';

$token = $_GET['t'] ?? '';
$valid = $token && hash_equals(cleanerToken(), $token);
if (!$valid) { http_response_code(401); exit('Link non valido'); }

// Mese di riferimento
$ref = !empty($_GET['m']) ? strtotime($_GET['m'] . '-01') : strtotime(date('Y-m-01'));
$cy = (int)date('Y', $ref); $cm = (int)date('n', $ref);
$first = mktime(0,0,0,$cm,1,$cy);
$daysInMonth = (int)date('t', $first);
$today = date('Y-m-d');

$prev_m = date('Y-m', strtotime('-1 month', $ref));
$next_m = date('Y-m', strtotime('+1 month', $ref));
$months_it = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

// Tutti gli appartamenti
$apartments = rows('SELECT id, name FROM apartments WHERE active = 1 ORDER BY name ASC');

// Sessioni di pulizia del mese (= check-out di ogni booking)
$startMonth = date('Y-m-01', $first);
$endMonth   = date('Y-m-d', strtotime('+1 month', $first));
$sessions = [];
try {
  $sessions = rows("SELECT s.id, s.apartment_id, s.scheduled_date, s.status, b.code AS booking_code, c.name AS customer_name
                    FROM cleaning_sessions s
                    LEFT JOIN bookings b ON b.id = s.booking_id
                    LEFT JOIN customers c ON c.id = b.customer_id
                    WHERE s.scheduled_date >= ? AND s.scheduled_date < ?", [$startMonth, $endMonth]);
} catch (Throwable $e) {}

// Index: [apartment_id][YYYY-MM-DD] = session
$byApt = [];
foreach ($sessions as $s) {
    $byApt[$s['apartment_id']][$s['scheduled_date']] = $s;
}

$title = 'Calendario · Pulizie';
$pwaManifest = '/manifest-cleaner.php?t=' . rawurlencode($token);
$cleanerToken = $token;
$cleanerActiveTab = 'calendar';
require __DIR__ . '/partials/head.php';
?>
<div class="min-h-screen bg-ink-50/40 dark:bg-ink-950 pb-28">
  <header class="bg-white dark:bg-ink-900 border-b border-ink-100 dark:border-ink-800/80 sticky top-0 z-30">
    <div class="container-narrow py-3 px-4 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-sky-400 to-sky-600 text-white flex items-center justify-center shadow-md shrink-0"><i data-lucide="calendar" class="size-[20px]"></i></span>
        <div class="min-w-0">
          <div class="font-display font-bold leading-none">Calendario check-out</div>
          <div class="text-xs text-ink-500 mt-0.5">Quando devi pulire ogni appartamento</div>
        </div>
      </div>
    </div>
  </header>

  <main class="container-narrow py-4 px-4 space-y-4">
    <!-- Mese nav -->
    <div class="card p-3 flex items-center justify-between">
      <a href="?t=<?= e($token) ?>&m=<?= $prev_m ?>" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center hover:bg-ink-50 dark:hover:bg-ink-800 transition"><i data-lucide="chevron-left" class="size-[18px]"></i></a>
      <div class="font-display font-bold text-lg tabular-nums"><?= $months_it[$cm-1] ?> <?= $cy ?></div>
      <a href="?t=<?= e($token) ?>&m=<?= $next_m ?>" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center hover:bg-ink-50 dark:hover:bg-ink-800 transition"><i data-lucide="chevron-right" class="size-[18px]"></i></a>
    </div>

    <?php if (!$apartments): ?>
      <div class="card p-8 text-center text-sm text-ink-500">Nessun appartamento.</div>
    <?php else: ?>
      <?php foreach ($apartments as $apt):
        $aptSessions = $byApt[$apt['id']] ?? [];
        ksort($aptSessions);
      ?>
        <div class="card p-4 sm:p-5">
          <div class="flex items-center justify-between gap-2 mb-3">
            <h3 class="font-display font-bold text-base sm:text-lg truncate"><?= e($apt['name']) ?></h3>
            <?php if (count($aptSessions)): ?>
              <span class="badge-soft text-[10px]"><?= count($aptSessions) ?> check-out</span>
            <?php endif; ?>
          </div>

          <?php if (!$aptSessions): ?>
            <div class="text-sm text-ink-400 italic">Nessun check-out questo mese.</div>
          <?php else: ?>
            <!-- Mini strip: tutti i giorni del mese, evidenziati solo i check-out -->
            <div class="grid grid-cols-7 sm:grid-cols-[repeat(15,minmax(0,1fr))] gap-1 mb-3">
              <?php for ($d = 1; $d <= $daysInMonth; $d++):
                $date = sprintf('%04d-%02d-%02d', $cy, $cm, $d);
                $sess = $aptSessions[$date] ?? null;
                $isToday = $date === $today;
                $isPast  = $date < $today;
              ?>
                <?php if ($sess): ?>
                  <a href="/pulizia.php?t=<?= e($token) ?>&id=<?= e($sess['id']) ?>" title="Check-out: <?= e($sess['customer_name'] ?: 'ospite') ?>" class="aspect-square min-h-[36px] rounded-lg flex items-center justify-center text-sm font-bold transition <?= $sess['status'] === 'done' ? 'bg-emerald-500 text-white' : ($isPast ? 'bg-red-400 text-white' : 'bg-amber-400 text-amber-950 hover:bg-amber-500') ?>">
                    <?= $d ?>
                  </a>
                <?php else: ?>
                  <div class="aspect-square min-h-[36px] rounded-lg flex items-center justify-center text-xs text-ink-300 dark:text-ink-700 <?= $isToday ? 'ring-2 ring-sky-500/50' : '' ?>">
                    <?= $d ?>
                  </div>
                <?php endif; ?>
              <?php endfor; ?>
            </div>

            <!-- Lista discorsiva -->
            <ul class="divide-y divide-ink-100 dark:divide-ink-800">
              <?php foreach ($aptSessions as $date => $sess):
                $isPast = $date < $today;
                $isToday = $date === $today;
              ?>
                <li>
                  <a href="/pulizia.php?t=<?= e($token) ?>&id=<?= e($sess['id']) ?>" class="flex items-center gap-3 py-2.5 hover:bg-ink-50 dark:hover:bg-ink-800/40 rounded-lg px-2 -mx-2 transition">
                    <span class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 <?= $sess['status'] === 'done' ? 'bg-emerald-100 text-emerald-700' : ($isPast ? 'bg-red-100 text-red-700' : ($isToday ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700')) ?>">
                      <span class="text-sm font-bold tabular-nums"><?= (int)date('j', strtotime($date)) ?></span>
                    </span>
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-medium truncate"><?= fmtDate($date) ?> <?= $isToday ? '<span class="text-sky-600 text-xs">· OGGI</span>' : '' ?></div>
                      <?php if ($sess['customer_name']): ?>
                        <div class="text-xs text-ink-500 truncate">Ospite: <?= e($sess['customer_name']) ?></div>
                      <?php endif; ?>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider <?= $sess['status'] === 'done' ? 'text-emerald-700 font-bold' : 'text-ink-400' ?>"><?= $sess['status'] === 'done' ? '✓ fatto' : 'check-out' ?></span>
                    <i data-lucide="chevron-right" class="size-[16px] text-ink-400"></i>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <div class="flex flex-wrap gap-3 text-xs text-ink-500 mt-2 px-1">
      <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded bg-amber-400"></span> Da pulire</span>
      <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded bg-emerald-500"></span> Già fatta</span>
      <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded bg-red-400"></span> In ritardo</span>
    </div>
  </main>
</div>
<?php require __DIR__ . '/partials/cleaner-nav.php'; ?>
<script>if (window.lucide) lucide.createIcons();</script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
