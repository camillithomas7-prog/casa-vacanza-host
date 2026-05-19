<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$apartments = rows('SELECT id, name, city, weekly_price, biweekly_price, triweekly_price, monthly_price FROM apartments ORDER BY city ASC, name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    csrfCheck($_POST['csrf'] ?? null);
    $bw = max(0, min(50, (float)($_POST['discount_bw'] ?? 5)));
    $tw = max(0, min(50, (float)($_POST['discount_tw'] ?? 10)));
    $m  = max(0, min(50, (float)($_POST['discount_m']  ?? 15)));
    $mode = $_POST['mode'] ?? 'only_empty';
    $updated = 0; $skipped = 0;
    foreach ($apartments as $a) {
        $w = (float)$a['weekly_price'];
        if ($w <= 0) { $skipped++; continue; }
        $vals = [
            'biweekly_price'  => round($w * 2     * (1 - $bw/100), 2),
            'triweekly_price' => round($w * 3     * (1 - $tw/100), 2),
            'monthly_price'   => round($w * (30/7)* (1 - $m /100), 2),
        ];
        if ($mode === 'only_empty') {
            // Aggiorna solo i campi vuoti/null
            $set = []; $params = [];
            foreach ($vals as $k => $v) {
                if (empty($a[$k])) { $set[] = "$k = ?"; $params[] = $v; }
            }
            if (!$set) { $skipped++; continue; }
            $params[] = $a['id'];
            q('UPDATE apartments SET ' . implode(', ', $set) . ' WHERE id = ?', $params);
        } else {
            // overwrite all
            q('UPDATE apartments SET biweekly_price = ?, triweekly_price = ?, monthly_price = ? WHERE id = ?',
                [$vals['biweekly_price'], $vals['triweekly_price'], $vals['monthly_price'], $a['id']]);
        }
        $updated++;
    }
    flash("Sconti applicati a $updated appartamenti" . ($skipped ? " · $skipped saltat" . ($skipped === 1 ? 'o' : 'i') : '') . ". I clienti ora vedono il risparmio quando selezionano 2/3 sett. o 1 mese.");
    redirect('/admin/appartamenti.php');
}

$title = 'Applica sconti su pacchetti';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div>
    <h1 class="font-serif text-3xl font-semibold">Sconti su pacchetti settimanali</h1>
    <p class="text-ink-500 mt-1">Imposta in batch i prezzi di 2 settimane / 3 settimane / 1 mese come sconto % rispetto al settimanale.</p>
  </div>

  <div class="card p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-sm">
    <p><b>⚠ Perché serve:</b> se lasci vuoti i prezzi 2/3/mese il sistema mostra il pieno proporzionale, e il cliente <b>non vede risparmio</b>. Con sconti suggeriti il cliente vede subito un box verde "Stai risparmiando €X" che incentiva a prenotare più giorni.</p>
  </div>

  <form method="post" class="card p-5 space-y-4">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="confirm" value="yes">

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <label class="block">
        <span class="label">Sconto 2 settimane (%)</span>
        <div class="relative">
          <input class="input pr-10" type="number" step="0.5" min="0" max="50" name="discount_bw" value="5">
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-400">%</span>
        </div>
      </label>
      <label class="block">
        <span class="label">Sconto 3 settimane (%)</span>
        <div class="relative">
          <input class="input pr-10" type="number" step="0.5" min="0" max="50" name="discount_tw" value="10">
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-400">%</span>
        </div>
      </label>
      <label class="block">
        <span class="label">Sconto 1 mese (%)</span>
        <div class="relative">
          <input class="input pr-10" type="number" step="0.5" min="0" max="50" name="discount_m" value="15">
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-400">%</span>
        </div>
      </label>
    </div>

    <div>
      <span class="label">Modalità</span>
      <div class="grid sm:grid-cols-2 gap-2 mt-1">
        <label class="card p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-500/10 transition">
          <input type="radio" name="mode" value="only_empty" checked class="mr-2">
          <b>Solo dove vuoto</b>
          <div class="text-xs text-ink-500 mt-1">Non sovrascrive i prezzi pacchetto già impostati a mano. Consigliato.</div>
        </label>
        <label class="card p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-500/10 transition">
          <input type="radio" name="mode" value="overwrite" class="mr-2">
          <b>Sovrascrivi tutto</b>
          <div class="text-xs text-ink-500 mt-1">Ricalcola da zero per tutti i <?= count($apartments) ?> appartamenti.</div>
        </label>
      </div>
    </div>

    <div class="flex gap-2">
      <button type="submit" class="btn-primary"><i data-lucide="zap" class="size-[16px]"></i> Applica sconti ai <?= count($apartments) ?> appartamenti</button>
      <a href="/admin/appartamenti.php" class="btn-outline">Annulla</a>
    </div>
  </form>

  <div class="card p-4">
    <h4 class="font-display font-bold text-sm mb-2">Anteprima esempio</h4>
    <p class="text-sm text-ink-600 mb-2">Se un appartamento ha settimanale <b>200 €</b> e applichi -5% / -10% / -15%:</p>
    <ul class="text-xs space-y-1 text-ink-600">
      <li>· <b>2 settimane</b>: era 400 € → diventa <b class="text-emerald-700">380 €</b> (risparmio 20 €)</li>
      <li>· <b>3 settimane</b>: era 600 € → diventa <b class="text-emerald-700">540 €</b> (risparmio 60 €)</li>
      <li>· <b>1 mese</b>: era 857 € → diventa <b class="text-emerald-700">729 €</b> (risparmio 128 €)</li>
    </ul>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
