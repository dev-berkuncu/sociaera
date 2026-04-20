<?php
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/ImageUploader.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';
require_once __DIR__ . '/../../app/Models/Ad.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

Auth::requireAdmin();

$adModel = new AdModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title    = trim($_POST['title'] ?? '');
        $linkUrl  = trim($_POST['link_url'] ?? '');
        $position = $_POST['position'] ?? 'carousel';
        $sort     = (int)($_POST['sort_order'] ?? 0);

        if (!empty($_FILES['image']['name'])) {
            $uploader = new ImageUploader();
            $result = $uploader->upload($_FILES['image'], 'ads', ['maxSize' => MAX_AD_SIZE]);
            if ($result['success']) {
                $adModel->create($title, $result['path'], $linkUrl, $position, $sort);
                Logger::adminAudit('create', 'ad', null, $title);
                Auth::setFlash('success', 'Reklam eklendi.');
            } else {
                Auth::setFlash('error', $result['error']);
            }
        } else {
            Auth::setFlash('error', 'Resim dosyası gerekli.');
        }
    } elseif ($action === 'toggle') {
        $adModel->toggleActive((int)($_POST['ad_id'] ?? 0));
        Auth::setFlash('success', 'Reklam durumu değiştirildi.');
    } elseif ($action === 'delete') {
        $adId = (int)($_POST['ad_id'] ?? 0);
        $adModel->delete($adId);
        Logger::adminAudit('delete', 'ad', $adId);
        Auth::setFlash('success', 'Reklam silindi.');
    }
    header('Location: ' . BASE_URL . '/admin/ads'); exit;
}

$ads = $adModel->getAll();
$pendingVenues = (new VenueModel())->getPendingCount();

$pageTitle = 'Reklam Yönetimi';
$adminPage = 'ads';
require_once __DIR__ . '/../../public/partials/header.php';
require_once __DIR__ . '/../../public/partials/navbar.php';
require_once __DIR__ . '/../../public/partials/flash.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <h1 style="font-size:1.3rem; font-weight:800; margin-bottom:20px;"><i class="bi bi-megaphone" style="color:var(--primary)"></i> Reklamlar</h1>

        <!-- Yeni Reklam -->
        <div class="settings-card" style="margin-bottom:24px;">
            <h2>Yeni Reklam Ekle</h2>
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="create">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group"><label>Başlık</label><input type="text" name="title" class="form-control-styled" required></div>
                    <div class="form-group"><label>Pozisyon</label>
                        <select name="position" class="form-control-styled">
                            <option value="carousel">Carousel (Sağ)</option>
                            <option value="sidebar_left">Sol Sidebar</option>
                            <option value="sidebar_right">Sağ Sidebar</option>
                            <option value="footer_banner">Footer Banner</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Link URL</label><input type="url" name="link_url" class="form-control-styled" placeholder="https://..."></div>
                    <div class="form-group"><label>Sıra</label><input type="number" name="sort_order" class="form-control-styled" value="0"></div>
                </div>
                <div class="form-group"><label>Resim</label><input type="file" name="image" accept="image/*" class="form-control-styled" required></div>
                <button type="submit" class="btn-primary-orange">Reklam Ekle</button>
            </form>
        </div>

        <!-- Mevcut Reklamlar -->
        <div class="card-box" style="overflow-x:auto; padding:0;">
            <table class="admin-table">
                <thead><tr><th>#</th><th>Başlık</th><th>Pozisyon</th><th>Durum</th><th>İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($ads as $ad): ?>
                <tr>
                    <td><?php echo $ad['id']; ?></td>
                    <td style="font-weight:600;"><?php echo escape($ad['title']); ?></td>
                    <td><span class="venue-card-cat" style="margin:0;"><?php echo escape($ad['position']); ?></span></td>
                    <td><span class="status-badge <?php echo $ad['is_active'] ? 'active' : 'rejected'; ?>"><?php echo $ad['is_active'] ? 'Aktif' : 'Pasif'; ?></span></td>
                    <td>
                        <div style="display:flex; gap:4px;">
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>"><input type="hidden" name="action" value="toggle"><button class="btn-secondary-soft btn-sm"><i class="bi bi-toggle-on"></i></button></form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Silmek?')"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>"><input type="hidden" name="action" value="delete"><button class="btn-danger-soft btn-sm"><i class="bi bi-trash3"></i></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../public/partials/footer.php'; ?>
