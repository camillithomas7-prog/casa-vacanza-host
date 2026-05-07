<?php
require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $name = cfg('auth.session_name') ?: 'cv_session';
    session_name($name);
    session_set_cookie_params([
        'lifetime' => cfg('auth.session_ttl'),
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function currentUser(): ?array {
    startSession();
    if (empty($_SESSION['user_id'])) return null;
    return row('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
}

function loginUser(string $email, string $password): ?array {
    $u = row('SELECT * FROM users WHERE email = ?', [$email]);
    if (!$u || !password_verify($password, $u['password'])) return null;
    startSession();
    $_SESSION['user_id'] = $u['id'];
    logActivity('login', 'user', $u['id'], null, $u['id']);
    return $u;
}

function logoutUser(): void {
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function requireAdmin(): array {
    $u = currentUser();
    if (!$u) { header('Location: /admin/login.php'); exit; }
    return $u;
}

function logActivity(string $action, ?string $entity = null, ?string $entityId = null, ?string $details = null, ?string $userId = null): void {
    $u = $userId ?: ($_SESSION['user_id'] ?? null);
    q('INSERT INTO activity_logs (id, user_id, action, entity, entity_id, details, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [newId(), $u, $action, $entity, $entityId, $details]);
}

function ensureAdminUser(): void {
    $email = cfg('admin_default.email');
    $existing = row('SELECT id FROM users WHERE email = ?', [$email]);
    if ($existing) return;
    q('INSERT INTO users (id, email, password, name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
        [newId(), $email, password_hash(cfg('admin_default.password'), PASSWORD_DEFAULT), 'Admin', 'admin']);
}

function csrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrfCheck(?string $token): void {
    startSession();
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403); die('CSRF token non valido');
    }
}
