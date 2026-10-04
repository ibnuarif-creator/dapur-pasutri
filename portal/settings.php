<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_feature((bool) SETTINGS_IMAGES);

$settings = load_settings();

portal_header('Tampilan', 'tampilan');
?>
    <div class="page-header">
      <div>
        <h1>Tampilan</h1>
        <p class="muted">Foto-foto tunggal di halaman utama.</p>
      </div>
    </div>

    <?php foreach (SETTINGS_IMAGES as $key => [$label, $hint, $default]): ?>
      <section class="card">
        <h2><?= e($label) ?></h2>
        <p class="hint"><?= e($hint) ?></p>
        <form method="post" action="settings-save.php" enctype="multipart/form-data" class="form">
          <?= csrf_field() ?>
          <input type="hidden" name="key" value="<?= e($key) ?>">
          <img class="preview-wide" src="<?= e(admin_image_src($settings[$key])) ?>" alt="<?= e($label) ?> saat ini">
          <div class="field">
            <label for="img-<?= e($key) ?>">Ganti Foto</label>
            <input type="file" id="img-<?= e($key) ?>" name="image" accept="image/jpeg,image/png,image/webp" data-image-input required>
            <p class="hint">JPG, PNG, atau WEBP. Foto besar otomatis diperkecil sebelum diunggah.</p>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Foto</button>
            <?php if ($settings[$key] !== $default): ?>
              <button type="submit" name="reset" value="1" class="btn btn-ghost" formnovalidate
                      onclick="return confirm('Kembalikan ke foto bawaan?');">Kembalikan ke Bawaan</button>
            <?php endif; ?>
          </div>
        </form>
      </section>
    <?php endforeach; ?>
<?php portal_footer(); ?>
