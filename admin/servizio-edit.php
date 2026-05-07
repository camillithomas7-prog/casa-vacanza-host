<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/services.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$svc = $id ? row('SELECT * FROM services WHERE id = ?', [$id]) : null;
if ($id && !$svc) { flash('Servizio non trovato', 'error'); redirect('/admin/servizi.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete' && $svc) {
        q('DELETE FROM services WHERE id = ?', [$svc['id']]);
        logActivity('delete', 'service', $svc['id'], $svc['name']);
        flash('Servizio eliminato');
        redirect('/admin/servizi.php');
    }

    $type = $_POST['type'] ?? 'car';
    $data = [
        'type' => $type,
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? '') ?: slugify($_POST['name'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'cover_image' => $_POST['cover_image'] ?? null,
        'gallery' => json_encode(array_values(array_filter(array_map('trim', explode("\n", $_POST['gallery'] ?? ''))))),
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
$gallery = is_array(parseFeatures($f['gallery'])) ? implode("\n", parseFeatures($f['gallery'])) : '';
$features = implode(', ', parseFeatures($f['features']));
$includes = implode("\n", parseFeatures($f['includes'] ?? ''));
$excludes = implode("\n", parseFeatures($f['excludes'] ?? ''));

$title = $svc ? 'Modifica servizio' : 'Nuovo servizio';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<form method="post" class="space-y-5" x-data="{ type: '<?= e($f['type']) ?>' }">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <h1 class="font-serif text-2xl font-semibold tracking-tight"><?= $svc ? 'Modifica servizio' : 'Nuovo servizio' ?></h1>
    <div class="flex gap-2">
      <?php if ($svc): ?>
        <button name="action" value="delete" type="submit" onclick="return confirm('Eliminare?')" class="btn-danger"><i data-lucide="trash-2" class="size-[16px]"></i> Elimina</button>
      <?php endif; ?>
      <button name="action" value="save" class="btn-primary"><i data-lucide="save" class="size-[18px]"></i> Salva</button>
    </div>
  </div>

  <div class="grid lg:grid-cols-2 gap-5">
    <div class="card p-5 space-y-3">
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
      <label class="block"><span class="label">URL foto cover</span><input class="input" name="cover_image" value="<?= e($f['cover_image']) ?>"></label>
      <label class="block"><span class="label">Galleria (un URL per riga)</span><textarea class="input min-h-[80px] font-mono text-xs" name="gallery"><?= e($gallery) ?></textarea></label>
      <label class="block"><span class="label">Caratteristiche (separate da virgola)</span><input class="input" name="features" value="<?= e($features) ?>" placeholder="Aria condizionata, Bluetooth, GPS"></label>
      <label class="flex items-center gap-2"><input type="checkbox" name="active" <?= $f['active'] ? 'checked' : '' ?>> Attivo (visibile sul sito)</label>
    </div>

    <!-- NOLEGGIO -->
    <div class="card p-5 space-y-3" x-show="['car','golf_cart','scooter','escooter'].includes(type)">
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
    <div class="card p-5 space-y-3" x-show="['boat_excursion','desert_excursion','diving','tour','spa','other_excursion'].includes(type)">
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
    <div class="card p-5 space-y-3" x-show="type === 'transfer'">
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
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
