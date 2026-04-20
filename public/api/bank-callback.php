<?php
/**
 * Fleeca Banking Gateway Callback
 * URL: /api/bank-callback{TOKEN}
 * .htaccess: /api/bank-callback.php?token={TOKEN}
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
    Auth::setFlash('error', 'Ödeme tokeni bulunamadı.');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

Logger::info('Fleeca callback START', ['token' => $token]);

// ── Kullanıcı bilgisi ────────────────────────────────────
$pending = $_SESSION['fleeca_pending'] ?? null;
$userId = $pending['user_id'] ?? null;

if (!$userId && Auth::check()) {
    $userId = Auth::id();
}

if (!$userId) {
    Auth::setFlash('error', 'Oturum bilgisi bulunamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// ── Token doğrulama ──────────────────────────────────────
$verifyUrl = 'https://banking-tr.gta.world/gateway_token/' . $token;

$ch = curl_init($verifyUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

Logger::info('Fleeca verify result', [
    'http_code' => $httpCode,
    'response'  => $response,
    'curl_err'  => $curlError,
]);

// cURL hatası
if ($curlError) {
    Auth::setFlash('error', 'Fleeca bağlantı hatası: ' . $curlError);
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// HTTP hatası
if ($httpCode < 200 || $httpCode >= 300) {
    Auth::setFlash('error', 'Fleeca doğrulama hatası (HTTP ' . $httpCode . '). Token: ' . substr($token, 0, 15) . '...');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// JSON parse
$data = json_decode($response, true);
if (!$data) {
    Auth::setFlash('error', 'Fleeca yanıtı okunamadı. Yanıt: ' . substr($response, 0, 100));
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

Logger::info('Fleeca parsed data', $data);

// ── Ödeme bilgilerini çıkar ──────────────────────────────
$paymentMessage = $data['message'] ?? 'yok';
$paymentAmount  = $data['payment'] ?? 0;
$paymentAuthKey = $data['auth_key'] ?? '';
$tokenExpired   = $data['token_expired'] ?? false;
$sandbox        = $data['sandbox'] ?? false;
$paymentToken   = $data['token'] ?? $token;

// Auth key kontrolü (varsa)
if ($paymentAuthKey && $paymentAuthKey !== FLEECA_AUTH_KEY) {
    Auth::setFlash('error', 'Auth key eşleşmiyor. Beklenen: ' . substr(FLEECA_AUTH_KEY, 0, 8) . '... Gelen: ' . substr($paymentAuthKey, 0, 8) . '...');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// Mesaj kontrolü
$validMessages = ['successful_payment', 'payment_successful'];
if (!in_array($paymentMessage, $validMessages)) {
    Auth::setFlash('error', 'Ödeme durumu: "' . $paymentMessage . '". Beklenen: "successful_payment"');
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// Tutar kontrolü
$amount = (float) $paymentAmount;
if ($amount <= 0) {
    Auth::setFlash('error', 'Geçersiz tutar: ' . $paymentAmount);
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}

// ── İdempotency ──────────────────────────────────────────
$db = Database::getConnection();

$stmt = $db->prepare("SELECT id FROM transactions WHERE reference_id = ? LIMIT 1");
$stmt->execute([$paymentToken]);
if ($stmt->fetch()) {
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
        'Fleeca Banking ($' . number_format($amount, 2) . ')' . ($sandbox ? ' [SANDBOX]' : ''),
        $paymentToken,
    ]);

    $db->commit();
    unset($_SESSION['fleeca_pending']);

    Logger::info('PAYMENT SUCCESS', [
        'userId'  => $userId,
        'amount'  => $amount,
        'sandbox' => $sandbox,
    ]);

    Auth::setFlash('success', 'Bakiye başarıyla yüklendi! $' . number_format($amount, 2) . ' hesabınıza eklendi. 🎉');
    header('Location: ' . BASE_URL . '/wallet');
    exit;

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    Logger::error('PAYMENT DB ERROR', ['error' => $e->getMessage()]);
    Auth::setFlash('error', 'Veritabanı hatası: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/wallet');
    exit;
}
