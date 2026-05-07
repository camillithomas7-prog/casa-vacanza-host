<?php
$cal_ref = !empty($_GET['m']) ? strtotime($_GET['m'] . '-01') : strtotime(date('Y-m-01'));
$cal_year = (int)date('Y', $cal_ref); $cal_month = (int)date('n', $cal_ref);
$first = mktime(0,0,0,$cal_month,1,$cal_year);
$first_dow = ((int)date('N', $first) - 1);
$start_grid = $first - $first_dow * 86400;
$days = [];
for ($i = 0; $i < 42; $i++) $days[] = $start_grid + $i * 86400;

if (!function_exists('dayCellStatus')) {
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
}
$prev_m = date('Y-m', strtotime('-1 month', $cal_ref));
$next_m = date('Y-m', strtotime('+1 month', $cal_ref));
$months_it = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
?>
<div>
  <div class="flex items-center justify-between mb-4">
    <a href="?slug=<?= e($a['slug']) ?>&m=<?= $prev_m ?>#calendar" class="h-9 w-9 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center hover:bg-ink-50 dark:hover:bg-ink-800"><i data-lucide="chevron-left" class="size-[16px]"></i></a>
    <div class="font-display font-bold text-lg"><?= $months_it[$cal_month-1] ?> <?= $cal_year ?></div>
    <a href="?slug=<?= e($a['slug']) ?>&m=<?= $next_m ?>#calendar" class="h-9 w-9 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center hover:bg-ink-50 dark:hover:bg-ink-800"><i data-lucide="chevron-right" class="size-[16px]"></i></a>
  </div>
  <div class="grid grid-cols-7 gap-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500 mb-2">
    <?php foreach (['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $d): ?><div class="text-center"><?= $d ?></div><?php endforeach; ?>
  </div>
  <div class="grid grid-cols-7 gap-1.5">
    <?php foreach ($days as $ts): $in = (int)date('n',$ts) === $cal_month; $st = dayCellStatus($ts, $bookings, $blocks);
      $cls = $st === 'booked' ? 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300 line-through' :
             ($st === 'check_in' || $st === 'check_out' ? 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' :
             ($st === 'blocked' ? 'bg-ink-100 text-ink-400 dark:bg-ink-800 dark:text-ink-500' :
             'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300 hover:ring-2 hover:ring-emerald-500/30'));
    ?>
      <div class="aspect-square flex items-center justify-center text-sm rounded-lg transition <?= $in ? $cls : 'text-ink-300 dark:text-ink-700' ?>"><?= (int)date('j', $ts) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="flex flex-wrap gap-4 text-xs mt-5 text-ink-500">
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-md bg-emerald-200 dark:bg-emerald-500/30"></span> Disponibile</span>
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-md bg-red-200 dark:bg-red-500/30"></span> Occupato</span>
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-md bg-amber-200 dark:bg-amber-500/30"></span> Check-in/out</span>
  </div>
</div>
