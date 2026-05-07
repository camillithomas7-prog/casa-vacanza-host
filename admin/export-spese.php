<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

$year = (int)($_GET['year'] ?? date('Y'));
$apt = $_GET['apt'] ?? 'all';
$where = ['date >= ? AND date < ?']; $params = ["$year-01-01", ($year + 1) . "-01-01"];
if ($apt !== 'all') { $where[] = 'apartment_id = ?'; $params[] = $apt; }
$rows = rows("SELECT e.date, e.category, COALESCE(a.name, '') AS apartment, e.amount, COALESCE(e.description, '') AS description FROM expenses e LEFT JOIN apartments a ON e.apartment_id = a.id WHERE " . implode(' AND ', $where) . " ORDER BY date DESC", $params);

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=spese-$year.csv");
$out = fopen('php://output', 'w');
fputcsv($out, ['Data','Categoria','Appartamento','Importo','Descrizione']);
foreach ($rows as $r) fputcsv($out, [$r['date'], $r['category'], $r['apartment'], $r['amount'], $r['description']]);
fclose($out);
