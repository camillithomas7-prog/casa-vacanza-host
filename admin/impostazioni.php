<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
$user = requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'save_settings') {
        foreach (['site_name','contact_email','contact_phone','currency','language','timezone',
                 'social_facebook','social_instagram','social_tiktok'] as $k) {
            $v = trim($_POST[$k] ?? '');
            q('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$k, $v]);
        }
        $msg = 'Impostazioni salvate';
    }
    if ($action === 'save_features') {
        foreach (['rentals','excursions','transfer'] as $k) {
            $v = isset($_POST['feature_' . $k]) ? '1' : '0';
            q('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', ['feature_' . $k, $v]);
        }
        $msg = 'Servizi sul sito aggiornati';
    }
    if ($action === 'change_password') {
        $u = row('SELECT * FROM users WHERE id = ?', [$user['id']]);
        if (!password_verify($_POST['old'] ?? '', $u['password'])) { $msg = 'Password attuale errata'; }
        elseif (strlen($_POST['next'] ?? '') < 6) { $msg = 'Password troppo corta'; }
        else {
            q('UPDATE users SET password = ? WHERE id = ?', [password_hash($_POST['next'], PASSWORD_DEFAULT), $u['id']]);
            $msg = 'Password aggiornata';
        }
    }
}

$settings = [];
foreach (rows('SELECT * FROM settings') as $s) $settings[$s['setting_key']] = $s['setting_value'];

$stats = [
  'apartments' => (int)val('SELECT COUNT(*) FROM apartments'),
  'bookings' => (int)val('SELECT COUNT(*) FROM bookings'),
  'customers' => (int)val('SELECT COUNT(*) FROM customers'),
];

$title = 'Impostazioni';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <h1 class="font-display text-2xl sm:text-3xl font-bold">Impostazioni</h1>

  <?php if ($msg): ?><div class="card p-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700"><?= e($msg) ?></div><?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <form method="post" class="card p-4 sm:p-5 space-y-3">
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="action" value="save_settings">
      <h3 class="font-display font-bold flex items-center gap-2"><i data-lucide="globe" class="size-[18px]"></i> Sito</h3>
      <label class="block"><span class="label">Nome del sito</span><input class="input" name="site_name" value="<?= e($settings['site_name'] ?? cfg('site.name')) ?>"></label>
      <div class="grid grid-cols-2 gap-3">
        <label class="block"><span class="label">Email</span><input class="input" name="contact_email" value="<?= e($settings['contact_email'] ?? cfg('site.email')) ?>"></label>
        <label class="block"><span class="label">Telefono</span><input class="input" name="contact_phone" value="<?= e($settings['contact_phone'] ?? cfg('site.phone')) ?>"></label>
        <label class="block"><span class="label">Valuta</span>
          <select class="input" name="currency">
            <?php foreach (['EUR','USD','GBP'] as $c): ?><option value="<?= $c ?>" <?= ($settings['currency'] ?? 'EUR') === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="block"><span class="label">Lingua</span>
          <select class="input" name="language">
            <option value="it" <?= ($settings['language'] ?? 'it') === 'it' ? 'selected' : '' ?>>Italiano</option>
            <option value="en" <?= ($settings['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
          </select>
        </label>
        <label class="block col-span-2"><span class="label">Timezone</span><input class="input" name="timezone" value="<?= e($settings['timezone'] ?? cfg('site.timezone')) ?>"></label>
      </div>
      <div class="pt-3 border-t border-ink-100 dark:border-ink-800/80">
        <h3 class="font-display font-bold flex items-center gap-2 mb-3"><i data-lucide="share-2" class="size-[18px]"></i> Social</h3>
        <p class="text-xs text-ink-500 mb-3">URL completi (es. https://instagram.com/patrizia.mancini). Lascia vuoto per nascondere.</p>
        <div class="space-y-2.5">
          <label class="block">
            <span class="label flex items-center gap-1.5"><i data-lucide="facebook" class="size-[14px] text-[#1877f2]"></i> Facebook</span>
            <input class="input" name="social_facebook" placeholder="https://facebook.com/..." value="<?= e($settings['social_facebook'] ?? '') ?>">
          </label>
          <label class="block">
            <span class="label flex items-center gap-1.5"><i data-lucide="instagram" class="size-[14px] text-[#e4405f]"></i> Instagram</span>
            <input class="input" name="social_instagram" placeholder="https://instagram.com/..." value="<?= e($settings['social_instagram'] ?? '') ?>">
          </label>
          <label class="block">
            <span class="label flex items-center gap-1.5"><i data-lucide="music" class="size-[14px]"></i> TikTok</span>
            <input class="input" name="social_tiktok" placeholder="https://tiktok.com/@..." value="<?= e($settings['social_tiktok'] ?? '') ?>">
          </label>
        </div>
      </div>
      <button class="btn-primary"><i data-lucide="save" class="size-[16px]"></i> Salva</button>
    </form>

    <form method="post" class="card p-4 sm:p-5 space-y-3">
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="action" value="change_password">
      <h3 class="font-display font-bold">Account</h3>
      <div class="text-sm text-ink-500">Connesso come <strong><?= e($user['email']) ?></strong></div>
      <label class="block"><span class="label">Password attuale</span><input type="password" class="input" name="old"></label>
      <label class="block"><span class="label">Nuova password</span><input type="password" class="input" name="next"></label>
      <button class="btn-secondary">Aggiorna password</button>
    </form>

    <form method="post" class="card p-4 sm:p-5 space-y-3 lg:col-span-2 border-2 border-brand-200 dark:border-brand-500/30 bg-brand-50/40 dark:bg-brand-500/5">
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="action" value="save_features">
      <div class="flex items-start gap-3">
        <span class="h-9 w-9 rounded-xl bg-brand-100 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="layers" class="size-[18px]"></i></span>
        <div>
          <h3 class="font-display font-bold">Servizi attivi sul sito</h3>
          <p class="text-xs text-ink-500 mt-0.5">Gli appartamenti sono sempre attivi. Attiva qui sotto le altre categorie quando inizi a offrirle: appariranno nel menù e nella homepage.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <?php foreach ([
          ['rentals',    'Noleggi',     'Auto, scooter, golf cart',                'key-round'],
          ['excursions', 'Escursioni',  'Snorkeling, deserto, diving, cultura',    'compass'],
          ['transfer',   'Transfer',    'Trasporti aeroporto ↔ alloggio',          'plane-takeoff'],
        ] as $f):
          $on = featureEnabled($f[0]);
        ?>
          <label x-data="{ on: <?= $on ? 'true' : 'false' ?> }"
                 :class="on ? 'border-brand-400 bg-white dark:bg-ink-900 shadow-sm' : 'border-ink-200 dark:border-ink-700/60 bg-white/60 dark:bg-ink-900/40'"
                 class="relative flex items-start gap-3 p-3 rounded-xl border cursor-pointer hover:border-brand-400 transition">
            <input type="checkbox" name="feature_<?= $f[0] ?>" class="sr-only" x-model="on">
            <span :class="on ? 'bg-brand-100 text-brand-600' : 'bg-ink-100 dark:bg-ink-800 text-ink-400'"
                  class="h-9 w-9 rounded-lg flex items-center justify-center shrink-0 transition"><i data-lucide="<?= $f[3] ?>" class="size-[18px]"></i></span>
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm flex items-center gap-2"><?= e($f[1]) ?>
                <span x-show="on" class="badge-success text-[10px]">Attivo</span>
                <span x-show="!on" class="badge-soft text-[10px]">Off</span>
              </div>
              <div class="text-[11px] text-ink-500 mt-0.5"><?= e($f[2]) ?></div>
            </div>
            <span :class="on ? 'bg-brand-500' : 'bg-ink-300 dark:bg-ink-700'"
                  class="relative inline-block h-5 w-9 rounded-full transition shrink-0">
              <span :class="on ? 'left-[18px]' : 'left-0.5'"
                    class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-all"></span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="flex items-center justify-between pt-2">
        <span class="text-xs text-ink-500">L'admin per gestire i servizi resta sempre disponibile.</span>
        <button class="btn-primary"><i data-lucide="save" class="size-[16px]"></i> Salva</button>
      </div>
    </form>

    <div class="card p-4 sm:p-5">
      <h3 class="font-display font-bold flex items-center gap-2"><i data-lucide="database" class="size-[18px]"></i> Backup & dati</h3>
      <p class="text-sm text-ink-500 mb-3 mt-2">Esporta tutti i dati in formato JSON.</p>
      <a href="/admin/backup.php" class="btn-outline">Scarica backup JSON</a>
      <div class="grid grid-cols-3 gap-2 mt-4">
        <div class="rounded-xl bg-ink-50 dark:bg-ink-900 p-3 text-center"><div class="text-xs text-ink-500">Appartamenti</div><div class="font-display font-bold text-xl"><?= (int)$stats['apartments'] ?></div></div>
        <div class="rounded-xl bg-ink-50 dark:bg-ink-900 p-3 text-center"><div class="text-xs text-ink-500">Prenotazioni</div><div class="font-display font-bold text-xl"><?= (int)$stats['bookings'] ?></div></div>
        <div class="rounded-xl bg-ink-50 dark:bg-ink-900 p-3 text-center"><div class="text-xs text-ink-500">Clienti</div><div class="font-display font-bold text-xl"><?= (int)$stats['customers'] ?></div></div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
