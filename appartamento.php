<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

$slug = $_GET['slug'] ?? '';
$a = row('SELECT * FROM apartments WHERE slug = ? AND active = 1', [$slug]);
if (!$a) { http_response_code(404); $title='Non trovato'; require __DIR__.'/partials/head.php'; require __DIR__.'/partials/site-header.php'; echo '<div class="max-w-xl mx-auto card p-10 mt-20 text-center"><h1 class="text-2xl font-bold">Appartamento non trovato</h1></div>'; require __DIR__.'/partials/site-footer.php'; exit; }

$photos = rows('SELECT * FROM photos WHERE apartment_id = ? ORDER BY position ASC', [$a['id']]);
if (!$photos && $a['cover_image']) $photos = [['url' => $a['cover_image'], 'alt' => $a['name']]];
$reviews = rows('SELECT * FROM reviews WHERE apartment_id = ? AND approved = 1 ORDER BY created_at DESC', [$a['id']]);
$bookings = rows('SELECT check_in, check_out, status FROM bookings WHERE apartment_id = ? AND status != "cancelled"', [$a['id']]);
$blocks = rows('SELECT start_date, end_date FROM date_blocks WHERE apartment_id = ?', [$a['id']]);
$amenities = parseAmenities($a['amenities']);
$rating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;

$title = $a['name'];
$metaDesc = mb_substr(strip_tags($a['description']), 0, 160);
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/site-header.php';
?>
<div class="max-w-7xl mx-auto px-5 py-8" x-data="{ lightbox: null, photos: <?= e(json_encode(array_column($photos, 'url'))) ?> }">
  <div class="flex items-end justify-between flex-wrap gap-4">
    <div>
      <h1 class="font-display text-3xl md:text-4xl font-bold"><?= e($a['name']) ?></h1>
      <div class="flex items-center gap-3 text-sm text-ink-500 mt-2">
        <span class="flex items-center gap-1"><i data-lucide="map-pin" class="size-[14px]"></i> <?= e($a['address'] ?: $a['city']) ?></span>
        <?php if ($rating): ?><span class="flex items-center gap-1"><i data-lucide="star" class="size-[14px] fill-yellow-400 text-yellow-400"></i> <?= number_format($rating, 1) ?> · <?= count($reviews) ?> recensioni</span><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-4 grid-rows-2 gap-2 mt-6 rounded-2xl overflow-hidden h-[420px]">
    <?php $vis = array_slice($photos, 0, 5); $main = $vis[0] ?? null; $thumbs = array_slice($vis, 1); ?>
    <?php if ($main): ?><button @click="lightbox=0" class="col-span-2 row-span-2"><img src="<?= e($main['url']) ?>" class="h-full w-full object-cover hover:scale-[1.02] transition"></button><?php endif; ?>
    <?php foreach ($thumbs as $i => $p): ?>
      <button @click="lightbox=<?= $i + 1 ?>" class="relative">
        <img src="<?= e($p['url']) ?>" class="h-full w-full object-cover hover:scale-[1.02] transition">
        <?php if ($i === count($thumbs) - 1 && count($photos) > 5): ?><span class="absolute inset-0 bg-black/40 text-white flex items-center justify-center font-semibold">+<?= count($photos) - 5 ?> foto</span><?php endif; ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div x-show="lightbox !== null" x-cloak @click="lightbox=null" class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center" style="display:none">
    <button class="absolute top-5 right-5 text-white" @click.stop="lightbox=null"><i data-lucide="x" class="size-[28px]"></i></button>
    <button class="absolute left-4 text-white" @click.stop="lightbox = (lightbox - 1 + photos.length) % photos.length"><i data-lucide="chevron-left" class="size-[36px]"></i></button>
    <img :src="photos[lightbox]" class="max-h-[88vh] max-w-[88vw] object-contain">
    <button class="absolute right-4 text-white" @click.stop="lightbox = (lightbox + 1) % photos.length"><i data-lucide="chevron-right" class="size-[36px]"></i></button>
  </div>

  <div class="grid lg:grid-cols-3 gap-8 mt-10">
    <div class="lg:col-span-2 space-y-8">
      <div class="card p-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
          <?php foreach ([['users','Ospiti',$a['guests']],['bed-double','Camere',$a['bedrooms']],['bed-double','Letti',$a['beds']],['bath','Bagni',$a['bathrooms']]] as $s): ?>
            <div class="rounded-xl bg-ink-50 dark:bg-ink-800/50 p-3">
              <div class="text-ink-500 text-xs flex items-center gap-1"><i data-lucide="<?= $s[0] ?>" class="size-[14px]"></i> <?= e($s[1]) ?></div>
              <div class="font-semibold text-lg mt-0.5"><?= (int)$s[2] ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <h2 class="font-display text-xl font-bold mb-2">Descrizione</h2>
        <p class="text-ink-600 dark:text-ink-300 whitespace-pre-line"><?= e($a['description']) ?></p>
      </div>

      <?php if ($amenities): ?>
        <div class="card p-6">
          <h2 class="font-display text-xl font-bold mb-3">Servizi</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <?php foreach ($amenities as $s): ?>
              <div class="flex items-center gap-2 text-sm"><i data-lucide="check" class="size-[16px] text-brand-500"></i> <?= e($s) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="card p-6">
        <h2 class="font-display text-xl font-bold mb-3">Disponibilità</h2>
        <?php require __DIR__ . '/partials/calendar-public.php'; ?>
      </div>

      <?php if ($a['rules']): ?>
        <div class="card p-6">
          <h2 class="font-display text-xl font-bold mb-3">Regole della casa</h2>
          <p class="text-ink-600 dark:text-ink-300 whitespace-pre-line"><?= e($a['rules']) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($reviews): ?>
        <div class="card p-6">
          <h2 class="font-display text-xl font-bold mb-3">Recensioni</h2>
          <div class="space-y-4">
            <?php foreach ($reviews as $r): ?>
              <div class="border-b border-ink-100 dark:border-ink-800 pb-4 last:border-0">
                <div class="flex items-center gap-2">
                  <span class="font-semibold"><?= e($r['author_name']) ?></span>
                  <span class="text-yellow-500 text-sm"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
                </div>
                <?php if ($r['title']): ?><div class="text-sm font-medium mt-1"><?= e($r['title']) ?></div><?php endif; ?>
                <p class="text-sm text-ink-600 dark:text-ink-300 mt-1"><?= e($r['body']) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="lg:sticky lg:top-24 self-start">
      <div class="card p-5" x-data="bookingForm()">
        <div class="flex items-baseline gap-1 mb-1">
          <span class="font-display text-2xl font-bold"><?= fmtMoney((float)$a['base_price']) ?></span>
          <span class="text-sm text-ink-500">/notte</span>
        </div>
        <div class="text-xs text-ink-500 mb-4">Pulizie incluse: <?= fmtMoney((float)$a['cleaning_fee']) ?> · Tassa soggiorno <?= fmtMoney((float)$a['city_tax']) ?>/p/notte</div>

        <template x-if="done">
          <div class="text-center py-4">
            <div class="text-4xl mb-2">🎉</div>
            <div class="font-display text-xl font-bold">Richiesta inviata</div>
            <p class="text-sm text-ink-500 mt-1">Codice: <strong x-text="done"></strong></p>
            <p class="text-sm text-ink-500 mt-2">Ti contatteremo a breve.</p>
          </div>
        </template>

        <form x-show="!done" @submit.prevent="submit" class="space-y-3">
          <div class="grid grid-cols-2 gap-2">
            <label class="block"><span class="text-xs text-ink-500">Check-in</span><input type="date" required class="input mt-1" x-model="from" @change="quote()"></label>
            <label class="block"><span class="text-xs text-ink-500">Check-out</span><input type="date" required class="input mt-1" x-model="to" @change="quote()"></label>
          </div>
          <label class="block"><span class="text-xs text-ink-500">Ospiti (max <?= (int)$a['guests'] ?>)</span><input type="number" min="1" max="<?= (int)$a['guests'] ?>" class="input mt-1" x-model.number="guests" @input="quote()"></label>
          <label class="block"><span class="text-xs text-ink-500">Codice sconto</span><input class="input mt-1" placeholder="opzionale" x-model="coupon" @input.debounce.500="quote()"></label>

          <template x-if="q && q.nights > 0">
            <div class="rounded-xl border border-ink-100 dark:border-ink-800 p-3 text-sm space-y-1.5">
              <div class="flex justify-between"><span class="text-ink-500"><span x-text="q.nights"></span> notti</span><span x-text="fmt(q.nightlyTotal)"></span></div>
              <template x-if="q.discount > 0">
                <div class="flex justify-between text-emerald-600"><span x-text="q.discountLabel"></span><span x-text="'-' + fmt(q.discount)"></span></div>
              </template>
              <div class="flex justify-between"><span class="text-ink-500">Pulizie</span><span x-text="fmt(q.cleaningFee)"></span></div>
              <div class="flex justify-between"><span class="text-ink-500">Tassa soggiorno</span><span x-text="fmt(q.cityTax)"></span></div>
              <div class="flex justify-between font-semibold pt-2 border-t border-ink-100 dark:border-ink-800"><span>Totale</span><span x-text="fmt(q.total)"></span></div>
            </div>
          </template>

          <div class="grid grid-cols-2 gap-2">
            <input required placeholder="Nome e cognome" class="input col-span-2" x-model="name">
            <input type="email" required placeholder="Email" class="input" x-model="email">
            <input required placeholder="Telefono" class="input" x-model="phone">
          </div>

          <div x-show="err" x-text="err" class="text-sm text-red-600"></div>

          <button :disabled="busy" class="btn-primary w-full">
            <span x-show="!busy">Richiedi prenotazione</span>
            <span x-show="busy">Invio...</span>
          </button>
          <p class="text-[11px] text-ink-500 text-center">Non ti verrà addebitato nulla ora. Confermeremo a breve.</p>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function bookingForm() {
  return {
    from: '', to: '', guests: 2, coupon: '', name: '', email: '', phone: '',
    q: null, busy: false, done: null, err: '',
    fmt(n) { return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(n || 0); },
    async quote() {
      if (!this.from || !this.to) return;
      try {
        const r = await fetch('/api/quote.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ apartment_id: <?= json_encode($a['id']) ?>, from: this.from, to: this.to, guests: this.guests, coupon: this.coupon }) });
        this.q = await r.json();
      } catch (e) {}
    },
    async submit() {
      this.busy = true; this.err = '';
      try {
        const r = await fetch('/api/booking.php', { method: 'POST', headers: { 'content-type': 'application/json' },
          body: JSON.stringify({ apartment_id: <?= json_encode($a['id']) ?>, from: this.from, to: this.to, guests: this.guests, coupon: this.coupon, name: this.name, email: this.email, phone: this.phone }) });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error || 'Errore');
        this.done = d.code;
      } catch (e) { this.err = e.message; } finally { this.busy = false; }
    }
  };
}
</script>
<?php require __DIR__ . '/partials/site-footer.php';
