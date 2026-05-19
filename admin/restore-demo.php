<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();
ensureColumns();

$demos = [
    [
        'name' => 'Appartamento 34026',
        'city' => 'Domina Coral Bay',
        'base_price' => 50,
        'guests' => 3, 'bedrooms' => 1, 'beds' => 2, 'bathrooms' => 1, 'size_sqm' => 45,
        'description' => 'Appartamento elegante nel cuore di Domina Coral Bay con accesso alla piscina e al mare.',
        'active' => 0,
    ],
    [
        'name' => 'Naama Bay Sea View',
        'city' => 'Naama Bay',
        'base_price' => 95,
        'guests' => 4, 'bedrooms' => 2, 'beds' => 3, 'bathrooms' => 1, 'size_sqm' => 70,
        'description' => 'Splendido appartamento con vista mare a Naama Bay, vicino alle attrazioni principali.',
        'active' => 1,
    ],
    [
        'name' => 'Nabq Bay Family',
        'city' => 'Nabq Bay',
        'base_price' => 130,
        'guests' => 6, 'bedrooms' => 3, 'beds' => 4, 'bathrooms' => 2, 'size_sqm' => 110,
        'description' => 'Ampio appartamento per famiglie a Nabq Bay con spazi confortevoli e piscina condominiale.',
        'active' => 1,
    ],
    [
        'name' => 'Sharks Bay Diving Suite',
        'city' => 'Sharks Bay',
        'base_price' => 110,
        'guests' => 3, 'bedrooms' => 1, 'beds' => 2, 'bathrooms' => 1, 'size_sqm' => 55,
        'description' => 'Suite ideale per gli amanti delle immersioni, a pochi passi dai migliori diving center di Sharks Bay.',
        'active' => 1,
    ],
    [
        'name' => 'Hadaba Pool Residence',
        'city' => 'Hadaba',
        'base_price' => 70,
        'guests' => 2, 'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1, 'size_sqm' => 50,
        'description' => 'Accogliente appartamento nel residence di Hadaba con piscina e ambiente tranquillo.',
        'active' => 1,
    ],
];

if (($_POST['confirm'] ?? '') === 'yes') {
    csrfCheck($_POST['csrf'] ?? null);
    $created = 0; $skipped = 0;
    foreach ($demos as $d) {
        if (row('SELECT id FROM apartments WHERE name = ?', [$d['name']])) { $skipped++; continue; }
        $data = [
            'name' => $d['name'],
            'slug' => slugify($d['name']),
            'description' => $d['description'],
            'address' => '', 'city' => $d['city'], 'country' => 'Egitto',
            'guests' => $d['guests'], 'bedrooms' => $d['bedrooms'],
            'bathrooms' => $d['bathrooms'], 'beds' => $d['beds'],
            'size_sqm' => $d['size_sqm'],
            'amenities' => json_encode([]),
            'rules' => '',
            'check_in_time' => '15:00', 'check_out_time' => '11:00',
            'base_price' => $d['base_price'],
            'weekly_price' => null, 'biweekly_price' => null,
            'triweekly_price' => null, 'monthly_price' => null, 'weekend_price' => null,
            'cleaning_fee' => 35, 'security_deposit' => 0,
            'city_tax' => 2, 'city_tax_max_nights' => 5,
            'long_stay_discount_7' => 5, 'long_stay_discount_14' => 10, 'long_stay_discount_30' => 20,
            'manager_commission_pct' => 20, 'owner_name' => '',
            'block_number' => '', 'cleaner_directions' => '',
            'gmaps_code' => '', 'gmaps_resolved' => '',
            'map_x' => null, 'map_y' => null,
            'active' => $d['active'], 'under_maintenance' => 0,
            'cover_image' => null,
        ];
        $id = newId();
        $cols = implode(',', array_keys($data));
        $ph = implode(',', array_fill(0, count($data), '?'));
        q("INSERT INTO apartments (id, $cols) VALUES (?, $ph)", array_merge([$id], array_values($data)));
        logActivity('create', 'apartment', $id, $data['name']);
        $created++;
    }
    flash("$created appartamenti demo ripristinati" . ($skipped ? " ($skipped già presenti)" : '') . ". NB: le prenotazioni demo non sono recuperabili (cliente + date erano dati di prova).");
    redirect('/admin/appartamenti.php');
}

$title = 'Ripristina appartamenti demo';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
?>
<h1 class="font-display text-2xl font-bold mb-3">Ripristina appartamenti demo</h1>
<div class="card p-4 mb-4 bg-amber-50 border border-amber-200 text-sm">
  <p><b>⚠ Solo gli appartamenti vengono ripristinati.</b> Le prenotazioni demo che erano collegate non sono recuperabili: erano già eliminate dal DB (cliente/date erano dati di prova, non li abbiamo da nessuna parte).</p>
</div>
<p class="text-ink-600 mb-3">Verranno ricreati:</p>
<ul class="list-disc list-inside mb-4 text-sm">
<?php foreach ($demos as $d):
    $ex = row('SELECT id FROM apartments WHERE name = ?', [$d['name']]);
    echo '<li><b>' . htmlspecialchars($d['name']) . '</b> · ' . htmlspecialchars($d['city']) . ' · €' . $d['base_price'] . '/notte';
    if ($ex) echo ' <span class="text-ink-400">(già presente, sarà saltato)</span>';
    echo '</li>';
endforeach; ?>
</ul>
<form method="post" class="flex gap-2">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
  <input type="hidden" name="confirm" value="yes">
  <button type="submit" class="btn-primary">↩ Ripristina demo</button>
  <a href="/admin/appartamenti.php" class="btn-outline">Annulla</a>
</form>
<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
