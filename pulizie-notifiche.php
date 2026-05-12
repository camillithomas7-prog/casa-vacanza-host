<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/cleaning.php';

$token = $_GET['t'] ?? '';
$valid = $token && hash_equals(cleanerToken(), $token);
if (!$valid) { http_response_code(401); exit('Link non valido'); }

$vapidPub = setting('vapid_public') ?: '';

$title = 'Notifiche · Pulizie';
$pwaManifest = '/manifest-cleaner.php?t=' . rawurlencode($token);
$cleanerToken = $token;
$cleanerActiveTab = 'settings';
require __DIR__ . '/partials/head.php';
?>
<div class="min-h-screen bg-ink-50/40 dark:bg-ink-950 pb-28">
  <header class="bg-white dark:bg-ink-900 border-b border-ink-100 dark:border-ink-800/80 sticky top-0 z-30">
    <div class="container-narrow py-3 px-4 flex items-center gap-3">
      <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center shadow-md"><i data-lucide="bell" class="size-[20px]"></i></span>
      <div>
        <div class="font-display font-bold leading-none">Notifiche</div>
        <div class="text-xs text-ink-500 mt-0.5">Reminder pulizie 24h prima</div>
      </div>
    </div>
  </header>

  <main class="container-narrow py-4 px-4 space-y-4">
    <?php if (!$vapidPub): ?>
      <div class="card p-5 text-sm text-ink-500">
        Il sistema notifiche non è ancora configurato. Avvisa Patrizia.
      </div>
    <?php else: ?>
      <div class="card p-5 sm:p-6" id="push-card">
        <!-- Stato: non ancora attivate -->
        <div id="state-default" class="hidden">
          <div class="text-center py-4">
            <div class="h-20 w-20 mx-auto rounded-3xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center shadow-md mb-4">
              <i data-lucide="bell-ring" class="size-[36px]"></i>
            </div>
            <h2 class="font-display font-bold text-xl">Attiva le notifiche</h2>
            <p class="text-sm text-ink-500 mt-2 max-w-sm mx-auto">Ricevi un avviso sul telefono il giorno prima di ogni appartamento da pulire — anche con l'app chiusa.</p>
            <button id="push-enable-btn" class="btn-primary mt-5 h-12 px-6 text-base bg-emerald-500 hover:bg-emerald-600 border-emerald-600">
              <i data-lucide="bell" class="size-[18px]"></i> Attiva ora
            </button>
            <p class="text-[11px] text-ink-400 mt-3">Il browser ti chiederà il permesso. Tocca "Consenti".</p>
          </div>
        </div>

        <!-- Stato: attivate -->
        <div id="state-on" class="hidden">
          <div class="flex items-start gap-4">
            <span class="h-14 w-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-md"><i data-lucide="bell-check" class="size-[28px]"></i></span>
            <div class="flex-1 min-w-0">
              <h2 class="font-display font-bold text-lg text-emerald-700 dark:text-emerald-300">Notifiche attive</h2>
              <p class="text-sm text-ink-500 mt-1">Riceverai un avviso 24h prima di ogni pulizia su questo dispositivo.</p>
              <button id="push-test-btn" class="btn-outline mt-3 text-sm">
                <i data-lucide="zap" class="size-[14px]"></i> Invia notifica di prova
              </button>
            </div>
          </div>
        </div>

        <!-- Stato: bloccate -->
        <div id="state-denied" class="hidden">
          <div class="flex items-start gap-4">
            <span class="h-14 w-14 rounded-2xl bg-red-500 text-white flex items-center justify-center shrink-0"><i data-lucide="bell-off" class="size-[28px]"></i></span>
            <div class="flex-1 min-w-0">
              <h2 class="font-display font-bold text-lg text-red-700">Notifiche bloccate</h2>
              <p class="text-sm text-ink-500 mt-1">Le hai negate o bloccate in passato. Per riattivarle:</p>
              <ol class="text-sm text-ink-700 dark:text-ink-300 mt-3 space-y-2 list-decimal list-inside">
                <li><strong>iPhone</strong>: Impostazioni → Notifiche → cerca "Pulizie" → attiva</li>
                <li><strong>Android</strong>: Impostazioni → App → Pulizie → Notifiche → attiva</li>
                <li><strong>Chrome</strong>: tocca l'icona 🔒 a sinistra dell'indirizzo → "Notifiche" → Consenti</li>
              </ol>
            </div>
          </div>
        </div>

        <!-- Browser non supportato -->
        <div id="state-unsupported" class="hidden">
          <p class="text-sm text-ink-500">Il tuo browser non supporta le notifiche push. Apri il link in Chrome (Android) o Safari (iPhone con l'app installata sulla home).</p>
        </div>
      </div>

      <!-- Promemoria iPhone -->
      <div class="card p-4 border-blue-200 bg-blue-50/60 dark:bg-blue-500/5 dark:border-blue-500/30 text-sm text-blue-900 dark:text-blue-200">
        <div class="flex items-start gap-2">
          <i data-lucide="info" class="size-[16px] shrink-0 mt-0.5"></i>
          <div>
            <strong>iPhone</strong>: le notifiche funzionano solo se hai aggiunto questa app alla schermata Home. Se non lo hai ancora fatto: tocca <i data-lucide="share" class="inline-block size-[14px] align-text-bottom"></i> nel browser → "Aggiungi alla schermata Home".
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>
</div>
<?php require __DIR__ . '/partials/cleaner-nav.php'; ?>
<script>if (window.lucide) lucide.createIcons();</script>
<?php if ($vapidPub): ?>
<script>
(function(){
  const VAPID_PUBLIC = <?= json_encode($vapidPub) ?>;
  const TOKEN = <?= json_encode($token) ?>;
  function b64uToBytes(b64) {
    const pad = '='.repeat((4 - b64.length % 4) % 4);
    const s = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from([...atob(s)].map(c => c.charCodeAt(0)));
  }
  function bytesToB64u(buf) {
    return btoa(String.fromCharCode(...new Uint8Array(buf))).replace(/=+$/,'').replace(/\//g,'_').replace(/\+/g,'-');
  }
  async function enablePush() {
    try {
      const perm = await Notification.requestPermission();
      if (perm !== 'granted') {
        document.getElementById('state-default').classList.add('hidden');
        document.getElementById('state-denied').classList.remove('hidden');
        return;
      }
      const reg = await navigator.serviceWorker.register('/sw.js');
      await navigator.serviceWorker.ready;
      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64uToBytes(VAPID_PUBLIC) });
      }
      const body = {
        token: TOKEN,
        endpoint: sub.endpoint,
        keys: { p256dh: bytesToB64u(sub.getKey('p256dh')), auth: bytesToB64u(sub.getKey('auth')) }
      };
      const res = await fetch('/api/push-cleaner-subscribe.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
      if (res.ok) {
        document.getElementById('state-default').classList.add('hidden');
        document.getElementById('state-on').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
      }
    } catch(e) { console.error(e); alert('Errore: ' + e.message); }
  }
  async function testPush() {
    try {
      // Mostra una notifica locale via Service Worker per testare il flusso
      const reg = await navigator.serviceWorker.ready;
      await reg.showNotification('Test notifica Pulizie', {
        body: 'Funziona! Riceverai notifiche come questa il giorno prima di ogni pulizia.',
        icon: '/assets/logo-512.png',
        badge: '/assets/logo-192.png',
        tag: 'test',
      });
    } catch(e) { alert('Impossibile mostrare la notifica: ' + e.message); }
  }
  function syncState() {
    try {
      let state;
      if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        document.getElementById('state-unsupported').classList.remove('hidden');
        return;
      }
      state = Notification.permission || 'default';
      ['state-default','state-on','state-denied','state-unsupported'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
      });
      if (state === 'granted') document.getElementById('state-on').classList.remove('hidden');
      else if (state === 'denied') document.getElementById('state-denied').classList.remove('hidden');
      else document.getElementById('state-default').classList.remove('hidden');
    } catch(e) {
      const el = document.getElementById('state-unsupported');
      if (el) el.classList.remove('hidden');
    }
    if (window.lucide) try { lucide.createIcons(); } catch(e){}
  }
  document.addEventListener('DOMContentLoaded', () => {
    syncState();
    const btn = document.getElementById('push-enable-btn');
    if (btn) btn.addEventListener('click', async () => { await enablePush(); syncState(); });
    const tbtn = document.getElementById('push-test-btn');
    if (tbtn) tbtn.addEventListener('click', testPush);
  });
})();
</script>
<?php endif; ?>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
