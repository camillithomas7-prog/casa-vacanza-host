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
  <!-- LATO BRAND -->
  <div class="hidden lg:flex flex-col justify-between p-12 relative isolate overflow-hidden text-white bg-gradient-to-br from-brand-700 via-brand-600 to-ink-950">
    <div class="absolute -top-40 -right-40 h-[500px] w-[500px] rounded-full bg-brand-400/30 blur-3xl animate-glow-pulse pointer-events-none"></div>
    <div class="absolute -bottom-32 -left-32 h-[400px] w-[400px] rounded-full bg-sea-400/20 blur-3xl animate-glow-pulse pointer-events-none" style="animation-delay:-1.5s"></div>
    <div class="absolute inset-0 opacity-[0.05] pointer-events-none" style="background-image: radial-gradient(white 1px, transparent 1px); background-size: 24px 24px;"></div>

    <div class="relative flex items-center gap-2.5">
      <span class="h-10 w-10 rounded-2xl bg-white text-brand-600 flex items-center justify-center shadow-pop"><i data-lucide="home" class="size-[18px]"></i></span>
      <span class="font-display font-extrabold text-lg"><?= e(cfg('site.name')) ?></span>
    </div>

    <div class="relative">
      <h1 class="font-serif text-5xl xl:text-6xl font-semibold leading-[1.05] tracking-tight text-balance">
        Gestisci ogni casa<br>
        <span class="italic font-medium">come fosse la tua.</span>
      </h1>
      <p class="mt-6 text-white/80 text-lg max-w-md text-pretty">
        Calendario, prenotazioni, prezzi, spese, bilancio e messaggistica WhatsApp/Email automatica. Tutto in un unico posto.
      </p>
      <div class="grid grid-cols-3 gap-3 mt-10 max-w-md">
        <?php foreach ([['calendar','Calendario'],['receipt','Bilancio'],['message-circle','WhatsApp']] as $f): ?>
          <div class="rounded-xl p-3.5 bg-white/5 border border-white/10 backdrop-blur">
            <i data-lucide="<?= $f[0] ?>" class="size-[18px] text-brand-200"></i>
            <div class="text-xs mt-2 text-white/80"><?= e($f[1]) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="relative text-white/60 text-xs">© <?= date('Y') ?> <?= e(cfg('site.name')) ?> · v1.0</div>
  </div>

  <!-- LATO FORM -->
  <div class="flex items-center justify-center p-6 relative">
    <div class="absolute top-6 right-6">
      <button onclick="toggleTheme()" class="h-10 w-10 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-600 dark:text-ink-300">
        <i data-lucide="moon" class="size-[18px] dark:hidden"></i>
        <i data-lucide="sun" class="size-[18px] hidden dark:inline"></i>
      </button>
    </div>

    <form method="post" class="w-full max-w-sm animate-slide-up">
      <div class="lg:hidden flex items-center gap-2 mb-8">
        <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center"><i data-lucide="home" class="size-[18px]"></i></span>
        <span class="font-display font-extrabold text-lg"><?= e(cfg('site.name')) ?></span>
      </div>
      <div class="badge-brand mb-3">Area riservata</div>
      <h2 class="font-serif text-4xl font-semibold tracking-tight">Bentornato.</h2>
      <p class="text-ink-500 mt-2">Entra nel tuo gestionale.</p>
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <div class="space-y-3 mt-7">
        <label class="block">
          <span class="label">Email</span>
          <div class="relative"><i data-lucide="mail" class="size-[16px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400"></i>
            <input class="input pl-10" type="email" name="email" required value="<?= e($_POST['email'] ?? cfg('admin_default.email')) ?>">
          </div>
        </label>
        <label class="block">
          <span class="label">Password</span>
          <div class="relative"><i data-lucide="lock" class="size-[16px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400"></i>
            <input class="input pl-10" type="password" name="password" required>
          </div>
        </label>
      </div>
      <?php if ($err): ?>
        <div class="text-sm text-red-700 mt-4 p-3 rounded-lg bg-red-50 border border-red-200 flex items-center gap-2 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-300">
          <i data-lucide="alert-circle" class="size-[16px]"></i> <?= e($err) ?>
        </div>
      <?php endif; ?>
      <button class="btn-primary w-full mt-6 h-12 text-base">Accedi <i data-lucide="arrow-right" class="size-[16px]"></i></button>
      <div class="text-xs text-ink-500 mt-5 p-3.5 rounded-xl bg-ink-50 dark:bg-ink-900 border border-ink-100 dark:border-ink-800/80 flex items-start gap-2">
        <i data-lucide="info" class="size-[14px] text-brand-500 shrink-0 mt-0.5"></i>
        <div><strong>Demo:</strong> <?= e(cfg('admin_default.email')) ?> · <?= e(cfg('admin_default.password')) ?> <span class="block text-ink-400 mt-0.5">Ricordati di cambiarla dopo il primo accesso.</span></div>
      </div>
    </form>
  </div>
</div>
<script>
function toggleTheme(){ var d = document.documentElement.classList.toggle('dark'); try { localStorage.setItem('cv-theme', d ? 'dark' : 'light'); } catch(e){} }
lucide.createIcons();
</script>
</body></html>
