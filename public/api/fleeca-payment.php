<?php
/**
 * API: Fleeca Payment — DEPRECATED
 * Bakiye yükleme artık Gateway redirect ile yapılıyor.
 * Bu endpoint yalnızca geriye uyumluluk için korunmuştur.
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';

header('Content-Type: application/json');
echo json_encode([
    'ok' => false,
    'error' => 'Bakiye yükleme artık Fleeca Banking üzerinden yapılmaktadır. Lütfen cüzdan sayfasını kullanın.',
]);
