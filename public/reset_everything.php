<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/database.php';

try {
    $db = Database::getConnection();
    
    // 1. Yabancı anahtar kontrollerini kapat
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // 2. Tüm tabloları bul ve sil (DROP)
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $db->exec("DROP TABLE IF EXISTS `$table`");
    }
    
    // 3. Yabancı anahtar kontrollerini tekrar aç
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    // 4. Şemayı baştan kur
    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    if (file_exists($schemaPath)) {
        $schema = file_get_contents($schemaPath);
        $db->exec($schema);
    } else {
        throw new Exception("schema.sql bulunamadı!");
    }
    
    // 5. Seed verisini ekle (Admin kullanıcısı vb.)
    $seedPath = dirname(__DIR__) . '/database/seed.sql';
    if (file_exists($seedPath)) {
        $seed = file_get_contents($seedPath);
        $db->exec($seed);
    } else {
        throw new Exception("seed.sql bulunamadı!");
    }
    
    // 6. Uploads klasörlerini temizle (.gitkeep hariç)
    function clearDirectory($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..', '.gitkeep']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
    
    $uploadDirs = ['avatars', 'posts', 'venues', 'ads', 'banners'];
    foreach ($uploadDirs as $d) {
        clearDirectory(__DIR__ . '/uploads/' . $d);
    }
    
    // 7. Session'ı sıfırla (Kullanıcının çıkış yapması için)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    
    // Ekrana çıktı ver
    echo "<div style='font-family:sans-serif; padding:40px; text-align:center;'>";
    echo "<h1 style='color:green; font-size:32px;'>✅ SİSTEM TAMAMEN SIFIRLANDI!</h1>";
    echo "<p style='font-size:18px;'>Tüm veritabanı, tablolar ve yüklenen fotoğraflar başarıyla kalıcı olarak silindi.</p>";
    echo "<p style='font-size:18px;'>Veritabanı şeması ve sadece yetkili <b>Admin</b> kullanıcısı sıfırdan oluşturuldu.</p>";
    echo "<div style='margin: 30px 0; padding:20px; background:#f9f9f9; border-radius:10px; display:inline-block; text-align:left;'>";
    echo "<b>Silinen Tablolar:</b> " . count($tables) . "<br>";
    echo "<b>Temizlenen Klasörler:</b> uploads/ (avatarlar, mekan fotoğrafları, reklamlar vb.)";
    echo "</div>";
    echo "<p style='color:red; font-weight:bold;'>GÜVENLİK NOTU: Bu dosya işlemi tamamladıktan sonra kendini otomatik olarak sildi (Self-Destruct).</p>";
    echo "<a href='" . BASE_URL . "/login' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#F06D1F; color:#fff; text-decoration:none; border-radius:8px; font-weight:bold;'>Giriş Sayfasına Dön</a>";
    echo "</div>";
    
    // Güvenlik için kendi kendini sil
    unlink(__FILE__);
    
} catch (Exception $e) {
    echo "<h2>Kritik Bir Hata Oluştu!</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
