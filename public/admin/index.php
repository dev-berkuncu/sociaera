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

$logs = $db->query("
    SELECT al.*, u.username FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    ORDER BY al.created_at DESC LIMIT 10
")->fetchAll();

$pageTitle = 'Dashboard';
$adminPage = 'dashboard';
require_once __DIR__ . '/_header.php';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
    <?php
    $stats = [
        ['icon' => 'people', 'value' => $totalUsers, 'label' => 'Toplam Üye', 'color' => 'text-blue-400'],
        ['icon' => 'location_on', 'value' => $totalVenues, 'label' => 'Onaylı Mekan', 'color' => 'text-green-400'],
        ['icon' => 'edit_note', 'value' => $totalCheckins, 'label' => 'Toplam Check-in', 'color' => 'text-purple-400'],
        ['icon' => 'today', 'value' => $todayCheckins, 'label' => 'Bugünkü Check-in', 'color' => 'text-cyan-400'],
        ['icon' => 'pending', 'value' => $pendingVenues, 'label' => 'Bekleyen Mekan', 'color' => $pendingVenues > 0 ? 'text-amber-400' : 'text-slate-400'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <span class="material-symbols-outlined <?php echo $s['color']; ?> text-[28px] mb-2"><?php echo $s['icon']; ?></span>
        <div class="text-2xl font-black text-on-surface"><?php echo $s['value']; ?></div>
        <div class="text-label-sm text-slate-400 mt-1"><?php echo $s['label']; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Son Loglar -->
<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-primary-container text-[20px]">history</span> Son İşlemler
        </h2>
    </div>
    <?php if (empty($logs)): ?>
        <div class="p-8 text-center text-slate-400">Henüz log yok.</div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">Admin</th><th class="px-6 py-3">İşlem</th><th class="px-6 py-3">Hedef</th><th class="px-6 py-3">Tarih</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($logs as $log): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 font-medium"><?php echo escape($log['username']); ?></td>
                    <td class="px-6 py-3 text-slate-300"><?php echo escape($log['action_type']); ?></td>
                    <td class="px-6 py-3 text-slate-400"><?php echo escape($log['target_type']); ?> #<?php echo $log['target_id']; ?></td>
                    <td class="px-6 py-3 text-slate-500 text-xs"><?php echo formatDate($log['created_at'], true); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
