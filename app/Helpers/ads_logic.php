<?php
/**
 * Reklam Yükleme Mantığı
 * Sayfalardan include edilerek reklam slotlarını doldurur.
 */

$carouselAds     = [];
$sidebarLeftAds  = [];
$sidebarRightAds = [];
$footerAds       = [];

try {
    $db = Database::getConnection();

    // ads tablosu var mı kontrol et
    $check = $db->query("SHOW TABLES LIKE 'ads'");
    if ($check->rowCount() > 0) {
        // Carousel (birden fazla)
        $carouselAds = $db->query(
            "SELECT * FROM ads WHERE position = 'carousel' AND is_active = 1 ORDER BY sort_order, id DESC"
        )->fetchAll();

        // Sol sidebar (1 tane)
        $sidebarLeftAds = $db->query(
            "SELECT * FROM ads WHERE position = 'sidebar_left' AND is_active = 1 ORDER BY sort_order, id DESC LIMIT 1"
        )->fetchAll();

        // Sağ sidebar (1 tane)
        $sidebarRightAds = $db->query(
            "SELECT * FROM ads WHERE position = 'sidebar_right' AND is_active = 1 ORDER BY sort_order, id DESC LIMIT 1"
        )->fetchAll();

        // Footer banner (1 tane)
        $footerAds = $db->query(
            "SELECT * FROM ads WHERE position = 'footer_banner' AND is_active = 1 ORDER BY sort_order, id DESC LIMIT 1"
        )->fetchAll();
    }
} catch (Exception $e) {
    error_log("Ads fetch error: " . $e->getMessage());
}
