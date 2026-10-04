<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();

$products = load_products();
$editId = (string) ($_GET['id'] ?? '');
$index = $editId !== '' ? find_index_by_id($products, $editId) : null;
if ($editId !== '' && $index === null) {
    flash('error', 'Menu tidak ditemukan.');
    redirect('index.php');
}
$isEdit = $index !== null;
$item = $isEdit ? $products[$index] : [];

// After a failed save, show what the admin typed instead of the stored values.
$old = take_old_input();
if ($old !== null) {
    $item = array_merge($item, $old);
}

$categories = [];
foreach ($products as $p) {
    if (!empty($p['category']) && !in_array($p['category'], $categories, true)) {
        $categories[] = $p['category'];
    }
}

$price = $item['price'] ?? null;
$noPrice = $old !== null ? !empty($old['no_price']) : ($isEdit && $price === null);

portal_header($isEdit ? 'Edit Menu' : 'Tambah Menu', 'menu');
?>
    <div class="page-header">
      <h1><?= $isEdit ? 'Edit Menu' : 'Tambah Menu' ?></h1>
      <a class="btn btn-ghost" href="index.php">&larr; Kembali</a>
    </div>

    <section class="card">
      <form method="post" action="product-save.php" enctype="multipart/form-data" class="form" data-keepalive>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($isEdit ? $products[$index]['id'] : '') ?>">

        <div class="form-grid">
          <div class="field">
            <label for="name">Nama Menu</label>
            <input type="text" id="name" name="name" required maxlength="<?= PRODUCT_NAME_MAX ?>" value="<?= e($item['name'] ?? '') ?>">
          </div>
          <?php if (FEATURE_CATEGORY): ?>
          <div class="field">
            <label for="category">Kategori</label>
            <input type="text" id="category" name="category" list="category-list" required maxlength="40" value="<?= e($item['category'] ?? '') ?>">
            <datalist id="category-list">
              <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
            </datalist>
            <p class="hint">Pilih kategori yang sudah ada atau ketik yang baru.</p>
          </div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="price">Harga (Rp)</label>
          <!-- type="text", not "number": number inputs reject "25.000"/"25,000" or read
               them as 25, and step= blocks prices like 22750. The server parses it. -->
          <input type="text" id="price" name="price" inputmode="numeric" autocomplete="off"
                 placeholder="contoh: 25000 atau 25.000" data-price-input="price-preview"
                 value="<?= $noPrice ? '' : e($price) ?>"<?= $noPrice ? ' disabled' : ' required' ?>>
          <p class="hint price-preview" id="price-preview" aria-live="polite"></p>
          <label class="check">
            <input type="checkbox" id="no_price" name="no_price" value="1" data-no-price="price"<?= $noPrice ? ' checked' : '' ?>>
            Tanpa harga tetap (website menampilkan &ldquo;Hubungi Kami&rdquo;)
          </label>
        </div>

        <div class="field">
          <label for="desc">Deskripsi Singkat</label>
          <textarea id="desc" name="desc" required rows="3" maxlength="<?= PRODUCT_DESC_MAX ?>" data-counter="desc-counter"><?= e($item['desc'] ?? '') ?></textarea>
          <p class="hint char-counter" id="desc-counter"></p>
        </div>

        <?php if (PRODUCT_LINKS): ?>
        <h2 class="form-section">Tombol Pemesanan</h2>
        <p class="hint">Semua link opsional. Link toko yang dikosongkan tidak tampil ikonnya. Tombol WhatsApp selalu tampil &mdash; bila dikosongkan, memakai nomor WhatsApp utama (<?= e(WHATSAPP_NUMBER) ?>).</p>
        <?php foreach (PRODUCT_LINKS as $key => $label): ?>
          <div class="field">
            <label for="link-<?= e($key) ?>">Link <?= e($label) ?></label>
            <input type="text" id="link-<?= e($key) ?>" name="links[<?= e($key) ?>]" inputmode="url"
                   placeholder="<?= $key === 'whatsapp' ? 'https://wa.me/' . e(WHATSAPP_NUMBER) : 'https://...' ?>"
                   value="<?= e($item[$key] ?? '') ?>">
            <?php if ($key === 'whatsapp'): ?>
              <p class="hint">Link wa.me tanpa teks pesan otomatis diberi pesan berisi nama menu.</p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (FEATURE_FEATURED): ?>
        <label class="check">
          <input type="checkbox" name="featured" value="1"<?= !empty($item['featured']) ? ' checked' : '' ?>>
          Tampilkan sebagai <strong>menu unggulan</strong>
        </label>
        <p class="hint">Tidak dicentang = menu tetap tampil, di bagian &ldquo;Menu Lainnya&rdquo;.</p>
        <?php endif; ?>

        <div class="field">
          <label for="image"><?= $isEdit ? 'Ganti Foto (opsional)' : 'Foto Menu' ?></label>
          <?php if ($isEdit && !empty($products[$index]['image'])): ?>
            <div class="current-photo">
              <img src="<?= e(admin_image_src($products[$index]['image'])) ?>" alt="Foto saat ini">
              <span class="hint">Foto saat ini. Kosongkan pilihan file untuk tetap memakai foto ini.</span>
            </div>
          <?php endif; ?>
          <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" data-image-input<?= $isEdit ? '' : ' required' ?>>
          <?php if ($old !== null && !$isEdit): ?>
            <p class="hint hint-warn">Foto perlu dipilih ulang setelah ada kesalahan.</p>
          <?php endif; ?>
          <p class="hint">JPG, PNG, atau WEBP. Foto besar dari HP otomatis diperkecil sebelum diunggah. Foto persegi (1:1) paling rapi.</p>
        </div>

        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Menu' ?></button>
      </form>
    </section>
<?php portal_footer(); ?>
