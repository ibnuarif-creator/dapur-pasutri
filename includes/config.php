<?php
declare(strict_types=1);

/* =====================================================================
   KONFIGURASI SITUS — satu-satunya file PHP yang diubah per website.
   Samakan SITE_NAME dan WHATSAPP_NUMBER dengan SITE di js/site.js.
   ===================================================================== */

define('SITE_NAME', 'Dapur Pasutri');
define('WHATSAPP_NUMBER', '6281315127837');

/* ---- Fitur portal: matikan yang tidak dipakai layout website ini ---- */
define('FEATURE_PROMOS', false);   // tab Promo + data/promos.json
define('FEATURE_SLIDER', true);    // tab Foto Slider + data/slider.json (slideshow di Tentang Kami)
define('FEATURE_CATEGORY', false); // field Kategori di form menu
define('FEATURE_FEATURED', false); // checkbox "Menu unggulan" di form menu

// Link pemesanan per menu: key di products.json => label di form.
// Link kosong = ikon tidak tampil di website. Tambah/hapus sesuai brand.
define('PRODUCT_LINKS', [
    'shopee'   => 'Shopee',
    'gofood'   => 'GoFood',
    'smexpo'   => 'SMEXPO',
    'whatsapp' => 'WhatsApp',
]);

// Foto tunggal yang bisa diganti admin di tab Tampilan.
// key di settings.json => [label, petunjuk, foto bawaan]
define('SETTINGS_IMAGES', [
    'hero_image'  => ['Foto Hero (Beranda)', 'Foto besar di bagian paling atas halaman.', 'images/hero-tabung-spicy.jpg'],
]);

// Foto slideshow bawaan, dipakai sampai admin menyimpan daftar sendiri.
define('SLIDER_DEFAULT_IMAGES', [
    'images/tentang-tabung.jpg',
    'images/placeholder-tentang-2.jpg', // dummy: ganti lewat portal sebelum live
    'images/placeholder-tentang-3.jpg',
    'images/placeholder-tentang-4.jpg',
    'images/placeholder-tentang-5.jpg',
]);

/* ---- Batasan (biasanya tidak perlu diubah) ---- */
define('PRODUCT_NAME_MAX', 80);
define('PRODUCT_DESC_MAX', 120);
define('PROMO_TITLE_MAX', 100);
define('PROMO_DESC_MAX', 600);
define('SLIDER_MAX_PHOTOS', 5);

define('SESSION_IDLE_TIMEOUT', 2 * 60 * 60);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 15 * 60);

// Batas kita sendiri; batas efektif = min(ini, upload_max_filesize hosting). Foto dari HP
// juga diperkecil otomatis di browser (portal/js/portal.js) sebelum diunggah.
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024);
define('PRICE_MIN', 100); // harga di bawah ini hampir pasti salah ketik (mis. "25" maksudnya 25.000)
define('UPLOAD_ALLOWED_TYPES', [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_WEBP => 'webp',
]);

/* ---- Path ---- */
define('ROOT_DIR', dirname(__DIR__));
define('DATA_DIR', ROOT_DIR . '/data');
define('PRODUCTS_FILE', DATA_DIR . '/products.json');
define('PROMOS_FILE', DATA_DIR . '/promos.json');
define('SETTINGS_FILE', DATA_DIR . '/settings.json');
define('SLIDER_FILE', DATA_DIR . '/slider.json');
define('UPLOADS_DIR', ROOT_DIR . '/images/uploads');
define('UPLOADS_URL_PREFIX', 'images/uploads/');
define('CREDENTIALS_FILE', __DIR__ . '/credentials.json');
define('RATELIMIT_FILE', __DIR__ . '/ratelimit.json');
