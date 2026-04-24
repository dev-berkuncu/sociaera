<?php
/**
 * OAuth Callback — GTA World UCP'den dönüş + Fleeca Banking callback
 *
 * Fleeca Banking ödeme sonrası da bu URL'e token ile dönülür.
 * Token parametresi varsa Fleeca callback'e yönlendirilir.
 *
 * OAuth Akışı:
 * 1. State doğrula
 * 2. Code → Access Token
 * 3. Access Token → /api/user (kullanıcı + karakter bilgisi)
 * 4. DB'de kullanıcı oluştur/bul
 * 5. Karakter seçimine veya dashboard'a yönlendir
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';

// ── Fleeca Banking Token Kontrolü ────────────────────────
// Fleeca ödeme sonrası bu URL'e token ile döner
$fleecaToken = $_GET['token'] ?? null;
if ($fleecaToken) {
    // Fleeca callback handler'a yönlendir
    require_once __DIR__ . '/api/fleeca-callback.php';
    exit;
}

// ── OAuth Akışı ──────────────────────────────────────────
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

// 6. Karakter zaten seçilmiş olsa bile, kullanıcının her girişte karakter seçebilmesi için
// otomatik giriş adımlarını (Step 6 ve Step 7) kaldırıyoruz.
// Kullanıcı her login olduğunda character-select sayfasına yönlendirilecek.

// 8. Birden fazla karakter → seçim sayfası
header('Location: ' . BASE_URL . '/character-select');
exit;
