<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_feature(FEATURE_PROMOS);

$promos = load_promos();
$count = count($promos);
$activeCount = count(array_filter($promos, function ($p) { return ($p['active'] ?? true) !== false; }));

portal_header('Kelola Promo', 'promo');
?>
    <div class="page-header">
      <div>
        <h1>Kelola Promo (<?= $count ?>)</h1>
        <p class="muted">
          <?= $activeCount ? $activeCount . ' promo aktif tampil di website.' : 'Tidak ada promo aktif &mdash; bagian Promo otomatis disembunyikan dari website.' ?>
        </p>
      </div>
      <a class="btn btn-primary" href="promo-form.php">+ Tambah Promo</a>
    </div>

    <?php if (!$promos): ?>
      <div class="card empty">Belum ada promo. Klik <strong>+ Tambah Promo</strong> untuk membuat promo pertama.</div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($promos as $i => $p): ?>
          <?php $isActive = ($p['active'] ?? true) !== false; ?>
          <article class="list-row<?= $isActive ? '' : ' is-muted' ?>">
            <div class="list-order"><?php reorder_buttons('promo-action.php', (string) $p['id'], $i, $count); ?></div>
            <img class="thumb thumb-wide" src="<?= e(admin_image_src($p['image'] ?? '')) ?>" alt="">
            <div class="list-info">
              <strong><?= e($p['title'] ?? '') ?></strong>
              <span class="muted clamp"><?= e($p['desc'] ?? '') ?></span>
              <span class="tags">
                <span class="tag <?= $isActive ? 'tag-success' : '' ?>"><?= $isActive ? 'Aktif' : 'Nonaktif' ?></span>
              </span>
            </div>
            <div class="list-actions">
              <form method="post" action="promo-action.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
              </form>
              <a class="btn btn-ghost btn-sm" href="promo-form.php?id=<?= urlencode((string) $p['id']) ?>">Edit</a>
              <?php delete_button('promo-action.php', (string) $p['id'], 'Hapus promo "' . ($p['title'] ?? '') . '"? Tindakan ini tidak bisa dibatalkan.'); ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
<?php portal_footer(); ?>
