<?php
/**
 * API: Venue Search (Autocomplete)
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Models/Venue.php';

if (!Auth::check()) Response::error('Oturum gerekli.', 401);

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) Response::success([]);

$venueModel = new VenueModel();
$results = $venueModel->search($q, 8);
Response::success($results);
