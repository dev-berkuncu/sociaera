<?php
/**
 * Admin — Rapor Detay
 */
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
require_once __DIR__ . '/../../app/Models/Report.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

Auth::requireAccess('moderation');
$reportModel = new ReportModel();
$reportId = (int)($_GET['id'] ?? 0);
if (!$reportId) { header('Location: ' . BASE_URL . '/admin/reports'); exit; }

$report = $reportModel->getById($reportId);
if (!$report) { Auth::setFlash('error','Rapor bulunamadı.'); header('Location: ' . BASE_URL . '/admin/reports'); exit; }

$entity = $reportModel->getReportedEntity($report['entity_type'], $report['entity_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');
    $db = Database::getConnection();

    switch ($action) {
        case 'resolve':
            $reportModel->resolve($reportId, Auth::id(), $note);
            Logger::adminAudit('resolve_report','report',$reportId,$note);
            Auth::setFlash('success','Rapor çözüldü.');
            break;
        case 'dismiss':
            $reportModel->dismiss($reportId, Auth::id(), $note);
            Logger::adminAudit('dismiss_report','report',$reportId,$note);
            Auth::setFlash('success','Rapor reddedildi.');
            break;
        case 'hide_content':
            if ($report['entity_type'] === 'checkin') {
                $db->prepare("UPDATE checkins SET is_deleted = 1 WHERE id = ?")->execute([$report['entity_id']]);
            } elseif ($report['entity_type'] === 'comment') {
                $db->prepare("UPDATE post_comments SET is_deleted = 1 WHERE id = ?")->execute([$report['entity_id']]);
            }
            $reportModel->resolve($reportId, Auth::id(), $note ?: 'İçerik gizlendi');
            Logger::adminAudit('hide_reported_content',$report['entity_type'],$report['entity_id']);
            Auth::setFlash('success','İçerik gizlendi ve rapor çözüldü.');
            break;
        case 'ban_user':
            $targetUserId = null;
            if ($entity) {
                $targetUserId = (int)($entity['user_id'] ?? $entity['id'] ?? 0);
            }
            if ($targetUserId) {
                $days = max(1, (int)($_POST['ban_days'] ?? 7));
                $until = date('Y-m-d H:i:s', strtotime("+{$days} days"));
                (new UserModel())->ban($targetUserId, $until);
                Logger::adminAudit('ban','user',$targetUserId,"Rapor #{$reportId} nedeniyle {$days} gün ban");
            }
            $reportModel->resolve($reportId, Auth::id(), $note ?: "Kullanıcı banlandı");
            Auth::setFlash('success','Kullanıcı banlandı ve rapor çözüldü.');
            break;
    }
    header('Location: ' . BASE_URL . '/admin/report-detail?id=' . $reportId); exit;
}

$reasonLabels = [
    'spam'=>'Spam','harassment'=>'Hakaret','inappropriate'=>'Uygunsuz İçerik','wrong_venue'=>'Yanlış Mekan',
    'fake_checkin'=>'Sahte Check-in','fraud'=>'Dolandırıcılık','privacy'=>'Gizlilik İhlali',
    'copyright'=>'Telif İhlali','other'=>'Diğer'
];

$pendingVenues = (new VenueModel())->getPendingCount();
$pageTitle = 'Rapor #' . $reportId;
$adminPage = 'moderation';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center gap-2 text-sm text-slate-400 mb-6">
    <a href="<?php echo BASE_URL;?>/admin/reports" class="hover:text-primary-container">Raporlar</a>
    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
    <span class="text-on-surface">Rapor #<?php echo $reportId;?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Rapor Bilgileri -->
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-6">
        <h3 class="font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-red-400 text-[20px]">flag</span> Rapor Bilgileri
        </h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Durum</span>
                <span class="text-xs px-2 py-1 rounded <?php echo $report['status']==='pending'?'bg-amber-500/10 text-amber-400':'bg-emerald-500/10 text-emerald-400';?>"><?php echo ucfirst($report['status']);?></span>
            </div>
            <div class="flex justify-between"><span class="text-slate-500">Raporlayan</span><span class="text-on-surface font-semibold"><?php echo escape($report['reporter_name']);?></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Tür</span><span class="text-on-surface"><?php echo escape($report['entity_type']);?> #<?php echo $report['entity_id'];?></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Neden</span><span class="text-red-400"><?php echo escape($reasonLabels[$report['reason']]??$report['reason']);?></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Tarih</span><span class="text-on-surface"><?php echo formatDate($report['created_at'],true);?></span></div>
            <?php if($report['description']):?>
            <div><span class="text-slate-500 block mb-1">Açıklama</span><div class="text-slate-300 bg-white/5 rounded-lg p-3"><?php echo nl2brSafe($report['description']);?></div></div>
            <?php endif;?>
            <?php if($report['admin_note']):?>
            <div><span class="text-slate-500 block mb-1">Admin Notu</span><div class="text-slate-300 bg-white/5 rounded-lg p-3"><?php echo nl2brSafe($report['admin_note']);?></div></div>
            <?php endif;?>
        </div>
    </div>

    <!-- Raporlanan İçerik -->
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-6">
        <h3 class="font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-[20px]">preview</span> Raporlanan İçerik
        </h3>
        <?php if(!$entity):?>
            <div class="text-center text-slate-400 py-6">İçerik bulunamadı veya silinmiş.</div>
        <?php else:?>
        <div class="bg-white/5 rounded-lg p-4 text-sm">
            <?php if($report['entity_type']==='checkin'):?>
                <div class="font-semibold text-on-surface mb-1"><?php echo escape($entity['username']??'');?></div>
                <div class="text-slate-400 mb-2"><?php echo escape($entity['venue_name']??'');?></div>
                <div class="text-slate-300"><?php echo nl2brSafe($entity['note']??'');?></div>
            <?php elseif($report['entity_type']==='comment'):?>
                <div class="font-semibold text-on-surface mb-1"><?php echo escape($entity['username']??'');?></div>
                <div class="text-slate-300"><?php echo nl2brSafe($entity['comment']??'');?></div>
            <?php elseif($report['entity_type']==='user'):?>
                <div class="font-semibold text-on-surface"><?php echo escape($entity['username']??'');?></div>
                <div class="text-slate-400">@<?php echo escape($entity['tag']??'');?></div>
            <?php elseif($report['entity_type']==='venue'):?>
                <div class="font-semibold text-on-surface"><?php echo escape($entity['name']??'');?></div>
                <div class="text-slate-400"><?php echo escape($entity['category']??'');?></div>
            <?php endif;?>
        </div>
        <?php endif;?>
    </div>
</div>

<?php if($report['status']==='pending' && Auth::canWrite()):?>
<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-6">
    <h3 class="font-bold text-on-surface mb-4">Admin Aksiyonları</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>">
        <div class="mb-4">
            <label class="block text-label-md text-slate-400 mb-1">Admin Notu</label>
            <textarea name="admin_note" rows="3" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40" placeholder="Karar açıklaması..."></textarea>
        </div>
        <div class="flex flex-wrap gap-3">
            <button name="action" value="dismiss" class="bg-slate-500/10 text-slate-400 border border-slate-500/20 px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-slate-500/20 transition-colors">Raporu Reddet</button>
            <button name="action" value="resolve" class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-emerald-500/20 transition-colors">Çözüldü İşaretle</button>
            <button name="action" value="hide_content" class="bg-amber-500/10 text-amber-400 border border-amber-500/20 px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-amber-500/20 transition-colors">İçeriği Gizle</button>
            <div class="flex gap-2">
                <input type="number" name="ban_days" value="7" min="1" class="w-20 bg-white/5 border border-white/10 text-on-surface rounded-lg px-3 py-2.5 text-sm">
                <button name="action" value="ban_user" class="bg-red-500/10 text-red-400 border border-red-500/20 px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-500/20 transition-colors">Kullanıcıyı Banla</button>
            </div>
        </div>
    </form>
</div>
<?php endif;?>

<?php require_once __DIR__ . '/_footer.php'; ?>
