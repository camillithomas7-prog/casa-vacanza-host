<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

if (!tableExists('apartments')) {
    $title = 'Setup richiesto';
    require __DIR__ . '/partials/head.php';
    echo '<div class="container-narrow card-elev p-10 mt-20 text-center"><div class="h-16 w-16 mx-auto rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-4"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v3m0 16v3m11-11h-3M4 12H1M19.07 4.93l-2.12 2.12M7.05 16.95l-2.12 2.12M19.07 19.07l-2.12-2.12M7.05 7.05L4.93 4.93"/></svg></div><h1 class="font-serif text-3xl font-semibold">Database non inizializzato</h1><p class="mt-3 text-ink-500">Esegui <code class="px-2 py-1 rounded bg-ink-100 dark:bg-ink-800 font-mono text-sm">/setup.php</code> per creare le tabelle e caricare i dati demo.</p><a href="/setup.php" class="btn-primary mt-6 inline-flex">Esegui setup</a></div>';
    require __DIR__ . '/partials/site-footer.php';
    exit;
}

require_once __DIR__ . '/lib/services.php';

$apartments = rows('SELECT a.* FROM apartments a WHERE a.active = 1 ORDER BY a.created_at DESC LIMIT 6');

// Carica zone dalla tabella admin (con fallback a derivazione automatica dagli appartamenti se la tabella non esiste o è vuota)
$zones = [];
/* Mostra solo le zone con almeno 1 appartamento attivo */
try {
    $zones = rows("SELECT z.*, (SELECT COUNT(*) FROM apartments a WHERE a.city = z.name AND a.active = 1) AS apt_count
                   FROM zones z
                   WHERE z.active = 1
                   HAVING apt_count > 0
                   ORDER BY z.position ASC, z.name ASC");
} catch (Throwable $e) {}
if (!$zones) {
    $cityNames = array_filter(array_unique(array_column($apartments, 'city')));
    foreach ($cityNames as $cn) {
        $zones[] = ['name' => $cn, 'kind' => 'zone', 'image' => null, 'description' => null];
    }
}
$cities = array_column($zones, 'name');
$totalApartments = (int)val('SELECT COUNT(*) FROM apartments WHERE active = 1');
$avgRating = (float)val('SELECT AVG(rating) FROM reviews WHERE approved = 1');
$totalReviews = (int)val('SELECT COUNT(*) FROM reviews WHERE approved = 1');
$totalGuests = '5.000+';
$topReviews = rows('SELECT r.*, a.name AS apartment_name, a.city AS apartment_city FROM reviews r JOIN apartments a ON r.apartment_id = a.id WHERE r.approved = 1 ORDER BY r.rating DESC, r.created_at DESC LIMIT 3');

$cPhone = setting('contact_phone', cfg('site.phone'));
$waNumber = preg_replace('/\D/', '', $cPhone ?: '');
$waLink = $waNumber ? 'https://wa.me/' . $waNumber . '?text=' . rawurlencode(t('home.about.wa_message')) : '/contatti.php';

$rentalTypes = "'" . implode("','", SERVICE_GROUPS['rental']) . "'";
$expTypes = "'" . implode("','", SERVICE_GROUPS['experience']) . "'";
$rentals = rows("SELECT * FROM services WHERE active = 1 AND type IN ($rentalTypes) ORDER BY position ASC LIMIT 6");
$experiences = rows("SELECT * FROM services WHERE active = 1 AND type IN ($expTypes) ORDER BY position ASC LIMIT 6");
$transfers = rows("SELECT * FROM services WHERE active = 1 AND type = 'transfer' ORDER BY position ASC LIMIT 4");

$title = t('meta.home_title');
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
$_lp = currentLang() !== 'it' ? '?lang=' . urlencode(currentLang()) : '';
?>
<!-- HERO -->
<section class="relative isolate -mt-[68px] pt-[68px] overflow-hidden">
  <div class="absolute inset-0 -z-10 gradient-mesh"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-b from-transparent via-transparent to-white dark:to-ink-950"></div>
  <div class="container-wide pt-12 md:pt-20 pb-12 md:pb-20 grid lg:grid-cols-12 gap-10 items-center">
    <div class="lg:col-span-7 animate-slide-up">
      <div class="badge-brand mb-4"><i data-lucide="sparkles" class="size-[12px]"></i> <?= e(t('hero.badge')) ?></div>
      <h1 class="font-serif font-semibold text-[44px] sm:text-6xl lg:text-[72px] leading-[1.02] tracking-tight text-balance">
        <?= e(t('hero.title.line1')) ?><br>
        <span class="italic font-medium relative"><?= e(t('hero.title.italic')) ?>
          <svg class="absolute -bottom-2 left-0 w-full" height="16" viewBox="0 0 240 16" fill="none"><path d="M2 9 C 80 1, 160 1, 238 9" stroke="#ff6a0a" stroke-width="3" stroke-linecap="round" fill="none"/></svg>
        </span><br> <?= e(t('hero.title.line3')) ?>
      </h1>
      <p class="text-lg text-ink-600 dark:text-ink-300 mt-6 max-w-xl text-pretty"><?= e(t('hero.subtitle')) ?></p>
      <div class="flex flex-wrap gap-3 mt-8">
        <a href="/appartamenti.php<?= $_lp ?>" class="btn-primary h-12 px-6 text-base"><?= e(t('hero.cta.explore')) ?> <i data-lucide="arrow-right" class="size-[16px]"></i></a>
        <a href="#come-funziona" class="btn-outline h-12 px-6 text-base"><?= e(t('hero.cta.how')) ?></a>
      </div>

      <!-- TRUST -->
      <div class="grid grid-cols-3 gap-6 mt-12 max-w-md">
        <div>
          <div class="font-display font-bold text-3xl tabular-nums"><?= $totalApartments ?></div>
          <div class="text-xs text-ink-500 mt-1"><?= e(t('hero.stats.apartments')) ?></div>
        </div>
        <div>
          <div class="font-display font-bold text-3xl tabular-nums flex items-center gap-1"><?= $avgRating ? number_format($avgRating, 1) : '5.0' ?> <i data-lucide="star" class="size-[20px] fill-amber-400 text-amber-400"></i></div>
          <div class="text-xs text-ink-500 mt-1"><?= $totalReviews ? $totalReviews . ' ' . e(t('common.reviews')) : e(t('hero.stats.reviews_new')) ?></div>
        </div>
        <div>
          <div class="font-display font-bold text-3xl tabular-nums"><?= e($totalGuests) ?></div>
          <div class="text-xs text-ink-500 mt-1"><?= e(t('hero.stats.guests')) ?></div>
        </div>
      </div>
    </div>

    <!-- HERO COLLAGE - Sharm El Sheikh locations -->
    <div class="lg:col-span-5 relative h-[440px] lg:h-[540px] hidden md:block animate-blur-in">
      <div class="absolute top-0 right-0 w-[68%] h-[68%] rounded-3xl overflow-hidden shadow-pop ring-4 ring-white dark:ring-ink-900 animate-float">
        <img src="/assets/sharm/naama_bay.jpg" alt="Naama Bay, Sharm El Sheikh" class="h-full w-full object-cover">
      </div>
      <div class="absolute bottom-0 left-0 w-[58%] h-[55%] rounded-3xl overflow-hidden shadow-pop ring-4 ring-white dark:ring-ink-900 animate-float" style="animation-delay:-2s">
        <img src="/assets/sharm/sinai_sunset.jpg" alt="Sinai desert sunset" class="h-full w-full object-cover">
      </div>
      <div class="absolute top-[35%] left-[15%] w-[34%] h-[34%] rounded-2xl overflow-hidden shadow-pop ring-4 ring-white dark:ring-ink-900 animate-float" style="animation-delay:-4s">
        <img src="/assets/sharm/ras_mohammed.jpg" alt="Ras Mohammed coral reef" class="h-full w-full object-cover">
      </div>
      <div class="absolute -top-8 -left-4 h-32 w-32 rounded-full bg-brand-200/50 blur-2xl -z-10 pointer-events-none"></div>
      <div class="absolute -bottom-8 -right-4 h-40 w-40 rounded-full bg-sea-200/50 blur-2xl -z-10 pointer-events-none"></div>
    </div>
  </div>

  <!-- SEARCH BAR -->
  <div class="container-wide -mt-2 pb-12">
    <form action="/appartamenti.php" method="get" class="card-elev p-2 grid grid-cols-1 md:grid-cols-[1.4fr_1fr_1fr_0.9fr_auto] gap-1 max-w-5xl mx-auto">
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent">
        <i data-lucide="map-pin" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1">
          <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500"><?= e(t('common.where')) ?></div>
          <select name="city" class="w-full bg-transparent outline-none text-sm font-medium">
            <option value=""><?= e(t('common.all_areas')) ?></option>
            <?php foreach ($cities as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </div>
      </label>
      <?php if (isWeeklyOnly()): ?>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="calendar" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Giorno di arrivo</div>
          <input type="date" name="from" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="hourglass" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Durata</div>
          <select name="weeks" class="w-full bg-transparent outline-none text-sm font-medium">
            <option value="1">1 settimana</option>
            <option value="2">2 settimane</option>
            <option value="3">3 settimane</option>
            <option value="4">1 mese</option>
          </select></div>
      </label>
      <?php else: ?>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="calendar" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500"><?= e(t('common.checkin')) ?></div>
          <input type="date" name="from" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="calendar" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500"><?= e(t('common.checkout')) ?></div>
          <input type="date" name="to" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <?php endif; ?>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="users" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500"><?= e(t('common.guests')) ?></div>
          <input type="number" name="guests" min="1" value="2" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <button class="btn-primary h-12 md:h-full px-6 md:px-5"><i data-lucide="search" class="size-[18px]"></i> <?= e(t('common.search')) ?></button>
    </form>
  </div>
</section>

<!-- DESTINAZIONI -->
<?php if ($cities): ?>
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8">
    <div>
      <div class="badge-brand mb-2"><?= e(t('home.zones.badge')) ?></div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight"><?= e(t('home.zones.title')) ?></h2>
      <p class="text-ink-500 mt-2"><?= e(t('home.zones.sub')) ?></p>
    </div>
    <a href="/appartamenti.php<?= $_lp ?>" class="hidden sm:inline-flex text-sm text-brand-600 font-medium hover:underline"><?= e(t('common.see_all_arrow')) ?></a>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php foreach ($zones as $z):
      $zCover = $z['image'] ?? null;
      if (!$zCover) {
        // Fallback: prima foto di un appartamento in quella città
        $zCover = val('SELECT cover_image FROM apartments WHERE city = ? AND cover_image IS NOT NULL LIMIT 1', [$z['name']]);
      }
      $zCount = (int)val('SELECT COUNT(*) FROM apartments WHERE city = ? AND active = 1', [$z['name']]);
    ?>
      <a href="/appartamenti.php?city=<?= urlencode($z['name']) ?><?= $_lp ? '&lang=' . urlencode(currentLang()) : '' ?>" class="relative aspect-[4/5] rounded-2xl overflow-hidden group shadow-card">
        <?php if ($zCover): ?><img src="<?= e($zCover) ?>" alt="<?= e($z['name']) ?>" loading="lazy" class="absolute inset-0 h-full w-full object-cover group-hover:scale-110 transition duration-700 ease-out-expo"><?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-5 text-white">
          <?php if (!empty($z['kind']) && $z['kind'] !== 'zone'): ?>
            <div class="text-[10px] uppercase tracking-wider text-white/70 mb-1"><?= e(t('home.zones.kind_' . $z['kind'])) ?></div>
          <?php endif; ?>
          <div class="font-serif font-semibold text-2xl"><?= e($z['name']) ?></div>
          <div class="text-xs text-white/80 mt-1"><?= $zCount ?> <?= $zCount === 1 ? e(t('home.zones.unit_one')) : e(t('home.zones.unit_many')) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- APPARTAMENTI -->
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8">
    <div>
      <div class="badge-brand mb-2"><?= e(t('home.featured.badge')) ?></div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight"><?= e(t('home.featured.title')) ?></h2>
      <p class="text-ink-500 mt-2"><?= e(t('home.featured.sub')) ?></p>
    </div>
    <a href="/appartamenti.php<?= $_lp ?>" class="btn-outline hidden sm:inline-flex"><?= e(t('common.see_all')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($apartments as $i => $a):
      $cover = $a['cover_image'] ?: (rows('SELECT url FROM photos WHERE apartment_id = ? ORDER BY position ASC LIMIT 1', [$a['id']])[0]['url'] ?? '');
      $reviews = rows('SELECT rating FROM reviews WHERE apartment_id = ? AND approved = 1', [$a['id']]);
      $rating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;
    ?>
      <a href="/appartamento.php?slug=<?= e($a['slug']) ?>" class="group block animate-slide-up" style="animation-delay:<?= $i * 60 ?>ms">
        <div class="aspect-[4/5] rounded-2xl overflow-hidden bg-ink-100 dark:bg-ink-900 relative shadow-card">
          <img src="<?= e($cover) ?>" alt="<?= e($a['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo">
          <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/0 to-black/15"></div>
          <?php if ($rating): ?><span class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="star" class="size-[12px] fill-amber-400 text-amber-400"></i> <?= number_format($rating, 1) ?></span><?php endif; ?>
          <div class="absolute top-3 right-3 h-9 w-9 rounded-full bg-white/15 backdrop-blur-md flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition"><i data-lucide="arrow-up-right" class="size-[16px]"></i></div>
          <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
            <div class="text-xs uppercase tracking-wider text-white/70"><?= e($a['city'] ?: $a['country']) ?></div>
            <div class="font-display font-bold text-xl mt-0.5 truncate"><?= e($a['name']) ?></div>
          </div>
        </div>
        <div class="mt-4 flex items-center justify-between">
          <div class="flex gap-3 text-sm text-ink-500 dark:text-ink-400">
            <span class="flex items-center gap-1"><i data-lucide="users" class="size-[14px]"></i> <?= (int)$a['guests'] ?></span>
            <span class="flex items-center gap-1"><i data-lucide="bed-double" class="size-[14px]"></i> <?= (int)$a['bedrooms'] ?></span>
            <span class="flex items-center gap-1"><i data-lucide="bath" class="size-[14px]"></i> <?= (int)$a['bathrooms'] ?></span>
          </div>
          <div class="text-right">
            <span class="font-display font-bold text-lg tabular-nums"><?= fmtMoney((float)$a['base_price']) ?></span>
            <span class="text-xs text-ink-500"><?= e(t('common.per_night')) ?></span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- BANNER SERVIZI EXTRA -->
<?php
  $_extraBanners = array_values(array_filter([
    featureEnabled('rentals')    ? ['key-round',     t('home.banner.rentals.title'),    t('home.banner.rentals.sub'),    '/noleggi.php',                'Da €15/g'] : null,
    featureEnabled('excursions') ? ['compass',       t('home.banner.excursions.title'), t('home.banner.excursions.sub'), '/escursioni.php',             'Da €30/p'] : null,
    featureEnabled('transfer')   ? ['plane-takeoff', t('home.banner.transfer.title'),   t('home.banner.transfer.sub'),   '/transfer.php',               'Da €25']   : null,
    featureEnabled('excursions') ? ['waves',         t('home.banner.diving.title'),     t('home.banner.diving.sub'),     '/escursioni.php?cat=diving',  'Da €45/p'] : null,
  ]));
  if ($_extraBanners):
?>
<section class="container-wide py-12">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?= min(4, count($_extraBanners)) ?> gap-4">
    <?php foreach ($_extraBanners as $i => $s):
      $href = $s[3] . ($_lp ? (strpos($s[3], '?') !== false ? '&lang=' . urlencode(currentLang()) : $_lp) : '');
    ?>
      <a href="<?= e($href) ?>" class="card p-5 card-hover group animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
        <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center shadow-[0_6px_16px_-6px_rgba(240,78,0,.5)] mb-3"><i data-lucide="<?= $s[0] ?>" class="size-[18px]"></i></div>
        <div class="font-display font-bold"><?= e($s[1]) ?></div>
        <p class="text-ink-500 mt-1 text-xs"><?= e($s[2]) ?></p>
        <div class="flex items-center justify-between mt-3 pt-3 border-t border-ink-100 dark:border-ink-800/80">
          <span class="text-xs font-semibold text-brand-600"><?= e($s[4]) ?></span>
          <i data-lucide="arrow-right" class="size-[14px] text-ink-400 group-hover:text-brand-600 group-hover:translate-x-1 transition-all"></i>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- NOLEGGI -->
<?php if (featureEnabled('rentals') && $rentals): ?>
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8 flex-wrap gap-3">
    <div>
      <div class="badge-brand mb-2"><i data-lucide="key-round" class="size-[12px]"></i> <?= e(t('nav.rentals')) ?></div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight"><?= e(t('home.rentals.title')) ?></h2>
      <p class="text-ink-500 mt-2"><?= e(t('home.rentals.sub')) ?></p>
    </div>
    <a href="/noleggi.php<?= $_lp ?>" class="btn-outline hidden sm:inline-flex"><?= e(t('home.rentals.see_all')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($rentals as $i => $s): ?>
      <a href="/noleggio.php?slug=<?= e($s['slug']) ?>" class="group block animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
        <div class="aspect-[4/3] rounded-2xl overflow-hidden bg-ink-100 dark:bg-ink-900 relative shadow-card">
          <?php if ($s['cover_image']): ?><img src="<?= e($s['cover_image']) ?>" alt="<?= e($s['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo"><?php endif; ?>
          <div class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[12px]"></i> <?= e(serviceTypeShort($s['type'])) ?></div>
          <?php if ($s['resort_name']): ?><div class="absolute top-3 right-3 badge bg-brand-500 text-white"><?= e(t('rent.at_resort')) ?></div><?php endif; ?>
        </div>
        <div class="mt-4 flex items-center justify-between gap-2">
          <div class="min-w-0">
            <div class="font-display font-bold text-lg truncate"><?= e($s['name']) ?></div>
            <?php if ($s['resort_name']): ?><div class="text-xs text-ink-500 mt-0.5 flex items-center gap-1 truncate"><i data-lucide="map-pin" class="size-[12px]"></i> <?= e($s['resort_name']) ?></div><?php endif; ?>
          </div>
          <div class="text-right shrink-0">
            <span class="font-display font-bold text-lg tabular-nums"><?= fmtMoney((float)$s['daily_price']) ?></span>
            <div class="text-xs text-ink-500"><?= e(t('common.per_day')) ?></div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="mt-8 sm:hidden text-center">
    <a href="/noleggi.php<?= $_lp ?>" class="btn-outline inline-flex"><?= e(t('home.rentals.see_all')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</section>
<?php endif; ?>

<!-- ESCURSIONI -->
<?php if (featureEnabled('excursions') && $experiences): ?>
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8 flex-wrap gap-3">
    <div>
      <div class="badge-brand mb-2"><i data-lucide="compass" class="size-[12px]"></i> <?= e(t('nav.excursions')) ?></div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight"><?= e(t('home.excursions.title')) ?></h2>
      <p class="text-ink-500 mt-2"><?= e(t('home.excursions.sub')) ?></p>
    </div>
    <a href="/escursioni.php<?= $_lp ?>" class="btn-outline hidden sm:inline-flex"><?= e(t('home.excursions.see_all')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($experiences as $i => $s): ?>
      <a href="/escursione.php?slug=<?= e($s['slug']) ?>" class="group block animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
        <div class="aspect-[4/5] rounded-2xl overflow-hidden bg-ink-100 dark:bg-ink-900 relative shadow-card">
          <?php if ($s['cover_image']): ?><img src="<?= e($s['cover_image']) ?>" alt="<?= e($s['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo"><?php endif; ?>
          <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/0 to-black/15"></div>
          <div class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[12px]"></i> <?= e(serviceTypeShort($s['type'])) ?></div>
          <?php if ($s['duration_hours']): ?><div class="absolute top-3 right-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="clock" class="size-[12px]"></i> <?= rtrim(rtrim(number_format((float)$s['duration_hours'], 1), '0'), '.') ?> h</div><?php endif; ?>
          <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
            <div class="font-display font-bold text-xl line-clamp-2"><?= e($s['name']) ?></div>
            <div class="flex items-baseline gap-1 mt-1">
              <span class="text-xs text-white/80"><?= e(t('common.from')) ?></span>
              <span class="font-display font-bold text-xl tabular-nums"><?= fmtMoney((float)$s['price_per_person']) ?></span>
              <span class="text-xs text-white/80"><?= e(t('common.per_person')) ?></span>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="mt-8 sm:hidden text-center">
    <a href="/escursioni.php<?= $_lp ?>" class="btn-outline inline-flex"><?= e(t('home.excursions.see_all')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</section>
<?php endif; ?>

<!-- TRANSFER -->
<?php if (featureEnabled('transfer') && $transfers): ?>
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8 flex-wrap gap-3">
    <div>
      <div class="badge-brand mb-2"><i data-lucide="plane-takeoff" class="size-[12px]"></i> <?= e(t('home.banner.transfer.title')) ?></div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight"><?= e(t('home.transfer.title')) ?></h2>
      <p class="text-ink-500 mt-2"><?= e(t('home.transfer.sub')) ?></p>
    </div>
    <a href="/transfer.php<?= $_lp ?>" class="btn-outline hidden sm:inline-flex"><?= e(t('home.transfer.see_all')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php foreach ($transfers as $i => $s): ?>
      <a href="/transfer.php?slug=<?= e($s['slug']) ?>" class="card overflow-hidden p-0 card-hover group animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
        <div class="aspect-[16/10] bg-ink-100 dark:bg-ink-900 relative">
          <?php if ($s['cover_image']): ?><img src="<?= e($s['cover_image']) ?>" alt="<?= e($s['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo"><?php endif; ?>
          <div class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="users" class="size-[12px]"></i> <?= e(t('common.max')) ?> <?= (int)$s['vehicle_capacity'] ?></div>
        </div>
        <div class="p-4">
          <div class="text-xs text-ink-500 truncate"><?= e($s['from_location']) ?> →</div>
          <div class="font-display font-bold truncate"><?= e($s['to_location']) ?></div>
          <div class="flex items-baseline gap-1 mt-2">
            <span class="font-display font-bold text-lg tabular-nums"><?= fmtMoney((float)$s['price_per_group']) ?></span>
            <span class="text-xs text-ink-500"><?= e(t('common.per_trip')) ?></span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="mt-8 sm:hidden text-center">
    <a href="/transfer.php<?= $_lp ?>" class="btn-outline inline-flex"><?= e(t('home.transfer.see_all')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</section>
<?php endif; ?>

<!-- COME FUNZIONA -->
<section id="come-funziona" class="container-wide py-20">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <div class="badge-brand mb-3"><?= e(t('home.howit.badge')) ?></div>
    <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight"><?= e(t('home.howit.title')) ?></h2>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5 relative">
    <div class="hidden md:block absolute top-12 left-[16%] right-[16%] h-px border-t-2 border-dashed border-brand-300/50"></div>
    <?php foreach ([
      ['1','search', t('home.howit.s1.t'), t('home.howit.s1.d')],
      ['2','calendar-check', t('home.howit.s2.t'), t('home.howit.s2.d')],
      ['3','key-round', t('home.howit.s3.t'), t('home.howit.s3.d')],
    ] as $i => $s): ?>
      <div class="card-elev p-7 relative animate-slide-up" style="animation-delay:<?= $i * 100 ?>ms">
        <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center shadow-[0_8px_18px_-6px_rgba(240,78,0,.5)] mb-5"><i data-lucide="<?= $s[1] ?>" class="size-[22px]"></i></div>
        <div class="absolute top-7 right-7 font-serif text-5xl font-semibold text-ink-200 dark:text-ink-700"><?= $s[0] ?></div>
        <div class="font-display font-bold text-xl"><?= e($s[2]) ?></div>
        <p class="text-ink-500 mt-2 text-pretty"><?= e($s[3]) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- BENEFIT STRIP -->
<section class="container-wide py-12">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([
      ['shield-check', t('home.benefit.b1.t'), t('home.benefit.b1.d')],
      ['key-round', t('home.benefit.b2.t'), t('home.benefit.b2.d')],
      ['star', t('home.benefit.b3.t'), t('home.benefit.b3.d')],
      ['heart', t('home.benefit.b4.t'), t('home.benefit.b4.d')],
    ] as $i => $f): ?>
      <div class="card p-5 card-hover">
        <div class="h-11 w-11 rounded-xl bg-brand-50 dark:bg-brand-500/15 text-brand-600 flex items-center justify-center mb-3"><i data-lucide="<?= $f[0] ?>" class="size-[18px]"></i></div>
        <div class="font-semibold"><?= e($f[1]) ?></div>
        <p class="text-sm text-ink-500 mt-1 text-pretty"><?= e($f[2]) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- TESTIMONIAL -->
<?php if ($topReviews): ?>
<section class="container-wide py-16">
  <div class="text-center max-w-2xl mx-auto mb-10">
    <div class="badge-brand mb-3"><?= e(t('home.testi.badge')) ?></div>
    <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight"><?= e(t('home.testi.title')) ?></h2>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <?php foreach ($topReviews as $r):
      $parts = explode(' ', trim($r['author_name']));
      $initials = mb_strtoupper(mb_substr($parts[0] ?? '·', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    ?>
      <div class="card-elev p-7 relative">
        <div class="text-yellow-500 flex gap-0.5 mb-4">
          <?php for ($i = 0; $i < (int)$r['rating']; $i++): ?><i data-lucide="star" class="size-[16px] fill-amber-400 text-amber-400"></i><?php endfor; ?>
        </div>
        <?php if ($r['title']): ?><div class="font-serif text-xl font-semibold mb-2"><?= e($r['title']) ?></div><?php endif; ?>
        <p class="text-ink-700 dark:text-ink-300 leading-relaxed">«<?= e($r['body']) ?>»</p>
        <div class="flex items-center gap-3 mt-5 pt-5 border-t border-ink-100 dark:border-ink-800/80">
          <span class="h-10 w-10 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center font-semibold text-sm"><?= e($initials) ?></span>
          <div>
            <div class="font-medium text-sm"><?= e($r['author_name']) ?></div>
            <div class="text-xs text-ink-500"><?= e($r['apartment_name']) ?> · <?= e($r['apartment_city']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ABOUT PATRIZIA -->
<section class="container-wide py-16 sm:py-20">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">
    <div class="relative">
      <div class="absolute -inset-4 bg-gradient-to-br from-brand-100 via-brand-50 to-sand-100 dark:from-brand-500/10 dark:via-brand-500/5 dark:to-transparent rounded-[2.5rem] -z-10 blur-xl opacity-70"></div>
      <div class="absolute -top-3 -left-3 h-24 w-24 rounded-2xl bg-brand-500/15 -z-10"></div>
      <div class="absolute -bottom-3 -right-3 h-32 w-32 rounded-3xl bg-sea-300/30 dark:bg-sea-500/15 -z-10"></div>
      <img src="/assets/patrizia-portrait.jpg?v=2"
           alt="Patrizia Mancini"
           loading="lazy"
           class="relative w-full h-auto rounded-3xl shadow-pop object-cover">
      <div class="absolute bottom-5 left-5 bg-white/95 dark:bg-ink-900/95 backdrop-blur-sm rounded-2xl px-4 py-2.5 shadow-card flex items-center gap-2.5">
        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <span class="text-sm font-medium"><?= e(t('home.about.available')) ?></span>
      </div>
    </div>

    <div>
      <div class="badge-brand mb-4"><i data-lucide="user-round" class="size-[14px]"></i> <?= e(t('home.about.badge')) ?></div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight text-balance">
        <span class="text-gradient-brand">Patrizia Mancini</span><?= e(t('home.about.title_post')) ?>
      </h2>

      <div class="mt-6 space-y-4 text-ink-700 dark:text-ink-300 text-[17px] leading-relaxed text-pretty">
        <p><?= t('home.about.p1') ?></p>
        <p><?= t('home.about.p2') ?></p>
      </div>

      <div class="grid grid-cols-3 gap-4 mt-8">
        <div>
          <div class="flex items-center gap-2"><i data-lucide="check-circle-2" class="size-[20px] text-brand-600 shrink-0"></i><div class="font-display text-base sm:text-lg font-bold"><?= e(t('home.about.feature1.t')) ?></div></div>
          <div class="text-xs text-ink-500 mt-1"><?= e(t('home.about.feature1.d')) ?></div>
        </div>
        <div>
          <div class="flex items-center gap-2"><i data-lucide="message-circle" class="size-[20px] text-brand-600 shrink-0"></i><div class="font-display text-base sm:text-lg font-bold"><?= e(t('home.about.feature2.t')) ?></div></div>
          <div class="text-xs text-ink-500 mt-1"><?= e(t('home.about.feature2.d')) ?></div>
        </div>
        <div>
          <div class="flex items-center gap-2"><i data-lucide="map-pin" class="size-[20px] text-brand-600 shrink-0"></i><div class="font-display text-base sm:text-lg font-bold"><?= e(t('home.about.feature3.t')) ?></div></div>
          <div class="text-xs text-ink-500 mt-1"><?= e(t('home.about.feature3.d')) ?></div>
        </div>
      </div>

      <div class="flex flex-wrap gap-3 mt-8">
        <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="btn-primary"><i data-lucide="message-circle" class="size-[16px]"></i> <?= e(t('home.about.cta_wa')) ?></a>
        <a href="/appartamenti.php<?= $_lp ?>" class="btn-outline"><i data-lucide="building-2" class="size-[16px]"></i> <?= e(t('home.about.cta_apt')) ?></a>
      </div>

      <blockquote class="mt-8 pl-5 border-l-2 border-brand-500 italic text-ink-600 dark:text-ink-400 text-pretty">
        <?= e(t('home.about.quote')) ?>
      </blockquote>
    </div>
  </div>
</section>

<!-- CTA FINALE -->
<section class="container-wide py-16">
  <div class="relative isolate overflow-hidden p-10 md:p-16 text-center rounded-3xl shadow-pop bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700">
    <div class="absolute -top-20 -right-20 h-80 w-80 rounded-full bg-white/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-20 -left-20 h-80 w-80 rounded-full bg-white/15 blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 opacity-[0.05] pointer-events-none" style="background-image: radial-gradient(white 1px, transparent 1px); background-size: 24px 24px;"></div>
    <div class="relative">
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight text-white text-balance"><?= e(t('home.cta.title')) ?></h2>
      <p class="text-white/85 mt-3 max-w-xl mx-auto text-pretty"><?= e(t('home.cta.sub')) ?></p>
      <div class="flex flex-wrap gap-3 justify-center mt-7">
        <a href="/appartamenti.php<?= $_lp ?>" class="btn h-12 px-7 bg-white text-brand-700 hover:bg-ink-50 text-base font-semibold shadow-lg"><?= e(t('hero.cta.explore')) ?> <i data-lucide="arrow-right" class="size-[16px]"></i></a>
        <a href="/contatti.php<?= $_lp ?>" class="btn h-12 px-7 bg-white/10 text-white border border-white/30 hover:bg-white/20 backdrop-blur text-base"><?= e(t('nav.contact')) ?></a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/partials/site-footer.php';
