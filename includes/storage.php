<?php
declare(strict_types=1);

/* =====================================================================
   JSON storage + image uploads. Every content file in data/ goes through
   read_json()/write_json(); never write those files any other way.
   ===================================================================== */

function read_json(string $file, array $default = []): array
{
    if (!is_file($file)) {
        return $default;
    }
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') {
        return $default;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

/**
 * Atomic write: a temp file in the same folder, then rename() over the target,
 * so a visitor never fetches a half-written JSON file.
 */
function write_json(string $file, array $data, int $mode = 0644): bool
{
    // INVALID_UTF8_SUBSTITUTE: text pasted from Word/WhatsApp can contain broken
    // UTF-8, which would otherwise make json_encode() fail and the save silently lost.
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        error_log('[portal] json_encode failed for ' . basename($file) . ': ' . json_last_error_msg());
        return false;
    }
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    // Not tempnam(): when the folder isn't writable it silently falls back to the
    // system temp dir, and the rename across filesystems then fails in odd ways.
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        error_log('[portal] cannot write ' . $tmp . ' — is ' . $dir . ' writable?');
        @unlink($tmp);
        return false;
    }
    // Windows rename() can't replace an existing file; harmless no-op on Linux hosting.
    if (DIRECTORY_SEPARATOR === '\\' && is_file($file)) {
        @unlink($file);
    }
    if (!@rename($tmp, $file)) {
        error_log('[portal] cannot replace ' . $file);
        @unlink($tmp);
        return false;
    }
    // Some hosts create files 0600; public data must be 0644 or the site's fetch() gets a 403.
    @chmod($file, $mode);
    return true;
}

/**
 * Exclusive lock on data/ for the rest of this request (released when PHP exits).
 * Called by require_post() so every portal write runs one at a time.
 */
function lock_data(): void
{
    static $handle = null;
    if ($handle !== null) {
        return;
    }
    $handle = @fopen(DATA_DIR . '/.lock', 'c');
    if ($handle !== false) {
        flock($handle, LOCK_EX);
    }
}

/* ---------------- List helpers (products, promos, slider) ---------------- */

function generate_id(string $name, array $existing, string $fallback = 'item'): string
{
    $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    if ($slug === '') {
        $slug = $fallback;
    }
    $slug = substr($slug, 0, 40);
    do {
        $id = $slug . '-' . bin2hex(random_bytes(3));
    } while (find_index_by_id($existing, $id) !== null);
    return $id;
}

function find_index_by_id(array $items, string $id): ?int
{
    foreach ($items as $index => $item) {
        if (($item['id'] ?? null) === $id) {
            return (int) $index;
        }
    }
    return null;
}

/** Swap an item with its neighbour. Returns false when it's already at that edge. */
function move_item(array &$items, int $index, string $direction): bool
{
    $target = $direction === 'up' ? $index - 1 : ($direction === 'down' ? $index + 1 : -1);
    if ($target < 0 || $target >= count($items)) {
        return false;
    }
    $tmp = $items[$index];
    $items[$index] = $items[$target];
    $items[$target] = $tmp;
    return true;
}

/* ---------------- Content files ---------------- */

function load_products(): array
{
    return array_values(read_json(PRODUCTS_FILE));
}

function save_products(array $products): bool
{
    return write_json(PRODUCTS_FILE, array_values($products));
}

function load_promos(): array
{
    return array_values(read_json(PROMOS_FILE));
}

function save_promos(array $promos): bool
{
    return write_json(PROMOS_FILE, array_values($promos));
}

/** Settings with every SETTINGS_IMAGES key guaranteed present (falls back to its bundled photo). */
function load_settings(): array
{
    $settings = read_json(SETTINGS_FILE);
    foreach (SETTINGS_IMAGES as $key => $meta) {
        if (empty($settings[$key])) {
            $settings[$key] = $meta[2];
        }
    }
    return $settings;
}

function save_settings(array $settings): bool
{
    return write_json(SETTINGS_FILE, $settings);
}

function load_slider(): array
{
    if (is_file(SLIDER_FILE)) {
        return array_values(read_json(SLIDER_FILE));
    }
    $defaults = [];
    foreach (SLIDER_DEFAULT_IMAGES as $i => $image) {
        $defaults[] = ['id' => 'default-' . ($i + 1), 'image' => $image];
    }
    return $defaults;
}

function save_slider(array $photos): bool
{
    return write_json(SLIDER_FILE, array_values($photos));
}

/* ---------------- Image uploads ---------------- */

/**
 * Validate and store one uploaded image in images/uploads/.
 * Returns the site-relative path, or null when no file was chosen (caller keeps the old image).
 * @throws RuntimeException with a message that is safe to show the admin.
 */
function handle_image_upload(array $file): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Ukuran foto terlalu besar. Maksimal ' . upload_limit_label() . '.');
    }
    if ($error === UPLOAD_ERR_PARTIAL) {
        throw new RuntimeException('Upload foto terputus (koneksi tidak stabil). Coba lagi.');
    }
    if ($error === UPLOAD_ERR_NO_TMP_DIR || $error === UPLOAD_ERR_CANT_WRITE) {
        throw new RuntimeException('Server tidak bisa menyimpan file sementara. Hubungi pengelola hosting.');
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload foto gagal (kode ' . (int) $error . '). Coba lagi.');
    }
    if ((int) $file['size'] > upload_limit_bytes()) {
        throw new RuntimeException('Ukuran foto maksimal ' . upload_limit_label() . '.');
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !isset(UPLOAD_ALLOWED_TYPES[$info[2]])) {
        throw new RuntimeException('File harus berupa gambar JPG, PNG, atau WEBP.');
    }

    if (!is_dir(UPLOADS_DIR)) {
        @mkdir(UPLOADS_DIR, 0755, true);
    }
    if (!is_writable(UPLOADS_DIR)) {
        throw new RuntimeException('Folder images/uploads tidak bisa ditulis server. Ubah permission folder menjadi 755.');
    }

    // Never reuse the visitor-supplied name; the extension comes from the detected type.
    $filename = bin2hex(random_bytes(10)) . '.' . UPLOAD_ALLOWED_TYPES[$info[2]];
    $destination = UPLOADS_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Gagal menyimpan foto di server.');
    }
    // The upload can land 0600, which would 403 the photo for visitors.
    @chmod($destination, 0644);

    return UPLOADS_URL_PREFIX . $filename;
}

/** Turn $_FILES['x'] from a multiple <input> into a list of single-file arrays. */
function normalize_multi_upload($raw): array
{
    if (!is_array($raw) || !is_array($raw['name'] ?? null)) {
        return [];
    }
    $files = [];
    foreach ($raw['name'] as $i => $_) {
        if (($raw['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name'     => $raw['name'][$i],
            'type'     => $raw['type'][$i],
            'tmp_name' => $raw['tmp_name'][$i],
            'error'    => $raw['error'][$i],
            'size'     => $raw['size'][$i],
        ];
    }
    return $files;
}

/** Delete an image file, but only one the admin uploaded — bundled images are never touched. */
function delete_uploaded_image(?string $path): void
{
    $path = (string) $path;
    if ($path === '' || strpos($path, UPLOADS_URL_PREFIX) !== 0 || strpos($path, '..') !== false) {
        return;
    }
    $file = ROOT_DIR . '/' . $path;
    if (is_file($file)) {
        @unlink($file);
    }
}
