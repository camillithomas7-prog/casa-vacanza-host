<?php
require_once __DIR__ . '/../lib/auth.php';
$current = currentUser();
$path = $_SERVER['SCRIPT_NAME'] ?? '';
function navItem($href, $icon, $label, $current) {
    $active = strpos($current, $href) !== false || ($href === '/admin/index.php' && in_array($current, ['/admin/index.php','/admin/']));
    $cls = $active
      ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-[0_4px_12px_-4px_rgba(240,78,0,.45)]'
      : 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800/60';
    echo '<a href="' . e($href) . '" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition ' . $cls . '"><i data-lucide="' . e($icon) . '" class="size-[18px] shrink-0"></i> ' . e($label) . '</a>';
}
$unreadNotifs = (int)val('SELECT COUNT(*) FROM notifications WHERE is_read = 0');
$chatPending = 0;
try { $chatPending = (int)val("SELECT COUNT(*) FROM chat_conversations WHERE status = 'escalated'"); } catch (Throwable $e) {}
$initials = $current ? mb_strtoupper(mb_substr($current['name'] ?: $current['email'], 0, 1) . mb_substr($current['email'], 1, 1)) : 'A';

$titles = [
  '/admin/index.php' => ['Dashboard', 'Panoramica del tuo gestionale'],
  '/admin/appartamenti.php' => ['Appartamenti', 'Gestisci la tua flotta'],
  '/admin/appartamento-edit.php' => ['Appartamento', 'Modifica scheda'],
  '/admin/calendario.php' => ['Calendario', 'Disponibilità e blocchi'],
  '/admin/prenotazioni.php' => ['Prenotazioni', 'Tutte le prenotazioni'],
  '/admin/prenotazione.php' => ['Prenotazione', 'Dettaglio'],
  '/admin/prenotazione-nuova.php' => ['Nuova prenotazione', 'Crea manualmente'],
  '/admin/prezzi.php' => ['Prezzi avanzati', 'Regole stagionali'],
  '/admin/zone.php' => ['Zone & villaggi', 'Destinazioni'],
  '/admin/zone-edit.php' => ['Zona', 'Modifica scheda'],
  '/admin/spese.php' => ['Spese & bilancio', 'Costi e ricavi'],
  '/admin/template.php' => ['Template messaggi', 'WhatsApp & Email'],
  '/admin/recensioni.php' => ['Recensioni', 'Modera i feedback'],
  '/admin/coupon.php' => ['Coupon', 'Sconti'],
  '/admin/impostazioni.php' => ['Impostazioni', 'Account e backup'],
  '/admin/notifiche.php' => ['Notifiche', 'Attività recente'],
  '/admin/pulizie.php' => ['Pulizie', 'Checklist e link signora pulizie'],
  '/admin/pulizie-checklist.php' => ['Checklist pulizie', 'Catalogo voci'],
  '/admin/pulizia.php' => ['Sessione pulizia', 'Dettaglio'],
  '/admin/servizi.php' => ['Servizi extra', 'Veicoli, escursioni, transfer'],
  '/admin/servizio-edit.php' => ['Servizio', 'Modifica scheda'],
  '/admin/servizi-prenotazioni.php' => ['Prenotazioni servizi', 'Veicoli, escursioni, transfer'],
  '/admin/servizi-prenotazione.php' => ['Prenotazione servizio', 'Dettaglio'],
  '/admin/chat.php' => ['Chat clienti', 'Conversazioni dal widget del sito'],
];
$pageMeta = $titles[$path] ?? ['Admin', ''];
?>
<div class="min-h-screen flex bg-ink-50 dark:bg-ink-950 overflow-x-hidden w-full max-w-[100vw]" x-data="{ open: false }">
<aside class="fixed lg:sticky inset-y-0 left-0 z-40 w-72 max-w-[85vw] bg-white dark:bg-ink-900/95 dark:backdrop-blur-xl border-r border-ink-100 dark:border-ink-800/80 transition-transform lg:translate-x-0 flex flex-col" :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
  <div class="h-20 px-5 flex items-center justify-between border-b border-ink-100 dark:border-ink-800/80 shrink-0">
    <a href="/admin/index.php" class="flex items-center group">
      <img src="/assets/logo-256.png?v=2" alt="<?= e(cfg('site.name')) ?>" class="h-14 w-auto">
    </a>
    <button class="lg:hidden btn-ghost p-2" @click="open=false"><i data-lucide="x" class="size-[18px]"></i></button>
  </div>
  <nav class="p-3 space-y-0.5 flex-1 overflow-y-auto scrollbar-thin">
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-2">Menu</div>
    <?php navItem('/admin/index.php', 'bar-chart-3', 'Dashboard', $path); ?>
    <?php navItem('/admin/appartamenti.php', 'building-2', 'Appartamenti', $path); ?>
    <?php navItem('/admin/zone.php', 'map-pin', 'Zone & villaggi', $path); ?>
    <?php navItem('/admin/calendario.php', 'calendar', 'Calendario', $path); ?>
    <?php navItem('/admin/prenotazioni.php', 'bookmark-check', 'Prenotazioni', $path); ?>
    <?php navItem('/admin/pulizie.php', 'sparkles', 'Pulizie', $path); ?>
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-4">Servizi extra</div>
    <?php navItem('/admin/servizi.php', 'package', 'Catalogo servizi', $path); ?>
    <?php navItem('/admin/servizi-prenotazioni.php', 'clipboard-list', 'Prenotazioni servizi', $path); ?>
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-4">Strategia</div>
    <?php navItem('/admin/prezzi.php', 'wallet', 'Prezzi', $path); ?>
    <?php navItem('/admin/spese.php', 'receipt', 'Spese & bilancio', $path); ?>
    <?php navItem('/admin/coupon.php', 'tag', 'Coupon', $path); ?>
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-4">Comunicazione</div>
    <?php
      // Voce Chat con badge se ci sono escalation pendenti
      $isActive = strpos($path, '/admin/chat.php') !== false;
      $cls = $isActive
        ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-[0_4px_12px_-4px_rgba(240,78,0,.45)]'
        : 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800/60';
      echo '<a href="/admin/chat.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition ' . $cls . '"><i data-lucide="message-circle" class="size-[18px] shrink-0"></i> Chat clienti';
      if ($chatPending > 0) echo ' <span class="ml-auto h-5 min-w-5 px-1.5 rounded-full bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center">' . $chatPending . '</span>';
      echo '</a>';
    ?>
    <?php navItem('/admin/template.php', 'message-square', 'Template', $path); ?>
    <?php navItem('/admin/recensioni.php', 'star', 'Recensioni', $path); ?>
    <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 px-3 pb-1.5 pt-4">Sistema</div>
    <?php navItem('/admin/impostazioni.php', 'settings', 'Impostazioni', $path); ?>
    <?php navItem('/admin/notifiche.php', 'bell', 'Notifiche', $path); ?>
  </nav>
  <div class="p-3 border-t border-ink-100 dark:border-ink-800/80 shrink-0">
    <div class="flex items-center gap-3 p-2.5 rounded-xl">
      <span class="h-9 w-9 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-white flex items-center justify-center font-semibold text-sm shrink-0"><?= e($initials) ?></span>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold truncate"><?= e($current['name'] ?: 'Admin') ?></div>
        <div class="text-xs text-ink-500 truncate"><?= e($current['email']) ?></div>
      </div>
      <a href="/admin/logout.php" class="h-8 w-8 rounded-lg flex items-center justify-center text-ink-500 hover:bg-ink-100 dark:hover:bg-ink-800" title="Esci"><i data-lucide="log-out" class="size-[14px]"></i></a>
    </div>
  </div>
</aside>

<div x-show="open" x-cloak class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="open=false" style="display:none"></div>

<div class="flex-1 min-w-0 max-w-full overflow-x-hidden">
  <header class="sticky top-0 z-20 h-16 bg-white/85 dark:bg-ink-950/85 backdrop-blur-xl border-b border-ink-100 dark:border-ink-800/80">
    <div class="h-full px-3 sm:px-5 flex items-center gap-2 sm:gap-3">
      <button class="lg:hidden btn-ghost p-2 shrink-0" @click="open=true"><i data-lucide="menu" class="size-[20px]"></i></button>
      <div class="min-w-0 flex-1 sm:flex-none">
        <div class="text-[10px] sm:text-xs text-ink-500 truncate"><?= e($pageMeta[1]) ?></div>
        <div class="font-display font-bold text-sm sm:text-base leading-tight truncate"><?= e($pageMeta[0]) ?></div>
      </div>
      <div class="flex-1 max-w-md ml-auto relative hidden md:block" id="admin-search-wrap">
        <i data-lucide="search" class="size-[16px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
        <input id="admin-search-input" autocomplete="off" placeholder="Cerca prenotazioni, clienti, appartamenti…" class="input pl-10 pr-12 bg-ink-50/50 dark:bg-ink-900/40 border-transparent focus:bg-white dark:focus:bg-ink-900">
        <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-mono bg-white dark:bg-ink-800 border border-ink-200 dark:border-ink-700 rounded px-1.5 py-0.5 text-ink-500 pointer-events-none">⌘K</kbd>
        <div id="admin-search-results" class="hidden absolute left-0 right-0 mt-2 bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 rounded-xl shadow-xl max-h-[480px] overflow-auto z-50"></div>
      </div>
      <script>
      (function(){
        const inp = document.getElementById('admin-search-input');
        const box = document.getElementById('admin-search-results');
        const wrap = document.getElementById('admin-search-wrap');
        if (!inp || !box) return;
        let t = null, lastQ = '', sel = -1, items = [];
        const labels = { apartment: 'Appartamento', customer: 'Cliente', booking: 'Prenotazione' };
        const colors = { apartment: 'text-orange-600 bg-orange-50', customer: 'text-sky-600 bg-sky-50', booking: 'text-emerald-600 bg-emerald-50' };

        function render(results){
          items = results || [];
          if (!items.length) {
            box.innerHTML = '<div class="p-4 text-sm text-ink-400 text-center">Nessun risultato</div>';
          } else {
            box.innerHTML = items.map((r, i) => `
              <a href="${r.url}" data-i="${i}" class="result-row flex items-center gap-3 px-3 py-2.5 hover:bg-ink-50 dark:hover:bg-ink-800 border-b border-ink-100 dark:border-ink-800 last:border-b-0">
                ${r.cover ? `<img src="${r.cover}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">` : `<div class="w-10 h-10 rounded-lg ${colors[r.type] || 'bg-ink-100'} flex items-center justify-center flex-shrink-0 text-xs font-bold">${(labels[r.type]||'?')[0]}</div>`}
                <div class="min-w-0 flex-1">
                  <div class="text-sm font-medium truncate">${escapeHtml(r.label)}</div>
                  <div class="text-xs text-ink-500 truncate">${escapeHtml(r.sub || '')}</div>
                </div>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-400 flex-shrink-0">${labels[r.type] || r.type}</span>
              </a>
            `).join('');
          }
          box.classList.remove('hidden');
          sel = -1;
        }
        function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
        function doSearch(){
          const q = inp.value.trim();
          if (q.length < 2) { box.classList.add('hidden'); return; }
          if (q === lastQ) return;
          lastQ = q;
          fetch('/api/admin-search.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(d => render(d.results || []))
            .catch(() => { box.innerHTML = '<div class="p-4 text-sm text-red-500 text-center">Errore di ricerca</div>'; box.classList.remove('hidden'); });
        }
        inp.addEventListener('input', () => { clearTimeout(t); t = setTimeout(doSearch, 220); });
        inp.addEventListener('focus', () => { if (inp.value.trim().length >= 2) box.classList.remove('hidden'); });
        inp.addEventListener('keydown', e => {
          const rows = box.querySelectorAll('.result-row');
          if (e.key === 'ArrowDown' && rows.length) { e.preventDefault(); sel = Math.min(sel + 1, rows.length - 1); rows.forEach((r,i) => r.classList.toggle('bg-ink-100', i===sel)); rows[sel] && rows[sel].scrollIntoView({block:'nearest'}); }
          else if (e.key === 'ArrowUp' && rows.length) { e.preventDefault(); sel = Math.max(sel - 1, 0); rows.forEach((r,i) => r.classList.toggle('bg-ink-100', i===sel)); rows[sel] && rows[sel].scrollIntoView({block:'nearest'}); }
          else if (e.key === 'Enter' && sel >= 0 && rows[sel]) { e.preventDefault(); window.location.href = rows[sel].getAttribute('href'); }
          else if (e.key === 'Escape') { box.classList.add('hidden'); inp.blur(); }
        });
        document.addEventListener('click', e => { if (!wrap.contains(e.target)) box.classList.add('hidden'); });
        // Shortcut ⌘K / Ctrl+K
        document.addEventListener('keydown', e => {
          if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); inp.focus(); inp.select(); }
        });
      })();
      </script>
      <button onclick="toggleTheme()" class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-600 dark:text-ink-300 transition">
        <i data-lucide="moon" class="size-[18px] dark:hidden"></i>
        <i data-lucide="sun" class="size-[18px] hidden dark:inline"></i>
      </button>
      <a href="/admin/notifiche.php" class="relative h-10 w-10 shrink-0 rounded-xl flex items-center justify-center hover:bg-ink-100 dark:hover:bg-ink-800 transition">
        <i data-lucide="bell" class="size-[18px]"></i>
        <?php if ($unreadNotifs > 0): ?><span class="absolute top-1.5 right-1.5 h-4 min-w-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"><?= $unreadNotifs ?></span><?php endif; ?>
      </a>
      <a href="/" target="_blank" title="Vai al sito" class="h-10 w-10 sm:w-auto sm:px-3.5 shrink-0 rounded-xl flex items-center justify-center sm:gap-2 border border-ink-200 dark:border-ink-700/80 text-ink-700 dark:text-ink-200 hover:bg-ink-50 dark:hover:bg-ink-800 transition text-sm"><i data-lucide="external-link" class="size-[16px]"></i> <span class="hidden sm:inline">Sito</span></a>
    </div>
  </header>
  <main class="p-4 sm:p-5 lg:p-8 animate-fade-in">
  <?php $_flash = flash(); if ($_flash): ?>
    <div class="mb-5 p-3.5 rounded-xl border flex items-center gap-2.5 animate-slide-down <?= $_flash['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-300' : 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300' ?>">
      <i data-lucide="<?= $_flash['type'] === 'error' ? 'alert-circle' : 'check-circle-2' ?>" class="size-[18px]"></i>
      <span class="text-sm font-medium"><?= e($_flash['msg']) ?></span>
    </div>
  <?php endif; ?>
<script>
function toggleTheme(){
  var d = document.documentElement.classList.toggle('dark');
  try { localStorage.setItem('cv-theme', d ? 'dark' : 'light'); } catch(e){}
}
</script>
