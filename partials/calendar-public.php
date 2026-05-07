<?php
$cal_ref = !empty($_GET['m']) ? strtotime($_GET['m'] . '-01') : strtotime(date('Y-m-01'));
$cal_year = (int)date('Y', $cal_ref); $cal_month = (int)date('n', $cal_ref);
$first = mktime(0,0,0,$cal_month,1,$cal_year);
$last = mktime(0,0,0,$cal_month+1,0,$cal_year);
$first_dow = ((int)date('N', $first) - 1); // 0=Mon
$start_grid = $first - $first_dow * 86400;
$days = [];
for ($i = 0; $i < 42; $i++) $days[] = $start_grid + $i * 86400;

function dayCellStatus($ts, $bookings, $blocks) {
  $d = date('Y-m-d', $ts);
  foreach ($blocks as $b) if ($d >= $b['start_date'] && $d < $b['end_date']) return 'blocked';
  foreach ($bookings as $b) {
    if ($d === $b['check_in']) return 'check_in';
    if ($d === $b['check_out']) return 'check_out';
    if ($d > $b['check_in'] && $d < $b['check_out']) return 'booked';
  }
  return 'free';
}
$prev_m = date('Y-m', strtotime('-1 month', $cal_ref));
$next_m = date('Y-m', strtotime('+1 month', $cal_ref));
$months_it = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
?>
<div>
  <div class="flex items-center justify-between mb-3">
    <a href="?slug=<?= e($a['slug']) ?>&m=<?= $prev_m ?>#calendar" class="btn-ghost"><i data-lucide="chevron-left" class="size-[18px]"></i></a>
    <div class="font-display font-semibold text-lg"><?= $months_it[$cal_month-1] ?> <?= $cal_year ?></div>
    <a href="?slug=<?= e($a['slug']) ?>&m=<?= $next_m ?>#calendar" class="btn-ghost"><i data-lucide="chevron-right" class="size-[18px]"></i></a>
  </div>
  <div class="grid grid-cols-7 gap-1 text-xs text-ink-500 mb-1">
    <?php foreach (['L','M','M','G','V','S','D'] as $d): ?><div class="text-center font-medium"><?= $d ?></div><?php endforeach; ?>
  </div>
  <div class="grid grid-cols-7 gap-1">
    <?php foreach ($days as $ts): $in = (int)date('n',$ts) === $cal_month; $st = dayCellStatus($ts, $bookings, $blocks);
      $cls = $st === 'booked' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 line-through' :
             ($st === 'check_in' || $st === 'check_out' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40' :
             ($st === 'blocked' ? 'bg-ink-200 text-ink-500 dark:bg-ink-800' :
             'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'));
    ?>
      <div class="text-center text-sm py-2 rounded-lg <?= $in ? $cls : 'text-ink-300' ?>"><?= (int)date('j', $ts) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="flex flex-wrap gap-3 text-xs mt-3 text-ink-500">
    <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-emerald-300"></span> Disponibile</span>
    <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-red-300"></span> Occupato</span>
    <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-amber-300"></span> Check-in/out</span>
  </div>
</div>
