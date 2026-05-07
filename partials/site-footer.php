<footer class="mt-32 border-t border-ink-100 dark:border-ink-800/80 bg-ink-50/40 dark:bg-ink-950/60">
  <div class="container-wide py-16 grid lg:grid-cols-12 gap-10">
    <div class="lg:col-span-5">
      <div class="flex items-center gap-2.5">
        <span class="h-10 w-10 rounded-2xl bg-gradient-to-br from-brand-400 via-brand-500 to-brand-700 text-white flex items-center justify-center shadow-[0_8px_24px_-8px_rgba(240,78,0,.55)]">
          <i data-lucide="home" class="size-[18px]"></i>
        </span>
        <span class="font-display font-extrabold text-lg"><?= e(cfg('site.name')) ?></span>
      </div>
      <p class="text-ink-500 dark:text-ink-400 mt-4 max-w-md text-pretty">
        Affitti brevi gestiti con cura. Una collezione di case selezionate, dal centro di Roma alla costiera amalfitana, per chi cerca soggiorni autentici e curati nel dettaglio.
      </p>
      <div class="flex items-center gap-3 mt-6">
        <a href="#" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center text-ink-500 hover:text-brand-600 hover:border-brand-300 transition"><i data-lucide="instagram" class="size-[16px]"></i></a>
        <a href="#" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center text-ink-500 hover:text-brand-600 hover:border-brand-300 transition"><i data-lucide="facebook" class="size-[16px]"></i></a>
        <a href="mailto:<?= e(cfg('site.email')) ?>" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center text-ink-500 hover:text-brand-600 hover:border-brand-300 transition"><i data-lucide="mail" class="size-[16px]"></i></a>
      </div>
    </div>

    <div class="lg:col-span-2">
      <div class="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-4">Esplora</div>
      <ul class="space-y-2.5 text-sm">
        <li><a href="/" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Home</a></li>
        <li><a href="/appartamenti.php" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Appartamenti</a></li>
        <li><a href="/contatti.php" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Contatti</a></li>
        <li><a href="/admin/login.php" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Area admin</a></li>
      </ul>
    </div>

    <div class="lg:col-span-2">
      <div class="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-4">Supporto</div>
      <ul class="space-y-2.5 text-sm">
        <li><a href="#" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">FAQ</a></li>
        <li><a href="#" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Termini</a></li>
        <li><a href="#" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Privacy</a></li>
      </ul>
    </div>

    <div class="lg:col-span-3">
      <div class="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-4">Contattaci</div>
      <div class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300"><i data-lucide="mail" class="size-[14px] text-brand-500"></i> <?= e(cfg('site.email')) ?></div>
      <div class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300 mt-1.5"><i data-lucide="phone" class="size-[14px] text-brand-500"></i> <?= e(cfg('site.phone')) ?></div>
      <div class="mt-4 p-3 rounded-xl bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800/80">
        <div class="text-xs text-ink-500">Risposta entro</div>
        <div class="font-semibold">poche ore, 7/7</div>
      </div>
    </div>
  </div>
  <div class="border-t border-ink-100 dark:border-ink-800/80">
    <div class="container-wide py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ink-500">
      <span>© <?= date('Y') ?> <?= e(cfg('site.name')) ?>. Tutti i diritti riservati.</span>
      <span>Progettato con cura · v1.0</span>
    </div>
  </div>
</footer>
<script>lucide.createIcons();</script>
</body></html>
