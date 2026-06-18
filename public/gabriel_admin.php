<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(__DIR__ . '/../.env');

$dbHost = env('DB_HOST', '127.0.0.1');
$dbPort = env('DB_PORT', '3306');
$dbName = env('DB_NAME', 'sociaera');
$dbUser = env('DB_USER', 'root');
$dbPass = env('DB_PASS', '');

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $db = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $searchTerm = '%Gabriel cruz%';
    $stmt = $db->prepare("SELECT id, username FROM users WHERE username LIKE ? OR tag LIKE ? OR gta_character_name LIKE ?");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $user = $stmt->fetch();

    if ($user) {
        $hash = password_hash('gabriel123', PASSWORD_DEFAULT);
        
        try {
            $db->prepare("UPDATE users SET is_admin = 1, admin_role = 'Super Admin', password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);
            echo "<h1>Basarili!</h1><p>Kullanici: {$user['username']} Super Admin yapildi.</p><p>Sifresi: <b>gabriel123</b> olarak guncellendi.</p><p><b>Lutfen guvenliginiz icin bu dosyayi (gabriel_admin.php) hemen sunucudan silin!</b></p>";
        } catch(Exception $e) {
            $db->prepare("UPDATE users SET is_admin = 1, password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);
            echo "<h1>Basarili!</h1><p>Kullanici: {$user['username']} Admin yapildi.</p><p>Sifresi: <b>gabriel123</b> olarak guncellendi.</p><p><b>Lutfen guvenliginiz icin bu dosyayi (gabriel_admin.php) hemen sunucudan silin!</b></p>";
        }
    } else {
        echo "<h1>Hata</h1><p>Gabriel cruz isimli kullanici veritabaninda bulunamadi.</p>";
    }

} catch (PDOException $e) {
    echo "Veritabani baglantisi basarisiz: " . $e->getMessage();
}
