<?php
declare(strict_types=1);

/* Every portal page starts with: require __DIR__ . '/../includes/bootstrap.php'; */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ratelimit.php';
require_once __DIR__ . '/layout.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

auth_bootstrap();
