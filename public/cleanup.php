<?php
/**
 * Sociaera — Database Cleanup Script
 * Admin hariç tüm kullanıcıları ve içerikleri temizler.
 * Tek kullanımlık — kullandıktan sonra silinmeli.
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';

$db = Database::getConnection();

// Admin olmayan kullanıcı ID'lerini al
$stmt = $db->query("SELECT id FROM users WHERE is_admin = 0 OR is_admin IS NULL");
$nonAdminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($nonAdminIds)) {
    die("Silinecek admin dışı kullanıcı yok.\n");
}

$placeholders = implode(',', array_fill(0, count($nonAdminIds), '?'));

$db->beginTransaction();
try {
    // 1. Check-in fotoğraflarına bağlı dosya listesi (opsiyonel, sadece log)
    $deleted = [];

    // 2. Tüm içerik tablolarını temizle (sırayla — foreign key'e dikkat)
    $tables = [
        'notifications'  => 'DELETE FROM notifications',
        'likes'          => "DELETE FROM likes WHERE user_id IN ($placeholders) OR checkin_id IN (SELECT id FROM checkins WHERE user_id IN ($placeholders))",
        'comments'       => "DELETE FROM comments WHERE user_id IN ($placeholders) OR checkin_id IN (SELECT id FROM checkins WHERE user_id IN ($placeholders))",
        'follows'        => "DELETE FROM follows WHERE follower_id IN ($placeholders) OR following_id IN ($placeholders)",
        'checkins'       => "DELETE FROM checkins WHERE user_id IN ($placeholders)",
        'wallet_transactions' => "DELETE FROM wallet_transactions WHERE user_id IN ($placeholders)",
        'user_missions'  => "DELETE FROM user_missions WHERE user_id IN ($placeholders)",
        'leaderboard'    => 'DELETE FROM leaderboard',
        'users_nonAdmin' => "DELETE FROM users WHERE is_admin = 0 OR is_admin IS NULL",
    ];

    // notifications — tam temizle
    $db->exec("DELETE FROM notifications");
    $deleted[] = "notifications: temizlendi";

    // likes
    try {
        $db->prepare("DELETE FROM likes WHERE user_id IN ($placeholders)")->execute($nonAdminIds);
        $deleted[] = "likes: temizlendi";
    } catch (Exception $e) { $deleted[] = "likes: " . $e->getMessage(); }

    // comments
    try {
        $db->prepare("DELETE FROM comments WHERE user_id IN ($placeholders)")->execute($nonAdminIds);
        $deleted[] = "comments: temizlendi";
    } catch (Exception $e) { $deleted[] = "comments: " . $e->getMessage(); }

    // follows
    try {
        $db->prepare("DELETE FROM follows WHERE follower_id IN ($placeholders) OR following_id IN ($placeholders)")
           ->execute(array_merge($nonAdminIds, $nonAdminIds));
        $deleted[] = "follows: temizlendi";
    } catch (Exception $e) { $deleted[] = "follows: " . $e->getMessage(); }

    // checkins (ana içerik)
    try {
        $db->prepare("DELETE FROM checkins WHERE user_id IN ($placeholders)")->execute($nonAdminIds);
        $deleted[] = "checkins: temizlendi";
    } catch (Exception $e) { $deleted[] = "checkins: " . $e->getMessage(); }

    // wallet_transactions
    try {
        $db->prepare("DELETE FROM wallet_transactions WHERE user_id IN ($placeholders)")->execute($nonAdminIds);
        $deleted[] = "wallet_transactions: temizlendi";
    } catch (Exception $e) { $deleted[] = "wallet_transactions: atlandı"; }

    // user_missions
    try {
        $db->prepare("DELETE FROM user_missions WHERE user_id IN ($placeholders)")->execute($nonAdminIds);
        $deleted[] = "user_missions: temizlendi";
    } catch (Exception $e) { $deleted[] = "user_missions: atlandı"; }

    // leaderboard
    try {
        $db->exec("DELETE FROM leaderboard");
        $deleted[] = "leaderboard: temizlendi";
    } catch (Exception $e) { $deleted[] = "leaderboard: atlandı"; }

    // Son olarak kullanıcılar
    $userCount = $db->prepare("DELETE FROM users WHERE is_admin = 0 OR is_admin IS NULL");
    $userCount->execute();
    $deleted[] = "users (admin dışı): " . $userCount->rowCount() . " kullanıcı silindi";

    // AUTO_INCREMENT sıfırla (opsiyonel)
    // $db->exec("ALTER TABLE users AUTO_INCREMENT = 1");
    // $db->exec("ALTER TABLE checkins AUTO_INCREMENT = 1");

    $db->commit();

    echo "<pre style='font-family:monospace;background:#1a1a1a;color:#22c55e;padding:24px;border-radius:8px;'>";
    echo "✅ TEMİZLEME TAMAMLANDI\n\n";
    echo "Admin kullanıcıları korundu.\n\n";
    echo implode("\n", $deleted);
    echo "\n\n⚠️  Bu scripti sunucudan silin: /public/cleanup.php";
    echo "</pre>";

} catch (Exception $e) {
    $db->rollBack();
    echo "<pre style='background:#1a1a1a;color:#ef4444;padding:24px;border-radius:8px;'>";
    echo "❌ HATA — Geri alındı:\n" . $e->getMessage();
    echo "</pre>";
}
?>
