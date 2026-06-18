<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Models/User.php';

Auth::requireLogin();

$db = Database::getConnection();
$userId = Auth::id();

try {
    // Veritabanını güncelle
    $db->prepare("UPDATE users SET is_admin = 1, admin_role = 'super_admin' WHERE id = ?")->execute([$userId]);
    
    // Kullanıcıyı veritabanından tekrar çek
    $userModel = new UserModel();
    $user = $userModel->getById($userId);
    
    // Oturumu güncelle
    Auth::login($user);

    echo "<h1 style='color:green;'>Basarili!</h1>";
    echo "<p>Şu an giriş yapmış olduğunuz <b>{$user['username']}</b> hesabı doğrudan Super Admin yapıldı ve oturumunuz güncellendi.</p>";
    echo "<p>Lütfen <a href='" . BASE_URL . "/dashboard'>buraya tıklayarak</a> ana sayfaya dönün. Sağ üstte kırmızı kalkan ikonu çıkacaktır.</p>";
    
    // Güvenlik için kendini silebilir mi? (Opsiyonel, manuel silelim)
    echo "<p style='color:red;'><b>İşleminiz bittikten sonra bana haber verin, bu dosyayı sileceğim.</b></p>";

} catch (Exception $e) {
    echo "<h1>Hata</h1><p>" . $e->getMessage() . "</p>";
}
