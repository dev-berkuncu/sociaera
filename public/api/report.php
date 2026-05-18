<?php
/**
 * API — İçerik Raporlama
 * POST /api/report
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/Report.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

if (!Auth::check()) {
    Response::json(['ok' => false, 'error' => 'Giriş yapmalısınız.'], 401);
}

Csrf::requireValid();

$entityType = $_POST['entity_type'] ?? '';
$entityId   = (int)($_POST['entity_id'] ?? 0);
$reason     = $_POST['reason'] ?? '';
$description = trim($_POST['description'] ?? '');

$validTypes = ['checkin', 'comment', 'user', 'venue'];
$validReasons = ['spam','harassment','inappropriate','wrong_venue','fake_checkin','fraud','privacy','copyright','other'];

if (!in_array($entityType, $validTypes) || !$entityId || !in_array($reason, $validReasons)) {
    Response::json(['ok' => false, 'error' => 'Geçersiz rapor parametreleri.']);
}

try {
    $reportModel = new ReportModel();
    $reportId = $reportModel->create(Auth::id(), $entityType, $entityId, $reason, $description ?: null);
    Response::json(['ok' => true, 'report_id' => $reportId, 'message' => 'Raporunuz alındı. Ekibimiz inceleyecek.']);
} catch (\Throwable $e) {
    Logger::error('Report creation failed', ['error' => $e->getMessage()]);
    Response::json(['ok' => false, 'error' => 'Rapor oluşturulamadı.'], 500);
}
