<?php
require_once __DIR__ . '/db.php';

/**
 * Crea (o aggiorna) la sessione di pulizia per una prenotazione, allineata al check-out.
 * Idempotente: se esiste già una sessione per (booking_id), la riallinea se necessario.
 * Se la prenotazione è cancelled/rejected, la sessione associata viene eliminata.
 */
function ensureCleaningSession(string $bookingId): ?string {
    $b = row('SELECT id, apartment_id, check_out, status FROM bookings WHERE id = ?', [$bookingId]);
    if (!$b) return null;
    $existing = row('SELECT id, status FROM cleaning_sessions WHERE booking_id = ?', [$bookingId]);
    // Booking annullato → cancello la sessione (se non già completata)
    if (in_array($b['status'], ['cancelled', 'rejected'], true)) {
        if ($existing && $existing['status'] !== 'done') {
            q('DELETE FROM cleaning_sessions WHERE id = ?', [$existing['id']]);
        }
        return null;
    }
    if ($existing) {
        // Aggiorna la data se cambiata (es. modifica check-out)
        q('UPDATE cleaning_sessions SET apartment_id = ?, scheduled_date = ? WHERE id = ?',
            [$b['apartment_id'], $b['check_out'], $existing['id']]);
        return $existing['id'];
    }
    // Crea nuova sessione + items dal catalogo attivo
    $sid = newId();
    q('INSERT INTO cleaning_sessions (id, booking_id, apartment_id, scheduled_date, status) VALUES (?, ?, ?, ?, ?)',
        [$sid, $bookingId, $b['apartment_id'], $b['check_out'], 'pending']);
    $tasks = rows('SELECT id, label FROM cleaning_tasks WHERE active = 1 ORDER BY position ASC, label ASC');
    $pos = 1;
    foreach ($tasks as $t) {
        q('INSERT INTO cleaning_session_items (id, session_id, task_id, label_snapshot, position) VALUES (?, ?, ?, ?, ?)',
            [newId(), $sid, $t['id'], $t['label'], $pos++]);
    }
    return $sid;
}

/**
 * Aggiunge all'attuale sessione un item dal catalogo (se la sessione esiste).
 * Utile dopo che l'admin ha aggiunto un nuovo task al catalogo: viene propagato
 * solo alle sessioni "pending" (non a quelle già completate).
 */
function propagateTaskToOpenSessions(string $taskId): int {
    $task = row('SELECT id, label FROM cleaning_tasks WHERE id = ?', [$taskId]);
    if (!$task) return 0;
    $openSessions = rows("SELECT id FROM cleaning_sessions WHERE status = 'pending'");
    $count = 0;
    foreach ($openSessions as $s) {
        $existsItem = (int)val('SELECT COUNT(*) FROM cleaning_session_items WHERE session_id = ? AND task_id = ?', [$s['id'], $task['id']]);
        if ($existsItem) continue;
        $pos = (int)val('SELECT COALESCE(MAX(position), 0) + 1 FROM cleaning_session_items WHERE session_id = ?', [$s['id']]);
        q('INSERT INTO cleaning_session_items (id, session_id, task_id, label_snapshot, position) VALUES (?, ?, ?, ?, ?)',
            [newId(), $s['id'], $task['id'], $task['label'], $pos]);
        $count++;
    }
    return $count;
}

function cleanerToken(): string {
    return (string)val('SELECT setting_value FROM settings WHERE setting_key = ?', ['cleaner_link_token']);
}

function cleanerLinkUrl(): string {
    $token = cleanerToken();
    if (!$token) return '';
    $base = rtrim(cfg('site.url') ?: ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
    return $base . '/pulizie.php?t=' . $token;
}

/**
 * Invia un push alle subscription "cleaner".
 * Ritorna ['sent' => N, 'errors' => N].
 */
function sendPushToCleaners(array $payload): array {
    require_once __DIR__ . '/notify.php';
    require_once __DIR__ . '/webpush.php';
    ensurePushSchema();
    try {
        $keys = vapidKeys();
    } catch (Throwable $e) { return ['sent'=>0,'errors'=>1]; }
    $subject = setting('push_subject') ?: ('mailto:' . (cfg('site.email') ?: 'admin@casavacanza.it'));
    try {
        $wp = new WebPush($keys['public'], $keys['private'], $subject);
    } catch (Throwable $e) { return ['sent'=>0,'errors'=>1]; }
    $subs = rows("SELECT * FROM push_subscriptions WHERE role = 'cleaner'");
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $sent = 0; $errors = 0;
    foreach ($subs as $sub) {
        try {
            $res = $wp->send($sub, $payloadJson);
            if ($res['ok']) $sent++;
            else {
                $errors++;
                if ($res['status'] === 404 || $res['status'] === 410) {
                    q('DELETE FROM push_subscriptions WHERE id = ?', [$sub['id']]);
                }
            }
        } catch (Throwable $e) { $errors++; }
    }
    return ['sent' => $sent, 'errors' => $errors];
}

/**
 * Cerca pulizie con check-out domani (status != done) e ancora senza reminder,
 * invia un push a tutti i "cleaner" e marca la sessione come notificata.
 * Idempotente: non manda più di una volta per sessione.
 */
function sendCleaningReminders(): int {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    try {
        $sessions = rows("SELECT s.*, a.name AS apartment_name, a.address AS apartment_address
                          FROM cleaning_sessions s
                          JOIN apartments a ON a.id = s.apartment_id
                          WHERE s.scheduled_date = ?
                            AND s.status != 'done'
                            AND s.reminder_sent_at IS NULL", [$tomorrow]);
    } catch (Throwable $e) { return 0; }
    if (!$sessions) return 0;

    $count = count($sessions);
    $link = cleanerLinkUrl();
    if ($count === 1) {
        $s = $sessions[0];
        $payload = [
            'type'  => 'cleaning_reminder',
            'title' => 'Pulizia domani: ' . $s['apartment_name'],
            'body'  => 'Check-out ' . date('d/m', strtotime($s['scheduled_date'])) . ($s['apartment_address'] ? ' · ' . $s['apartment_address'] : ''),
            'link'  => $link,
        ];
    } else {
        $payload = [
            'type'  => 'cleaning_reminder',
            'title' => 'Domani ' . $count . ' pulizie',
            'body'  => 'Hai ' . $count . ' appartamenti da pulire. Apri la lista per i dettagli.',
            'link'  => $link,
        ];
    }
    try { sendPushToCleaners($payload); } catch (Throwable $e) {}
    foreach ($sessions as $s) {
        try { q('UPDATE cleaning_sessions SET reminder_sent_at = NOW() WHERE id = ?', [$s['id']]); } catch (Throwable $e) {}
    }
    return $count;
}


// === Google Maps navigation helpers =====================================

/**
 * Risolve un Plus Code o URL Google Maps in coordinate "lat,lng".
 * Se è un short link (maps.app.goo.gl / goo.gl/maps), segue i redirect
 * ed estrae @lat,lng dall URL finale. Ritorna null se non risolvibile.
 */
function resolveGmapsCoords(string $codeOrUrl): ?string {
    $s = trim($codeOrUrl);
    if ($s === "") return null;

    // Cerca tutti i pattern noti di lat,lng in una stringa
    $extract = function(string $text): ?string {
        // @lat,lng (es: /place/.../@27.86,34.32,17z)
        if (preg_match("#@(-?\d{1,3}\.\d{4,}),(-?\d{1,3}\.\d{4,})#", $text, $m)) return $m[1] . "," . $m[2];
        // /search/lat,+?lng (es: redirect dei maps.app.goo.gl)
        if (preg_match("#/search/(-?\d{1,3}\.\d{4,}),\+?(-?\d{1,3}\.\d{4,})#", $text, $m)) return $m[1] . "," . $m[2];
        // /place/lat,+?lng
        if (preg_match("#/place/(-?\d{1,3}\.\d{4,}),\+?(-?\d{1,3}\.\d{4,})#", $text, $m)) return $m[1] . "," . $m[2];
        // !3dLAT!4dLNG (pattern interno nelle pagine HTML)
        if (preg_match("/!3d(-?\d{1,3}\.\d{4,})!4d(-?\d{1,3}\.\d{4,})/", $text, $m)) return $m[1] . "," . $m[2];
        // ?q=lat,lng / ?ll=lat,lng / &destination=lat,lng (anche URL-encoded virgola)
        if (preg_match("#[?&](?:q|ll|center|destination)=(-?\d{1,3}\.\d{4,})(?:,|%2C|%2c)\+?(-?\d{1,3}\.\d{4,})#", $text, $m)) return $m[1] . "," . $m[2];
        return null;
    };

    // 1. Stringa già nel formato "lat,lng"
    if (preg_match("/^(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)$/", $s, $m)) {
        return $m[1] . "," . $m[2];
    }

    // 2. URL Google Maps "lungo": prova a estrarre direttamente
    if ($c = $extract($s)) return $c;

    // 3. Short link: prima leggi la Location header del 302, poi fallback al body
    if (preg_match("#^https?://(maps\.app\.goo\.gl|goo\.gl/maps|g\.co/kgs|maps\.google\.com)/#i", $s) && function_exists("curl_init")) {
        // Tentativo 1: solo la Location del 302 (la rotta diretta più affidabile)
        $ch = curl_init($s);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT      => "Mozilla/5.0 (compatible; CasaVacanza/1.0)",
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $headers = @curl_exec($ch);
        if (is_string($headers) && preg_match("#^location:\s*(\S+)#im", $headers, $loc)) {
            $locUrl = trim($loc[1]);
            if ($c = $extract($locUrl)) return $c;
        }

        // Tentativo 2: segui tutti i redirect e cerca nel body
        $ch = curl_init($s);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 6,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => "Mozilla/5.0 (compatible; CasaVacanza/1.0)",
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = @curl_exec($ch);
        $final = (string)@curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        if ($final && ($c = $extract($final))) return $c;
        if (is_string($body) && ($c = $extract($body))) return $c;
    }

    return null;
}

/**
 * Restituisce l URL ottimale di Google Maps per la navigazione a piedi
 * verso un appartamento. Preferisce le coordinate risolte (parte direttamente
 * con il navigatore); altrimenti apre il link/Plus Code come destinazione.
 */
function gmapsNavUrl(string $code, ?string $resolved = null): string {
    $code = trim($code);
    $resolved = trim((string)$resolved);
    if ($resolved !== "" && preg_match("/^-?\d+\.\d+,-?\d+\.\d+$/", $resolved)) {
        return "https://www.google.com/maps/dir/?api=1&destination=" . rawurlencode($resolved) . "&travelmode=walking";
    }
    if ($code === "") return "";
    if (preg_match("#^https?://#i", $code)) {
        // Link diretto: apre Google Maps sul pin, lutente tocca "Indicazioni"
        return $code;
    }
    // Plus Code o indirizzo testuale: parte direttamente con la navigazione
    return "https://www.google.com/maps/dir/?api=1&destination=" . rawurlencode($code) . "&travelmode=walking";
}

