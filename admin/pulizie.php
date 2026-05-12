<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/cleaning.php';
requireAdmin();

// Verifica esistenza tabelle (utile dopo deploy, prima di setup.php)
$tablesReady = true;
try { val('SELECT 1 FROM cleaning_sessions LIMIT 1'); }
catch (Throwable $e) { $tablesReady = false; }

if ($tablesReady) {
  // Auto-allinea: ricostruisce le sessioni mancanti per tutte le booking attive future
  try {
    $missing = rows("SELECT b.id FROM bookings b
                     LEFT JOIN cleaning_sessions s ON s.booking_id = b.id
                     WHERE s.id IS NULL AND b.status NOT IN ('cancelled','rejected') AND b.check_out >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    foreach ($missing as $m) { try { ensureCleaningSession($m['id']); } catch (Throwable $e) {} }
  } catch (Throwable $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'regen_token') {
        $newToken = bin2hex(random_bytes(16));
        $exists = (int)val('SELECT COUNT(*) FROM settings WHERE setting_key = ?', ['cleaner_link_token']);
        if ($exists) q('UPDATE settings SET setting_value = ? WHERE setting_key = ?', [$newToken, 'cleaner_link_token']);
        else q('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)', ['cleaner_link_token', $newToken]);
        flash('Nuovo link generato — il precedente non funziona più');
    }
    if ($action === 'reopen') {
        q("UPDATE cleaning_sessions SET status = 'pending', completed_at = NULL WHERE id = ?", [$_POST['session_id']]);
        flash('Sessione riaperta');
    }
    redirect('/admin/pulizie.php');
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$weekEnd = date('Y-m-d', strtotime('+7 days'));

$sessions = [];
if ($tablesReady) {
  try {
    $sessions = rows("SELECT s.*, a.name AS apartment_name, a.address AS apartment_address, b.code AS booking_code, c.name AS customer_name,
                      (SELECT COUNT(*) FROM cleaning_session_items WHERE session_id = s.id) AS total_items,
                      (SELECT COUNT(*) FROM cleaning_session_items WHERE session_id = s.id AND checked = 1) AS done_items
                      FROM cleaning_sessions s
                      JOIN apartments a ON a.id = s.apartment_id
                      LEFT JOIN bookings b ON b.id = s.booking_id
                      LEFT JOIN customers c ON c.id = b.customer_id
                      WHERE s.scheduled_date >= ?
                      ORDER BY s.scheduled_date ASC, a.name ASC", [date('Y-m-d', strtotime('-7 days'))]);
  } catch (Throwable $e) {}
}

$grouped = ['oggi' => [], 'domani' => [], 'settimana' => [], 'completate' => [], 'in_ritardo' => []];
foreach ($sessions as $s) {
    if ($s['status'] === 'done') { $grouped['completate'][] = $s; continue; }
    if ($s['scheduled_date'] < $today) { $grouped['in_ritardo'][] = $s; continue; }
    if ($s['scheduled_date'] === $today) { $grouped['oggi'][] = $s; continue; }
    if ($s['scheduled_date'] === $tomorrow) { $grouped['domani'][] = $s; continue; }
    if ($s['scheduled_date'] <= $weekEnd) { $grouped['settimana'][] = $s; continue; }
}

$cleanerLink = $tablesReady ? cleanerLinkUrl() : '';
$totalTasks = 0;
if ($tablesReady) {
  try { $totalTasks = (int)val('SELECT COUNT(*) FROM cleaning_tasks WHERE active = 1'); } catch (Throwable $e) {}
}

$title = 'Pulizie';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-end justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold">Pulizie</h1>
      <p class="text-ink-500 text-sm mt-1">Gestisci la checklist e condividi il link con la signora delle pulizie.</p>
    </div>
    <?php if ($tablesReady): ?>
    <div class="flex gap-2 flex-wrap">
      <a href="/admin/pulizie-checklist.php" class="btn-outline"><i data-lucide="list-checks" class="size-[16px]"></i> Checklist (<?= $totalTasks ?> voci)</a>
    </div>
    <?php endif; ?>
  </div>

  <?php if (!$tablesReady): ?>
    <div class="card p-6 border-amber-300 bg-amber-50 dark:bg-amber-500/10">
      <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="size-[22px] text-amber-700 shrink-0 mt-0.5"></i>
        <div class="flex-1">
          <h3 class="font-display font-bold text-amber-900 dark:text-amber-200">Setup richiesto</h3>
          <p class="text-sm text-amber-900/80 dark:text-amber-200/80 mt-1">Le tabelle del sistema pulizie non sono ancora state create. Apri <strong>una sola volta</strong> questo URL per inizializzare il database:</p>
          <a href="/setup.php" target="_blank" class="btn-primary mt-3"><i data-lucide="play" class="size-[14px]"></i> Esegui /setup.php</a>
          <p class="text-xs text-amber-800/70 mt-2">Crea 3 tabelle, carica 15 voci checklist e genera il token segreto. Dopo, ricarica questa pagina.</p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($tablesReady): ?>
  <!-- LINK CONDIVISIBILE -->
  <div class="card p-5 sm:p-6 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-500/10 dark:to-transparent border-emerald-200 dark:border-emerald-500/30" x-data="{copied:false}">
    <div class="flex items-start gap-3">
      <span class="h-11 w-11 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shadow-md shrink-0"><i data-lucide="link" class="size-[20px]"></i></span>
      <div class="flex-1 min-w-0">
        <div class="font-display font-bold text-base">Link per la signora delle pulizie</div>
        <p class="text-xs text-ink-500 mt-0.5">Condividilo via WhatsApp. Lei vedrà solo le pulizie del giorno (no accesso admin).</p>
        <div class="mt-3 flex flex-wrap gap-2 items-center">
          <input type="text" readonly value="<?= e($cleanerLink) ?>" class="input flex-1 min-w-[200px] text-xs sm:text-sm font-mono bg-white" onclick="this.select()">
          <button @click="navigator.clipboard.writeText('<?= e($cleanerLink) ?>').then(()=>{copied=true; setTimeout(()=>copied=false,1500)})" class="btn-secondary text-sm">
            <i data-lucide="copy" class="size-[14px]"></i> <span x-show="!copied">Copia</span><span x-show="copied" x-cloak>✓ Copiato</span>
          </button>
          <a href="https://wa.me/?text=<?= e(rawurlencode("Ciao! Ti mando il link delle pulizie. Salvalo nei preferiti / Aggiungi alla schermata Home dal browser:\n\n" . $cleanerLink)) ?>" target="_blank" rel="noopener" class="btn-primary text-sm bg-emerald-500 hover:bg-emerald-600 border-emerald-600">
            <i data-lucide="message-circle" class="size-[14px]"></i> Invia su WhatsApp
          </a>
          <form method="post" onsubmit="return confirm('Generare un nuovo link? Il precedente smetterà di funzionare.')" class="inline">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="regen_token">
            <button class="btn-ghost text-sm text-ink-500 hover:text-red-600" title="Rigenera link"><i data-lucide="refresh-cw" class="size-[14px]"></i></button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php endif; ?>

  <?php if ($tablesReady):
    $sections = [
      ['in_ritardo', 'In ritardo',   'alert-triangle', 'border-red-300 dark:border-red-500/40 bg-red-50/40 dark:bg-red-500/5'],
      ['oggi',       'Oggi',          'sparkles',       'border-brand-300 dark:border-brand-500/40 bg-brand-50/40 dark:bg-brand-500/5'],
      ['domani',     'Domani',        'sun',            'border-amber-200 dark:border-amber-500/30'],
      ['settimana',  'Prossimi giorni','calendar',     'border-ink-200 dark:border-ink-700'],
      ['completate', 'Completate',    'check-circle-2', 'border-emerald-200 dark:border-emerald-500/30'],
    ];
    foreach ($sections as $sec):
      $list = $grouped[$sec[0]];
      if (!$list && $sec[0] !== 'oggi') continue;
  ?>
    <div class="space-y-2">
      <div class="flex items-center gap-2 mt-4">
        <i data-lucide="<?= $sec[2] ?>" class="size-[16px] text-ink-500"></i>
        <h2 class="font-display font-bold"><?= e($sec[1]) ?></h2>
        <span class="text-xs text-ink-500">(<?= count($list) ?>)</span>
      </div>
      <?php if (!$list): ?>
        <div class="card p-5 text-center text-sm text-ink-500">Nessuna pulizia oggi.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <?php foreach ($list as $s):
            $pct = $s['total_items'] ? round(100 * $s['done_items'] / $s['total_items']) : 0;
          ?>
            <div class="card p-4 sm:p-5 <?= $sec[3] ?>">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <div class="text-[10px] uppercase tracking-wider text-ink-500 font-semibold"><?= fmtDate($s['scheduled_date']) ?></div>
                  <div class="font-display font-bold truncate"><?= e($s['apartment_name']) ?></div>
                  <?php if ($s['apartment_address']): ?><div class="text-xs text-ink-500 mt-0.5 truncate"><?= e($s['apartment_address']) ?></div><?php endif; ?>
                </div>
                <span class="<?= $s['status'] === 'done' ? 'badge-success' : ($s['status'] === 'in_progress' ? 'bg-amber-100 text-amber-700' : 'badge-soft') ?> text-[10px] capitalize shrink-0 px-2 py-1 rounded-full"><?= str_replace('_',' ',e($s['status'])) ?></span>
              </div>
              <?php if ($s['customer_name']): ?>
                <div class="mt-2 text-xs text-ink-500 flex items-center gap-1.5">
                  <i data-lucide="user" class="size-[12px]"></i> Ospite: <span class="text-ink-700 dark:text-ink-300 font-medium"><?= e($s['customer_name']) ?></span>
                </div>
              <?php endif; ?>
              <div class="mt-3">
                <div class="flex items-center justify-between text-[11px] text-ink-500 mb-1">
                  <span><?= (int)$s['done_items'] ?> / <?= (int)$s['total_items'] ?> spunte</span>
                  <span class="tabular-nums"><?= $pct ?>%</span>
                </div>
                <div class="h-1.5 bg-ink-100 dark:bg-ink-800 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500 transition-all" style="width:<?= $pct ?>%"></div>
                </div>
              </div>
              <?php if (!empty($s['cleaner_notes'])): ?>
                <div class="mt-3 p-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900">
                  <i data-lucide="message-square" class="size-[12px] inline-block mr-1"></i>
                  <?= e($s['cleaner_notes']) ?>
                </div>
              <?php endif; ?>
              <div class="mt-3 flex gap-2">
                <a href="/admin/pulizia.php?id=<?= e($s['id']) ?>" class="btn-outline text-sm flex-1 justify-center"><i data-lucide="eye" class="size-[14px]"></i> Dettagli</a>
                <?php if ($s['status'] === 'done'): ?>
                  <form method="post" class="inline">
                    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="reopen">
                    <input type="hidden" name="session_id" value="<?= e($s['id']) ?>">
                    <button class="btn-ghost text-sm text-ink-500" title="Riapri"><i data-lucide="rotate-ccw" class="size-[14px]"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
