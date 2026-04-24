<?php
/**
 * API: Venue Search (Autocomplete)
 * Auth gerektirmez — sadece onaylı mekanları arar.
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Models/Venue.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['ok' => true, 'data' => []]);
    exit;
}

try {
    $venueModel = new VenueModel();
    $results = $venueModel->search($q, 8);
    echo json_encode(['ok' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
