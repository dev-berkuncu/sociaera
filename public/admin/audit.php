<?php
// Audit log kaldırıldı — kullanıcı verisi tutulmayacak politikası gereği.
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Models/User.php';
Auth::requireAdmin();
Auth::setFlash('info', 'Audit log kaldırıldı.');
header('Location: ' . BASE_URL . '/admin/');
exit;

$page = max(1, (int)($_GET['page'] ?? 1));
$filters = [];
if (!empty($_GET['action_type'])) $filters['action_type'] = $_GET['action_type'];
$result = Logger::getAuditLogs($page, 50, $filters);

$pendingVenues = (new VenueModel())->getPendingCount();
$pageTitle = 'Audit Log';
$adminPage = 'audit';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">history</span> Audit Log (<?php echo $result['total'];?>)
    </h1>
</div>

<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl overflow-hidden">
    <?php if(empty($result['logs'])):?>
        <div class="p-8 text-center text-slate-400">Henüz log yok.</div>
    <?php else:?>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Admin</th><th class="px-6 py-3">İşlem</th><th class="px-6 py-3">Hedef</th><th class="px-6 py-3">Detay</th><th class="px-6 py-3">IP</th><th class="px-6 py-3">Tarih</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach($result['logs'] as $log):?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $log['id'];?></td>
                    <td class="px-6 py-3 font-semibold text-on-surface"><?php echo escape($log['admin_name']);?></td>
                    <td class="px-6 py-3"><span class="text-xs px-2 py-1 rounded bg-white/5 text-slate-300"><?php echo escape($log['action_type']);?></span></td>
                    <td class="px-6 py-3 text-slate-300"><?php echo escape($log['target_type']);?> <?php echo $log['target_id']?'#'.$log['target_id']:'';?></td>
                    <td class="px-6 py-3 text-slate-400 text-xs max-w-[200px] truncate"><?php echo escape(truncate($log['details']??'',50));?></td>
                    <td class="px-6 py-3 text-slate-500 text-xs font-mono"><?php echo escape($log['ip']??'');?></td>
                    <td class="px-6 py-3 text-slate-500 text-xs"><?php echo formatDate($log['created_at'],true);?></td>
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
    <a href="?page=<?php echo $p;?>" class="<?php echo $cls;?> px-3 py-1.5 rounded-lg text-label-md"><?php echo $p;?></a>
    <?php endfor;?>
</div>
<?php endif;?>

<?php require_once __DIR__ . '/_footer.php'; ?>
