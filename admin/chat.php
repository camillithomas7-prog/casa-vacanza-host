<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

// Mark notifications chat_escalation as read se accedi alla pagina
try { q("UPDATE notifications SET is_read = 1 WHERE type = 'chat_escalation' AND is_read = 0"); } catch (Throwable $e) {}

$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    $cid = $_POST['cid'] ?? '';
    if ($action === 'resolve' && $cid) {
        q('UPDATE chat_conversations SET status = "resolved", handled_by_admin = 1 WHERE id = ?', [$cid]);
        flash('Conversazione contrassegnata come risolta');
        redirect('/admin/chat.php');
    }
    if ($action === 'update_contact' && $cid) {
        q('UPDATE chat_conversations SET customer_name = ?, customer_phone = ? WHERE id = ?',
            [trim($_POST['name'] ?? ''), trim($_POST['phone'] ?? ''), $cid]);
        flash('Contatto aggiornato');
        redirect('/admin/chat.php?id=' . $cid);
    }
    if ($action === 'add_note' && $cid) {
        $note = trim($_POST['note'] ?? '');
        if ($note !== '') {
            q('INSERT INTO chat_messages (id, conversation_id, role, content) VALUES (?, ?, ?, ?)',
                [newId(), $cid, 'admin_note', $note]);
        }
        redirect('/admin/chat.php?id=' . $cid);
    }
    if ($action === 'delete' && $cid) {
        q('DELETE FROM chat_conversations WHERE id = ?', [$cid]);
        flash('Conversazione eliminata');
        redirect('/admin/chat.php');
    }
}

if ($id) {
    // Dettaglio
    $conv = row('SELECT * FROM chat_conversations WHERE id = ?', [$id]);
    if (!$conv) { flash('Conversazione non trovata', 'error'); redirect('/admin/chat.php'); }
    $msgs = rows('SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC', [$id]);

    $title = 'Conversazione · ' . ($conv['customer_name'] ?: 'Anonimo');
    require __DIR__ . '/../partials/head.php';
    require __DIR__ . '/../partials/admin-shell-top.php';
    ?>
    <div class="space-y-4">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <a href="/admin/chat.php" class="text-sm text-ink-500 hover:text-brand-600">← Tutte le chat</a>
          <h1 class="font-serif text-3xl font-semibold mt-1"><?= e($conv['customer_name'] ?: 'Cliente anonimo') ?></h1>
          <div class="text-sm text-ink-500 mt-1 flex items-center gap-3 flex-wrap">
            <?php if ($conv['customer_phone']): ?>
              <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $conv['customer_phone'])) ?>" target="_blank" class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-xs font-bold hover:bg-emerald-200">
                <i data-lucide="message-circle" class="size-[12px]"></i> WhatsApp: <?= e($conv['customer_phone']) ?>
              </a>
            <?php endif; ?>
            <span class="text-xs">Iniziata <?= fmtDate($conv['created_at']) ?> · <?= (int)$conv['message_count'] ?> msg</span>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $conv['status'] === 'escalated' ? 'bg-amber-100 text-amber-800' : ($conv['status'] === 'resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-ink-100 text-ink-700') ?>">
              <?= $conv['status'] === 'escalated' ? '⚠ richiede risposta' : ($conv['status'] === 'resolved' ? '✓ risolta' : '· attiva') ?>
            </span>
          </div>
          <?php if ($conv['escalation_reason']): ?>
            <div class="mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-900"><b>Motivo escalation:</b> <?= e($conv['escalation_reason']) ?></div>
          <?php endif; ?>
        </div>
        <div class="flex gap-2 flex-wrap">
          <?php if ($conv['status'] !== 'resolved'): ?>
            <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="cid" value="<?= e($id) ?>"><button name="action" value="resolve" class="btn-primary"><i data-lucide="check" class="size-[16px]"></i> Segna come risolta</button></form>
          <?php endif; ?>
          <form method="post" class="inline" onsubmit="return confirm('Eliminare la conversazione?')"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="cid" value="<?= e($id) ?>"><button name="action" value="delete" class="btn-danger"><i data-lucide="trash-2" class="size-[16px]"></i> Elimina</button></form>
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-4">
        <div class="card p-4 lg:col-span-2 space-y-3 max-h-[70vh] overflow-y-auto">
          <?php foreach ($msgs as $m):
            $isAdmin = $m['role'] === 'admin_note';
            $isUser = $m['role'] === 'user';
            $cls = $isAdmin ? 'bg-amber-50 border border-amber-200 text-amber-900' : ($isUser ? 'bg-ink-100 dark:bg-ink-800 text-ink-900 dark:text-ink-100' : 'bg-brand-50 dark:bg-brand-500/10 text-ink-900 dark:text-ink-100');
            $align = $isUser ? 'mr-auto' : ($isAdmin ? 'mx-auto w-full' : 'ml-auto');
            $label = $isAdmin ? '📝 Nota interna' : ($isUser ? '👤 Cliente' : '🤖 Sofia');
          ?>
            <div class="<?= $align ?> max-w-[85%] <?= $cls ?> rounded-xl p-3">
              <div class="text-[10px] font-bold uppercase tracking-wider opacity-60 mb-1"><?= $label ?> · <?= fmtDate($m['created_at']) ?></div>
              <div class="text-sm whitespace-pre-wrap"><?= nl2br(e($m['content'])) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (!$msgs): ?><div class="text-center text-ink-400 text-sm py-6">Nessun messaggio</div><?php endif; ?>
        </div>

        <div class="space-y-4">
          <form method="post" class="card p-4 space-y-2">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="cid" value="<?= e($id) ?>">
            <input type="hidden" name="action" value="update_contact">
            <h3 class="font-display font-bold text-sm">Contatto cliente</h3>
            <label class="block"><span class="label">Nome</span><input class="input" name="name" value="<?= e($conv['customer_name']) ?>"></label>
            <label class="block"><span class="label">Telefono</span><input class="input" name="phone" value="<?= e($conv['customer_phone']) ?>" placeholder="+39 333 1234567"></label>
            <button class="btn-secondary w-full text-sm">Salva contatto</button>
          </form>

          <form method="post" class="card p-4 space-y-2">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="cid" value="<?= e($id) ?>">
            <input type="hidden" name="action" value="add_note">
            <h3 class="font-display font-bold text-sm">Nota interna</h3>
            <textarea class="input min-h-[80px]" name="note" placeholder="Es. richiamato il 19/05 alle 11:00, prenotato bilocale Coral Bay"></textarea>
            <button class="btn-secondary w-full text-sm">Aggiungi nota</button>
          </form>
        </div>
      </div>
    </div>
    <?php
    require __DIR__ . '/../partials/admin-shell-bottom.php';
    exit;
}

// ──────── LISTA ────────
$filter = $_GET['filter'] ?? 'escalated';
$where = '';
if ($filter === 'escalated') $where = "WHERE status = 'escalated'";
elseif ($filter === 'active') $where = "WHERE status = 'active'";
elseif ($filter === 'resolved') $where = "WHERE status = 'resolved'";
$convs = rows("SELECT * FROM chat_conversations $where ORDER BY updated_at DESC LIMIT 100");
$counts = [
    'escalated' => (int)val("SELECT COUNT(*) FROM chat_conversations WHERE status = 'escalated'"),
    'active' => (int)val("SELECT COUNT(*) FROM chat_conversations WHERE status = 'active'"),
    'resolved' => (int)val("SELECT COUNT(*) FROM chat_conversations WHERE status = 'resolved'"),
];

$title = 'Chat clienti';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-4">
  <div>
    <h1 class="font-serif text-3xl font-semibold">Chat clienti</h1>
    <p class="text-ink-500 mt-1">Conversazioni dal widget chat del sito · clicca per leggere e gestire.</p>
  </div>

  <div class="flex gap-2 flex-wrap">
    <a href="?filter=escalated" class="px-3 py-1.5 rounded-full text-sm border <?= $filter === 'escalated' ? 'bg-amber-500 text-white border-amber-500' : 'border-ink-200 text-ink-600 hover:bg-ink-50' ?>">⚠ Richiedono risposta (<?= $counts['escalated'] ?>)</a>
    <a href="?filter=active" class="px-3 py-1.5 rounded-full text-sm border <?= $filter === 'active' ? 'bg-ink-700 text-white border-ink-700' : 'border-ink-200 text-ink-600 hover:bg-ink-50' ?>">Attive (<?= $counts['active'] ?>)</a>
    <a href="?filter=resolved" class="px-3 py-1.5 rounded-full text-sm border <?= $filter === 'resolved' ? 'bg-emerald-500 text-white border-emerald-500' : 'border-ink-200 text-ink-600 hover:bg-ink-50' ?>">✓ Risolte (<?= $counts['resolved'] ?>)</a>
  </div>

  <?php if (!$convs): ?>
    <div class="card p-14 text-center">
      <div class="h-16 w-16 mx-auto rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-3"><i data-lucide="message-circle" class="size-[28px]"></i></div>
      <div class="font-display font-bold text-xl">Nessuna conversazione</div>
      <div class="text-ink-500 mt-1">Quando un cliente userà la chat del sito apparirà qui.</div>
    </div>
  <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($convs as $c):
        $lastMsg = row('SELECT role, content, created_at FROM chat_messages WHERE conversation_id = ? AND role != "admin_note" ORDER BY created_at DESC LIMIT 1', [$c['id']]);
      ?>
        <a href="/admin/chat.php?id=<?= e($c['id']) ?>" class="block card card-hover p-4">
          <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <div class="font-display font-bold"><?= e($c['customer_name'] ?: '· Cliente anonimo') ?></div>
                <?php if ($c['status'] === 'escalated'): ?><span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold">⚠ DA GESTIRE</span><?php endif; ?>
                <?php if ($c['status'] === 'resolved'): ?><span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">✓ risolta</span><?php endif; ?>
              </div>
              <?php if ($c['customer_phone']): ?>
                <div class="text-xs text-emerald-700 mt-0.5 flex items-center gap-1"><i data-lucide="phone" class="size-[11px]"></i> <?= e($c['customer_phone']) ?></div>
              <?php endif; ?>
              <?php if ($c['escalation_reason']): ?>
                <div class="text-xs text-amber-700 mt-1"><b>Domanda:</b> <?= e($c['escalation_reason']) ?></div>
              <?php endif; ?>
              <?php if ($lastMsg): ?>
                <div class="text-xs text-ink-500 mt-1 truncate"><?= $lastMsg['role'] === 'user' ? '👤' : '🤖' ?> <?= e(mb_substr($lastMsg['content'], 0, 140)) ?></div>
              <?php endif; ?>
            </div>
            <div class="text-right shrink-0">
              <div class="text-[11px] text-ink-500"><?= fmtDate($c['updated_at']) ?></div>
              <div class="text-[10px] text-ink-400"><?= (int)$c['message_count'] ?> msg</div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
