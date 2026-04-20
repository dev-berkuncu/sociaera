<?php
/**
 * Debug / Diagnostic Test — deploy sonrası sil
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Sociaera Debug</h2>";

// 1. PHP Versiyonu
echo "<p>PHP: " . PHP_VERSION . "</p>";

// 2. .env dosyası
$envPath = dirname(__DIR__) . '/.env';
echo "<p>.env yolu: {$envPath}</p>";
echo "<p>.env var mı: " . (file_exists($envPath) ? '✅ EVET' : '❌ HAYIR') . "</p>";

// 3. app/ klasörü
$appPath = dirname(__DIR__) . '/app/Config/env.php';
echo "<p>env.php var mı: " . (file_exists($appPath) ? '✅ EVET' : '❌ HAYIR') . "</p>";

// 4. .env okuma testi
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "<p>.env satır sayısı: " . count($lines) . "</p>";
}

// 5. DB bağlantı testi
if (file_exists($envPath)) {
    require_once dirname(__DIR__) . '/app/Config/env.php';
    loadEnv($envPath);

    $host = env('DB_HOST', 'localhost');
    $name = env('DB_NAME', '');
    $user = env('DB_USER', '');
    $pass = env('DB_PASS', '');

    echo "<p>DB_HOST: {$host}</p>";
    echo "<p>DB_NAME: {$name}</p>";
    echo "<p>DB_USER: {$user}</p>";

    try {
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "<p>DB Bağlantı: ✅ BAŞARILI</p>";

        // Tablo kontrolü
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tablolar (" . count($tables) . "): " . implode(', ', $tables) . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>DB Hata: " . $e->getMessage() . "</p>";
    }
}

// 6. Klasör izinleri
$dirs = ['uploads', 'uploads/avatars', 'uploads/posts', 'storage', 'storage/logs'];
foreach ($dirs as $d) {
    $full = dirname(__DIR__) . '/' . $d;
    $writable = is_writable($full) ? '✅' : '❌';
    echo "<p>{$d}: {$writable} writable</p>";
}

// 7. Session testi
session_start();
echo "<p>Session: ✅ çalışıyor</p>";

echo "<hr><p><strong>Bu dosyayı deploy sonrası silin!</strong></p>";
