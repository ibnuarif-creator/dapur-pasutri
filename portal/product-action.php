<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_post('index.php');

// Handles action=delete and action=move (direction=up|down) for products.
$products = load_products();
$index = find_index_by_id($products, post_str('id'));
if ($index === null) {
    fail_back('index.php', 'Menu tidak ditemukan.');
}

$action = post_str('action');

if ($action === 'delete') {
    $removed = $products[$index];
    array_splice($products, $index, 1);
    if (!save_products($products)) {
        fail_back('index.php', 'Gagal menghapus menu.');
    }
    delete_uploaded_image($removed['image'] ?? null);
    flash('success', 'Menu "' . ($removed['name'] ?? '') . '" berhasil dihapus.');
    redirect('index.php');
}

if ($action === 'move') {
    if (move_item($products, $index, post_str('direction')) && !save_products($products)) {
        fail_back('index.php', 'Gagal menyimpan urutan menu.');
    }
    redirect('index.php');
}

fail_back('index.php', 'Aksi tidak dikenal.');
