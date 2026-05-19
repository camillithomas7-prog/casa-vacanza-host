<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/notify.php';
requireAdmin();

$logFile = __DIR__ . '/../uploads/logs/push.log';
$log = file_exists($logFile) ? file_get_contents($logFile) : '';
// Mostra solo ultimi 200 righe per non far esplodere la pagina
$lines = $log ? array_slice(explode("\n", trim($log)), -200) : [];

$subs = [];
try { $subs = rows('SELECT * FROM push_subscriptions ORDER BY created_at DESC'); } catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    if (($_POST['action'] ?? '') === 'clear_log' && file_exists($logFile)) {
        @file_put_contents($logFile, '');
        flash('Log push svuotato');
    } elseif (($_POST['action'] ?? '') === 'test_push') {
        $r = sendPushToAll([
            'id' => 'dbg-' . time(),
            'type' => 'test',
            'title' => '🔧 Test push debug',
            'body' => 'Inviata alle ' . date('H:i:s') . ' su ' . count($subs) . ' dispositiv' . (count($subs) === 1 ? 'o' : 'i'),
            'link' => '/admin/push-debug.php',
        ]);
        flash("Test inviato: {$r['sent']} consegnate, {$r['errors']} errori");
    } elseif (($_POST['action'] ?? '') === 'delete_sub') {
        q('DELETE FROM push_subscriptions WHERE id = ?', [$_POST['sub_id']]);
        flash('Subscription eliminata');
    }
    redirect('/admin/push-debug.php');
}

$title = 'Debug notifiche push';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div>
    <h1 class="font-serif text-3xl font-semibold">Debug notifiche push</h1>
    <p class="text-ink-500 mt-1">Diagnostica perché le push non arrivano. Visibile solo agli admin.</p>
  </div>

  <div class="card p-4">
    <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
      <h3 class="font-display font-bold">Dispositivi iscritti <span class="text-ink-400 font-normal">(<?= count($subs) ?>)</span></h3>
      <form method="post" class="inline">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <button name="action" value="test_push" class="btn-primary text-sm">🔔 Invia push di test a tutti</button>
      </form>
    </div>
    <?php if (!$subs): ?>
      <p class="text-sm text-ink-500">Nessun dispositivo iscritto. Apri il sito da iPhone/Mac, vai in <code>/admin/notifiche.php</code> e abilita le notifiche.</p>
    <?php else: ?>
      <div class="space-y-2">
        <?php foreach ($subs as $s):
          $endpoint = parse_url($s['endpoint'], PHP_URL_HOST) ?: $s['endpoint'];
          $service = 'Sconosciuto';
          if (strpos($endpoint, 'apple.com') !== false) $service = '🍎 Apple (iOS/Safari)';
          elseif (strpos($endpoint, 'google.com') !== false || strpos($endpoint, 'fcm.googleapis') !== false) $service = '🤖 Google FCM (Android/Chrome)';
          elseif (strpos($endpoint, 'mozilla') !== false) $service = '🦊 Mozilla (Firefox)';
          elseif (strpos($endpoint, 'microsoft') !== false || strpos($endpoint, 'windows') !== false) $service = '🪟 Microsoft (Edge/Windows)';
          $ua = $s['user_agent'] ?: '—';
        ?>
          <div class="p-3 rounded-lg bg-ink-50 dark:bg-ink-800/50 flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1 text-xs">
              <div class="font-bold text-sm"><?= $service ?></div>
              <div class="text-ink-500 mt-0.5 truncate"><?= e($endpoint) ?></div>
              <div class="text-ink-400 mt-0.5 truncate" title="<?= e($ua) ?>">UA: <?= e(mb_substr($ua, 0, 80)) ?></div>
              <div class="text-ink-400 mt-0.5">Iscritta: <?= fmtDate($s['created_at']) ?></div>
            </div>
            <form method="post" onsubmit="return confirm('Eliminare questa subscription?')">
              <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
              <input type="hidden" name="sub_id" value="<?= e($s['id']) ?>">
              <button name="action" value="delete_sub" class="btn-ghost text-xs text-red-600 hover:bg-red-50">Elimina</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card p-4">
    <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
      <h3 class="font-display font-bold">Log push <span class="text-ink-400 font-normal">(ultime <?= count($lines) ?> righe)</span></h3>
      <form method="post" class="inline">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <button name="action" value="clear_log" class="btn-ghost text-xs text-red-600">Svuota log</button>
      </form>
    </div>
    <?php if (!$lines): ?>
      <p class="text-sm text-ink-500">Nessun log. Sarà popolato alla prossima notifica push inviata.</p>
    <?php else: ?>
      <pre class="bg-ink-900 text-emerald-300 text-xs p-4 rounded-lg max-h-[500px] overflow-auto whitespace-pre-wrap"><?= e(implode("\n", $lines)) ?></pre>
    <?php endif; ?>
  </div>

  <div class="card p-4 bg-sky-50 border-sky-200 dark:bg-sky-500/10 dark:border-sky-500/30">
    <h3 class="font-display font-bold mb-2">📱 Checklist iOS</h3>
    <ol class="text-sm space-y-1 list-decimal list-inside text-ink-700 dark:text-ink-300">
      <li>iOS ≥ 16.4 (le push web non funzionano su versioni precedenti)</li>
      <li><b>PWA installata sulla Home</b> (Safari → Condividi → Aggiungi a Home). Le push iOS NON funzionano nel browser Safari, solo nella PWA installata.</li>
      <li>Aperta dalla Home almeno una volta dopo l'installazione</li>
      <li>Permessi notifiche concessi (popup all'apertura)</li>
      <li>Sopra deve apparire <b>🍎 Apple (iOS/Safari)</b> tra i dispositivi. Se non c'è = la subscription non è arrivata al server.</li>
      <li>Se hai installato la PWA e non vedi l'iscrizione → apri /admin/notifiche.php dentro la PWA e ri-abilita le notifiche</li>
    </ol>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
