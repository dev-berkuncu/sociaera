<?php
/**
 * Admin — Rapor Listesi (Moderasyon)
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
require_once __DIR__ . '/../../app/Models/Report.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

Auth::requireAccess('moderation');
$reportModel = new ReportModel();
$status = $_GET['status'] ?? 'pending';
$page = max(1, (int)($_GET['page'] ?? 1));
$result = $reportModel->getAll($page, 30, ['status' => $status]);

$reasonLabels = [
    'spam'=>'Spam','harassment'=>'Hakaret','inappropriate'=>'Uygunsuz','wrong_venue'=>'Yanlış Mekan',
    'fake_checkin'=>'Sahte Check-in','fraud'=>'Dolandırıcılık','privacy'=>'Gizlilik İhlali',
    'copyright'=>'Telif İhlali','other'=>'Diğer'
];

$pendingVenues = (new VenueModel())->getPendingCount();
$pageTitle = 'Moderasyon';
$adminPage = 'moderation';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">flag</span> Raporlar (<?php echo $result['total']; ?>)
    </h1>
</div>

<div class="flex gap-2 mb-6 flex-wrap">
    <?php foreach(['pending'=>'Bekleyen','reviewed'=>'İncelenen','resolved'=>'Çözülen','dismissed'=>'Reddedilen'] as $k=>$l):
        $active = $status===$k;
        $cls=$active?'bg-primary-container text-white':'bg-white/5 text-slate-400 hover:bg-white/10';
    ?>
    <a href="?status=<?php echo $k;?>" class="<?php echo $cls;?> px-4 py-1.5 rounded-lg text-label-md font-semibold transition-colors"><?php echo $l;?></a>
    <?php endforeach;?>
</div>

<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl overflow-hidden">
    <?php if(empty($result['reports'])):?>
        <div class="p-8 text-center text-slate-400">Bu kategoride rapor yok.</div>
    <?php else:?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Raporlayan</th><th class="px-6 py-3">Tür</th><th class="px-6 py-3">Hedef</th><th class="px-6 py-3">Neden</th><th class="px-6 py-3">Tarih</th><th class="px-6 py-3">İşlem</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach($result['reports'] as $r):?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $r['id'];?></td>
                    <td class="px-6 py-3 font-semibold text-on-surface"><?php echo escape($r['reporter_name']);?></td>
                    <td class="px-6 py-3"><span class="bg-white/5 text-slate-300 text-xs px-2 py-1 rounded"><?php echo escape($r['entity_type']);?></span></td>
                    <td class="px-6 py-3 text-slate-300">#<?php echo $r['entity_id'];?></td>
                    <td class="px-6 py-3"><span class="text-xs px-2 py-1 rounded bg-red-500/10 text-red-400"><?php echo escape($reasonLabels[$r['reason']]??$r['reason']);?></span></td>
                    <td class="px-6 py-3 text-slate-500 text-xs"><?php echo timeAgo($r['created_at']);?></td>
                    <td class="px-6 py-3"><a href="<?php echo BASE_URL;?>/admin/report-detail?id=<?php echo $r['id'];?>" class="text-primary-container hover:underline text-xs font-semibold">İncele →</a></td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>
    </div>
    <?php endif;?>
</div>

<?php if($result['pages']>1):?>
<div class="flex justify-center gap-2 mt-6">
    <?php for($p=1;$p<=$result['pages'];$p++):$cls=$p===$page?'bg-primary-container text-white':'bg-white/5 text-slate-400 hover:bg-white/10';?>
    <a href="?status=<?php echo escape($status);?>&page=<?php echo $p;?>" class="<?php echo $cls;?> px-3 py-1.5 rounded-lg text-label-md"><?php echo $p;?></a>
    <?php endfor;?>
</div>
<?php endif;?>

<?php require_once __DIR__ . '/_footer.php'; ?>
