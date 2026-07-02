<?php
/**
 * Uploaded File Proxy
 * Symlink'e izin verilmeyen katı paylaşımlı hostinglerde (Hostinger vb.)
 * resimleri dış (güvenli) dizinden okuyarak HTTP üzerinden sunar.
 */

require __DIR__ . '/../app/Config/env.php';
loadEnv(__DIR__ . '/../.env');
require __DIR__ . '/../app/Config/app.php';

$path = $_GET['path'] ?? '';
if (empty($path)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Yolu temizle (../ ile dizin dışına çıkılmasını engelle)
$path = str_replace(['../', '..\\'], '', $path);
$path = ltrim($path, '/');

// Dosyanın asıl bulunduğu güvenli dış dizin
$persistentUploads = dirname(ROOT_PATH) . '/sociaera_uploads_persistent';
$file = $persistentUploads . '/' . $path;

// Dosya var mı ve güvenli mi kontrol et
if (file_exists($file) && is_file($file)) {
    $mime = mime_content_type($file);
    if (!$mime) $mime = 'application/octet-stream';
    
    // Basit cache başlıkları (tarayıcı sürekli sunucuyu yormasın)
    $expires = 60 * 60 * 24 * 30; // 30 gün
    header("Pragma: public");
    header("Cache-Control: max-age=" . $expires);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
    
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

// Dosya yoksa 404 dön
header("HTTP/1.0 404 Not Found");
exit;
