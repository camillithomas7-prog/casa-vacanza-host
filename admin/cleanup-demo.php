<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

// Nomi degli appartamenti demo da eliminare
$demoNames = [
    'Appartamento 34026',
    'Naama Bay Sea View',
    'Nabq Bay Family',
    'Sharks Bay Diving Suite',
    'Hadaba Pool Residence',
];

if (($_POST['confirm'] ?? '') === 'yes') {
    csrfCheck($_POST['csrf'] ?? null);
    $deleted = 0; $bookDel = 0;
    foreach ($demoNames as $n) {
        $apt = row('SELECT id, name FROM apartments WHERE name = ?', [$n]);
        if (!$apt) continue;
        try {
            db()->beginTransaction();
            $bk = (int)val('SELECT COUNT(*) FROM bookings WHERE apartment_id = ?', [$apt['id']]);
            if ($bk > 0) {
                q('DELETE FROM bookings WHERE apartment_id = ?', [$apt['id']]);
                $bookDel += $bk;
            }
            q('DELETE FROM apartments WHERE id = ?', [$apt['id']]);
            db()->commit();
            logActivity('delete', 'apartment', $apt['id'], $apt['name']);
            $deleted++;
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
        }
    }
    flash("$deleted appartamenti demo eliminati ($bookDel prenotazioni rimosse)");
    redirect('/admin/appartamenti.php');
}

$title = 'Pulizia appartamenti demo';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<h1 class="font-display text-2xl font-bold mb-3">Pulizia appartamenti demo</h1>
<p class="text-ink-600 mb-4">Verranno eliminati questi appartamenti e <b>tutte le prenotazioni demo collegate</b>:</p>
<ul class="list-disc list-inside mb-4 text-sm">
<?php foreach ($demoNames as $n):
    $apt = row('SELECT id, name FROM apartments WHERE name = ?', [$n]);
    if (!$apt) { echo '<li class="text-ink-400">' . htmlspecialchars($n) . ' — già eliminato</li>'; continue; }
    $bk = (int)val('SELECT COUNT(*) FROM bookings WHERE apartment_id = ?', [$apt['id']]);
    echo '<li><b>' . htmlspecialchars($n) . '</b> (' . $bk . ' prenotazion' . ($bk === 1 ? 'e' : 'i') . ')</li>';
endforeach; ?>
</ul>
<form method="post" class="flex gap-2">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
  <input type="hidden" name="confirm" value="yes">
  <button type="submit" class="btn-danger" onclick="return confirm('Confermi eliminazione demo?')">⚠ Elimina tutti</button>
  <a href="/admin/appartamenti.php" class="btn-outline">Annulla</a>
</form>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
