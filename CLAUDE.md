# Website UMKM — starter kit

Sebelum mengerjakan apa pun di project ini, baca **`PANDUAN-TEMPLATE-WEBSITE.md`** sampai habis.

Ringkasan aturan:
- Fungsi (portal admin, `includes/`, `js/site.js`, `js/ui.js`) **tetap** — hanya dikonfigurasi lewat `includes/config.php` dan blok `SITE` di `js/site.js`.
- Layout (`index.html`, `css/style.css`, template di `js/main.js`) **bebas** didesain ulang, asal hook `data-*` di §4 panduan dipertahankan.
- Wajib kompatibel PHP 7.4; tes lokal: `C:\xampp\php\php.exe -S localhost:8000`.
- Semua data JSON di-render lewat `escapeHtml()`; semua output PHP lewat `e()`.
