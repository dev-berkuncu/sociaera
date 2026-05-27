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

// ── Fleeca V2 Redirect (payment_id ile döner) ─────────────
$fleecaPaymentId = $_GET['payment_id'] ?? null;
if ($fleecaPaymentId) {
    // Kullanıcıyı cüzdana yönlendir — webhook bakiyeyi zaten ekledi (veya ekleyecek)
    require_once __DIR__ . '/../app/Config/database.php';
    require_once __DIR__ . '/../app/Models/User.php';
    require_once __DIR__ . '/../app/Models/Wallet.php';

    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT status FROM fleeca_payments WHERE payment_id = ? LIMIT 1");
    $stmt->execute([$fleecaPaymentId]);
    $row = $stmt->fetch();

    if ($row && $row['status'] === 'paid') {
        Auth::setFlash('success', 'Ödeme başarıyla tamamlandı! Bakiyeniz güncellendi.');
    } else {
        Auth::setFlash('info', 'Ödemeniz işleniyor. Birkaç saniye içinde bakiyenize yansıyacak.');
    }
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// ── Fleeca V1 Legacy Token (geriye dönük uyumluluk) ───────
$fleecaToken = $_GET['token'] ?? null;
if ($fleecaToken) {
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

// 4. Session'a kaydet — kullanıcı oluşturma karakter seçimine ertelenir
$_SESSION['oauth_characters']   = $characters;
$_SESSION['oauth_gta_user_id']  = (int)$gtaUser['id'];
$_SESSION['oauth_gta_username'] = $gtaUser['username'] ?? 'user_' . $gtaUser['id'];

// 5. Karakter seçim sayfasına yönlendir
header('Location: ' . BASE_URL . '/character-select');
exit;
