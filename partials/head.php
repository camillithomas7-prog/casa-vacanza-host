<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/utils.php';
$siteName = cfg('site.name');
$pageTitle = $title ?? $siteName;
?><!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
<meta name="description" content="<?= e($metaDesc ?? 'Affitti brevi premium gestiti con cura. Prenota la tua prossima casa per le vacanze.') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23f04e00'/><path d='M8 18l8-8 8 8v6a2 2 0 0 1-2 2h-3v-6h-6v6h-3a2 2 0 0 1-2-2v-6z' fill='%23fff'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: {
    colors: {
      brand: { 50:'#fff7ec',100:'#ffecd1',200:'#ffd5a4',300:'#ffb56c',400:'#ff8a32',500:'#ff6a0a',600:'#f04e00',700:'#c63a04',800:'#9c300d',900:'#7d2a0f'},
      ink:   { 50:'#f7f7f8',100:'#eeeef1',200:'#d9d9e0',300:'#b9b9c4',400:'#9293a2',500:'#737486',600:'#5b5c6e',700:'#494a5a',800:'#2f303d',900:'#1a1b25',950:'#0d0e15'},
    },
    fontFamily: { sans:['Inter','system-ui','sans-serif'], display:['Plus Jakarta Sans','Inter','sans-serif'] },
    boxShadow: { soft: '0 1px 2px rgb(0 0 0 / 0.04), 0 8px 24px -8px rgb(0 0 0 / 0.08)' },
    keyframes: { fadeIn: {'0%':{opacity:'0'},'100%':{opacity:'1'}}, slideUp: {'0%':{transform:'translateY(8px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}} },
    animation: { 'fade-in':'fadeIn .25s ease-out', 'slide-up':'slideUp .25s ease-out' }
  } }
};
try { var t = localStorage.getItem('cv-theme'); if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch(e){}
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  .font-display { font-family: 'Plus Jakarta Sans', Inter, sans-serif; }
  .card { @apply bg-white dark:bg-ink-900 border border-ink-100 dark:border-ink-800 rounded-2xl shadow-soft; }
  .btn-primary { @apply inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white px-4 py-2.5 font-medium transition active:scale-[0.98] disabled:opacity-50; }
  .btn-secondary { @apply inline-flex items-center justify-center gap-2 rounded-xl bg-ink-900 hover:bg-ink-800 text-white px-4 py-2.5 font-medium transition dark:bg-white dark:text-ink-900 dark:hover:bg-ink-100; }
  .btn-outline { @apply inline-flex items-center justify-center gap-2 rounded-xl border border-ink-200 dark:border-ink-700 hover:bg-ink-50 dark:hover:bg-ink-800 px-4 py-2.5 font-medium transition; }
  .btn-ghost { @apply inline-flex items-center justify-center gap-2 rounded-xl hover:bg-ink-100 dark:hover:bg-ink-800 text-ink-700 dark:text-ink-200 px-3 py-2 transition; }
  .btn-danger { @apply inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 font-medium transition; }
  .input { @apply w-full rounded-xl border border-ink-200 dark:border-ink-700 bg-white dark:bg-ink-900 px-3.5 py-2.5 text-sm placeholder:text-ink-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 transition; }
  .label { @apply text-sm font-medium text-ink-700 dark:text-ink-200 mb-1.5 block; }
  .badge { @apply inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium; }
  table.table-base { @apply w-full text-sm; }
  table.table-base thead th { @apply text-left text-xs font-semibold uppercase tracking-wide text-ink-500 px-4 py-3 border-b border-ink-100 dark:border-ink-800; }
  table.table-base tbody td { @apply px-4 py-3 border-b border-ink-100 dark:border-ink-800; }
  table.table-base tbody tr:hover { @apply bg-ink-50/50 dark:bg-ink-900/40; }
</style>
</head>
<body class="bg-white dark:bg-ink-950 text-ink-900 dark:text-ink-100 min-h-screen">
