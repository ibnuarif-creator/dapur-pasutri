<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// Keep-alive for open portal forms (portal/js/portal.js pings every few minutes).
// 204 = session still valid, 401 = logged out, so the page can warn before the admin saves.
header('Cache-Control: no-store');
http_response_code(is_logged_in() ? 204 : 401);
