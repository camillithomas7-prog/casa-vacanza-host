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
      <h1 class="font-display text-3xl font-bold">Calendario</h1>
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

  <div class="card p-3 sm:p-5">
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
    <div class="card p-5">
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
    <form method="post" class="card p-5 w-full max-w-md" @click.stop>
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
