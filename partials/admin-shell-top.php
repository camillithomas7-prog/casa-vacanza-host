<?php
require_once __DIR__ . '/../lib/auth.php';
$current = currentUser();
$path = $_SERVER['SCRIPT_NAME'] ?? '';
function navItem($href, $icon, $label, $current) {
    $active = strpos($current, $href) !== false || ($href === '/admin/index.php' && in_array($current, ['/admin/index.php','/admin/']));
    $cls = $active ? 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300' : 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800';
    echo '<a href="' . e($href) . '" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium ' . $cls . '"><i data-lucide="' . e($icon) . '" class="size-[18px]"></i> ' . e($label) . '</a>';
}
$unreadNotifs = (int)val('SELECT COUNT(*) FROM notifications WHERE is_read = 0');
?>
<div class="min-h-screen flex bg-ink-50 dark:bg-ink-950" x-data="{open:false}">
<aside class="fixed lg:sticky inset-y-0 left-0 z-40 w-72 bg-white dark:bg-ink-900 border-r border-ink-100 dark:border-ink-800 transition-transform lg:translate-x-0" :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
  <div class="h-16 px-5 flex items-center justify-between border-b border-ink-100 dark:border-ink-800">
    <a href="/admin/index.php" class="flex items-center gap-2 font-display font-bold">
      <span class="h-9 w-9 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center"><i data-lucide="home" class="size-[18px]"></i></span>
      <?= e(cfg('site.name')) ?>
    </a>
    <button class="lg:hidden btn-ghost" @click="open=false"><i data-lucide="x" class="size-[18px]"></i></button>
  </div>
  <nav class="p-3 space-y-0.5">
    <?php navItem('/admin/index.php', 'bar-chart-3', 'Dashboard', $path); ?>
    <?php navItem('/admin/appartamenti.php', 'building-2', 'Appartamenti', $path); ?>
    <?php navItem('/admin/calendario.php', 'calendar', 'Calendario', $path); ?>
    <?php navItem('/admin/prenotazioni.php', 'bookmark-check', 'Prenotazioni', $path); ?>
    <?php navItem('/admin/prezzi.php', 'wallet', 'Prezzi', $path); ?>
    <?php navItem('/admin/spese.php', 'receipt', 'Spese & bilancio', $path); ?>
    <?php navItem('/admin/template.php', 'message-square', 'Template messaggi', $path); ?>
    <?php navItem('/admin/recensioni.php', 'star', 'Recensioni', $path); ?>
    <?php navItem('/admin/coupon.php', 'tag', 'Coupon', $path); ?>
    <?php navItem('/admin/impostazioni.php', 'settings', 'Impostazioni', $path); ?>
  </nav>
  <div class="absolute bottom-0 left-0 right-0 p-3 border-t border-ink-100 dark:border-ink-800">
    <a href="/admin/logout.php" class="w-full btn-outline justify-start"><i data-lucide="log-out" class="size-[16px]"></i> Esci</a>
  </div>
</aside>
<div x-show="open" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="open=false" style="display:none"></div>

<div class="flex-1 min-w-0">
  <header class="sticky top-0 z-20 h-16 bg-white/80 dark:bg-ink-950/80 backdrop-blur border-b border-ink-100 dark:border-ink-800">
    <div class="h-full px-5 flex items-center gap-3">
      <button class="lg:hidden btn-ghost" @click="open=true"><i data-lucide="menu" class="size-[20px]"></i></button>
      <div class="flex-1 max-w-lg relative">
        <i data-lucide="search" class="size-[16px] absolute left-3 top-1/2 -translate-y-1/2 text-ink-400"></i>
        <input placeholder="Cerca prenotazioni, clienti, appartamenti..." class="input pl-10">
      </div>
      <button onclick="toggleTheme()" class="h-9 w-9 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-600 dark:text-ink-300">
        <i data-lucide="moon" class="size-[18px] dark:hidden"></i>
        <i data-lucide="sun" class="size-[18px] hidden dark:inline"></i>
      </button>
      <a href="/admin/notifiche.php" class="relative h-9 w-9 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800">
        <i data-lucide="bell" class="size-[18px]"></i>
        <?php if ($unreadNotifs > 0): ?><span class="absolute -top-0.5 -right-0.5 h-4 min-w-4 px-1 rounded-full bg-red-500 text-white text-[10px] flex items-center justify-center"><?= $unreadNotifs ?></span><?php endif; ?>
      </a>
      <a href="/" class="hidden sm:inline-flex btn-outline">Sito pubblico</a>
    </div>
  </header>
  <main class="p-5 lg:p-8 animate-fade-in">
  <?php $f = flash(); if ($f): ?>
    <div class="mb-4 p-3 rounded-xl border <?= $f['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' ?>"><?= e($f['msg']) ?></div>
  <?php endif; ?>
