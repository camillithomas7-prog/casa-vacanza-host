<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/cleaning.php';
requireAdmin();
ensureColumns();

$id = $_GET['id'] ?? null;
$apt = $id ? row('SELECT * FROM apartments WHERE id = ?', [$id]) : null;
if ($id && !$apt) { flash('Appartamento non trovato', 'error'); redirect('/admin/appartamenti.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && $apt) {
        try {
            db()->beginTransaction();
            // Elimina prenotazioni (cascade su payments/documents)
            $bk = (int)val('SELECT COUNT(*) FROM bookings WHERE apartment_id = ?', [$apt['id']]);
            if ($bk > 0) {
                q('DELETE FROM bookings WHERE apartment_id = ?', [$apt['id']]);
            }
            q('DELETE FROM apartments WHERE id = ?', [$apt['id']]);
            db()->commit();
            logActivity('delete', 'apartment', $apt['id'], $apt['name']);
            flash('Appartamento eliminato' . ($bk > 0 ? " (+ $bk prenotazion" . ($bk === 1 ? 'e' : 'i') . " collegate)" : ''));
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            flash('Errore eliminazione: ' . $e->getMessage(), 'error');
            redirect('/admin/appartamento-edit.php?id=' . $apt['id']);
        }
        redirect('/admin/appartamenti.php');
    }

    if ($action === 'photo_add' && $apt) {
        $allowed = ['jpg','jpeg','png','webp','mp4','webm','mov','m4v'];
        $added = 0; $skipped = 0;
        if (!empty($_FILES['photos']['tmp_name'][0])) {
            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if (!is_uploaded_file($tmp)) continue;
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) { $skipped++; continue; }
                $isVideo = in_array($ext, ['mp4','webm','mov','m4v']);
                $fname = ($isVideo ? 'v_' : 'p_') . uniqid() . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
                $dest = __DIR__ . '/../uploads/photos/' . $fname;
                if (move_uploaded_file($tmp, $dest)) {
                    q('INSERT INTO photos (id, apartment_id, url, position) VALUES (?, ?, ?, (SELECT IFNULL(MAX(position),0)+1 FROM (SELECT position FROM photos WHERE apartment_id = ?) p))',
                        [newId(), $apt['id'], '/uploads/photos/' . $fname, $apt['id']]);
                    $added++;
                }
            }
        }
        flash($added . ' file caricat' . ($added === 1 ? 'o' : 'i') . ($skipped ? " · $skipped scartat" . ($skipped === 1 ? 'o' : 'i') . ' (formato non valido)' : ''));
        redirect('/admin/appartamento-edit.php?id=' . $apt['id']);
    }

    if ($action === 'photo_delete' && $apt) {
        q('DELETE FROM photos WHERE id = ? AND apartment_id = ?', [$_POST['photo_id'], $apt['id']]);
        redirect('/admin/appartamento-edit.php?id=' . $apt['id']);
    }

    // Upload cover image se presente
    $coverPath = $_POST['existing_cover'] ?? null;
    if (isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
        $coverPath = null;
    }
    if (!empty($_FILES['cover_image_file']['name']) && $_FILES['cover_image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $dir = __DIR__ . '/../uploads/photos';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = 'cover_' . substr(uniqid(), -8) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
            $dest = $dir . '/' . $fname;
            if (move_uploaded_file($_FILES['cover_image_file']['tmp_name'], $dest)) {
                $coverPath = '/uploads/photos/' . $fname;
            }
        }
    }

    // save
    $bedsDouble = max(0, (int)($_POST['beds_double'] ?? 0));
    $bedsSingle = max(0, (int)($_POST['beds_single'] ?? 0));
    $bedsSofa   = max(0, (int)($_POST['beds_sofa'] ?? 0));
    $bedsTotal  = $bedsDouble + $bedsSingle + $bedsSofa;
    if ($bedsTotal === 0) $bedsTotal = max(1, (int)($_POST['beds'] ?? 1));

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? '') ?: slugify($_POST['name'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? '',
        'country' => $_POST['country'] ?? 'Egitto',
        'guests' => (int)($_POST['guests'] ?? 2),
        'bedrooms' => (int)($_POST['bedrooms'] ?? 1),
        'bathrooms' => (int)($_POST['bathrooms'] ?? 1),
        'beds' => $bedsTotal,
        'beds_double' => $bedsDouble,
        'beds_single' => $bedsSingle,
        'beds_sofa' => $bedsSofa,
        'size_sqm' => $_POST['size_sqm'] !== '' ? (int)$_POST['size_sqm'] : null,
        'amenities' => json_encode(array_values(array_filter(array_map('trim', explode(',', $_POST['amenities'] ?? ''))))),
        'rules' => $_POST['rules'] ?? '',
        'check_in_time' => $_POST['check_in_time'] ?? '15:00',
        'check_out_time' => $_POST['check_out_time'] ?? '11:00',
        'base_price' => (float)$_POST['base_price'],
        'weekly_price' => $_POST['weekly_price'] !== '' ? (float)$_POST['weekly_price'] : null,
        'biweekly_price' => $_POST['biweekly_price'] !== '' ? (float)$_POST['biweekly_price'] : null,
        'triweekly_price' => $_POST['triweekly_price'] !== '' ? (float)$_POST['triweekly_price'] : null,
        'monthly_price' => $_POST['monthly_price'] !== '' ? (float)$_POST['monthly_price'] : null,
        'weekend_price' => $_POST['weekend_price'] !== '' ? (float)$_POST['weekend_price'] : null,
        'cleaning_fee' => (float)$_POST['cleaning_fee'],
        'security_deposit' => (float)$_POST['security_deposit'],
        'city_tax' => (float)$_POST['city_tax'],
        'city_tax_max_nights' => (int)$_POST['city_tax_max_nights'],
        'long_stay_discount_7' => (float)$_POST['long_stay_discount_7'],
        'long_stay_discount_14' => (float)$_POST['long_stay_discount_14'],
        'long_stay_discount_30' => (float)$_POST['long_stay_discount_30'],
        'manager_commission_pct' => (float)($_POST['manager_commission_pct'] ?? 20),
        'owner_name' => trim($_POST['owner_name'] ?? ''),
        'block_number' => trim($_POST['block_number'] ?? ''),
        'cleaner_directions' => $_POST['cleaner_directions'] ?? '',
        'gmaps_code' => trim($_POST['gmaps_code'] ?? ''),
        'gmaps_resolved' => (function($code, $apt){
            $code = trim($code);
            if ($code === '') return '';
            // Riusa il vecchio resolved se il codice non è cambiato
            if ($apt && trim($apt['gmaps_code'] ?? '') === $code && !empty($apt['gmaps_resolved'])) {
                return $apt['gmaps_resolved'];
            }
            $resolved = resolveGmapsCoords($code);
            return $resolved ?? '';
        })($_POST['gmaps_code'] ?? '', $apt),
        'map_x' => null,
        'map_y' => null,
        'active' => isset($_POST['active']) ? 1 : 0,
        'under_maintenance' => isset($_POST['under_maintenance']) ? 1 : 0,
        'cover_image' => $coverPath,
    ];
    if ($apt) {
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        q("UPDATE apartments SET $set WHERE id = ?", array_merge(array_values($data), [$apt['id']]));
        logActivity('update', 'apartment', $apt['id'], $data['name']);
        flash('Appartamento aggiornato');
        redirect('/admin/appartamento-edit.php?id=' . $apt['id']);
    } else {
        $newId = newId();
        $cols = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        q("INSERT INTO apartments (id, $cols) VALUES (?, $placeholders)", array_merge([$newId], array_values($data)));
        logActivity('create', 'apartment', $newId, $data['name']);
        flash('Appartamento creato');
        redirect('/admin/appartamento-edit.php?id=' . $newId);
    }
}

$photos = $apt ? rows('SELECT * FROM photos WHERE apartment_id = ? ORDER BY position ASC', [$apt['id']]) : [];
$amenitiesStr = $apt ? implode(', ', parseAmenities($apt['amenities'])) : '';

// Carica zone/villaggi dalla tabella zones (con fallback se la tabella non esiste)
$zonesList = [];
try {
    $zonesList = rows('SELECT name, kind FROM zones WHERE active = 1 ORDER BY kind ASC, position ASC, name ASC');
} catch (Throwable $e) {}
$defaults = ['name'=>'','slug'=>'','description'=>'','address'=>'','city'=>'','country'=>'Egitto','guests'=>2,'bedrooms'=>1,'bathrooms'=>1,'beds'=>1,'beds_double'=>0,'beds_single'=>0,'beds_sofa'=>0,'size_sqm'=>'','rules'=>'','check_in_time'=>'15:00','check_out_time'=>'11:00','base_price'=>80,'weekly_price'=>'','biweekly_price'=>'','triweekly_price'=>'','monthly_price'=>'','weekend_price'=>'','cleaning_fee'=>35,'security_deposit'=>0,'city_tax'=>2,'city_tax_max_nights'=>5,'long_stay_discount_7'=>5,'long_stay_discount_14'=>10,'long_stay_discount_30'=>20,'manager_commission_pct'=>20,'owner_name'=>'','active'=>1,'under_maintenance'=>0,'cover_image'=>'','block_number'=>'','cleaner_directions'=>'','gmaps_code'=>'','gmaps_resolved'=>''];
// Merge: i valori salvati sovrascrivono i default
$f = $apt ? array_merge($defaults, $apt) : $defaults;

$title = $apt ? 'Modifica appartamento' : 'Nuovo appartamento';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<form method="post" enctype="multipart/form-data" class="space-y-5">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold"><?= $apt ? 'Modifica appartamento' : 'Nuovo appartamento' ?></h1>
      <p class="text-ink-500 text-sm">Compila tutte le informazioni rilevanti.</p>
    </div>
    <div class="flex gap-2">
      <?php if ($apt): ?>
        <a href="/appartamento.php?slug=<?= e($apt['slug']) ?>" target="_blank" rel="noopener" class="btn-outline" title="Apri pagina pubblica in nuova scheda"><i data-lucide="external-link" class="size-[18px]"></i> Vedi sul sito</a>
        <button name="action" value="delete" type="submit" onclick="return confirm('Eliminare appartamento?')" class="btn-danger"><i data-lucide="trash-2" class="size-[18px]"></i> Elimina</button>
      <?php endif; ?>
      <button name="action" value="save" type="submit" class="btn-primary"><i data-lucide="save" class="size-[18px]"></i> Salva</button>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="card p-4 sm:p-5 space-y-3">
      <h3 class="font-display font-bold">Dati principali</h3>
      <label class="block"><span class="label">Nome</span><input class="input" name="name" required value="<?= e($f['name']) ?>"></label>
      <label class="block"><span class="label">Slug</span><input class="input" name="slug" value="<?= e($f['slug']) ?>" placeholder="auto dal nome"></label>
      <label class="block"><span class="label">Descrizione</span><textarea class="input min-h-[120px]" name="description"><?= e($f['description']) ?></textarea></label>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Indirizzo</span><input class="input" name="address" value="<?= e($f['address']) ?>"></label>
        <label class="block">
          <span class="label">Zona / Villaggio</span>
          <?php
            $currentCity = $f['city'] ?? '';
            $zoneNames = array_column($zonesList, 'name');
            $cityInList = in_array($currentCity, $zoneNames, true);
            // Raggruppa per kind
            $byKind = [];
            foreach ($zonesList as $z) { $byKind[$z['kind']][] = $z['name']; }
            $kindLabels = ['zone' => 'Zone', 'villaggio' => 'Villaggi / Resort', 'quartiere' => 'Quartieri'];
          ?>
          <select class="input" name="city">
            <option value="">— Nessuna —</option>
            <?php foreach ($kindLabels as $k => $label): if (empty($byKind[$k])) continue; ?>
              <optgroup label="<?= e($label) ?>">
                <?php foreach ($byKind[$k] as $zname): ?>
                  <option value="<?= e($zname) ?>" <?= $currentCity === $zname ? 'selected' : '' ?>><?= e($zname) ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
            <?php if ($currentCity && !$cityInList): ?>
              <option value="<?= e($currentCity) ?>" selected><?= e($currentCity) ?> (non in elenco)</option>
            <?php endif; ?>
          </select>
          <?php if (!$zonesList): ?>
            <span class="text-xs text-amber-600 mt-1 block">Nessuna zona creata. <a href="/admin/zone.php" class="underline">Creale qui</a> per poterle selezionare.</span>
          <?php else: ?>
            <span class="text-xs text-ink-500 mt-1 block">Per aggiungerne una nuova vai in <a href="/admin/zone.php" class="text-brand-600 underline">Zone & villaggi</a>.</span>
          <?php endif; ?>
        </label>
        <label class="block"><span class="label">Paese</span><input class="input" name="country" value="<?= e($f['country']) ?>"></label>
        <div></div>
      </div>
      <div class="space-y-2">
        <span class="label">Foto di copertina</span>
        <input type="hidden" name="existing_cover" value="<?= e($f['cover_image']) ?>">
        <div class="rounded-xl bg-ink-100 dark:bg-ink-800 overflow-hidden relative aspect-[16/9]">
          <?php if ($f['cover_image']): ?>
            <img id="cover-preview" src="<?= e($f['cover_image']) ?>" alt="" class="absolute inset-0 h-full w-full object-cover">
          <?php else: ?>
            <div id="cover-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-ink-400 gap-2">
              <i data-lucide="image" class="size-[28px]"></i>
              <span class="text-xs">Nessuna foto cover</span>
            </div>
            <img id="cover-preview" src="" alt="" class="absolute inset-0 h-full w-full object-cover hidden">
          <?php endif; ?>
        </div>
        <div class="flex flex-wrap gap-2">
          <label class="btn-outline cursor-pointer text-sm">
            <i data-lucide="upload" class="size-[14px]"></i> Carica foto
            <input type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp" class="hidden"
                   onchange="const f=this.files[0]; if(f){const r=new FileReader();r.onload=e=>{const i=document.getElementById('cover-preview');i.src=e.target.result;i.classList.remove('hidden');const p=document.getElementById('cover-placeholder');if(p)p.classList.add('hidden');document.getElementById('remove-cover-flag').value=''};r.readAsDataURL(f)}">
          </label>
          <?php if ($f['cover_image']): ?>
            <button type="button" class="btn-ghost text-red-600 text-sm" onclick="document.getElementById('cover-preview').classList.add('hidden');document.getElementById('remove-cover-flag').value='1';this.style.display='none'">
              <i data-lucide="trash-2" class="size-[14px]"></i> Rimuovi
            </button>
          <?php endif; ?>
          <input type="hidden" id="remove-cover-flag" name="remove_cover" value="">
        </div>
        <span class="text-xs text-ink-500">JPG, PNG o WebP. Consigliato 16:9, almeno 1200×675px.</span>
      </div>
    </div>

    <div class="card p-4 sm:p-5 space-y-3">
      <h3 class="font-display font-bold">Capacità & spazi</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Ospiti</span><input class="input" type="number" min="1" name="guests" value="<?= (int)$f['guests'] ?>"></label>
        <label class="block"><span class="label">Dimensione (mq)</span><input class="input" type="number" name="size_sqm" value="<?= e((string)$f['size_sqm']) ?>"></label>
        <label class="block"><span class="label">Camere</span><input class="input" type="number" min="0" name="bedrooms" value="<?= (int)$f['bedrooms'] ?>"></label>
        <label class="block"><span class="label">Bagni</span><input class="input" type="number" min="0" name="bathrooms" value="<?= (int)$f['bathrooms'] ?>"></label>
        <div class="sm:col-span-2">
          <span class="label">Dettaglio letti</span>
          <div class="grid grid-cols-3 gap-2">
            <label class="block">
              <input class="input text-center" type="number" min="0" max="20" name="beds_double" value="<?= (int)$f['beds_double'] ?>">
              <span class="text-[11px] text-ink-500 mt-1 block text-center">🛏️ Matrimoniali</span>
            </label>
            <label class="block">
              <input class="input text-center" type="number" min="0" max="20" name="beds_single" value="<?= (int)$f['beds_single'] ?>">
              <span class="text-[11px] text-ink-500 mt-1 block text-center">🛌 Singoli</span>
            </label>
            <label class="block">
              <input class="input text-center" type="number" min="0" max="20" name="beds_sofa" value="<?= (int)$f['beds_sofa'] ?>">
              <span class="text-[11px] text-ink-500 mt-1 block text-center">🛋️ Divani letto</span>
            </label>
          </div>
          <span class="text-[11px] text-ink-500 mt-1 block">Il totale (<?= (int)$f['beds'] ?> letto<?= (int)$f['beds'] === 1 ? '' : 'i' ?>) viene calcolato automaticamente dalla somma.</span>
        </div>
        <div class="sm:col-span-2">
          <span class="label">Servizi</span>
          <?php
            $currentAmenities = array_values(array_filter(array_map('trim', explode(',', $amenitiesStr))));
            $presetAmenities = [
              'WiFi a pagamento', 'WiFi gratuito',
              'Cucina', 'Lavatrice', 'Lavastoviglie', 'Frigorifero', 'Microonde', 'Forno', 'Macchina caffè',
              'Aria condizionata', 'TV', 'Phon', 'Ferro da stiro', 'Cassaforte',
              'Asciugamani', 'Teli mare', 'Biancheria letti',
              'Piscina inclusa', 'Spiaggia inclusa', 'Spiaggia a pagamento (circa 800 m)',
              'Navetta interna al resort gratuita',
              'Balcone', 'Terrazzo', 'Vista mare', 'Vista piscina',
              'Parcheggio', 'Ascensore', 'Animali ammessi', 'Culla disponibile',
            ];
            $presetAvailable = array_values(array_diff($presetAmenities, $currentAmenities));
          ?>
          <input type="hidden" name="amenities" id="amenities-csv" value="<?= e(implode(', ', $currentAmenities)) ?>">
          <div id="amenities-chips" class="flex flex-wrap gap-1.5 mt-1 p-2 min-h-[44px] bg-ink-50 dark:bg-ink-800/50 rounded-xl border border-ink-200 dark:border-ink-700/60">
            <?php if (!$currentAmenities): ?>
              <span id="amenities-empty" class="text-xs text-ink-400 italic px-1 self-center">Nessun servizio selezionato</span>
            <?php endif; ?>
            <?php foreach ($currentAmenities as $a): ?>
              <span class="amenity-chip inline-flex items-center gap-1 bg-brand-100 dark:bg-brand-500/20 text-brand-800 dark:text-brand-200 text-xs font-medium px-2.5 py-1 rounded-full" data-name="<?= e($a) ?>">
                <?= e($a) ?>
                <button type="button" class="amenity-remove hover:text-red-600 ml-0.5" aria-label="Rimuovi"><i data-lucide="x" class="size-[12px]"></i></button>
              </span>
            <?php endforeach; ?>
          </div>
          <div class="flex flex-wrap gap-2 mt-2">
            <select id="amenity-preset" class="input flex-1 min-w-[180px] text-sm">
              <option value="">+ Aggiungi servizio predefinito...</option>
              <?php foreach ($presetAvailable as $p): ?>
                <option value="<?= e($p) ?>"><?= e($p) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" id="amenity-custom" class="input flex-1 min-w-[180px] text-sm" placeholder="...oppure scrivine uno tuo">
            <button type="button" id="amenity-add" class="btn-secondary text-sm whitespace-nowrap"><i data-lucide="plus" class="size-[14px]"></i> Aggiungi</button>
          </div>
          <script>
          (function(){
            const chipsBox = document.getElementById('amenities-chips');
            const csv = document.getElementById('amenities-csv');
            const preset = document.getElementById('amenity-preset');
            const custom = document.getElementById('amenity-custom');
            const addBtn = document.getElementById('amenity-add');
            function sync(){
              const names = Array.from(chipsBox.querySelectorAll('.amenity-chip')).map(c => c.dataset.name);
              csv.value = names.join(', ');
              const empty = document.getElementById('amenities-empty');
              if (names.length === 0 && !empty){
                const s = document.createElement('span');
                s.id = 'amenities-empty';
                s.className = 'text-xs text-ink-400 italic px-1 self-center';
                s.textContent = 'Nessun servizio selezionato';
                chipsBox.appendChild(s);
              } else if (names.length > 0 && empty){
                empty.remove();
              }
            }
            function addAmenity(name){
              name = name.trim(); if (!name) return;
              const existing = Array.from(chipsBox.querySelectorAll('.amenity-chip')).map(c => c.dataset.name.toLowerCase());
              if (existing.includes(name.toLowerCase())) return;
              const span = document.createElement('span');
              span.className = 'amenity-chip inline-flex items-center gap-1 bg-brand-100 dark:bg-brand-500/20 text-brand-800 dark:text-brand-200 text-xs font-medium px-2.5 py-1 rounded-full';
              span.dataset.name = name;
              span.innerHTML = name.replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c])) + ' <button type="button" class="amenity-remove hover:text-red-600 ml-0.5" aria-label="Rimuovi"><i data-lucide="x" class="size-[12px]"></i></button>';
              chipsBox.appendChild(span);
              if (window.lucide) lucide.createIcons();
              // rimuovi opzione dal select se era un preset
              const opt = preset.querySelector('option[value="' + CSS.escape(name) + '"]');
              if (opt) opt.remove();
              sync();
            }
            chipsBox.addEventListener('click', e => {
              const btn = e.target.closest('.amenity-remove');
              if (!btn) return;
              const chip = btn.closest('.amenity-chip');
              const name = chip.dataset.name;
              chip.remove();
              // rimetti l'opzione nel select se era un preset
              const allOpts = Array.from(preset.options).map(o => o.value);
              if (!allOpts.includes(name)) {
                const o = document.createElement('option');
                o.value = name; o.textContent = name;
                preset.appendChild(o);
              }
              sync();
            });
            preset.addEventListener('change', () => {
              if (preset.value){ addAmenity(preset.value); preset.value = ''; }
            });
            addBtn.addEventListener('click', () => { addAmenity(custom.value); custom.value = ''; });
            custom.addEventListener('keydown', e => { if (e.key === 'Enter'){ e.preventDefault(); addBtn.click(); } });
          })();
          </script>
        </div>
        <label class="block"><span class="label">Check-in</span><input class="input" type="time" name="check_in_time" value="<?= e($f['check_in_time']) ?>"></label>
        <label class="block"><span class="label">Check-out</span><input class="input" type="time" name="check_out_time" value="<?= e($f['check_out_time']) ?>"></label>
      </div>
      <label class="block"><span class="label">Regole della casa</span><textarea class="input min-h-[100px]" name="rules"><?= e($f['rules']) ?></textarea></label>
    </div>

    <div class="card p-4 sm:p-5 space-y-3">
      <h3 class="font-display font-bold">Prezzi</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Per notte (€)</span><input class="input" type="number" step="0.01" name="base_price" value="<?= e((string)$f['base_price']) ?>" required></label>
        <label class="block"><span class="label">Weekend (opz.)</span><input class="input" type="number" step="0.01" name="weekend_price" value="<?= e((string)$f['weekend_price']) ?>"></label>
        <label class="block"><span class="label">Settimanale (totale)</span><input class="input" type="number" step="0.01" name="weekly_price" value="<?= e((string)$f['weekly_price']) ?>"></label>
        <label class="block"><span class="label">2 settimane (totale)</span><input class="input" type="number" step="0.01" name="biweekly_price" value="<?= e((string)$f['biweekly_price']) ?>"></label>
        <label class="block"><span class="label">3 settimane (totale)</span><input class="input" type="number" step="0.01" name="triweekly_price" value="<?= e((string)$f['triweekly_price']) ?>"></label>
        <label class="block"><span class="label">Mensile</span><input class="input" type="number" step="0.01" name="monthly_price" value="<?= e((string)$f['monthly_price']) ?>"></label>
      </div>
      <h3 class="font-display font-bold pt-2">Sconti automatici %</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <label class="block"><span class="label">>7 notti</span><input class="input" type="number" name="long_stay_discount_7" value="<?= e((string)$f['long_stay_discount_7']) ?>"></label>
        <label class="block"><span class="label">>14 notti</span><input class="input" type="number" name="long_stay_discount_14" value="<?= e((string)$f['long_stay_discount_14']) ?>"></label>
        <label class="block"><span class="label">>30 notti</span><input class="input" type="number" name="long_stay_discount_30" value="<?= e((string)$f['long_stay_discount_30']) ?>"></label>
      </div>
    </div>

    <div class="card p-4 sm:p-5 space-y-3">
      <h3 class="font-display font-bold">Tasse & fee</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Pulizie (€)</span><input class="input" type="number" step="0.01" name="cleaning_fee" value="<?= e((string)$f['cleaning_fee']) ?>"></label>
        <label class="block"><span class="label">Cauzione (€)</span><input class="input" type="number" step="0.01" name="security_deposit" value="<?= e((string)$f['security_deposit']) ?>"></label>
        <label class="block"><span class="label">Tassa soggiorno €/p/notte</span><input class="input" type="number" step="0.01" name="city_tax" value="<?= e((string)$f['city_tax']) ?>"></label>
        <label class="block"><span class="label">Notti max tassa</span><input class="input" type="number" name="city_tax_max_nights" value="<?= e((string)$f['city_tax_max_nights']) ?>"></label>
      </div>
      <div class="flex flex-wrap gap-4 pt-2">
        <label class="flex items-center gap-2"><input type="checkbox" name="active" <?= $f['active'] ? 'checked' : '' ?>> Attivo (visibile sul sito)</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="under_maintenance" <?= $f['under_maintenance'] ? 'checked' : '' ?>> In manutenzione</label>
      </div>
    </div>

    <div class="card p-4 sm:p-5 space-y-3 border-2 border-sky-200 dark:border-sky-500/30 bg-sky-50/40 dark:bg-sky-500/5 lg:col-span-2">
      <div class="flex items-start gap-3">
        <span class="h-9 w-9 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center shrink-0"><i data-lucide="map-pinned" class="size-[18px]"></i></span>
        <div>
          <h3 class="font-display font-bold">Posizione su Google Maps</h3>
          <p class="text-xs text-ink-500 mt-0.5">La signora delle pulizie aprirà un bottone che apre direttamente Google Maps con la navigazione fino all'appartamento.</p>
        </div>
      </div>

      <label class="block">
        <span class="label flex items-center gap-2">Link o Plus Code di Google Maps <span class="badge-soft text-[10px]">obbligatorio</span></span>
        <input class="input font-mono text-xs" type="text" name="gmaps_code" value="<?= e((string)$f['gmaps_code']) ?>" placeholder="es. https://maps.app.goo.gl/XmwqWBWQ9QDJFJ9Q7  oppure  W9G6+MWJ Sharm El Sheikh">
        <?php if (!empty($f['gmaps_code'])):
          $isUrl = (bool)preg_match('#^https?://#i', $f['gmaps_code']);
          $resolved = trim((string)($f['gmaps_resolved'] ?? ''));
        ?>
          <div class="flex flex-wrap items-center gap-2 mt-2">
            <a href="<?= e(gmapsNavUrl($f['gmaps_code'], $resolved)) ?>" target="_blank" rel="noopener" class="btn-outline text-xs">
              <i data-lucide="external-link" class="size-[12px]"></i> Anteprima navigazione
            </a>
            <?php if ($resolved): ?>
              <span class="text-[11px] text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5 font-medium">
                <i data-lucide="check-circle-2" class="size-[12px]"></i>
                Coordinate estratte: <code class="bg-emerald-50 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded font-mono"><?= e($resolved) ?></code> · la navigazione partirà subito
              </span>
            <?php elseif ($isUrl): ?>
              <span class="text-[11px] text-amber-700 flex items-center gap-1.5">
                <i data-lucide="info" class="size-[12px]"></i> Link non risolvibile lato server: la signora dovrà toccare "Indicazioni" dentro Google Maps
              </span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <details class="text-[11px] text-ink-500 mt-3">
          <summary class="cursor-pointer font-semibold text-ink-600 hover:text-ink-800">Come si ottiene? (clicca per istruzioni)</summary>
          <div class="mt-2 space-y-3 pl-1">
            <div>
              <div class="font-semibold text-ink-700 dark:text-ink-200">Opzione 1 — Link condiviso (consigliato, più preciso)</div>
              <ol class="list-decimal list-inside space-y-0.5 ml-1 mt-1">
                <li>Apri Google Maps sul telefono nel punto esatto dell'appartamento</li>
                <li>Tocca a lungo per piazzare un segnaposto</li>
                <li>Tocca "Condividi" → "Copia link"</li>
                <li>Incolla qui (sarà tipo <code class="bg-ink-100 dark:bg-ink-800 px-1 rounded">https://maps.app.goo.gl/...</code>)</li>
              </ol>
            </div>
            <div>
              <div class="font-semibold text-ink-700 dark:text-ink-200">Opzione 2 — Plus Code</div>
              <ol class="list-decimal list-inside space-y-0.5 ml-1 mt-1">
                <li>Tocca a lungo per il segnaposto → tocca il segnaposto</li>
                <li>Tocca il codice tipo <code class="bg-ink-100 dark:bg-ink-800 px-1 rounded">V75V+8Q3</code></li>
                <li>Copia il Plus Code completo (es. <code class="bg-ink-100 dark:bg-ink-800 px-1 rounded">W9G6+MWJ Sharm El Sheikh</code>)</li>
              </ol>
            </div>
          </div>
        </details>
      </label>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-sky-100 dark:border-sky-500/20">
        <label class="block">
          <span class="label">Numero blocco (opzionale)</span>
          <input class="input" type="text" name="block_number" value="<?= e((string)$f['block_number']) ?>" placeholder="es. 19, 47, K3">
          <span class="text-[11px] text-ink-500 mt-1 block">Mostrato come badge nella lista pulizie e nel dettaglio.</span>
        </label>
        <label class="block">
          <span class="label">Indicazioni extra (opzionale)</span>
          <textarea class="input min-h-[72px]" name="cleaner_directions" placeholder="Es: 1° piano, chiavi dal portiere del blocco"><?= e((string)$f['cleaner_directions']) ?></textarea>
        </label>
      </div>
    </div>

    <div class="card p-4 sm:p-5 space-y-3 border-2 border-brand-200 dark:border-brand-500/30 bg-brand-50/40 dark:bg-brand-500/5">
      <div class="flex items-start gap-3">
        <span class="h-9 w-9 rounded-xl bg-brand-100 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="percent" class="size-[18px]"></i></span>
        <div>
          <h3 class="font-display font-bold">Property management</h3>
          <p class="text-xs text-ink-500 mt-0.5">Imposta la tua commissione su ogni prenotazione di questo appartamento. Userai questi dati nella Dashboard e in Spese & bilancio.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="label">Commissione di gestione (%)</span>
          <div class="relative">
            <input class="input pr-10" type="number" step="0.01" min="0" max="100" name="manager_commission_pct" value="<?= e((string)$f['manager_commission_pct']) ?>">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-400 text-sm">%</span>
          </div>
          <span class="text-[11px] text-ink-500 mt-1 block">Es. 20 = trattieni il 20% sui ricavi netti, il resto va al proprietario.</span>
        </label>
        <label class="block">
          <span class="label">Proprietario (opzionale)</span>
          <input class="input" type="text" name="owner_name" value="<?= e((string)$f['owner_name']) ?>" placeholder="Nome dell'imprenditore">
          <span class="text-[11px] text-ink-500 mt-1 block">Solo per memoria interna, non viene mostrato sul sito.</span>
        </label>
      </div>
    </div>
  </div>
</form>

<?php if (!$apt): ?>
<div class="card p-5 mt-5 border-2 border-dashed border-brand-300 bg-brand-50/50 dark:bg-brand-500/5 text-center">
  <div class="h-14 w-14 mx-auto rounded-2xl bg-brand-100 text-brand-600 flex items-center justify-center mb-3"><i data-lucide="image-plus" class="size-[26px]"></i></div>
  <h3 class="font-display font-bold text-lg">Foto e video</h3>
  <p class="text-ink-500 text-sm mt-1 max-w-md mx-auto">Compila prima i dati principali qui sopra e clicca <b>Salva</b>. Subito dopo potrai caricare la galleria di foto e video.</p>
</div>
<?php else: ?>
<div class="card p-4 sm:p-5 mt-5">
  <h3 class="font-display font-bold mb-3">Galleria foto e video</h3>
  <form method="post" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 mb-4">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="photo_add">
    <label class="btn-primary cursor-pointer"><i data-lucide="image-plus" class="size-[18px]"></i> Carica foto o video<input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" class="hidden" onchange="this.form.submit()"></label>
    <span class="text-xs text-ink-500">Foto: JPG, PNG, WebP. Video: MP4, WebM, MOV. Puoi selezionare più file insieme.</span>
  </form>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
    <?php foreach ($photos as $p): $isVideo = isVideoUrl($p['url']); ?>
      <div class="relative group rounded-xl overflow-hidden aspect-[4/3] bg-ink-100 dark:bg-ink-900">
        <?php if ($isVideo): ?>
          <video src="<?= e($p['url']) ?>" class="h-full w-full object-cover" muted preload="metadata"></video>
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="h-12 w-12 rounded-full bg-black/60 backdrop-blur flex items-center justify-center">
              <i data-lucide="play" class="size-[20px] text-white"></i>
            </div>
          </div>
          <span class="absolute bottom-2 left-2 badge bg-black/70 text-white text-[10px]"><i data-lucide="video" class="size-[10px]"></i> Video</span>
        <?php else: ?>
          <img src="<?= e($p['url']) ?>" class="h-full w-full object-cover">
        <?php endif; ?>
        <form method="post" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="photo_delete">
          <input type="hidden" name="photo_id" value="<?= e($p['id']) ?>">
          <button onclick="return confirm('Rimuovere?')" class="h-8 w-8 rounded-full bg-red-500 text-white flex items-center justify-center"><i data-lucide="trash-2" class="size-[14px]"></i></button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$photos): ?><div class="col-span-full text-sm text-ink-500 text-center py-6">Nessuna foto o video.</div><?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
