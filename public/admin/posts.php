<?php
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';
require_once __DIR__ . '/../../app/Models/Checkin.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Settings.php';

Auth::requireAdmin();

$checkinModel = new CheckinModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId) {
        switch ($action) {
            case 'delete': $checkinModel->adminDelete($postId); Logger::adminAudit('delete', 'post', $postId); Auth::setFlash('success', 'Gönderi silindi.'); break;
            case 'flag': $checkinModel->toggleFlag($postId); Logger::adminAudit('flag', 'post', $postId); Auth::setFlash('success', 'İşaretleme değiştirildi.'); break;
            case 'exclude': $checkinModel->toggleExclude($postId); Logger::adminAudit('exclude_lb', 'post', $postId); Auth::setFlash('success', 'Sıralamayla ilgili durum değiştirildi.'); break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/posts'); exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$result = $checkinModel->adminGetAll($page);
$pendingVenues = (new VenueModel())->getPendingCount();

$pageTitle = 'Gönderi Yönetimi';
$adminPage = 'posts';
require_once __DIR__ . '/../../public/partials/header.php';
require_once __DIR__ . '/../../public/partials/navbar.php';
require_once __DIR__ . '/../../public/partials/flash.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <h1 style="font-size:1.3rem; font-weight:800; margin-bottom:20px;"><i class="bi bi-file-text" style="color:var(--primary)"></i> Gönderiler (<?php echo $result['total']; ?>)</h1>

        <div class="card-box" style="overflow-x:auto; padding:0;">
            <table class="admin-table">
                <thead><tr><th>#</th><th>Kullanıcı</th><th>Mekan</th><th>İçerik</th><th>Tarih</th><th>İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($result['posts'] as $p): ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td style="font-weight:600;"><?php echo escape($p['username']); ?></td>
                    <td style="font-size:0.85rem;"><?php echo escape($p['venue_name']); ?></td>
                    <td style="font-size:0.82rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo escape(truncate($p['note'] ?? '', 60)); ?></td>
                    <td style="font-size:0.82rem;"><?php echo formatDate($p['created_at'], true); ?></td>
                    <td>
                        <div style="display:flex; gap:4px;">
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="post_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="action" value="flag"><button class="btn-secondary-soft btn-sm" title="İşaretle"><i class="bi bi-flag"></i></button></form>
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="post_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="action" value="exclude"><button class="btn-secondary-soft btn-sm" title="Sıralamadan Çıkar"><i class="bi bi-trophy"></i></button></form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="post_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="action" value="delete"><button class="btn-danger-soft btn-sm" title="Sil"><i class="bi bi-trash3"></i></button></form>
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
