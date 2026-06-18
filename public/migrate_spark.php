<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';

Auth::requireAdmin();

try {
    $db = Database::getConnection();
    
    // Daha önce eklenip eklenmediğini kontrol et
    $stmt = $db->prepare("SELECT id FROM ads WHERE title = 'SparkLS' AND position = 'sidebar_right'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<h1>Zaten Eklenmiş!</h1><p>SparkLS reklamı zaten veritabanında mevcut.</p>";
    } else {
        // Veritabanına gerçek bir reklam olarak ekle
        $insert = $db->prepare("
            INSERT INTO ads (title, image_url, link_url, position, is_active, status, sort_order, media_type) 
            VALUES ('SparkLS', '/assets/img/sponsors/sparkls.jpg', 'https://sparkls.online', 'sidebar_right', 1, 'approved', 0, 'image')
        ");
        $insert->execute();
        
        echo "<h1 style='color:green;'>Başarılı!</h1>";
        echo "<p>SparkLS reklamı başarıyla veritabanına eklendi! Artık Admin Paneli > Reklamlar sayfasında görebilir ve yönetebilirsiniz.</p>";
    }
    
    echo "<p><a href='" . BASE_URL . "/admin/ads'>Reklamlar Paneline Git</a></p>";
    
} catch (Exception $e) {
    echo "<h1>Hata</h1><p>" . $e->getMessage() . "</p>";
}
