<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();
ensureColumns();

$id = $_GET['id'] ?? null;
$apt = $id ? row('SELECT * FROM apartments WHERE id = ?', [$id]) : null;
if ($id && !$apt) { flash('Appartamento non trovato', 'error'); redirect('/admin/appartamenti.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && $apt) {
        q('DELETE FROM apartments WHERE id = ?', [$apt['id']]);
        logActivity('delete', 'apartment', $apt['id'], $apt['name']);
        flash('Appartamento eliminato');
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
        'beds' => (int)($_POST['beds'] ?? 1),
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
        'map_x' => isset($_POST['map_x']) && $_POST['map_x'] !== '' ? (float)$_POST['map_x'] : null,
        'map_y' => isset($_POST['map_y']) && $_POST['map_y'] !== '' ? (float)$_POST['map_y'] : null,
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
$defaults = ['name'=>'','slug'=>'','description'=>'','address'=>'','city'=>'','country'=>'Egitto','guests'=>2,'bedrooms'=>1,'bathrooms'=>1,'beds'=>1,'size_sqm'=>'','rules'=>'','check_in_time'=>'15:00','check_out_time'=>'11:00','base_price'=>80,'weekly_price'=>'','biweekly_price'=>'','triweekly_price'=>'','monthly_price'=>'','weekend_price'=>'','cleaning_fee'=>35,'security_deposit'=>0,'city_tax'=>2,'city_tax_max_nights'=>5,'long_stay_discount_7'=>5,'long_stay_discount_14'=>10,'long_stay_discount_30'=>20,'manager_commission_pct'=>20,'owner_name'=>'','active'=>1,'under_maintenance'=>0,'cover_image'=>'','block_number'=>'','cleaner_directions'=>'','map_x'=>null,'map_y'=>null];
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
        <label class="block"><span class="label">Letti</span><input class="input" type="number" min="1" name="beds" value="<?= (int)$f['beds'] ?>"></label>
        <label class="block"><span class="label">Bagni</span><input class="input" type="number" min="0" name="bathrooms" value="<?= (int)$f['bathrooms'] ?>"></label>
        <label class="block"><span class="label">Servizi (separati da virgola)</span><input class="input" name="amenities" value="<?= e($amenitiesStr) ?>" placeholder="WiFi, Aria, Parcheggio"></label>
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
          <h3 class="font-display font-bold">Posizione nel resort (Domina Coral Bay)</h3>
          <p class="text-xs text-ink-500 mt-0.5">Imposta il blocco e indica sulla mappa dove si trova l'appartamento. La signora delle pulizie vedrà queste informazioni con un puntatore animato.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="label">Numero blocco</span>
          <input class="input" type="text" name="block_number" id="block_number_input" value="<?= e((string)$f['block_number']) ?>" placeholder="es. 19, 47, K3">
          <span class="text-[11px] text-ink-500 mt-1 block">Verrà mostrato nella lista pulizie e accanto al puntatore sulla mappa.</span>
        </label>
        <div class="block">
          <span class="label">Coordinate marker</span>
          <div class="flex items-center gap-2">
            <input class="input flex-1 tabular-nums text-xs" type="text" id="map_xy_display" value="<?= $f['map_x'] !== null && $f['map_y'] !== null ? round((float)$f['map_x']*100,1) . '% , ' . round((float)$f['map_y']*100,1) . '%' : 'Non posizionato' ?>" readonly>
            <button type="button" id="map_clear_btn" class="btn-outline text-xs">Reset</button>
          </div>
          <input type="hidden" name="map_x" id="map_x_input" value="<?= $f['map_x'] !== null ? e((string)$f['map_x']) : '' ?>">
          <input type="hidden" name="map_y" id="map_y_input" value="<?= $f['map_y'] !== null ? e((string)$f['map_y']) : '' ?>">
          <span class="text-[11px] text-ink-500 mt-1 block">Tocca/clicca sulla mappa o trascina il puntatore per posizionarlo.</span>
        </div>
      </div>

      <div class="relative rounded-2xl overflow-hidden border-2 border-sky-200 dark:border-sky-500/30 bg-amber-50 dark:bg-ink-900 select-none" style="aspect-ratio: 2.05 / 1;">
        <img src="/assets/resort-map.jpg?v=4" alt="Mappa resort Domina Coral Bay" id="resort_map_img" class="absolute inset-0 w-full h-full object-contain" draggable="false">
        <div id="resort_marker" class="absolute z-10 -translate-x-1/2 -translate-y-full cursor-grab active:cursor-grabbing transition-opacity duration-200" style="left: <?= $f['map_x'] !== null ? round((float)$f['map_x']*100,2) : 50 ?>%; top: <?= $f['map_y'] !== null ? round((float)$f['map_y']*100,2) : 50 ?>%; <?= $f['map_x'] === null ? 'opacity:0;pointer-events:none;' : '' ?>">
          <div class="relative">
            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-6 h-2 bg-black/30 rounded-full blur-sm"></div>
            <svg width="44" height="56" viewBox="0 0 44 56" class="drop-shadow-xl">
              <defs>
                <linearGradient id="pinGrad" x1="0" x2="0" y1="0" y2="1">
                  <stop offset="0" stop-color="#f43f5e"/>
                  <stop offset="1" stop-color="#be123c"/>
                </linearGradient>
              </defs>
              <path d="M22 0 C 9 0, 0 10, 0 22 C 0 36, 22 56, 22 56 C 22 56, 44 36, 44 22 C 44 10, 35 0, 22 0 Z" fill="url(#pinGrad)" stroke="#fff" stroke-width="2"/>
              <circle cx="22" cy="20" r="7" fill="#fff"/>
              <text x="22" y="24" text-anchor="middle" font-family="Inter,system-ui" font-size="11" font-weight="800" fill="#be123c" id="resort_marker_text"><?= e((string)$f['block_number']) ?: '?' ?></text>
            </svg>
          </div>
        </div>
        <div id="map_hint" class="absolute bottom-3 left-1/2 -translate-x-1/2 px-3 py-1.5 rounded-full bg-black/70 backdrop-blur text-white text-xs font-medium <?= $f['map_x'] !== null ? 'hidden' : '' ?>">
          Tocca sulla mappa per posizionare
        </div>
      </div>

      <label class="block">
        <span class="label">Indicazioni per la signora delle pulizie</span>
        <textarea class="input min-h-[100px]" name="cleaner_directions" placeholder="Es: Entrata principale, gira a destra dopo la reception, oltre la piscina principale. L'appartamento è al primo piano. Le chiavi sono dal portiere del blocco."><?= e((string)$f['cleaner_directions']) ?></textarea>
        <span class="text-[11px] text-ink-500 mt-1 block">Testo libero. Comparirà nella pagina di dettaglio della pulizia.</span>
      </label>
    </div>

    <script>
    (function(){
      const img = document.getElementById('resort_map_img');
      const marker = document.getElementById('resort_marker');
      const xInput = document.getElementById('map_x_input');
      const yInput = document.getElementById('map_y_input');
      const display = document.getElementById('map_xy_display');
      const clearBtn = document.getElementById('map_clear_btn');
      const hint = document.getElementById('map_hint');
      const blockInput = document.getElementById('block_number_input');
      const markerText = document.getElementById('resort_marker_text');
      let dragging = false;

      function setPos(x, y) {
        x = Math.max(0, Math.min(1, x));
        y = Math.max(0, Math.min(1, y));
        marker.style.left = (x * 100).toFixed(2) + '%';
        marker.style.top = (y * 100).toFixed(2) + '%';
        marker.style.opacity = '1';
        marker.style.pointerEvents = '';
        xInput.value = x.toFixed(3);
        yInput.value = y.toFixed(3);
        display.value = (x * 100).toFixed(1) + '% , ' + (y * 100).toFixed(1) + '%';
        if (hint) hint.classList.add('hidden');
      }

      function eventToFrac(e) {
        const r = img.getBoundingClientRect();
        const pt = e.touches ? e.touches[0] : e;
        return { x: (pt.clientX - r.left) / r.width, y: (pt.clientY - r.top) / r.height };
      }

      img.addEventListener('click', (e) => {
        if (dragging) return;
        const p = eventToFrac(e);
        setPos(p.x, p.y);
      });

      function startDrag(e) {
        dragging = true;
        e.preventDefault();
      }
      marker.addEventListener('mousedown', startDrag);
      marker.addEventListener('touchstart', startDrag, {passive:false});

      document.addEventListener('mousemove', (e) => { if (!dragging) return; const p = eventToFrac(e); setPos(p.x, p.y); });
      document.addEventListener('touchmove', (e) => { if (!dragging) return; e.preventDefault(); const p = eventToFrac(e); setPos(p.x, p.y); }, {passive:false});
      document.addEventListener('mouseup', () => { setTimeout(() => dragging = false, 50); });
      document.addEventListener('touchend', () => { setTimeout(() => dragging = false, 50); });

      clearBtn.addEventListener('click', () => {
        xInput.value = '';
        yInput.value = '';
        display.value = 'Non posizionato';
        marker.style.opacity = '0';
        marker.style.pointerEvents = 'none';
        if (hint) hint.classList.remove('hidden');
      });

      if (blockInput && markerText) {
        blockInput.addEventListener('input', () => {
          markerText.textContent = blockInput.value.trim().substring(0, 3) || '?';
        });
      }
    })();
    </script>

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

<?php if ($apt): ?>
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
