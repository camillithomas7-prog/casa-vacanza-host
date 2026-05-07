<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
$title = 'Contatti';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<div class="max-w-5xl mx-auto px-5 py-16">
  <h1 class="font-display text-4xl font-bold">Contatti</h1>
  <p class="text-ink-500 mt-2 max-w-xl">Hai bisogno di informazioni? Scrivici, ti risponderemo entro poche ore.</p>
  <div class="grid md:grid-cols-3 gap-5 mt-10">
    <?php foreach ([
      ['mail','Email', cfg('site.email')],
      ['phone','Telefono / WhatsApp', cfg('site.phone')],
      ['map-pin','Indirizzo','Italia'],
    ] as $c): ?>
      <div class="card p-6">
        <div class="h-12 w-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-3"><i data-lucide="<?= $c[0] ?>"></i></div>
        <div class="font-semibold"><?= e($c[1]) ?></div>
        <div class="text-ink-500 mt-1"><?= e($c[2]) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/partials/site-footer.php';
