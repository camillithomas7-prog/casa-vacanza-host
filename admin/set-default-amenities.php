<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

// Servizi di default per Coral Bay (dentro il resort, spiaggia interna + navetta)
$amenitiesCoralBay = [
    'WiFi a pagamento',
    'Cucina',
    'Lavatrice',
    'Lavastoviglie',
    'Frigorifero',
    'Aria condizionata',
    'Asciugamani',
    'Teli mare',
    'Phon',
    'Biancheria letti',
    'TV',
    'Piscina inclusa',
    'Spiaggia inclusa',
    'Navetta interna al resort gratuita',
];

// Servizi di default per tutti gli altri (Sunny Lakes, Atelier, Naama, Delta, ecc.)
$amenitiesAltro = [
    'WiFi a pagamento',
    'Cucina',
    'Lavatrice',
    'Lavastoviglie',
    'Frigorifero',
    'Aria condizionata',
    'Asciugamani',
    'Teli mare',
    'Phon',
    'Biancheria letti',
    'TV',
    'Piscina inclusa',
    'Spiaggia a pagamento (circa 800 m)',
];

function isCoralBay(string $city): bool {
    return stripos($city, 'coral bay') !== false || stripos($city, 'domina') !== false;
}

$apartments = rows("SELECT id, name, city, amenities FROM apartments ORDER BY city ASC, name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    csrfCheck($_POST['csrf'] ?? null);
    $mode = $_POST['mode'] ?? 'replace'; // replace | merge
    $coralCount = 0; $altroCount = 0;
    foreach ($apartments as $a) {
        $isCB = isCoralBay($a['city'] ?? '');
        $newSet = $isCB ? $amenitiesCoralBay : $amenitiesAltro;
        if ($mode === 'merge') {
            $existing = json_decode($a['amenities'] ?: '[]', true) ?: [];
            // dedup case-insensitive
            $seen = [];
            $combined = [];
            foreach (array_merge($existing, $newSet) as $item) {
                $k = mb_strtolower(trim($item));
                if ($k === '' || isset($seen[$k])) continue;
                $seen[$k] = true;
                $combined[] = trim($item);
            }
            $final = $combined;
        } else {
            $final = $newSet;
        }
        q('UPDATE apartments SET amenities = ? WHERE id = ?', [json_encode($final, JSON_UNESCAPED_UNICODE), $a['id']]);
        if ($isCB) $coralCount++; else $altroCount++;
    }
    flash("Servizi aggiornati: $coralCount appartamenti Coral Bay + $altroCount altre zone");
    redirect('/admin/appartamenti.php');
}

$coralApts = array_filter($apartments, fn($a) => isCoralBay($a['city'] ?? ''));
$altroApts = array_filter($apartments, fn($a) => !isCoralBay($a['city'] ?? ''));

$title = 'Imposta servizi di default';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div>
    <h1 class="font-serif text-3xl font-semibold">Imposta servizi di default</h1>
    <p class="text-ink-500 mt-1">Applica un set predefinito di servizi a tutti gli appartamenti, distinguendo Coral Bay dalle altre zone.</p>
  </div>

  <div class="grid lg:grid-cols-2 gap-4">
    <div class="card p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="h-9 w-9 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center"><i data-lucide="palmtree" class="size-[18px]"></i></span>
        <div>
          <h3 class="font-display font-bold">Coral Bay</h3>
          <p class="text-xs text-ink-500"><?= count($coralApts) ?> appartament<?= count($coralApts) === 1 ? 'o' : 'i' ?></p>
        </div>
      </div>
      <ul class="text-sm space-y-1">
        <?php foreach ($amenitiesCoralBay as $a): ?>
          <li class="flex items-center gap-2"><i data-lucide="check" class="size-[14px] text-emerald-600 shrink-0"></i> <?= e($a) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="card p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="h-9 w-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center"><i data-lucide="map-pin" class="size-[18px]"></i></span>
        <div>
          <h3 class="font-display font-bold">Altre zone</h3>
          <p class="text-xs text-ink-500"><?= count($altroApts) ?> appartament<?= count($altroApts) === 1 ? 'o' : 'i' ?> (Sunny Lakes, Atelier Residence, Naama Bay, Delta Sharm, Sharks Bay, Nabq, ecc.)</p>
        </div>
      </div>
      <ul class="text-sm space-y-1">
        <?php foreach ($amenitiesAltro as $a): ?>
          <li class="flex items-center gap-2"><i data-lucide="check" class="size-[14px] text-emerald-600 shrink-0"></i> <?= e($a) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <form method="post" class="card p-5 space-y-4">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="confirm" value="yes">
    <div>
      <span class="label">Modalità</span>
      <div class="grid sm:grid-cols-2 gap-2 mt-1">
        <label class="card p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-500/10 transition">
          <input type="radio" name="mode" value="replace" checked class="mr-2">
          <b>Sostituisci</b>
          <div class="text-xs text-ink-500 mt-1">Rimuove i servizi attuali e applica solo quelli del template. Più pulito.</div>
        </label>
        <label class="card p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-500/10 transition">
          <input type="radio" name="mode" value="merge" class="mr-2">
          <b>Unisci</b>
          <div class="text-xs text-ink-500 mt-1">Aggiunge i servizi del template a quelli esistenti, senza duplicati.</div>
        </label>
      </div>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="btn-primary"><i data-lucide="check-circle" class="size-[16px]"></i> Applica a tutti i <?= count($apartments) ?> appartamenti</button>
      <a href="/admin/appartamenti.php" class="btn-outline">Annulla</a>
    </div>
  </form>

  <?php if ($coralApts): ?>
    <div class="card p-4">
      <h4 class="font-display font-bold text-sm mb-2">Appartamenti a Coral Bay che verranno aggiornati</h4>
      <ul class="text-xs text-ink-600 space-y-0.5">
        <?php foreach ($coralApts as $a): ?>
          <li>· <?= e($a['name']) ?> <span class="text-ink-400">(<?= e($a['city']) ?>)</span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
