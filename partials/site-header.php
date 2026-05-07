<header x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 8"
  class="sticky top-0 z-40 transition-all duration-300"
  :class="scrolled ? 'backdrop-blur-xl bg-white/80 dark:bg-ink-950/85 border-b border-ink-100 dark:border-ink-800/60 shadow-soft' : 'bg-transparent'">
  <div class="container-wide h-[68px] flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 group">
      <img src="/assets/logo-256.png" alt="<?= e(cfg('site.name')) ?>" class="h-12 w-auto group-hover:scale-[1.04] transition-transform" />
    </a>
    <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
      <?php foreach ([['/','Home'],['/appartamenti.php','Appartamenti'],['/contatti.php','Contatti']] as $n): ?>
        <a href="<?= e($n[0]) ?>" class="px-3.5 py-2 rounded-xl text-ink-700 dark:text-ink-300 hover:text-ink-900 dark:hover:text-white hover:bg-ink-100/70 dark:hover:bg-ink-800/60 transition"><?= e($n[1]) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="flex items-center gap-2">
      <button onclick="toggleTheme()" class="h-10 w-10 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-600 dark:text-ink-300 transition" aria-label="Tema">
        <i data-lucide="moon" class="size-[18px] dark:hidden"></i>
        <i data-lucide="sun" class="size-[18px] hidden dark:inline"></i>
      </button>
      <a href="/admin/login.php" class="hidden sm:inline-flex h-10 px-3.5 rounded-xl items-center text-sm text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800 transition">Area admin</a>
      <a href="/appartamenti.php" class="hidden md:inline-flex btn-primary">Prenota ora <i data-lucide="arrow-right" class="size-[14px]"></i></a>
      <button class="md:hidden btn-ghost" @click="$dispatch('toggle-menu')"><i data-lucide="menu" class="size-[20px]"></i></button>
    </div>
  </div>
</header>
<div x-data="{ open: false }" @toggle-menu.window="open = !open" x-show="open" x-cloak
     class="md:hidden fixed inset-0 z-50 bg-white dark:bg-ink-950 p-6 animate-fade-in" @click.self="open=false">
  <div class="flex items-center justify-between">
    <img src="/assets/logo-256.png" alt="<?= e(cfg('site.name')) ?>" class="h-10 w-auto">
    <button @click="open=false" class="btn-ghost"><i data-lucide="x" class="size-[20px]"></i></button>
  </div>
  <div class="flex flex-col gap-1 mt-8 text-lg">
    <?php foreach ([['/','Home'],['/appartamenti.php','Appartamenti'],['/contatti.php','Contatti'],['/admin/login.php','Area admin']] as $n): ?>
      <a href="<?= e($n[0]) ?>" class="px-3 py-3 rounded-xl hover:bg-ink-100 dark:hover:bg-ink-800"><?= e($n[1]) ?></a>
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
