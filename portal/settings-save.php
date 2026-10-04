<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_feature((bool) SETTINGS_IMAGES);
require_post('settings.php');

$key = post_str('key');
if (!isset(SETTINGS_IMAGES[$key])) {
    fail_back('settings.php', 'Pengaturan tidak dikenal.');
}
[$label, , $default] = SETTINGS_IMAGES[$key];

$settings = load_settings();
$oldImage = $settings[$key];

if (!empty($_POST['reset'])) {
    $settings[$key] = $default;
    $uploaded = null;
} else {
    try {
        $uploaded = handle_image_upload($_FILES['image'] ?? []);
    } catch (RuntimeException $e) {
        fail_back('settings.php', $e->getMessage());
    }
    if ($uploaded === null) {
        fail_back('settings.php', 'Pilih foto terlebih dahulu.');
    }
    $settings[$key] = $uploaded;
}

if (!save_settings($settings)) {
    delete_uploaded_image($uploaded);
    fail_back('settings.php', 'Gagal menyimpan pengaturan. Pastikan folder data/ bisa ditulis.');
}
if ($oldImage !== $settings[$key]) {
    delete_uploaded_image($oldImage);
}

flash('success', $label . ' berhasil diperbarui.');
redirect('settings.php');
