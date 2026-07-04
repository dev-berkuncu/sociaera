<?php
/**
 * Sociaera — Ana Uygulama Yapılandırması
 * Tüm sabitler, session ayarları ve bootstrap burada.
 */

// Hata raporlama — ortam bazlı
error_reporting(E_ALL);
if (env('APP_ENV', 'production') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

// Session ve Çerez Güvenliği
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Varsayılan UTF-8 Encoding Başlığı
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

// ── Sabitler ──────────────────────────────────────────────
define('APP_NAME',    'Sociaera');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     env('APP_ENV', 'production'));

// Dizin yolları
define('ROOT_PATH',    dirname(__DIR__, 2));
define('APP_PATH',     ROOT_PATH . '/app');
define('PUBLIC_PATH',  ROOT_PATH . '/public');


// Otomatik Güvenli Upload Klasörü (Hostinger Git Deploy gibi ortamlar için)
// ROOT_PATH'in bir üst dizinine (genelde /domains/domain.com/) güvenli bir klasör oluşturur.
$persistentUploads = dirname(ROOT_PATH) . '/sociaera_uploads_persistent';

if (!file_exists($persistentUploads)) {
    @mkdir($persistentUploads, 0755, true);
    @mkdir($persistentUploads . '/avatars', 0755, true);
    @mkdir($persistentUploads . '/banners', 0755, true);
    @mkdir($persistentUploads . '/ads', 0755, true);
    @mkdir($persistentUploads . '/venues', 0755, true);
    @mkdir($persistentUploads . '/posts', 0755, true);
    @mkdir($persistentUploads . '/sponsors', 0755, true);
}

// Eğer public/uploads yoksa (Git pull sonrası silinmişse veya yeni kurulumsa)
$publicUploads = PUBLIC_PATH . '/uploads';
$symlinked = false;

if (!file_exists($publicUploads)) {
    // Linux/Hostinger ortamında symlink oluşturarak kalıcı klasöre bağla (eğer fonksiyon açıksa)
    if (is_dir($persistentUploads) && function_exists('symlink')) {
        $symlinked = @symlink($persistentUploads, $publicUploads);
    }
} else if (is_link($publicUploads)) {
    $symlinked = true;
}

// DİKKAT: Eğer $publicUploads gerçek bir klasörse (symlink değilse), onu kullanmamalıyız!
// Çünkü Git deploy o klasörün içini silecektir. 
// Bu yüzden $symlinked false kalır ve UPLOAD_PATH doğrudan dışarıdaki klasör (persistent) olur.
// .htaccess dosyası, fiziksel olarak bulunamayan resimleri proxy (serve_upload.php) ile oradan sunar!
define('UPLOAD_PATH', $symlinked ? $publicUploads : $persistentUploads);

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
define('MAX_AVATAR_SIZE',  10 * 1024 * 1024);  // 10 MB
define('MAX_BANNER_SIZE',  10 * 1024 * 1024);  // 10 MB
define('MAX_POST_SIZE',    10 * 1024 * 1024);  // 10 MB
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
define('FLEECA_GATEWAY_ID', env('FLEECA_GATEWAY_ID', ''));
define('FLEECA_CALLBACK_URL', env('FLEECA_CALLBACK_URL', ''));
define('FLEECA_MODE', (int) env('FLEECA_MODE', 0)); // 0=sandbox, 1=live
define('FLEECA_GATEWAY_BASE', 'https://banking-tr.gta.world/gateway');
define('FLEECA_VERIFY_BASE', 'https://banking-tr.gta.world/gateway_token');

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
