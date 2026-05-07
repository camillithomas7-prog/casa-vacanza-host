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
$today = date('Y-m-d');
$apt_id = (int)$a['id'];
?>
<div x-data="calPicker(<?= $apt_id ?>)" x-init="init()">
  <div class="flex items-center justify-between mb-4">
    <a href="?slug=<?= e($a['slug']) ?>&m=<?= $prev_m ?>#calendar" class="h-9 w-9 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center hover:bg-ink-50 dark:hover:bg-ink-800"><i data-lucide="chevron-left" class="size-[16px]"></i></a>
    <div class="font-display font-bold text-lg"><?= $months_it[$cal_month-1] ?> <?= $cal_year ?></div>
    <a href="?slug=<?= e($a['slug']) ?>&m=<?= $next_m ?>#calendar" class="h-9 w-9 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center hover:bg-ink-50 dark:hover:bg-ink-800"><i data-lucide="chevron-right" class="size-[16px]"></i></a>
  </div>
  <div class="grid grid-cols-7 gap-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500 mb-2">
    <?php foreach (['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $d): ?><div class="text-center"><?= $d ?></div><?php endforeach; ?>
  </div>
  <div class="grid grid-cols-7 gap-1 sm:gap-1.5">
    <?php foreach ($days as $ts):
      $in = (int)date('n',$ts) === $cal_month;
      $st = dayCellStatus($ts, $bookings, $blocks);
      $d = date('Y-m-d', $ts);
      $past = $d < $today;
      $clickable = $in && !$past && ($st === 'free' || $st === 'check_out');
      $base = $st === 'booked' ? 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300 line-through' :
              ($st === 'check_in' ? 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' :
              ($st === 'check_out' ? 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' :
              ($st === 'blocked' ? 'bg-ink-100 text-ink-400 dark:bg-ink-800 dark:text-ink-500' :
              'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300')));
      $not_in_cls = 'text-ink-300 dark:text-ink-700';
      $past_cls = 'text-ink-300 dark:text-ink-700 line-through';
    ?>
      <?php if ($clickable): ?>
        <button type="button" @click="pick('<?= $d ?>')" :class="cellCls('<?= $d ?>', '<?= $base ?> hover:ring-2 hover:ring-emerald-500/40')" class="aspect-square min-h-[40px] flex items-center justify-center text-sm rounded-lg transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-brand-500">
          <?= (int)date('j', $ts) ?>
        </button>
      <?php else: ?>
        <div class="aspect-square min-h-[40px] flex items-center justify-center text-sm rounded-lg <?= !$in ? $not_in_cls : ($past ? $past_cls : $base) ?>">
          <?= (int)date('j', $ts) ?>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <div class="flex flex-wrap gap-4 text-xs mt-5 text-ink-500">
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-md bg-emerald-200 dark:bg-emerald-500/30"></span> Disponibile</span>
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-md bg-red-200 dark:bg-red-500/30"></span> Occupato</span>
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-md bg-amber-200 dark:bg-amber-500/30"></span> Check-in/out</span>
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-md bg-brand-500"></span> Selezionato</span>
  </div>
  <div x-show="from || to" class="mt-4 p-3 rounded-xl bg-brand-50 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/30 text-sm flex items-center justify-between gap-2 animate-fade-in">
    <div class="flex items-center gap-2 text-brand-700 dark:text-brand-300">
      <i data-lucide="calendar-check" class="size-[16px]"></i>
      <span><span x-show="from && !to">Check-in: <strong x-text="fmtIt(from)"></strong> · Scegli check-out</span><span x-show="from && to"><strong x-text="fmtIt(from)"></strong> → <strong x-text="fmtIt(to)"></strong> · <span x-text="nights"></span> notti</span></span>
    </div>
    <button type="button" @click="reset()" class="text-xs font-semibold text-brand-700 dark:text-brand-300 hover:underline">Reset</button>
  </div>
</div>
<script>
function calPicker(aptId) {
  return {
    aptId,
    from: '',
    to: '',
    get nights() {
      if (!this.from || !this.to) return 0;
      return Math.round((new Date(this.to) - new Date(this.from)) / 86400000);
    },
    init() {
      try {
        const saved = JSON.parse(sessionStorage.getItem('cv_book_' + this.aptId) || '{}');
        if (saved.from) this.from = saved.from;
        if (saved.to) this.to = saved.to;
      } catch(e) {}
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },
    fmtIt(d) {
      if (!d) return '';
      const [y, m, day] = d.split('-');
      return `${day}/${m}/${y}`;
    },
    cellCls(d, def) {
      if (d === this.from || d === this.to) return 'bg-brand-500 text-white shadow-md ring-2 ring-brand-500';
      if (this.from && this.to && d > this.from && d < this.to) return 'bg-brand-100 text-brand-700 dark:bg-brand-500/30 dark:text-brand-200';
      return def;
    },
    pick(d) {
      if (!this.from || (this.from && this.to)) {
        this.from = d; this.to = '';
      } else if (d <= this.from) {
        this.from = d; this.to = '';
      } else {
        this.to = d;
      }
      this.persist();
      window.dispatchEvent(new CustomEvent('cv-cal-pick', { detail: { from: this.from, to: this.to } }));
      if (this.from && this.to) {
        setTimeout(() => {
          const f = document.getElementById('booking-form');
          if (f) f.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 150);
      }
    },
    reset() {
      this.from = ''; this.to = '';
      this.persist();
      window.dispatchEvent(new CustomEvent('cv-cal-pick', { detail: { from: '', to: '' } }));
    },
    persist() {
      try { sessionStorage.setItem('cv_book_' + this.aptId, JSON.stringify({ from: this.from, to: this.to })); } catch(e) {}
    }
  };
}
</script>
