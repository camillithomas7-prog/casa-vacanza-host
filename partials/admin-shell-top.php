<?php
require_once __DIR__ . '/../lib/auth.php';
$current = currentUser();
$path = $_SERVER['SCRIPT_NAME'] ?? '';
function navItem($href, $icon, $label, $current) {
    $active = strpos($current, $href) !== false || ($href === '/admin/index.php' && in_array($current, ['/admin/index.php','/admin/']));
    $cls = $active
      ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-[0_4px_12px_-4px_rgba(240,78,0,.45)]'
      : 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800/60';
    echo '<a href="' . e($href) . '" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition ' . $cls . '"><i data-lucide="' . e($icon) . '" class="size-[18px] shrink-0"></i> ' . e($label) . '</a>';
}
$unreadNotifs = (int)val('SELECT COUNT(*) FROM notifications WHERE is_read = 0');
$initials = $current ? mb_strtoupper(mb_substr($current['name'] ?: $current['email'], 0, 1) . mb_substr($current['email'], 1, 1)) : 'A';

$titles = [
  '/admin/index.php' => ['Dashboard', 'Panoramica del tuo gestionale'],
  '/admin/appartamenti.php' => ['Appartamenti', 'Gestisci la tua flotta'],
  '/admin/appartamento-edit.php' => ['Appartamento', 'Modifica scheda'],
  '/admin/calendario.php' => ['Calendario', 'Disponibilità e blocchi'],
  '/admin/prenotazioni.php' => ['Prenotazioni', 'Tutte le prenotazioni'],
  '/admin/prenotazione.php' => ['Prenotazione', 'Dettaglio'],
  '/admin/prenotazione-nuova.php' => ['Nuova prenotazione', 'Crea manualmente'],
  '/admin/prezzi.php' => ['Prezzi avanzati', 'Regole stagionali'],
  '/admin/spese.php' => ['Spese & bilancio', 'Costi e ricavi'],
  '/admin/template.php' => ['Template messaggi', 'WhatsApp & Email'],
  '/admin/recensioni.php' => ['Recensioni', 'Modera i feedback'],
  '/admin/coupon.php' => ['Coupon', 'Sconti'],
  '/admin/impostazioni.php' => ['Impostazioni', 'Account e backup'],
  '/admin/notifiche.php' => ['Notifiche', 'Attività recente'],
  '/admin/servizi.php' => ['Servizi extra', 'Veicoli, escursioni, transfer'],
  '/admin/servizio-edit.php' => ['Servizio', 'Modifica scheda'],
  '/admin/servizi-prenotazioni.php' => ['Prenotazioni servizi', 'Veicoli, escursioni, transfer'],
  '/admin/servizi-prenotazione.php' => ['Prenotazione servizio', 'Dettaglio'],
];
$pageMeta = $titles[$path] ?? ['Admin', ''];
?>
<div class="min-h-screen flex bg-ink-50 dark:bg-ink-950" x-data="{ open: false }">
<aside class="fixed lg:sticky inset-y-0 left-0 z-40 w-72 bg-white dark:bg-ink-900/95 dark:backdrop-blur-xl border-r border-ink-100 dark:border-ink-800/80 transition-transform lg:translate-x-0 flex flex-col" :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
  <div class="h-20 px-5 flex items-center justify-between border-b border-ink-100 dark:border-ink-800/80 shrink-0">
    <a href="/admin/index.php" class="flex items-center group">
      <img src="/assets/logo-256.png?v=2" alt="<?= e(cfg('site.name')) ?>" class="h-14 w-auto">
    </a>
    <button class="lg:hidden btn-ghost p-2" @click="open=false"><i data-lucide="x" class="size-[18px]"></i></button>
  </div>
  <nav class="p-3 space-y-0.5 flex-1 overflow-y-auto scrollbar-thin">
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-2">Menu</div>
    <?php navItem('/admin/index.php', 'bar-chart-3', 'Dashboard', $path); ?>
    <?php navItem('/admin/appartamenti.php', 'building-2', 'Appartamenti', $path); ?>
    <?php navItem('/admin/calendario.php', 'calendar', 'Calendario', $path); ?>
    <?php navItem('/admin/prenotazioni.php', 'bookmark-check', 'Prenotazioni', $path); ?>
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-4">Servizi extra</div>
    <?php navItem('/admin/servizi.php', 'package', 'Catalogo servizi', $path); ?>
    <?php navItem('/admin/servizi-prenotazioni.php', 'clipboard-list', 'Prenotazioni servizi', $path); ?>
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-4">Strategia</div>
    <?php navItem('/admin/prezzi.php', 'wallet', 'Prezzi', $path); ?>
    <?php navItem('/admin/spese.php', 'receipt', 'Spese & bilancio', $path); ?>
    <?php navItem('/admin/coupon.php', 'tag', 'Coupon', $path); ?>
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-4">Comunicazione</div>
    <?php navItem('/admin/template.php', 'message-square', 'Template', $path); ?>
    <?php navItem('/admin/recensioni.php', 'star', 'Recensioni', $path); ?>
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-4">Sistema</div>
    <?php navItem('/admin/impostazioni.php', 'settings', 'Impostazioni', $path); ?>
    <?php navItem('/admin/notifiche.php', 'bell', 'Notifiche', $path); ?>
  </nav>
  <div class="p-3 border-t border-ink-100 dark:border-ink-800/80 shrink-0">
    <div class="flex items-center gap-3 p-2.5 rounded-xl">
      <span class="h-9 w-9 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center font-semibold text-sm shrink-0"><?= e($initials) ?></span>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold truncate"><?= e($current['name'] ?: 'Admin') ?></div>
        <div class="text-xs text-ink-500 truncate"><?= e($current['email']) ?></div>
      </div>
      <a href="/admin/logout.php" class="h-8 w-8 rounded-lg flex items-center justify-center text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800" title="Esci"><i data-lucide="log-out" class="size-[14px]"></i></a>
    </div>
  </div>
</aside>

<div x-show="open" x-cloak class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="open=false" style="display:none"></div>

<div class="flex-1 min-w-0">
  <header class="sticky top-0 z-20 h-16 bg-white/85 dark:bg-ink-950/85 backdrop-blur-xl border-b border-ink-100 dark:border-ink-800/80">
    <div class="h-full px-5 flex items-center gap-3">
      <button class="lg:hidden btn-ghost p-2" @click="open=true"><i data-lucide="menu" class="size-[20px]"></i></button>
      <div>
        <div class="text-xs text-ink-500"><?= e($pageMeta[1]) ?></div>
        <div class="font-display font-bold text-base leading-tight"><?= e($pageMeta[0]) ?></div>
      </div>
      <div class="flex-1 max-w-md ml-auto relative hidden md:block">
        <i data-lucide="search" class="size-[16px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400"></i>
        <input placeholder="Cerca prenotazioni, clienti, appartamenti…" class="input pl-10 pr-12 bg-ink-50/50 dark:bg-ink-900/40 border-transparent focus:bg-white dark:focus:bg-ink-900">
        <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-mono bg-white dark:bg-ink-800 border border-ink-200 dark:border-ink-700 rounded px-1.5 py-0.5 text-ink-500">⌘K</kbd>
      </div>
      <button onclick="toggleTheme()" class="h-10 w-10 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-600 dark:text-ink-300 transition">
        <i data-lucide="moon" class="size-[18px] dark:hidden"></i>
        <i data-lucide="sun" class="size-[18px] hidden dark:inline"></i>
      </button>
      <a href="/admin/notifiche.php" class="relative h-10 w-10 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 transition">
        <i data-lucide="bell" class="size-[18px]"></i>
        <?php if ($unreadNotifs > 0): ?><span class="absolute top-1.5 right-1.5 h-4 min-w-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"><?= $unreadNotifs ?></span><?php endif; ?>
      </a>
      <a href="/" target="_blank" title="Vai al sito" class="h-10 w-10 sm:w-auto sm:px-3.5 rounded-xl flex items-center justify-center sm:gap-2 border border-ink-200 dark:border-ink-700/80 text-ink-700 dark:text-ink-200 hover:bg-ink-50 dark:hover:bg-ink-800 transition text-sm"><i data-lucide="external-link" class="size-[16px]"></i> <span class="hidden sm:inline">Sito</span></a>
    </div>
  </header>
  <main class="p-5 lg:p-8 animate-fade-in">
  <?php $f = flash(); if ($f): ?>
    <div class="mb-5 p-3.5 rounded-xl border flex items-center gap-2.5 animate-slide-down <?= $f['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-300' : 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300' ?>">
      <i data-lucide="<?= $f['type'] === 'error' ? 'alert-circle' : 'check-circle-2' ?>" class="size-[18px]"></i>
      <span class="text-sm font-medium"><?= e($f['msg']) ?></span>
    </div>
  <?php endif; ?>
<script>
function toggleTheme(){
  var d = document.documentElement.classList.toggle('dark');
  try { localStorage.setItem('cv-theme', d ? 'dark' : 'light'); } catch(e){}
}
</script>
