<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();

$products = load_products();
$count = count($products);
$missingPhotos = count(array_filter($products, function ($p) { return !site_image_exists($p['image'] ?? ''); }));

portal_header('Kelola Menu', 'menu');
?>
    <div class="page-header">
      <div>
        <h1>Kelola Menu (<?= $count ?>)</h1>
        <p class="muted">Urutan di sini = urutan tampil di website.</p>
      </div>
      <a class="btn btn-primary" href="product-form.php">+ Tambah Menu</a>
    </div>

    <?php if ($missingPhotos): ?>
      <div class="flash flash-error"><?= $missingPhotos ?> menu fotonya tidak ditemukan di server, sehingga tidak tampil benar di website. Edit menu bertanda merah lalu unggah ulang fotonya.</div>
    <?php endif; ?>

    <?php if (!$products): ?>
      <div class="card empty">Belum ada menu. Klik <strong>+ Tambah Menu</strong> untuk menambahkan menu pertama.</div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($products as $i => $p): ?>
          <article class="list-row">
            <div class="list-order"><?php reorder_buttons('product-action.php', (string) $p['id'], $i, $count); ?></div>
            <img class="thumb" src="<?= e(admin_image_src($p['image'] ?? '')) ?>" alt="">
            <div class="list-info">
              <strong><?= e($p['name'] ?? '') ?></strong>
              <span class="muted"><?= e($p['desc'] ?? '') ?></span>
              <span class="tags">
                <span class="tag tag-price"><?= isset($p['price']) && $p['price'] !== null ? e(format_rupiah((int) $p['price'])) : 'Tanya harga' ?></span>
                <?php if (FEATURE_CATEGORY && !empty($p['category'])): ?><span class="tag"><?= e($p['category']) ?></span><?php endif; ?>
                <?php if (FEATURE_FEATURED): ?>
                  <span class="tag<?= !empty($p['featured']) ? ' tag-accent' : '' ?>"><?= !empty($p['featured']) ? 'Unggulan' : 'Menu Lainnya' ?></span>
                <?php endif; ?>
                <?php if (!site_image_exists($p['image'] ?? '')): ?><span class="tag tag-warn">Foto tidak ditemukan</span><?php endif; ?>
              </span>
            </div>
            <div class="list-actions">
              <a class="btn btn-ghost btn-sm" href="product-form.php?id=<?= urlencode((string) $p['id']) ?>">Edit</a>
              <?php delete_button('product-action.php', (string) $p['id'], 'Hapus menu "' . ($p['name'] ?? '') . '"? Tindakan ini tidak bisa dibatalkan.'); ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
<?php portal_footer(); ?>
