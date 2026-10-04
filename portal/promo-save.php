<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_feature(FEATURE_PROMOS);

$id = post_str('id');
$formUrl = 'promo-form.php' . ($id !== '' ? '?id=' . urlencode($id) : '');
require_post($formUrl);

$promos = load_promos();
$index = $id !== '' ? find_index_by_id($promos, $id) : null;
if ($id !== '' && $index === null) {
    fail_back('promos.php', 'Promo tidak ditemukan. Mungkin sudah dihapus.');
}
$isEdit = $index !== null;

$title = post_str('title');
$desc = str_replace("\r\n", "\n", post_str('desc'));
$active = !empty($_POST['active']);
$input = ['title' => $title, 'desc' => $desc, 'active' => $active];

if ($title === '' || $desc === '') {
    fail_back($formUrl, 'Judul dan deskripsi wajib diisi.', $input);
}
if (str_length($title) > PROMO_TITLE_MAX) {
    fail_back($formUrl, 'Judul maksimal ' . PROMO_TITLE_MAX . ' karakter.', $input);
}
if (str_length($desc) > PROMO_DESC_MAX) {
    fail_back($formUrl, 'Deskripsi maksimal ' . PROMO_DESC_MAX . ' karakter.', $input);
}

try {
    $uploaded = handle_image_upload($_FILES['image'] ?? []);
} catch (RuntimeException $e) {
    fail_back($formUrl, $e->getMessage(), $input);
}
if (!$isEdit && $uploaded === null) {
    fail_back($formUrl, 'Gambar promo wajib diunggah.', $input);
}

$oldImage = $isEdit ? ($promos[$index]['image'] ?? '') : '';
$record = [
    'id'     => $isEdit ? $promos[$index]['id'] : generate_id($title, $promos, 'promo'),
    'title'  => $title,
    'desc'   => $desc,
    'image'  => $uploaded ?? $oldImage,
    'active' => $active,
];

if ($isEdit) {
    $promos[$index] = $record + $promos[$index];
} else {
    $promos[] = $record;
}

if (!save_promos($promos)) {
    delete_uploaded_image($uploaded);
    fail_back($formUrl, 'Gagal menyimpan data promo ke server. Pastikan folder data/ bisa ditulis.', $input);
}
if ($uploaded !== null && $oldImage !== '') {
    delete_uploaded_image($oldImage);
}

flash('success', $isEdit ? 'Promo berhasil diperbarui.' : 'Promo berhasil ditambahkan.');
redirect('promos.php');
