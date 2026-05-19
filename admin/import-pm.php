<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();
ensureColumns();

set_time_limit(300);
ini_set('memory_limit', '256M');

$DATA_FILE = __DIR__ . '/pm_import_data.json';
if (!file_exists($DATA_FILE)) {
    http_response_code(500);
    die('File pm_import_data.json mancante in /admin/.');
}

$raw = json_decode(file_get_contents($DATA_FILE), true);
if (!is_array($raw)) die('JSON non valido.');

// Filtra records validi (con titolo)
$items = array_values(array_filter($raw, fn($p) => !empty($p['title'])));
$TOTAL = count($items);

$action = $_GET['action'] ?? 'home';

// ───────────────────────────────────────────── PROCESS SINGLE ITEM
if ($action === 'process') {
    $idx = (int)($_GET['idx'] ?? 0);
    if ($idx >= $TOTAL) {
        header('Location: ?action=done');
        exit;
    }
    $p = $items[$idx];

    // dedup: skip se già esiste un appartamento con stesso slug
    $slug = slugify($p['title']);
    $exists = row('SELECT id FROM apartments WHERE slug = ?', [$slug]);

    $log = [];
    $log[] = "[".($idx+1)."/$TOTAL] {$p['title']}";

    if ($exists) {
        $log[] = "✓ Già presente (skip): {$exists['id']}";
    } else {
        // Calcola prezzi
        $price = (float)($p['price_eur'] ?? 0);
        $period = $p['price_period'] ?? 'weekly';
        $weekly = null; $monthly = null; $base = 0;
        if ($period === 'weekly') {
            $weekly = $price;
            $base = round($price / 7, 2);
        } elseif ($period === 'monthly') {
            $monthly = $price;
            $base = round($price / 30, 2);
        } else { // daily
            $base = $price;
            $weekly = round($price * 7, 2);
        }

        // Scarica foto (prima = cover)
        $uploadsDir = __DIR__ . '/../uploads/photos';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $photoUrls = $p['photo_urls'] ?? [];
        $localPaths = [];
        foreach ($photoUrls as $i => $url) {
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) $ext = 'jpg';
            $fname = 'pm_' . substr(md5($url), 0, 12) . '_' . $i . '.' . $ext;
            $dest = $uploadsDir . '/' . $fname;
            if (!file_exists($dest)) {
                $ctx = stream_context_create(['http' => ['timeout' => 20, 'header' => "User-Agent: Mozilla/5.0\r\n"]]);
                $bin = @file_get_contents($url, false, $ctx);
                if ($bin && strlen($bin) > 1000) {
                    file_put_contents($dest, $bin);
                } else {
                    $log[] = "  ✗ Foto non scaricata: $url";
                    continue;
                }
            }
            $localPaths[] = '/uploads/photos/' . $fname;
        }
        $log[] = "  ↓ Foto scaricate: " . count($localPaths) . "/" . count($photoUrls);

        $cover = $localPaths[0] ?? null;
        $bedrooms = (int)($p['bedrooms'] ?? 1);
        $bathrooms = (int)($p['bathrooms'] ?? 1);
        $beds = max(1, $bedrooms);
        $guests = max(2, $bedrooms * 2);

        $data = [
            'name' => $p['title'],
            'slug' => $slug,
            'description' => $p['description'] ?? '',
            'address' => '',
            'city' => $p['city'] ?? '',
            'country' => 'Egitto',
            'guests' => $guests,
            'bedrooms' => $bedrooms,
            'bathrooms' => $bathrooms,
            'beds' => $beds,
            'size_sqm' => $p['size_sqm'] !== null ? (int)$p['size_sqm'] : null,
            'amenities' => json_encode([]),
            'rules' => '',
            'check_in_time' => '15:00',
            'check_out_time' => '11:00',
            'base_price' => $base,
            'weekly_price' => $weekly,
            'biweekly_price' => null,
            'triweekly_price' => null,
            'monthly_price' => $monthly,
            'weekend_price' => null,
            'cleaning_fee' => 35,
            'security_deposit' => 0,
            'city_tax' => 2,
            'city_tax_max_nights' => 5,
            'long_stay_discount_7' => 5,
            'long_stay_discount_14' => 10,
            'long_stay_discount_30' => 20,
            'manager_commission_pct' => 20,
            'owner_name' => '',
            'block_number' => '',
            'cleaner_directions' => '',
            'gmaps_code' => '',
            'gmaps_resolved' => '',
            'map_x' => null,
            'map_y' => null,
            'active' => 1,
            'under_maintenance' => 0,
            'cover_image' => $cover,
        ];

        $newAptId = newId();
        $cols = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        q("INSERT INTO apartments (id, $cols) VALUES (?, $placeholders)", array_merge([$newAptId], array_values($data)));

        foreach ($localPaths as $pos => $path) {
            q('INSERT INTO photos (id, apartment_id, url, position) VALUES (?, ?, ?, ?)',
                [newId(), $newAptId, $path, $pos + 1]);
        }
        logActivity('create', 'apartment', $newAptId, $data['name']);
        $log[] = "  ✓ Creato apartment_id={$newAptId}, " . count($localPaths) . " foto in galleria";
    }

    // Salva log in session per visualizzazione
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['pm_import_log'] = ($_SESSION['pm_import_log'] ?? '') . implode("\n", $log) . "\n\n";

    $nextIdx = $idx + 1;
    header("Location: ?action=process&idx=$nextIdx&t=" . time());
    exit;
}

// ───────────────────────────────────────────── DONE
if ($action === 'done') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $logTxt = $_SESSION['pm_import_log'] ?? '(nessun log)';
    $aptCount = (int)val('SELECT COUNT(*) FROM apartments');
    $title = 'Import PM completato';
    require __DIR__ . '/../partials/head.php';
    require __DIR__ . '/../partials/admin-shell-top.php';
    ?>
    <h1 class="font-display text-2xl font-bold mb-3">✓ Import completato</h1>
    <p class="mb-4 text-ink-600">Totale appartamenti in DB: <b><?= $aptCount ?></b></p>
    <a href="/admin/appartamenti.php" class="btn-primary mb-4 inline-flex">Vai agli appartamenti</a>
    <pre class="bg-ink-100 dark:bg-ink-800 p-4 rounded text-xs overflow-auto max-h-[600px] whitespace-pre-wrap"><?= htmlspecialchars($logTxt) ?></pre>
    <?php
    unset($_SESSION['pm_import_log']);
    require __DIR__ . '/../partials/admin-shell-bottom.php';
    exit;
}

// ───────────────────────────────────────────── HOME (lista + bottone)
$title = 'Import da PM Servizi Immobiliari';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/admin-shell-top.php';
$existingSlugs = array_column(rows('SELECT slug FROM apartments'), 'slug');
?>
<h1 class="font-display text-2xl font-bold mb-3">Import appartamenti da PM Servizi Immobiliari</h1>
<p class="text-ink-600 mb-4">Trovati <b><?= $TOTAL ?></b> appartamenti pronti per l'import. Le foto verranno scaricate dal CDN originale e salvate localmente.</p>

<div class="card p-4 mb-5 bg-amber-50 border border-amber-200">
  <p class="text-sm"><b>Come funziona:</b> cliccando "Avvia import" lo script processa 1 appartamento alla volta (per evitare timeout PHP), reindirizzando automaticamente al successivo. Le foto vengono salvate in <code>/uploads/photos/</code>. Gli appartamenti con slug già esistente vengono saltati.</p>
</div>

<a href="?action=process&idx=0" class="btn-primary mb-5 inline-flex">▶ Avvia import (<?= $TOTAL ?> appartamenti)</a>

<div class="card p-4">
  <h3 class="font-display font-bold mb-3">Anteprima</h3>
  <table class="w-full text-sm">
    <thead><tr class="text-left text-ink-500"><th class="py-1">#</th><th>Titolo</th><th>Zona</th><th>Camere</th><th>Foto</th><th>Prezzo</th><th>Stato</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i => $p):
      $slug = slugify($p['title']);
      $exists = in_array($slug, $existingSlugs);
    ?>
      <tr class="border-t border-ink-100 dark:border-ink-800">
        <td class="py-1"><?= $i+1 ?></td>
        <td><?= htmlspecialchars($p['title']) ?></td>
        <td><?= htmlspecialchars($p['city'] ?? '') ?></td>
        <td><?= (int)($p['bedrooms'] ?? 0) ?></td>
        <td><?= count($p['photo_urls'] ?? []) ?></td>
        <td>€<?= (int)($p['price_eur'] ?? 0) ?>/<?= ($p['price_period'] ?? 'weekly') === 'weekly' ? 'sett' : (($p['price_period'] ?? '') === 'daily' ? 'giorno' : 'mese') ?></td>
        <td><?= $exists ? '<span class="text-emerald-600">✓ già presente</span>' : '<span class="text-amber-600">⏳ da importare</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../partials/admin-shell-bottom.php';
