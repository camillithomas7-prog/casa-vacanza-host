<?php
// Endpoint diagnostico temporaneo. Apri /admin/debug-apt.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

header('Content-Type: text/plain; charset=utf-8');

echo "== APPARTAMENTI NEL DB ==\n\n";
$apts = rows('SELECT id, name, slug, city, base_price, guests, bedrooms, beds, active, created_at FROM apartments ORDER BY created_at DESC');
foreach ($apts as $a) {
    echo "ID: {$a['id']}\n";
    echo "  name='{$a['name']}'  slug='{$a['slug']}'\n";
    echo "  city='{$a['city']}'  base_price={$a['base_price']}  guests={$a['guests']}  active={$a['active']}\n";
    echo "  edit URL: /admin/appartamento-edit.php?id={$a['id']}\n\n";
}

echo "\n== TEST QUERY (simula apertura modifica) ==\n\n";
if (!empty($_GET['id'])) {
    $id = $_GET['id'];
    echo "ID richiesto: '$id'\n";
    $row = row('SELECT * FROM apartments WHERE id = ?', [$id]);
    if ($row) {
        echo "TROVATO. Dati grezzi:\n";
        foreach ($row as $k => $v) {
            echo "  $k = " . (is_null($v) ? 'NULL' : "'$v'") . "\n";
        }
    } else {
        echo "NESSUNA RIGA TROVATA per id='$id'\n";
    }
}
