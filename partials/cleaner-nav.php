<?php
// Tab bar inferiore per le pagine "signora delle pulizie".
// Richiede $cleanerToken e opzionalmente $cleanerActiveTab ('today' | 'calendar' | 'settings').
$_tabs = [
  ['today',    '/pulizie.php',             'sparkles', 'Oggi'],
  ['calendar', '/pulizie-calendario.php',  'calendar', 'Calendario'],
  ['settings', '/pulizie-notifiche.php',   'bell',     'Notifiche'],
];
$_active = $cleanerActiveTab ?? '';
$_t = rawurlencode($cleanerToken ?? '');
?>
<nav class="fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-ink-900/95 backdrop-blur-xl border-t border-ink-100 dark:border-ink-800/80 pb-[env(safe-area-inset-bottom)]">
  <div class="max-w-md mx-auto grid grid-cols-3">
    <?php foreach ($_tabs as $tab):
      $isActive = $_active === $tab[0];
    ?>
      <a href="<?= e($tab[1]) ?>?t=<?= $_t ?>" class="flex flex-col items-center justify-center gap-0.5 py-2.5 transition <?= $isActive ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-500 hover:text-ink-700 dark:text-ink-400 dark:hover:text-ink-200' ?>">
        <i data-lucide="<?= e($tab[2]) ?>" class="size-[20px]"></i>
        <span class="text-[11px] font-semibold"><?= e($tab[3]) ?></span>
        <?php if ($isActive): ?>
          <span class="absolute top-0 h-0.5 w-10 bg-emerald-500 rounded-full"></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
