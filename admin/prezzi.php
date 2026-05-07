<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$apartments = rows('SELECT * FROM apartments ORDER BY name ASC');
if (!$apartments) { $title='Prezzi'; require __DIR__.'/../partials/head.php'; require __DIR__.'/../partials/admin-shell-top.php'; echo '<div class="card p-10 text-center">Crea prima un appartamento.</div>'; require __DIR__.'/../partials/admin-shell-bottom.php'; exit; }

$aptId = $_GET['apt'] ?? $apartments[0]['id'];
$apt = row('SELECT * FROM apartments WHERE id = ?', [$aptId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        q('INSERT INTO price_rules (id, apartment_id, name, start_date, end_date, price_per_night, min_nights, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [newId(), $aptId, $_POST['name'], $_POST['start_date'], $_POST['end_date'], (float)$_POST['price_per_night'], (int)$_POST['min_nights'], (int)$_POST['priority']]);
        flash('Regola aggiunta');
    }
    if ($action === 'delete') {
        q('DELETE FROM price_rules WHERE id = ?', [$_POST['rule_id']]);
    }
    if ($action === 'copy') {
        $rules_src = rows('SELECT * FROM price_rules WHERE apartment_id = ?', [$aptId]);
        foreach ($rules_src as $r) {
            q('INSERT INTO price_rules (id, apartment_id, name, start_date, end_date, price_per_night, min_nights, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [newId(), $_POST['target'], $r['name'], $r['start_date'], $r['end_date'], $r['price_per_night'], $r['min_nights'], $r['priority']]);
        }
        flash(count($rules_src) . ' regole copiate');
    }
    redirect('/admin/prezzi.php?apt=' . urlencode($aptId));
}

$rules = rows('SELECT * FROM price_rules WHERE apartment_id = ? ORDER BY start_date ASC', [$aptId]);

$title = 'Prezzi avanzati';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5" x-data="{ helpOpen: false }">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <div class="flex items-center gap-2">
        <h1 class="font-display text-2xl sm:text-3xl font-bold">Prezzi avanzati</h1>
        <button type="button" @click="helpOpen = true"
          class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-600 hover:bg-orange-200 transition"
          title="Cosa significa? Clicca per la guida"
          aria-label="Apri guida prezzi avanzati">
          <i data-lucide="help-circle" class="size-[20px]"></i>
        </button>
      </div>
      <p class="text-ink-500 mt-1">Tariffe stagionali, weekend, alta stagione, override per intervalli.</p>
    </div>
    <form method="get"><select name="apt" onchange="this.form.submit()" class="input">
      <?php foreach ($apartments as $a): ?><option value="<?= e($a['id']) ?>" <?= $a['id'] === $aptId ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
    </select></form>
  </div>

  <!-- Modale guida -->
  <div x-show="helpOpen" x-cloak
       @keydown.escape.window="helpOpen = false"
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       style="display: none;">
    <div class="absolute inset-0 bg-black/50" @click="helpOpen = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between rounded-t-2xl">
        <h2 class="font-display text-2xl font-bold">Come funzionano i Prezzi avanzati</h2>
        <button type="button" @click="helpOpen = false" class="text-ink-500 hover:text-ink-900" aria-label="Chiudi guida">
          <i data-lucide="x" class="size-[24px]"></i>
        </button>
      </div>
      <div class="px-6 py-5 space-y-5 text-[15px] leading-relaxed">

        <div>
          <h3 class="font-bold text-lg mb-2">A cosa serve?</h3>
          <p>Quando hai creato l'appartamento hai messo <b>un prezzo solo a notte</b> (per esempio 70 €). Quello vale per tutto l'anno.</p>
          <p class="mt-2">Ma in una casa vacanza i prezzi cambiano con le stagioni: <b>in agosto</b> o a <b>Capodanno</b> c'è tanta richiesta e puoi chiedere di più; <b>a novembre</b> invece conviene abbassare per riempire le settimane.</p>
          <p class="mt-2">In questa pagina crei le <b>eccezioni stagionali</b>: scegli un periodo dell'anno e per quei giorni il sito userà un prezzo diverso, al posto dei 70 € normali.</p>
        </div>

        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
          <h3 class="font-bold mb-2">Esempio pratico</h3>
          <p class="mb-3">Per un appartamento con prezzo base <b>70 €/notte</b>, potresti aggiungere queste regole:</p>
          <table class="w-full text-sm">
            <thead class="text-left">
              <tr class="border-b border-orange-200">
                <th class="py-1">Quando</th>
                <th class="py-1 text-right">€/notte</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-orange-200/60">
              <tr><td class="py-1.5">Bassa stagione (1 nov → 31 mar)</td><td class="text-right font-medium">45 €</td></tr>
              <tr><td class="py-1.5">Alta stagione (1 giu → 30 set)</td><td class="text-right font-medium">100 €</td></tr>
              <tr><td class="py-1.5">Ferragosto (10 → 20 ago)</td><td class="text-right font-medium">140 €</td></tr>
              <tr><td class="py-1.5">Capodanno (28 dic → 5 gen)</td><td class="text-right font-medium">180 €</td></tr>
            </tbody>
          </table>
        </div>

        <div>
          <h3 class="font-bold text-lg mb-2">Come si compila?</h3>
          <ul class="space-y-2.5 list-none pl-0">
            <li class="flex gap-3"><span class="font-bold text-orange-600 min-w-[80px]">Nome</span><span>Una piccola etichetta che ti aiuta a riconoscere la regola. Esempi: <i>"Agosto"</i>, <i>"Capodanno"</i>, <i>"Bassa stagione"</i>.</span></li>
            <li class="flex gap-3"><span class="font-bold text-orange-600 min-w-[80px]">Dal / Al</span><span>Le date del periodo. <b>Attenzione:</b> il giorno di <i>"Al"</i> NON è incluso. Esempio: dal <b>1 agosto</b> al <b>1 settembre</b> = tutto agosto.</span></li>
            <li class="flex gap-3"><span class="font-bold text-orange-600 min-w-[80px]">€/notte</span><span>Il prezzo speciale per quel periodo. Sostituisce i 70 € normali.</span></li>
            <li class="flex gap-3"><span class="font-bold text-orange-600 min-w-[80px]">Min notti</span><span>Lascia <b>1</b>. È un campo non ancora attivo.</span></li>
            <li class="flex gap-3"><span class="font-bold text-orange-600 min-w-[80px]">Priorità</span><span>Serve solo se due regole si sovrappongono. Per le stagioni lascia <b>1</b>; per i periodi speciali tipo Capodanno o Ferragosto metti <b>5</b> o <b>10</b>: così quel prezzo "vince" sopra l'altro.</span></li>
          </ul>
        </div>

        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
          <h3 class="font-bold mb-1 text-emerald-900">Non sai cosa fare?</h3>
          <p class="text-emerald-900">Non aggiungere nessuna regola. Il sito userà <b>il prezzo base</b> che hai messo nell'appartamento (<?= fmtMoney((float)$apt['base_price']) ?>) tutti i giorni dell'anno. Tutto funziona lo stesso.</p>
        </div>

      </div>
      <div class="sticky bottom-0 bg-white border-t px-6 py-3 rounded-b-2xl flex justify-end">
        <button type="button" @click="helpOpen = false" class="btn-primary">Ho capito</button>
      </div>
    </div>
  </div>

  <form method="post" class="card p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="add">
    <input class="input col-span-full lg:col-span-2" placeholder="Nome (es. Agosto)" name="name" required>
    <input class="input" type="date" name="start_date" required>
    <input class="input" type="date" name="end_date" required>
    <input class="input" type="number" step="0.01" placeholder="€/notte" name="price_per_night" value="<?= e((string)$apt['base_price']) ?>" required>
    <input class="input" type="number" placeholder="Min notti" name="min_nights" value="1">
    <input class="input" type="number" placeholder="Priorità" name="priority" value="1">
    <button class="btn-primary col-span-full"><i data-lucide="plus" class="size-[16px]"></i> Aggiungi regola</button>
  </form>

  <div class="card p-0 overflow-x-auto">
    <table class="table-base">
      <thead><tr><th>Nome</th><th>Dal</th><th>Al</th><th>€/notte</th><th>Min notti</th><th>Priorità</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rules as $r): ?>
          <tr>
            <td class="font-medium"><?= e($r['name']) ?></td>
            <td><?= fmtDateShort($r['start_date']) ?></td>
            <td><?= fmtDateShort($r['end_date']) ?></td>
            <td><?= fmtMoney((float)$r['price_per_night']) ?></td>
            <td><?= (int)$r['min_nights'] ?></td>
            <td><?= (int)$r['priority'] ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Eliminare?')">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="rule_id" value="<?= e($r['id']) ?>">
                <button class="btn-ghost text-red-600"><i data-lucide="trash-2" class="size-[14px]"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$rules): ?><div class="p-8 text-center text-sm text-ink-500">Nessuna regola attiva. Verrà usato il prezzo base (<?= fmtMoney((float)$apt['base_price']) ?>).</div><?php endif; ?>
  </div>

  <?php if (count($apartments) > 1 && $rules): ?>
    <div class="card p-4 sm:p-5">
      <h3 class="font-display font-bold mb-3">Copia regole verso un altro appartamento</h3>
      <div class="flex gap-2 flex-wrap">
        <?php foreach ($apartments as $a): if ($a['id'] === $aptId) continue; ?>
          <form method="post" onsubmit="return confirm('Copiare le regole?')">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="copy">
            <input type="hidden" name="target" value="<?= e($a['id']) ?>">
            <button class="btn-outline text-sm"><i data-lucide="copy" class="size-[14px]"></i> <?= e($a['name']) ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
