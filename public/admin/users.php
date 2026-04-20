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

$userModel = new UserModel();

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if ($targetId) {
        switch ($action) {
            case 'ban':
                $days = max(1, (int)($_POST['days'] ?? 7));
                $until = date('Y-m-d H:i:s', strtotime("+{$days} days"));
                $userModel->ban($targetId, $until);
                Logger::adminAudit('ban', 'user', $targetId, "{$days} gün");
                Auth::setFlash('success', 'Kullanıcı banlandı.');
                break;
            case 'unban':
                $userModel->unban($targetId);
                Logger::adminAudit('unban', 'user', $targetId);
                Auth::setFlash('success', 'Ban kaldırıldı.');
                break;
            case 'toggle_admin':
                $userModel->toggleAdmin($targetId);
                Logger::adminAudit('toggle_admin', 'user', $targetId);
                Auth::setFlash('success', 'Admin durumu değiştirildi.');
                break;
            case 'delete':
                $userModel->delete($targetId);
                Logger::adminAudit('delete', 'user', $targetId);
                Auth::setFlash('success', 'Kullanıcı silindi.');
                break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/users'); exit;
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$result = $userModel->getAll($page, 30, $search);
$pendingVenues = (new VenueModel())->getPendingCount();

$pageTitle = 'Kullanıcı Yönetimi';
$adminPage = 'users';
require_once __DIR__ . '/../../public/partials/header.php';
require_once __DIR__ . '/../../public/partials/navbar.php';
require_once __DIR__ . '/../../public/partials/flash.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <h1 style="font-size:1.3rem; font-weight:800;"><i class="bi bi-people" style="color:var(--primary)"></i> Kullanıcılar (<?php echo $result['total']; ?>)</h1>
            <form method="GET" class="search-bar" style="max-width:300px; margin:0;">
                <i class="bi bi-search"></i>
                <input type="text" name="q" placeholder="Ara..." value="<?php echo escape($search); ?>">
            </form>
        </div>

        <div class="card-box" style="overflow-x:auto; padding:0;">
            <table class="admin-table">
                <thead><tr><th>#</th><th>Kullanıcı</th><th>E-posta</th><th>Durum</th><th>Kayıt</th><th>İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($result['users'] as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <?php echo avatarHtml($u['avatar'] ?? null, $u['username'], '28'); ?>
                            <div>
                                <div style="font-weight:600;"><?php echo escape($u['username']); ?></div>
                                <?php if ($u['tag']): ?><div style="font-size:0.75rem; color:var(--text-muted);">@<?php echo escape($u['tag']); ?></div><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:0.82rem;"><?php echo escape($u['email']); ?></td>
                    <td>
                        <?php if ($u['is_admin']): ?><span class="status-badge admin">Admin</span><?php endif; ?>
                        <?php if ($u['banned_until'] && strtotime($u['banned_until']) > time()): ?><span class="status-badge banned">Banlı</span>
                        <?php else: ?><span class="status-badge active">Aktif</span><?php endif; ?>
                    </td>
                    <td style="font-size:0.82rem;"><?php echo formatDate($u['created_at']); ?></td>
                    <td>
                        <div style="display:flex; gap:4px;">
                            <?php if (!$u['is_admin'] || (int)$u['id'] !== Auth::id()): ?>
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="action" value="toggle_admin"><button class="btn-secondary-soft btn-sm" title="Admin Toggle"><i class="bi bi-shield"></i></button></form>
                            <?php if (!($u['banned_until'] && strtotime($u['banned_until']) > time())): ?>
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="action" value="ban"><input type="hidden" name="days" value="7"><button class="btn-danger-soft btn-sm" title="7 Gün Ban"><i class="bi bi-slash-circle"></i></button></form>
                            <?php else: ?>
                            <form method="POST" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="action" value="unban"><button class="btn-secondary-soft btn-sm" title="Ban Kaldır"><i class="bi bi-check-circle"></i></button></form>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:20px;">
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <a href="?q=<?php echo escape($search); ?>&page=<?php echo $p; ?>" class="btn-secondary-soft btn-sm" style="<?php echo $p === $page ? 'background:var(--primary-light);color:var(--primary);' : ''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../public/partials/footer.php'; ?>
