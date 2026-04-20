<?php
/**
 * Admin Panel — Dashboard
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/RateLimit.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';
require_once __DIR__ . '/../../app/Models/Checkin.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Leaderboard.php';
require_once __DIR__ . '/../../app/Models/Ad.php';
require_once __DIR__ . '/../../app/Models/Settings.php';
require_once __DIR__ . '/../../app/Models/Wallet.php';

Auth::requireAdmin();

$db = Database::getConnection();
$pendingVenues = (new VenueModel())->getPendingCount();
$totalUsers = (int) $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$totalVenues = (int) $db->query("SELECT COUNT(*) FROM venues WHERE status = 'approved'")->fetchColumn();
$totalCheckins = (int) $db->query("SELECT COUNT(*) FROM checkins WHERE is_deleted = 0")->fetchColumn();
$todayCheckins = (int) $db->query("SELECT COUNT(*) FROM checkins WHERE is_deleted = 0 AND DATE(created_at) = CURDATE()")->fetchColumn();

// Son admin logları
$logs = $db->query("
    SELECT al.*, u.username FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    ORDER BY al.created_at DESC LIMIT 10
")->fetchAll();

$pageTitle = 'Admin Panel';
$activeNav = '';
$adminPage = 'dashboard';
require_once __DIR__ . '/../../public/partials/header.php';
require_once __DIR__ . '/../../public/partials/navbar.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <div class="admin-content">
        <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:24px;">
            <i class="bi bi-speedometer2" style="color:var(--primary)"></i> Admin Panel
        </h1>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-card-icon">👥</div><div class="stat-card-value"><?php echo $totalUsers; ?></div><div class="stat-card-label">Toplam Üye</div></div>
            <div class="stat-card"><div class="stat-card-icon">📍</div><div class="stat-card-value"><?php echo $totalVenues; ?></div><div class="stat-card-label">Onaylı Mekan</div></div>
            <div class="stat-card"><div class="stat-card-icon">📝</div><div class="stat-card-value"><?php echo $totalCheckins; ?></div><div class="stat-card-label">Toplam Check-in</div></div>
            <div class="stat-card"><div class="stat-card-icon">📆</div><div class="stat-card-value"><?php echo $todayCheckins; ?></div><div class="stat-card-label">Bugünkü Check-in</div></div>
            <div class="stat-card" style="<?php echo $pendingVenues > 0 ? 'border-color:var(--warning);' : ''; ?>"><div class="stat-card-icon">⏳</div><div class="stat-card-value"><?php echo $pendingVenues; ?></div><div class="stat-card-label">Bekleyen Mekan</div></div>
        </div>

        <!-- Son Loglar -->
        <div class="card-box" style="padding:0;">
            <h2 style="padding:20px 20px 12px; font-size:1.1rem; font-weight:700;">Son İşlemler</h2>
            <?php if (empty($logs)): ?>
                <div class="empty-state"><p>Henüz log yok.</p></div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr><th>Admin</th><th>İşlem</th><th>Hedef</th><th>Tarih</th></tr></thead>
                        <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo escape($log['username']); ?></td>
                                <td><?php echo escape($log['action_type']); ?></td>
                                <td><?php echo escape($log['target_type']); ?> #<?php echo $log['target_id']; ?></td>
                                <td><?php echo formatDate($log['created_at'], true); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../public/partials/footer.php'; ?>
