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
