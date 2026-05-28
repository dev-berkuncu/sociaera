<?php
/**
 * Switch Character — Aynı UCP hesabına bağlı karakterler arası hızlı geçiş
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Models/User.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    if (!$targetUserId) {
        Auth::setFlash('error', 'Geçersiz hedef kullanıcı.');
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
    
    $userModel = new UserModel();
    $currentUser = $userModel->getById(Auth::id());
    
    if (empty($currentUser['gta_user_id'])) {
        Auth::setFlash('error', 'Karakter geçişi için bağlı bir UCP hesabı bulunmalıdır.');
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
    
    // Hedef kullanıcıyı bul
    $targetUser = $userModel->getById($targetUserId);
    if (!$targetUser || !$targetUser['is_active']) {
        Auth::setFlash('error', 'Hedef karakter bulunamadı veya pasif.');
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
    
    // Aynı UCP hesabı mı kontrol et
    if ((int)$targetUser['gta_user_id'] !== (int)$currentUser['gta_user_id']) {
        Auth::setFlash('error', 'Bu karaktere geçiş yetkiniz yok.');
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
    
    // Giriş yap
    Auth::login($targetUser);
    Csrf::regenerate();
    
    $charName = $targetUser['gta_character_name'] ?: $targetUser['username'];
    Auth::setFlash('success', "{$charName} karakterine başarıyla geçiş yapıldı! 🎭");
    
    header('Location: ' . BASE_URL . '/settings');
    exit;
}

header('Location: ' . BASE_URL . '/dashboard');
exit;
