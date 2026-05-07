<footer class="mt-32 border-t border-ink-100 dark:border-ink-800/80 bg-ink-50/40 dark:bg-ink-950/60">
  <div class="container-wide py-16 grid lg:grid-cols-12 gap-10">
    <div class="lg:col-span-5">
      <img src="/assets/logo-256.png?v=2" alt="<?= e(cfg('site.name')) ?>" class="h-20 w-auto">
      <p class="text-ink-500 dark:text-ink-400 mt-4 max-w-md text-pretty">
        <?= e(t('foot.tagline')) ?>
      </p>
      <?php
        $sFb = setting('social_facebook');
        $sIg = setting('social_instagram');
        $sTk = setting('social_tiktok');
        $sEmail = setting('contact_email', cfg('site.email'));
      ?>
      <div class="flex items-center gap-3 mt-6">
        <?php if ($sFb): ?>
          <a href="<?= e($sFb) ?>" target="_blank" rel="noopener" aria-label="Facebook" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center text-ink-500 hover:text-white hover:bg-[#1877f2] hover:border-[#1877f2] transition">
            <svg viewBox="0 0 24 24" fill="currentColor" class="h-[18px] w-[18px]" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
        <?php endif; ?>
        <?php if ($sIg): ?>
          <a href="<?= e($sIg) ?>" target="_blank" rel="noopener" aria-label="Instagram" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center text-ink-500 hover:text-white hover:bg-gradient-to-br hover:from-[#feda75] hover:via-[#d62976] hover:to-[#4f5bd5] hover:border-transparent transition">
            <svg viewBox="0 0 24 24" fill="currentColor" class="h-[18px] w-[18px]" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.849.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.849.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
          </a>
        <?php endif; ?>
        <?php if ($sTk): ?>
          <a href="<?= e($sTk) ?>" target="_blank" rel="noopener" aria-label="TikTok" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center text-ink-500 hover:text-white hover:bg-ink-900 dark:hover:bg-white dark:hover:text-ink-900 hover:border-ink-900 transition">
            <svg viewBox="0 0 24 24" fill="currentColor" class="h-[18px] w-[18px]" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5.8 20.1a6.34 6.34 0 0 0 10.86-4.43V8.66a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.84-.09Z"/></svg>
          </a>
        <?php endif; ?>
        <a href="mailto:<?= e($sEmail) ?>" aria-label="Email" class="h-10 w-10 rounded-xl border border-ink-200 dark:border-ink-700/80 flex items-center justify-center text-ink-500 hover:text-brand-600 hover:border-brand-300 transition">
          <i data-lucide="mail" class="size-[18px]"></i>
        </a>
      </div>
    </div>

    <?php $cl = currentLang(); $lp = $cl !== 'it' ? '?lang=' . urlencode($cl) : ''; ?>
    <div class="lg:col-span-2">
      <div class="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-4"><?= e(t('foot.explore')) ?></div>
      <ul class="space-y-2.5 text-sm">
        <li><a href="/<?= $lp ?>" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('nav.home')) ?></a></li>
        <li><a href="/appartamenti.php<?= $lp ?>" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('nav.apartments')) ?></a></li>
        <li><a href="/noleggi.php<?= $lp ?>" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('nav.rentals')) ?></a></li>
        <li><a href="/escursioni.php<?= $lp ?>" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('nav.excursions')) ?></a></li>
        <li><a href="/transfer.php<?= $lp ?>" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('nav.transfer')) ?></a></li>
        <li><a href="/contatti.php<?= $lp ?>" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('nav.contact')) ?></a></li>
      </ul>
    </div>

    <div class="lg:col-span-2">
      <div class="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-4"><?= e(t('foot.support')) ?></div>
      <ul class="space-y-2.5 text-sm">
        <li><a href="#" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('foot.faq')) ?></a></li>
        <li><a href="#" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('foot.terms')) ?></a></li>
        <li><a href="#" class="text-ink-700 dark:text-ink-300 hover:text-brand-600"><?= e(t('foot.privacy')) ?></a></li>
      </ul>
    </div>

    <div class="lg:col-span-3">
      <div class="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-4"><?= e(t('foot.contact_us')) ?></div>
      <div class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300"><i data-lucide="mail" class="size-[14px] text-brand-500"></i> <?= e(setting('contact_email', cfg('site.email'))) ?></div>
      <div class="flex items-center gap-2 text-sm text-ink-700 dark:text-ink-300 mt-1.5"><i data-lucide="phone" class="size-[14px] text-brand-500"></i> <?= e(setting('contact_phone', cfg('site.phone') && !str_starts_with(cfg('site.phone'), '+39 000') ? cfg('site.phone') : '+39 371 352 4264')) ?></div>
      <div class="mt-4 p-3 rounded-xl bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800/80">
        <div class="text-xs text-ink-500"><?= e(t('foot.response_in')) ?></div>
        <div class="font-semibold"><?= e(t('foot.response_time')) ?></div>
      </div>
    </div>
  </div>
  <div class="border-t border-ink-100 dark:border-ink-800/80">
    <div class="container-wide py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ink-500">
      <span>© <?= date('Y') ?> <?= e(setting('site_name', cfg('site.name'))) ?>. <?= e(t('foot.copyright')) ?></span>
      <span><?= e(t('foot.crafted')) ?> · v1.0</span>
    </div>
  </div>
</footer>
<script>lucide.createIcons();</script>
</body></html>
