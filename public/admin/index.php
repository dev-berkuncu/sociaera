<?php
/**
 * Admin Panel — Dashboard (V1)
 * 13+ metrik kartı, grafikler, top listeler
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
require_once __DIR__ . '/../../app/Models/Admin.php';
require_once __DIR__ . '/../../app/Models/Report.php';

Auth::requireAdmin();

$adminModel = new AdminModel();
$stats = $adminModel->getDashboardStats();
$regChart = $adminModel->getRegistrationChart(7);
$checkinChart = $adminModel->getCheckinChart(7);
$topVenues = $adminModel->getTopVenues(5);
$topUsers = $adminModel->getTopUsers(5);
$recentTransactions = $adminModel->getRecentTransactions(5);
$recentReports = $adminModel->getRecentReports(5);

$db = Database::getConnection();
$logs = $db->query("
    SELECT al.*, u.username FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    ORDER BY al.created_at DESC LIMIT 10
")->fetchAll();

$pendingVenues = $stats['pending_venues'];
$pageTitle = 'Dashboard';
$adminPage = 'dashboard';
require_once __DIR__ . '/_header.php';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8">
    <?php
    $statCards = [
        ['icon' => 'people',             'value' => $stats['total_users'],         'label' => 'Toplam Üye',        'color' => 'text-blue-400'],
        ['icon' => 'person_add',         'value' => $stats['today_registrations'], 'label' => 'Bugün Kayıt',       'color' => 'text-cyan-400'],
        ['icon' => 'bolt',               'value' => $stats['active_users'],        'label' => 'Aktif (7 gün)',     'color' => 'text-emerald-400'],
        ['icon' => 'location_on',        'value' => $stats['total_venues'],        'label' => 'Onaylı Mekan',      'color' => 'text-green-400'],
        ['icon' => 'edit_note',          'value' => $stats['total_checkins'],      'label' => 'Toplam Check-in',   'color' => 'text-purple-400'],
        ['icon' => 'today',              'value' => $stats['today_checkins'],      'label' => 'Bugün Check-in',    'color' => 'text-indigo-400'],
        ['icon' => 'flag',               'value' => $stats['pending_reports'],     'label' => 'Bekleyen Rapor',    'color' => $stats['pending_reports'] > 0 ? 'text-red-400' : 'text-slate-400'],
        ['icon' => 'payments',           'value' => $stats['successful_payments'], 'label' => 'Toplam Ödeme',      'color' => 'text-amber-400'],
        ['icon' => 'account_balance_wallet', 'value' => '$' . number_format($stats['total_wallet_balance'], 0), 'label' => 'Toplam Bakiye', 'color' => 'text-yellow-400'],
        ['icon' => 'workspace_premium',  'value' => $stats['premium_users'],      'label' => 'Premium Üye',       'color' => 'text-orange-400'],
        ['icon' => 'pending',            'value' => $stats['pending_venues'],      'label' => 'Bekleyen Mekan',    'color' => $stats['pending_venues'] > 0 ? 'text-amber-400' : 'text-slate-400'],
    ];
    foreach ($statCards as $s):
    ?>
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] hover:border-white/20 transition-colors">
        <span class="material-symbols-outlined <?php echo $s['color']; ?> text-[28px] mb-2"><?php echo $s['icon']; ?></span>
        <div class="text-2xl font-black text-on-surface"><?php echo $s['value']; ?></div>
        <div class="text-label-sm text-slate-400 mt-1"><?php echo $s['label']; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Kayıt Grafiği -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <h3 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-400 text-[20px]">person_add</span> Son 7 Gün — Kayıtlar
        </h3>
        <canvas id="regChart" height="200"></canvas>
    </div>
    <!-- Check-in Grafiği -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <h3 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-purple-400 text-[20px]">edit_note</span> Son 7 Gün — Check-in'ler
        </h3>
        <canvas id="checkinChart" height="200"></canvas>
    </div>
</div>

<!-- Top Lists -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- En Popüler Mekanlar -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5">
            <h3 class="text-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-green-400 text-[20px]">trending_up</span> En Popüler Mekanlar
            </h3>
        </div>
        <?php if (empty($topVenues)): ?>
            <div class="p-6 text-center text-slate-400">Henüz veri yok.</div>
        <?php else: ?>
        <div class="divide-y divide-white/5">
            <?php foreach ($topVenues as $i => $tv): ?>
            <div class="flex items-center gap-4 px-6 py-3 hover:bg-white/[0.02] transition-colors">
                <span class="text-slate-500 font-bold w-6 text-center"><?php echo $i + 1; ?></span>
                <div class="flex-grow min-w-0">
                    <div class="font-semibold text-on-surface truncate"><?php echo escape($tv['name']); ?></div>
                    <span class="text-xs text-slate-500"><?php echo escape(VenueModel::categories()[$tv['category']] ?? $tv['category']); ?></span>
                </div>
                <span class="text-sm font-bold text-primary-container"><?php echo $tv['checkin_count']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- En Aktif Kullanıcılar -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5">
            <h3 class="text-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-400 text-[20px]">emoji_events</span> En Aktif Kullanıcılar
            </h3>
        </div>
        <?php if (empty($topUsers)): ?>
            <div class="p-6 text-center text-slate-400">Henüz veri yok.</div>
        <?php else: ?>
        <div class="divide-y divide-white/5">
            <?php foreach ($topUsers as $i => $tu): ?>
            <div class="flex items-center gap-4 px-6 py-3 hover:bg-white/[0.02] transition-colors">
                <span class="text-slate-500 font-bold w-6 text-center"><?php echo $i + 1; ?></span>
                <div class="flex items-center gap-3 flex-grow min-w-0">
                    <?php echo avatarHtml($tu['avatar'] ?? null, $tu['username'], '28'); ?>
                    <div class="truncate">
                        <a href="<?php echo BASE_URL; ?>/admin/user-detail?id=<?php echo $tu['id']; ?>" class="font-semibold text-on-surface hover:text-primary-container transition-colors"><?php echo escape($tu['username']); ?></a>
                    </div>
                </div>
                <span class="text-sm font-bold text-primary-container"><?php echo $tu['checkin_count']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Transactions & Reports -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Son Ödeme İşlemleri -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5">
            <h3 class="text-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-yellow-400 text-[20px]">payments</span> Son Ödemeler
            </h3>
        </div>
        <?php if (empty($recentTransactions)): ?>
            <div class="p-6 text-center text-slate-400">Henüz ödeme yok.</div>
        <?php else: ?>
        <div class="divide-y divide-white/5">
            <?php foreach ($recentTransactions as $rt): ?>
            <div class="flex items-center gap-4 px-6 py-3 hover:bg-white/[0.02] transition-colors">
                <span class="material-symbols-outlined text-[18px] <?php echo $rt['type'] === 'deposit' ? 'text-emerald-400' : 'text-red-400'; ?>">
                    <?php echo $rt['type'] === 'deposit' ? 'arrow_downward' : 'arrow_upward'; ?>
                </span>
                <div class="flex-grow min-w-0">
                    <div class="font-semibold text-on-surface"><?php echo escape($rt['username']); ?></div>
                    <span class="text-xs text-slate-500"><?php echo escape(truncate($rt['description'] ?? '', 40)); ?></span>
                </div>
                <div class="text-right">
                    <span class="font-bold <?php echo $rt['type'] === 'deposit' ? 'text-emerald-400' : 'text-red-400'; ?>">$<?php echo number_format($rt['amount'], 2); ?></span>
                    <div class="text-[10px] text-slate-500"><?php echo timeAgo($rt['created_at']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Son Raporlar -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5">
            <h3 class="text-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-red-400 text-[20px]">flag</span> Bekleyen Raporlar
            </h3>
        </div>
        <?php if (empty($recentReports)): ?>
            <div class="p-6 text-center text-slate-400">Bekleyen rapor yok.</div>
        <?php else: ?>
        <div class="divide-y divide-white/5">
            <?php foreach ($recentReports as $rr): ?>
            <div class="flex items-center gap-4 px-6 py-3 hover:bg-white/[0.02] transition-colors">
                <span class="material-symbols-outlined text-[18px] text-red-400">report</span>
                <div class="flex-grow min-w-0">
                    <div class="font-semibold text-on-surface"><?php echo escape($rr['entity_type']); ?> #<?php echo $rr['entity_id']; ?></div>
                    <span class="text-xs text-slate-500"><?php echo escape($rr['reason']); ?> — <?php echo escape($rr['reporter_name']); ?></span>
                </div>
                <div class="text-right">
                    <a href="<?php echo BASE_URL; ?>/admin/report-detail?id=<?php echo $rr['id']; ?>" class="text-xs text-primary-container hover:underline">İncele</a>
                    <div class="text-[10px] text-slate-500"><?php echo timeAgo($rr['created_at']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Son Loglar -->
<?php
// Gizli müşteri bekleyen sayısı
require_once __DIR__ . '/../../app/Models/MysteryShopperModel.php';
$mysteryPending = (new MysteryShopperModel())->countPending();
?>

<!-- Mystery Shopper Kart -->
<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-indigo-500/20 rounded-xl p-6 flex items-center gap-4 mb-6 hover:border-indigo-500/40 transition-colors">
    <div class="w-14 h-14 rounded-xl bg-indigo-500/15 flex items-center justify-center flex-shrink-0">
        <span class="material-symbols-outlined text-indigo-400 text-[28px]">person_search</span>
    </div>
    <div class="flex-grow">
        <div class="font-bold text-on-surface">Gizli Müşteri Başvuruları</div>
        <div class="text-sm text-slate-400 mt-0.5">
            <?php if ($mysteryPending > 0): ?>
                <span class="text-amber-400 font-semibold"><?php echo $mysteryPending; ?> başvuru</span> inceleme bekliyor
            <?php else: ?>
                Bekleyen başvuru yok
            <?php endif; ?>
        </div>
    </div>
    <a href="<?php echo BASE_URL; ?>/admin/mystery"
       class="flex-shrink-0 px-4 py-2 rounded-lg bg-indigo-500/15 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/25 text-sm font-semibold transition-colors flex items-center gap-1">
        <?php if ($mysteryPending > 0): ?>
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
        <?php endif; ?>
        İncele →
    </a>
</div>

<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-primary-container text-[20px]">history</span> Son İşlemler
        </h2>
        <a href="<?php echo BASE_URL; ?>/admin/audit" class="text-xs text-primary-container hover:underline">Tümünü Gör →</a>
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

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 11 } } },
            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 11 }, beginAtZero: true } }
        }
    };

    // Kayıt grafiği
    new Chart(document.getElementById('regChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($regChart['labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($regChart['data']); ?>,
                backgroundColor: 'rgba(96, 165, 250, 0.4)',
                borderColor: '#60a5fa',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: chartDefaults
    });

    // Check-in grafiği
    new Chart(document.getElementById('checkinChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($checkinChart['labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($checkinChart['data']); ?>,
                borderColor: '#a78bfa',
                backgroundColor: 'rgba(167, 139, 250, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#a78bfa'
            }]
        },
        options: chartDefaults
    });
});
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
