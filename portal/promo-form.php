<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_feature(FEATURE_PROMOS);

$promos = load_promos();
$editId = (string) ($_GET['id'] ?? '');
$index = $editId !== '' ? find_index_by_id($promos, $editId) : null;
if ($editId !== '' && $index === null) {
    flash('error', 'Promo tidak ditemukan.');
    redirect('promos.php');
}
$isEdit = $index !== null;
$item = $isEdit ? $promos[$index] : ['active' => true];

$old = take_old_input();
if ($old !== null) {
    $item = array_merge($item, $old);
}

portal_header($isEdit ? 'Edit Promo' : 'Tambah Promo', 'promo');
?>
    <div class="page-header">
      <h1><?= $isEdit ? 'Edit Promo' : 'Tambah Promo' ?></h1>
      <a class="btn btn-ghost" href="promos.php">&larr; Kembali</a>
    </div>

    <section class="card">
      <form method="post" action="promo-save.php" enctype="multipart/form-data" class="form" data-keepalive>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($isEdit ? $promos[$index]['id'] : '') ?>">

        <div class="field">
          <label for="title">Judul Promo</label>
          <input type="text" id="title" name="title" required maxlength="<?= PROMO_TITLE_MAX ?>" value="<?= e($item['title'] ?? '') ?>"
                 placeholder="Contoh: DISKON 20% AKHIR BULAN!">
        </div>

        <div class="field">
          <label for="desc">Deskripsi</label>
          <textarea id="desc" name="desc" required rows="6" maxlength="<?= PROMO_DESC_MAX ?>" data-counter="desc-counter"><?= e($item['desc'] ?? '') ?></textarea>
          <p class="hint char-counter" id="desc-counter"></p>
          <p class="hint">Baris kosong (Enter dua kali) menjadi paragraf baru di website.</p>
        </div>

        <label class="check">
          <input type="checkbox" name="active" value="1"<?= !empty($item['active']) ? ' checked' : '' ?>>
          Aktif &mdash; tampilkan promo ini di website
        </label>

        <div class="field">
          <label for="image"><?= $isEdit ? 'Ganti Gambar (opsional)' : 'Gambar Promo' ?></label>
          <?php if ($isEdit && !empty($promos[$index]['image'])): ?>
            <div class="current-photo">
              <img src="<?= e(admin_image_src($promos[$index]['image'])) ?>" alt="Gambar saat ini">
              <span class="hint">Gambar saat ini. Kosongkan pilihan file untuk tetap memakai gambar ini.</span>
            </div>
          <?php endif; ?>
          <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" data-image-input<?= $isEdit ? '' : ' required' ?>>
          <?php if ($old !== null && !$isEdit): ?>
            <p class="hint hint-warn">Gambar perlu dipilih ulang setelah ada kesalahan.</p>
          <?php endif; ?>
          <p class="hint">JPG, PNG, atau WEBP. Gambar besar otomatis diperkecil sebelum diunggah.</p>
        </div>

        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Promo' ?></button>
      </form>
    </section>

<?php portal_footer(); ?>
