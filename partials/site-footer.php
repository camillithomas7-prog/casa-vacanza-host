<footer class="mt-32 border-t border-ink-100 dark:border-ink-800/80 bg-ink-50/40 dark:bg-ink-950/60">
  <div class="container-wide py-16 grid lg:grid-cols-12 gap-10">
    <div class="lg:col-span-5">
      <img src="/assets/logo-256.png?v=2" alt="<?= e(cfg('site.name')) ?>" class="h-20 w-auto">
      <p class="text-ink-500 dark:text-ink-400 mt-4 max-w-md text-pretty">
        Appartamenti selezionati a Sharm El Sheikh: Naama Bay, Hadaba, Sharks Bay, Old Market e Nabq. Gestione diretta in italiano, soggiorni curati, prezzi trasparenti.
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
        <li><a href="/noleggi.php" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Noleggi</a></li>
        <li><a href="/escursioni.php" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Escursioni</a></li>
        <li><a href="/transfer.php" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Transfer aeroporto</a></li>
        <li><a href="/contatti.php" class="text-ink-700 dark:text-ink-300 hover:text-brand-600">Contatti</a></li>
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
