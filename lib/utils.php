<?php
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function setting(string $key, ?string $default = null): ?string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (rows('SELECT setting_key, setting_value FROM settings') as $s) {
                $cache[$s['setting_key']] = $s['setting_value'];
            }
        } catch (Throwable $e) {}
    }
    return $cache[$key] ?? $default;
}

function fmtMoney(float $amount, ?string $cur = null): string {
    $cur = $cur ?: cfg('site.currency') ?: 'EUR';
    $f = new NumberFormatter('it_IT', NumberFormatter::CURRENCY);
    return $f->formatCurrency($amount, $cur);
}

function fmtDate($d): string {
    if (!$d) return '—';
    $ts = is_numeric($d) ? (int)$d : strtotime((string)$d);
    if (!$ts) return '—';
    return strftime('%d %b %Y', $ts);
}

function fmtDateShort($d): string {
    if (!$d) return '';
    $ts = is_numeric($d) ? (int)$d : strtotime((string)$d);
    return date('d/m/Y', $ts);
}

function fmtDateISO($d): string {
    if (!$d) return '';
    $ts = is_numeric($d) ? (int)$d : strtotime((string)$d);
    return date('Y-m-d', $ts);
}

function fmtDateTime($d): string {
    if (!$d) return '—';
    $ts = is_numeric($d) ? (int)$d : strtotime((string)$d);
    return date('d/m/Y H:i', $ts);
}

function nightsBetween(string $a, string $b): int {
    $ta = strtotime($a); $tb = strtotime($b);
    if (!$ta || !$tb) return 0;
    return max(0, (int)round(($tb - $ta) / 86400));
}

function daysRange(string $from, string $to): array {
    $out = [];
    $a = strtotime($from); $b = strtotime($to);
    while ($a < $b) { $out[] = $a; $a += 86400; }
    return $out;
}

function bookingCode(int $seq): string {
    return 'PM-' . date('Y') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
}

function slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = transliterator_transliterate('Any-Latin; Latin-ASCII;', $s) ?: $s;
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function parseAmenities($raw): array {
    if (!$raw) return [];
    $v = json_decode($raw, true);
    return is_array($v) ? $v : [];
}

function rangesOverlap(string $a1, string $a2, string $b1, string $b2): bool {
    return strtotime($a1) < strtotime($b2) && strtotime($b1) < strtotime($a2);
}

function flash(?string $msg = null, string $type = 'info') {
    startSession();
    if ($msg !== null) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url): void { header("Location: $url"); exit; }

function url(string $path = ''): string {
    return rtrim(cfg('site.url') ?: '', '/') . $path;
}

function asset(string $path): string {
    return $path . '?v=' . filemtime(__DIR__ . '/../' . ltrim($path, '/')) ?: '1';
}

if (!class_exists('NumberFormatter')) {
    class NumberFormatter {
        const CURRENCY = 1;
        public function __construct($l, $s) {}
        public function formatCurrency($v, $c) {
            $sym = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'][$c] ?? $c;
            return $sym . ' ' . number_format($v, 2, ',', '.');
        }
    }
}

if (!function_exists('strftime')) {
    // PHP 8.1+ ha rimosso strftime
    function strftime($fmt, $ts) {
        $months = ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
        if ($fmt === '%d %b %Y') return date('d', $ts) . ' ' . $months[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
        return date('Y-m-d', $ts);
    }
}

if (!function_exists('transliterator_transliterate')) {
    function transliterator_transliterate($r, $s) {
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    }
}

date_default_timezone_set(cfg('site.timezone') ?: 'Europe/Rome');
