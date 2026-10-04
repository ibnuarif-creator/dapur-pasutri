<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();

$id = post_str('id');
$formUrl = 'product-form.php' . ($id !== '' ? '?id=' . urlencode($id) : '');
require_post($formUrl);

$products = load_products();
$index = $id !== '' ? find_index_by_id($products, $id) : null;
if ($id !== '' && $index === null) {
    fail_back('index.php', 'Menu tidak ditemukan. Mungkin sudah dihapus.');
}
$isEdit = $index !== null;

// --- Read input -------------------------------------------------------
$name = post_str('name');
$category = FEATURE_CATEGORY ? post_str('category') : '';
$desc = post_str('desc');
$noPrice = !empty($_POST['no_price']);
$priceRaw = post_str('price');
$featured = FEATURE_FEATURED && !empty($_POST['featured']);

$rawLinks = is_array($_POST['links'] ?? null) ? $_POST['links'] : [];
$links = [];
foreach (PRODUCT_LINKS as $key => $label) {
    $links[$key] = is_string($rawLinks[$key] ?? null) ? trim($rawLinks[$key]) : '';
}

$input = ['name' => $name, 'category' => $category, 'desc' => $desc, 'price' => $priceRaw,
          'no_price' => $noPrice, 'featured' => $featured] + $links;

// --- Validate (the browser checks too, but that is trivial to bypass) ---
if ($name === '' || $desc === '' || (FEATURE_CATEGORY && $category === '')) {
    fail_back($formUrl, FEATURE_CATEGORY ? 'Nama, kategori, dan deskripsi wajib diisi.' : 'Nama dan deskripsi wajib diisi.', $input);
}
if (str_length($name) > PRODUCT_NAME_MAX) {
    fail_back($formUrl, 'Nama menu maksimal ' . PRODUCT_NAME_MAX . ' karakter.', $input);
}
if (str_length($desc) > PRODUCT_DESC_MAX) {
    fail_back($formUrl, 'Deskripsi maksimal ' . PRODUCT_DESC_MAX . ' karakter (saat ini ' . str_length($desc) . ').', $input);
}

// Price is never silently dropped: either a valid number, or the admin explicitly
// ticked "Tanpa harga tetap". An empty/unreadable price is an error, not "no price".
$price = null;
if (!$noPrice) {
    if ($priceRaw === '') {
        fail_back($formUrl, 'Harga belum diisi. Isi harga (contoh: 25000 atau 25.000), atau centang "Tanpa harga tetap".', $input);
    }
    $price = parse_price($priceRaw);
    if ($price === null) {
        fail_back($formUrl, 'Harga "' . $priceRaw . '" tidak bisa dibaca. Tulis angka saja, contoh: 25000 atau 25.000.', $input);
    }
    if ($price < PRICE_MIN) {
        fail_back($formUrl, 'Harga Rp' . $price . ' terlalu kecil — mungkin maksudnya ' . format_rupiah($price * 1000) . '? Tulis harga lengkap, contoh: 25000.', $input);
    }
}

foreach ($links as $key => $url) {
    $normalized = normalize_link($url);
    if ($normalized === null) {
        $example = $key === 'whatsapp' ? 'wa.me/' . WHATSAPP_NUMBER : 'shopee.co.id/namatoko';
        fail_back($formUrl, 'Link ' . PRODUCT_LINKS[$key] . ' tidak valid. Contoh: ' . $example, $input);
    }
    $links[$key] = $normalized;
}

// --- Image --------------------------------------------------------------
try {
    $uploaded = handle_image_upload($_FILES['image'] ?? []);
} catch (RuntimeException $e) {
    fail_back($formUrl, $e->getMessage(), $input);
}
if (!$isEdit && $uploaded === null) {
    fail_back($formUrl, 'Foto menu wajib diunggah.', $input);
}

// --- Save ----------------------------------------------------------------
$oldImage = $isEdit ? ($products[$index]['image'] ?? '') : '';
$record = [
    'id'    => $isEdit ? $products[$index]['id'] : generate_id($name, $products, 'menu'),
    'name'  => $name,
    'price' => $price,
    'desc'  => $desc,
    'image' => $uploaded ?? $oldImage,
];
if (FEATURE_CATEGORY) {
    $record['category'] = $category;
}
if (FEATURE_FEATURED) {
    $record['featured'] = $featured;
}
$record += $links;

if ($isEdit) {
    // Keep any extra fields a custom layout may have added to this item.
    $products[$index] = $record + $products[$index];
} else {
    $products[] = $record;
}

if (!save_products($products)) {
    delete_uploaded_image($uploaded);
    fail_back($formUrl, 'Gagal menyimpan data menu ke server. Pastikan folder data/ bisa ditulis.', $input);
}
if ($uploaded !== null && $oldImage !== '') {
    delete_uploaded_image($oldImage);
}

flash('success', $isEdit ? 'Menu "' . $name . '" berhasil diperbarui.' : 'Menu "' . $name . '" berhasil ditambahkan.');
redirect('index.php');
