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

Auth::requireAccess('dashboard');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'distribute_rewards') {
    Csrf::requireValid();
    
    $prevWeek = LeaderboardModel::getPreviousWeekRange();
    $weekStr = date('Y-\WW', strtotime($prevWeek['start']));

    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'last_rewarded_week'");
    $stmt->execute();
    $lastWeek = $stmt->fetchColumn();

    if ($lastWeek === $weekStr) {
        Auth::setFlash('error', "Geçen haftanın ödülleri zaten dağıtıldı!");
    } else {
        $lbModel = new LeaderboardModel();
        $top3 = $lbModel->getPreviousTopUsers(3);
        
        $walletModel = new WalletModel();
        $notifModel = new NotificationModel();
        $rewards = [3000, 2000, 1000];
        
        $db->beginTransaction();
        try {
            foreach ($top3 as $index => $user) {
                $amount = $rewards[$index] ?? 0;
                if ($amount > 0) {
                    $walletModel->deposit($user['id'], $amount, "Haftalık Liderlik Ödülü (" . ($index+1) . ". Sıra)");
                    $notifModel->create(
                        $user['id'], 
                        Auth::id(), 
                        'wallet', 
                        "Tebrikler! Geçen haftayı " . ($index+1) . ". sırada tamamladınız ve $" . number_format($amount, 0) . " kazandınız!"
                    );
                }
            }
            
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'last_rewarded_week'");
            $stmt->execute([$weekStr]);
            
            $db->commit();
            Auth::setFlash('success', "Geçen haftanın ödülleri başarıyla dağıtıldı!");
        } catch (\Exception $e) {
            $db->rollBack();
            Auth::setFlash('error', "Ödüller dağıtılırken bir hata oluştu: " . $e->getMessage());
        }
    }
    header("Location: " . BASE_URL . "/admin/");
    exit;
}

$adminModel = new AdminModel();
$stats = $adminModel->getDashboardStats();
$regChart = $adminModel->getRegistrationChart(7);
$checkinChart = $adminModel->getCheckinChart(7);
$topVenues = $adminModel->getTopVenues(5);
$topUsers = $adminModel->getTopUsers(5);
$recentTransactions = $adminModel->getRecentTransactions(5);
$recentReports = $adminModel->getRecentReports(5);

$db = Database::getConnection();
$logs = [];
try {
    $logs = $db->query("
        SELECT al.*, u.username FROM admin_logs al
        JOIN users u ON al.admin_id = u.id
        ORDER BY al.created_at DESC LIMIT 10
    ")->fetchAll();
} catch (\Throwable $e) {
    // admin_logs tablosu yoksa sessizce devam et
}

$pendingVenues = $stats['pending_venues'];
$pageTitle = 'Dashboard';
$adminPage = 'dashboard';
require_once __DIR__ . '/_header.php';
?>

<!-- Page header -->
<div class="admin-section-header">
    <h1 class="admin-page-title">
        <span class="material-symbols-outlined" style="color:var(--cp);font-size:22px;" data-fill="1">dashboard</span>
        Dashboard
    </h1>
    <div style="display:flex;align-items:center;gap:12px;">
        <form method="POST" style="margin:0;" onsubmit="return confirm('Geçen haftanın liderlik ödüllerini dağıtmak istediğinize emin misiniz? (İlk 3 kişiye sırasıyla 3k-2k-1k dağıtılacak)');">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="distribute_rewards">
            <button type="submit" class="btn-admin btn-admin-primary">
                <span class="material-symbols-outlined" style="font-size:18px;">workspace_premium</span>
                Geçen Haftanın Ödüllerini Dağıt
            </button>
        </form>
        <span style="font-size:12px;color:var(--t3);"><?php echo date('d M Y, H:i'); ?></span>
    </div>
</div>

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
        ['icon' => 'payments',           'value' => '$' . number_format($stats['monthly_earnings'], 0), 'label' => 'Aylık Kazanç',      'color' => 'text-emerald-500'],
        ['icon' => 'account_balance',    'value' => '$' . number_format($stats['total_earnings'], 0),   'label' => 'Toplam Kazanç',     'color' => 'text-amber-500'],
        ['icon' => 'account_balance_wallet', 'value' => '$' . number_format($stats['total_wallet_balance'], 0), 'label' => 'Kullanıcı Bakiyesi', 'color' => 'text-yellow-400'],
        ['icon' => 'workspace_premium',  'value' => $stats['premium_users'],      'label' => 'Premium Üye',       'color' => 'text-orange-400'],
        ['icon' => 'pending',            'value' => $stats['pending_venues'],      'label' => 'Bekleyen Mekan',    'color' => $stats['pending_venues'] > 0 ? 'text-amber-400' : 'text-slate-400'],
    ];
    foreach ($statCards as $s):
    ?>
    <div class="admin-stat-card">
        <span class="material-symbols-outlined" style="font-size:28px;color:var(--cp);margin-bottom:4px;display:block;" data-fill="1"><?php echo $s['icon']; ?></span>
        <div class="admin-stat-value"><?php echo $s['value']; ?></div>
        <div class="admin-stat-label"><?php echo $s['label']; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Kayıt Grafiği -->
    <div class="admin-chart-card">
        <div class="admin-chart-title">
            <span class="material-symbols-outlined" style="color:#3B82F6;font-size:20px;">person_add</span> Son 7 Gün — Kayıtlar
        </div>
        <div style="height:200px;position:relative;"><canvas id="regChart"></canvas></div>
    </div>
    <!-- Check-in Grafiği -->
    <div class="admin-chart-card">
        <div class="admin-chart-title">
            <span class="material-symbols-outlined" style="color:#8B5CF6;font-size:20px;">edit_note</span> Son 7 Gün — Check-in'ler
        </div>
        <div style="height:200px;position:relative;"><canvas id="checkinChart"></canvas></div>
    </div>
</div>

<!-- Top Lists -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- En Popüler Mekanlar -->
    <div class="admin-table-card">
        <div class="admin-table-head">
            <div class="admin-table-title">
                <span class="material-symbols-outlined" style="color:#16A34A;font-size:18px;">trending_up</span> En Popüler Mekanlar
            </div>
        </div>
        <?php if (empty($topVenues)): ?>
            <div style="padding:24px;text-align:center;color:var(--t3);">Henüz veri yok.</div>
        <?php else: ?>
        <?php foreach ($topVenues as $i => $tv): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border-l);" onmouseover="this.style.background='var(--section)'" onmouseout="this.style.background=''"> 
            <span style="font-size:13px;font-weight:800;color:var(--t3);width:20px;text-align:center;flex-shrink:0;"><?php echo $i + 1; ?></span>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo escape($tv['name']); ?></div>
                <div style="font-size:11px;color:var(--t3);"><?php echo escape(VenueModel::categories()[$tv['category']] ?? $tv['category']); ?></div>
            </div>
            <span style="font-size:13px;font-weight:800;color:var(--cp);flex-shrink:0;"><?php echo $tv['checkin_count']; ?> ✓</span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- En Aktif Kullanıcılar -->
    <div class="admin-table-card">
        <div class="admin-table-head">
            <div class="admin-table-title">
                <span class="material-symbols-outlined" style="color:#F59E0B;font-size:18px;">emoji_events</span> En Aktif Kullanıcılar
            </div>
        </div>
        <?php if (empty($topUsers)): ?>
            <div style="padding:24px;text-align:center;color:var(--t3);">Henüz veri yok.</div>
        <?php else: ?>
        <?php foreach ($topUsers as $i => $tu): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border-l);" onmouseover="this.style.background='var(--section)'" onmouseout="this.style.background=''"> 
            <span style="font-size:13px;font-weight:800;color:var(--t3);width:20px;text-align:center;flex-shrink:0;"><?php echo $i + 1; ?></span>
            <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;">
                <?php echo avatarHtml($tu['avatar'] ?? null, $tu['username'], '28'); ?>
                <a href="<?php echo BASE_URL; ?>/admin/user-detail?id=<?php echo $tu['id']; ?>" style="font-size:13px;font-weight:700;color:var(--t1);text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" onmouseover="this.style.color='var(--cp)'" onmouseout="this.style.color='var(--t1)'"><?php echo escape($tu['username']); ?></a>
            </div>
            <span style="font-size:13px;font-weight:800;color:var(--cp);flex-shrink:0;"><?php echo $tu['checkin_count']; ?> ✓</span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Transactions & Reports -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Son Ödeme İşlemleri -->
    <div class="admin-table-card">
        <div class="admin-table-head">
            <div class="admin-table-title"><span class="material-symbols-outlined" style="color:#F59E0B;font-size:18px;">payments</span> Son Ödemeler</div>
            <a href="<?php echo BASE_URL; ?>/admin/wallet" class="admin-table-link">Tümü →</a>
        </div>
        <?php if (empty($recentTransactions)): ?>
            <div style="padding:24px;text-align:center;color:var(--t3);">Henüz ödeme yok.</div>
        <?php else: ?>
        <?php foreach ($recentTransactions as $rt): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border-l);" onmouseover="this.style.background='var(--section)'" onmouseout="this.style.background=''">
            <span class="material-symbols-outlined" style="font-size:18px;color:<?php echo $rt['type']==='deposit'?'#16A34A':'#DC2626'; ?>;flex-shrink:0;">
                <?php echo $rt['type']==='deposit' ? 'arrow_downward' : 'arrow_upward'; ?>
            </span>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:var(--t1);"><?php echo escape($rt['username']); ?></div>
                <div style="font-size:11px;color:var(--t3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo escape(truncate($rt['description']??'',40)); ?></div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:13px;font-weight:800;color:<?php echo $rt['type']==='deposit'?'#16A34A':'#DC2626'; ?>;"><?php echo ($rt['type']==='deposit'?'+':'-').'$'.number_format($rt['amount'],0,',','.'); ?></div>
                <div style="font-size:10px;color:var(--t3);"><?php echo timeAgo($rt['created_at']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Son Raporlar -->
    <div class="admin-table-card">
        <div class="admin-table-head">
            <div class="admin-table-title"><span class="material-symbols-outlined" style="color:#DC2626;font-size:18px;">flag</span> Bekleyen Raporlar</div>
            <a href="<?php echo BASE_URL; ?>/admin/reports" class="admin-table-link">Tümü →</a>
        </div>
        <?php if (empty($recentReports)): ?>
            <div style="padding:24px;text-align:center;color:var(--t3);">Bekleyen rapor yok. ✓</div>
        <?php else: ?>
        <?php foreach ($recentReports as $rr): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border-l);" onmouseover="this.style.background='var(--section)'" onmouseout="this.style.background=''">
            <span class="material-symbols-outlined" style="font-size:18px;color:#DC2626;flex-shrink:0;">report</span>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:var(--t1);"><?php echo escape($rr['entity_type']); ?> #<?php echo $rr['entity_id']; ?></div>
                <div style="font-size:11px;color:var(--t3);"><?php echo escape($rr['reason']); ?> — <?php echo escape($rr['reporter_name']); ?></div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <a href="<?php echo BASE_URL; ?>/admin/report-detail?id=<?php echo $rr['id']; ?>" style="font-size:12px;font-weight:700;color:var(--cp);text-decoration:none;">İncele →</a>
                <div style="font-size:10px;color:var(--t3);"><?php echo timeAgo($rr['created_at']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Son Loglar -->
<?php
// Gizli müşteri bekleyen sayısı
require_once __DIR__ . '/../../app/Models/MysteryShopperModel.php';
$mysteryPending = 0;
try { $mysteryPending = (new MysteryShopperModel())->countPending(); } catch (\Throwable $e) {}
?>

<!-- Mystery Shopper Kart -->
<div style="background:#fff;border:1.5px solid #E0E7FF;border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;margin-bottom:20px;box-shadow:var(--shadow);">
    <div style="width:50px;height:50px;border-radius:12px;background:#EEF2FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <span class="material-symbols-outlined" style="font-size:26px;color:#4F46E5;" data-fill="1">person_search</span>
    </div>
    <div style="flex:1;">
        <div style="font-size:14px;font-weight:700;color:var(--t1);">Gizli Müşteri Başvuruları</div>
        <div style="font-size:12px;color:var(--t3);margin-top:2px;">
            <?php if ($mysteryPending > 0): ?>
                <span style="color:#D97706;font-weight:700;"><?php echo $mysteryPending; ?> başvuru</span> inceleme bekliyor
            <?php else: ?>
                Bekleyen başvuru yok
            <?php endif; ?>
        </div>
    </div>
    <a href="<?php echo BASE_URL; ?>/admin/mystery" class="btn-admin btn-admin-ghost" style="color:#4F46E5;border-color:#C7D2FE;background:#EEF2FF;gap:6px;">
        <?php if ($mysteryPending > 0): ?>
            <span style="width:7px;height:7px;border-radius:50%;background:#F59E0B;display:inline-block;"></span>
        <?php endif; ?>
        İncele →
    </a>
</div>

<div class="admin-table-card">
    <div class="admin-table-head">
        <div class="admin-table-title"><span class="material-symbols-outlined" style="color:var(--cp);font-size:18px;">history</span> Son Admin İşlemleri</div>
        <a href="<?php echo BASE_URL; ?>/admin/audit" class="admin-table-link">Tümünü Gör →</a>
    </div>
    <?php if (empty($logs)): ?>
        <div style="padding:32px;text-align:center;color:var(--t3);">Henüz log yok.</div>
    <?php else: ?>
    <div class="admin-table-overflow">
        <table class="admin-table">
            <thead><tr><th>Admin</th><th>İşlem</th><th>Hedef</th><th>Tarih</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="font-weight:700;"><?php echo escape($log['username']); ?></td>
                    <td><?php echo escape($log['action_type']); ?></td>
                    <td style="color:var(--t2);"><?php echo escape($log['target_type']); ?> #<?php echo $log['target_id']; ?></td>
                    <td style="font-size:11px;color:var(--t3);"><?php echo formatDate($log['created_at'], true); ?></td>
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
            x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { color: '#A0A0A0', font: { size: 11, family: 'Plus Jakarta Sans' } } },
            y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { color: '#A0A0A0', font: { size: 11, family: 'Plus Jakarta Sans' }, beginAtZero: true } }
        }
    };

    // Kayıt grafiği
    new Chart(document.getElementById('regChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($regChart['labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($regChart['data']); ?>,
                backgroundColor: 'rgba(240,109,31,0.15)',
                borderColor: '#F06D1F',
                borderWidth: 2,
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
                borderColor: '#8B5CF6',
                backgroundColor: 'rgba(139,92,246,0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#8B5CF6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: chartDefaults
    });
});
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
