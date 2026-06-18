<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';

// Mock Auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

ob_start();
require __DIR__ . '/add-venue.php';
$html = ob_get_clean();

// Check if form exists
if (strpos($html, '<form method="POST"') !== false) {
    echo "FORM EXISTS.\n";
} else {
    echo "FORM IS MISSING!\n";
}
echo "HTML Length: " . strlen($html) . "\n";
$start = strpos($html, '<!-- Feed area starts');
$end = strpos($html, '<!-- Feed area ends');
if ($start !== false && $end !== false) {
    echo substr($html, $start, $end - $start);
} else {
    echo "Layout markers missing!\n";
    echo substr($html, 500, 1000); // print some part
}
