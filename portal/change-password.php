<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('change-password.php');
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($new) < 8) {
        fail_back('change-password.php', 'Password baru minimal 8 karakter.');
    }
    if ($new !== $confirm) {
        fail_back('change-password.php', 'Konfirmasi password baru tidak cocok.');
    }
    if (!change_password($current, $new)) {
        fail_back('change-password.php', 'Password saat ini salah.');
    }
    flash('success', 'Password berhasil diganti.');
    redirect('index.php');
}

portal_header('Ganti Password');
?>
    <div class="page-header">
      <h1>Ganti Password</h1>
      <a class="btn btn-ghost" href="index.php">&larr; Kembali</a>
    </div>

    <section class="card card-narrow">
      <form method="post" class="form">
        <?= csrf_field() ?>
        <div class="field">
          <label for="current_password">Password Saat Ini</label>
          <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="field">
          <label for="new_password">Password Baru</label>
          <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
          <p class="hint">Minimal 8 karakter.</p>
        </div>
        <div class="field">
          <label for="confirm_password">Konfirmasi Password Baru</label>
          <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
      </form>
    </section>
<?php portal_footer(); ?>
