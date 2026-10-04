<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_feature(FEATURE_PROMOS);
require_post('promos.php');

// Handles action=delete, action=toggle (active on/off) and action=move for promos.
$promos = load_promos();
$index = find_index_by_id($promos, post_str('id'));
if ($index === null) {
    fail_back('promos.php', 'Promo tidak ditemukan.');
}

$action = post_str('action');

if ($action === 'delete') {
    $removed = $promos[$index];
    array_splice($promos, $index, 1);
    if (!save_promos($promos)) {
        fail_back('promos.php', 'Gagal menghapus promo.');
    }
    delete_uploaded_image($removed['image'] ?? null);
    flash('success', 'Promo berhasil dihapus.');
    redirect('promos.php');
}

if ($action === 'toggle') {
    $nowActive = ($promos[$index]['active'] ?? true) === false;
    $promos[$index]['active'] = $nowActive;
    if (!save_promos($promos)) {
        fail_back('promos.php', 'Gagal mengubah status promo.');
    }
    flash('success', $nowActive ? 'Promo diaktifkan dan tampil di website.' : 'Promo dinonaktifkan dan disembunyikan dari website.');
    redirect('promos.php');
}

if ($action === 'move') {
    if (move_item($promos, $index, post_str('direction')) && !save_promos($promos)) {
        fail_back('promos.php', 'Gagal menyimpan urutan promo.');
    }
    redirect('promos.php');
}

fail_back('promos.php', 'Aksi tidak dikenal.');
