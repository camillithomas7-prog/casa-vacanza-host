<?php
require_once __DIR__ . '/i18n.php';

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/**
 * Feature flag per le sezioni del sito gestite da Patrizia.
 * - apartments: sempre ON (è il core business)
 * - rentals / excursions / transfer: di default OFF (Patrizia li attiva
 *   manualmente quando inizia a offrire quel servizio).
 */
function featureEnabled(string $key): bool {
    if ($key === 'apartments') return true;
    $defaults = ['rentals' => false, 'excursions' => false, 'transfer' => false];
    $raw = setting('feature_' . $key);
    if ($raw === null) return $defaults[$key] ?? false;
    return $raw === '1';
}

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

function isWeeklyOnly(): bool {
    return setting('weekly_only_mode', '1') === '1';
}

function securityDepositPct(): float {
    return (float) setting('security_deposit_pct', '20');
}

function weeklyPriceOf(array $apt): float {
    if (!empty($apt['weekly_price'])) return (float)$apt['weekly_price'];
    return (float)$apt['base_price'] * 7;
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

function isVideoUrl(?string $url): bool {
    if (!$url) return false;
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'mov', 'm4v']);
}

/**
 * Lista di paesi in ordine alfabetico nella lingua specificata.
 * Restituisce array di [code => 'IT', name => 'Italia'].
 * I primi sono i paesi più frequenti per il mercato Sharm (italiani, tedeschi, russi…).
 */
function countryList(string $lang = 'it'): array {
    $codes = [
        'IT','DE','RU','UA','GB','FR','ES','PL','CZ','SK','RO','BG','HU','AT','CH','BE','NL','SE','NO','DK','FI','IE','PT','GR','EG',
        'US','CA','MX','AR','BR','CL','CO','PE','VE','UY','PY','EC','BO','CR','PA','DO','CU',
        'AU','NZ','JP','CN','KR','IN','TH','VN','ID','MY','PH','SG','HK','TW','TR','IL','SA','AE','QA','KW','BH','OM','JO','LB','MA','TN','DZ','LY','SD','ET','KE','TZ','ZA','NG','GH','SN',
        'AL','AM','AZ','BA','BY','EE','GE','HR','IS','LT','LU','LV','MD','ME','MK','MT','RS','SI','XK',
        'IS','LI','SM','VA','AD','MC',
        'AF','BD','LK','PK','NP','MM','LA','KH','MN','UZ','KZ','KG','TM','TJ','IR','IQ','SY','YE',
        'AO','BI','BJ','BF','BW','CF','CD','CG','CI','CM','CV','DJ','ER','GA','GM','GN','GQ','GW','LR','LS','MG','ML','MR','MU','MW','MZ','NA','NE','RW','SC','SL','SO','SS','SZ','TD','TG','UG','ZM','ZW',
        'BS','BB','BZ','GD','GT','GY','HN','HT','JM','LC','NI','SR','SV','TT','VC',
        'FJ','PG','SB','TO','VU','WS',
    ];
    // Dedup preservando ordine (alcuni codici tipo IS sono comparsi due volte)
    $codes = array_values(array_unique($codes));
    $list = [];
    foreach ($codes as $code) {
        $name = class_exists('Locale') ? \Locale::getDisplayRegion('-' . $code, $lang) : $code;
        if (!$name || $name === $code) continue;
        $list[] = ['code' => $code, 'name' => $name];
    }
    // Ordina alfabeticamente
    usort($list, fn($a, $b) => strcoll($a['name'], $b['name']));
    return $list;
}

/**
 * Converte un codice ISO paese nel suo nome localizzato.
 */
function countryName(?string $code, string $lang = 'it'): string {
    if (!$code) return '';
    if (strlen($code) !== 2 || !class_exists('Locale')) return $code;
    return \Locale::getDisplayRegion('-' . strtoupper($code), $lang) ?: $code;
}

/**
 * Mappa codice ISO paese → prefisso telefonico (E.164).
 */
function countryDialCodes(): array {
    return [
        'IT'=>'39','DE'=>'49','RU'=>'7','UA'=>'380','GB'=>'44','FR'=>'33','ES'=>'34','PL'=>'48','CZ'=>'420','SK'=>'421','RO'=>'40','BG'=>'359','HU'=>'36','AT'=>'43','CH'=>'41','BE'=>'32','NL'=>'31','SE'=>'46','NO'=>'47','DK'=>'45','FI'=>'358','IE'=>'353','PT'=>'351','GR'=>'30','EG'=>'20',
        'US'=>'1','CA'=>'1','MX'=>'52','AR'=>'54','BR'=>'55','CL'=>'56','CO'=>'57','PE'=>'51','VE'=>'58','UY'=>'598','PY'=>'595','EC'=>'593','BO'=>'591','CR'=>'506','PA'=>'507','DO'=>'1','CU'=>'53',
        'AU'=>'61','NZ'=>'64','JP'=>'81','CN'=>'86','KR'=>'82','IN'=>'91','TH'=>'66','VN'=>'84','ID'=>'62','MY'=>'60','PH'=>'63','SG'=>'65','HK'=>'852','TW'=>'886','TR'=>'90','IL'=>'972','SA'=>'966','AE'=>'971','QA'=>'974','KW'=>'965','BH'=>'973','OM'=>'968','JO'=>'962','LB'=>'961','MA'=>'212','TN'=>'216','DZ'=>'213','LY'=>'218','SD'=>'249','ET'=>'251','KE'=>'254','TZ'=>'255','ZA'=>'27','NG'=>'234','GH'=>'233','SN'=>'221',
        'AL'=>'355','AM'=>'374','AZ'=>'994','BA'=>'387','BY'=>'375','EE'=>'372','GE'=>'995','HR'=>'385','IS'=>'354','LT'=>'370','LU'=>'352','LV'=>'371','MD'=>'373','ME'=>'382','MK'=>'389','MT'=>'356','RS'=>'381','SI'=>'386','XK'=>'383',
        'LI'=>'423','SM'=>'378','VA'=>'379','AD'=>'376','MC'=>'377',
        'AF'=>'93','BD'=>'880','LK'=>'94','PK'=>'92','NP'=>'977','MM'=>'95','LA'=>'856','KH'=>'855','MN'=>'976','UZ'=>'998','KZ'=>'7','KG'=>'996','TM'=>'993','TJ'=>'992','IR'=>'98','IQ'=>'964','SY'=>'963','YE'=>'967',
        'AO'=>'244','BI'=>'257','BJ'=>'229','BF'=>'226','BW'=>'267','CF'=>'236','CD'=>'243','CG'=>'242','CI'=>'225','CM'=>'237','CV'=>'238','DJ'=>'253','ER'=>'291','GA'=>'241','GM'=>'220','GN'=>'224','GQ'=>'240','GW'=>'245','LR'=>'231','LS'=>'266','MG'=>'261','ML'=>'223','MR'=>'222','MU'=>'230','MW'=>'265','MZ'=>'258','NA'=>'264','NE'=>'227','RW'=>'250','SC'=>'248','SL'=>'232','SO'=>'252','SS'=>'211','SZ'=>'268','TD'=>'235','TG'=>'228','UG'=>'256','ZM'=>'260','ZW'=>'263',
        'BS'=>'1','BB'=>'1','BZ'=>'501','GD'=>'1','GT'=>'502','GY'=>'592','HN'=>'504','HT'=>'509','JM'=>'1','LC'=>'1','NI'=>'505','SR'=>'597','SV'=>'503','TT'=>'1','VC'=>'1',
        'FJ'=>'679','PG'=>'675','SB'=>'677','TO'=>'676','VU'=>'678','WS'=>'685',
    ];
}

/**
 * Lista paesi con dial code, per il prefisso telefonico.
 */
function phoneCountryList(string $lang = 'it'): array {
    $dial = countryDialCodes();
    $list = [];
    foreach (countryList($lang) as $c) {
        if (!isset($dial[$c['code']])) continue;
        $list[] = ['code' => $c['code'], 'name' => $c['name'], 'dial' => $dial[$c['code']]];
    }
    return $list;
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
