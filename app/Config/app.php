<?php
/**
 * Sociaera — Ana Uygulama Yapılandırması
 * Tüm sabitler, session ayarları ve bootstrap burada.
 */

// Hata raporlama — geçici olarak açık (debug)
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// ── Sabitler ──────────────────────────────────────────────
define('APP_NAME',    'Sociaera');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     env('APP_ENV', 'production'));

// Dizin yolları
define('ROOT_PATH',    dirname(__DIR__, 2));
define('APP_PATH',     ROOT_PATH . '/app');
define('PUBLIC_PATH',  ROOT_PATH . '/public');
define('UPLOAD_PATH',  ROOT_PATH . '/uploads');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Base URL (trailing slash yok)
// .env yoksa sunucu ortamından otomatik algıla
$envBaseUrl = env('BASE_URL', null);
if ($envBaseUrl === null) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $envBaseUrl = $scheme . '://' . $host;
}
$baseUrl = rtrim($envBaseUrl, '/');
define('BASE_URL', $baseUrl);

// Timezone
$timezone = env('APP_TIMEZONE', 'Europe/Istanbul');
date_default_timezone_set($timezone);
define('APP_TIMEZONE', $timezone);

// Upload limitleri (byte)
define('MAX_AVATAR_SIZE',  2 * 1024 * 1024);   // 2 MB
define('MAX_BANNER_SIZE',  5 * 1024 * 1024);   // 5 MB
define('MAX_POST_SIZE',    5 * 1024 * 1024);   // 5 MB
define('MAX_AD_SIZE',      5 * 1024 * 1024);   // 5 MB

// Upload boyut limitleri (px)
define('AVATAR_MAX_W', 400);
define('AVATAR_MAX_H', 400);
define('BANNER_MAX_W', 1500);
define('BANNER_MAX_H', 500);

// OAuth
define('OAUTH_CLIENT_ID',     env('OAUTH_CLIENT_ID', ''));
define('OAUTH_CLIENT_SECRET', env('OAUTH_CLIENT_SECRET', ''));
define('OAUTH_REDIRECT_URI',  env('OAUTH_REDIRECT_URI', BASE_URL . '/oauth-callback'));

// Fleeca Bank
define('FLEECA_AUTH_KEY', env('FLEECA_AUTH_KEY', ''));

// ── Session başlat ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $lifetime = (int) env('SESSION_LIFETIME', 86400);

    $cookieParams = [
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly'  => true,
        'samesite'  => 'Lax',
    ];

    session_set_cookie_params($cookieParams);
    session_name('sociaera_session');
    session_start();
}

// ── Helpers & Core yükle ──────────────────────────────────
require_once APP_PATH . '/Helpers/functions.php';
require_once APP_PATH . '/Core/Csrf.php';
require_once APP_PATH . '/Core/Auth.php';
require_once APP_PATH . '/Core/View.php';
require_once APP_PATH . '/Core/Response.php';
require_once APP_PATH . '/Core/RateLimit.php';
require_once APP_PATH . '/Config/database.php';

// ── Servisler ─────────────────────────────────────────────
require_once APP_PATH . '/Services/Logger.php';
require_once APP_PATH . '/Services/ImageUploader.php';
require_once APP_PATH . '/Services/OAuthGtaWorld.php';

// ── Modeller (navbar, sidebar vb. her yerde lazım) ────────
require_once APP_PATH . '/Models/User.php';
require_once APP_PATH . '/Models/Venue.php';
require_once APP_PATH . '/Models/Checkin.php';
require_once APP_PATH . '/Models/Notification.php';
require_once APP_PATH . '/Models/Leaderboard.php';
require_once APP_PATH . '/Models/Ad.php';
require_once APP_PATH . '/Models/Settings.php';
require_once APP_PATH . '/Models/Wallet.php';
require_once APP_PATH . '/Helpers/ads_logic.php';
