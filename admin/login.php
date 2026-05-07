<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';

if (!tableExists('users')) { redirect('/setup.php'); }
ensureAdminUser();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $u = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($u) redirect('/admin/index.php');
    $err = 'Credenziali non valide';
}

if (currentUser()) redirect('/admin/index.php');

$title = 'Accedi';
require __DIR__ . '/../partials/head.php';
?>
<div class="min-h-screen grid lg:grid-cols-2">
  <div class="hidden lg:flex flex-col justify-between p-10 bg-gradient-to-br from-brand-500 to-brand-700 text-white relative overflow-hidden">
    <div class="relative font-display font-bold text-2xl"><?= e(cfg('site.name')) ?> Admin</div>
    <div class="relative">
      <h1 class="font-display text-4xl font-bold leading-tight">Gestisci tutti<br>i tuoi appartamenti<br>in un unico posto.</h1>
      <p class="mt-4 text-white/80 max-w-md">Calendario, prenotazioni, prezzi, spese, bilancio e messaggistica WhatsApp/Email automatica.</p>
    </div>
    <div class="relative text-white/60 text-sm">© <?= e(cfg('site.name')) ?> Manager</div>
  </div>
  <div class="flex items-center justify-center p-6">
    <form method="post" class="w-full max-w-sm">
      <h2 class="font-display text-3xl font-bold">Accedi</h2>
      <p class="text-ink-500 mt-1">Entra nel tuo gestionale.</p>
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <div class="space-y-3 mt-6">
        <label class="block">
          <span class="label">Email</span>
          <div class="relative"><i data-lucide="mail" class="size-[16px] absolute left-3 top-1/2 -translate-y-1/2 text-ink-400"></i>
            <input class="input pl-10" type="email" name="email" required value="<?= e($_POST['email'] ?? cfg('admin_default.email')) ?>">
          </div>
        </label>
        <label class="block">
          <span class="label">Password</span>
          <div class="relative"><i data-lucide="lock" class="size-[16px] absolute left-3 top-1/2 -translate-y-1/2 text-ink-400"></i>
            <input class="input pl-10" type="password" name="password" required>
          </div>
        </label>
      </div>
      <?php if ($err): ?><div class="text-sm text-red-600 mt-3"><?= e($err) ?></div><?php endif; ?>
      <button class="btn-primary w-full mt-5">Accedi</button>
      <div class="text-xs text-ink-500 mt-4 p-3 rounded-lg bg-ink-50 dark:bg-ink-900 border border-ink-100 dark:border-ink-800">
        <strong>Demo:</strong> <?= e(cfg('admin_default.email')) ?> / <?= e(cfg('admin_default.password')) ?>
      </div>
    </form>
  </div>
</div>
<script>lucide.createIcons();</script>
</body></html>
