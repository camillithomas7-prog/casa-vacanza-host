<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

$slug = $_GET['slug'] ?? '';
$a = row('SELECT * FROM apartments WHERE slug = ? AND active = 1', [$slug]);
if (!$a) { http_response_code(404); $title=t('apt.detail.not_found_title'); require __DIR__.'/partials/head.php'; require __DIR__.'/partials/site-header.php'; echo '<div class="container-narrow card p-14 mt-20 text-center"><h1 class="font-serif text-3xl">' . e(t('apt.detail.not_found_title')) . '</h1><a href="/appartamenti.php?lang=' . e(currentLang()) . '" class="btn-primary mt-6 inline-flex">' . e(t('apt.detail.not_found_back')) . '</a></div>'; require __DIR__.'/partials/site-footer.php'; exit; }

$photos = rows('SELECT * FROM photos WHERE apartment_id = ? ORDER BY position ASC', [$a['id']]);
if (!$photos && $a['cover_image']) $photos = [['url' => $a['cover_image'], 'alt' => $a['name']]];
$reviews = rows('SELECT * FROM reviews WHERE apartment_id = ? AND approved = 1 ORDER BY created_at DESC', [$a['id']]);
$bookings = rows('SELECT check_in, check_out, status FROM bookings WHERE apartment_id = ? AND status != "cancelled"', [$a['id']]);
$blocks = rows('SELECT start_date, end_date FROM date_blocks WHERE apartment_id = ?', [$a['id']]);
$amenities = parseAmenities($a['amenities']);
$rating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;

$AMENITY_ICON = [
  'wifi' => 'wifi', 'aria condizionata' => 'wind', 'aria' => 'wind', 'piscina' => 'waves',
  'cucina' => 'chef-hat', 'parcheggio' => 'car', 'tv' => 'tv', 'lavatrice' => 'shirt',
  'vista mare' => 'eye', 'balcone' => 'door-open', 'asciugamani' => 'bath',
  'smart tv' => 'tv', 'riscaldamento' => 'thermometer', 'colazione' => 'coffee',
];

$title = $a['name'];
$metaDesc = mb_substr(strip_tags($a['description']), 0, 160);
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<?php
  $mediaList = [];
  foreach ($photos as $p) {
      $mediaList[] = ['url' => $p['url'], 'isVideo' => isVideoUrl($p['url'])];
  }
?>
<div class="container-wide pt-8 pb-4" x-data="{ lightbox: null, media: <?= e(json_encode($mediaList)) ?> }">
  <a href="/appartamenti.php?lang=<?= e(currentLang()) ?>" class="text-sm text-ink-500 hover:text-brand-600 inline-flex items-center gap-1"><i data-lucide="chevron-left" class="size-[14px]"></i> <?= e(t('apt.detail.back')) ?></a>
  <div class="mt-4 flex items-end justify-between flex-wrap gap-4">
    <div>
      <div class="badge-soft mb-3"><i data-lucide="map-pin" class="size-[12px]"></i> <?= e($a['city'] ?: $a['country']) ?></div>
      <h1 class="font-serif text-4xl md:text-6xl font-semibold tracking-tight max-w-2xl text-balance"><?= e($a['name']) ?></h1>
      <div class="flex flex-wrap items-center gap-4 text-sm text-ink-500 mt-3">
        <?php if ($rating): ?><span class="flex items-center gap-1"><i data-lucide="star" class="size-[14px] fill-amber-400 text-amber-400"></i> <strong class="text-ink-900 dark:text-white"><?= number_format($rating, 1) ?></strong> · <?= e(t('apt.detail.reviews_count', ['n' => count($reviews)])) ?></span><?php endif; ?>
        <?php if ($a['address']): ?><span class="flex items-center gap-1"><i data-lucide="map-pin" class="size-[14px]"></i> <?= e($a['address']) ?></span><?php endif; ?>
        <span class="flex items-center gap-1"><i data-lucide="users" class="size-[14px]"></i> <?= e(t('apt.detail.up_to_guests', ['n' => (int)$a['guests']])) ?></span>
      </div>
    </div>
  </div>

  <!-- GALLERY -->
  <div class="grid grid-cols-4 grid-rows-2 gap-2.5 mt-6 rounded-3xl overflow-hidden h-[300px] sm:h-[440px]">
    <?php $vis = array_slice($photos, 0, 5); $main = $vis[0] ?? null; $thumbs = array_slice($vis, 1); ?>
    <?php if ($main): $mainIsVideo = isVideoUrl($main['url']); ?>
      <button @click="lightbox=0" class="col-span-4 sm:col-span-2 row-span-2 relative group bg-ink-100 dark:bg-ink-900">
        <?php if ($mainIsVideo): ?>
          <video src="<?= e($main['url']) ?>" muted preload="metadata" class="absolute inset-0 h-full w-full object-cover"></video>
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="h-16 w-16 rounded-full bg-black/60 backdrop-blur flex items-center justify-center"><i data-lucide="play" class="size-[28px] text-white"></i></div>
          </div>
        <?php else: ?>
          <img src="<?= e($main['url']) ?>" class="absolute inset-0 h-full w-full object-cover group-hover:scale-[1.03] transition duration-[700ms] ease-out-expo">
        <?php endif; ?>
      </button>
    <?php endif; ?>
    <?php foreach ($thumbs as $i => $p): $tIsVideo = isVideoUrl($p['url']); ?>
      <button @click="lightbox=<?= $i + 1 ?>" class="hidden sm:block relative group bg-ink-100 dark:bg-ink-900 col-span-1 row-span-1">
        <?php if ($tIsVideo): ?>
          <video src="<?= e($p['url']) ?>" muted preload="metadata" class="absolute inset-0 h-full w-full object-cover"></video>
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="h-10 w-10 rounded-full bg-black/60 backdrop-blur flex items-center justify-center"><i data-lucide="play" class="size-[16px] text-white"></i></div>
          </div>
        <?php else: ?>
          <img src="<?= e($p['url']) ?>" class="absolute inset-0 h-full w-full object-cover group-hover:scale-[1.03] transition duration-[700ms] ease-out-expo">
        <?php endif; ?>
        <?php if ($i === count($thumbs) - 1 && count($photos) > 5): ?>
          <span class="absolute inset-0 bg-black/45 text-white flex items-center justify-center font-semibold backdrop-blur-[2px]">+<?= count($photos) - 5 ?></span>
        <?php endif; ?>
      </button>
    <?php endforeach; ?>
  </div>
  <button @click="lightbox=0" class="mt-3 inline-flex items-center gap-2 text-sm text-ink-600 dark:text-ink-300 hover:text-brand-600">
    <i data-lucide="layout-grid" class="size-[14px]"></i> <?= e(t('apt.detail.show_all_photos', ['n' => count($photos)])) ?>
  </button>

  <!-- LIGHTBOX (foto + video) -->
  <div x-show="lightbox !== null" x-cloak @click="lightbox=null" class="fixed inset-0 z-50 bg-ink-950/95 backdrop-blur-sm flex items-center justify-center" style="display:none">
    <button class="absolute top-5 right-5 h-10 w-10 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20" @click.stop="lightbox=null"><i data-lucide="x" class="size-[20px]"></i></button>
    <button class="absolute left-5 h-12 w-12 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20" @click.stop="lightbox = (lightbox - 1 + media.length) % media.length"><i data-lucide="chevron-left" class="size-[24px]"></i></button>
    <template x-if="lightbox !== null && media[lightbox] && media[lightbox].isVideo">
      <video :src="media[lightbox].url" controls autoplay class="max-h-[88vh] max-w-[88vw] rounded-2xl shadow-pop bg-black" @click.stop></video>
    </template>
    <template x-if="lightbox !== null && media[lightbox] && !media[lightbox].isVideo">
      <img :src="media[lightbox].url" class="max-h-[88vh] max-w-[88vw] object-contain rounded-2xl shadow-pop" @click.stop>
    </template>
    <button class="absolute right-5 h-12 w-12 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20" @click.stop="lightbox = (lightbox + 1) % media.length"><i data-lucide="chevron-right" class="size-[24px]"></i></button>
    <div class="absolute bottom-5 left-1/2 -translate-x-1/2 text-white/70 text-sm tabular-nums" x-text="(lightbox + 1) + ' / ' + media.length"></div>
  </div>
</div>

<div class="container-wide pb-20">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2 space-y-10">
      <!-- INFO STRIP -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php foreach ([
          ['users', t('apt.info.guests'), $a['guests']],
          ['bed-double', t('apt.info.bedrooms'), $a['bedrooms']],
          ['bed', t('apt.info.beds'), $a['beds']],
          ['bath', t('apt.info.bathrooms'), $a['bathrooms']],
        ] as $s): ?>
          <div class="card p-4">
            <div class="text-ink-500 text-xs flex items-center gap-1.5 uppercase tracking-wider"><i data-lucide="<?= $s[0] ?>" class="size-[14px]"></i> <?= e($s[1]) ?></div>
            <div class="font-display font-bold text-2xl mt-1"><?= (int)$s[2] ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div>
        <h2 class="font-serif text-3xl font-semibold tracking-tight mb-4"><?= e(t('apt.about')) ?></h2>
        <p class="text-ink-700 dark:text-ink-300 whitespace-pre-line leading-relaxed text-pretty"><?= e($a['description']) ?></p>
      </div>

      <?php if ($amenities): ?>
        <div>
          <h2 class="font-serif text-3xl font-semibold tracking-tight mb-5"><?= e(t('apt.amenities')) ?></h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($amenities as $s):
              $key = mb_strtolower($s);
              $icon = $AMENITY_ICON[$key] ?? 'check-circle-2';
            ?>
              <div class="flex items-center gap-3 p-3 rounded-xl bg-ink-50/60 dark:bg-ink-900/40 border border-ink-100/60 dark:border-ink-800/60">
                <span class="h-9 w-9 rounded-lg bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800/80 flex items-center justify-center text-brand-500"><i data-lucide="<?= e($icon) ?>" class="size-[16px]"></i></span>
                <span class="text-sm font-medium"><?= e(tAmenity($s)) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div id="calendar" class="scroll-mt-24">
        <h2 class="font-serif text-3xl font-semibold tracking-tight mb-2"><?= e(t('apt.availability')) ?></h2>
        <p class="text-ink-500 mb-5"><?= e(t('apt.detail.cal_help')) ?></p>
        <div class="card p-3 sm:p-6">
          <?php require __DIR__ . '/partials/calendar-public.php'; ?>
        </div>
      </div>

      <?php
        $globalNoSmoking = setting('rules_no_smoking_global', '1') === '1';
        $hasRules = !empty(trim($a['rules'] ?? '')) || $globalNoSmoking;
      ?>
      <?php if ($hasRules): ?>
        <div>
          <h2 class="font-serif text-3xl font-semibold tracking-tight mb-4"><?= e(t('apt.rules')) ?></h2>
          <div class="card p-6 space-y-3">
            <?php if ($globalNoSmoking): ?>
              <div class="flex items-start gap-3 p-3 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30">
                <div class="h-9 w-9 rounded-xl bg-red-100 dark:bg-red-500/20 text-red-600 flex items-center justify-center shrink-0 text-lg">🚭</div>
                <div>
                  <div class="font-semibold text-red-900 dark:text-red-200">Solo non fumatori all'interno</div>
                  <div class="text-sm text-red-700 dark:text-red-300/80 mt-0.5">È possibile fumare esclusivamente sul balcone o in terrazzo.</div>
                </div>
              </div>
            <?php endif; ?>
            <?php if (!empty(trim($a['rules'] ?? ''))): ?>
              <p class="text-ink-700 dark:text-ink-300 whitespace-pre-line leading-relaxed"><?= e($a['rules']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($reviews): ?>
        <div>
          <div class="flex items-end justify-between flex-wrap gap-2 mb-5">
            <h2 class="font-serif text-3xl font-semibold tracking-tight flex items-center gap-3">
              <i data-lucide="star" class="size-[24px] fill-amber-400 text-amber-400"></i>
              <?= number_format($rating, 1) ?> · <?= e(t('apt.detail.reviews_count', ['n' => count($reviews)])) ?>
            </h2>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($reviews as $r):
              $parts = explode(' ', trim($r['author_name']));
              $initials = mb_strtoupper(mb_substr($parts[0] ?? '·', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
            ?>
              <div class="card p-5">
                <div class="flex items-center gap-3">
                  <span class="h-10 w-10 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center font-semibold text-sm"><?= e($initials) ?></span>
                  <div>
                    <div class="font-medium"><?= e($r['author_name']) ?></div>
                    <div class="text-yellow-500 text-xs flex gap-0.5"><?php for ($i = 0; $i < (int)$r['rating']; $i++): ?><i data-lucide="star" class="size-[12px] fill-amber-400 text-amber-400"></i><?php endfor; ?></div>
                  </div>
                </div>
                <?php if ($r['title']): ?><div class="font-medium mt-3"><?= e($r['title']) ?></div><?php endif; ?>
                <p class="text-sm text-ink-600 dark:text-ink-300 mt-1.5 leading-relaxed"><?= e($r['body']) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- BOOKING CARD -->
    <aside id="booking-form" class="lg:sticky lg:top-24 self-start scroll-mt-24" x-data="bookingForm()" x-init="init()">
      <div class="card-elev p-6 shadow-card">
        <div class="flex items-baseline justify-between gap-2 mb-1">
          <div>
            <?php if (isWeeklyOnly()): ?>
              <div class="flex items-baseline gap-2 flex-wrap">
                <span class="font-display text-3xl font-bold tabular-nums" x-text="fmt(currentPackagePrice())"></span>
                <span class="text-sm text-ink-500" x-text="'/ ' + currentPackageLabel()"></span>
              </div>
              <template x-if="currentSavings() > 0">
                <div class="text-xs text-emerald-600 font-semibold mt-0.5">
                  Risparmi <span x-text="fmt(currentSavings())" class="tabular-nums"></span> rispetto al prezzo settimanale ×<span x-text="parseInt(weeks) === 4 ? '4 settimane' : weeks"></span>
                </div>
              </template>
            <?php else: ?>
              <span class="font-display text-3xl font-bold"><?= fmtMoney((float)$a['base_price']) ?></span>
              <span class="text-sm text-ink-500"><?= e(t('common.per_night')) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($rating): ?><div class="text-sm flex items-center gap-1"><i data-lucide="star" class="size-[14px] fill-amber-400 text-amber-400"></i> <strong><?= number_format($rating, 1) ?></strong></div><?php endif; ?>
        </div>
        <div class="text-xs text-ink-500 mb-5">Pulizie <?= fmtMoney((float)$a['cleaning_fee']) ?> · Caparra <?= (int)setting('security_deposit_pct', '20') ?>% per confermare</div>

        <template x-if="done">
          <div class="text-center py-6 animate-fade-in">
            <div class="h-16 w-16 mx-auto rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3"><i data-lucide="check" class="size-[32px]"></i></div>
            <div class="font-display text-xl font-bold"><?= e(t('apt.req_sent')) ?></div>
            <p class="text-sm text-ink-500 mt-1"><?= e(t('apt.booking_code')) ?></p>
            <p class="font-mono text-base mt-1" x-text="done"></p>
            <p class="text-xs text-ink-500 mt-3"><?= e(t('apt.contact_soon')) ?></p>
          </div>
        </template>

        <form x-show="!done" @submit.prevent="submit" class="space-y-2.5">
          <?php if (isWeeklyOnly()): ?>
            <div class="rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 p-3 flex items-start gap-2 text-xs text-sky-900 dark:text-sky-200">
              <i data-lucide="info" class="size-[14px] text-sky-600 shrink-0 mt-0.5"></i>
              <div><b>Come funziona:</b> scegli quanto vuoi restare (1, 2, 3 sett. o 1 mese) e poi il giorno di arrivo che preferisci. La data di partenza la calcoliamo noi.</div>
            </div>
            <label class="block px-3.5 py-2.5 rounded-2xl border border-ink-100 dark:border-ink-700/60 bg-white dark:bg-ink-900/40 shadow-sm cursor-pointer hover:bg-ink-50/40 dark:hover:bg-ink-900/60 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/15 transition-all">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-400">1. Durata soggiorno</span>
              <select class="w-full bg-transparent outline-none text-[15px] font-medium text-ink-800 dark:text-ink-100 mt-0.5" x-model.number="weeks" @change="onWeeksChange();">
                <option value="1">1 settimana (7 notti)</option>
                <option value="2">2 settimane (14 notti)</option>
                <option value="3">3 settimane (21 notti)</option>
                <option value="4">1 mese (30 notti)</option>
              </select>
            </label>
            <label class="block px-3.5 py-2.5 rounded-2xl border border-ink-100 dark:border-ink-700/60 bg-white dark:bg-ink-900/40 shadow-sm cursor-pointer hover:bg-ink-50/40 dark:hover:bg-ink-900/60 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/15 transition-all">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-400">2. Giorno di arrivo (qualsiasi giorno)</span>
              <input type="date" required class="w-full bg-transparent outline-none text-[15px] font-medium text-ink-800 dark:text-ink-100 mt-0.5 booking-date" x-model="from" @change="updateCheckout(); quote();">
            </label>
            <div class="text-xs px-1 flex items-center gap-1.5" x-show="from">
              <i data-lucide="calendar-check" class="size-[12px] text-emerald-600"></i>
              <span class="text-ink-500">Partirai il <span class="font-semibold text-ink-700 dark:text-ink-200" x-text="to ? new Date(to).toLocaleDateString('it-IT') : '—'"></span></span>
            </div>
          <?php else: ?>
          <div class="grid grid-cols-2 gap-0 rounded-2xl border border-ink-100 dark:border-ink-700/60 bg-white dark:bg-ink-900/40 shadow-sm overflow-hidden divide-x divide-ink-100 dark:divide-ink-700/60 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/15 transition-all">
            <label class="block px-3.5 py-2.5 cursor-pointer hover:bg-ink-50/60 dark:hover:bg-ink-900/60 transition-colors">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-400"><?= e(t('apt.checkin')) ?></span>
              <input type="date" required class="w-full bg-transparent outline-none text-[15px] font-medium text-ink-800 dark:text-ink-100 mt-0.5 booking-date" x-model="from" @change="quote()">
            </label>
            <label class="block px-3.5 py-2.5 cursor-pointer hover:bg-ink-50/60 dark:hover:bg-ink-900/60 transition-colors">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-400"><?= e(t('apt.checkout')) ?></span>
              <input type="date" required class="w-full bg-transparent outline-none text-[15px] font-medium text-ink-800 dark:text-ink-100 mt-0.5 booking-date" x-model="to" @change="quote()">
            </label>
          </div>
          <?php endif; ?>
          <label class="block px-3.5 py-2.5 rounded-2xl border border-ink-100 dark:border-ink-700/60 bg-white dark:bg-ink-900/40 shadow-sm cursor-pointer hover:bg-ink-50/40 dark:hover:bg-ink-900/60 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/15 transition-all">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-400"><?= e(t('apt.guests_max', ['n' => (int)$a['guests']])) ?></span>
            <input type="number" min="1" max="<?= (int)$a['guests'] ?>" class="w-full bg-transparent outline-none text-[15px] font-medium text-ink-800 dark:text-ink-100 mt-0.5" x-model.number="guests" @input="quote()">
          </label>
          <label class="block px-3.5 py-2.5 rounded-2xl border border-ink-100 dark:border-ink-700/60 bg-white dark:bg-ink-900/40 shadow-sm cursor-pointer hover:bg-ink-50/40 dark:hover:bg-ink-900/60 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/15 transition-all">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-400"><?= e(t('apt.coupon_optional')) ?></span>
            <input class="w-full bg-transparent outline-none text-[15px] font-medium text-ink-800 dark:text-ink-100 placeholder:text-ink-300 placeholder:font-normal mt-0.5" placeholder="<?= e(t('apt.coupon_ph')) ?>" x-model="coupon" @input.debounce.500="quote()">
          </label>

          <template x-if="q && q.nights > 0 && q.savings > 0">
            <div class="rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-500/15 dark:to-teal-500/15 border border-emerald-200 dark:border-emerald-500/30 p-3.5 animate-slide-up">
              <div class="flex items-center gap-2.5">
                <div class="h-9 w-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0"><i data-lucide="piggy-bank" class="size-[18px]"></i></div>
                <div class="text-sm">
                  <div class="font-semibold text-emerald-700 dark:text-emerald-300">Stai risparmiando <span x-text="fmt(q.savings)" class="tabular-nums"></span></div>
                  <div class="text-xs text-emerald-600/80 dark:text-emerald-400/80">avendo prenotato <span x-text="q.packageLabel"></span> invece di pagare a settimana</div>
                </div>
              </div>
            </div>
          </template>

          <template x-if="q && q.nights > 0">
            <div class="rounded-xl bg-ink-50 dark:bg-ink-900/40 p-4 text-sm space-y-2 animate-slide-up">
              <div class="flex justify-between">
                <span class="text-ink-500" x-text="q.packageLabel || (q.nights + ' <?= e(t('apt.summary.nights')) ?>')"></span>
                <span class="font-medium tabular-nums" x-text="fmt(q.subtotal - q.cleaningFee + (q.discount && !q.savings ? q.discount : 0))"></span>
              </div>
              <template x-if="q.savings > 0">
                <div class="flex justify-between text-emerald-600 text-xs"><span>↳ Risparmio</span><span class="tabular-nums" x-text="'-' + fmt(q.savings)"></span></div>
              </template>
              <div class="flex justify-between"><span class="text-ink-500"><?= e(t('form.cleaning')) ?></span><span class="tabular-nums" x-text="fmt(q.cleaningFee)"></span></div>
              <template x-if="q.cityTax > 0">
                <div class="flex justify-between"><span class="text-ink-500"><?= e(t('form.city_tax')) ?></span><span class="tabular-nums" x-text="fmt(q.cityTax)"></span></div>
              </template>
              <div class="flex justify-between font-display font-bold text-base pt-2 mt-1 border-t border-ink-200 dark:border-ink-700/80"><span><?= e(t('form.total')) ?></span><span class="tabular-nums" x-text="fmt(q.total)"></span></div>
              <template x-if="q.securityDeposit > 0">
                <div class="mt-2 pt-2 border-t border-dashed border-ink-200 dark:border-ink-700/60">
                  <div class="flex justify-between items-start gap-2">
                    <div class="flex items-start gap-1.5">
                      <i data-lucide="lock" class="size-[14px] text-brand-600 mt-0.5 shrink-0"></i>
                      <div>
                        <div class="text-ink-700 dark:text-ink-200 text-xs font-medium">Caparra per confermare la prenotazione</div>
                        <div class="text-[10px] text-ink-500">Versa solo questa cifra ora per bloccare le date · il saldo lo paghi all'arrivo</div>
                      </div>
                    </div>
                    <span class="tabular-nums text-sm font-bold text-brand-700 dark:text-brand-300" x-text="fmt(q.securityDeposit)"></span>
                  </div>
                </div>
              </template>
            </div>
          </template>

          <div class="space-y-2 pt-2">
            <input required placeholder="<?= e(t('apt.fullname')) ?>" class="input" x-model="name">
            <input type="email" required placeholder="<?= e(t('apt.email')) ?>" class="input" x-model="email">

            <!-- Phone with country prefix -->
            <div class="relative flex w-full max-w-full" @click.outside="dialOpen=false">
              <button type="button" @click="dialOpen=!dialOpen; if(dialOpen){$nextTick(()=>$refs.dialSearch.focus())}"
                class="rounded-xl rounded-r-none border border-r-0 border-ink-200 dark:border-ink-700/80 bg-white dark:bg-ink-900/80 px-2.5 py-2.5 flex items-center gap-1 cursor-pointer shrink-0 w-[96px] hover:bg-ink-50 dark:hover:bg-ink-800/80 transition">
                <span class="text-base leading-none" x-text="flagOf(dial)"></span>
                <span class="text-sm font-medium tabular-nums" x-text="'+' + dialCodeOf(dial)"></span>
                <i data-lucide="chevron-down" class="size-[12px] text-ink-400 shrink-0 transition" :class="dialOpen && 'rotate-180'"></i>
              </button>
              <div class="flex-1 min-w-0">
                <input required placeholder="<?= e(t('apt.phone')) ?>" type="tel"
                       class="w-full rounded-xl rounded-l-none border border-ink-200 dark:border-ink-700/80 bg-white dark:bg-ink-900/80 px-3.5 py-2.5 text-sm placeholder:text-ink-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 transition-all"
                       x-model="phone" inputmode="tel">
              </div>

              <div x-show="dialOpen" x-cloak x-transition.opacity.duration.150ms
                   class="absolute z-50 top-full mt-1 left-0 right-0 sm:w-[280px] sm:right-auto bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 rounded-2xl shadow-pop overflow-hidden"
                   style="display:none">
                <div class="p-2 border-b border-ink-100 dark:border-ink-800/80">
                  <div class="relative">
                    <i data-lucide="search" class="size-[14px] absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400"></i>
                    <input x-ref="dialSearch" x-model="dialSearch" type="text" placeholder="<?= e(t('apt.country_search')) ?>"
                           class="w-full pl-8 pr-3 py-2 text-sm bg-ink-50 dark:bg-ink-800 rounded-xl outline-none focus:ring-2 focus:ring-brand-500/30">
                  </div>
                </div>
                <ul class="max-h-[260px] overflow-y-auto py-1">
                  <template x-for="c in filteredDials()" :key="c.code">
                    <li>
                      <button type="button" @click="dial=c.code; dialOpen=false; dialSearch=''"
                              class="w-full px-3 py-2 flex items-center gap-2.5 text-sm text-left hover:bg-brand-50 dark:hover:bg-brand-500/10 transition"
                              :class="dial===c.code && 'bg-brand-50 dark:bg-brand-500/15'">
                        <span class="text-base leading-none" x-text="flagOf(c.code)"></span>
                        <span class="flex-1 truncate" x-text="c.name"></span>
                        <span class="text-ink-500 tabular-nums text-xs" x-text="'+' + c.dial"></span>
                      </button>
                    </li>
                  </template>
                </ul>
              </div>
            </div>

            <!-- Country picker custom -->
            <div class="relative" @click.outside="countryOpen=false">
              <button type="button" @click="countryOpen=!countryOpen; if(countryOpen){$nextTick(()=>$refs.countrySearch.focus())}"
                class="input w-full text-left flex items-center gap-2 cursor-pointer"
                :class="!country && 'text-ink-400'">
                <template x-if="country">
                  <span class="text-lg leading-none" x-text="flagOf(country)"></span>
                </template>
                <template x-if="!country">
                  <i data-lucide="globe" class="size-[16px] text-ink-400 shrink-0"></i>
                </template>
                <span class="flex-1 truncate" x-text="country ? countryNameOf(country) : '<?= e(t('apt.country_select')) ?>'"></span>
                <i data-lucide="chevron-down" class="size-[14px] text-ink-400 shrink-0 transition" :class="countryOpen && 'rotate-180'"></i>
              </button>

              <div x-show="countryOpen" x-cloak x-transition.opacity.duration.150ms
                   class="absolute z-50 mt-1 w-full bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 rounded-2xl shadow-pop overflow-hidden"
                   style="display:none">
                <div class="p-2 border-b border-ink-100 dark:border-ink-800/80">
                  <div class="relative">
                    <i data-lucide="search" class="size-[14px] absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400"></i>
                    <input x-ref="countrySearch" x-model="countrySearch" type="text" placeholder="<?= e(t('apt.country_search')) ?>"
                           class="w-full pl-8 pr-3 py-2 text-sm bg-ink-50 dark:bg-ink-800 rounded-xl outline-none focus:ring-2 focus:ring-brand-500/30">
                  </div>
                </div>
                <ul class="max-h-[280px] overflow-y-auto py-1">
                  <template x-for="c in filteredCountries()" :key="c.code">
                    <li>
                      <button type="button" @click="country=c.code; countryOpen=false; countrySearch=''"
                              class="w-full px-3 py-2 flex items-center gap-2.5 text-sm text-left hover:bg-brand-50 dark:hover:bg-brand-500/10 transition"
                              :class="country===c.code && 'bg-brand-50 dark:bg-brand-500/15 text-brand-700 dark:text-brand-300 font-medium'">
                        <span class="text-base leading-none" x-text="flagOf(c.code)"></span>
                        <span class="flex-1 truncate" x-text="c.name"></span>
                        <template x-if="country===c.code"><i data-lucide="check" class="size-[14px] text-brand-600"></i></template>
                      </button>
                    </li>
                  </template>
                  <li x-show="filteredCountries().length === 0" class="px-3 py-4 text-sm text-ink-500 text-center"><?= e(t('apt.country_empty')) ?></li>
                </ul>
              </div>
            </div>
          </div>

          <div x-show="err" x-text="err" class="text-sm text-red-600 p-2 rounded-lg bg-red-50 dark:bg-red-500/10"></div>

          <button :disabled="busy" class="btn-primary w-full h-12 text-base">
            <span x-show="!busy"><?= e(t('apt.request_book')) ?></span>
            <span x-show="busy" class="flex items-center gap-2"><i data-lucide="loader-2" class="size-[18px] animate-spin"></i> <?= e(t('form.sending')) ?></span>
          </button>
          <p class="text-[11px] text-ink-500 text-center"><?= e(t('apt.no_charge')) ?></p>
        </form>
      </div>

      <div class="card p-5 mt-4">
        <div class="flex items-start gap-3">
          <span class="h-10 w-10 rounded-xl bg-brand-50 dark:bg-brand-500/15 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="message-circle" class="size-[18px]"></i></span>
          <div>
            <div class="font-medium"><?= e(t('apt.help.title')) ?></div>
            <div class="text-sm text-ink-500"><?= e(t('apt.help.sub')) ?></div>
            <a href="/contatti.php?lang=<?= e(currentLang()) ?>" class="text-sm text-brand-600 font-medium mt-1 inline-block"><?= e(t('apt.help.cta')) ?></a>
          </div>
        </div>
      </div>
    </aside>
  </div>
</div>

<script>
function bookingForm() {
  return {
    aptId: <?= json_encode($a['id']) ?>,
    weeklyOnly: <?= isWeeklyOnly() ? 'true' : 'false' ?>,
    pkgPrices: {
      1: <?= (float)($a['weekly_price'] ?: $a['base_price'] * 7) ?>,
      2: <?= (float)($a['biweekly_price'] ?: ($a['weekly_price'] ?: $a['base_price'] * 7) * 2) ?>,
      3: <?= (float)($a['triweekly_price'] ?: ($a['weekly_price'] ?: $a['base_price'] * 7) * 3) ?>,
      4: <?= (float)($a['monthly_price'] ?: ($a['weekly_price'] ?: $a['base_price'] * 7) * (30/7)) ?>,
    },
    pkgLabels: { 1: 'settimana', 2: '2 settimane', 3: '3 settimane', 4: '1 mese' },
    weeks: 1,
    from: '', to: '', guests: 2, coupon: '', name: '', email: '', phone: '', country: '', dial: 'IT',
    countries: <?= json_encode(countryList(currentLang())) ?>,
    phoneCountries: <?= json_encode(phoneCountryList(currentLang())) ?>,
    countryOpen: false, countrySearch: '',
    dialOpen: false, dialSearch: '',
    q: null, busy: false, done: null, err: '',
    fmt(n) { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n || 0); },
    currentPackagePrice() {
      const w = parseInt(this.weeks) || 1;
      return this.pkgPrices[w] || this.pkgPrices[1];
    },
    currentPackageLabel() {
      const w = parseInt(this.weeks) || 1;
      return this.pkgLabels[w] || 'settimana';
    },
    currentSavings() {
      const w = parseInt(this.weeks) || 1;
      if (w === 1) return 0;
      const weekly = this.pkgPrices[1] || 0;
      const base = w === 4 ? weekly * (30/7) : weekly * w;
      const pkg = this.pkgPrices[w] || 0;
      return Math.max(0, Math.round((base - pkg) * 100) / 100);
    },
    updateCheckout() {
      if (!this.weeklyOnly || !this.from) { return; }
      const w = parseInt(this.weeks) || 1;
      const nights = w === 4 ? 30 : w * 7;
      const d = new Date(this.from + 'T00:00:00');
      if (isNaN(d.getTime())) return;
      d.setDate(d.getDate() + nights);
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      this.to = yyyy + '-' + mm + '-' + dd;
    },
    onWeeksChange() {
      this.updateCheckout();
      this.quote();
      // Sincronizza il calendario visivo
      window.dispatchEvent(new CustomEvent('cv-weeks-change', { detail: { weeks: this.weeks } }));
    },
    flagOf(code) {
      if (!code || code.length !== 2) return '';
      return code.toUpperCase().replace(/./g, c => String.fromCodePoint(127397 + c.charCodeAt(0)));
    },
    countryNameOf(code) {
      const f = this.countries.find(c => c.code === code);
      return f ? f.name : code;
    },
    dialCodeOf(code) {
      const f = this.phoneCountries.find(c => c.code === code);
      return f ? f.dial : '';
    },
    filteredCountries() {
      const q = (this.countrySearch || '').trim().toLowerCase();
      if (!q) return this.countries;
      return this.countries.filter(c => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q));
    },
    filteredDials() {
      const q = (this.dialSearch || '').trim().toLowerCase();
      if (!q) return this.phoneCountries;
      return this.phoneCountries.filter(c => c.name.toLowerCase().includes(q) || c.dial.includes(q) || c.code.toLowerCase().includes(q));
    },
    init() {
      try {
        const saved = JSON.parse(sessionStorage.getItem('cv_book_' + this.aptId) || '{}');
        if (saved.from) this.from = saved.from;
        if (saved.to) this.to = saved.to;
        if (saved.weeks && this.weeklyOnly) this.weeks = parseInt(saved.weeks) || 1;
      } catch (e) {}
      // In modalità weekly-only: se ho una "from" ma non "to" (o "to" inconsistente con weeks),
      // ricalcolo "to" subito così il quote parte coi numeri giusti.
      if (this.weeklyOnly && this.from) {
        this.updateCheckout();
      }
      if (this.from && this.to) this.quote();
      this.$watch('from', () => this.persist());
      this.$watch('to', () => this.persist());
      this.$watch('weeks', () => this.persist());
      window.addEventListener('cv-cal-pick', (e) => {
        this.from = e.detail.from || '';
        this.to = e.detail.to || '';
        if (this.weeklyOnly && this.from) this.updateCheckout();
        if (this.from && this.to) this.quote(); else this.q = null;
      });
      window.addEventListener('cv-cal-weeks', (e) => {
        this.weeks = parseInt(e.detail.weeks) || 1;
        if (this.from) { this.updateCheckout(); this.quote(); }
      });
    },
    persist() {
      try { sessionStorage.setItem('cv_book_' + this.aptId, JSON.stringify({ from: this.from, to: this.to, weeks: this.weeks })); } catch (e) {}
    },
    async quote() {
      if (!this.from || !this.to) return;
      try {
        const r = await fetch('/api/quote.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ apartment_id: this.aptId, from: this.from, to: this.to, guests: this.guests, coupon: this.coupon }) });
        this.q = await r.json();
      } catch (e) {}
    },
    async submit() {
      if (!this.country) { this.err = '<?= e(t('apt.country_select')) ?>'; return; }
      this.busy = true; this.err = '';
      const dialCode = this.dialCodeOf(this.dial) || '39';
      const phoneFull = '+' + dialCode + ' ' + (this.phone || '').replace(/^\+?\d{1,4}\s*/, '');
      try {
        const r = await fetch('/api/booking.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ apartment_id: this.aptId, from: this.from, to: this.to, guests: this.guests, coupon: this.coupon, name: this.name, email: this.email, phone: phoneFull, country: this.country }) });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error || 'Errore');
        this.done = d.code;
        try { sessionStorage.removeItem('cv_book_' + this.aptId); } catch (e) {}
      } catch (e) { this.err = e.message; } finally { this.busy = false; }
    }
  };
}
</script>

<!-- STICKY MOBILE CTA -->
<div class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 dark:bg-ink-950/95 backdrop-blur-xl border-t border-ink-100 dark:border-ink-800/80 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_-12px_rgba(0,0,0,.15)]">
  <div class="flex items-center justify-between gap-3">
    <div class="min-w-0">
      <div class="flex items-baseline gap-1">
        <span class="font-display text-xl font-bold"><?= fmtMoney((float)$a['base_price']) ?></span>
        <span class="text-xs text-ink-500"><?= e(t('common.per_night')) ?></span>
      </div>
      <?php if ($rating): ?>
        <div class="text-xs text-ink-500 flex items-center gap-1 mt-0.5"><i data-lucide="star" class="size-[12px] fill-amber-400 text-amber-400"></i> <strong class="text-ink-900 dark:text-white"><?= number_format($rating, 1) ?></strong> · <?= e(t('apt.detail.reviews_count', ['n' => count($reviews)])) ?></div>
      <?php else: ?>
        <div class="text-xs text-ink-500 mt-0.5"><?= e(t('apt.detail.quick_confirm')) ?></div>
      <?php endif; ?>
    </div>
    <a href="#booking-form" class="btn-primary h-12 px-5 shrink-0"><?= e(t('cta.book_now')) ?> <i data-lucide="arrow-right" class="size-[14px]"></i></a>
  </div>
</div>
<div class="lg:hidden h-20"></div>

<?php require __DIR__ . '/partials/site-footer.php';
