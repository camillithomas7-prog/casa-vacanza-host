<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

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
        if (!empty($_FILES['photos']['tmp_name'][0])) {
            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if (!is_uploaded_file($tmp)) continue;
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                $fname = uniqid('p_') . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
                $dest = __DIR__ . '/../uploads/photos/' . $fname;
                move_uploaded_file($tmp, $dest);
                q('INSERT INTO photos (id, apartment_id, url, position) VALUES (?, ?, ?, (SELECT IFNULL(MAX(position),0)+1 FROM (SELECT position FROM photos WHERE apartment_id = ?) p))',
                    [newId(), $apt['id'], '/uploads/photos/' . $fname, $apt['id']]);
            }
        }
        if (!empty($_POST['photo_url'])) {
            q('INSERT INTO photos (id, apartment_id, url, position) VALUES (?, ?, ?, (SELECT IFNULL(MAX(position),0)+1 FROM (SELECT position FROM photos WHERE apartment_id = ?) p))',
                [newId(), $apt['id'], $_POST['photo_url'], $apt['id']]);
        }
        flash('Foto aggiunta');
        redirect('/admin/appartamento-edit.php?id=' . $apt['id']);
    }

    if ($action === 'photo_delete' && $apt) {
        q('DELETE FROM photos WHERE id = ? AND apartment_id = ?', [$_POST['photo_id'], $apt['id']]);
        redirect('/admin/appartamento-edit.php?id=' . $apt['id']);
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
        'active' => isset($_POST['active']) ? 1 : 0,
        'under_maintenance' => isset($_POST['under_maintenance']) ? 1 : 0,
        'cover_image' => $_POST['cover_image'] ?? null,
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
$f = $apt ?? ['name'=>'','slug'=>'','description'=>'','address'=>'','city'=>'','country'=>'Egitto','guests'=>2,'bedrooms'=>1,'bathrooms'=>1,'beds'=>1,'size_sqm'=>'','rules'=>'','check_in_time'=>'15:00','check_out_time'=>'11:00','base_price'=>80,'weekly_price'=>'','biweekly_price'=>'','triweekly_price'=>'','monthly_price'=>'','weekend_price'=>'','cleaning_fee'=>35,'security_deposit'=>0,'city_tax'=>2,'city_tax_max_nights'=>5,'long_stay_discount_7'=>5,'long_stay_discount_14'=>10,'long_stay_discount_30'=>20,'active'=>1,'under_maintenance'=>0,'cover_image'=>''];

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

  <div class="grid lg:grid-cols-2 gap-5">
    <div class="card p-5 space-y-3">
      <h3 class="font-display font-bold">Dati principali</h3>
      <label class="block"><span class="label">Nome</span><input class="input" name="name" required value="<?= e($f['name']) ?>"></label>
      <label class="block"><span class="label">Slug</span><input class="input" name="slug" value="<?= e($f['slug']) ?>" placeholder="auto dal nome"></label>
      <label class="block"><span class="label">Descrizione</span><textarea class="input min-h-[120px]" name="description"><?= e($f['description']) ?></textarea></label>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="block"><span class="label">Indirizzo</span><input class="input" name="address" value="<?= e($f['address']) ?>"></label>
        <label class="block"><span class="label">Zona di Sharm</span><input class="input" name="city" value="<?= e($f['city']) ?>" placeholder="es. Naama Bay, Hadaba, Sharks Bay…"></label>
        <label class="block"><span class="label">Paese</span><input class="input" name="country" value="<?= e($f['country']) ?>"></label>
        <label class="block"><span class="label">URL foto cover</span><input class="input" name="cover_image" value="<?= e($f['cover_image']) ?>"></label>
      </div>
    </div>

    <div class="card p-5 space-y-3">
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

    <div class="card p-5 space-y-3">
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

    <div class="card p-5 space-y-3">
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
  </div>
</form>

<?php if ($apt): ?>
<div class="card p-5 mt-5">
  <h3 class="font-display font-bold mb-3">Galleria foto</h3>
  <form method="post" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 mb-4">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="photo_add">
    <label class="btn-outline cursor-pointer"><i data-lucide="image-plus" class="size-[18px]"></i> Carica file<input type="file" name="photos[]" multiple accept="image/*" class="hidden" onchange="this.form.submit()"></label>
    <div class="flex gap-2 flex-1">
      <input class="input flex-1" name="photo_url" placeholder="https://... (URL immagine)">
      <button class="btn-secondary"><i data-lucide="plus" class="size-[16px]"></i> Aggiungi</button>
    </div>
  </form>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
    <?php foreach ($photos as $p): ?>
      <div class="relative group rounded-xl overflow-hidden aspect-[4/3] bg-ink-100 dark:bg-ink-900">
        <img src="<?= e($p['url']) ?>" class="h-full w-full object-cover">
        <form method="post" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="photo_delete">
          <input type="hidden" name="photo_id" value="<?= e($p['id']) ?>">
          <button onclick="return confirm('Rimuovere?')" class="h-8 w-8 rounded-full bg-red-500 text-white flex items-center justify-center"><i data-lucide="trash-2" class="size-[14px]"></i></button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$photos): ?><div class="col-span-full text-sm text-ink-500 text-center py-6">Nessuna foto.</div><?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
