<?php
/**
 * OAuth Callback — GTA World UCP'den dönüş
 *
 * Akış:
 * 1. State doğrula
 * 2. Code → Access Token
 * 3. Access Token → /api/user (kullanıcı + karakter bilgisi)
 * 4. DB'de kullanıcı oluştur/bul
 * 5. Karakter seçimine veya dashboard'a yönlendir
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
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

// 1. Token al
$tokenData = OAuthGtaWorld::getAccessToken($code);
if (!$tokenData) {
    Auth::setFlash('error', 'GTA World ile bağlantı kurulamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$accessToken = $tokenData['access_token'];

// 2. Kullanıcı bilgilerini al (karakter listesi dahil)
$gtaUser = OAuthGtaWorld::getUser($accessToken);
if (!$gtaUser || empty($gtaUser['id'])) {
    Logger::error('OAuth callback: user data empty or missing id', ['data' => $gtaUser]);
    Auth::setFlash('error', 'GTA World kullanıcı bilgileri alınamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// 3. Karakterleri çıkar (aynı /api/user yanıtından)
$characters = OAuthGtaWorld::extractCharacters($gtaUser);

Logger::info('OAuth user fetched', [
    'gta_id'     => $gtaUser['id'],
    'username'   => $gtaUser['username'] ?? 'unknown',
    'char_count' => count($characters),
]);

// 4. DB'de kullanıcı oluştur veya bul
$userModel = new UserModel();
$result = $userModel->findOrCreateByOAuth(
    $gtaUser['id'],
    $gtaUser['username'] ?? 'user_' . $gtaUser['id'],
    '' // GTA World API e-posta döndürmüyor
);

if (!$result['ok']) {
    Auth::setFlash('error', 'Hesap oluşturulamadı: ' . ($result['error'] ?? 'Bilinmeyen hata'));
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// 5. Session'a kaydet
$_SESSION['oauth_characters'] = $characters;
$_SESSION['oauth_user_id'] = $result['user']['id'];

// 6. Karakter zaten seçilmişse direkt giriş yap
if (!empty($result['user']['gta_character_id'])) {
    // Mevcut kullanıcıların username'ini karakter adıyla senkronla
    if (!empty($result['user']['gta_character_name']) && $result['user']['username'] !== $result['user']['gta_character_name']) {
        $userModel->updateCharacter($result['user']['id'], $result['user']['gta_character_id'], $result['user']['gta_character_name']);
        $result['user'] = $userModel->getById($result['user']['id']);
    }
    Auth::login($result['user']);
    Csrf::regenerate();
    Logger::info('OAuth login (existing character)', ['user_id' => $result['user']['id']]);
    $displayName = $result['user']['gta_character_name'] ?: $result['user']['username'];
    Auth::setFlash('success', 'Hoş geldin, ' . $displayName . '! 👋');
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}

// 7. Tek karakter varsa otomatik seç
if (count($characters) === 1) {
    $char = $characters[0];
    $userModel->updateCharacter($result['user']['id'], $char['id'], $char['name']);
    $user = $userModel->getById($result['user']['id']);
    Auth::login($user);
    Csrf::regenerate();
    unset($_SESSION['oauth_characters'], $_SESSION['oauth_user_id']);
    Logger::info('OAuth login (auto single char)', ['user_id' => $user['id'], 'char' => $char['name']]);
    Auth::setFlash('success', $char['name'] . ' olarak giriş yaptın! 🎭');
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}

// 8. Birden fazla karakter → seçim sayfası
header('Location: ' . BASE_URL . '/character-select');
exit;
