<header class="sticky top-0 z-40 backdrop-blur bg-white/80 dark:bg-ink-950/80 border-b border-ink-100 dark:border-ink-800">
  <div class="max-w-7xl mx-auto px-5 h-16 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 font-display font-bold text-lg">
      <span class="h-9 w-9 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center"><i data-lucide="home" class="size-[18px]"></i></span>
      <?= e(cfg('site.name')) ?>
    </a>
    <nav class="hidden md:flex items-center gap-7 text-sm">
      <a href="/" class="hover:text-brand-600">Home</a>
      <a href="/appartamenti.php" class="hover:text-brand-600">Appartamenti</a>
      <a href="/contatti.php" class="hover:text-brand-600">Contatti</a>
      <a href="/admin/login.php" class="hover:text-brand-600">Area admin</a>
    </nav>
    <div class="flex items-center gap-2">
      <button onclick="toggleTheme()" class="h-9 w-9 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-600 dark:text-ink-300" aria-label="Tema">
        <i data-lucide="moon" class="size-[18px] dark:hidden"></i>
        <i data-lucide="sun" class="size-[18px] hidden dark:inline"></i>
      </button>
      <a href="/appartamenti.php" class="hidden md:inline-flex btn-primary">Prenota ora</a>
    </div>
  </div>
</header>
<script>
function toggleTheme(){
  var d = document.documentElement.classList.toggle('dark');
  try { localStorage.setItem('cv-theme', d ? 'dark' : 'light'); } catch(e){}
}
</script>
