<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$apartments = rows('SELECT id, name FROM apartments ORDER BY name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        q('INSERT INTO reviews (id, apartment_id, author_name, rating, title, body, approved) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [newId(), $_POST['apartment_id'], $_POST['author_name'], (int)$_POST['rating'], $_POST['title'] ?: null, $_POST['body'], isset($_POST['approved']) ? 1 : 0]);
        flash('Recensione aggiunta');
    }
    if ($action === 'approve') q('UPDATE reviews SET approved = 1 WHERE id = ?', [$_POST['review_id']]);
    if ($action === 'delete') q('DELETE FROM reviews WHERE id = ?', [$_POST['review_id']]);
    redirect('/admin/recensioni.php');
}

$reviews = rows('SELECT r.*, a.name AS apartment_name FROM reviews r JOIN apartments a ON r.apartment_id = a.id ORDER BY r.created_at DESC');

$title = 'Recensioni';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <h1 class="font-display text-2xl sm:text-3xl font-bold">Recensioni</h1>

  <form method="post" class="card p-4 sm:p-5 grid sm:grid-cols-6 gap-2">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="add">
    <select class="input" name="apartment_id" required>
      <?php foreach ($apartments as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
    </select>
    <input class="input" name="author_name" placeholder="Autore" required>
    <select class="input" name="rating">
      <?php foreach ([5,4,3,2,1] as $n): ?><option value="<?= $n ?>"><?= str_repeat('★', $n) ?></option><?php endforeach; ?>
    </select>
    <input class="input" name="title" placeholder="Titolo">
    <input class="input" name="body" placeholder="Recensione" required>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="approved" checked> Pubblica</label>
    <button class="btn-primary sm:col-span-6"><i data-lucide="plus" class="size-[16px]"></i> Aggiungi</button>
  </form>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($reviews as $r): ?>
      <div class="card p-4 sm:p-5">
        <div class="flex items-center justify-between">
          <div>
            <div class="font-semibold"><?= e($r['author_name']) ?> <span class="text-xs text-ink-500">· <?= e($r['apartment_name']) ?></span></div>
            <div class="text-yellow-500 text-sm"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></div>
          </div>
          <div class="flex gap-1">
            <?php if (!$r['approved']): ?>
              <form method="post"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="review_id" value="<?= e($r['id']) ?>"><button class="btn-outline text-xs"><i data-lucide="check" class="size-[14px]"></i> Approva</button></form>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Eliminare?')"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="review_id" value="<?= e($r['id']) ?>"><button class="btn-ghost text-red-600"><i data-lucide="trash-2" class="size-[14px]"></i></button></form>
          </div>
        </div>
        <?php if ($r['title']): ?><div class="font-medium mt-2"><?= e($r['title']) ?></div><?php endif; ?>
        <p class="text-sm text-ink-600 dark:text-ink-300 mt-1"><?= e($r['body']) ?></p>
        <?php if (!$r['approved']): ?><div class="text-xs text-amber-600 mt-2">In attesa di approvazione</div><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$reviews): ?><div class="md:col-span-2 card p-10 text-center text-ink-500">Nessuna recensione.</div><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
