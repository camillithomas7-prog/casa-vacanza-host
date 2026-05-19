<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$defaultRule = "🚭 Vietato fumare all'interno dell'appartamento. È possibile fumare esclusivamente sul balcone o in terrazzo.";

$apartments = rows('SELECT id, name, city, rules FROM apartments ORDER BY city ASC, name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    csrfCheck($_POST['csrf'] ?? null);
    $mode = $_POST['mode'] ?? 'append';
    $ruleText = trim($_POST['rule_text'] ?? $defaultRule) ?: $defaultRule;
    $added = 0; $skipped = 0;
    foreach ($apartments as $a) {
        $current = trim($a['rules'] ?? '');
        if ($mode === 'replace') {
            $final = $ruleText;
        } else { // append (default): non duplicare se già presente
            // Match laschino: cerca le parole-chiave principali per evitare duplicati
            $alreadyHas = stripos($current, 'vietato fumare') !== false
                       || stripos($current, 'non si fuma') !== false
                       || stripos($current, 'no smoking') !== false;
            if ($alreadyHas) { $skipped++; continue; }
            $final = $current === '' ? $ruleText : ($ruleText . "\n\n" . $current);
        }
        q('UPDATE apartments SET rules = ? WHERE id = ?', [$final, $a['id']]);
        $added++;
    }
    flash("Regola applicata a $added appartament" . ($added === 1 ? 'o' : 'i') . ($skipped ? " · $skipped saltat" . ($skipped === 1 ? 'o' : 'i') . ' (regola già presente)' : ''));
    redirect('/admin/appartamenti.php');
}

$title = 'Imposta regole di default';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div>
    <h1 class="font-serif text-3xl font-semibold">Regole di default</h1>
    <p class="text-ink-500 mt-1">Aggiunge una regola comune a tutti i <?= count($apartments) ?> appartamenti, mantenendo eventuali regole specifiche già scritte.</p>
  </div>

  <form method="post" class="card p-5 space-y-4">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="confirm" value="yes">

    <label class="block">
      <span class="label">Testo regola</span>
      <textarea name="rule_text" class="input min-h-[100px] font-mono text-sm"><?= e($defaultRule) ?></textarea>
      <span class="text-[11px] text-ink-500 mt-1 block">Modifica liberamente se vuoi cambiare il wording.</span>
    </label>

    <div>
      <span class="label">Modalità</span>
      <div class="grid sm:grid-cols-2 gap-2 mt-1">
        <label class="card p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-500/10 transition">
          <input type="radio" name="mode" value="append" checked class="mr-2">
          <b>Aggiungi in cima</b>
          <div class="text-xs text-ink-500 mt-1">Inserisce la regola sopra alle regole esistenti. Se la stessa regola è già presente (rilevazione "vietato fumare" / "no smoking") salta l'appartamento. Consigliato.</div>
        </label>
        <label class="card p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-500/10 transition">
          <input type="radio" name="mode" value="replace" class="mr-2">
          <b>Sostituisci tutto</b>
          <div class="text-xs text-ink-500 mt-1">Cancella le regole esistenti e mette solo questa. Usa solo se vuoi ripartire da zero.</div>
        </label>
      </div>
    </div>

    <div class="flex gap-2">
      <button type="submit" class="btn-primary"><i data-lucide="check-circle" class="size-[16px]"></i> Applica ai <?= count($apartments) ?> appartamenti</button>
      <a href="/admin/appartamenti.php" class="btn-outline">Annulla</a>
    </div>
  </form>

  <div class="card p-4">
    <h4 class="font-display font-bold text-sm mb-2">Stato attuale</h4>
    <ul class="text-xs text-ink-600 space-y-0.5 max-h-[300px] overflow-y-auto">
      <?php foreach ($apartments as $a):
        $has = stripos($a['rules'] ?? '', 'vietato fumare') !== false || stripos($a['rules'] ?? '', 'non si fuma') !== false || stripos($a['rules'] ?? '', 'no smoking') !== false;
      ?>
        <li class="flex items-center gap-2">
          <?php if ($has): ?>
            <span class="text-emerald-600">✓</span>
          <?php else: ?>
            <span class="text-ink-300">○</span>
          <?php endif; ?>
          <?= e($a['name']) ?> <span class="text-ink-400">(<?= e($a['city']) ?>)</span>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="text-[11px] text-ink-500 mt-2">✓ = regola anti-fumo già presente · ○ = da aggiungere</p>
  </div>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
