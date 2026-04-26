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
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">location_on</span> Mekanlar
    </h1>
</div>

<!-- Filtreler -->
<div class="flex gap-2 mb-6 flex-wrap">
    <?php
    $filters = [
        '' => ['label' => 'Tümü', 'active' => !$status],
        'pending' => ['label' => 'Bekleyen (' . $pendingVenues . ')', 'active' => $status === 'pending'],
        'approved' => ['label' => 'Onaylı', 'active' => $status === 'approved'],
        'rejected' => ['label' => 'Reddedilmiş', 'active' => $status === 'rejected'],
    ];
    foreach ($filters as $key => $f):
        $cls = $f['active']
            ? 'bg-primary-container text-white px-4 py-1.5 rounded-lg text-label-md font-semibold'
            : 'bg-white/5 text-slate-400 hover:text-white hover:bg-white/10 px-4 py-1.5 rounded-lg text-label-md transition-colors';
    ?>
    <a href="?status=<?php echo $key; ?>" class="<?php echo $cls; ?>"><?php echo $f['label']; ?></a>
    <?php endforeach; ?>
</div>

<!-- Tablo -->
<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Mekan</th><th class="px-6 py-3">Kategori</th><th class="px-6 py-3">Check-in</th><th class="px-6 py-3">Oluşturan</th><th class="px-6 py-3">Durum</th><th class="px-6 py-3">İşlem</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($venues as $v): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $v['id']; ?></td>
                    <td class="px-6 py-3 font-semibold text-on-surface"><?php echo escape($v['name']); ?></td>
                    <td class="px-6 py-3"><span class="bg-white/5 text-slate-300 text-xs px-2 py-1 rounded"><?php echo escape($categories[$v['category']] ?? ($v['category'] ?: '-')); ?></span></td>
                    <td class="px-6 py-3 text-slate-300"><?php echo (int)($v['checkin_count'] ?? 0); ?></td>
                    <td class="px-6 py-3 text-slate-400 text-xs"><?php echo escape($v['creator_name'] ?? '-'); ?></td>
                    <td class="px-6 py-3">
                        <?php
                        $badgeCls = match($v['status']) {
                            'approved' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                            'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                            'rejected' => 'bg-red-500/10 text-red-400 border-red-500/20',
                            default => 'bg-white/5 text-slate-400 border-white/10'
                        };
                        ?>
                        <span class="text-xs font-semibold px-2 py-1 rounded border <?php echo $badgeCls; ?>"><?php echo ucfirst($v['status']); ?></span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex gap-1">
                            <?php if ($v['status'] === 'pending'): ?>
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="venue_id" value="<?php echo $v['id']; ?>"><input type="hidden" name="action" value="approve"><button class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 flex items-center justify-center transition-colors" title="Onayla"><span class="material-symbols-outlined text-[18px]">check</span></button></form>
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="venue_id" value="<?php echo $v['id']; ?>"><input type="hidden" name="action" value="reject"><button class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 flex items-center justify-center transition-colors" title="Reddet"><span class="material-symbols-outlined text-[18px]">close</span></button></form>
                            <?php endif; ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?')"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="venue_id" value="<?php echo $v['id']; ?>"><input type="hidden" name="action" value="delete"><button class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 flex items-center justify-center transition-colors" title="Sil"><span class="material-symbols-outlined text-[18px]">delete</span></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
