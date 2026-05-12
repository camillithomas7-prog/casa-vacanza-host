<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $points = [];
        for ($i = 0; $i < 3; $i++) {
            $px = isset($_POST['px'][$i]) ? (float)$_POST['px'][$i] : null;
            $py = isset($_POST['py'][$i]) ? (float)$_POST['py'][$i] : null;
            $lat = isset($_POST['lat'][$i]) && $_POST['lat'][$i] !== '' ? (float)$_POST['lat'][$i] : null;
            $lng = isset($_POST['lng'][$i]) && $_POST['lng'][$i] !== '' ? (float)$_POST['lng'][$i] : null;
            $label = trim($_POST['label'][$i] ?? '');
            if ($px === null || $py === null || $lat === null || $lng === null) {
                flash('Compila tutti i campi dei 3 punti di riferimento', 'error');
                redirect('/admin/mappa-calibrazione.php');
            }
            $points[] = ['px' => $px, 'py' => $py, 'lat' => $lat, 'lng' => $lng, 'label' => $label];
        }
        $json = json_encode($points);
        q('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', ['resort_calibration', $json]);
        logActivity('update', 'setting', 'resort_calibration', 'Mappa calibrata');
        flash('Calibrazione salvata. La signora delle pulizie ora vede la sua posizione sulla mappa.');
        redirect('/admin/mappa-calibrazione.php');
    }

    if ($action === 'reset') {
        q('DELETE FROM settings WHERE setting_key = ?', ['resort_calibration']);
        flash('Calibrazione rimossa');
        redirect('/admin/mappa-calibrazione.php');
    }
}

$existing = setting('resort_calibration');
$points = $existing ? (json_decode($existing, true) ?: []) : [];
// Default initial positions if not yet calibrated
$defaults = [
    ['px' => 0.18, 'py' => 0.30, 'lat' => '', 'lng' => '', 'label' => 'Punto 1 — es. Reception (01)'],
    ['px' => 0.50, 'py' => 0.82, 'lat' => '', 'lng' => '', 'label' => 'Punto 2 — es. Floating Centrale'],
    ['px' => 0.82, 'py' => 0.60, 'lat' => '', 'lng' => '', 'label' => 'Punto 3 — es. Lago Salato'],
];
for ($i = 0; $i < 3; $i++) {
    if (!isset($points[$i])) $points[$i] = $defaults[$i];
}

$title = 'Calibrazione mappa resort';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold flex items-center gap-2"><i data-lucide="locate-fixed" class="size-[24px] text-sky-600"></i> Calibrazione mappa resort</h1>
      <p class="text-ink-500 text-sm mt-1">Imposta 3 punti noti sulla mappa con le loro coordinate GPS reali — così la signora delle pulizie vedrà la sua posizione live mentre si muove nel resort.</p>
    </div>
    <a href="/admin/pulizie.php" class="btn-ghost text-sm"><i data-lucide="arrow-left" class="size-[16px]"></i> Torna a Pulizie</a>
  </div>

  <div class="card p-4 sm:p-5 bg-amber-50/60 border-amber-200 dark:bg-amber-500/5 dark:border-amber-500/30">
    <div class="flex items-start gap-3">
      <i data-lucide="info" class="size-[20px] text-amber-700 shrink-0 mt-0.5"></i>
      <div class="text-sm text-amber-900 dark:text-amber-200">
        <strong>Come si fa</strong> (5 minuti, una volta sola):
        <ol class="list-decimal list-inside mt-2 space-y-1">
          <li>Apri <a href="https://www.google.com/maps/place/Domina+Coral+Bay/@27.8666,34.3221,16z" target="_blank" class="underline font-semibold">Domina Coral Bay su Google Maps</a> (vista satellitare).</li>
          <li>Identifica 3 punti riconoscibili sia sulla mappa qui sotto che su Google Maps (es. ingresso 01, Floating Centrale, Lago Salato).</li>
          <li>Per ogni punto: trascina il marker colorato sulla posizione corretta della mappa illustrata, poi su Google Maps fai <strong>tasto destro</strong> sul punto reale → copia le coordinate (saranno tipo <code class="bg-amber-100 px-1 rounded">27.8666, 34.3221</code>).</li>
          <li>Incolla latitudine e longitudine nei campi qui sotto.</li>
          <li>Più i 3 punti sono distanti tra loro (e formano un triangolo, non una linea), più la calibrazione sarà precisa.</li>
        </ol>
      </div>
    </div>
  </div>

  <form method="post" class="space-y-5">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="save">

    <div class="card p-3 sm:p-4">
      <div class="relative rounded-2xl overflow-hidden border-2 border-sky-200 dark:border-sky-500/30 bg-amber-50 dark:bg-ink-900 select-none" style="aspect-ratio: 2.05 / 1;" id="map-wrap">
        <img src="/assets/resort-map.jpg?v=5" alt="Mappa resort" id="cal-map" class="absolute inset-0 w-full h-full object-contain pointer-events-none" draggable="false">
        <?php
          $colors = ['#ef4444', '#3b82f6', '#10b981'];
          $letters = ['A', 'B', 'C'];
          foreach ($points as $i => $p):
            $px = (float)$p['px']; $py = (float)$p['py'];
        ?>
          <div class="cal-marker absolute cursor-grab active:cursor-grabbing" data-idx="<?= $i ?>"
               style="left: <?= round($px*100,3) ?>%; top: <?= round($py*100,3) ?>%; transform: translate(-50%, -100%); z-index: <?= 10 + $i ?>;">
            <div class="relative" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.4));">
              <svg width="44" height="56" viewBox="0 0 44 56">
                <path d="M22 0 C 9 0, 0 10, 0 22 C 0 36, 22 56, 22 56 C 22 56, 44 36, 44 22 C 44 10, 35 0, 22 0 Z" fill="<?= $colors[$i] ?>" stroke="#fff" stroke-width="3"/>
                <circle cx="22" cy="20" r="8" fill="#fff"/>
                <text x="22" y="25" text-anchor="middle" font-family="Inter,system-ui" font-size="14" font-weight="800" fill="<?= $colors[$i] ?>"><?= $letters[$i] ?></text>
              </svg>
            </div>
            <input type="hidden" name="px[]" value="<?= e((string)$px) ?>">
            <input type="hidden" name="py[]" value="<?= e((string)$py) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <?php foreach ($points as $i => $p):
        $col = $colors[$i]; $letter = $letters[$i];
      ?>
        <div class="card p-4 sm:p-5 space-y-3" style="border-color: <?= $col ?>30;">
          <div class="flex items-center gap-2">
            <span class="h-9 w-9 rounded-xl flex items-center justify-center text-white font-bold" style="background: <?= $col ?>;"><?= $letter ?></span>
            <h3 class="font-display font-bold">Punto <?= $letter ?></h3>
          </div>
          <label class="block">
            <span class="label">Etichetta (cosa rappresenta)</span>
            <input class="input" type="text" name="label[]" value="<?= e($p['label'] ?? '') ?>" placeholder="es. Ingresso 01 / Floating / Lago Salato">
          </label>
          <div class="grid grid-cols-2 gap-2">
            <label class="block">
              <span class="label">Latitudine</span>
              <input class="input tabular-nums text-sm" type="number" step="0.0000001" name="lat[]" value="<?= e((string)$p['lat']) ?>" placeholder="27.8666" required>
            </label>
            <label class="block">
              <span class="label">Longitudine</span>
              <input class="input tabular-nums text-sm" type="number" step="0.0000001" name="lng[]" value="<?= e((string)$p['lng']) ?>" placeholder="34.3221" required>
            </label>
          </div>
          <div class="text-[11px] text-ink-500">
            Posizione sulla mappa: <span class="font-mono cal-pos" data-for="<?= $i ?>"><?= round((float)$p['px']*100,1) ?>% , <?= round((float)$p['py']*100,1) ?>%</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 sticky bottom-3 z-30 card p-3 sm:p-4 bg-white/95 dark:bg-ink-900/95 backdrop-blur">
      <div class="text-sm text-ink-500">
        <?php if ($existing): ?>
          <span class="text-emerald-600 font-semibold flex items-center gap-1.5"><i data-lucide="check-circle-2" class="size-[16px]"></i> Mappa già calibrata</span>
        <?php else: ?>
          <span class="text-amber-600 font-semibold flex items-center gap-1.5"><i data-lucide="alert-triangle" class="size-[16px]"></i> Mappa non ancora calibrata</span>
        <?php endif; ?>
      </div>
      <div class="flex flex-wrap gap-2">
        <?php if ($existing): ?>
          <button type="submit" formaction="?" formmethod="post" name="action" value="reset" onclick="return confirm('Vuoi davvero rimuovere la calibrazione? Il navigatore smetterà di funzionare.')" class="btn-ghost text-red-600 text-sm">
            <i data-lucide="trash-2" class="size-[16px]"></i> Rimuovi calibrazione
          </button>
        <?php endif; ?>
        <button type="submit" class="btn-primary"><i data-lucide="save" class="size-[18px]"></i> Salva calibrazione</button>
      </div>
    </div>
  </form>
</div>

<script>
(function(){
  const wrap = document.getElementById('map-wrap');
  const markers = document.querySelectorAll('.cal-marker');
  let dragging = null;

  function moveMarker(marker, clientX, clientY) {
    const r = wrap.getBoundingClientRect();
    let x = (clientX - r.left) / r.width;
    let y = (clientY - r.top) / r.height;
    x = Math.max(0, Math.min(1, x));
    y = Math.max(0, Math.min(1, y));
    marker.style.left = (x * 100).toFixed(3) + '%';
    marker.style.top = (y * 100).toFixed(3) + '%';
    const idx = marker.getAttribute('data-idx');
    marker.querySelectorAll('input').forEach((inp, k) => {
      if (k === 0) inp.value = x.toFixed(4);
      if (k === 1) inp.value = y.toFixed(4);
    });
    const posLabel = document.querySelector('.cal-pos[data-for="' + idx + '"]');
    if (posLabel) posLabel.textContent = (x*100).toFixed(1) + '% , ' + (y*100).toFixed(1) + '%';
  }

  markers.forEach(m => {
    function start(e) {
      dragging = m;
      e.preventDefault();
    }
    m.addEventListener('mousedown', start);
    m.addEventListener('touchstart', start, {passive: false});
  });

  document.addEventListener('mousemove', e => { if (!dragging) return; moveMarker(dragging, e.clientX, e.clientY); });
  document.addEventListener('touchmove', e => { if (!dragging) return; e.preventDefault(); const t = e.touches[0]; moveMarker(dragging, t.clientX, t.clientY); }, {passive: false});
  document.addEventListener('mouseup', () => { dragging = null; });
  document.addEventListener('touchend', () => { dragging = null; });
})();
</script>

<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
