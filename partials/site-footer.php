<footer class="border-t border-ink-100 dark:border-ink-800 mt-24">
  <div class="max-w-7xl mx-auto px-5 py-10 grid md:grid-cols-3 gap-8 text-sm">
    <div>
      <div class="font-display font-bold text-lg mb-2"><?= e(cfg('site.name')) ?></div>
      <p class="text-ink-500 max-w-sm">Affitti brevi gestiti con cura. Trova la tua prossima casa per le vacanze fra appartamenti selezionati.</p>
    </div>
    <div>
      <div class="font-semibold mb-2">Esplora</div>
      <ul class="space-y-1 text-ink-500">
        <li><a href="/appartamenti.php">Tutti gli appartamenti</a></li>
        <li><a href="/contatti.php">Contattaci</a></li>
        <li><a href="/admin/login.php">Area admin</a></li>
      </ul>
    </div>
    <div>
      <div class="font-semibold mb-2">Contatti</div>
      <ul class="space-y-1 text-ink-500">
        <li><?= e(cfg('site.email')) ?></li>
        <li><?= e(cfg('site.phone')) ?></li>
      </ul>
    </div>
  </div>
  <div class="border-t border-ink-100 dark:border-ink-800 py-4 text-center text-xs text-ink-500">© <?= date('Y') ?> <?= e(cfg('site.name')) ?>. Tutti i diritti riservati.</div>
</footer>
<script>lucide.createIcons();</script>
</body></html>
