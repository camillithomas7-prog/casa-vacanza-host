<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/services.php';
requireAdmin();

$id = $_GET['id'] ?? '';
$b = row('SELECT b.*, s.name AS service_name, s.type AS service_type, s.cover_image AS service_image, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.country AS customer_country FROM service_bookings b JOIN services s ON b.service_id = s.id JOIN customers c ON b.customer_id = c.id WHERE b.id = ?', [$id]);
if (!$b) { flash('Non trovato', 'error'); redirect('/admin/servizi-prenotazioni.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'status') {
        q('UPDATE service_bookings SET status = ? WHERE id = ?', [$_POST['status'], $b['id']]);
        flash('Stato aggiornato');
    }
    if ($action === 'delete') {
        q('DELETE FROM service_bookings WHERE id = ?', [$b['id']]);
        flash('Prenotazione eliminata');
        redirect('/admin/servizi-prenotazioni.php');
    }
    if ($action === 'pay') {
        q('UPDATE service_bookings SET paid = ? WHERE id = ?', [(float)$_POST['paid'], $b['id']]);
        flash('Pagamento aggiornato');
    }
    redirect('/admin/servizi-prenotazione.php?id=' . $b['id']);
}

function svDetailBadge($s) {
  $map = ['pending'=>['warning','In attesa'],'confirmed'=>['info','Confermata'],'completed'=>['soft','Completata'],'cancelled'=>['danger','Cancellata']];
  $x = $map[$s] ?? ['soft', $s];
  return '<span class="badge-' . $x[0] . '">' . e($x[1]) . '</span>';
}
$due = (float)$b['total'] - (float)$b['paid'];

$title = $b['service_name'] . ' · ' . $b['code'];
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-serif text-2xl font-semibold tracking-tight"><?= e($b['customer_name']) ?></h1>
      <div class="text-sm text-ink-500 mt-1 flex items-center gap-2 flex-wrap">
        <span class="font-mono"><?= e($b['code']) ?></span> · <?= svDetailBadge($b['status']) ?> · <span><?= e($b['service_name']) ?></span>
      </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <form method="post" class="contents">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="status">
        <select name="status" onchange="this.form.submit()" class="input">
          <?php foreach (['pending'=>'In attesa','confirmed'=>'Conferma','completed'=>'Completa','cancelled'=>'Cancella'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $b['status'] === $v ? 'selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <form method="post" onsubmit="return confirm('Eliminare?')">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="delete">
        <button class="btn-danger"><i data-lucide="trash-2" class="size-[16px]"></i> Elimina</button>
      </form>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
      <div class="card p-4 sm:p-5">
        <h3 class="font-display font-bold mb-3">Servizio</h3>
        <div class="flex items-center gap-4 p-3 rounded-xl bg-ink-50 dark:bg-ink-900/40">
          <?php if ($b['service_image']): ?><img src="<?= e($b['service_image']) ?>" class="h-16 w-24 object-cover rounded-lg"><?php endif; ?>
          <div>
            <div class="font-medium"><?= e($b['service_name']) ?></div>
            <div class="text-xs text-ink-500"><?= e(serviceTypeLabel($b['service_type'])) ?></div>
          </div>
        </div>
      </div>

      <div class="card p-4 sm:p-5">
        <h3 class="font-display font-bold mb-3">Dettagli prenotazione</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
          <div><div class="text-xs text-ink-500">Data inizio</div><div><?= fmtDate($b['start_date']) ?></div></div>
          <?php if ($b['end_date'] && $b['end_date'] !== $b['start_date']): ?><div><div class="text-xs text-ink-500">Data fine</div><div><?= fmtDate($b['end_date']) ?></div></div><?php endif; ?>
          <?php if ($b['pickup_time']): ?><div><div class="text-xs text-ink-500">Ora pickup</div><div><?= e($b['pickup_time']) ?></div></div><?php endif; ?>
          <div><div class="text-xs text-ink-500">Persone</div><div><?= (int)$b['participants'] ?></div></div>
          <?php if ($b['flight_number']): ?><div><div class="text-xs text-ink-500">Volo</div><div class="font-mono"><?= e($b['flight_number']) ?></div></div><?php endif; ?>
          <?php if ($b['pickup_location']): ?><div class="col-span-full"><div class="text-xs text-ink-500">Pickup</div><div><?= e($b['pickup_location']) ?></div></div><?php endif; ?>
          <?php if ($b['dropoff_location']): ?><div class="col-span-full"><div class="text-xs text-ink-500">Dropoff</div><div><?= e($b['dropoff_location']) ?></div></div><?php endif; ?>
        </div>
        <?php if ($b['notes']): ?><div class="text-sm mt-4 p-3 rounded-xl bg-ink-50 dark:bg-ink-900"><?= e($b['notes']) ?></div><?php endif; ?>
      </div>

      <div class="card p-4 sm:p-5">
        <h3 class="font-display font-bold mb-3">Cliente</h3>
        <div class="grid sm:grid-cols-2 gap-3 text-sm">
          <div><div class="text-xs text-ink-500">Nome</div><div><?= e($b['customer_name']) ?></div></div>
          <div><div class="text-xs text-ink-500">Email</div><div><?= e($b['customer_email'] ?: '—') ?></div></div>
          <div><div class="text-xs text-ink-500">Telefono</div><div><?= e($b['customer_phone'] ?: '—') ?></div></div>
        </div>
        <?php if ($b['customer_phone']): ?>
          <div class="flex gap-2 mt-4">
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $b['customer_phone']) ?>" target="_blank" class="btn-primary text-sm"><i data-lucide="message-circle" class="size-[14px]"></i> WhatsApp</a>
            <?php if ($b['customer_email']): ?><a href="mailto:<?= e($b['customer_email']) ?>" class="btn-secondary text-sm"><i data-lucide="mail" class="size-[14px]"></i> Email</a><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-4 sm:p-5">
        <h3 class="font-display font-bold mb-3">Importi</h3>
        <div class="space-y-1.5">
          <div class="flex justify-between py-1.5 text-sm border-b border-ink-100 dark:border-ink-800"><span class="text-ink-500">Base</span><span class="tabular-nums"><?= fmtMoney((float)$b['base_price']) ?></span></div>
          <?php if ($b['discount'] > 0): ?><div class="flex justify-between py-1.5 text-sm border-b border-ink-100 dark:border-ink-800 text-emerald-600"><span>Sconto</span><span class="tabular-nums">-<?= fmtMoney((float)$b['discount']) ?></span></div><?php endif; ?>
          <div class="flex justify-between py-1.5 text-base border-b border-ink-100 dark:border-ink-800 font-display font-bold"><span>Totale</span><span class="tabular-nums"><?= fmtMoney((float)$b['total']) ?></span></div>
          <div class="flex justify-between py-1.5 text-sm"><span class="text-ink-500">Incassato</span><span class="tabular-nums"><?= fmtMoney((float)$b['paid']) ?></span></div>
          <div class="flex justify-between py-2 text-sm"><span class="text-ink-500">Saldo</span><span class="<?= $due > 0 ? 'text-amber-600 font-semibold' : 'text-emerald-600' ?> tabular-nums"><?= fmtMoney(max(0, $due)) ?></span></div>
        </div>
        <form method="post" class="mt-3 flex gap-2">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="pay">
          <input type="number" step="0.01" name="paid" value="<?= e((string)$b['paid']) ?>" class="input flex-1 text-sm">
          <button class="btn-outline text-sm">Aggiorna</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
