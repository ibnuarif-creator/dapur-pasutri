<?php
declare(strict_types=1);

/* Failed-login lockout per IP, stored in includes/ratelimit.json. */

function ratelimit_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function ratelimit_seconds_remaining(): int
{
    $data = read_json(RATELIMIT_FILE);
    $lockedUntil = (int) ($data[ratelimit_ip()]['lockedUntil'] ?? 0);
    return max(0, $lockedUntil - time());
}

function ratelimit_is_locked(): bool
{
    return ratelimit_seconds_remaining() > 0;
}

function ratelimit_register_failure(): void
{
    $data = read_json(RATELIMIT_FILE);
    $now = time();
    // Drop expired entries so the file doesn't grow forever.
    foreach ($data as $ip => $entry) {
        if ((int) ($entry['lockedUntil'] ?? 0) < $now && (int) ($entry['last'] ?? 0) < $now - LOGIN_LOCKOUT_SECONDS) {
            unset($data[$ip]);
        }
    }

    $ip = ratelimit_ip();
    $entry = $data[$ip] ?? ['count' => 0, 'lockedUntil' => 0, 'last' => 0];
    $entry['count'] = (int) $entry['count'] + 1;
    $entry['last'] = $now;
    if ($entry['count'] >= LOGIN_MAX_ATTEMPTS) {
        $entry['lockedUntil'] = $now + LOGIN_LOCKOUT_SECONDS;
        $entry['count'] = 0;
    }
    $data[$ip] = $entry;
    write_json(RATELIMIT_FILE, $data, 0600);
}

function ratelimit_reset(): void
{
    $data = read_json(RATELIMIT_FILE);
    $ip = ratelimit_ip();
    if (isset($data[$ip])) {
        unset($data[$ip]);
        write_json(RATELIMIT_FILE, $data, 0600);
    }
}
