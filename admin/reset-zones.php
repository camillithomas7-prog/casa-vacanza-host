<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

// Definizione zone aggiornate basate sulle city effettive degli appartamenti
// kind: 'zone' (area geografica) o 'villaggio' (resort/residence)
$zoneDef = [
    ['Coral Bay',         'coral-bay',         'villaggio', '/assets/sharm/zone_naama_bay.jpg',  1, 'Resort vista mare con piscine, spa, ristoranti e spiaggia inclusa.'],
    ['Atelier Residence', 'atelier-residence', 'villaggio', '',                                   2, 'Residence tranquillo con piscina, ideale per famiglie. Ottimo rapporto qualità/prezzo.'],
    ['Sunny Lakes',       'sunny-lakes',       'villaggio', '',                                   3, 'Residence economico con piscine, vicino Naama Bay.'],
    ['Delta Sharm',       'delta-sharm',       'villaggio', '',                                   4, 'Residence centrale con servizi e prezzi medi.'],
    ['Naama Bay',         'naama-bay',         'zone',      '/assets/sharm/zone_naama_bay.jpg',  5, 'Cuore della movida di Sharm: ristoranti, bar, vita notturna e spiaggia.'],
    ['Hadaba',            'hadaba',            'zone',      '/assets/sharm/zone_hadaba.jpg',     6, 'Zona panoramica vista mare, tranquilla e residenziale.'],
    ['Sharks Bay',        'sharks-bay',        'zone',      '/assets/sharm/zone_sharks_bay.jpg', 7, 'Zona diving con baia incantevole, vicino all\'aeroporto.'],
    ['Nabq',              'nabq',              'zone',      '/assets/sharm/zone_nabq_bay.jpg',   8, 'Zona tranquilla lontano dal centro, prezzi accessibili.'],
    ['Old Market',        'old-market',        'zone',      '/assets/sharm/zone_old_market.jpg', 9, 'Quartiere autentico egiziano con bazaar, ristoranti tipici e cultura locale.'],
    ['Montaza',           'montaza',           'zone',      '',                                  10, 'Zona ville esclusive con spiagge private e laguna.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    csrfCheck($_POST['csrf'] ?? null);
    // Garantisco tabella + colonna description
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS zones (
          id VARCHAR(32) PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL,
          kind VARCHAR(20) NOT NULL DEFAULT 'zone', description TEXT, image VARCHAR(500),
          position INT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}

    // Cancella tutte le zone esistenti
    q('DELETE FROM zones');
    // Inserisci le nuove
    foreach ($zoneDef as [$name, $slug, $kind, $img, $pos, $desc]) {
        q('INSERT INTO zones (id, name, slug, kind, description, image, position, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
            [newId(), $name, $slug, $kind, $desc, $img, $pos]);
    }
    flash('Zone resettate: ' . count($zoneDef) . ' nuove zone create. Le città degli appartamenti devono matchare il nome (es. "Coral Bay") per essere conteggiate.');
    redirect('/admin/zone.php');
}

// Anteprima: conta gli appartamenti per ciascuna zona
$counts = [];
foreach ($zoneDef as [$name]) {
    $counts[$name] = (int)val('SELECT COUNT(*) FROM apartments WHERE city = ? AND active = 1', [$name]);
}
// Mostra anche le city presenti ma non in $zoneDef
$existingCities = rows("SELECT city, COUNT(*) AS n FROM apartments WHERE city IS NOT NULL AND city != '' GROUP BY city");
$orphanCities = [];
$definedNames = array_column($zoneDef, 0);
foreach ($existingCities as $r) {
    if (!in_array($r['city'], $definedNames, true)) $orphanCities[] = $r;
}

$title = 'Reset zone & villaggi';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div>
    <h1 class="font-serif text-3xl font-semibold">Reset Zone & villaggi</h1>
    <p class="text-ink-500 mt-1">Cancella tutte le zone esistenti e crea le 10 zone aggiornate basate sui veri appartamenti importati da PM Servizi.</p>
  </div>

  <div class="card p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-sm">
    <p><b>⚠ Attenzione:</b> questa operazione <b>cancella TUTTE le zone esistenti</b> e le ricrea da zero. Gli appartamenti collegati per nome non si rompono (il match è su <code>city</code> testuale).</p>
  </div>

  <div class="card p-4">
    <h3 class="font-display font-bold mb-3">Zone che verranno create (<?= count($zoneDef) ?>)</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
      <?php foreach ($zoneDef as [$name, $slug, $kind, $img, $pos, $desc]):
        $c = $counts[$name];
      ?>
        <div class="p-3 rounded-lg border border-ink-200 dark:border-ink-700/60 bg-white dark:bg-ink-900">
          <div class="flex items-center justify-between gap-2 mb-1">
            <b class="text-sm"><?= e($name) ?></b>
            <span class="text-[10px] uppercase tracking-wider <?= $kind === 'villaggio' ? 'text-amber-700 bg-amber-100' : 'text-sky-700 bg-sky-100' ?> px-1.5 py-0.5 rounded"><?= e($kind) ?></span>
          </div>
          <div class="text-xs text-ink-500"><?= e($desc) ?></div>
          <div class="text-xs mt-1 <?= $c > 0 ? 'text-emerald-600 font-bold' : 'text-ink-400' ?>"><?= $c ?> appartamenti</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($orphanCities): ?>
  <div class="card p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200">
    <h3 class="font-display font-bold mb-2 text-sm">⚠ Città degli appartamenti non in elenco</h3>
    <p class="text-xs text-ink-600 mb-2">Queste città sono presenti negli appartamenti ma non hanno una zona corrispondente. Considera di rinominare l'appartamento per matchare una zona esistente.</p>
    <ul class="text-xs space-y-0.5">
      <?php foreach ($orphanCities as $oc): ?>
        <li>· <b><?= e($oc['city']) ?></b> (<?= (int)$oc['n'] ?> appartament<?= (int)$oc['n'] === 1 ? 'o' : 'i' ?>)</li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form method="post" class="flex gap-2">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="confirm" value="yes">
    <button type="submit" class="btn-danger" onclick="return confirm('Eliminare tutte le zone esistenti e ricrearle?')"><i data-lucide="refresh-cw" class="size-[16px]"></i> Cancella e ricrea le zone</button>
    <a href="/admin/zone.php" class="btn-outline">Annulla</a>
  </form>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
