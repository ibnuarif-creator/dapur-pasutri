<?php
declare(strict_types=1);

/* Shared chrome for portal pages: <head>, topbar, tabs, flash, footer. */

/** Tabs shown in the portal, filtered by the FEATURE_* switches in config.php. */
function portal_tabs(): array
{
    $tabs = ['menu' => ['index.php', 'Menu']];
    if (FEATURE_PROMOS) {
        $tabs['promo'] = ['promos.php', 'Promo'];
    }
    if (SETTINGS_IMAGES) {
        $tabs['tampilan'] = ['settings.php', 'Tampilan'];
    }
    if (FEATURE_SLIDER) {
        $tabs['slider'] = ['slider.php', 'Foto Slider'];
    }
    return $tabs;
}

function portal_head(string $title, string $bodyClass = 'portal-app'): void
{
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Portal <?= e(SITE_NAME) ?></title>
<link rel="icon" href="../images/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="css/admin.css">
</head>
<body class="<?= e($bodyClass) ?>" data-upload-max="<?= upload_limit_bytes() ?>" data-post-max="<?= ini_bytes('post_max_size') ?>">
    <?php
}

/** Topbar + tabs + flash. $activeTab is a key of portal_tabs(), or '' for none. */
function portal_header(string $title, string $activeTab = ''): void
{
    portal_head($title);
    ?>
  <header class="topbar">
    <div class="topbar-brand">
      <strong>Portal <?= e(SITE_NAME) ?></strong>
      <span>Masuk sebagai <?= e(current_user()) ?></span>
    </div>
    <nav class="topbar-actions">
      <a href="../" target="_blank" rel="noopener">Lihat Situs ↗</a>
      <a href="change-password.php">Ganti Password</a>
      <a href="logout.php">Keluar</a>
    </nav>
  </header>

  <main class="wrap">
    <?php if ($activeTab !== ''): ?>
    <nav class="tabs" aria-label="Bagian portal">
      <?php foreach (portal_tabs() as $key => [$href, $label]): ?>
        <a href="<?= e($href) ?>"<?= $key === $activeTab ? ' class="is-active" aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php portal_flash(); ?>
    <?php
}

function portal_flash(): void
{
    $flash = take_flash();
    if ($flash) {
        echo '<div class="flash flash-' . e($flash['type']) . '" role="status">' . e($flash['message']) . '</div>';
    }
}

function portal_footer(): void
{
    ?>
  </main>
  <script src="js/portal.js"></script>
</body>
</html>
    <?php
}

/** ▲▼ reorder buttons that POST to $action with the given id. */
function reorder_buttons(string $action, string $id, int $index, int $count, array $extra = []): void
{
    foreach (['up' => ['▲', 'Naikkan urutan', $index === 0], 'down' => ['▼', 'Turunkan urutan', $index === $count - 1]] as $dir => [$icon, $label, $disabled]) {
        ?>
        <form method="post" action="<?= e($action) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="move">
          <input type="hidden" name="direction" value="<?= $dir ?>">
          <input type="hidden" name="id" value="<?= e($id) ?>">
          <?php foreach ($extra as $name => $value): ?>
            <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
          <?php endforeach; ?>
          <button type="submit" class="btn btn-ghost btn-icon" title="<?= $label ?>" aria-label="<?= $label ?>"<?= $disabled ? ' disabled' : '' ?>><?= $icon ?></button>
        </form>
        <?php
    }
}

/** Delete button with a confirm() prompt that POSTs action=delete. */
function delete_button(string $action, string $id, string $confirmText, bool $disabled = false, string $disabledTitle = ''): void
{
    ?>
    <form method="post" action="<?= e($action) ?>" onsubmit="return confirm(<?= e(json_encode($confirmText, JSON_UNESCAPED_UNICODE)) ?>);">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= e($id) ?>">
      <button type="submit" class="btn btn-danger btn-sm"<?= $disabled ? ' disabled title="' . e($disabledTitle) . '"' : '' ?>>Hapus</button>
    </form>
    <?php
}
