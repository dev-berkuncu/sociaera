<?php
/**
 * Fleeca Banking Gateway Callback
 * 
 * Fleeca ödeme sonrası kullanıcıyı buraya yönlendirir.
 * URL formatı: /api/bank-callback{TOKEN}
 * .htaccess ile: /api/bank-callback.php?token={TOKEN}
 * 
 * Token strict mode ile doğrulanır, başarılıysa bakiye eklenir.
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Wallet.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

// ── Token'ı al ──────────────────────────────────────────
$token = $_GET['token'] ?? null;

if (!$token) {
    Logger::warning('Fleeca callback: missing token', ['uri' => $_SERVER['REQUEST_URI'] ?? '']);
    Auth::setFlash('error', 'Ödeme tokeni bulunamadı.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

Logger::info('Fleeca callback received', ['token' => substr($token, 0, 30) . '...']);

// ── Session'dan pending bilgi al ─────────────────────────
$pending = $_SESSION['fleeca_pending'] ?? null;
$userId = $pending['user_id'] ?? null;

// Session yoksa giriş yapmış kullanıcıyı al
if (!$userId && Auth::check()) {
    $userId = Auth::id();
}

if (!$userId) {
    Logger::error('Fleeca callback: no user context', ['token' => $token]);
    Auth::setFlash('error', 'Oturum bilgisi bulunamadı. Lütfen giriş yapıp tekrar deneyin.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// ── Token doğrulama (strict mode) ────────────────────────
$verifyUrl = 'https://banking-tr.gta.world/gateway_token/' . rawurlencode($token) . '/strict';

Logger::info('Fleeca verify request', ['url' => $verifyUrl]);

$ch = curl_init($verifyUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

Logger::info('Fleeca verify response', [
    'http_code' => $httpCode,
    'response'  => substr($response, 0, 500),
    'curl_err'  => $curlError,
]);

if ($curlError) {
    Auth::setFlash('error', 'Fleeca Banking bağlantı hatası.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

if ($httpCode === 404) {
    Auth::setFlash('error', 'Ödeme tokeni geçersiz veya süresi dolmuş.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    Auth::setFlash('error', 'Ödeme doğrulanamadı. (HTTP ' . $httpCode . ')');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

$data = json_decode($response, true);

if (!$data) {
    Logger::error('Fleeca verify invalid JSON', ['response' => $response]);
    Auth::setFlash('error', 'Ödeme yanıtı okunamadı.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// ── Güvenlik kontrolleri ─────────────────────────────────

// 1. Auth key kontrolü
if (isset($data['auth_key']) && $data['auth_key'] !== FLEECA_AUTH_KEY) {
    Logger::error('Fleeca verify: auth_key mismatch');
    Auth::setFlash('error', 'Ödeme doğrulaması başarısız.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// 2. Mesaj kontrolü
if (($data['message'] ?? '') !== 'successful_payment') {
    Logger::error('Fleeca verify: not successful', ['message' => $data['message'] ?? '']);
    Auth::setFlash('error', 'Ödeme başarısız.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// 3. Tutar
$amount = (float) ($data['payment'] ?? 0);
if ($amount <= 0) {
    Logger::error('Fleeca verify: invalid amount', ['payment' => $data['payment'] ?? null]);
    Auth::setFlash('error', 'Geçersiz ödeme tutarı.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// ── İdempotency kontrolü ─────────────────────────────────
$db = Database::getConnection();
$paymentToken = $data['token'] ?? $token;

$stmt = $db->prepare("SELECT id FROM transactions WHERE reference_id = ? LIMIT 1");
$stmt->execute([$paymentToken]);
if ($stmt->fetch()) {
    Logger::warning('Fleeca callback: duplicate token', ['token' => $paymentToken]);
    unset($_SESSION['fleeca_pending']);
    Auth::setFlash('info', 'Bu ödeme zaten işlenmiş.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// ── Bakiye yükle ─────────────────────────────────────────
try {
    $walletModel = new WalletModel();
    $walletModel->ensureWallet($userId);

    $db->beginTransaction();

    $stmt = $db->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
    $stmt->execute([$amount, $userId]);

    $stmt = $db->prepare("
        INSERT INTO transactions (user_id, type, amount, description, reference_id)
        VALUES (?, 'deposit', ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $amount,
        'Fleeca Banking ile bakiye yükleme ($' . number_format($amount, 2) . ')',
        $paymentToken,
    ]);

    $db->commit();

    // Session temizle
    unset($_SESSION['fleeca_pending']);

    Logger::info('Fleeca payment SUCCESS', [
        'userId'  => $userId,
        'amount'  => $amount,
        'token'   => $paymentToken,
        'sandbox' => $data['sandbox'] ?? false,
    ]);

    Auth::setFlash('success', 'Bakiye başarıyla yüklendi! $' . number_format($amount, 2) . ' hesabınıza eklendi. 🎉');
    header('Location: ' . BASE_URL . '/wallet');
    exit;

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    Logger::error('Fleeca deposit DB error', [
        'userId' => $userId,
        'amount' => $amount,
        'error'  => $e->getMessage(),
    ]);
    Auth::setFlash('error', 'Bakiye yüklenirken bir hata oluştu. Lütfen destek ile iletişime geçin.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}
