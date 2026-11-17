<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/format.php';
require_once __DIR__ . '/../lib/flash.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/validate.php';
require_once __DIR__ . '/../lib/options.php';
require_once __DIR__ . '/../lib/aircraft.php';
require_once __DIR__ . '/../lib/gemini.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}
