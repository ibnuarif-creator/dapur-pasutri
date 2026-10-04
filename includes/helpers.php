<?php
declare(strict_types=1);

/** HTML-escape for templates. No type hint: values from JSON may be int or null. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Trimmed string from $_POST; arrays or missing keys become ''. */
function post_str(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function str_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/* ---------------- Flash messages & old input ---------------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

/** Keep the submitted form values so a failed save doesn't wipe what the admin typed. */
function remember_input(array $values): void
{
    $_SESSION['old_input'] = $values;
}

function take_old_input(): ?array
{
    $old = $_SESSION['old_input'] ?? null;
    unset($_SESSION['old_input']);
    return is_array($old) ? $old : null;
}

/** Flash an error, remember the form values, and send the admin back to the form. */
function fail_back(string $url, string $message, array $input = []): void
{
    flash('error', $message);
    if ($input) {
        remember_input($input);
    }
    redirect($url);
}

/* ---------------- Request guards ---------------- */

/**
 * Only allow POST with a valid CSRF token. When the upload is bigger than PHP's
 * post_max_size, PHP silently drops the whole body (including the CSRF token),
 * so say "file too big" instead of a confusing security error.
 */
function require_post(string $backUrl): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($backUrl);
    }
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('error', 'File terlalu besar untuk diunggah. Maksimal ' . upload_limit_label() . ' per foto.');
        redirect($backUrl);
    }
    if (!csrf_verify()) {
        flash('error', 'Sesi kedaluwarsa atau permintaan tidak valid. Silakan coba lagi.');
        redirect($backUrl);
    }
    // Serialize every load→modify→save so a double-click or two admins saving at
    // once can't overwrite each other's change.
    lock_data();
}

function require_feature(bool $enabled): void
{
    if (!$enabled) {
        redirect('index.php');
    }
}

/* ---------------- URLs & images ---------------- */

/**
 * Normalize an optional shop/marketplace link. '' stays ''. A bare domain gets
 * https:// prepended. Returns null when the result isn't a valid http(s) URL,
 * which also rejects javascript: and other unsafe schemes.
 */
function normalize_link(string $url): ?string
{
    if ($url === '') {
        return '';
    }
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    if (filter_var($url, FILTER_VALIDATE_URL) === false || !preg_match('~^https?://~i', $url)) {
        return null;
    }
    return $url;
}

/** <img src> usable from inside /portal/ for a path stored relative to the site root. */
function admin_image_src(?string $path): string
{
    $path = (string) $path;
    if ($path === '' || preg_match('~^(https?:)?//~i', $path)) {
        return $path;
    }
    return '../' . $path;
}

/**
 * Does a site-relative image actually exist on disk? On Linux hosting this is
 * case-sensitive ("Foto.JPG" ≠ "foto.jpg"), unlike Windows where the site was built.
 */
function site_image_exists(?string $path): bool
{
    $path = (string) $path;
    if ($path === '') {
        return false;
    }
    if (preg_match('~^(https?:)?//~i', $path)) {
        return true;
    }
    return is_file(ROOT_DIR . '/' . ltrim($path, '/'));
}

function format_rupiah(?int $price): string
{
    return $price === null ? '' : 'Rp' . number_format($price, 0, ',', '.');
}

/**
 * Parse a price the way admins actually type it: "25000", "25.000", "25,000",
 * "Rp 25.000", "25.000,00", "25rb", "25k". Returns null when it isn't a price.
 * Rupiah has no cents, so a 1–2 digit tail after the last separator is dropped.
 * Mirrored in portal/js/portal.js (parsePrice) for the live preview — keep in sync.
 */
function parse_price(string $raw): ?int
{
    $s = strtolower(preg_replace('/\s+/', '', $raw));
    $s = (string) preg_replace('/^rp\.?/', '', $s);
    if ($s === '') {
        return null;
    }
    if (preg_match('/^(\d+(?:[.,]\d+)?)(k|rb|ribu)$/', $s, $m)) {
        return (int) round((float) str_replace(',', '.', $m[1]) * 1000);
    }
    if (!preg_match('/^\d[\d.,]*$/', $s)) {
        return null;
    }
    $s = (string) preg_replace('/[.,]\d{1,2}$/', '', $s);
    $digits = (string) preg_replace('/\D/', '', $s);
    if ($digits === '' || strlen($digits) > 10) {
        return null;
    }
    return (int) $digits;
}

/* ---------------- Upload limits ---------------- */

/** php.ini size ("2M", "512K", "1G") in bytes; 0 when unset/unlimited. */
function ini_bytes(string $key): int
{
    $value = trim((string) ini_get($key));
    if ($value === '') {
        return 0;
    }
    $number = (int) $value;
    switch (strtolower(substr($value, -1))) {
        case 'g':
            $number *= 1024;
            // no break
        case 'm':
            $number *= 1024;
            // no break
        case 'k':
            $number *= 1024;
    }
    return $number;
}

/**
 * The real per-file limit: our own UPLOAD_MAX_BYTES, but never above what the
 * hosting's php.ini accepts (cPanel often defaults upload_max_filesize to 2M).
 */
function upload_limit_bytes(): int
{
    $limits = [UPLOAD_MAX_BYTES];
    foreach (['upload_max_filesize', 'post_max_size'] as $key) {
        $bytes = ini_bytes($key);
        if ($bytes > 0) {
            $limits[] = $bytes;
        }
    }
    return min($limits);
}

function upload_limit_label(): string
{
    $mb = upload_limit_bytes() / 1024 / 1024;
    return rtrim(rtrim(number_format($mb, 1, ',', ''), '0'), ',') . 'MB';
}
