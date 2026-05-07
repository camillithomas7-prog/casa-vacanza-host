<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$isNew = !$id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);

    $name = trim($_POST['name'] ?? '');
    $kind = in_array($_POST['kind'] ?? 'zone', ['zone','villaggio','quartiere']) ? $_POST['kind'] : 'zone';
    $description = trim($_POST['description'] ?? '');
    $position = (int)($_POST['position'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;

    if (!$name) { flash('Nome obbligatorio', 'error'); redirect($_SERVER['REQUEST_URI']); }

    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $slug = trim(preg_replace('/-+/', '-', $slug), '-');

    // Gestione upload foto
    $imagePath = $_POST['existing_image'] ?? '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            flash('Formato immagine non supportato (jpg/png/webp)', 'error');
            redirect($_SERVER['REQUEST_URI']);
        }
        $dir = __DIR__ . '/../uploads/zones';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fname = 'z_' . $slug . '_' . substr(uniqid(), -6) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
        $dest = $dir . '/' . $fname;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $imagePath = '/uploads/zones/' . $fname;
        }
    }

    if ($isNew) {
        // Slug uniqueness
        $base = $slug; $i = 2;
        while (val('SELECT id FROM zones WHERE slug = ?', [$slug])) { $slug = $base . '-' . $i++; }
        $newId = newId();
        q('INSERT INTO zones (id, name, slug, kind, description, image, position, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$newId, $name, $slug, $kind, $description ?: null, $imagePath ?: null, $position, $active]);
        flash('Zona creata');
        redirect('/admin/zone-edit.php?id=' . $newId);
    } else {
        q('UPDATE zones SET name = ?, kind = ?, description = ?, image = ?, position = ?, active = ? WHERE id = ?',
            [$name, $kind, $description ?: null, $imagePath ?: null, $position, $active, $id]);
        flash('Zona aggiornata');
        redirect('/admin/zone-edit.php?id=' . $id);
    }
}

$z = $isNew ? ['id'=>null,'name'=>'','kind'=>'zone','description'=>'','image'=>'','position'=>0,'active'=>1]
            : row('SELECT * FROM zones WHERE id = ?', [$id]);
if (!$z) { flash('Zona non trovata', 'error'); redirect('/admin/zone.php'); }

$title = $isNew ? 'Nuova zona' : 'Modifica zona';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<form method="post" enctype="multipart/form-data" class="space-y-5">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
  <input type="hidden" name="existing_image" value="<?= e($z['image']) ?>">

  <div class="flex items-center justify-between flex-wrap gap-3">
    <div class="min-w-0">
      <a href="/admin/zone.php" class="text-sm text-ink-500 hover:text-ink-900 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="size-[14px]"></i> Tutte le zone</a>
      <h1 class="font-display text-2xl sm:text-3xl font-bold mt-1"><?= $isNew ? 'Nuova zona / villaggio' : e($z['name']) ?></h1>
    </div>
    <div class="flex gap-2">
      <?php if (!$isNew): ?>
        <a href="/appartamenti.php?city=<?= urlencode($z['name']) ?>" target="_blank" class="btn-outline"><i data-lucide="external-link" class="size-[14px]"></i> Anteprima</a>
      <?php endif; ?>
      <button class="btn-primary"><i data-lucide="save" class="size-[16px]"></i> Salva</button>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
      <div class="card p-4 sm:p-5 space-y-3">
        <h3 class="font-display font-bold">Dati principali</h3>
        <label class="block">
          <span class="label">Nome</span>
          <input class="input" name="name" value="<?= e($z['name']) ?>" placeholder="Es. Naama Bay, Hadaba, Domina Coral Bay…" required>
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="label">Tipo</span>
            <select class="input" name="kind">
              <option value="zone" <?= $z['kind']==='zone'?'selected':'' ?>>Zona / Quartiere</option>
              <option value="villaggio" <?= $z['kind']==='villaggio'?'selected':'' ?>>Villaggio / Resort</option>
              <option value="quartiere" <?= $z['kind']==='quartiere'?'selected':'' ?>>Sotto-zona</option>
            </select>
          </label>
          <label class="block">
            <span class="label">Ordine di visualizzazione</span>
            <input class="input" type="number" name="position" value="<?= (int)$z['position'] ?>">
            <span class="text-xs text-ink-500">Più basso = più in alto nella home</span>
          </label>
        </div>
        <label class="block">
          <span class="label">Descrizione (opzionale)</span>
          <textarea class="input min-h-[100px]" name="description" placeholder="Breve descrizione della zona/villaggio (visibile nei dettagli)"><?= e($z['description']) ?></textarea>
        </label>
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" name="active" <?= $z['active'] ? 'checked' : '' ?>>
          <span>Visibile sulla home pubblica</span>
        </label>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-4 sm:p-5 space-y-3">
        <h3 class="font-display font-bold">Foto della zona</h3>
        <div class="aspect-[4/3] rounded-xl bg-ink-100 dark:bg-ink-800 overflow-hidden relative">
          <?php if ($z['image']): ?>
            <img id="preview" src="<?= e($z['image']) ?>" alt="" class="absolute inset-0 h-full w-full object-cover">
          <?php else: ?>
            <div id="placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-ink-400 gap-2">
              <i data-lucide="image" class="size-[32px]"></i>
              <span class="text-xs">Nessuna foto</span>
            </div>
            <img id="preview" src="" alt="" class="absolute inset-0 h-full w-full object-cover hidden">
          <?php endif; ?>
        </div>
        <label class="block">
          <span class="label">Carica nuova foto</span>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="input"
                 onchange="const f=this.files[0]; if(f){const r=new FileReader();r.onload=e=>{const i=document.getElementById('preview');i.src=e.target.result;i.classList.remove('hidden');const p=document.getElementById('placeholder');if(p)p.classList.add('hidden')};r.readAsDataURL(f)}">
          <span class="text-xs text-ink-500 mt-1 block">JPG, PNG o WebP. Consigliato 4:3, 1200×900px.</span>
        </label>
      </div>

      <div class="card p-4 sm:p-5 bg-sky-50/40 dark:bg-sky-500/5 border-sky-200">
        <div class="flex items-start gap-2 text-sm">
          <i data-lucide="link" class="size-[16px] text-sky-600 shrink-0 mt-0.5"></i>
          <div class="text-ink-700 dark:text-ink-300">
            Per collegare un appartamento a questa zona, scrivi <b>"<?= e($z['name'] ?: 'NomeZona') ?>"</b> nel campo <i>Zona di Sharm</i> della scheda appartamento.
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
