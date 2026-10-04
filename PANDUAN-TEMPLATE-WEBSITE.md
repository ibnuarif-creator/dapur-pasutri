# Panduan Template Website UMKM (Flat-File + Portal Admin)

> **Untuk AI yang membaca dokumen ini:** folder ini (`starter-kit/`) adalah titik awal **semua**
> website company-profile UMKM milik pemilik. Website baru = salinan folder ini.
>
> - **Fungsi selalu sama**: portal admin, penyimpanan JSON, keamanan, dan lapisan data JS.
>   Bagian ini **dipakai apa adanya** dan hanya dikonfigurasi lewat `includes/config.php` + blok `SITE` di `js/site.js`.
> - **Layout & tampilan selalu berbeda**: `index.html`, `css/style.css`, dan template di `js/main.js`
>   **boleh ditulis ulang total**, asalkan *kontrak hook* (§4) dipertahankan.
>
> Jangan menanyakan ulang hal yang sudah ditetapkan di sini. Acuan nyata yang sudah live:
> `../Ayakuuuu/` (Ayaku Food) dan `../Cimeler/` (Cimeler) untuk inspirasi layout.

---

## 1. Stack & Batasan

| Aspek | Ketentuan |
|---|---|
| Jenis situs | One-page company profile + katalog menu + CTA order (WhatsApp/Shopee/marketplace) |
| Frontend | HTML + CSS + JavaScript vanilla. **Tanpa framework, build step, atau npm** |
| Backend | PHP murni, hanya untuk `/portal/`. **Tanpa database** |
| Konten | File JSON di `data/`, ditulis portal dan dibaca halaman lewat `fetch()` |
| Hosting | Shared hosting cPanel (Rumahweb), deploy dengan zip lewat File Manager, sering sebagai addon domain |
| PHP | **Wajib jalan di PHP 7.4.** Dilarang memakai `str_starts_with`, `str_contains`, `match`, `?->`, named args, union types, `enum`, atau `readonly`. Tes lokal pakai `C:\xampp\php\php.exe` (7.4) |
| `strict_types` | Aktif di semua file PHP. Fungsi yang menerima nilai dari JSON **jangan** diberi type hint `string` (nilainya bisa int/null) |
| Bahasa | Seluruh teks situs & portal dalam Bahasa Indonesia. Komentar kode dalam bahasa Inggris, singkat, menjelaskan *kenapa* |

---

## 2. Struktur & Kepemilikan File

Legenda: **TETAP** = jangan diubah per website · **KONFIG** = hanya isi nilai · **BEBAS** = tulis ulang sesuai desain.

```
starter-kit/
├── index.html                 BEBAS   layout halaman; wajib memuat hook §4
├── css/style.css              BEBAS   (bagian 1 token: KONFIG, bagian 3 widget: pertahankan class-nya)
├── css/fonts.css              BEBAS   @font-face kalau font self-hosted
├── js/site.js                 TETAP   lapisan data (objek global `Site`); hanya blok SITE = KONFIG
├── js/ui.js                   TETAP   widget berbasis data-* (objek global `UI`)
├── js/main.js                 BEBAS   template kartu + pemanggilan widget untuk layout ini
├── data/*.json                KONFIG  data awal; setelah live, milik server (§9)
├── images/                    BEBAS   logo, favicon, foto bawaan; placeholder-*.svg wajib diganti
├── images/uploads/            TETAP   hasil upload portal (+ .htaccess anti-eksekusi)
├── includes/config.php        KONFIG  nama, WA, fitur on/off, link toko, foto setting, batas
├── includes/*.php (lainnya)   TETAP   bootstrap, helpers, storage, auth, csrf, ratelimit, layout
├── portal/*.php               TETAP   halaman & aksi admin
├── portal/css/admin.css       TETAP   hanya 3 token warna teratas = KONFIG
├── portal/js/portal.js        TETAP   preview harga, kompres foto, anti klik ganda, keep-alive sesi
├── portal/ping.php            TETAP   keep-alive sesi untuk form yang lama diisi
├── tools/check-site.php       TETAP   pemeriksa sebelum/sesudah deploy (CLI, diblok dari web)
├── .user.ini                  TETAP   batas upload & umur sesi PHP untuk hosting
├── images/no-photo.svg        TETAP   pengganti otomatis bila foto menu hilang di server
├── .htaccess, robots.txt      TETAP   (salin blok cPanel bila ada, §9)
├── .gitignore                 TETAP   + tambahkan file kerja brand ini
├── CLAUDE.md                  TETAP   penunjuk ke dokumen ini
└── PANDUAN-TEMPLATE-WEBSITE.md        dokumen ini
```

Kalau suatu website butuh **fungsi baru** (misalnya field produk baru atau tab portal baru), tambahkan di starter kit dengan pola yang sama (§7). Jangan menambalnya hanya di satu website. Fungsi baru juga wajib dijaga lewat flag `FEATURE_*` agar website lain tidak terpengaruh.

---

## 3. Konfigurasi per Website

### 3.1 `includes/config.php`
| Konstanta | Fungsi |
|---|---|
| `SITE_NAME`, `WHATSAPP_NUMBER` | Nama brand (judul portal) & nomor WA format `62…` |
| `FEATURE_PROMOS` | Tab Promo + `data/promos.json` |
| `FEATURE_SLIDER` | Tab Foto Slider + `data/slider.json` (maks. `SLIDER_MAX_PHOTOS`) |
| `FEATURE_CATEGORY` | Field Kategori pada menu |
| `FEATURE_FEATURED` | Checkbox "menu unggulan" (layout memisahkan unggulan vs lainnya) |
| `PRODUCT_LINKS` | Link pemesanan per menu, `['whatsapp' => 'WhatsApp', 'shopee' => 'Shopee', 'tokopedia' => 'Tokopedia', …]`. Key = nama field di `products.json` |
| `SETTINGS_IMAGES` | Foto tunggal yang bisa diganti di tab Tampilan: `key => [label, petunjuk, foto bawaan]`. Contoh tambahan: `'about_image' => ['Foto Tentang Kami', '…', 'images/about.jpg']` |
| `SLIDER_DEFAULT_IMAGES` | Foto slider bawaan sebelum admin menyimpan sendiri |
| `PRODUCT_DESC_MAX` dll. | Batas panjang teks, ukuran upload (3 MB), timeout sesi (2 jam), lockout (5x / 15 menit) |

### 3.2 `js/site.js` (blok `SITE` saja)
```js
const SITE = {
  name: "Nama Brand",              // = SITE_NAME
  whatsapp: "6281234567890",       // = WHATSAPP_NUMBER
  orderLinks: {                    // key sama dengan PRODUCT_LINKS; urutan = urutan ikon
    whatsapp: { label: "WhatsApp", icon: "whatsapp" },
    shopee:   { label: "Shopee",   icon: "shopee" },
  },
};
```
Tombol WhatsApp per menu selalu tampil (link kosong = `SITE.whatsapp`). Ikon yang tersedia di `site.js` adalah `whatsapp`, `shopee`, `instagram`, dan `link` (fallback). Ikon baru ditambahkan ke objek `ICONS`.

### 3.3 Token warna (`css/style.css` bagian 1 + `portal/css/admin.css`)
Nama token berdasarkan **peran**, jadi ganti palet cukup dengan mengubah nilai:
`--primary`, `--primary-dark`, `--secondary`, `--accent`, `--surface-dark`, `--surface-dark-2`,
`--bg`, `--bg-alt`, `--card`, `--ink`, `--ink-soft`, `--line`, `--on-dark`, `--font-display`, `--font-body`.
Di luar `:root`, **dilarang menulis kode hex warna brand**. `admin.css` cukup menyalin `--primary`, `--primary-dark`, dan `--accent`.

Palet acuan:

| Token | Ayaku Food | Cimeler |
|---|---|---|
| `--primary` | `#FF9400` | `#C1272D` |
| `--secondary` | `#E4453C` | `#E86A17` |
| `--surface-dark` | `#5D3A20` | `#2A0E0C` |
| `--bg` | `#FFFBF5` | `#FBF3E7` |
| Font | Fredoka / Plus Jakarta Sans (Google Fonts) | Roboto / General Sans (self-hosted) |

---

## 4. Kontrak Hook HTML (yang menghubungkan layout bebas dengan fungsi tetap)

Class, struktur, dan urutan section bebas. Hanya atribut berikut yang dibaca JS:

| Hook | Diproses oleh | Perilaku |
|---|---|---|
| `<a data-wa="pesan">` | `Site.bindStatic` | `href` diisi link WhatsApp + pesan; `target=_blank` otomatis. **Jangan menulis nomor WA langsung di HTML** |
| `<span data-year>` | `Site.bindStatic` | Tahun berjalan |
| `<img data-setting="hero_image">` | `Site.applySettings` | `src` dari `settings.json` (key apa pun di `SETTINGS_IMAGES`). Isi `src` bawaan = foto default |
| `[data-products="all\|featured\|other"]` | `renderProducts` (main.js) | Grid menu. `featured`/`other` dipakai hanya bila `FEATURE_FEATURED`; selain itu pakai `all` |
| `[data-products-block]` | `renderProducts` | Pembungkus grid (beserta judulnya) yang disembunyikan otomatis bila grid kosong |
| `[data-promo-section]` (+ atribut `hidden`) & `[data-promos]` | `renderPromos` | Section tampil hanya bila ada promo aktif |
| `[data-slideshow]` (+ `data-interval`, `data-alt`) berisi `<img>` & `[data-slideshow-dots]` | `renderSlider` + `UI.slideshow` | `<img>` bawaan diganti foto dari portal; crossfade + dots |
| `[data-carousel]` > `[data-carousel-track]`, `[data-carousel-prev]`, `[data-carousel-next]` | `UI.carousel` | Carousel tanpa ujung (item di-clone), autoplay |
| `<header data-header>` | `UI.headerScrolled` | Class `.is-scrolled` saat halaman di-scroll |
| `<nav data-nav id>` + `<button data-nav-toggle aria-controls>` | `UI.nav` | Hamburger: class `.is-open`, `aria-expanded`, tombol Esc |
| `[data-nav] a[href="#id"]` (+ `data-also="id,id"`) | `UI.scrollSpy` | `.is-active` untuk section yang terlihat; link disembunyikan bila semua section-nya `hidden` |
| `[data-reveal]`, `[data-reveal="stagger"]` | `UI.reveal` | `.is-visible` saat masuk viewport |
| `[data-count="5000"]` (+ `data-format="year"`) | `UI.counters` | Angka berhitung naik |

Urutan script (di akhir `<body>`, dengan `defer`): `js/site.js` → `js/ui.js` → `js/main.js`.

### 4.1 Section standar (isi & urutan bebas, pilih sesuai brief)
Header sticky (logo, nav, CTA WA, hamburger) → Hero (foto `hero_image`, tagline, tombol, statistik) → Promo (opsional) → Legalitas/badge (opsional) → Keunggulan (grid/carousel) → Menu (grid + CTA reseller) → Tentang/Korporasi (slideshow) → Klien/Mitra (opsional) → Kontak (WA, email, IG, alamat, Maps) → Footer (`© <tahun> <Brand>. Website made by @desainytta` + link kecil **"Portal Team"** ke `portal/`) → tombol WA melayang.

### 4.2 Menulis `js/main.js` untuk layout baru
- Ubah **template** (`productCard`, `promoCard`, `orderButtons`, pesan kosong/error). Pola `renderProducts`/`renderPromos`/`renderSlider` dan `init()` sebaiknya dipertahankan.
- **Semua** nilai dari JSON wajib lewat `escapeHtml()`. Teks promo multi-paragraf pakai `paragraphs()`.
- Harga pakai `formatPrice()`, yang menghasilkan `"Rp22.500"` atau `null` (tampilkan "Hubungi Kami").
- Link pemesanan pakai `Site.orderLinks(product, pesanWA)`, yang mengembalikan `[{key, url, label, icon}]` dan melewati link kosong.
- `Site.getProducts()` mengembalikan `null` kalau gagal (tampilkan pesan + link WA) dan `[]` kalau kosong.
- Widget yang bergantung pada konten hasil render (`UI.reveal`, `UI.counters`, `UI.scrollSpy`) dipanggil **setelah** render selesai.

---

## 5. Skema Data JSON

Semua berupa array (kecuali `settings.json`). **Urutan array = urutan tampil.** Path gambar relatif terhadap root situs, tanpa `/` di depan.

**`products.json`**
```json
{ "id": "cilok-goreng-a1b2c3", "name": "Cilok Goreng", "price": 22500, "desc": "…",
  "image": "images/uploads/57cb….png", "category": "Cilok", "featured": true,
  "whatsapp": "https://wa.me/62…", "shopee": "https://shopee.co.id/…" }
```
`price` **selalu int atau `null`** (`null` = "Hubungi Kami", hanya jika admin mencentang "Tanpa harga tetap"). `category`/`featured` hanya ada bila fiturnya aktif. Satu field per key di `PRODUCT_LINKS`; string kosong berarti ikon disembunyikan. Field ekstra yang tidak dikenal dipertahankan saat edit.

**`promos.json`**: `{ "id", "title", "desc" (paragraf dipisah "\n\n"), "image", "active": bool }`

**`settings.json`**: `{ "hero_image": "images/…" }`. Key mengikuti `SETTINGS_IMAGES`; kalau key belum ada, dipakai foto bawaan.

**`slider.json`**: `[{ "id": "slide-3fa9c1d2", "image": "images/uploads/….jpg" }]`. Kalau file belum ada, dipakai `SLIDER_DEFAULT_IMAGES`.

---

## 6. Portal Admin (fungsi tetap)

| Tab / halaman | File | Fungsi |
|---|---|---|
| Login & setup awal | `login.php` | Kalau `credentials.json` belum ada, halaman ini membuat akun pertama (username ≥ 3, password ≥ 8). Setelah itu jadi form login. Gagal 5x = kunci 15 menit per IP |
| Menu | `index.php`, `product-form.php` → `product-save.php`, `product-action.php` (delete/move) | Tambah/edit/hapus, ▲▼ urutan, penghitung karakter, datalist kategori, harga atau "tanpa harga tetap", link toko per menu, input tidak hilang saat validasi gagal |
| Promo | `promos.php`, `promo-form.php` → `promo-save.php`, `promo-action.php` (delete/toggle/move) | Seperti Menu, plus tombol Aktifkan/Nonaktifkan |
| Tampilan | `settings.php` → `settings-save.php` | Ganti foto dari `SETTINGS_IMAGES` dengan preview, plus "Kembalikan ke Bawaan" |
| Foto Slider | `slider.php` → `slider-save.php` (add/delete/move) | Upload banyak foto sekaligus (sisa slot ditampilkan), minimal 1 foto |
| Ganti Password | `change-password.php` | Password lama + baru + konfirmasi |
| Keluar | `logout.php` | Hapus sesi dan cookie |

Tab yang fiturnya dimatikan otomatis hilang, dan halamannya me-redirect ke `index.php`.

### 6.1 Perilaku yang sudah dijamin (jangan dibuat ulang)
- **Harga** diinput sebagai teks dan diparse `parse_price()`. Format yang diterima: `25000`, `25.000`, `25,000`, `Rp 25.000`, `25.000,00`, `25rb`, dan `12.5k`. Harga kosong atau tidak terbaca **ditolak** (tidak pernah diam-diam jadi "Hubungi Kami"), dan harga < `PRICE_MIN` dianggap salah ketik. Form menampilkan preview "Tampil di website: Rp25.000". **Jangan pernah memakai `<input type="number" step=…>` untuk harga.**
- **Foto** dari HP diperkecil di browser sebelum diunggah (maks. sisi 1600 px, JPEG 82%, PNG/WEBP tetap transparan), jadi foto 12 MB menjadi ±0,6 MB. Batas efektif = min(`UPLOAD_MAX_BYTES`, `upload_max_filesize` hosting) dan ditampilkan apa adanya di pesan error.
- **Sesi** disimpan di `includes/sessions/` dengan umur sendiri (tidak ikut terhapus oleh gc 24 menit cPanel). Form panjang mengirim keep-alive ke `ping.php` setiap 4 menit dan menampilkan peringatan kalau sesi berakhir.
- **Kunci data** (`lock_data()` lewat `require_post()`): simpan bersamaan atau klik ganda tidak saling menimpa. Tombol submit juga terkunci saat dikirim ("Menyimpan…").
- **Daftar menu portal** memberi tanda "Foto tidak ditemukan" dan label "Unggulan"/"Menu Lainnya" di tiap menu.
- **Halaman publik**: JSON diambil dengan `?v=timestamp` + `no-store` (anti cache hosting/proxy). Tiap kartu dirender terpisah (satu data rusak tidak mengosongkan grid), foto hilang diganti `no-photo.svg`, grid "featured" tanpa grid "other"/"all" otomatis menampilkan semua menu, dan tombol WhatsApp selalu ada (link kosong = nomor utama).
- Pola Post/Redirect/Get dengan pesan flash di sesi (`flash()` / `take_flash()`).
- Validasi server-side untuk semua field, karena atribut HTML mudah dilewati.
- Upload: `getimagesize` + whitelist JPG/PNG/WEBP, maks. 3 MB, nama file acak, ekstensi dari tipe yang terdeteksi, `chmod 0644`.
- Upload yang melebihi `post_max_size` memunculkan pesan "File terlalu besar", bukan error CSRF.
- Foto lama di `images/uploads/` dihapus setelah diganti atau dihapus, tapi **hanya setelah** JSON berhasil disimpan. Foto bawaan tidak pernah dihapus.
- Kalau simpan JSON gagal, foto yang baru diupload ikut dihapus (tidak meninggalkan file yatim).
- Tulis JSON secara atomik (temp file + rename) lalu `chmod 0644` (credentials/ratelimit `0600`).
- CSRF di setiap form POST, `session_regenerate_id` saat login, cookie `HttpOnly` + `SameSite=Strict` (+ `Secure` di HTTPS), idle timeout, header `X-Frame-Options: DENY`.

---

## 7. Menambah Fungsi Baru ke Starter Kit (pola wajib)

Halaman portal:
```php
<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';   // config, helpers, storage, csrf, auth, ratelimit, layout + session
require_login();
require_feature(FEATURE_XXX);                      // kalau fiturnya opsional

portal_header('Judul Halaman', 'key-tab');         // topbar + tab + flash
?>
    … HTML; semua output lewat e(); setiap form POST berisi <?= csrf_field() ?> …
<?php portal_footer(); ?>
```

Aksi POST:
```php
<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_post('halaman-asal.php');                  // tolak non-POST, CSRF salah, body kebesaran

$items = load_xxx();
// validasi → fail_back('form.php', 'Pesan', $inputUntukDiisiUlang);
// upload  → try { $path = handle_image_upload($_FILES['image'] ?? []); } catch (RuntimeException $e) { fail_back(…); }
// simpan  → if (!save_xxx($items)) { delete_uploaded_image($path); fail_back(…); }
flash('success', 'Berhasil.');
redirect('halaman-asal.php');
```

Data baru: tambahkan konstanta path di `config.php`, pasangan `load_xxx()`/`save_xxx()` di `storage.php` (pakai `read_json`/`write_json`), tab di `portal_tabs()` (`layout.php`), loader `getXxx()` di `site.js`, dan dokumentasikan hook + skemanya di dokumen ini.

Helper yang tersedia: `e()`, `redirect()`, `post_str()`, `str_length()`, `flash()`, `fail_back()`, `take_old_input()`, `require_post()`, `require_feature()`, `normalize_link()`, `admin_image_src()`, `format_rupiah()`, `generate_id()`, `find_index_by_id()`, `move_item()`, `normalize_multi_upload()`, `delete_uploaded_image()`, `reorder_buttons()`, `delete_button()`.

---

## 8. Checklist Website Baru

**A. Brief dari pemilik** (tanyakan hanya yang belum diberikan):
1. Nama brand, tagline, tahun berdiri, cerita brand
2. Logo (PNG transparan/SVG), palet warna (atau dokumen brand), font, referensi gaya/desain
3. WhatsApp, email, Instagram, alamat + lokasi Maps, link toko (Shopee/TikTok/Tokopedia/lainnya)
4. Produk awal: nama, kategori, harga/"tanya harga", deskripsi, foto
5. Fitur: promo? slider? kategori? menu unggulan? foto apa saja yang bisa diganti admin?
6. Section opsional: legalitas (BPOM/Halal/NIB/HKI), klien/mitra, korporasi/maklon/reseller
7. 4–6 poin keunggulan
8. Domain & hosting (domain utama atau addon domain)

**B. Bangun**
1. Salin `starter-kit/` ke `E:\WEBProject\<NamaBrand>\`.
2. Isi `includes/config.php`, blok `SITE` di `js/site.js`, dan token warna di `style.css` + `admin.css`.
3. Desain ulang `index.html`, `css/style.css`, dan template `js/main.js` sesuai brand, dengan hook §4 tetap dipertahankan.
4. Ganti logo, favicon (`images/favicon.svg` atau tambahkan `.ico`/PNG 192 + `apple-touch-icon`), dan semua `placeholder-*.svg`.
5. Isi `data/products.json` (dan promo bila ada) dengan data asli. **Jangan memakai gambar Unsplash atau placeholder di versi live.**
6. Cari sisa teks template: `grep -rn "Nama Brand\|6281234567890\|namabrand\|namatoko\|placeholder-" .`
7. Tes lokal di PHP 7.4: `C:\xampp\php\php.exe -S localhost:8000`, lalu cek setup akun, CRUD menu/promo, upload, urutan, slider, tampilan, ganti password, logout, dan lockout.
8. Cek tampilan di lebar 375, 768, dan 1280 px: menu hamburger, slideshow terlihat, tidak ada scroll horizontal.
9. Lint: `for f in includes/*.php portal/*.php tools/*.php; do C:/xampp/php/php.exe -l $f; done`
10. Jalankan `C:/xampp/php/php.exe tools/check-site.php`: harus **0 error**, dan peringatan placeholder/teks template sudah dibereskan.
11. Tes dengan batas hosting: `C:/xampp/php/php.exe -d upload_max_filesize=2M -d post_max_size=8M -S localhost:8000`, lalu tambah menu dengan foto HP asli dan harga "25.000".

**C. Deploy**
1. Zip **tanpa** `.git`, `CLAUDE.md`, `PANDUAN-*.md`, `tools/`, file kerja, `includes/credentials.json`, `includes/ratelimit.json`, dan `includes/sessions/`. `.user.ini` **ikut** di-zip.
2. Upload & extract di document root. Pastikan PHP ≥ 7.4 (MultiPHP Manager).
3. Pastikan folder `data/`, `images/uploads/`, dan `includes/` bisa ditulis PHP (755). Cek di cPanel → MultiPHP INI Editor bahwa `upload_max_filesize` ≥ 8M (kalau `.user.ini` tidak terbaca, set di sana).
3b. Kalau hosting punya Terminal: `php tools/check-site.php` di folder situs (upload `tools/` sementara, lalu hapus lagi).
4. Aktifkan SSL, lalu cek redirect HTTPS.
5. **Segera** buka `/portal/` dan buat akun admin. Siapa pun yang membukanya lebih dulu bisa membuat akun.
6. Untuk update berikutnya, zip **hanya** file kode yang berubah (lihat §9).

---

## 9. Keamanan Server & Data Runtime

- `/.htaccess`: `-Indexes`, redirect paksa ke HTTPS, blok dotfile dan `*.md/.zip/.bak/.log/.sql`, cache aset panjang, HTML/JSON/PHP `no-cache`. Kalau cPanel sudah menulis blok `# BEGIN cPanel-generated php ini directives` di server, **salin blok itu ke atas file** sebelum deploy.
- `includes/.htaccess`: deny all.
- `images/uploads/.htaccess`: PHP mati dan blok ekstensi skrip/SVG/HTML.
- `robots.txt`: disallow `/portal/` dan `/includes/`.
- `.user.ini`: `upload_max_filesize=8M`, `post_max_size=32M`, `session.gc_maxlifetime=7800`.
- **Milik server setelah live** (jangan ditimpa saat update): `data/*.json`, `images/uploads/*`, `includes/credentials.json`, `includes/ratelimit.json`, `includes/sessions/`.

---

## 10. Jebakan yang Sudah Pernah Terjadi

| Masalah | Penyebab | Sudah ditangani di |
|---|---|---|
| Portal error di hosting | Fungsi PHP 8 di server PHP 7.4 | Aturan §1 + lint pakai 7.4 |
| Form edit berhenti di field harga | `e(?string)` + `strict_types` menerima int | `e($value)` tanpa type hint |
| Menu/JSON 403 setelah disimpan | `tempnam()` membuat file 0600 | `write_json()` → `chmod 0644` |
| Foto upload 403 | File upload 0600 | `handle_image_upload()` → `chmod 0644` |
| Editan admin tidak muncul | Cache JSON | `fetch(…, {cache:"no-store"})` + `.htaccess` no-cache |
| Slideshow hilang di mobile | Wadah berisi gambar absolute tanpa width | `.slideshow { width:100% }` (jangan dihapus) |
| Slideshow berhenti setelah disentuh | `mouseenter` palsu saat tap di layar sentuh | Pause hanya bila `(hover: hover)` |
| Logo miring & hamburger keluar layar | Margin desktop tanpa override mobile | Cek di 375 px; jangan memakai margin tetap besar di header |
| Link toko tanpa `https://` ditolak | Validasi terlalu ketat | `normalize_link()` menambahkan `https://` |
| Carousel lompat mundur | Scroll balik ke 0 | `UI.carousel` (clone item) |
| XSS dari isi JSON | `innerHTML` tanpa escape | `escapeHtml()` wajib di `main.js` |
| Upload besar memunculkan "token tidak valid" | `post_max_size` membuang seluruh body | `require_post()` mendeteksi & memberi pesan jelas |
| Garis tab portal memunculkan scrollbar | Margin negatif di dalam `overflow-x:auto` | `.tabs` memakai inset box-shadow |
| **Harga tidak muncul** (Ayaku) | `type="number"`: "25.000"/"25,000" terbaca kosong lalu disimpan `null` | Input teks + `parse_price()`; kosong = error, bukan null |
| **Harga tidak muncul** (Cimeler) | Form menu baru membuka dengan "Tanpa harga tetap" sudah tercentang | Default tidak tercentang; tanpa harga harus dicentang sengaja |
| Harga 22.750 tidak bisa disimpan | `step="500"` ditolak browser | Tanpa `step`, server yang memvalidasi |
| **Menu tidak muncul**: simpan ditolak setelah lama mengisi form | gc sesi cPanel 24 menit menghapus sesi → CSRF/login gagal | Folder sesi sendiri + `ping.php` keep-alive + `.user.ini` |
| **Menu tidak muncul**: foto HP gagal diunggah | Foto 3–8 MB > `upload_max_filesize` hosting (sering 2M) | Kompres di browser + `.user.ini` + pesan batas yang sebenarnya |
| **Menu tidak muncul** sampai di-refresh paksa (Cimeler) | `fetch` JSON tanpa `no-store` → versi lama dari cache | `?v=timestamp` + `no-store` |
| Menu baru tampil di tempat lain / "hilang" | Tidak dicentang unggulan → masuk "Menu Lainnya"; layout tanpa grid itu menyembunyikannya | Label di portal + fallback grid di `main.js` |
| Menu hilang saat dua kali klik Simpan | Dua request load→save bersamaan saling menimpa | `lock_data()` + tombol terkunci saat submit |
| Simpan gagal tanpa sebab | Teks tempelan berisi UTF-8 rusak → `json_encode` gagal | `JSON_INVALID_UTF8_SUBSTITUTE` + `error_log` |
| Foto tampil di lokal, hilang di hosting | Linux membedakan huruf besar/kecil (`Foto.JPG` ≠ `foto.jpg`) | `tools/check-site.php` memeriksa huruf persis + tanda di portal |
| Menu tanpa tombol pesan | Link WA per menu dikosongkan | WA selalu tampil, fallback nomor utama |
