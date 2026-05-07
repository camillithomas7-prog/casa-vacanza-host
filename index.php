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
$cities = array_filter(array_unique(array_column($apartments, 'city')));
$totalApartments = (int)val('SELECT COUNT(*) FROM apartments WHERE active = 1');
$avgRating = (float)val('SELECT AVG(rating) FROM reviews WHERE approved = 1');
$totalReviews = (int)val('SELECT COUNT(*) FROM reviews WHERE approved = 1');
$totalGuests = '5.000+';
$topReviews = rows('SELECT r.*, a.name AS apartment_name, a.city AS apartment_city FROM reviews r JOIN apartments a ON r.apartment_id = a.id WHERE r.approved = 1 ORDER BY r.rating DESC, r.created_at DESC LIMIT 3');

$rentalTypes = "'" . implode("','", SERVICE_GROUPS['rental']) . "'";
$expTypes = "'" . implode("','", SERVICE_GROUPS['experience']) . "'";
$rentals = rows("SELECT * FROM services WHERE active = 1 AND type IN ($rentalTypes) ORDER BY position ASC LIMIT 6");
$experiences = rows("SELECT * FROM services WHERE active = 1 AND type IN ($expTypes) ORDER BY position ASC LIMIT 6");
$transfers = rows("SELECT * FROM services WHERE active = 1 AND type = 'transfer' ORDER BY position ASC LIMIT 4");

$title = 'Casa Vacanza · Affitti brevi premium';
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<!-- HERO -->
<section class="relative isolate -mt-[68px] pt-[68px] overflow-hidden">
  <div class="absolute inset-0 -z-10 gradient-mesh"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-b from-transparent via-transparent to-white dark:to-ink-950"></div>
  <div class="container-wide pt-12 md:pt-20 pb-12 md:pb-20 grid lg:grid-cols-12 gap-10 items-center">
    <div class="lg:col-span-7 animate-slide-up">
      <div class="badge-brand mb-4"><i data-lucide="sparkles" class="size-[12px]"></i> Selezionati a mano · 100% gestione diretta</div>
      <h1 class="font-serif font-semibold text-[44px] sm:text-6xl lg:text-[72px] leading-[1.02] tracking-tight text-balance">
        La tua prossima<br>
        <span class="italic font-medium relative">vacanza
          <svg class="absolute -bottom-2 left-0 w-full" height="16" viewBox="0 0 240 16" fill="none"><path d="M2 9 C 80 1, 160 1, 238 9" stroke="#ff6a0a" stroke-width="3" stroke-linecap="round" fill="none"/></svg>
        </span> inizia<br> qui.
      </h1>
      <p class="text-lg text-ink-600 dark:text-ink-300 mt-6 max-w-xl text-pretty">Appartamenti selezionati personalmente a Sharm El Sheikh: Naama Bay, Hadaba, Nabq, Sharks Bay e Old Market. Check-in fluido, assistenza in italiano, ospitalità che si sente.</p>
      <div class="flex flex-wrap gap-3 mt-8">
        <a href="/appartamenti.php" class="btn-primary h-12 px-6 text-base">Esplora appartamenti <i data-lucide="arrow-right" class="size-[16px]"></i></a>
        <a href="#come-funziona" class="btn-outline h-12 px-6 text-base">Come funziona</a>
      </div>

      <!-- TRUST -->
      <div class="grid grid-cols-3 gap-6 mt-12 max-w-md">
        <div>
          <div class="font-display font-bold text-3xl tabular-nums"><?= $totalApartments ?></div>
          <div class="text-xs text-ink-500 mt-1">Appartamenti</div>
        </div>
        <div>
          <div class="font-display font-bold text-3xl tabular-nums flex items-center gap-1"><?= $avgRating ? number_format($avgRating, 1) : '5.0' ?> <i data-lucide="star" class="size-[20px] fill-amber-400 text-amber-400"></i></div>
          <div class="text-xs text-ink-500 mt-1"><?= $totalReviews ?: 'Nuove' ?> recensioni</div>
        </div>
        <div>
          <div class="font-display font-bold text-3xl tabular-nums"><?= e($totalGuests) ?></div>
          <div class="text-xs text-ink-500 mt-1">Ospiti accolti</div>
        </div>
      </div>
    </div>

    <!-- HERO COLLAGE -->
    <div class="lg:col-span-5 relative h-[440px] lg:h-[540px] hidden md:block animate-blur-in">
      <?php $heroImgs = array_slice(array_filter(array_column($apartments, 'cover_image')), 0, 3); ?>
      <?php if (count($heroImgs) >= 1): ?>
        <div class="absolute top-0 right-0 w-[68%] h-[68%] rounded-3xl overflow-hidden shadow-pop ring-4 ring-white dark:ring-ink-900 animate-float">
          <img src="<?= e($heroImgs[0]) ?>" class="h-full w-full object-cover">
        </div>
      <?php endif; ?>
      <?php if (count($heroImgs) >= 2): ?>
        <div class="absolute bottom-0 left-0 w-[58%] h-[55%] rounded-3xl overflow-hidden shadow-pop ring-4 ring-white dark:ring-ink-900 animate-float" style="animation-delay:-2s">
          <img src="<?= e($heroImgs[1]) ?>" class="h-full w-full object-cover">
        </div>
      <?php endif; ?>
      <?php if (count($heroImgs) >= 3): ?>
        <div class="absolute top-[35%] left-[15%] w-[34%] h-[34%] rounded-2xl overflow-hidden shadow-pop ring-4 ring-white dark:ring-ink-900 animate-float" style="animation-delay:-4s">
          <img src="<?= e($heroImgs[2]) ?>" class="h-full w-full object-cover">
        </div>
      <?php endif; ?>
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
          <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Dove</div>
          <select name="city" class="w-full bg-transparent outline-none text-sm font-medium">
            <option value="">Tutte le zone</option>
            <?php foreach ($cities as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </div>
      </label>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="calendar" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Check-in</div>
          <input type="date" name="from" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="calendar" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Check-out</div>
          <input type="date" name="to" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <label class="flex items-center gap-3 px-4 py-3 rounded-xl ring-focus border border-transparent border-l-ink-100 dark:border-l-ink-800">
        <i data-lucide="users" class="size-[18px] text-brand-500 shrink-0"></i>
        <div class="flex-1"><div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Ospiti</div>
          <input type="number" name="guests" min="1" value="2" class="w-full bg-transparent outline-none text-sm font-medium"></div>
      </label>
      <button class="btn-primary h-full px-6"><i data-lucide="search" class="size-[18px]"></i> <span class="hidden sm:inline">Cerca</span></button>
    </form>
  </div>
</section>

<!-- DESTINAZIONI -->
<?php if ($cities): ?>
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8">
    <div>
      <div class="badge-brand mb-2">Zone di Sharm El Sheikh</div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight">Scegli la tua zona.</h2>
      <p class="text-ink-500 mt-2">Naama Bay, Hadaba, Sharks Bay, Nabq Bay e Old Market.</p>
    </div>
    <a href="/appartamenti.php" class="hidden sm:inline-flex text-sm text-brand-600 font-medium hover:underline">Vedi tutte →</a>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach (array_slice($cities, 0, 4) as $city):
      $cityCover = val('SELECT cover_image FROM apartments WHERE city = ? AND cover_image IS NOT NULL LIMIT 1', [$city]);
      $cityCount = (int)val('SELECT COUNT(*) FROM apartments WHERE city = ? AND active = 1', [$city]);
    ?>
      <a href="/appartamenti.php?city=<?= urlencode($city) ?>" class="relative aspect-[4/5] rounded-2xl overflow-hidden group shadow-card">
        <?php if ($cityCover): ?><img src="<?= e($cityCover) ?>" class="absolute inset-0 h-full w-full object-cover group-hover:scale-110 transition duration-700 ease-out-expo"><?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-5 text-white">
          <div class="font-serif font-semibold text-2xl"><?= e($city) ?></div>
          <div class="text-xs text-white/80 mt-1"><?= $cityCount ?> <?= $cityCount === 1 ? 'appartamento' : 'appartamenti' ?></div>
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
      <div class="badge-brand mb-2">In evidenza</div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight">Le nostre case.</h2>
      <p class="text-ink-500 mt-2">Una selezione delle proprietà più richieste.</p>
    </div>
    <a href="/appartamenti.php" class="btn-outline hidden sm:inline-flex">Vedi tutto <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
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
            <span class="text-xs text-ink-500">/notte</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- BANNER SERVIZI EXTRA -->
<section class="container-wide py-12">
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([
      ['key-round', 'Noleggi', 'Auto, scooter, golf cart, monopattini', '/noleggi.php', 'Da €15/g'],
      ['compass', 'Escursioni', 'Ras Mohammed, deserto, Cairo, Sinai', '/escursioni.php', 'Da €30/p'],
      ['plane-takeoff', 'Transfer aeroporto', 'Auto e minibus da/per SSH', '/transfer.php', 'Da €25'],
      ['waves', 'Diving & snorkeling', 'Reef e relitti del Mar Rosso', '/escursioni.php?cat=diving', 'Da €45/p'],
    ] as $i => $s): ?>
      <a href="<?= e($s[3]) ?>" class="card p-5 card-hover group animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
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

<!-- NOLEGGI -->
<?php if ($rentals): ?>
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8 flex-wrap gap-3">
    <div>
      <div class="badge-brand mb-2"><i data-lucide="key-round" class="size-[12px]"></i> Noleggi</div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight">Muoviti come vuoi.</h2>
      <p class="text-ink-500 mt-2">Auto, golf cart, scooter e monopattini elettrici.</p>
    </div>
    <a href="/noleggi.php" class="btn-outline hidden sm:inline-flex">Tutti i noleggi <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($rentals as $i => $s): ?>
      <a href="/noleggio.php?slug=<?= e($s['slug']) ?>" class="group block animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
        <div class="aspect-[4/3] rounded-2xl overflow-hidden bg-ink-100 dark:bg-ink-900 relative shadow-card">
          <?php if ($s['cover_image']): ?><img src="<?= e($s['cover_image']) ?>" alt="<?= e($s['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo"><?php endif; ?>
          <div class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="<?= e(serviceTypeIcon($s['type'])) ?>" class="size-[12px]"></i> <?= e(serviceTypeShort($s['type'])) ?></div>
          <?php if ($s['resort_name']): ?><div class="absolute top-3 right-3 badge bg-brand-500 text-white">presso resort</div><?php endif; ?>
        </div>
        <div class="mt-4 flex items-center justify-between gap-2">
          <div class="min-w-0">
            <div class="font-display font-bold text-lg truncate"><?= e($s['name']) ?></div>
            <?php if ($s['resort_name']): ?><div class="text-xs text-ink-500 mt-0.5 flex items-center gap-1 truncate"><i data-lucide="map-pin" class="size-[12px]"></i> <?= e($s['resort_name']) ?></div><?php endif; ?>
          </div>
          <div class="text-right shrink-0">
            <span class="font-display font-bold text-lg tabular-nums"><?= fmtMoney((float)$s['daily_price']) ?></span>
            <div class="text-xs text-ink-500">/giorno</div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="mt-8 sm:hidden text-center">
    <a href="/noleggi.php" class="btn-outline inline-flex">Tutti i noleggi <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</section>
<?php endif; ?>

<!-- ESCURSIONI -->
<?php if ($experiences): ?>
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8 flex-wrap gap-3">
    <div>
      <div class="badge-brand mb-2"><i data-lucide="compass" class="size-[12px]"></i> Escursioni</div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight">Vivi Sharm.</h2>
      <p class="text-ink-500 mt-2">Snorkeling, deserto, diving, tour culturali — con guida italiana.</p>
    </div>
    <a href="/escursioni.php" class="btn-outline hidden sm:inline-flex">Tutte le escursioni <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
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
              <span class="text-xs text-white/80">da</span>
              <span class="font-display font-bold text-xl tabular-nums"><?= fmtMoney((float)$s['price_per_person']) ?></span>
              <span class="text-xs text-white/80">/persona</span>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="mt-8 sm:hidden text-center">
    <a href="/escursioni.php" class="btn-outline inline-flex">Tutte le escursioni <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</section>
<?php endif; ?>

<!-- TRANSFER -->
<?php if ($transfers): ?>
<section class="container-wide py-16">
  <div class="flex items-end justify-between mb-8 flex-wrap gap-3">
    <div>
      <div class="badge-brand mb-2"><i data-lucide="plane-takeoff" class="size-[12px]"></i> Transfer aeroporto</div>
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight">Dal volo al letto.</h2>
      <p class="text-ink-500 mt-2">Transfer privato dall'aeroporto SSH a tutti i villaggi.</p>
    </div>
    <a href="/transfer.php" class="btn-outline hidden sm:inline-flex">Tutte le tratte <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php foreach ($transfers as $i => $s): ?>
      <a href="/transfer.php?slug=<?= e($s['slug']) ?>" class="card overflow-hidden p-0 card-hover group animate-slide-up" style="animation-delay:<?= $i * 50 ?>ms">
        <div class="aspect-[16/10] bg-ink-100 dark:bg-ink-900 relative">
          <?php if ($s['cover_image']): ?><img src="<?= e($s['cover_image']) ?>" alt="<?= e($s['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition duration-700 ease-out-expo"><?php endif; ?>
          <div class="absolute top-3 left-3 badge bg-white/95 text-ink-900 backdrop-blur"><i data-lucide="users" class="size-[12px]"></i> max <?= (int)$s['vehicle_capacity'] ?></div>
        </div>
        <div class="p-4">
          <div class="text-xs text-ink-500 truncate"><?= e($s['from_location']) ?> →</div>
          <div class="font-display font-bold truncate"><?= e($s['to_location']) ?></div>
          <div class="flex items-baseline gap-1 mt-2">
            <span class="font-display font-bold text-lg tabular-nums"><?= fmtMoney((float)$s['price_per_group']) ?></span>
            <span class="text-xs text-ink-500">a tratta</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="mt-8 sm:hidden text-center">
    <a href="/transfer.php" class="btn-outline inline-flex">Tutte le tratte <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</section>
<?php endif; ?>

<!-- COME FUNZIONA -->
<section id="come-funziona" class="container-wide py-20">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <div class="badge-brand mb-3">Come funziona</div>
    <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight">Tre passi, zero stress.</h2>
  </div>
  <div class="grid md:grid-cols-3 gap-5 relative">
    <div class="hidden md:block absolute top-12 left-[16%] right-[16%] h-px border-t-2 border-dashed border-brand-300/50"></div>
    <?php foreach ([
      ['1','search', 'Trova', 'Sfoglia la collezione, filtra per zona, date e ospiti. Vedi disponibilità live.'],
      ['2','calendar-check', 'Prenota', 'Compila il modulo. Ricevi conferma immediata via WhatsApp/Email con tutto il necessario.'],
      ['3','key-round', 'Soggiorna', 'Check-in semplice, istruzioni chiare. Siamo qui per qualsiasi cosa, 7/7.'],
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
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([
      ['shield-check', 'Pagamenti sicuri', 'Acconto e saldo trasparenti, ricevuta sempre.'],
      ['key-round', 'Check-in semplice', 'Istruzioni chiare, supporto WhatsApp 7/7.'],
      ['star', 'Recensioni reali', 'Solo clienti verificati, niente sorprese.'],
      ['heart', 'Selezione curata', 'Ogni casa visitata personalmente.'],
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
    <div class="badge-brand mb-3">Cosa dicono di noi</div>
    <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight">Recensioni vere, da clienti veri.</h2>
  </div>
  <div class="grid md:grid-cols-3 gap-5">
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

<!-- CTA FINALE -->
<section class="container-wide py-16">
  <div class="relative isolate overflow-hidden p-10 md:p-16 text-center rounded-3xl shadow-pop bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700">
    <div class="absolute -top-20 -right-20 h-80 w-80 rounded-full bg-white/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-20 -left-20 h-80 w-80 rounded-full bg-white/15 blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 opacity-[0.05] pointer-events-none" style="background-image: radial-gradient(white 1px, transparent 1px); background-size: 24px 24px;"></div>
    <div class="relative">
      <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-tight text-white text-balance">Pronti a partire?</h2>
      <p class="text-white/85 mt-3 max-w-xl mx-auto text-pretty">Trova la casa giusta in pochi click. Conferma immediata, prezzi senza sorprese.</p>
      <div class="flex flex-wrap gap-3 justify-center mt-7">
        <a href="/appartamenti.php" class="btn h-12 px-7 bg-white text-brand-700 hover:bg-ink-50 text-base font-semibold shadow-lg">Esplora ora <i data-lucide="arrow-right" class="size-[16px]"></i></a>
        <a href="/contatti.php" class="btn h-12 px-7 bg-white/10 text-white border border-white/30 hover:bg-white/20 backdrop-blur text-base">Contattaci</a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/partials/site-footer.php';
