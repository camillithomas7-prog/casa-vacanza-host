<?php
$curLang = currentLang();
// Voci di nav, filtrate dai feature flag (Patrizia attiva i servizi via admin).
$_navItems = array_values(array_filter([
    ['/', 'nav.home', null],
    ['/appartamenti.php', 'nav.apartments', 'apartments'],
    ['/noleggi.php', 'nav.rentals', 'rentals'],
    ['/escursioni.php', 'nav.excursions', 'excursions'],
    ['/transfer.php', 'nav.transfer', 'transfer'],
    ['/contatti.php', 'nav.contact', null],
], fn($n) => $n[2] === null || featureEnabled($n[2])));
?>
<header x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 8"
  class="sticky top-0 z-40 transition-all duration-300"
  :class="scrolled ? 'backdrop-blur-xl bg-white/80 dark:bg-ink-950/85 border-b border-ink-100 dark:border-ink-800/60 shadow-soft' : 'bg-transparent'">
  <div class="container-wide h-[68px] flex items-center justify-between gap-2">
    <a href="/<?= $curLang !== 'it' ? '?lang=' . e($curLang) : '' ?>" class="flex items-center gap-2 group shrink-0">
      <img src="/assets/logo-256.png?v=2" alt="<?= e(cfg('site.name')) ?>" class="h-12 w-auto group-hover:scale-[1.04] transition-transform" />
    </a>
    <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
      <?php foreach ($_navItems as $n):
        $href = $n[0] . ($curLang !== 'it' ? '?lang=' . urlencode($curLang) : '');
      ?>
        <a href="<?= e($href) ?>" class="px-3 py-2 rounded-xl text-ink-700 dark:text-ink-300 hover:text-ink-900 dark:hover:text-white hover:bg-ink-100/70 dark:hover:bg-ink-800/60 transition"><?= e(t($n[1])) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="flex items-center gap-1.5 sm:gap-2">
      <!-- LANG SWITCHER -->
      <div x-data="{ open: false }" @click.outside="open=false" class="relative">
        <button @click="open=!open" :aria-expanded="open" aria-label="<?= e(t('meta.lang_label')) ?>" class="h-10 px-2.5 sm:px-3 rounded-xl flex items-center gap-1.5 hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-700 dark:text-ink-200 transition text-sm font-medium">
          <span class="text-base leading-none"><?= $LANGUAGES[$curLang]['flag'] ?></span>
          <span class="hidden sm:inline uppercase tracking-wider text-xs"><?= e($curLang) ?></span>
          <i data-lucide="chevron-down" class="size-[14px] opacity-60"></i>
        </button>
        <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-44 rounded-xl bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800/80 shadow-card overflow-hidden z-50" style="display:none">
          <?php foreach ($LANGUAGES as $lc => $l): ?>
            <a href="<?= e(langSwitch($lc)) ?>" class="flex items-center gap-2.5 px-3.5 py-2.5 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/50 <?= $lc === $curLang ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-semibold' : '' ?>">
              <span class="text-lg leading-none"><?= $l['flag'] ?></span>
              <span class="flex-1"><?= e($l['native']) ?></span>
              <?php if ($lc === $curLang): ?><i data-lucide="check" class="size-[14px]"></i><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <button onclick="toggleTheme()" class="h-10 w-10 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-600 dark:text-ink-300 transition" aria-label="Theme">
        <i data-lucide="moon" class="size-[18px] dark:hidden"></i>
        <i data-lucide="sun" class="size-[18px] hidden dark:inline"></i>
      </button>
      <a href="/admin/login.php" class="hidden lg:inline-flex h-10 px-3.5 rounded-xl items-center text-sm text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800 transition"><?= e(t('nav.admin')) ?></a>
      <a href="/appartamenti.php<?= $curLang !== 'it' ? '?lang=' . e($curLang) : '' ?>" class="hidden md:inline-flex btn-primary"><?= e(t('cta.book_now')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
      <button class="md:hidden btn-ghost p-2" @click="$dispatch('toggle-menu')" aria-label="Menu"><i data-lucide="menu" class="size-[20px]"></i></button>
    </div>
  </div>
</header>
<div x-data="{ open: false }" @toggle-menu.window="open = !open" x-show="open" x-cloak
     class="md:hidden fixed inset-0 z-50 bg-white dark:bg-ink-950 p-6 animate-fade-in overflow-y-auto" @click.self="open=false">
  <div class="flex items-center justify-between">
    <img src="/assets/logo-256.png?v=2" alt="<?= e(cfg('site.name')) ?>" class="h-10 w-auto">
    <button @click="open=false" class="btn-ghost"><i data-lucide="x" class="size-[20px]"></i></button>
  </div>
  <div class="flex flex-col gap-1 mt-8 text-lg">
    <?php
      $_mobileNav = array_merge($_navItems, [['/admin/login.php', 'nav.admin', null]]);
      foreach ($_mobileNav as $n):
        $href = $n[0] . ($curLang !== 'it' ? '?lang=' . urlencode($curLang) : '');
    ?>
      <a href="<?= e($href) ?>" class="px-3 py-3 rounded-xl hover:bg-ink-100 dark:hover:bg-ink-800"><?= e(t($n[1])) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<script>
function toggleTheme(){
  var d = document.documentElement.classList.toggle('dark');
  try { localStorage.setItem('cv-theme', d ? 'dark' : 'light'); } catch(e){}
}
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
