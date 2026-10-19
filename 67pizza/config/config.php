<?php
/**
 * 67pizza – Application configuration
 * Edit the values below to match your local environment.
 */
declare(strict_types=1);

// ---- Core app settings ---------------------------------------------------
define('APP_NAME', '67pizza');
define('APP_ENV', 'local');                 // local | production
define('APP_DEBUG', APP_ENV === 'local');   // show detailed errors locally only
// define('BASE_URL', '/67pizza');  
define('BASE_URL', '/67pizza-food-ordering-website/67pizza');
           // URL path under http://localhost (no trailing slash)
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOAD_URL', BASE_URL . '/assets/uploads');
define('DEFAULT_TIMEZONE', 'Asia/Phnom_Penh');

// ---- Security ------------------------------------------------------------
// Secret used by admin/setup-admin.php. Change it, run the setup once, then set ADMIN_SETUP_ENABLED to false.
define('ADMIN_SETUP_ENABLED', true);
define('ADMIN_SETUP_KEY', 'nang7858');
define('MAX_LOGIN_ATTEMPTS', 5);            // attempts before temporary lock
define('LOGIN_LOCK_MINUTES', 0);
define('SESSION_NAME', 'sixty7pizza_sid');
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB
// In local development there is no mail server; password reset links are shown on screen.
define('DEV_SHOW_RESET_LINK', APP_ENV === 'local');

// ---- Runtime -------------------------------------------------------------
date_default_timezone_set(DEFAULT_TIMEZONE);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

// ---- Secure session ------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

require_once __DIR__ . '/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/includes/validation.php';
require_once ROOT_PATH . '/includes/auth.php';
