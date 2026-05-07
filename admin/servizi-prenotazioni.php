<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/services.php';
requireAdmin();

$where = ['1=1']; $params = [];
if (!empty($_GET['status'])) { $where[] = 'b.status = ?'; $params[] = $_GET['status']; }
$items = rows("SELECT b.*, s.name AS service_name, s.type AS service_type, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
               FROM service_bookings b JOIN services s ON b.service_id = s.id JOIN customers c ON b.customer_id = c.id
               WHERE " . implode(' AND ', $where) . " ORDER BY b.created_at DESC", $params);

$statuses = ['' => 'Tutte', 'pending' => 'In attesa', 'confirmed' => 'Confermate', 'completed' => 'Completate', 'cancelled' => 'Cancellate'];
function svBadge($s) {
  $map = ['pending'=>['warning','In attesa'],'confirmed'=>['info','Confermata'],'completed'=>['soft','Completata'],'cancelled'=>['danger','Cancellata']];
  $x = $map[$s] ?? ['soft', $s];
  return '<span class="badge-' . $x[0] . '">' . e($x[1]) . '</span>';
}

$title = 'Prenotazioni servizi';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-serif text-3xl font-semibold tracking-tight">Prenotazioni servizi</h1>
      <p class="text-ink-500 mt-1">Veicoli, escursioni, transfer · <?= count($items) ?> risultati</p>
    </div>
    <a href="/admin/servizi.php" class="btn-outline">Gestisci servizi</a>
  </div>

  <div class="card p-2 flex items-center gap-1 overflow-x-auto">
    <?php foreach ($statuses as $s => $l): $active = ($_GET['status'] ?? '') === $s; ?>
      <a href="<?= $s ? '?status=' . e($s) : '/admin/servizi-prenotazioni.php' ?>" class="text-sm px-3.5 py-2 rounded-lg whitespace-nowrap font-medium transition <?= $active ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md' : 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800' ?>"><?= e($l) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="card p-0 overflow-x-auto">
    <table class="table-base">
      <thead><tr><th>Codice</th><th>Cliente</th><th>Servizio</th><th>Data</th><th>Persone</th><th class="text-right">Totale</th><th>Stato</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($items as $b):
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
            <td>
              <div class="flex items-center gap-2 text-sm">
                <i data-lucide="<?= e(serviceTypeIcon($b['service_type'])) ?>" class="size-[14px] text-brand-500"></i> <?= e($b['service_name']) ?>
              </div>
            </td>
            <td class="text-sm tabular-nums"><?= fmtDateShort($b['start_date']) ?><?= $b['end_date'] && $b['end_date'] !== $b['start_date'] ? ' → ' . fmtDateShort($b['end_date']) : '' ?></td>
            <td class="text-sm tabular-nums"><?= (int)$b['participants'] ?></td>
            <td class="text-right font-semibold tabular-nums"><?= fmtMoney((float)$b['total']) ?></td>
            <td><?= svBadge($b['status']) ?></td>
            <td><a href="/admin/servizi-prenotazione.php?id=<?= e($b['id']) ?>" class="btn-ghost text-sm py-1.5 px-2.5">Apri <i data-lucide="chevron-right" class="size-[14px]"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$items): ?>
      <div class="p-14 text-center">
        <div class="h-16 w-16 mx-auto rounded-2xl bg-ink-100 dark:bg-ink-800 text-ink-400 flex items-center justify-center mb-4"><i data-lucide="package-x" class="size-[28px]"></i></div>
        <div class="font-display font-bold text-lg">Nessuna prenotazione servizi</div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
