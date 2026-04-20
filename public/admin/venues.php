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
require_once __DIR__ . '/../../app/Models/Notification.php';

Auth::requireAdmin();

$venueModel = new VenueModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $venueId = (int)($_POST['venue_id'] ?? 0);

    if ($venueId) {
        switch ($action) {
            case 'approve': $venueModel->approve($venueId); Logger::adminAudit('approve', 'venue', $venueId); Auth::setFlash('success', 'Mekan onaylandı.'); break;
            case 'reject': $venueModel->reject($venueId); Logger::adminAudit('reject', 'venue', $venueId); Auth::setFlash('success', 'Mekan reddedildi.'); break;
            case 'delete': $venueModel->delete($venueId); Logger::adminAudit('delete', 'venue', $venueId); Auth::setFlash('success', 'Mekan silindi.'); break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/venues'); exit;
}

$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$venues = $venueModel->getAll($status, $search);
$pendingVenues = $venueModel->getPendingCount();
$categories = VenueModel::categories();

$pageTitle = 'Mekan Yönetimi';
$adminPage = 'venues';
require_once __DIR__ . '/../../public/partials/header.php';
require_once __DIR__ . '/../../public/partials/navbar.php';
require_once __DIR__ . '/../../public/partials/flash.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <h1 style="font-size:1.3rem; font-weight:800; margin-bottom:20px;"><i class="bi bi-geo-alt" style="color:var(--primary)"></i> Mekanlar</h1>

        <div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap;">
            <a href="?status=" class="btn-secondary-soft btn-sm" style="<?php echo !$status ? 'background:var(--primary-light);color:var(--primary);' : ''; ?>">Tümü</a>
            <a href="?status=pending" class="btn-secondary-soft btn-sm" style="<?php echo $status === 'pending' ? 'background:#FFFBEB;color:#92400E;' : ''; ?>">Bekleyen (<?php echo $pendingVenues; ?>)</a>
            <a href="?status=approved" class="btn-secondary-soft btn-sm" style="<?php echo $status === 'approved' ? 'background:#ECFDF5;color:#065F46;' : ''; ?>">Onaylı</a>
            <a href="?status=rejected" class="btn-secondary-soft btn-sm" style="<?php echo $status === 'rejected' ? 'background:#FEF2F2;color:#991B1B;' : ''; ?>">Reddedilmiş</a>
        </div>

        <div class="card-box" style="overflow-x:auto; padding:0;">
            <table class="admin-table">
                <thead><tr><th>#</th><th>Mekan</th><th>Kategori</th><th>Check-in</th><th>Oluşturan</th><th>Durum</th><th>İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($venues as $v): ?>
                <tr>
                    <td><?php echo $v['id']; ?></td>
                    <td style="font-weight:600;"><?php echo escape($v['name']); ?></td>
                    <td><span class="venue-card-cat" style="margin:0;"><?php echo escape($categories[$v['category']] ?? ($v['category'] ?: '-')); ?></span></td>
                    <td><?php echo (int)($v['checkin_count'] ?? 0); ?></td>
                    <td style="font-size:0.82rem;"><?php echo escape($v['creator_name'] ?? '-'); ?></td>
                    <td>
                        <span class="status-badge <?php echo $v['status']; ?>"><?php echo ucfirst($v['status']); ?></span>
                    </td>
                    <td>
                        <div style="display:flex; gap:4px;">
                            <?php if ($v['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="venue_id" value="<?php echo $v['id']; ?>"><input type="hidden" name="action" value="approve"><button class="btn-secondary-soft btn-sm" style="color:var(--success);" title="Onayla"><i class="bi bi-check-lg"></i></button></form>
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="venue_id" value="<?php echo $v['id']; ?>"><input type="hidden" name="action" value="reject"><button class="btn-danger-soft btn-sm" title="Reddet"><i class="bi bi-x-lg"></i></button></form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="venue_id" value="<?php echo $v['id']; ?>"><input type="hidden" name="action" value="delete"><button class="btn-danger-soft btn-sm" title="Sil"><i class="bi bi-trash3"></i></button></form>
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
