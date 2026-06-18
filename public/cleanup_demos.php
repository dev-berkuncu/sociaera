<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/database.php';

try {
    $db = Database::getConnection();
    
    // 1. Test Kullanıcılarını Sil (Carlos, Aylin, Jake vb.)
    $demoUsernames = ['Carlos_Mendoza', 'Aylin_Demir', 'Jake_Morrison'];
    $in = str_repeat('?,', count($demoUsernames) - 1) . '?';
    $stmt = $db->prepare("DELETE FROM users WHERE username IN ($in)");
    $stmt->execute($demoUsernames);
    $deletedUsers = $stmt->rowCount();
    
    // 2. Test Mekanlarını Sil
    $demoVenues = ['Pillbox Casino', 'Bean Machine Coffee', 'Tequi-la-la', 'Bahama Mamas West', 'Paradise Hotel & Spa'];
    $inV = str_repeat('?,', count($demoVenues) - 1) . '?';
    $stmtV = $db->prepare("DELETE FROM venues WHERE name IN ($inV)");
    $stmtV->execute($demoVenues);
    $deletedVenues = $stmtV->rowCount();
    
    echo "<div style='font-family:sans-serif; padding:40px;'>";
    echo "<h1 style='color:green;'>✅ Demo Veri Temizliği Başarılı!</h1>";
    echo "<ul>";
    echo "<li><b>Silinen Test Kullanıcısı:</b> $deletedUsers</li>";
    echo "<li><b>Silinen Test Mekanı:</b> $deletedVenues</li>";
    echo "<li><i>(Silinen hesaplara ve mekanlara ait check-in, yorum, beğeni, bildirim ve finans hareketi CASCADE ile otomatik olarak sıfırlandı.)</i></li>";
    echo "</ul>";
    echo "<p style='color:red;'><b>GÜVENLİK NOTU:</b> Bu dosya güvenlik amacıyla kendi kendini İMHA ETMİŞTİR. Sayfayı yenilediğinizde 404 alacaksınız.</p>";
    echo "</div>";
    
    // Güvenlik için kendi kendini sil
    unlink(__FILE__);
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
