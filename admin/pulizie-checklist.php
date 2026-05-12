<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/cleaning.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $label = trim($_POST['label'] ?? '');
        if ($label !== '') {
            $tid = newId();
            $pos = (int)val('SELECT COALESCE(MAX(position),0)+1 FROM cleaning_tasks');
            q('INSERT INTO cleaning_tasks (id, label, description, position, active) VALUES (?, ?, ?, ?, 1)',
                [$tid, $label, $_POST['description'] ?: null, $pos]);
            try { propagateTaskToOpenSessions($tid); } catch (Throwable $e) {}
            flash('Voce aggiunta');
        }
    }
    if ($action === 'update') {
        q('UPDATE cleaning_tasks SET label = ?, description = ?, active = ? WHERE id = ?',
            [trim($_POST['label']), $_POST['description'] ?: null, isset($_POST['active']) ? 1 : 0, $_POST['task_id']]);
        flash('Voce aggiornata');
    }
    if ($action === 'delete') {
        q('DELETE FROM cleaning_tasks WHERE id = ?', [$_POST['task_id']]);
        flash('Voce eliminata');
    }
    if ($action === 'move') {
        $dir = $_POST['dir'] === 'up' ? -1 : 1;
        $tasks = rows('SELECT id, position FROM cleaning_tasks ORDER BY position ASC');
        $ids = array_column($tasks, 'id');
        $i = array_search($_POST['task_id'], $ids, true);
        if ($i !== false && isset($ids[$i + $dir])) {
            $a = $tasks[$i]; $b = $tasks[$i + $dir];
            q('UPDATE cleaning_tasks SET position = ? WHERE id = ?', [$b['position'], $a['id']]);
            q('UPDATE cleaning_tasks SET position = ? WHERE id = ?', [$a['position'], $b['id']]);
        }
    }
    redirect('/admin/pulizie-checklist.php');
}

$tasks = rows('SELECT * FROM cleaning_tasks ORDER BY position ASC, label ASC');

$title = 'Checklist pulizie';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-end justify-between flex-wrap gap-3">
    <div>
      <a href="/admin/pulizie.php" class="text-sm text-ink-500 hover:text-brand-600 inline-flex items-center gap-1"><i data-lucide="chevron-left" class="size-[14px]"></i> Torna alle pulizie</a>
      <h1 class="font-display text-2xl sm:text-3xl font-bold mt-2">Checklist pulizie</h1>
      <p class="text-ink-500 text-sm mt-1">Definisci le voci che la signora deve controllare a ogni pulizia. Le nuove voci vengono aggiunte automaticamente alle pulizie in corso.</p>
    </div>
  </div>

  <form method="post" class="card p-4 sm:p-5 space-y-3">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="add">
    <h3 class="font-display font-bold">Nuova voce</h3>
    <div class="grid grid-cols-1 sm:grid-cols-[1fr_2fr_auto] gap-3">
      <input class="input" name="label" placeholder="es. Cambio lenzuola e federe" required>
      <input class="input" name="description" placeholder="Nota o istruzione (opzionale)">
      <button class="btn-primary"><i data-lucide="plus" class="size-[16px]"></i> Aggiungi</button>
    </div>
  </form>

  <div class="card overflow-hidden">
    <?php if (!$tasks): ?>
      <div class="p-10 text-center text-sm text-ink-500">Nessuna voce. Aggiungi la prima qui sopra.</div>
    <?php else: ?>
      <ul class="divide-y divide-ink-100 dark:divide-ink-800">
        <?php foreach ($tasks as $i => $t): ?>
          <li class="p-3 sm:p-4 flex items-start gap-3 <?= !$t['active'] ? 'opacity-50' : '' ?>" x-data="{ editing:false }">
            <div class="flex flex-col gap-0.5 pt-1">
              <form method="post" class="leading-none">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="move"><input type="hidden" name="task_id" value="<?= e($t['id']) ?>"><input type="hidden" name="dir" value="up">
                <button <?= $i === 0 ? 'disabled' : '' ?> class="text-ink-400 hover:text-ink-700 disabled:opacity-30"><i data-lucide="chevron-up" class="size-[14px]"></i></button>
              </form>
              <form method="post" class="leading-none">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="move"><input type="hidden" name="task_id" value="<?= e($t['id']) ?>"><input type="hidden" name="dir" value="down">
                <button <?= $i === count($tasks)-1 ? 'disabled' : '' ?> class="text-ink-400 hover:text-ink-700 disabled:opacity-30"><i data-lucide="chevron-down" class="size-[14px]"></i></button>
              </form>
            </div>
            <div class="flex-1 min-w-0">
              <div x-show="!editing">
                <div class="font-medium text-sm sm:text-base"><?= e($t['label']) ?></div>
                <?php if ($t['description']): ?><div class="text-xs text-ink-500 mt-0.5"><?= e($t['description']) ?></div><?php endif; ?>
              </div>
              <form method="post" x-show="editing" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="task_id" value="<?= e($t['id']) ?>">
                <input class="input" name="label" value="<?= e($t['label']) ?>" required>
                <input class="input" name="description" value="<?= e($t['description'] ?? '') ?>" placeholder="Descrizione (opzionale)">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" <?= $t['active'] ? 'checked' : '' ?>> Attiva (mostra alla signora)</label>
                <div class="flex gap-2 justify-end">
                  <button type="button" @click="editing=false" class="btn-ghost text-sm">Annulla</button>
                  <button class="btn-primary text-sm">Salva</button>
                </div>
              </form>
            </div>
            <div class="flex gap-1 shrink-0" x-show="!editing">
              <button type="button" @click="editing=true" class="btn-ghost p-2" title="Modifica"><i data-lucide="pencil" class="size-[14px]"></i></button>
              <form method="post" onsubmit="return confirm('Eliminare questa voce?')">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="task_id" value="<?= e($t['id']) ?>">
                <button class="btn-ghost p-2 text-red-600" title="Elimina"><i data-lucide="trash-2" class="size-[14px]"></i></button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
