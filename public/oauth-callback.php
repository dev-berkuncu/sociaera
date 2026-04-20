<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Services/OAuthGtaWorld.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Models/User.php';

// State doğrulama
if (!OAuthGtaWorld::verifyState($_GET['state'] ?? null)) {
    Auth::setFlash('error', 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$code = $_GET['code'] ?? '';
if (empty($code)) {
    Auth::setFlash('error', 'Yetkilendirme kodu alınamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Token al
$tokenData = OAuthGtaWorld::getAccessToken($code);
if (!$tokenData) {
    Auth::setFlash('error', 'GTA World ile bağlantı kurulamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$accessToken = $tokenData['access_token'];

// Kullanıcı bilgilerini al
$gtaUser = OAuthGtaWorld::getUser($accessToken);
if (!$gtaUser || empty($gtaUser['id'])) {
    Auth::setFlash('error', 'GTA World kullanıcı bilgileri alınamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Kullanıcı oluştur veya bul
$userModel = new UserModel();
$result = $userModel->findOrCreateByOAuth($gtaUser['id'], $gtaUser['username'] ?? 'user_' . $gtaUser['id'], $gtaUser['email'] ?? '');

if (!$result['ok']) {
    Auth::setFlash('error', 'Hesap oluşturulamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Karakterleri al ve session'a kaydet
$characters = OAuthGtaWorld::getCharacters($accessToken);
$_SESSION['oauth_characters'] = $characters;
$_SESSION['oauth_user_id'] = $result['user']['id'];

// Karakteri zaten seçilmiş mi?
if (!empty($result['user']['gta_character_id'])) {
    Auth::login($result['user']);
    Csrf::regenerate();
    Logger::info('OAuth login', ['user_id' => $result['user']['id']]);
    Auth::setFlash('success', 'Hoş geldin, ' . $result['user']['username'] . '! 👋');
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}

// Karakter seçim sayfasına yönlendir
header('Location: ' . BASE_URL . '/character-select');
exit;
