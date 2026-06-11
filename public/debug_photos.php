<?php
/**
 * Sociaera — Debug Photos
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== System Info ===\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "ROOT_PATH: " . ROOT_PATH . "\n";
echo "PUBLIC_PATH: " . PUBLIC_PATH . "\n";
echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";

echo "\n=== Directory Contents (ROOT/uploads) ===\n";
listDirRecursive(ROOT_PATH . '/uploads');

echo "\n=== Directory Contents (PUBLIC/uploads) ===\n";
listDirRecursive(PUBLIC_PATH . '/uploads');

echo "\n=== Latest Check-ins ===\n";
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, user_id, venue_id, note, image, created_at FROM checkins ORDER BY id DESC LIMIT 20");
    $checkins = $stmt->fetchAll(PDO::class === 'PDO' ? PDO::FETCH_ASSOC : 2);
    foreach ($checkins as $c) {
        $img = $c['image'];
        $resolvedUrl = uploadUrl('posts', $img);
        $existsInRoot = $img ? file_exists(ROOT_PATH . '/uploads/posts/' . $img) : false;
        $existsInPublic = $img ? file_exists(PUBLIC_PATH . '/uploads/posts/' . $img) : false;
        
        echo "ID: {$c['id']} | Note: " . substr($c['note'] ?? '', 0, 30) . "\n";
        echo "  DB Image: " . ($img ? $img : "[NULL/EMPTY]") . "\n";
        echo "  Resolved URL: " . ($resolvedUrl ? $resolvedUrl : "[NULL]") . "\n";
        echo "  Exists in ROOT/uploads: " . ($existsInRoot ? "YES" : "NO") . "\n";
        echo "  Exists in PUBLIC/uploads: " . ($existsInPublic ? "YES" : "NO") . "\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

function listDirRecursive($dir, $prefix = '') {
    if (!is_dir($dir)) {
        echo "Directory does not exist: $dir\n";
        return;
    }
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            echo "{$prefix}[DIR] $file\n";
            listDirRecursive($path, $prefix . '  ');
        } else {
            $size = filesize($path);
            echo "{$prefix}[FILE] $file ($size bytes)\n";
        }
    }
}
