<?php
/**
 * Admin — Yorum Yönetimi
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
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Report.php';

Auth::requireAdmin();
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $commentId = (int)($_POST['comment_id'] ?? 0);
    if ($commentId) {
        switch ($action) {
            case 'hide':
                $db->prepare("UPDATE post_comments SET is_deleted = 1 WHERE id = ?")->execute([$commentId]);
                Logger::adminAudit('hide', 'comment', $commentId);
                Auth::setFlash('success', 'Yorum gizlendi.');
                break;
            case 'restore':
                $db->prepare("UPDATE post_comments SET is_deleted = 0 WHERE id = ?")->execute([$commentId]);
                Logger::adminAudit('restore', 'comment', $commentId);
                Auth::setFlash('success', 'Yorum geri yüklendi.');
                break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/comments'); exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;
$showDeleted = !empty($_GET['deleted']);

$where = $showDeleted ? "1=1" : "pc.is_deleted = 0";
$total = (int)$db->prepare("SELECT COUNT(*) FROM post_comments pc WHERE {$where}")->execute() ? (int)$db->query("SELECT COUNT(*) FROM post_comments pc WHERE {$where}")->fetchColumn() : 0;
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare("
    SELECT pc.*, u.username, u.avatar, c.note as checkin_note, v.name as venue_name
    FROM post_comments pc
    JOIN users u ON pc.user_id = u.id
    JOIN checkins c ON pc.checkin_id = c.id
    JOIN venues v ON c.venue_id = v.id
    WHERE {$where}
    ORDER BY pc.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$comments = $stmt->fetchAll();

$pendingVenues = (new VenueModel())->getPendingCount();
$pageTitle = 'Yorum Yönetimi';
$adminPage = 'comments';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">chat_bubble</span> Yorumlar (<?php echo $total; ?>)
    </h1>
    <div class="flex gap-2">
        <a href="?deleted=<?php echo $showDeleted ? '' : '1'; ?>" class="bg-white/5 text-slate-400 hover:text-white hover:bg-white/10 px-4 py-1.5 rounded-lg text-label-md transition-colors">
            <?php echo $showDeleted ? 'Aktif Göster' : 'Silinenleri Göster'; ?>
        </a>
    </div>
</div>

<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Kullanıcı</th><th class="px-6 py-3">Yorum</th><th class="px-6 py-3">Mekan</th><th class="px-6 py-3">Tarih</th><th class="px-6 py-3">İşlem</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($comments as $c): ?>
                <tr class="hover:bg-white/[0.02] transition-colors <?php echo $c['is_deleted'] ? 'opacity-50' : ''; ?>">
                    <td class="px-6 py-3 text-slate-500"><?php echo $c['id']; ?></td>
                    <td class="px-6 py-3"><div class="flex items-center gap-2"><?php echo avatarHtml($c['avatar']??null,$c['username'],'24');?><span class="font-semibold text-on-surface"><?php echo escape($c['username']);?></span></div></td>
                    <td class="px-6 py-3 text-slate-300 text-xs max-w-[300px] truncate"><?php echo escape(truncate($c['comment']??'',80));?></td>
                    <td class="px-6 py-3 text-slate-400 text-xs"><?php echo escape($c['venue_name']);?></td>
                    <td class="px-6 py-3 text-slate-500 text-xs"><?php echo timeAgo($c['created_at']);?></td>
                    <td class="px-6 py-3">
                        <?php if(Auth::canWrite()):?>
                        <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="comment_id" value="<?php echo $c['id'];?>"><input type="hidden" name="action" value="<?php echo $c['is_deleted']?'restore':'hide';?>">
                            <button class="w-8 h-8 rounded-lg <?php echo $c['is_deleted']?'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20':'bg-red-500/10 text-red-400 hover:bg-red-500/20';?> flex items-center justify-center transition-colors" title="<?php echo $c['is_deleted']?'Geri Yükle':'Gizle';?>">
                                <span class="material-symbols-outlined text-[18px]"><?php echo $c['is_deleted']?'visibility':'visibility_off';?></span>
                            </button>
                        </form>
                        <?php endif;?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($totalPages > 1):?>
<div class="flex justify-center gap-2 mt-6">
    <?php for($p=1;$p<=$totalPages;$p++):$cls=$p===$page?'bg-primary-container text-white':'bg-white/5 text-slate-400 hover:bg-white/10';?>
    <a href="?page=<?php echo $p;?>&deleted=<?php echo $showDeleted?'1':'';?>" class="<?php echo $cls;?> px-3 py-1.5 rounded-lg text-label-md"><?php echo $p;?></a>
    <?php endfor;?>
</div>
<?php endif;?>

<?php require_once __DIR__ . '/_footer.php'; ?>
