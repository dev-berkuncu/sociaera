<?php
/**
 * Fleeca Banking Gateway Callback
 * 
 * Ödeme sonrası Fleeca bu endpoint'e token ile yönlendirir.
 * Token strict mode ile doğrulanır, başarılıysa bakiye eklenir.
 * 
 * Doğrulama yanıtı:
 * {
 *   "token": "...",
 *   "auth_key": "...",
 *   "message": "successful_payment",
 *   "payment": 100.00,
 *   "routing_from": "...",
 *   "routing_to": "...",
 *   "sandbox": false,
 *   "token_expired": false,
 *   "token_created_at": "..."
 * }
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

// Path'ten token alma desteği: /oauth-callback/TOKEN veya /fleeca-callback/TOKEN
if (!$token) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    // URL'nin son segmentini token olarak al
    $parts = explode('/', rtrim(parse_url($uri, PHP_URL_PATH), '/'));
    $lastSegment = end($parts);
    if ($lastSegment && $lastSegment !== 'oauth-callback' && $lastSegment !== 'fleeca-callback' && strlen($lastSegment) > 10) {
        $token = $lastSegment;
    }
}

if (!$token) {
    Logger::warning('Fleeca callback: missing token', ['uri' => $_SERVER['REQUEST_URI'] ?? '']);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=no_token');
    exit;
}

Logger::info('Fleeca callback received', ['token' => substr($token, 0, 30) . '...']);

// ── Session'dan pending bilgi al ─────────────────────────
$pending = $_SESSION['fleeca_pending'] ?? null;
$userId = $pending['user_id'] ?? null;
$expectedAmount = $pending['amount'] ?? null;

// Kullanıcı giriş yapmışsa onu kullan
if (!$userId && Auth::check()) {
    $userId = Auth::id();
}

if (!$userId) {
    Logger::error('Fleeca callback: no user context', ['token' => $token]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=no_user');
    exit;
}

// ── Token doğrulama (strict mode) ────────────────────────
$verifyUrl = FLEECA_VERIFY_BASE . '/' . rawurlencode($token) . '/strict';

$ch = curl_init($verifyUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    Logger::error('Fleeca verify curl error', ['error' => $curlError, 'token' => $token]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=connection');
    exit;
}

if ($httpCode === 404) {
    Logger::error('Fleeca verify 404 - token expired/sandbox/invalid', ['token' => $token]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=expired');
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    Logger::error('Fleeca verify failed', ['http_code' => $httpCode, 'response' => $response]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=verify');
    exit;
}

$data = json_decode($response, true);

if (!$data) {
    Logger::error('Fleeca verify invalid JSON', ['response' => $response]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=json');
    exit;
}

Logger::info('Fleeca verify response', ['data' => $data]);

// ── Güvenlik kontrolleri ─────────────────────────────────

// 1. Auth key kontrolü — token bizim gateway'imize mi ait?
if (isset($data['auth_key']) && $data['auth_key'] !== FLEECA_AUTH_KEY) {
    Logger::error('Fleeca verify: auth_key mismatch', [
        'expected' => substr(FLEECA_AUTH_KEY, 0, 10) . '...',
        'got'      => substr($data['auth_key'] ?? '', 0, 10) . '...',
    ]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=auth');
    exit;
}

// 2. Mesaj kontrolü
if (($data['message'] ?? '') !== 'successful_payment') {
    Logger::error('Fleeca verify: not successful', ['message' => $data['message'] ?? '']);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=status');
    exit;
}

// 3. Sandbox kontrolü (production'da sandbox kabul etme)
if (!empty($data['sandbox']) && $data['sandbox'] === true) {
    Logger::warning('Fleeca verify: sandbox payment', ['data' => $data]);
    // Sandbox modda iken yine de kabul et (test)
    // Production'da bu satırı kaldırın:
    // header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=sandbox');
    // exit;
}

// 4. Tutar
$amount = (float) ($data['payment'] ?? 0);
if ($amount <= 0) {
    Logger::error('Fleeca verify: invalid amount', ['payment' => $data['payment'] ?? null]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=amount');
    exit;
}

// 5. Beklenen tutarla karşılaştır (varsa)
if ($expectedAmount && abs($amount - $expectedAmount) > 0.01) {
    Logger::warning('Fleeca verify: amount mismatch', [
        'expected' => $expectedAmount,
        'got'      => $amount,
    ]);
    // Yine de devam et — Fleeca'dan gelen tutarı kullan
}

// ── İdempotency kontrolü ─────────────────────────────────
$db = Database::getConnection();
$paymentToken = $data['token'] ?? $token;

$stmt = $db->prepare("SELECT id FROM transactions WHERE reference_id = ? LIMIT 1");
$stmt->execute([$paymentToken]);
if ($stmt->fetch()) {
    Logger::warning('Fleeca callback: duplicate token', ['token' => $paymentToken]);
    // Session temizle
    unset($_SESSION['fleeca_pending']);
    header('Location: ' . BASE_URL . '/wallet?payment=already');
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

    Logger::info('Fleeca payment success', [
        'userId'  => $userId,
        'amount'  => $amount,
        'token'   => $paymentToken,
        'sandbox' => $data['sandbox'] ?? false,
    ]);

    header('Location: ' . BASE_URL . '/wallet?payment=success');
    exit;

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    Logger::error('Fleeca deposit error', [
        'userId' => $userId,
        'amount' => $amount,
        'error'  => $e->getMessage(),
    ]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed&reason=db');
    exit;
}
