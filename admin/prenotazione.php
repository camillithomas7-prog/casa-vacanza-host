<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/messages.php';
require_once __DIR__ . '/../lib/cleaning.php';
requireAdmin();

$id = $_GET['id'] ?? '';
$b = row('SELECT b.*, a.name AS apartment_name, a.address AS apartment_address, a.city AS apartment_city,
          a.check_in_time, a.check_out_time, a.rules AS apartment_rules,
          c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.country AS customer_country, c.document AS customer_document
          FROM bookings b JOIN apartments a ON b.apartment_id = a.id JOIN customers c ON b.customer_id = c.id WHERE b.id = ?', [$id]);
if (!$b) { flash('Prenotazione non trovata', 'error'); redirect('/admin/prenotazioni.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';

    if ($action === 'status') {
        q('UPDATE bookings SET status = ? WHERE id = ?', [$_POST['status'], $b['id']]);
        logActivity('status', 'booking', $b['id'], $_POST['status']);
        try { ensureCleaningSession($b['id']); } catch (Throwable $e) {}
        flash('Stato aggiornato');
    }
    if ($action === 'delete') {
        q('DELETE FROM bookings WHERE id = ?', [$b['id']]);
        flash('Prenotazione eliminata');
        redirect('/admin/prenotazioni.php');
    }
    if ($action === 'payment_add') {
        q('INSERT INTO payments (id, booking_id, amount, type, method, notes) VALUES (?, ?, ?, ?, ?, ?)',
            [newId(), $b['id'], (float)$_POST['amount'], $_POST['type'], $_POST['method'], $_POST['notes'] ?: null]);
        $sum = (float)val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE booking_id = ?', [$b['id']]);
        q('UPDATE bookings SET paid = ? WHERE id = ?', [$sum, $b['id']]);
        flash('Pagamento registrato');
    }
    if ($action === 'payment_delete') {
        q('DELETE FROM payments WHERE id = ? AND booking_id = ?', [$_POST['payment_id'], $b['id']]);
        $sum = (float)val('SELECT COALESCE(SUM(amount),0) FROM payments WHERE booking_id = ?', [$b['id']]);
        q('UPDATE bookings SET paid = ? WHERE id = ?', [$sum, $b['id']]);
    }
    if ($action === 'doc_add' && !empty($_FILES['files']['tmp_name'][0])) {
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            if (!is_uploaded_file($tmp)) continue;
            $name = $_FILES['files']['name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $fname = uniqid('d_') . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
            move_uploaded_file($tmp, __DIR__ . '/../uploads/documents/' . $fname);
            q('INSERT INTO documents (id, booking_id, url, filename, type) VALUES (?, ?, ?, ?, ?)',
                [newId(), $b['id'], '/uploads/documents/' . $fname, $name, 'id']);
        }
        flash('Documento caricato');
    }
    if ($action === 'doc_delete') {
        q('DELETE FROM documents WHERE id = ? AND booking_id = ?', [$_POST['doc_id'], $b['id']]);
    }
    redirect('/admin/prenotazione.php?id=' . $b['id']);
}

$payments = rows('SELECT * FROM payments WHERE booking_id = ? ORDER BY date DESC', [$b['id']]);
$documents = rows('SELECT * FROM documents WHERE booking_id = ?', [$b['id']]);
$templates = rows('SELECT * FROM message_templates WHERE active = 1 ORDER BY name ASC');
$tplTranslations = templateTranslations();
$due = (float)$b['total'] - (float)$b['paid'];

// Auto-detect lingua dell'ospite dal country (supporta sia ISO code IT/DE/RU/... sia stringhe libere)
$countryLang = function (?string $country): string {
    if (!$country) return 'it';
    $code = strtoupper(trim($country));
    // ISO alpha-2
    $byCode = [
        'IT'=>'it', 'CH'=>'it', 'SM'=>'it', 'VA'=>'it',
        'DE'=>'de', 'AT'=>'de', 'LI'=>'de',
        'RU'=>'ru', 'BY'=>'ru', 'UA'=>'ru', 'KZ'=>'ru', 'KG'=>'ru',
        'ES'=>'es', 'MX'=>'es', 'AR'=>'es', 'CL'=>'es', 'CO'=>'es', 'PE'=>'es', 'VE'=>'es', 'UY'=>'es', 'BO'=>'es', 'PY'=>'es', 'EC'=>'es', 'CU'=>'es', 'DO'=>'es', 'GT'=>'es', 'CR'=>'es', 'PA'=>'es', 'SV'=>'es', 'HN'=>'es', 'NI'=>'es',
    ];
    if (strlen($code) === 2 && isset($byCode[$code])) return $byCode[$code];
    // Fallback su stringhe libere (legacy)
    $c = mb_strtolower($country);
    if (preg_match('/italia|italy|italian/u', $c)) return 'it';
    if (preg_match('/germ|deutsch|austria|switzer|svizzera/u', $c)) return 'de';
    if (preg_match('/russi|україн|ukrain|belarus/u', $c)) return 'ru';
    if (preg_match('/españ|spain|spagna|méxic|mexic|argent|chile|colomb|peru/u', $c)) return 'es';
    return 'en';
};
$guestLang = $countryLang($b['customer_country'] ?? '');

$vars = [
  'nome' => $b['customer_name'], 'appartamento' => $b['apartment_name'],
  'indirizzo' => $b['apartment_address'] ?: $b['apartment_city'],
  'checkin' => fmtDate($b['check_in']), 'checkout' => fmtDate($b['check_out']),
  'ora_checkin' => $b['check_in_time'], 'ora_checkout' => $b['check_out_time'],
  'ospiti' => $b['guests'], 'totale' => fmtMoney((float)$b['total']),
  'acconto' => fmtMoney((float)$b['paid']), 'saldo' => fmtMoney($due),
  'codice' => $b['code'], 'telefono' => $b['customer_phone'],
];

function statusBadgeBig($s) {
  $map = ['pending'=>['yellow','In attesa'],'confirmed'=>['blue','Confermata'],'checked_in'=>['green','Check-in'],'completed'=>['gray','Completata'],'cancelled'=>['red','Cancellata']];
  $x = $map[$s] ?? ['gray', $s];
  $cls = ['gray'=>'bg-ink-100 text-ink-700','green'=>'bg-emerald-100 text-emerald-700','red'=>'bg-red-100 text-red-700','yellow'=>'bg-amber-100 text-amber-800','blue'=>'bg-sky-100 text-sky-700'][$x[0]] ?? '';
  return '<span class="badge ' . $cls . '">' . e($x[1]) . '</span>';
}

$title = $b['customer_name'] . ' · ' . $b['code'];
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<div class="space-y-5">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold"><?= e($b['customer_name']) ?></h1>
      <div class="text-sm text-ink-500 mt-1 flex items-center gap-2 flex-wrap">
        <span class="font-mono"><?= e($b['code']) ?></span> · <?= statusBadgeBig($b['status']) ?> · <span><?= e($b['apartment_name']) ?></span>
      </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <form method="post" class="contents">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="status">
        <select name="status" onchange="this.form.submit()" class="input">
          <?php foreach (['pending'=>'In attesa','confirmed'=>'Conferma','checked_in'=>'Check-in','completed'=>'Completa','cancelled'=>'Cancella'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $b['status'] === $v ? 'selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <form method="post" onsubmit="return confirm('Eliminare prenotazione?')">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="delete">
        <button class="btn-danger"><i data-lucide="trash-2" class="size-[16px]"></i> Elimina</button>
      </form>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
      <div class="card p-4 sm:p-5">
        <h3 class="font-display font-bold mb-3">Soggiorno</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
          <?php foreach ([
            ['Check-in', fmtDate($b['check_in'])], ['Check-out', fmtDate($b['check_out'])],
            ['Notti', $b['nights']], ['Ospiti', $b['guests']],
            ['Pernottamento', fmtMoney((float)$b['base_price'])], ['Pulizie', fmtMoney((float)$b['cleaning_fee'])],
            ['Sconto', $b['discount'] > 0 ? '-' . fmtMoney((float)$b['discount']) : '—'], ['Totale', fmtMoney((float)$b['total'])],
          ] as $i): ?>
            <div><div class="text-xs text-ink-500"><?= e($i[0]) ?></div><div class="<?= $i[0] === 'Totale' ? 'text-lg font-display font-bold' : '' ?>"><?= e($i[1]) ?></div></div>
          <?php endforeach; ?>
        </div>
        <?php if ($b['notes']): ?><div class="text-sm mt-4 p-3 rounded-xl bg-ink-50 dark:bg-ink-900"><?= e($b['notes']) ?></div><?php endif; ?>
      </div>

      <div class="card p-4 sm:p-5">
        <h3 class="font-display font-bold mb-3">Contatti cliente</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <div><div class="text-xs text-ink-500">Email</div><div><?= e($b['customer_email'] ?: '—') ?></div></div>
          <div><div class="text-xs text-ink-500">Telefono</div><div><?= e($b['customer_phone'] ?: '—') ?></div></div>
          <div><div class="text-xs text-ink-500">Paese</div><div><?= e($b['customer_country'] ? countryName($b['customer_country'], 'it') : '—') ?></div></div>
          <div><div class="text-xs text-ink-500">Documento</div><div><?= e($b['customer_document'] ?: '—') ?></div></div>
        </div>
      </div>

      <div class="card p-4 sm:p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-display font-bold">Pagamenti</h3>
        </div>
        <form method="post" class="grid grid-cols-1 sm:grid-cols-5 gap-2 mb-4 p-3 rounded-xl bg-ink-50 dark:bg-ink-900">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="payment_add">
          <input class="input" type="number" step="0.01" name="amount" placeholder="Importo" value="<?= $due > 0 ? $due : 0 ?>" required>
          <select class="input" name="type"><option value="deposit">Acconto</option><option value="balance" selected>Saldo</option><option value="refund">Rimborso</option></select>
          <select class="input" name="method"><option value="cash">Contanti</option><option value="bank" selected>Bonifico</option><option value="stripe">Carta</option><option value="paypal">PayPal</option><option value="other">Altro</option></select>
          <input class="input" name="notes" placeholder="Note">
          <button class="btn-primary"><i data-lucide="plus" class="size-[14px]"></i> Aggiungi</button>
        </form>
        <ul class="divide-y divide-ink-100 dark:divide-ink-800">
          <?php foreach ($payments as $p): ?>
            <li class="py-2 flex items-center justify-between">
              <div>
                <div class="font-medium"><?= fmtMoney((float)$p['amount']) ?> <span class="text-xs text-ink-500 font-normal">· <?= e(['deposit'=>'acconto','balance'=>'saldo','refund'=>'rimborso'][$p['type']] ?? $p['type']) ?> · <?= e($p['method']) ?></span></div>
                <div class="text-xs text-ink-500"><?= fmtDateTime($p['date']) ?> <?= $p['notes'] ? '· ' . e($p['notes']) : '' ?></div>
              </div>
              <form method="post" onsubmit="return confirm('Eliminare?')">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="payment_delete">
                <input type="hidden" name="payment_id" value="<?= e($p['id']) ?>">
                <button class="btn-ghost text-red-600"><i data-lucide="trash-2" class="size-[14px]"></i></button>
              </form>
            </li>
          <?php endforeach; ?>
          <?php if (!$payments): ?><li class="py-4 text-sm text-ink-500 text-center">Nessun pagamento registrato.</li><?php endif; ?>
        </ul>
      </div>

      <div class="card p-4 sm:p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-display font-bold">Documenti cliente</h3>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="doc_add">
            <label class="btn-outline cursor-pointer text-sm"><i data-lucide="upload" class="size-[14px]"></i> Carica<input type="file" name="files[]" multiple class="hidden" onchange="this.form.submit()"></label>
          </form>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <?php foreach ($documents as $d): ?>
            <div class="flex items-center gap-2 p-2 rounded-xl border border-ink-100 dark:border-ink-800">
              <i data-lucide="file-text" class="size-[18px] text-brand-500"></i>
              <a href="<?= e($d['url']) ?>" target="_blank" class="text-sm flex-1 truncate hover:underline"><?= e($d['filename']) ?></a>
              <form method="post" onsubmit="return confirm('Eliminare?')">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="doc_delete">
                <input type="hidden" name="doc_id" value="<?= e($d['id']) ?>">
                <button class="btn-ghost text-red-600 p-1"><i data-lucide="trash-2" class="size-[14px]"></i></button>
              </form>
            </div>
          <?php endforeach; ?>
          <?php if (!$documents): ?><div class="col-span-full text-sm text-ink-500 text-center py-3">Nessun documento.</div><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="space-y-5">
      <div class="card p-4 sm:p-5">
        <h3 class="font-display font-bold mb-3">Pagamenti</h3>
        <div class="flex justify-between py-1.5 text-sm border-b border-ink-100 dark:border-ink-800"><span class="text-ink-500">Totale</span><span><?= fmtMoney((float)$b['total']) ?></span></div>
        <div class="flex justify-between py-1.5 text-sm border-b border-ink-100 dark:border-ink-800"><span class="text-ink-500">Incassato</span><span><?= fmtMoney((float)$b['paid']) ?></span></div>
        <div class="flex justify-between py-1.5 text-sm"><span class="text-ink-500">Saldo</span><span class="<?= $due > 0 ? 'text-amber-600 font-semibold' : 'text-emerald-600' ?>"><?= fmtMoney(max(0, $due)) ?></span></div>
      </div>

      <div class="card p-4 sm:p-5" x-data="messageSender(<?= e(json_encode($vars)) ?>, <?= e(json_encode($templates)) ?>, <?= e(json_encode($tplTranslations)) ?>, '<?= e($guestLang) ?>')">
        <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
          <h3 class="font-display font-bold">Messaggi rapidi</h3>
          <div class="flex items-center gap-1 text-xs">
            <?php foreach (['it'=>'🇮🇹','en'=>'🇬🇧','ru'=>'🇷🇺','es'=>'🇪🇸','de'=>'🇩🇪'] as $code => $flag): ?>
              <button type="button" @click="setLang('<?= $code ?>')"
                :class="lang === '<?= $code ?>' ? 'bg-brand-500 text-white shadow-[0_2px_6px_-2px_rgba(255,106,10,.6)]' : 'bg-ink-100 dark:bg-ink-800 text-ink-600 dark:text-ink-300 hover:bg-ink-200 dark:hover:bg-ink-700'"
                class="px-2 py-1 rounded-lg font-medium transition flex items-center gap-1 text-[11px]" title="<?= e(strtoupper($code)) ?>">
                <span class="text-[14px]"><?= $flag ?></span> <?= strtoupper($code) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
        <select class="input mb-3" x-model="key" @change="render()">
          <?php foreach ($templates as $t): ?><option value="<?= e($t['template_key']) ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
        </select>
        <textarea readonly class="input text-sm min-h-[160px] font-mono" x-text="body"></textarea>
        <p class="text-xs text-ink-500 mt-2" x-show="!hasTranslation" x-cloak>
          <i data-lucide="info" class="size-[12px] inline"></i>
          Traduzione non disponibile per questo template — viene mostrato il testo italiano.
        </p>
        <div class="grid grid-cols-2 gap-2 mt-3">
          <a :href="waLink" target="_blank" class="btn-primary text-sm justify-center"><i data-lucide="message-circle" class="size-[16px]"></i> WhatsApp</a>
          <a :href="mailLink" class="btn-secondary text-sm justify-center"><i data-lucide="mail" class="size-[16px]"></i> Email</a>
          <button @click="copy()" type="button" class="btn-outline text-sm col-span-2 justify-center"><span x-text="copied ? '✓ Copiato!' : 'Copia testo'"></span></button>
        </div>
      </div>

      <div class="card p-4 sm:p-5 text-sm">
        <h3 class="font-display font-bold mb-3">Stampa & contratto</h3>
        <a href="/admin/contratto.php?id=<?= e($b['id']) ?>" target="_blank" class="btn-outline w-full justify-center">📄 Contratto soggiorno</a>
        <a href="/admin/qr.php?id=<?= e($b['id']) ?>" target="_blank" class="btn-outline w-full justify-center mt-2">🔗 QR check-in</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function messageSender(vars, templates, translations, guestLang) {
  return {
    key: templates[0]?.template_key || '',
    lang: guestLang || 'it',
    templates, translations,
    body: '', subject: '', waLink: '#', mailLink: '#', copied: false,
    hasTranslation: true,
    init() { this.render(); },
    setLang(l) { this.lang = l; this.render(); },
    render() {
      const sub = (s) => (s || '').replace(/\{\{\s*(\w+)\s*\}\}/g, (_, k) => vars[k] ?? '');
      const tplKey = this.key;
      const trMap = this.translations[tplKey];
      let subject = '', body = '';
      this.hasTranslation = true;
      if (trMap && trMap[this.lang]) {
        subject = trMap[this.lang].subject;
        body = trMap[this.lang].body;
      } else if (trMap && trMap.it) {
        subject = trMap.it.subject;
        body = trMap.it.body;
        this.hasTranslation = (this.lang === 'it');
      } else {
        // template custom non in dizionario: usa quello dal DB (italiano)
        const tpl = this.templates.find(t => t.template_key === tplKey);
        if (tpl) { subject = tpl.subject || ''; body = tpl.body || ''; }
        this.hasTranslation = (this.lang === 'it');
      }
      this.body = sub(body);
      this.subject = sub(subject);
      const phone = (vars.telefono || '').replace(/\D/g, '');
      this.waLink = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(this.body);
      this.mailLink = 'mailto:?subject=' + encodeURIComponent(this.subject) + '&body=' + encodeURIComponent(this.body);
    },
    async copy() { await navigator.clipboard.writeText(this.body); this.copied = true; setTimeout(() => this.copied = false, 1500); }
  };
}
</script>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
