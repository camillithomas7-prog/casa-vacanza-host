<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';
$title = t('nav.contact');
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
$cEmail = setting('contact_email', cfg('site.email'));
$cPhone = setting('contact_phone', cfg('site.phone') && !str_starts_with(cfg('site.phone'), '+39 000') ? cfg('site.phone') : '+39 371 352 4264');
?>
<section class="relative isolate -mt-[68px] pt-[68px] overflow-hidden">
  <div class="absolute inset-0 -z-10 gradient-mesh"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-b from-transparent to-white dark:to-ink-950"></div>
  <div class="container-narrow pt-16 md:pt-20 pb-10">
    <div class="max-w-2xl">
      <div class="badge-brand mb-3"><?= e(t('ct.badge')) ?></div>
      <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl font-semibold tracking-tight text-balance"><?= e(t('ct.title')) ?></h1>
      <p class="text-ink-600 dark:text-ink-300 mt-4 text-lg max-w-xl text-pretty"><?= e(t('ct.sub')) ?></p>
    </div>
  </div>
</section>

<section class="container-wide pb-20">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <?php foreach ([
      ['mail', t('ct.card.email'), $cEmail, 'mailto:' . $cEmail, t('ct.card.email.note')],
      ['phone', t('ct.card.phone'), $cPhone, 'https://wa.me/' . preg_replace('/\D/', '', $cPhone ?: ''), t('ct.card.phone.note')],
      ['map-pin', t('ct.card.where'), t('ct.card.where.value'), '#', t('ct.card.where.note')],
    ] as $c): ?>
      <a href="<?= e($c[3]) ?>" class="card p-7 card-hover group">
        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 dark:from-brand-500/10 dark:to-brand-500/5 text-brand-600 flex items-center justify-center mb-4 group-hover:scale-105 transition"><i data-lucide="<?= $c[0] ?>" class="size-[20px]"></i></div>
        <div class="font-display font-bold text-xl"><?= e($c[1]) ?></div>
        <div class="text-ink-700 dark:text-ink-300 mt-1"><?= e($c[2]) ?></div>
        <div class="text-xs text-ink-500 mt-3 flex items-center gap-1"><i data-lucide="clock" class="size-[12px]"></i> <?= e($c[4]) ?></div>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="card-elev p-8 md:p-12 mt-8 grid md:grid-cols-2 gap-8 items-center">
    <div>
      <h2 class="font-serif text-3xl md:text-4xl font-semibold tracking-tight whitespace-pre-line"><?= e(t('ct.tailored.title')) ?></h2>
      <p class="text-ink-600 dark:text-ink-300 mt-3 text-pretty"><?= e(t('ct.tailored.sub')) ?></p>
    </div>
    <div class="flex flex-wrap gap-3">
      <a href="mailto:<?= e($cEmail) ?>" class="btn-primary h-12 px-6 text-base"><i data-lucide="mail" class="size-[16px]"></i> <?= e(t('ct.email_us')) ?></a>
      <a href="https://wa.me/<?= preg_replace('/\D/', '', $cPhone ?: '') ?>" class="btn-outline h-12 px-6 text-base"><i data-lucide="message-circle" class="size-[16px]"></i> WhatsApp</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/partials/site-footer.php';
