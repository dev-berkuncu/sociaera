<?php
/**
 * Sociaera — Upload Migration Script
 * 
 * Root /uploads/ dizinindeki dosyaları /public/uploads/ dizinine taşır.
 * Hostinger'da document root public/ olduğundan, dosyaların public/uploads/ 
 * altında olması gerekir.
 *
 * Kullanım: https://sociaera.online/admin/migrate-uploads?run=1
 *   - run parametresi olmadan: sadece PREVIEW (ne taşınacağını gösterir)
 *   - run=1 ile: gerçekten taşır
 */

// Bootstrap
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';

// Admin kontrolü (sadece admin çalıştırabilir)
Auth::requireLogin();
$userModel = new UserModel();
$currentUser = $userModel->getById(Auth::id());
if (empty($currentUser['is_admin'])) {
    http_response_code(403);
    die('Yetkiniz yok.');
}

header('Content-Type: text/html; charset=utf-8');

$dryRun = !isset($_GET['run']) || $_GET['run'] !== '1';
$rootUploads = ROOT_PATH . '/uploads';
$publicUploads = UPLOAD_PATH; // PUBLIC_PATH . '/uploads'
$folders = ['avatars', 'banners', 'posts', 'sponsors', 'ads'];
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Upload Migration</title>";
echo "<style>body{font-family:monospace;background:#0b1326;color:#dae2fd;padding:2rem;max-width:800px;margin:0 auto;}";
echo "h1{color:#ff6b35;} .ok{color:#10b981;} .warn{color:#f59e0b;} .err{color:#ef4444;} .info{color:#7bd0ff;}";
echo "pre{background:#1e293b;padding:1rem;border-radius:8px;overflow-x:auto;border:1px solid rgba(255,255,255,0.1);}";
echo "a{color:#ff6b35;}</style></head><body>";
echo "<h1>📦 Upload Migration</h1>";

// ── Durum Raporu ────────────────────────────────────────────
echo "<h2>📁 Dizin Durumu</h2><pre>";
echo "ROOT_PATH:    " . ROOT_PATH . "\n";
echo "PUBLIC_PATH:  " . PUBLIC_PATH . "\n";
echo "UPLOAD_PATH:  " . UPLOAD_PATH . "\n\n";

echo "Root uploads/:   " . (is_dir($rootUploads) ? "<span class='ok'>VAR</span>" : "<span class='warn'>YOK</span>") . "\n";
echo "Public uploads/: " . (is_dir($publicUploads) ? "<span class='ok'>VAR</span>" : "<span class='warn'>YOK</span>") . "\n";
echo "</pre>";

// ── Root uploads/ tarama ────────────────────────────────────
$filesToMigrate = [];
$alreadyInPublic = [];

echo "<h2>🔍 Dosya Tarama</h2><pre>";

foreach ($folders as $folder) {
    $rootFolder = $rootUploads . '/' . $folder;
    $publicFolder = $publicUploads . '/' . $folder;
    
    // Root folder kontrolü
    if (!is_dir($rootFolder)) {
        echo "  /{$folder}/ → <span class='info'>Root dizin yok, atlanıyor</span>\n";
        continue;
    }
    
    $rootFiles = scandir($rootFolder);
    $rootFileCount = 0;
    
    foreach ($rootFiles as $file) {
        if ($file === '.' || $file === '..' || $file === '.gitkeep' || $file === '.htaccess') continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) continue;
        
        $rootFileCount++;
        $srcPath = $rootFolder . '/' . $file;
        $destPath = $publicFolder . '/' . $file;
        
        if (file_exists($destPath)) {
            $alreadyInPublic[] = $folder . '/' . $file;
        } else {
            $filesToMigrate[] = [
                'folder' => $folder,
                'file' => $file,
                'src' => $srcPath,
                'dest' => $destPath,
                'size' => filesize($srcPath),
            ];
        }
    }
    
    // Public folder kontrolü
    $publicFileCount = 0;
    if (is_dir($publicFolder)) {
        foreach (scandir($publicFolder) as $pf) {
            if ($pf === '.' || $pf === '..' || $pf === '.gitkeep' || $pf === '.htaccess') continue;
            $pExt = strtolower(pathinfo($pf, PATHINFO_EXTENSION));
            if (in_array($pExt, $allowedExts)) $publicFileCount++;
        }
    }
    
    echo "  /{$folder}/ → Root: <span class='info'>{$rootFileCount}</span> dosya, Public: <span class='ok'>{$publicFileCount}</span> dosya\n";
}

echo "</pre>";

// ── Taşınacak dosyalar ──────────────────────────────────────
echo "<h2>📋 Sonuç</h2><pre>";

if (empty($filesToMigrate) && empty($alreadyInPublic)) {
    echo "<span class='ok'>✅ Root uploads/ dizininde taşınacak dosya yok.</span>\n";
    echo "<span class='info'>Dosyalar zaten public/uploads/ altında olabilir.</span>\n";
} else {
    if (!empty($filesToMigrate)) {
        $totalSize = array_sum(array_column($filesToMigrate, 'size'));
        echo "<span class='warn'>⚠️ Taşınacak " . count($filesToMigrate) . " dosya bulundu</span> (" . round($totalSize / 1024, 1) . " KB)\n\n";
        
        foreach ($filesToMigrate as $f) {
            echo "  📄 {$f['folder']}/{$f['file']} (" . round($f['size'] / 1024, 1) . " KB)\n";
        }
    }
    
    if (!empty($alreadyInPublic)) {
        echo "\n<span class='info'>ℹ️ " . count($alreadyInPublic) . " dosya zaten public/ altında mevcut (atlanacak)</span>\n";
    }
}

echo "</pre>";

// ── Migration çalıştır ──────────────────────────────────────
if (!empty($filesToMigrate)) {
    if ($dryRun) {
        echo "<h2>🚀 Çalıştır</h2>";
        echo "<p>Yukarıdaki dosyaları taşımak için:</p>";
        echo "<p><a href='?run=1' style='background:#ff6b35;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>Migration'ı Çalıştır</a></p>";
        echo "<p class='warn'>⚠️ Dosyalar root/uploads/'dan public/uploads/'a KOPYALANACAK (orijinaller kalacak).</p>";
    } else {
        echo "<h2>⚙️ Migration Çalışıyor...</h2><pre>";
        
        $success = 0;
        $failed = 0;
        
        foreach ($filesToMigrate as $f) {
            // Hedef klasörü oluştur
            $destDir = dirname($f['dest']);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            // Kopyala (silmeden — güvenlik için)
            if (copy($f['src'], $f['dest'])) {
                echo "<span class='ok'>✅ {$f['folder']}/{$f['file']} → kopyalandı</span>\n";
                $success++;
            } else {
                echo "<span class='err'>❌ {$f['folder']}/{$f['file']} → HATA!</span>\n";
                $failed++;
            }
        }
        
        echo "\n──────────────────────────────\n";
        echo "<span class='ok'>Başarılı: {$success}</span> | <span class='err'>Başarısız: {$failed}</span>\n";
        
        if ($success > 0 && $failed === 0) {
            echo "\n<span class='ok'>✅ Migration tamamlandı! Root uploads/ dizinindeki orijinalleri manuel silebilirsiniz.</span>\n";
        }
        
        echo "</pre>";
    }
}

// ── DB'deki kayıtları kontrol et ────────────────────────────
echo "<h2>🗄️ Veritabanı Kontrolü</h2><pre>";

try {
    $db = Database::getConnection();
    
    // Avatar'ı olan kullanıcılar
    $stmt = $db->query("SELECT COUNT(*) as c FROM users WHERE avatar IS NOT NULL AND avatar != ''");
    $avatarCount = $stmt->fetch()['c'];
    
    // Banner'ı olan kullanıcılar
    $stmt = $db->query("SELECT COUNT(*) as c FROM users WHERE banner IS NOT NULL AND banner != ''");
    $bannerCount = $stmt->fetch()['c'];
    
    // Post image'ı olan check-in'ler
    $stmt = $db->query("SELECT COUNT(*) as c FROM checkins WHERE image IS NOT NULL AND image != ''");
    $postImageCount = $stmt->fetch()['c'];
    
    echo "DB'de avatar kaydı olan kullanıcılar: <span class='info'>{$avatarCount}</span>\n";
    echo "DB'de banner kaydı olan kullanıcılar: <span class='info'>{$bannerCount}</span>\n";
    echo "DB'de fotoğraflı check-in'ler:       <span class='info'>{$postImageCount}</span>\n\n";
    
    // Fiziksel dosya kontrolü — public/uploads
    $missingAvatars = [];
    if ($avatarCount > 0) {
        $stmt = $db->query("SELECT id, username, avatar FROM users WHERE avatar IS NOT NULL AND avatar != ''");
        while ($row = $stmt->fetch()) {
            $path = UPLOAD_PATH . '/avatars/' . $row['avatar'];
            if (!file_exists($path)) {
                $missingAvatars[] = $row['username'] . ' → ' . $row['avatar'];
            }
        }
    }
    
    $missingBanners = [];
    if ($bannerCount > 0) {
        $stmt = $db->query("SELECT id, username, banner FROM users WHERE banner IS NOT NULL AND banner != ''");
        while ($row = $stmt->fetch()) {
            $path = UPLOAD_PATH . '/banners/' . $row['banner'];
            if (!file_exists($path)) {
                $missingBanners[] = $row['username'] . ' → ' . $row['banner'];
            }
        }
    }
    
    $missingPosts = [];
    if ($postImageCount > 0) {
        $stmt = $db->query("SELECT id, image FROM checkins WHERE image IS NOT NULL AND image != '' LIMIT 100");
        while ($row = $stmt->fetch()) {
            $path = UPLOAD_PATH . '/posts/' . $row['image'];
            if (!file_exists($path)) {
                $missingPosts[] = '#' . $row['id'] . ' → ' . $row['image'];
            }
        }
    }
    
    if (empty($missingAvatars) && empty($missingBanners) && empty($missingPosts)) {
        echo "<span class='ok'>✅ Tüm DB kayıtlarının dosyaları public/uploads/ altında mevcut!</span>\n";
    } else {
        if (!empty($missingAvatars)) {
            echo "<span class='err'>❌ Eksik avatarlar (" . count($missingAvatars) . "):</span>\n";
            foreach ($missingAvatars as $m) echo "  📄 {$m}\n";
            echo "\n";
        }
        if (!empty($missingBanners)) {
            echo "<span class='err'>❌ Eksik bannerlar (" . count($missingBanners) . "):</span>\n";
            foreach ($missingBanners as $m) echo "  📄 {$m}\n";
            echo "\n";
        }
        if (!empty($missingPosts)) {
            echo "<span class='err'>❌ Eksik post resimleri (" . count($missingPosts) . "):</span>\n";
            foreach (array_slice($missingPosts, 0, 20) as $m) echo "  📄 {$m}\n";
            if (count($missingPosts) > 20) echo "  ... ve " . (count($missingPosts) - 20) . " tane daha\n";
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "<span class='err'>DB Hatası: " . $e->getMessage() . "</span>\n";
}

echo "</pre>";
echo "<p style='margin-top:2rem;color:#94a3b8;font-size:0.85rem;'>⏰ " . date('Y-m-d H:i:s') . " | Bu scripti işiniz bitince silin.</p>";
echo "</body></html>";
