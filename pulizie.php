<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/cleaning.php';

$token = $_GET['t'] ?? '';
$valid = $token && hash_equals(cleanerToken(), $token);
if (!$valid) {
    http_response_code(401);
    $title = 'Accesso non valido';
    require __DIR__ . '/partials/head.php';
    ?>
    <div class="container-narrow py-20">
      <div class="card p-10 text-center">
        <div class="h-14 w-14 mx-auto rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mb-3"><i data-lucide="lock" class="size-[24px]"></i></div>
        <h1 class="font-serif text-2xl font-semibold">Link non valido</h1>
        <p class="text-ink-500 mt-2">Chiedi a Patrizia di mandarti il nuovo link.</p>
      </div>
    </div>
    <?php
    require __DIR__ . '/partials/site-footer.php';
    exit;
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$weekEnd = date('Y-m-d', strtotime('+7 days'));

$sessions = rows("SELECT s.*, a.name AS apartment_name, a.address AS apartment_address,
                  (SELECT COUNT(*) FROM cleaning_session_items WHERE session_id = s.id) AS total_items,
                  (SELECT COUNT(*) FROM cleaning_session_items WHERE session_id = s.id AND checked = 1) AS done_items
                  FROM cleaning_sessions s
                  JOIN apartments a ON a.id = s.apartment_id
                  WHERE s.scheduled_date >= ? AND s.scheduled_date <= ? AND s.status != 'done'
                  ORDER BY s.scheduled_date ASC, a.name ASC", [date('Y-m-d', strtotime('-3 days')), $weekEnd]);

$grouped = ['in_ritardo' => [], 'oggi' => [], 'domani' => [], 'settimana' => []];
foreach ($sessions as $s) {
    if ($s['scheduled_date'] < $today) $grouped['in_ritardo'][] = $s;
    elseif ($s['scheduled_date'] === $today) $grouped['oggi'][] = $s;
    elseif ($s['scheduled_date'] === $tomorrow) $grouped['domani'][] = $s;
    else $grouped['settimana'][] = $s;
}

$doneRecent = rows("SELECT s.*, a.name AS apartment_name
                    FROM cleaning_sessions s JOIN apartments a ON a.id = s.apartment_id
                    WHERE s.status = 'done' AND s.completed_at >= ?
                    ORDER BY s.completed_at DESC LIMIT 5", [date('Y-m-d', strtotime('-7 days'))]);

$vapidPub = setting('vapid_public') ?: '';

$title = 'Pulizie';
$metaDesc = 'Lista pulizie del giorno';
$pwaManifest = '/manifest-cleaner.php?t=' . rawurlencode($token);
require __DIR__ . '/partials/head.php';
?>
<div class="min-h-screen bg-ink-50/40 dark:bg-ink-950">
  <header class="bg-white dark:bg-ink-900 border-b border-ink-100 dark:border-ink-800/80 sticky top-0 z-30">
    <div class="container-narrow py-3 px-4 flex items-center gap-3">
      <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white flex items-center justify-center shadow-md"><i data-lucide="sparkles" class="size-[20px]"></i></span>
      <div>
        <div class="font-display font-bold leading-none">Pulizie del giorno</div>
        <div class="text-xs text-ink-500 mt-0.5"><?= fmtDate($today) ?></div>
      </div>
    </div>
  </header>

  <main class="container-narrow py-4 px-4 space-y-5 pb-20">
    <!-- Bottone "Attiva notifiche" — best-effort, sparisce quando già concesso -->
    <?php if ($vapidPub): ?>
    <div id="push-bar" class="card p-3 sm:p-4 hidden bg-amber-50 border-amber-200" x-data x-init="
      try {
        const state = (Notification.permission || 'default');
        if (state === 'granted' || state === 'denied') {
          if (state === 'granted') document.getElementById('push-on').classList.remove('hidden');
          if (state === 'denied')  document.getElementById('push-denied').classList.remove('hidden');
        } else {
          document.getElementById('push-cta').classList.remove('hidden');
        }
        $el.classList.remove('hidden');
      } catch(e){}
    ">
      <div id="push-cta" class="hidden flex items-start gap-3">
        <i data-lucide="bell-ring" class="size-[20px] text-amber-700 shrink-0 mt-0.5"></i>
        <div class="flex-1 min-w-0">
          <div class="font-display font-bold text-sm text-amber-900">Ricevi notifica 24h prima</div>
          <p class="text-xs text-amber-800/80 mt-0.5">Ti avvisiamo il giorno prima per ogni appartamento da pulire.</p>
        </div>
        <button type="button" id="push-enable-btn" class="btn-primary text-sm bg-emerald-500 hover:bg-emerald-600 border-emerald-600">
          <i data-lucide="bell" class="size-[14px]"></i> Attiva
        </button>
      </div>
      <div id="push-on" class="hidden text-sm text-emerald-700 flex items-center gap-2">
        <i data-lucide="bell-check" class="size-[16px]"></i> Notifiche attive su questo dispositivo
      </div>
      <div id="push-denied" class="hidden text-sm text-red-700 flex items-center gap-2">
        <i data-lucide="bell-off" class="size-[16px]"></i> Notifiche bloccate. Abilita dalle impostazioni del browser.
      </div>
    </div>
    <?php endif; ?>
    <?php
      $sections = [
        ['in_ritardo', 'In ritardo', 'alert-triangle', 'text-red-700', 'border-red-300 bg-red-50/60 dark:bg-red-500/5'],
        ['oggi',       'Oggi',       'sparkles',       'text-brand-700','border-brand-200 bg-brand-50/60 dark:bg-brand-500/5'],
        ['domani',     'Domani',     'sun',            'text-amber-700','border-amber-200'],
        ['settimana',  'Prossimi giorni','calendar',  'text-ink-700',  'border-ink-200'],
      ];
      foreach ($sections as $sec):
        $list = $grouped[$sec[0]];
        if (!$list && $sec[0] !== 'oggi') continue;
    ?>
      <section>
        <div class="flex items-center gap-2 mb-2">
          <i data-lucide="<?= $sec[2] ?>" class="size-[16px] <?= $sec[3] ?>"></i>
          <h2 class="font-display font-bold <?= $sec[3] ?>"><?= e($sec[1]) ?></h2>
          <span class="text-xs text-ink-500">(<?= count($list) ?>)</span>
        </div>
        <?php if (!$list): ?>
          <div class="card p-6 text-center text-sm text-ink-500">Niente pulizie per oggi. Goditi la giornata! ☀️</div>
        <?php else: ?>
          <div class="space-y-2">
            <?php foreach ($list as $s):
              $pct = $s['total_items'] ? round(100 * $s['done_items'] / $s['total_items']) : 0;
            ?>
              <a href="/pulizia.php?t=<?= e($token) ?>&id=<?= e($s['id']) ?>" class="block card card-hover p-4 <?= $sec[4] ?>">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0 flex-1">
                    <div class="font-display font-bold text-base sm:text-lg truncate"><?= e($s['apartment_name']) ?></div>
                    <?php if ($s['apartment_address']): ?>
                      <div class="text-xs text-ink-500 mt-0.5 truncate flex items-center gap-1"><i data-lucide="map-pin" class="size-[12px]"></i> <?= e($s['apartment_address']) ?></div>
                    <?php endif; ?>
                  </div>
                  <span class="<?= $s['status'] === 'in_progress' ? 'bg-amber-100 text-amber-800' : 'bg-ink-100 text-ink-700' ?> text-[10px] px-2 py-1 rounded-full capitalize shrink-0"><?= str_replace('_',' ',e($s['status'])) ?></span>
                </div>
                <div class="mt-3">
                  <div class="flex items-center justify-between text-[11px] text-ink-500 mb-1">
                    <span><?= (int)$s['done_items'] ?> / <?= (int)$s['total_items'] ?> spunte</span>
                    <span class="tabular-nums"><?= $pct ?>%</span>
                  </div>
                  <div class="h-2 bg-ink-100 dark:bg-ink-800 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 transition-all" style="width:<?= $pct ?>%"></div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <?php if ($doneRecent): ?>
      <section class="mt-8">
        <div class="flex items-center gap-2 mb-2 text-ink-400">
          <i data-lucide="check-circle-2" class="size-[16px]"></i>
          <h2 class="font-display font-bold">Completate di recente</h2>
        </div>
        <ul class="space-y-1.5">
          <?php foreach ($doneRecent as $d): ?>
            <li class="text-sm text-ink-500 flex items-center justify-between p-2 rounded-lg bg-emerald-50/40 dark:bg-emerald-500/5">
              <span class="truncate"><i data-lucide="check" class="size-[12px] inline-block text-emerald-600 mr-1"></i> <?= e($d['apartment_name']) ?></span>
              <span class="text-xs"><?= fmtDate($d['scheduled_date']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>
  </main>
</div>
<script>if (window.lucide) lucide.createIcons();</script>
<?php if ($vapidPub): ?>
<script>
(function(){
  const VAPID_PUBLIC = <?= json_encode($vapidPub) ?>;
  const TOKEN = <?= json_encode($token) ?>;
  function urlBase64ToUint8Array(b64) {
    const pad = '='.repeat((4 - b64.length % 4) % 4);
    const s = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(s);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
  }
  async function enablePush() {
    try {
      const perm = await Notification.requestPermission();
      if (perm !== 'granted') { document.getElementById('push-cta').classList.add('hidden'); document.getElementById('push-denied').classList.remove('hidden'); return; }
      const reg = await navigator.serviceWorker.register('/sw.js');
      await navigator.serviceWorker.ready;
      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC) });
      }
      const body = { token: TOKEN, endpoint: sub.endpoint, keys: { p256dh: btoa(String.fromCharCode(...new Uint8Array(sub.getKey('p256dh')))).replace(/=+$/,'').replace(/\//g,'_').replace(/\+/g,'-'), auth: btoa(String.fromCharCode(...new Uint8Array(sub.getKey('auth')))).replace(/=+$/,'').replace(/\//g,'_').replace(/\+/g,'-') } };
      const res = await fetch('/api/push-cleaner-subscribe.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
      if (res.ok) { document.getElementById('push-cta').classList.add('hidden'); document.getElementById('push-on').classList.remove('hidden'); }
    } catch(e) { console.error(e); }
  }
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('push-enable-btn');
    if (btn) btn.addEventListener('click', enablePush);
  });
})();
</script>
<?php endif; ?>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
