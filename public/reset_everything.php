<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/database.php';

try {
    $db = Database::getConnection();
    
    // 1. Yabancı anahtar kontrollerini kapat
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // 2. Tabloları temizle (Sadece izin verilenler hariç)
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $excludedTables = ['users', 'settings', 'wallets'];
    
    foreach ($tables as $table) {
        if (!in_array($table, $excludedTables)) {
            $db->exec("TRUNCATE TABLE `$table`");
        }
    }
    
    // 3. Kullanıcıları ve Cüzdanları Temizle
    // Sadece admin OLMAYAN kullanıcıları sil
    $stmt = $db->prepare("DELETE FROM users WHERE is_admin = 0");
    $stmt->execute();
    $deletedUsers = $stmt->rowCount();
    
    // Silinen kullanıcılara ait cüzdanları sil (Admin cüzdanları kalır)
    $db->exec("DELETE FROM wallets WHERE user_id NOT IN (SELECT id FROM users)");
    
    // Adminlerin avatarlarını ve bannerlarını sıfırla (Çünkü klasörü sileceğiz)
    $db->exec("UPDATE users SET avatar = NULL, banner = NULL");
    
    // 4. Yabancı anahtar kontrollerini tekrar aç
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
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
    echo "<h1 style='color:green; font-size:32px;'>✅ SİSTEM SIFIRLANDI (Adminler Hariç)!</h1>";
    echo "<p style='font-size:18px;'>Tüm veritabanı, gönderiler, beğeniler, check-in'ler, mekanlar ve yüklenen fotoğraflar başarıyla silindi.</p>";
    echo "<p style='font-size:18px;'><b>Yetkili Admin hesapları ve sistem ayarları</b> güvenliğiniz için korundu.</p>";
    echo "<div style='margin: 30px 0; padding:20px; background:#f9f9f9; border-radius:10px; display:inline-block; text-align:left;'>";
    echo "<b>Sıfırlanan Tablolar:</b> " . ($truncatedTables ?? count($tables)) . "<br>";
    echo "<b>Silinen Kullanıcı (Admin olmayanlar):</b> $deletedUsers<br>";
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
