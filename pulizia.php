<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/cleaning.php';

$token = $_GET['t'] ?? '';
$valid = $token && hash_equals(cleanerToken(), $token);
if (!$valid) { http_response_code(401); echo 'Link non valido'; exit; }

$sid = $_GET['id'] ?? '';
$s = row("SELECT s.*, a.name AS apartment_name, a.address AS apartment_address, a.check_out_time, a.rules,
          a.block_number, a.map_x, a.map_y, a.cleaner_directions,
          b.code AS booking_code, b.check_in, b.check_out, b.guests, c.name AS customer_name
          FROM cleaning_sessions s
          JOIN apartments a ON a.id = s.apartment_id
          LEFT JOIN bookings b ON b.id = s.booking_id
          LEFT JOIN customers c ON c.id = b.customer_id
          WHERE s.id = ?", [$sid]);
if (!$s) { http_response_code(404); echo 'Sessione non trovata'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_item') {
        $item = row('SELECT * FROM cleaning_session_items WHERE id = ? AND session_id = ?', [$_POST['item_id'], $s['id']]);
        if ($item) {
            $newVal = $item['checked'] ? 0 : 1;
            $when = $newVal ? date('Y-m-d H:i:s') : null;
            q('UPDATE cleaning_session_items SET checked = ?, checked_at = ? WHERE id = ?', [$newVal, $when, $item['id']]);
            // se la sessione era 'pending' la metto a 'in_progress' al primo tocco
            if ($s['status'] === 'pending' && $newVal === 1) {
                q("UPDATE cleaning_sessions SET status = 'in_progress', started_at = NOW() WHERE id = ?", [$s['id']]);
            }
            echo json_encode(['ok' => true, 'checked' => $newVal]);
            exit;
        }
        echo json_encode(['ok' => false]); exit;
    }
    if ($action === 'save_notes') {
        q('UPDATE cleaning_sessions SET cleaner_notes = ? WHERE id = ?', [$_POST['notes'] ?? '', $s['id']]);
        echo json_encode(['ok' => true]); exit;
    }
    if ($action === 'complete') {
        q("UPDATE cleaning_sessions SET status = 'done', completed_at = NOW(), cleaner_notes = ? WHERE id = ?", [$_POST['notes'] ?? $s['cleaner_notes'], $s['id']]);
        // Notifica admin
        try {
            q('INSERT INTO notifications (id, type, title, body, link) VALUES (?, ?, ?, ?, ?)',
                [newId(), 'cleaning_done', 'Pulizia completata: ' . $s['apartment_name'],
                 'Sessione del ' . fmtDate($s['scheduled_date']) . ($_POST['notes'] ? ' · Note: ' . substr($_POST['notes'],0,80) : ''),
                 '/admin/pulizia.php?id=' . $s['id']]);
        } catch (Throwable $e) {}
        header('Location: /pulizie.php?t=' . urlencode($token) . '&done=' . urlencode($s['id']));
        exit;
    }
}

$items = rows('SELECT * FROM cleaning_session_items WHERE session_id = ? ORDER BY position ASC', [$s['id']]);

$title = 'Pulizia · ' . $s['apartment_name'];
$pwaManifest = '/manifest-cleaner.php?t=' . rawurlencode($token);
$cleanerToken = $token;
// Pagina di dettaglio: NON evidenzio una tab specifica nella bottom nav
$cleanerActiveTab = '';
require __DIR__ . '/partials/head.php';
?>
<div class="min-h-screen bg-ink-50/40 dark:bg-ink-950 pb-32">
  <header class="bg-white dark:bg-ink-900 border-b border-ink-100 dark:border-ink-800/80 sticky top-0 z-30">
    <div class="container-narrow py-3 px-4 flex items-center gap-3">
      <a href="/pulizie.php?t=<?= e($token) ?>" class="btn-ghost p-2 -ml-2"><i data-lucide="chevron-left" class="size-[20px]"></i></a>
      <div class="flex-1 min-w-0">
        <div class="font-display font-bold leading-tight truncate"><?= e($s['apartment_name']) ?></div>
        <div class="text-xs text-ink-500 mt-0.5"><?= fmtDate($s['scheduled_date']) ?></div>
      </div>
    </div>
  </header>

  <main class="container-narrow py-4 px-4 space-y-4">
    <?php if ($s['apartment_address'] || $s['check_out_time'] || $s['customer_name']): ?>
      <div class="card p-4 sm:p-5 space-y-2 text-sm">
        <?php if ($s['apartment_address']): ?>
          <a href="https://www.google.com/maps/search/?api=1&query=<?= e(urlencode($s['apartment_address'])) ?>" target="_blank" rel="noopener" class="flex items-center gap-2 text-ink-700 dark:text-ink-200 hover:text-brand-600">
            <i data-lucide="map-pin" class="size-[16px] text-brand-600 shrink-0"></i> <span class="truncate"><?= e($s['apartment_address']) ?></span>
            <i data-lucide="external-link" class="size-[12px] opacity-50 ml-auto"></i>
          </a>
        <?php endif; ?>
        <?php if ($s['check_out_time']): ?>
          <div class="flex items-center gap-2 text-ink-700 dark:text-ink-200"><i data-lucide="clock" class="size-[16px] text-brand-600 shrink-0"></i> Check-out ospite: <strong><?= e($s['check_out_time']) ?></strong></div>
        <?php endif; ?>
        <?php if ($s['customer_name']): ?>
          <div class="flex items-center gap-2 text-ink-700 dark:text-ink-200"><i data-lucide="user" class="size-[16px] text-brand-600 shrink-0"></i> Ospite uscente: <?= e($s['customer_name']) ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($s['map_x'] !== null && $s['map_y'] !== null): ?>
      <!-- POSIZIONE NEL RESORT -->
      <div class="card overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-ink-100 dark:border-ink-800 bg-sky-50/50 dark:bg-sky-500/5 flex items-center gap-3">
          <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-sky-400 to-sky-600 text-white flex items-center justify-center shrink-0 shadow-md"><i data-lucide="map-pinned" class="size-[20px]"></i></span>
          <div class="min-w-0 flex-1">
            <div class="font-display font-bold">Posizione nel resort</div>
            <div class="text-xs text-ink-500 mt-0.5">Domina Coral Bay <?= $s['block_number'] ? '· Blocco <strong class="text-sky-700">' . e($s['block_number']) . '</strong>' : '' ?></div>
          </div>
        </div>
        <div class="relative bg-amber-50 dark:bg-ink-900" style="aspect-ratio: 2.05 / 1;">
          <img src="/assets/resort-map.jpg?v=4" alt="Mappa resort Domina Coral Bay" class="absolute inset-0 w-full h-full object-contain" draggable="false">
          <!-- Pulse ring -->
          <div class="absolute -translate-x-1/2 -translate-y-1/2 pointer-events-none" style="left: <?= round((float)$s['map_x']*100,2) ?>%; top: <?= round((float)$s['map_y']*100,2) ?>%;">
            <span class="block h-16 w-16 rounded-full bg-red-500/30 animate-ping"></span>
            <span class="block absolute inset-0 m-auto h-6 w-6 rounded-full bg-red-500/60"></span>
          </div>
          <!-- Marker pin -->
          <div class="absolute -translate-x-1/2 -translate-y-full pointer-events-none" style="left: <?= round((float)$s['map_x']*100,2) ?>%; top: <?= round((float)$s['map_y']*100,2) ?>%;">
            <div class="relative" style="filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5));">
              <svg width="52" height="66" viewBox="0 0 44 56">
                <defs>
                  <linearGradient id="pinGradView" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0" stop-color="#f43f5e"/>
                    <stop offset="1" stop-color="#9f1239"/>
                  </linearGradient>
                </defs>
                <path d="M22 0 C 9 0, 0 10, 0 22 C 0 36, 22 56, 22 56 C 22 56, 44 36, 44 22 C 44 10, 35 0, 22 0 Z" fill="url(#pinGradView)" stroke="#fff" stroke-width="2.5"/>
                <circle cx="22" cy="20" r="8" fill="#fff"/>
                <text x="22" y="24" text-anchor="middle" font-family="Inter,system-ui" font-size="11" font-weight="800" fill="#9f1239"><?= e((string)$s['block_number']) ?: '!' ?></text>
              </svg>
            </div>
          </div>
          <!-- Banner blocco -->
          <?php if ($s['block_number']): ?>
            <div class="absolute top-3 left-3 bg-white/95 dark:bg-ink-900/95 backdrop-blur px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1.5">
              <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
              <span class="text-xs font-display font-bold text-ink-800 dark:text-ink-100">Blocco <?= e($s['block_number']) ?></span>
            </div>
          <?php endif; ?>
        </div>
        <?php if (trim($s['cleaner_directions'] ?? '')): ?>
          <div class="p-4 sm:p-5 bg-amber-50/60 dark:bg-amber-500/5 border-t border-amber-100 dark:border-amber-500/20">
            <div class="flex items-start gap-3">
              <span class="h-8 w-8 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 flex items-center justify-center shrink-0"><i data-lucide="navigation" class="size-[16px]"></i></span>
              <div class="min-w-0 flex-1">
                <div class="font-display font-bold text-sm text-amber-900 dark:text-amber-200">Come arrivarci</div>
                <p class="text-sm text-amber-950/90 dark:text-amber-100/90 mt-1 whitespace-pre-line leading-relaxed"><?= e($s['cleaner_directions']) ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php elseif (trim($s['cleaner_directions'] ?? '')): ?>
      <!-- Solo indicazioni testuali, senza mappa -->
      <div class="card p-4 sm:p-5 bg-amber-50/60 dark:bg-amber-500/5 border-amber-200 dark:border-amber-500/30">
        <div class="flex items-start gap-3">
          <span class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0"><i data-lucide="navigation" class="size-[18px]"></i></span>
          <div class="min-w-0 flex-1">
            <div class="font-display font-bold text-amber-900 dark:text-amber-200">Come arrivarci <?= $s['block_number'] ? '· Blocco ' . e($s['block_number']) : '' ?></div>
            <p class="text-sm text-amber-950/90 dark:text-amber-100/90 mt-1 whitespace-pre-line leading-relaxed"><?= e($s['cleaner_directions']) ?></p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- CHECKLIST -->
    <div class="card overflow-hidden" x-data="{ done: <?= (int)val('SELECT COUNT(*) FROM cleaning_session_items WHERE session_id=? AND checked=1', [$s['id']]) ?>, total: <?= count($items) ?> }">
      <div class="p-4 sm:p-5 border-b border-ink-100 dark:border-ink-800 bg-emerald-50/40 dark:bg-emerald-500/5">
        <div class="flex items-center justify-between mb-2">
          <h2 class="font-display font-bold">Checklist</h2>
          <span class="text-sm font-bold tabular-nums" x-text="done + '/' + total"></span>
        </div>
        <div class="h-2 bg-white dark:bg-ink-800 rounded-full overflow-hidden">
          <div class="h-full bg-emerald-500 transition-all" :style="'width:' + (total ? Math.round(100*done/total) : 0) + '%'"></div>
        </div>
      </div>
      <ul class="divide-y divide-ink-100 dark:divide-ink-800">
        <?php foreach ($items as $it): ?>
          <li x-data="{ checked: <?= $it['checked'] ? 'true' : 'false' ?>, busy: false }"
              @click="if(busy) return; busy=true; const prev=checked; checked=!checked; if(checked){done++}else{done--};
                       fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
                          body:'action=toggle_item&item_id=<?= e($it['id']) ?>'})
                       .then(r=>r.json()).then(j=>{ if(!j.ok){ checked=prev; if(prev){done++}else{done--}; } }).finally(()=>busy=false)"
              class="p-3 sm:p-4 flex items-start gap-3 cursor-pointer active:bg-ink-50 dark:active:bg-ink-800/40 transition select-none"
              :class="checked ? 'bg-emerald-50/60 dark:bg-emerald-500/10' : ''">
            <span class="mt-0.5 h-6 w-6 rounded-md flex items-center justify-center shrink-0 transition"
                  :class="checked ? 'bg-emerald-500 border-2 border-emerald-500' : 'border-2 border-ink-300 dark:border-ink-600'">
              <i data-lucide="check" class="size-[14px] text-white" x-show="checked" x-cloak></i>
            </span>
            <div class="flex-1 min-w-0">
              <div class="text-[15px] sm:text-base leading-tight transition" :class="checked ? 'line-through text-ink-400' : ''"><?= e($it['label_snapshot']) ?></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- NOTE -->
    <div class="card p-4 sm:p-5" x-data="{ saved:false, busy:false, txt: <?= json_encode($s['cleaner_notes'] ?? '') ?> }">
      <h3 class="font-display font-bold flex items-center gap-2"><i data-lucide="message-square" class="size-[18px] text-amber-700"></i> Note per Patrizia</h3>
      <p class="text-xs text-ink-500 mt-1">Segnala oggetti dimenticati, guasti, cose da comprare, qualunque cosa.</p>
      <textarea x-model="txt" rows="4" class="input mt-3 resize-y" placeholder="es. Manca un cuscino. Lampadina rotta in salotto. Frigo da scongelare la prossima volta."
        @input.debounce.700ms="busy=true; saved=false;
          fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
              body:'action=save_notes&notes=' + encodeURIComponent(txt)})
          .then(r=>r.json()).then(()=>{ saved=true; setTimeout(()=>saved=false, 1500); }).finally(()=>busy=false)"></textarea>
      <div class="text-xs text-ink-400 mt-1 h-4">
        <span x-show="busy" x-cloak>Salvataggio…</span>
        <span x-show="saved" x-cloak class="text-emerald-600">✓ Salvato</span>
      </div>
    </div>
  </main>

  <!-- BOTTOM CTA: Completata -->
  <?php if ($s['status'] !== 'done'): ?>
    <div class="fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-ink-900/95 backdrop-blur-xl border-t border-ink-100 dark:border-ink-800/80 p-3 sm:p-4">
      <form method="post" class="container-narrow" onsubmit="this.querySelector('[name=notes]').value = document.querySelector('textarea').value;">
        <input type="hidden" name="action" value="complete">
        <input type="hidden" name="notes" value="<?= e($s['cleaner_notes'] ?? '') ?>">
        <button type="submit" class="btn-primary w-full h-12 text-base bg-emerald-500 hover:bg-emerald-600 border-emerald-600">
          <i data-lucide="check-circle-2" class="size-[20px]"></i> Pulizia completata
        </button>
      </form>
    </div>
  <?php else: ?>
    <div class="fixed bottom-0 inset-x-0 z-40 bg-emerald-50 dark:bg-emerald-500/10 border-t border-emerald-200 dark:border-emerald-500/30 p-4 text-center text-emerald-700 dark:text-emerald-300 font-medium">
      ✓ Pulizia completata <?= $s['completed_at'] ? fmtDateTime($s['completed_at']) : '' ?>
    </div>
  <?php endif; ?>
</div>
<script>if (window.lucide) lucide.createIcons();</script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
