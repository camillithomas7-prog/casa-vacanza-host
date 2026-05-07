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
  $map = ['pending'=>['warning','In attesa'],'confirmed'=>['info','Confermata'],'checked_in'=>['success','Check-in'],'completed'=>['soft','Completata'],'cancelled'=>['danger','Cancellata']];
  $x = $map[$s] ?? ['soft', $s];
  return '<span class="badge-' . $x[0] . '">' . e($x[1]) . '</span>';
}

$title = 'Prenotazioni';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-serif text-3xl font-semibold tracking-tight">Prenotazioni</h1>
      <p class="text-ink-500 mt-1">Tutte le prenotazioni dirette e dai canali esterni · <?= count($items) ?> risultati</p>
    </div>
    <a href="/admin/prenotazione-nuova.php" class="btn-primary"><i data-lucide="plus" class="size-[18px]"></i> Nuova prenotazione</a>
  </div>

  <div class="card p-2 flex items-center gap-1 overflow-x-auto">
    <?php foreach ($statuses as $s => $l): $active = ($_GET['status'] ?? '') === $s; ?>
      <a href="<?= $s ? '?status=' . e($s) : '/admin/prenotazioni.php' ?>" class="text-sm px-3.5 py-2 rounded-lg whitespace-nowrap font-medium transition <?= $active ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md' : 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800' ?>"><?= e($l) ?></a>
    <?php endforeach; ?>
  </div>

  <!-- Tabella desktop -->
  <div class="hidden md:block card p-0 overflow-x-auto">
    <table class="table-base">
      <thead><tr><th>Codice</th><th>Cliente</th><th>Appartamento</th><th>Date</th><th class="text-right">Totale</th><th class="text-right">Saldo</th><th>Stato</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($items as $b):
          $due = (float)$b['total'] - (float)$b['paid'];
          $parts = explode(' ', trim($b['customer_name']));
          $initials = mb_strtoupper(mb_substr($parts[0] ?? '·', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
        ?>
          <tr>
            <td class="font-mono text-xs text-ink-500"><?= e($b['code']) ?></td>
            <td>
              <div class="flex items-center gap-2.5">
                <span class="h-8 w-8 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center font-semibold text-xs shrink-0"><?= e($initials) ?></span>
                <div class="min-w-0">
                  <div class="font-medium truncate"><?= e($b['customer_name']) ?></div>
                  <div class="text-xs text-ink-500 truncate"><?= e($b['customer_email'] ?: $b['customer_phone']) ?></div>
                </div>
              </div>
            </td>
            <td class="text-sm"><?= e($b['apartment_name']) ?></td>
            <td class="text-sm tabular-nums"><?= fmtDateShort($b['check_in']) ?> → <?= fmtDateShort($b['check_out']) ?></td>
            <td class="text-right font-semibold tabular-nums"><?= fmtMoney((float)$b['total']) ?></td>
            <td class="text-right tabular-nums"><?= $due > 0 ? '<span class="text-amber-600 font-semibold">' . fmtMoney($due) . '</span>' : '<span class="text-emerald-600 inline-flex items-center gap-1"><i data-lucide=\'check\' class=\'size-[12px]\'></i> Saldata</span>' ?></td>
            <td><?= statusBadge($b['status']) ?></td>
            <td><a href="/admin/prenotazione.php?id=<?= e($b['id']) ?>" class="btn-ghost text-sm py-1.5 px-2.5">Apri <i data-lucide="chevron-right" class="size-[14px]"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$items): ?>
      <div class="p-14 text-center">
        <div class="h-16 w-16 mx-auto rounded-2xl bg-ink-100 dark:bg-ink-800 text-ink-400 flex items-center justify-center mb-4"><i data-lucide="bookmark-x" class="size-[28px]"></i></div>
        <div class="font-display font-bold text-lg">Nessuna prenotazione</div>
        <div class="text-ink-500 mt-1">Le richieste dal sito e quelle inserite manualmente compaiono qui.</div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Cards mobile -->
  <div class="md:hidden space-y-3">
    <?php foreach ($items as $b):
      $due = (float)$b['total'] - (float)$b['paid'];
      $parts = explode(' ', trim($b['customer_name']));
      $initials = mb_strtoupper(mb_substr($parts[0] ?? '·', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    ?>
      <a href="/admin/prenotazione.php?id=<?= e($b['id']) ?>" class="card p-4 block active:scale-[0.99] transition">
        <div class="flex items-start gap-3">
          <span class="h-10 w-10 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center font-semibold text-sm shrink-0"><?= e($initials) ?></span>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <div class="font-semibold truncate"><?= e($b['customer_name']) ?></div>
              <?= statusBadge($b['status']) ?>
            </div>
            <div class="text-xs text-ink-500 font-mono mt-0.5"><?= e($b['code']) ?></div>
            <div class="text-sm mt-2 text-ink-700 dark:text-ink-300 truncate"><i data-lucide="building-2" class="size-[12px] inline -mt-0.5"></i> <?= e($b['apartment_name']) ?></div>
            <div class="text-sm text-ink-700 dark:text-ink-300 mt-1 tabular-nums"><i data-lucide="calendar" class="size-[12px] inline -mt-0.5"></i> <?= fmtDateShort($b['check_in']) ?> → <?= fmtDateShort($b['check_out']) ?></div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-ink-100 dark:border-ink-800/80">
              <div>
                <div class="text-[10px] uppercase tracking-wider text-ink-500 font-semibold">Totale</div>
                <div class="font-display font-bold tabular-nums"><?= fmtMoney((float)$b['total']) ?></div>
              </div>
              <div class="text-right">
                <div class="text-[10px] uppercase tracking-wider text-ink-500 font-semibold">Saldo</div>
                <div class="font-semibold tabular-nums <?= $due > 0 ? 'text-amber-600' : 'text-emerald-600' ?>"><?= $due > 0 ? fmtMoney($due) : 'OK' ?></div>
              </div>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (!$items): ?>
      <div class="card p-10 text-center">
        <div class="h-14 w-14 mx-auto rounded-2xl bg-ink-100 dark:bg-ink-800 text-ink-400 flex items-center justify-center mb-3"><i data-lucide="bookmark-x" class="size-[24px]"></i></div>
        <div class="font-display font-bold">Nessuna prenotazione</div>
        <div class="text-ink-500 text-sm mt-1">Le richieste compaiono qui.</div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
