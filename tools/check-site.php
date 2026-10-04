<?php
declare(strict_types=1);

/* =====================================================================
   Pemeriksa situs — jalankan SEBELUM deploy (lokal) dan, bila hosting punya
   Terminal, SESUDAH deploy di server:

       php tools/check-site.php

   Mencari penyebab umum "menu tidak muncul / harga tidak muncul" di hosting:
   JSON rusak, harga bukan angka, foto hilang / beda huruf besar-kecil,
   folder tidak bisa ditulis, batas upload & sesi PHP terlalu kecil.
   Exit code 1 bila ada ERROR.
   ===================================================================== */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/storage.php';

$errors = 0;
$warnings = 0;

function report(string $level, string $message): void
{
    global $errors, $warnings;
    if ($level === 'ERROR') {
        $errors++;
    } elseif ($level === 'WARN') {
        $warnings++;
    }
    echo str_pad("[$level]", 8) . $message . PHP_EOL;
}

/** True only when every path segment matches the file on disk with the exact same case. */
function exists_exact_case(string $relative): bool
{
    $path = ROOT_DIR;
    foreach (explode('/', trim(str_replace('\\', '/', $relative), '/')) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        $entries = @scandir($path);
        if ($entries === false || !in_array($segment, $entries, true)) {
            return false;
        }
        $path .= '/' . $segment;
    }
    return file_exists($path);
}

function check_image(string $where, $path): void
{
    $path = (string) $path;
    if ($path === '') {
        report('ERROR', "$where: path foto kosong");
        return;
    }
    if (preg_match('~^(https?:)?//~i', $path)) {
        report('WARN', "$where: foto dari situs luar ($path) — sebaiknya diunggah ke server");
        return;
    }
    if (strpos($path, 'placeholder-') !== false) {
        report('WARN', "$where: masih memakai foto placeholder ($path)");
    }
    if (!is_file(ROOT_DIR . '/' . $path)) {
        report('ERROR', "$where: foto tidak ada di server ($path)");
    } elseif (!exists_exact_case($path)) {
        report('ERROR', "$where: huruf besar/kecil nama file beda dengan di disk ($path) — di hosting Linux foto ini TIDAK tampil");
    }
}

function check_json_file(string $file): ?array
{
    $name = 'data/' . basename($file);
    if (!is_file($file)) {
        return null;
    }
    $raw = (string) file_get_contents($file);
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
        report('WARN', "$name: diawali BOM UTF-8 (simpan ulang tanpa BOM)");
        $raw = substr($raw, 3);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        report('ERROR', "$name: JSON rusak — " . json_last_error_msg() . ' (website tidak bisa menampilkan isinya)');
        return null;
    }
    return $data;
}

echo 'Memeriksa ' . realpath(ROOT_DIR) . PHP_EOL . PHP_EOL;

/* ---- PHP environment ---- */
if (PHP_VERSION_ID < 70400) {
    report('ERROR', 'PHP ' . PHP_VERSION . ' — minimal 7.4');
} else {
    report('OK', 'PHP ' . PHP_VERSION);
}
$uploadMax = ini_bytes('upload_max_filesize');
report($uploadMax > 0 && $uploadMax < 2 * 1024 * 1024 ? 'WARN' : 'OK',
    'upload_max_filesize = ' . ini_get('upload_max_filesize') . ', post_max_size = ' . ini_get('post_max_size')
    . ' → batas efektif portal ' . upload_limit_label());
report('INFO', 'Sesi portal disimpan di includes/sessions/ dengan umur ' . (SESSION_IDLE_TIMEOUT / 60) . ' menit (tidak bergantung gc_maxlifetime hosting)');
if (!function_exists('getimagesize')) {
    report('ERROR', 'getimagesize() tidak tersedia — upload foto akan selalu ditolak');
}

/* ---- Writable folders ---- */
foreach (['data' => DATA_DIR, 'images/uploads' => UPLOADS_DIR, 'includes' => __DIR__ . '/../includes'] as $label => $dir) {
    if (!is_dir($dir)) {
        report('ERROR', "Folder $label tidak ada");
    } elseif (!is_writable($dir)) {
        report('ERROR', "Folder $label tidak bisa ditulis PHP — simpan dari portal akan gagal (set permission 755)");
    } else {
        report('OK', "Folder $label bisa ditulis");
    }
}
foreach (glob(DATA_DIR . '/*.json') ?: [] as $file) {
    if (!is_readable($file) || (DIRECTORY_SEPARATOR === '/' && (fileperms($file) & 0004) === 0)) {
        report('ERROR', 'data/' . basename($file) . ' tidak bisa dibaca publik (permission harus 644) — website gagal memuatnya');
    }
}
$leftovers = glob(DATA_DIR . '/*.tmp-*') ?: [];
if ($leftovers) {
    report('WARN', count($leftovers) . ' file sementara tertinggal di data/ (simpan yang gagal di tengah jalan) — aman dihapus');
}

/* ---- Products ---- */
$products = check_json_file(PRODUCTS_FILE);
if ($products !== null) {
    $ids = [];
    $featured = 0;
    foreach (array_values($products) as $i => $p) {
        $label = 'Menu #' . ($i + 1) . ' "' . ($p['name'] ?? '?') . '"';
        if (!is_array($p)) {
            report('ERROR', "$label: bukan objek");
            continue;
        }
        if (empty($p['id'])) {
            report('ERROR', "$label: tidak punya id (tidak bisa diedit/dihapus)");
        } elseif (isset($ids[$p['id']])) {
            report('ERROR', "$label: id kembar dengan menu lain ({$p['id']})");
        }
        $ids[$p['id'] ?? ''] = true;
        if (trim((string) ($p['name'] ?? '')) === '') {
            report('ERROR', "$label: nama kosong (tidak ditampilkan di website)");
        }
        if (!array_key_exists('price', $p)) {
            report('WARN', "$label: field price tidak ada (tampil \"Hubungi Kami\")");
        } elseif ($p['price'] !== null && !is_int($p['price'])) {
            report('ERROR', "$label: price harus angka atau null, sekarang " . json_encode($p['price']) . ' — simpan ulang menu ini dari portal');
        } elseif (is_int($p['price']) && $p['price'] < PRICE_MIN) {
            report('WARN', "$label: harga Rp{$p['price']} mencurigakan (salah ketik?)");
        }
        if (FEATURE_FEATURED && !empty($p['featured'])) {
            $featured++;
        }
        check_image($label, $p['image'] ?? '');
    }
    report('OK', count($products) . ' menu' . (FEATURE_FEATURED ? " ($featured unggulan, " . (count($products) - $featured) . ' menu lainnya)' : ''));
}

/* ---- Promos, settings, slider ---- */
if (FEATURE_PROMOS && ($promos = check_json_file(PROMOS_FILE)) !== null) {
    foreach (array_values($promos) as $i => $p) {
        check_image('Promo #' . ($i + 1) . ' "' . ($p['title'] ?? '?') . '"', $p['image'] ?? '');
    }
}
if (is_file(SETTINGS_FILE)) {
    check_json_file(SETTINGS_FILE);
}
foreach (load_settings() as $key => $path) {
    if (isset(SETTINGS_IMAGES[$key])) {
        check_image("Tampilan \"$key\"", $path);
    }
}
if (FEATURE_SLIDER) {
    if (is_file(SLIDER_FILE)) {
        check_json_file(SLIDER_FILE);
    }
    foreach (load_slider() as $i => $s) {
        check_image('Slider #' . ($i + 1), $s['image'] ?? '');
    }
}

/* ---- index.html: local assets with the exact case ---- */
$html = (string) @file_get_contents(ROOT_DIR . '/index.html');
preg_match_all('~(?:src|href|srcset)="([^"#?]+)~i', $html, $m);
foreach (array_unique($m[1]) as $ref) {
    if (preg_match('~^(https?:|//|mailto:|tel:|data:|portal/?$)~i', $ref) || $ref === '' || $ref[0] === '#') {
        continue;
    }
    $ref = explode(' ', trim($ref))[0];
    if (!exists_exact_case($ref)) {
        report('ERROR', "index.html merujuk \"$ref\" yang tidak ada (atau beda huruf besar/kecil)");
    }
}

/* ---- Template leftovers ---- */
$leftover = [];
foreach (['Nama Brand', '6281234567890', 'namabrand', 'namatoko'] as $needle) {
    if (stripos($html, $needle) !== false || stripos((string) file_get_contents(ROOT_DIR . '/js/site.js'), $needle) !== false) {
        $leftover[] = $needle;
    }
}
if ($leftover) {
    report('WARN', 'Masih ada teks template: ' . implode(', ', $leftover));
}
if (SITE_NAME === 'Nama Brand' || WHATSAPP_NUMBER === '6281234567890') {
    report('WARN', 'includes/config.php masih berisi nama/WA contoh');
}

echo PHP_EOL . "Selesai: $errors error, $warnings peringatan." . PHP_EOL;
exit($errors > 0 ? 1 : 0);
