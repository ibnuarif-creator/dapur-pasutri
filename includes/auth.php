<?php
declare(strict_types=1);

function auth_bootstrap(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Shared hosting deletes sessions after php.ini's gc_maxlifetime (cPanel: 1440s = 24 min),
    // regardless of SESSION_IDLE_TIMEOUT — an admin filling a long form then gets logged out
    // and the save is rejected. Keep our sessions in our own folder with our own lifetime.
    $sessionDir = __DIR__ . '/sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    ini_set('session.gc_maxlifetime', (string) (SESSION_IDLE_TIMEOUT + 600));
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');
    session_name('PORTALSID');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => $isHttps,
    ]);
    session_start();

    // A logged-in session that has been idle too long is dropped.
    if (!empty($_SESSION['user']) && !empty($_SESSION['last_activity'])
        && time() - (int) $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('error', 'Sesi berakhir karena tidak aktif. Silakan masuk lagi.');
    }
    $_SESSION['last_activity'] = time();
}

/* ---------------- Account (single admin, stored in credentials.json) ---------------- */

function has_account(): bool
{
    return is_file(CREDENTIALS_FILE);
}

function read_credentials(): ?array
{
    $data = read_json(CREDENTIALS_FILE);
    return !empty($data['username']) && !empty($data['password_hash']) ? $data : null;
}

function create_account(string $username, string $password): bool
{
    return write_json(CREDENTIALS_FILE, [
        'username'      => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'updated_at'    => date('c'),
    ], 0600);
}

/** Username is case-insensitive; password is not. */
function verify_credentials(string $username, string $password): bool
{
    $cred = read_credentials();
    if ($cred === null) {
        return false;
    }
    $userOk = hash_equals(strtolower($cred['username']), strtolower($username));
    // Always run password_verify so a wrong username takes as long as a wrong password.
    $passOk = password_verify($password, $cred['password_hash']);
    return $userOk && $passOk;
}

function change_password(string $current, string $new): bool
{
    $cred = read_credentials();
    if ($cred === null || !password_verify($current, $cred['password_hash'])) {
        return false;
    }
    return create_account($cred['username'], $new);
}

/* ---------------- Session ---------------- */

function login_user(string $username): void
{
    session_regenerate_id(true);
    $cred = read_credentials();
    $_SESSION['user'] = $cred['username'] ?? $username;
    $_SESSION['last_activity'] = time();
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function current_user(): string
{
    return (string) ($_SESSION['user'] ?? '');
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}
