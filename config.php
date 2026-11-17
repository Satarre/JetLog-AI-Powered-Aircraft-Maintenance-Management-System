<?php

declare(strict_types=1);

define('APP_NAME', 'JetLog');
define('BASE_URL', '/JetLog');
define('APP_TIMEZONE', 'Asia/Manila');

date_default_timezone_set(APP_TIMEZONE);

// Local settings and secrets. Not committed to version control.
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'jetlog');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', '');
}

if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', 'gemini-3.6-flash');
}
