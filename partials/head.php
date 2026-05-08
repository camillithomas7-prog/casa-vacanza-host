<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/i18n.php';
$siteName = cfg('site.name');
$pageTitle = $title ?? $siteName;
$lang = currentLang();
?><!doctype html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
<meta name="description" content="<?= e($metaDesc ?? t('meta.default_desc')) ?>">
<meta name="theme-color" content="#f04e00">
<link rel="icon" type="image/svg+xml" href="/assets/logo-mark.svg">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/logo-192.png?v=2">
<link rel="icon" type="image/png" sizes="512x512" href="/assets/logo-512.png?v=2">
<link rel="apple-touch-icon" href="/assets/logo-256.png?v=2">
<link rel="manifest" href="/manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: {
    colors: {
      brand: { 50:'#fff7ec',100:'#ffecd1',200:'#ffd5a4',300:'#ffb56c',400:'#ff8a32',500:'#ff6a0a',600:'#f04e00',700:'#c63a04',800:'#9c300d',900:'#7d2a0f',950:'#421407'},
      ink:   { 50:'#f7f7f8',100:'#eeeef1',200:'#d9d9e0',300:'#b9b9c4',400:'#9293a2',500:'#737486',600:'#5b5c6e',700:'#494a5a',800:'#2f303d',900:'#1a1b25',950:'#0a0b12'},
      sand:  { 50:'#faf8f3',100:'#f3eee0',200:'#e7dec0',300:'#d4c595',400:'#c0a866',500:'#a98c46'},
      sea:   { 50:'#eef9f9',100:'#d3f0f0',200:'#aae0e0',300:'#75c8c8',400:'#42a8a9',500:'#258a8c'},
    },
    fontFamily: {
      sans: ['Inter','system-ui','sans-serif'],
      display: ['"Plus Jakarta Sans"','Inter','sans-serif'],
      serif: ['Fraunces','Georgia','serif'],
      mono: ['"JetBrains Mono"','ui-monospace','monospace']
    },
    boxShadow: {
      soft: '0 1px 2px rgb(15 23 42 / 0.04), 0 8px 24px -8px rgb(15 23 42 / 0.08)',
      card: '0 0 0 1px rgb(15 23 42 / 0.04), 0 2px 4px rgb(15 23 42 / 0.04), 0 12px 32px -12px rgb(15 23 42 / 0.10)',
      pop:  '0 4px 8px rgb(15 23 42 / 0.06), 0 24px 48px -16px rgb(15 23 42 / 0.18)',
      glow: '0 0 0 4px rgb(255 106 10 / 0.18)',
      'inner-light': 'inset 0 1px 0 rgb(255 255 255 / 0.06)',
    },
    keyframes: {
      fadeIn:   {'0%':{opacity:'0'},'100%':{opacity:'1'}},
      slideUp:  {'0%':{transform:'translateY(12px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}},
      slideDown:{'0%':{transform:'translateY(-12px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}},
      blurIn:   {'0%':{filter:'blur(12px)',opacity:'0'},'100%':{filter:'blur(0)',opacity:'1'}},
      shimmer:  {'0%':{backgroundPosition:'-1000px 0'},'100%':{backgroundPosition:'1000px 0'}},
      float:    {'0%,100%':{transform:'translateY(0)'},'50%':{transform:'translateY(-10px)'}},
      glowPulse:{'0%,100%':{opacity:'.6'},'50%':{opacity:'1'}}
    },
    animation: {
      'fade-in':  'fadeIn .35s cubic-bezier(.2,.7,.2,1) both',
      'slide-up': 'slideUp .45s cubic-bezier(.2,.7,.2,1) both',
      'slide-down': 'slideDown .45s cubic-bezier(.2,.7,.2,1) both',
      'blur-in':  'blurIn .55s cubic-bezier(.2,.7,.2,1) both',
      'shimmer':  'shimmer 2s linear infinite',
      'float':    'float 6s ease-in-out infinite',
      'glow-pulse':'glowPulse 3s ease-in-out infinite'
    },
    transitionTimingFunction: { 'out-expo': 'cubic-bezier(.16,1,.3,1)' }
  } }
};
try { if (localStorage.getItem('cv-theme') === 'dark') document.documentElement.classList.add('dark'); } catch(e){}
</script>
<style type="text/tailwindcss">
@layer base {
  html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; scroll-behavior: smooth; overflow-x: clip; }
  body { @apply bg-white text-ink-900 dark:bg-ink-950 dark:text-ink-50; font-feature-settings: 'cv11','ss01','ss03'; overflow-x: clip; width: 100%; max-width: 100%; }
  ::selection { @apply bg-brand-500/30; }
  [x-cloak] { display: none !important; }
  img, video, canvas { max-width: 100%; height: auto; }
  /* Hard cap viewport overflow — exclude fixed elements which need to span viewport */
  body > *:not(.fixed):not([style*="position:fixed"]):not([style*="position: fixed"]) { max-width: 100%; }
  table { max-width: 100%; }
}
@layer components {
  .container-wide { @apply max-w-[1240px] mx-auto px-5 sm:px-6 lg:px-8; }
  .container-narrow { @apply max-w-[920px] mx-auto px-5 sm:px-6; }

  .card { @apply bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800/80 rounded-2xl shadow-soft; }
  .card-elev { @apply bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800/80 rounded-2xl shadow-card; }
  .card-glass { @apply bg-white/70 dark:bg-ink-900/60 backdrop-blur-xl border border-white/40 dark:border-white/5 rounded-2xl shadow-card; }
  .card-hover { @apply transition-all duration-300 hover:-translate-y-0.5 hover:shadow-pop; }

  .btn { @apply inline-flex items-center justify-center gap-2 rounded-xl font-medium transition-all duration-200 active:scale-[0.97] disabled:opacity-50 disabled:pointer-events-none; }
  .btn-primary { @apply btn bg-brand-500 text-white px-5 py-2.5 hover:bg-brand-600 hover:shadow-glow shadow-[0_8px_24px_-12px_rgba(255,106,10,.55)]; }
  .btn-secondary { @apply btn bg-ink-900 text-white px-5 py-2.5 hover:bg-ink-800 dark:bg-white dark:text-ink-900 dark:hover:bg-ink-100; }
  .btn-outline { @apply btn border border-ink-200 dark:border-ink-700/80 text-ink-800 dark:text-ink-100 px-5 py-2.5 hover:bg-ink-50 dark:hover:bg-ink-800; }
  .btn-ghost { @apply btn text-ink-700 dark:text-ink-200 px-3 py-2 hover:bg-ink-100 dark:hover:bg-ink-800; }
  .btn-danger { @apply btn bg-red-600 text-white px-5 py-2.5 hover:bg-red-700; }

  .input { @apply w-full rounded-xl border border-ink-200 dark:border-ink-700/80 bg-white dark:bg-ink-900/80 px-3.5 py-2.5 text-sm placeholder:text-ink-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 transition-all duration-200; }
  .label { @apply text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-1.5 block; }

  .badge { @apply inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium; }
  .badge-soft { @apply badge bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-200; }
  .badge-success { @apply badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300; }
  .badge-warning { @apply badge bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300; }
  .badge-danger  { @apply badge bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300; }
  .badge-info    { @apply badge bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300; }
  .badge-brand   { @apply badge bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300; }

  table.table-base { @apply w-full text-sm; }
  table.table-base thead th { @apply text-left text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400 px-4 py-3 border-b border-ink-100 dark:border-ink-800/80 bg-ink-50/50 dark:bg-ink-900/40; }
  table.table-base tbody td { @apply px-4 py-3.5 border-b border-ink-100 dark:border-ink-800/80; }
  table.table-base tbody tr { @apply transition-colors duration-150; }
  table.table-base tbody tr:hover { @apply bg-ink-50/70 dark:bg-ink-900/40; }

  .gradient-mesh {
    background-image:
      radial-gradient(at 18% 12%, rgb(255 213 164 / .55) 0px, transparent 45%),
      radial-gradient(at 85% 0%, rgb(255 138 50 / .35) 0px, transparent 45%),
      radial-gradient(at 70% 90%, rgb(255 106 10 / .25) 0px, transparent 50%),
      radial-gradient(at 0% 100%, rgb(170 224 224 / .35) 0px, transparent 45%);
  }
  .dark .gradient-mesh {
    background-image:
      radial-gradient(at 18% 12%, rgb(255 138 50 / .12) 0px, transparent 45%),
      radial-gradient(at 85% 0%, rgb(240 78 0 / .12) 0px, transparent 45%),
      radial-gradient(at 70% 90%, rgb(37 138 140 / .12) 0px, transparent 50%);
  }

  .text-gradient-brand { @apply bg-gradient-to-br from-brand-500 to-brand-700 bg-clip-text text-transparent; }
  .text-balance { text-wrap: balance; }
  .text-pretty { text-wrap: pretty; }

  .divider-soft { @apply h-px bg-gradient-to-r from-transparent via-ink-200 dark:via-ink-800 to-transparent; }

  .ring-focus { @apply focus-within:ring-4 focus-within:ring-brand-500/15 focus-within:border-brand-500; }
}
@layer utilities {
  .scrollbar-thin::-webkit-scrollbar { width: 8px; height: 8px; }
  .scrollbar-thin::-webkit-scrollbar-thumb { @apply bg-ink-200 dark:bg-ink-700 rounded-full; }
  .mask-fade-r { mask-image: linear-gradient(to right, black 70%, transparent); }
  .mask-fade-b { mask-image: linear-gradient(to bottom, black 70%, transparent); }

  /* Date input — ammorbidisce il "gg/mm/aaaa" quando il campo è vuoto */
  input[type="date"]:not(:focus):invalid::-webkit-datetime-edit { color: rgb(148 163 184); font-weight: 400; }
  input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: .55; cursor: pointer; transition: opacity .2s;
    filter: invert(45%) sepia(8%) saturate(380%) hue-rotate(176deg) brightness(95%) contrast(85%);
  }
  input[type="date"]:hover::-webkit-calendar-picker-indicator { opacity: 1; }
  .dark input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(75%); }
}
</style>
<script>
(function(){
  try {
    var isPwa = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    if (!isPwa) return;

    // Mostra la splash SOLO al primo caricamento della sessione app (cold start),
    // non ad ogni navigazione di pagina. sessionStorage si svuota alla chiusura dell'app.
    if (sessionStorage.getItem('cv-pwa-splash-shown')) return;

    // Se la pagina è caricata via navigazione interna (Back/Forward o link cliccato
    // dopo che la app era già aperta), non è una vera apertura.
    try {
      var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
      if (nav && nav.type !== 'reload' && nav.type !== 'navigate') return;
      // Anche su navigate puro: se è una sotto-navigazione dopo un primo load, skip.
      // Lo gestiamo col flag sessionStorage che impostiamo qui sotto.
    } catch(e){}

    sessionStorage.setItem('cv-pwa-splash-shown', '1');

    var s = document.createElement('div');
    s.id = 'cv-pwa-splash';
    s.style.cssText = 'position:fixed;inset:0;z-index:99999;background:#1a0d05 url(/assets/sharm/splash_bg.jpg) center/cover no-repeat;display:flex;align-items:center;justify-content:center;transition:opacity .5s;';
    s.innerHTML = '<div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(240,78,0,.35) 0%,rgba(26,13,5,.55) 60%,rgba(0,0,0,.7) 100%)"></div>'
      + '<div style="position:relative;text-align:center"><img src="/assets/logo-512.png?v=2" alt="" style="height:144px;width:auto;filter:drop-shadow(0 8px 24px rgba(0,0,0,.5))">'
      + '<div style="color:rgba(255,255,255,.92);margin-top:18px;font-family:Fraunces,Georgia,serif;font-size:18px;font-weight:500;letter-spacing:.02em;text-shadow:0 2px 12px rgba(0,0,0,.6)">Sharm El Sheikh · Patrizia Mancini</div></div>';
    document.documentElement.appendChild(s);
    setTimeout(function(){ s.style.opacity='0'; setTimeout(function(){ s.remove(); }, 600); }, 1400);
  } catch(e){}
})();
</script>
</head>
<body class="min-h-screen">
