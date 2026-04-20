<?php
/**
 * Fleeca Banking Gateway Callback
 * Ödeme sonrası Fleeca bu endpoint'e token ile yönlendirir.
 * Token doğrulanır, başarılıysa bakiye eklenir.
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
require_once __DIR__ . '/../../app/Models/Settings.php';

// Token'ı al (query veya path)
$token = $_GET['token'] ?? null;
if (!$token) {
    // Path token desteği: /api/fleeca-callback/TOKEN
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/fleeca-callback/([a-zA-Z0-9_\-]+)#', $uri, $m)) {
        $token = $m[1];
    }
}

if (!$token) {
    Logger::warning('Fleeca callback: missing token');
    header('Location: ' . BASE_URL . '/wallet?payment=failed');
    exit;
}

Logger::info('Fleeca callback received', ['token' => $token]);

// ── Token doğrulama ──────────────────────────────────────
$verifyUrl = FLEECA_VERIFY_BASE . '/' . rawurlencode($token);

$ch = curl_init($verifyUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    Logger::error('Fleeca verify curl error', ['error' => $curlError, 'token' => $token]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed');
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    Logger::error('Fleeca verify failed', ['http_code' => $httpCode, 'response' => $response, 'token' => $token]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed');
    exit;
}

$data = json_decode($response, true);

if (!$data) {
    Logger::error('Fleeca verify invalid JSON', ['response' => $response, 'token' => $token]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed');
    exit;
}

Logger::info('Fleeca verify response', ['data' => $data]);

// ── Veri çıkarma ─────────────────────────────────────────
// API yanıtından amount ve userId çıkar
$amount = 0;
$userId = 0;

// Farklı API yanıt formatlarına uyum
if (isset($data['amount'])) {
    $amount = (float) $data['amount'];
} elseif (isset($data['data']['amount'])) {
    $amount = (float) $data['data']['amount'];
}

// Query'den gelen userId
if (isset($data['query']['userId'])) {
    $userId = (int) $data['query']['userId'];
} elseif (isset($data['data']['query']['userId'])) {
    $userId = (int) $data['data']['query']['userId'];
} elseif (isset($data['userId'])) {
    $userId = (int) $data['userId'];
}

// Validasyonlar
if ($amount <= 0 || $userId <= 0) {
    Logger::error('Fleeca verify invalid data', ['amount' => $amount, 'userId' => $userId, 'data' => $data]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed');
    exit;
}

// ── Kullanıcı kontrolü ───────────────────────────────────
$db = Database::getConnection();

// Kullanıcı var mı?
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    Logger::error('Fleeca callback: user not found', ['userId' => $userId]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed');
    exit;
}

// ── İdempotency kontrolü (aynı token ikinci kez işlenmesin) ──
$stmt = $db->prepare("SELECT id FROM transactions WHERE reference_id = ? LIMIT 1");
$stmt->execute([$token]);
if ($stmt->fetch()) {
    Logger::warning('Fleeca callback: duplicate token', ['token' => $token, 'userId' => $userId]);
    header('Location: ' . BASE_URL . '/wallet?payment=already');
    exit;
}

// ── Bakiye yükle ─────────────────────────────────────────
try {
    $walletModel = new WalletModel();
    $walletModel->ensureWallet($userId);

    // Transaction ile deposit (reference_id olarak token sakla)
    $db->beginTransaction();

    $stmt = $db->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
    $stmt->execute([$amount, $userId]);

    $stmt = $db->prepare("
        INSERT INTO transactions (user_id, type, amount, description, reference_id)
        VALUES (?, 'deposit', ?, ?, ?)
    ");
    $stmt->execute([$userId, $amount, 'Fleeca Banking ile bakiye yükleme', $token]);

    $db->commit();

    Logger::info('Fleeca payment success', [
        'userId' => $userId,
        'amount' => $amount,
        'token' => $token,
    ]);

    // Başarılı — wallet sayfasına yönlendir
    header('Location: ' . BASE_URL . '/wallet?payment=success');
    exit;

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    Logger::error('Fleeca deposit error', [
        'userId' => $userId,
        'amount' => $amount,
        'error' => $e->getMessage(),
    ]);
    header('Location: ' . BASE_URL . '/wallet?payment=failed');
    exit;
}
