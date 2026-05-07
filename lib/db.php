<?php
function cfg(?string $key = null) {
    static $config = null;
    if ($config === null) {
        $path = __DIR__ . '/../config.php';
        if (!file_exists($path)) { http_response_code(500); die('Config mancante: copia config.sample.php in config.php'); }
        $config = require $path;
    }
    if ($key === null) return $config;
    $parts = explode('.', $key);
    $v = $config;
    foreach ($parts as $p) { if (!isset($v[$p])) return null; $v = $v[$p]; }
    return $v;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = cfg('db');
    $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        die('Errore connessione DB: ' . htmlspecialchars($e->getMessage()));
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function rows(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function row(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r ?: null; }
function val(string $sql, array $params = []) { $r = q($sql, $params)->fetch(PDO::FETCH_NUM); return $r ? $r[0] : null; }

function newId(): string {
    return bin2hex(random_bytes(12));
}

function tableExists(string $name): bool {
    try { q("SELECT 1 FROM `$name` LIMIT 1"); return true; } catch (Throwable $e) { return false; }
}
