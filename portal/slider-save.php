<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_feature(FEATURE_SLIDER);
require_post('slider.php');

// Handles action=add (multi-upload), action=delete and action=move for slider photos.
$action = post_str('action');
$photos = load_slider();

if ($action === 'add') {
    $files = normalize_multi_upload($_FILES['photos'] ?? null);
    if (!$files) {
        fail_back('slider.php', 'Pilih foto terlebih dahulu.');
    }
    $slotsLeft = SLIDER_MAX_PHOTOS - count($photos);
    if ($slotsLeft <= 0) {
        fail_back('slider.php', 'Slider sudah berisi ' . SLIDER_MAX_PHOTOS . ' foto. Hapus salah satu sebelum menambah.');
    }
    if (count($files) > $slotsLeft) {
        fail_back('slider.php', 'Maksimal ' . SLIDER_MAX_PHOTOS . ' foto. Anda hanya bisa menambah ' . $slotsLeft . ' foto lagi.');
    }

    $added = [];
    try {
        foreach ($files as $file) {
            $added[] = handle_image_upload($file);
        }
    } catch (RuntimeException $e) {
        // Don't leave half of a batch behind on the server.
        foreach ($added as $path) {
            delete_uploaded_image($path);
        }
        fail_back('slider.php', $e->getMessage());
    }

    foreach ($added as $path) {
        $photos[] = ['id' => 'slide-' . bin2hex(random_bytes(4)), 'image' => $path];
    }
    if (!save_slider($photos)) {
        foreach ($added as $path) {
            delete_uploaded_image($path);
        }
        fail_back('slider.php', 'Gagal menyimpan data slider. Pastikan folder data/ bisa ditulis.');
    }
    flash('success', count($added) . ' foto berhasil ditambahkan ke slider.');
    redirect('slider.php');
}

$index = find_index_by_id($photos, post_str('id'));
if ($index === null) {
    fail_back('slider.php', 'Foto tidak ditemukan.');
}

if ($action === 'delete') {
    if (count($photos) <= 1) {
        fail_back('slider.php', 'Slider harus berisi minimal 1 foto.');
    }
    $removed = $photos[$index]['image'] ?? null;
    array_splice($photos, $index, 1);
    if (!save_slider($photos)) {
        fail_back('slider.php', 'Gagal menghapus foto.');
    }
    delete_uploaded_image($removed);
    flash('success', 'Foto berhasil dihapus dari slider.');
    redirect('slider.php');
}

if ($action === 'move') {
    if (move_item($photos, $index, post_str('direction')) && !save_slider($photos)) {
        fail_back('slider.php', 'Gagal menyimpan urutan foto.');
    }
    redirect('slider.php');
}

fail_back('slider.php', 'Aksi tidak dikenal.');
