<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    if (($_POST['action'] ?? '') === 'mark_all') {
        q('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
        flash('Tutte segnate come lette');
    }
    redirect('/admin/notifiche.php');
}

$notifications = rows('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100');
$logs = rows('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 30');

$title = 'Notifiche';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<?php $deviceCount = (int)val('SELECT COUNT(*) FROM push_subscriptions'); ?>
<div class="space-y-5">
  <div class="flex items-center justify-between">
    <h1 class="font-display text-3xl font-bold">Notifiche & attività</h1>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="mark_all"><button class="btn-outline text-sm"><i data-lucide="check" class="size-[14px]"></i> Segna tutte come lette</button></form>
  </div>

  <!-- Push notifications card -->
  <div class="card p-5" x-data="pushPanel()" x-init="init()">
    <div class="flex items-start gap-4 flex-wrap">
      <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center shrink-0">
        <i data-lucide="bell-ring" class="size-[22px]"></i>
      </div>
      <div class="flex-1 min-w-[260px]">
        <h3 class="font-display font-bold text-lg">Notifiche sul telefono</h3>
        <p class="text-sm text-ink-500 mt-0.5">
          Ricevi un avviso istantaneo sul tuo telefono o computer quando arriva una nuova prenotazione.
        </p>

        <template x-if="state === 'loading'">
          <p class="text-sm text-ink-400 mt-3">Verifico lo stato…</p>
        </template>

        <template x-if="state === 'unsupported'">
          <div class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900">
            Questo browser non supporta le notifiche push. Su iPhone: <b>installa l'app dalla schermata "Aggiungi a Home"</b> di Safari (serve iOS 16.4 o superiore).
          </div>
        </template>

        <template x-if="state === 'denied'">
          <div class="mt-3 p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-800">
            Le notifiche sono <b>bloccate</b> nelle impostazioni del browser. Per riattivarle: apri le impostazioni del sito (icona lucchetto in alto vicino all'indirizzo) → "Notifiche" → consenti.
          </div>
        </template>

        <template x-if="state === 'inactive'">
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <button @click="enable()" :disabled="busy" class="btn-primary">
              <i data-lucide="bell" class="size-[16px]"></i>
              <span x-text="busy ? 'Attivazione…' : 'Attiva notifiche su questo dispositivo'"></span>
            </button>
            <span class="text-xs text-ink-500">Verrà chiesto il permesso al tuo browser.</span>
          </div>
        </template>

        <template x-if="state === 'active'">
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="badge-success"><i data-lucide="check-circle-2" class="size-[14px]"></i> Attive su questo dispositivo</span>
            <button @click="test()" :disabled="busy" class="btn-outline text-sm">
              <i data-lucide="send" class="size-[14px]"></i>
              <span x-text="busy ? 'Invio…' : 'Invia notifica di prova'"></span>
            </button>
            <button @click="disable()" :disabled="busy" class="btn-ghost text-sm text-red-600">
              <i data-lucide="bell-off" class="size-[14px]"></i> Disattiva qui
            </button>
          </div>
        </template>

        <template x-if="message">
          <p class="mt-3 text-sm" :class="messageType === 'error' ? 'text-red-600' : 'text-emerald-700'" x-text="message"></p>
        </template>

        <p class="text-xs text-ink-400 mt-3">
          Dispositivi collegati in totale: <b><?= $deviceCount ?></b> · Devi attivarle su ogni telefono/PC che usi.
        </p>
      </div>
    </div>
  </div>
  <script>
    function pushPanel(){
      return {
        state: 'loading',
        busy: false,
        message: '',
        messageType: 'info',
        async init(){
          if (!window.cvPush) { this.state = 'unsupported'; return; }
          const s = await window.cvPush.status();
          if (!s.supported) { this.state = 'unsupported'; return; }
          if (s.permission === 'denied') { this.state = 'denied'; return; }
          this.state = s.active ? 'active' : 'inactive';
        },
        async enable(){
          this.busy = true; this.message = '';
          try {
            await window.cvPush.subscribe();
            this.state = 'active';
            this.messageType = 'ok';
            this.message = 'Perfetto! Le notifiche sono attive su questo dispositivo.';
          } catch(e){
            this.messageType = 'error';
            if (e.message === 'permission_denied') {
              this.message = 'Hai negato il permesso. Riprova accettando quando il browser chiede.';
              this.state = 'denied';
            } else {
              this.message = 'Errore: ' + (e.message || 'imprevisto');
            }
          }
          this.busy = false;
        },
        async disable(){
          this.busy = true; this.message = '';
          try {
            await window.cvPush.unsubscribe();
            this.state = 'inactive';
            this.message = 'Notifiche disattivate su questo dispositivo.';
            this.messageType = 'ok';
          } catch(e){
            this.messageType = 'error';
            this.message = 'Errore disattivazione: ' + e.message;
          }
          this.busy = false;
        },
        async test(){
          this.busy = true; this.message = '';
          try {
            const res = await window.cvPush.sendTest();
            this.messageType = 'ok';
            this.message = 'Inviata a ' + (res.sent || 0) + ' dispositivo/i. Se non arriva, controlla che le notifiche del browser siano abilitate a livello di sistema.';
          } catch(e){
            this.messageType = 'error';
            this.message = 'Errore invio test: ' + e.message;
          }
          this.busy = false;
        },
      }
    }
  </script>
  <div class="grid lg:grid-cols-2 gap-5">
    <div class="card p-5">
      <h3 class="font-display font-bold mb-3">Notifiche</h3>
      <ul class="divide-y divide-ink-100 dark:divide-ink-800">
        <?php foreach ($notifications as $n): ?>
          <li class="py-3 <?= $n['is_read'] ? 'opacity-60' : '' ?>">
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="font-medium"><?= e($n['title']) ?></div>
                <div class="text-sm text-ink-500"><?= e($n['body']) ?></div>
                <div class="text-xs text-ink-400 mt-1"><?= fmtDateTime($n['created_at']) ?></div>
              </div>
              <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>" class="text-sm text-brand-600 shrink-0">Apri</a><?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
        <?php if (!$notifications): ?><li class="text-sm text-ink-500 py-4 text-center">Nessuna notifica.</li><?php endif; ?>
      </ul>
    </div>
    <div class="card p-5">
      <h3 class="font-display font-bold mb-3">Attività recente</h3>
      <ul class="divide-y divide-ink-100 dark:divide-ink-800">
        <?php foreach ($logs as $l): ?>
          <li class="py-2 text-sm">
            <span class="font-mono text-xs px-1.5 py-0.5 bg-ink-100 dark:bg-ink-800 rounded"><?= e($l['action']) ?></span>
            <span class="text-ink-500"><?= e($l['entity']) ?></span>
            <?php if ($l['details']): ?> · <?= e(mb_substr($l['details'], 0, 80)) ?><?php endif; ?>
            <div class="text-xs text-ink-400"><?= fmtDateTime($l['created_at']) ?></div>
          </li>
        <?php endforeach; ?>
        <?php if (!$logs): ?><li class="text-sm text-ink-500 py-4 text-center">Nessuna attività.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
