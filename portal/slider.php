<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_feature(FEATURE_SLIDER);

$slides = load_slider();
$count = count($slides);
$slotsLeft = SLIDER_MAX_PHOTOS - $count;

portal_header('Foto Slider', 'slider');
?>
    <div class="page-header">
      <div>
        <h1>Foto Slider (<?= $count ?>/<?= SLIDER_MAX_PHOTOS ?>)</h1>
        <p class="muted">Foto yang berganti otomatis di website. Foto paling atas tampil pertama.</p>
      </div>
    </div>

    <section class="card">
      <h2>Tambah Foto</h2>
      <?php if ($slotsLeft > 0): ?>
        <form method="post" action="slider-save.php" enctype="multipart/form-data" class="form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <div class="field">
            <label for="photos">Pilih foto (bisa lebih dari satu, sisa <?= $slotsLeft ?> slot)</label>
            <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-image-input required>
            <p class="hint">Gunakan foto mendatar (landscape) agar tidak banyak terpotong. Foto besar otomatis diperkecil sebelum diunggah.</p>
          </div>
          <button type="submit" class="btn btn-primary">Tambah ke Slider</button>
        </form>
      <?php else: ?>
        <p class="muted">Slider sudah berisi <?= SLIDER_MAX_PHOTOS ?> foto (batas maksimal). Hapus salah satu foto di bawah untuk menambah foto baru.</p>
      <?php endif; ?>
    </section>

    <div class="list">
      <?php foreach ($slides as $i => $s): ?>
        <article class="list-row">
          <div class="list-order"><?php reorder_buttons('slider-save.php', (string) $s['id'], $i, $count); ?></div>
          <img class="thumb thumb-wide" src="<?= e(admin_image_src($s['image'])) ?>" alt="Foto slider <?= $i + 1 ?>">
          <div class="list-info">
            <strong>Posisi <?= $i + 1 ?></strong>
            <?php if ($i === 0): ?><span class="muted">Tampil pertama</span><?php endif; ?>
          </div>
          <div class="list-actions">
            <?php delete_button('slider-save.php', (string) $s['id'], 'Hapus foto ini dari slider?', $count <= 1, 'Slider harus berisi minimal 1 foto'); ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
<?php portal_footer(); ?>
