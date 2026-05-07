<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    q('UPDATE message_templates SET name = ?, channel = ?, subject = ?, body = ?, active = ? WHERE id = ?',
        [$_POST['name'], $_POST['channel'], $_POST['subject'], $_POST['body'], isset($_POST['active']) ? 1 : 0, $_POST['id']]);
    flash('Template aggiornato');
    redirect('/admin/template.php?id=' . urlencode($_POST['id']));
}

$templates = rows('SELECT * FROM message_templates ORDER BY name ASC');
$activeId = $_GET['id'] ?? ($templates[0]['id'] ?? '');
$tpl = null;
foreach ($templates as $t) if ($t['id'] === $activeId) $tpl = $t;

$VARS = ['{{nome}}','{{appartamento}}','{{indirizzo}}','{{checkin}}','{{checkout}}','{{ora_checkin}}','{{ora_checkout}}','{{ospiti}}','{{totale}}','{{acconto}}','{{saldo}}','{{codice}}','{{telefono}}'];

$preview = $tpl ? preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function($m) {
  return ['nome'=>'Mario Rossi','appartamento'=>'Naama Bay Sea View','indirizzo'=>'Naama Bay Promenade, Sharm El Sheikh','checkin'=>'10 ago 2026','checkout'=>'17 ago 2026','ora_checkin'=>'15:00','ora_checkout'=>'11:00','ospiti'=>'2','totale'=>'€665,00','acconto'=>'€200,00','saldo'=>'€465,00','codice'=>'PM-2026-0001','telefono'=>'+39...'][$m[1]] ?? '';
}, $tpl['body']) : '';

$title = 'Template messaggi';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div>
    <h1 class="font-display text-2xl sm:text-3xl font-bold">Template messaggi</h1>
    <p class="text-ink-500 mt-1">Personalizza i messaggi automatici inviati ai clienti.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
    <div class="card p-3">
      <ul class="space-y-1">
        <?php foreach ($templates as $t): ?>
          <li><a href="?id=<?= e($t['id']) ?>" class="block px-3 py-2 rounded-lg text-sm <?= $t['id'] === $activeId ? 'bg-brand-50 text-brand-700 font-semibold' : 'hover:bg-ink-100 dark:hover:bg-ink-800' ?>">
            <?= e($t['name']) ?>
            <div class="text-xs text-ink-500 font-normal"><?= e($t['channel']) ?></div>
          </a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php if ($tpl): ?>
      <form method="post" class="card p-4 sm:p-5 lg:col-span-2 space-y-3">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= e($tpl['id']) ?>">
        <div class="grid grid-cols-2 gap-3">
          <label class="block"><span class="label">Nome</span><input class="input" name="name" value="<?= e($tpl['name']) ?>"></label>
          <label class="block"><span class="label">Canale</span>
            <select class="input" name="channel">
              <?php foreach (['whatsapp'=>'WhatsApp','email'=>'Email','both'=>'Entrambi'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $tpl['channel'] === $v ? 'selected' : '' ?>><?= e($l) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <label class="block"><span class="label">Oggetto (email)</span><input class="input" name="subject" value="<?= e($tpl['subject']) ?>"></label>
        <label class="block"><span class="label">Testo</span><textarea class="input min-h-[260px] font-mono text-sm" name="body" id="bodyTextarea"><?= e($tpl['body']) ?></textarea></label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" <?= $tpl['active'] ? 'checked' : '' ?>> Attivo</label>
        <button class="btn-primary"><i data-lucide="save" class="size-[18px]"></i> Salva</button>
      </form>

      <div class="space-y-3">
        <div class="card p-4">
          <div class="text-sm font-semibold mb-2">Variabili disponibili</div>
          <div class="flex flex-wrap gap-1">
            <?php foreach ($VARS as $v): ?>
              <button type="button" onclick="document.getElementById('bodyTextarea').value += ' <?= e($v) ?>'" class="text-xs px-2 py-1 rounded bg-ink-100 dark:bg-ink-800 hover:bg-brand-100"><?= e($v) ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card p-4">
          <div class="text-sm font-semibold mb-2">Anteprima</div>
          <pre class="whitespace-pre-wrap text-xs leading-relaxed text-ink-700 dark:text-ink-200"><?= e($preview) ?></pre>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
