<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/services.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$svc = $id ? row('SELECT * FROM services WHERE id = ?', [$id]) : null;
if ($id && !$svc) { flash('Servizio non trovato', 'error'); redirect('/admin/servizi.php'); }

// Helper upload immagini in /uploads/services
$uploadServiceImage = function (array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) return null;
    $dir = __DIR__ . '/../uploads/services';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fname = 's_' . substr(uniqid(), -8) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
    $dest = $dir . '/' . $fname;
    if (move_uploaded_file($file['tmp_name'], $dest)) return '/uploads/services/' . $fname;
    return null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete' && $svc) {
        q('DELETE FROM services WHERE id = ?', [$svc['id']]);
        logActivity('delete', 'service', $svc['id'], $svc['name']);
        flash('Servizio eliminato');
        redirect('/admin/servizi.php');
    }

    if ($action === 'gallery_remove' && $svc) {
        $url = $_POST['url'] ?? '';
        $existing = parseFeatures($svc['gallery'] ?? '');
        $filtered = array_values(array_filter($existing, fn($u) => $u !== $url));
        q('UPDATE services SET gallery = ? WHERE id = ?', [json_encode($filtered), $svc['id']]);
        flash('Foto rimossa');
        redirect('/admin/servizio-edit.php?id=' . $svc['id']);
    }

    if ($action === 'gallery_add' && $svc) {
        $existing = parseFeatures($svc['gallery'] ?? '');
        $added = 0;
        if (!empty($_FILES['gallery_files']['tmp_name'][0])) {
            foreach ($_FILES['gallery_files']['tmp_name'] as $i => $tmp) {
                if (!is_uploaded_file($tmp)) continue;
                $path = $uploadServiceImage([
                    'tmp_name' => $tmp,
                    'name' => $_FILES['gallery_files']['name'][$i],
                    'error' => $_FILES['gallery_files']['error'][$i],
                ]);
                if ($path) { $existing[] = $path; $added++; }
            }
        }
        q('UPDATE services SET gallery = ? WHERE id = ?', [json_encode(array_values($existing)), $svc['id']]);
        flash($added . ' foto caricat' . ($added === 1 ? 'a' : 'e'));
        redirect('/admin/servizio-edit.php?id=' . $svc['id']);
    }

    // Cover image upload (su salvataggio principale)
    $coverPath = $_POST['existing_cover'] ?? null;
    if (isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
        $coverPath = null;
    }
    if (!empty($_FILES['cover_image_file']['name']) && $_FILES['cover_image_file']['error'] === UPLOAD_ERR_OK) {
        $newCover = $uploadServiceImage($_FILES['cover_image_file']);
        if ($newCover) $coverPath = $newCover;
    }

    $type = $_POST['type'] ?? 'car';
    $existingGallery = $svc ? parseFeatures($svc['gallery'] ?? '') : [];
    $data = [
        'type' => $type,
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? '') ?: slugify($_POST['name'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'cover_image' => $coverPath,
        'gallery' => json_encode($existingGallery),
        'features' => json_encode(array_values(array_filter(array_map('trim', explode(',', $_POST['features'] ?? ''))))),
        'active' => isset($_POST['active']) ? 1 : 0,
    ];
    if (isRental($type)) {
        $data += [
            'daily_price' => (float)$_POST['daily_price'],
            'weekly_price' => $_POST['weekly_price'] !== '' ? (float)$_POST['weekly_price'] : null,
            'biweekly_price' => $_POST['biweekly_price'] !== '' ? (float)$_POST['biweekly_price'] : null,
            'triweekly_price' => $_POST['triweekly_price'] !== '' ? (float)$_POST['triweekly_price'] : null,
            'monthly_price' => $_POST['monthly_price'] !== '' ? (float)$_POST['monthly_price'] : null,
            'long_stay_discount_7' => (float)($_POST['long_stay_discount_7'] ?? 0),
            'long_stay_discount_14' => (float)($_POST['long_stay_discount_14'] ?? 0),
            'long_stay_discount_30' => (float)($_POST['long_stay_discount_30'] ?? 0),
            'security_deposit' => (float)($_POST['security_deposit'] ?? 0),
            'cleaning_fee' => (float)($_POST['cleaning_fee'] ?? 0),
            'resort_name' => $_POST['resort_name'] ?? null,
            'resort_address' => $_POST['resort_address'] ?? null,
            'min_age' => $_POST['min_age'] !== '' ? (int)$_POST['min_age'] : null,
            'license_required' => isset($_POST['license_required']) ? 1 : 0,
            'helmet_included' => isset($_POST['helmet_included']) ? 1 : 0,
            'fuel_included' => isset($_POST['fuel_included']) ? 1 : 0,
            'insurance_included' => isset($_POST['insurance_included']) ? 1 : 0,
        ];
    } elseif (isExperience($type)) {
        $data += [
            'price_per_person' => (float)$_POST['price_per_person'],
            'price_per_group' => $_POST['price_per_group'] !== '' ? (float)$_POST['price_per_group'] : null,
            'duration_hours' => $_POST['duration_hours'] !== '' ? (float)$_POST['duration_hours'] : null,
            'group_size_min' => (int)($_POST['group_size_min'] ?? 1),
            'group_size_max' => (int)($_POST['group_size_max'] ?? 30),
            'meeting_point' => $_POST['meeting_point'] ?? null,
            'schedule_days' => $_POST['schedule_days'] ?? null,
            'includes' => json_encode(array_values(array_filter(array_map('trim', explode("\n", $_POST['includes'] ?? ''))))),
            'excludes' => json_encode(array_values(array_filter(array_map('trim', explode("\n", $_POST['excludes'] ?? ''))))),
        ];
    } else { // transfer
        $data += [
            'price_per_group' => (float)$_POST['price_per_group'],
            'price_per_person' => $_POST['price_per_person'] !== '' ? (float)$_POST['price_per_person'] : null,
            'from_location' => $_POST['from_location'] ?? null,
            'to_location' => $_POST['to_location'] ?? null,
            'vehicle_capacity' => (int)($_POST['vehicle_capacity'] ?? 4),
        ];
    }

    if ($svc) {
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        q("UPDATE services SET $set WHERE id = ?", array_merge(array_values($data), [$svc['id']]));
        logActivity('update', 'service', $svc['id'], $data['name']);
        flash('Servizio aggiornato');
        redirect('/admin/servizio-edit.php?id=' . $svc['id']);
    } else {
        $newId = newId();
        $cols = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        q("INSERT INTO services (id, $cols) VALUES (?, $placeholders)", array_merge([$newId], array_values($data)));
        logActivity('create', 'service', $newId, $data['name']);
        flash('Servizio creato');
        redirect('/admin/servizio-edit.php?id=' . $newId);
    }
}

$f = $svc ?? [
  'type' => $_GET['type'] ?? 'car',
  'name' => '', 'slug' => '', 'description' => '', 'cover_image' => '', 'gallery' => '', 'features' => '',
  'daily_price' => 0, 'weekly_price' => '', 'biweekly_price' => '', 'triweekly_price' => '', 'monthly_price' => '',
  'long_stay_discount_7' => 5, 'long_stay_discount_14' => 10, 'long_stay_discount_30' => 20,
  'security_deposit' => 0, 'cleaning_fee' => 0,
  'resort_name' => '', 'resort_address' => '',
  'min_age' => '', 'license_required' => 0, 'helmet_included' => 0, 'fuel_included' => 0, 'insurance_included' => 0,
  'price_per_person' => 0, 'price_per_group' => 0, 'duration_hours' => '', 'group_size_min' => 1, 'group_size_max' => 30,
  'meeting_point' => '', 'schedule_days' => 'Lun,Mar,Mer,Gio,Ven,Sab,Dom',
  'includes' => '', 'excludes' => '',
  'from_location' => 'Aeroporto SSH', 'to_location' => '', 'vehicle_capacity' => 4,
  'active' => 1,
];
$galleryList = parseFeatures($f['gallery'] ?? '');
$features = implode(', ', parseFeatures($f['features']));
$includes = implode("\n", parseFeatures($f['includes'] ?? ''));
$excludes = implode("\n", parseFeatures($f['excludes'] ?? ''));

$title = $svc ? 'Modifica servizio' : 'Nuovo servizio';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<form method="post" enctype="multipart/form-data" class="space-y-5" x-data="{ type: '<?= e($f['type']) ?>' }">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
  <input type="hidden" name="existing_cover" value="<?= e($f['cover_image']) ?>">
  <input type="hidden" id="remove-cover-flag" name="remove_cover" value="">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <h1 class="font-serif text-2xl font-semibold tracking-tight"><?= $svc ? 'Modifica servizio' : 'Nuovo servizio' ?></h1>
    <div class="flex gap-2">
      <?php if ($svc): ?>
        <button name="action" value="delete" type="submit" onclick="return confirm('Eliminare?')" class="btn-danger"><i data-lucide="trash-2" class="size-[16px]"></i> Elimina</button>
      <?php endif; ?>
      <button name="action" value="save" class="btn-primary"><i data-lucide="save" class="size-[18px]"></i> Salva</button>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="card p-4 sm:p-5 space-y-3">
      <h3 class="font-display font-bold">Tipo & informazioni</h3>
      <label class="block"><span class="label">Tipo servizio</span>
        <select name="type" x-model="type" class="input">
          <optgroup label="Noleggi">
            <?php foreach (SERVICE_GROUPS['rental'] as $t): ?><option value="<?= $t ?>" <?= $f['type'] === $t ? 'selected' : '' ?>><?= e(serviceTypeLabel($t)) ?></option><?php endforeach; ?>
          </optgroup>
          <optgroup label="Escursioni">
            <?php foreach (SERVICE_GROUPS['experience'] as $t): ?><option value="<?= $t ?>" <?= $f['type'] === $t ? 'selected' : '' ?>><?= e(serviceTypeLabel($t)) ?></option><?php endforeach; ?>
          </optgroup>
          <option value="transfer" <?= $f['type'] === 'transfer' ? 'selected' : '' ?>>Transfer aeroporto</option>
        </select>
      </label>
      <label class="block"><span class="label">Nome</span><input class="input" name="name" required value="<?= e($f['name']) ?>"></label>
      <label class="block"><span class="label">Slug</span><input class="input" name="slug" value="<?= e($f['slug']) ?>" placeholder="auto dal nome"></label>
      <label class="block"><span class="label">Descrizione</span><textarea class="input min-h-[120px]" name="description"><?= e($f['description']) ?></textarea></label>

      <div class="space-y-2">
        <span class="label">Foto di copertina</span>
        <div class="rounded-xl bg-ink-100 dark:bg-ink-800 overflow-hidden relative aspect-[16/9]">
          <?php if ($f['cover_image']): ?>
            <img id="svc-cover-preview" src="<?= e($f['cover_image']) ?>" alt="" class="absolute inset-0 h-full w-full object-cover">
          <?php else: ?>
            <div id="svc-cover-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-ink-400 gap-2">
              <i data-lucide="image" class="size-[28px]"></i>
              <span class="text-xs">Nessuna foto cover</span>
            </div>
            <img id="svc-cover-preview" src="" alt="" class="absolute inset-0 h-full w-full object-cover hidden">
          <?php endif; ?>
        </div>
        <div class="flex flex-wrap gap-2">
          <label class="btn-outline cursor-pointer text-sm">
            <i data-lucide="upload" class="size-[14px]"></i> Carica foto
            <input type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp" class="hidden"
                   onchange="const f=this.files[0]; if(f){const r=new FileReader();r.onload=e=>{const i=document.getElementById('svc-cover-preview');i.src=e.target.result;i.classList.remove('hidden');const p=document.getElementById('svc-cover-placeholder');if(p)p.classList.add('hidden');document.getElementById('remove-cover-flag').value=''};r.readAsDataURL(f)}">
          </label>
          <?php if ($f['cover_image']): ?>
            <button type="button" class="btn-ghost text-red-600 text-sm" onclick="document.getElementById('svc-cover-preview').classList.add('hidden');document.getElementById('remove-cover-flag').value='1';this.style.display='none'">
              <i data-lucide="trash-2" class="size-[14px]"></i> Rimuovi
            </button>
          <?php endif; ?>
        </div>
        <span class="text-xs text-ink-500">JPG, PNG o WebP. Consigliato 16:9.</span>
      </div>

      <label class="block"><span class="label">Caratteristiche (separate da virgola)</span><input class="input" name="features" value="<?= e($features) ?>" placeholder="Aria condizionata, Bluetooth, GPS"></label>
      <label class="flex items-center gap-2"><input type="checkbox" name="active" <?= $f['active'] ? 'checked' : '' ?>> Attivo (visibile sul sito)</label>
    </div>

    <!-- NOLEGGIO -->
    <div class="card p-4 sm:p-5 space-y-3" x-show="['car','golf_cart','scooter','escooter'].includes(type)">
      <h3 class="font-display font-bold">Tariffe noleggio</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">€/giorno</span><input class="input" type="number" step="0.01" name="daily_price" value="<?= e((string)$f['daily_price']) ?>"></label>
        <label class="block"><span class="label">Settimanale</span><input class="input" type="number" step="0.01" name="weekly_price" value="<?= e((string)$f['weekly_price']) ?>"></label>
        <label class="block"><span class="label">2 settimane</span><input class="input" type="number" step="0.01" name="biweekly_price" value="<?= e((string)$f['biweekly_price']) ?>"></label>
        <label class="block"><span class="label">3 settimane</span><input class="input" type="number" step="0.01" name="triweekly_price" value="<?= e((string)$f['triweekly_price']) ?>"></label>
        <label class="block"><span class="label">Mensile</span><input class="input" type="number" step="0.01" name="monthly_price" value="<?= e((string)$f['monthly_price']) ?>"></label>
      </div>
      <h3 class="font-display font-bold pt-2">Sconti automatici %</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <label class="block"><span class="label">>7gg</span><input class="input" type="number" name="long_stay_discount_7" value="<?= e((string)$f['long_stay_discount_7']) ?>"></label>
        <label class="block"><span class="label">>14gg</span><input class="input" type="number" name="long_stay_discount_14" value="<?= e((string)$f['long_stay_discount_14']) ?>"></label>
        <label class="block"><span class="label">>30gg</span><input class="input" type="number" name="long_stay_discount_30" value="<?= e((string)$f['long_stay_discount_30']) ?>"></label>
      </div>
      <h3 class="font-display font-bold pt-2">Resort & dettagli</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Nome resort (opzionale)</span><input class="input" name="resort_name" value="<?= e($f['resort_name']) ?>"></label>
        <label class="block"><span class="label">Indirizzo resort</span><input class="input" name="resort_address" value="<?= e($f['resort_address']) ?>"></label>
        <label class="block"><span class="label">Cauzione (€)</span><input class="input" type="number" step="0.01" name="security_deposit" value="<?= e((string)$f['security_deposit']) ?>"></label>
        <label class="block"><span class="label">Età minima</span><input class="input" type="number" name="min_age" value="<?= e((string)$f['min_age']) ?>"></label>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="license_required" <?= $f['license_required'] ? 'checked' : '' ?>> Patente richiesta</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="helmet_included" <?= $f['helmet_included'] ? 'checked' : '' ?>> Casco incluso</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fuel_included" <?= $f['fuel_included'] ? 'checked' : '' ?>> Carburante incluso</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="insurance_included" <?= $f['insurance_included'] ? 'checked' : '' ?>> Assicurazione</label>
      </div>
    </div>

    <!-- ESCURSIONE -->
    <div class="card p-4 sm:p-5 space-y-3" x-show="['boat_excursion','desert_excursion','diving','tour','spa','other_excursion'].includes(type)">
      <h3 class="font-display font-bold">Tariffe escursione</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">€/persona</span><input class="input" type="number" step="0.01" name="price_per_person" value="<?= e((string)$f['price_per_person']) ?>"></label>
        <label class="block"><span class="label">€/gruppo (privato)</span><input class="input" type="number" step="0.01" name="price_per_group" value="<?= e((string)$f['price_per_group']) ?>"></label>
        <label class="block"><span class="label">Durata (ore)</span><input class="input" type="number" step="0.5" name="duration_hours" value="<?= e((string)$f['duration_hours']) ?>"></label>
        <label class="block"><span class="label">Gruppo min</span><input class="input" type="number" name="group_size_min" value="<?= e((string)$f['group_size_min']) ?>"></label>
        <label class="block"><span class="label">Gruppo max</span><input class="input" type="number" name="group_size_max" value="<?= e((string)$f['group_size_max']) ?>"></label>
      </div>
      <label class="block"><span class="label">Punto di incontro</span><input class="input" name="meeting_point" value="<?= e($f['meeting_point']) ?>"></label>
      <label class="block"><span class="label">Giorni disponibili (es. Lun,Mer,Ven)</span><input class="input" name="schedule_days" value="<?= e($f['schedule_days']) ?>"></label>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Incluso (uno per riga)</span><textarea class="input min-h-[100px] text-xs" name="includes"><?= e($includes) ?></textarea></label>
        <label class="block"><span class="label">Non incluso</span><textarea class="input min-h-[100px] text-xs" name="excludes"><?= e($excludes) ?></textarea></label>
      </div>
    </div>

    <!-- TRANSFER -->
    <div class="card p-4 sm:p-5 space-y-3" x-show="type === 'transfer'">
      <h3 class="font-display font-bold">Transfer</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Da</span><input class="input" name="from_location" value="<?= e($f['from_location']) ?>"></label>
        <label class="block"><span class="label">A</span><input class="input" name="to_location" value="<?= e($f['to_location']) ?>"></label>
        <label class="block"><span class="label">€/tratta</span><input class="input" type="number" step="0.01" name="price_per_group" value="<?= e((string)$f['price_per_group']) ?>"></label>
        <label class="block"><span class="label">€/persona (alternativa)</span><input class="input" type="number" step="0.01" name="price_per_person" value="<?= e((string)$f['price_per_person']) ?>"></label>
        <label class="block"><span class="label">Capacità veicolo</span><input class="input" type="number" name="vehicle_capacity" value="<?= e((string)$f['vehicle_capacity']) ?>"></label>
      </div>
    </div>
  </div>
</form>

<?php if ($svc): ?>
<div class="card p-4 sm:p-5 mt-5">
  <h3 class="font-display font-bold mb-3">Galleria foto</h3>
  <form method="post" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 mb-4">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="gallery_add">
    <label class="btn-primary cursor-pointer">
      <i data-lucide="image-plus" class="size-[18px]"></i> Carica una o più foto
      <input type="file" name="gallery_files[]" multiple accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.form.submit()">
    </label>
    <span class="text-xs text-ink-500">JPG, PNG o WebP. Selezione multipla.</span>
  </form>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
    <?php foreach ($galleryList as $url): ?>
      <div class="relative group rounded-xl overflow-hidden aspect-[4/3] bg-ink-100 dark:bg-ink-900">
        <img src="<?= e($url) ?>" class="h-full w-full object-cover">
        <form method="post" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="gallery_remove">
          <input type="hidden" name="url" value="<?= e($url) ?>">
          <button onclick="return confirm('Rimuovere?')" class="h-8 w-8 rounded-full bg-red-500 text-white flex items-center justify-center"><i data-lucide="trash-2" class="size-[14px]"></i></button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$galleryList): ?><div class="col-span-full text-sm text-ink-500 text-center py-6">Nessuna foto nella galleria.</div><?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
