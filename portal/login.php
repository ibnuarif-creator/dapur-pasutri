<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

// First visit (no credentials.json yet): this page creates the admin account instead.
$isSetup = !has_account();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post_str('username');
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify()) {
        $error = 'Sesi kedaluwarsa, silakan coba lagi.';
    } elseif ($isSetup) {
        $confirm = (string) ($_POST['confirm'] ?? '');
        if (str_length($username) < 3) {
            $error = 'Username minimal 3 karakter.';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($password !== $confirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (!create_account($username, $password)) {
            $error = 'Gagal menyimpan akun. Pastikan folder includes/ bisa ditulis server.';
        } else {
            login_user($username);
            flash('success', 'Akun berhasil dibuat. Selamat datang!');
            redirect('index.php');
        }
    } elseif (ratelimit_is_locked()) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . (int) ceil(ratelimit_seconds_remaining() / 60) . ' menit.';
    } elseif (verify_credentials($username, $password)) {
        ratelimit_reset();
        login_user($username);
        redirect('index.php');
    } else {
        ratelimit_register_failure();
        $error = ratelimit_is_locked()
            ? 'Terlalu banyak percobaan gagal. Login dikunci ' . (int) (LOGIN_LOCKOUT_SECONDS / 60) . ' menit.'
            : 'Username atau password salah.';
    }
}

portal_head($isSetup ? 'Buat Akun' : 'Masuk', 'portal-auth');
?>
  <main class="auth-card">
    <h1><?= $isSetup ? 'Buat Akun Pengelola' : 'Portal ' . e(SITE_NAME) ?></h1>
    <p class="muted">
      <?= $isSetup
        ? 'Ini akses pertama. Buat username &amp; password untuk mengelola konten website.'
        : 'Khusus tim internal ' . e(SITE_NAME) . '.' ?>
    </p>

    <?php portal_flash(); ?>
    <?php if ($error): ?>
      <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form">
      <?= csrf_field() ?>
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus autocomplete="username"
               <?= $isSetup ? 'minlength="3"' : '' ?> value="<?= e(post_str('username')) ?>">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required
               <?= $isSetup ? 'minlength="8" autocomplete="new-password"' : 'autocomplete="current-password"' ?>>
      </div>
      <?php if ($isSetup): ?>
        <div class="field">
          <label for="confirm">Konfirmasi Password</label>
          <input type="password" id="confirm" name="confirm" required minlength="8" autocomplete="new-password">
        </div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary btn-block"><?= $isSetup ? 'Buat Akun &amp; Masuk' : 'Masuk' ?></button>
    </form>

    <a class="btn btn-ghost btn-block" href="../">&larr; Kembali ke Halaman Utama</a>
  </main>
  <script src="js/portal.js"></script>
</body>
</html>
