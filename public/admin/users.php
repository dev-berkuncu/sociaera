<?php
/**
 * Admin — Kullanıcı Listesi (V1)
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
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Report.php';

Auth::requireAccess('users');
$userModel = new UserModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
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
            case 'delete':
                if (Auth::user()['admin_role'] === 'super_admin' && $targetId !== Auth::id()) {
                    $db = Database::getConnection();
                    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
                    Logger::adminAudit('delete_user', 'user', $targetId);
                    Auth::setFlash('success', 'Kullanıcı kalıcı olarak silindi.');
                } else {
                    Auth::setFlash('error', 'Bu işlem için Super Admin yetkisi gereklidir.');
                }
                break;
            case 'reset_balance':
                if (Auth::user()['admin_role'] === 'super_admin') {
                    $db = Database::getConnection();
                    $db->prepare("UPDATE wallets SET balance = 0 WHERE user_id = ?")->execute([$targetId]);
                    Logger::adminAudit('reset_balance', 'wallet', $targetId);
                    Auth::setFlash('success', 'Kullanıcının bakiyesi sıfırlandı.');
                } else {
                    Auth::setFlash('error', 'Bu işlem için Super Admin yetkisi gereklidir.');
                }
                break;
            case 'grant_premium':
                if (Auth::user()['admin_role'] === 'super_admin') {
                    $userModel->setPremium($targetId, 30);
                    Logger::adminAudit('grant_premium', 'user', $targetId, '30 gün');
                    Auth::setFlash('success', 'Kullanıcıya 30 günlük premium tanımlandı.');
                } else {
                    Auth::setFlash('error', 'Bu işlem için Super Admin yetkisi gereklidir.');
                }
                break;
            case 'revoke_premium':
                if (Auth::user()['admin_role'] === 'super_admin') {
                    $userModel->removePremium($targetId);
                    Logger::adminAudit('revoke_premium', 'user', $targetId);
                    Auth::setFlash('success', 'Kullanıcının premium üyeliği iptal edildi.');
                } else {
                    Auth::setFlash('error', 'Bu işlem için Super Admin yetkisi gereklidir.');
                }
                break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/users'); exit;
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$filter = trim($_GET['filter'] ?? 'all');
$result = $userModel->getAll($page, 30, $search, $filter);
$pendingVenues = (new VenueModel())->getPendingCount();

$pageTitle = 'Kullanıcı Yönetimi';
$adminPage = 'users';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">people</span> Kullanıcılar (<?php echo $result['total']; ?>)
    </h1>
    <form method="GET" class="relative">
        <?php if ($filter !== 'all'): ?>
            <input type="hidden" name="filter" value="<?php echo escape($filter); ?>">
        <?php endif; ?>
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
        <input type="text" name="q" placeholder="Kullanıcı ara..." value="<?php echo escape($search); ?>" class="bg-white/5 border border-white/10 text-on-surface rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-primary-container/40 w-64 transition-colors">
    </form>
</div>

<div class="flex gap-2 mb-4 flex-wrap">
    <a href="?filter=all&q=<?php echo escape($search); ?>" class="px-4 py-2 text-xs font-bold rounded-lg border transition-all duration-150 <?php echo $filter === 'all' ? 'bg-primary text-white border-primary shadow-sm shadow-primary/20' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-950'; ?>">Tümü</a>
    <a href="?filter=users&q=<?php echo escape($search); ?>" class="px-4 py-2 text-xs font-bold rounded-lg border transition-all duration-150 <?php echo $filter === 'users' ? 'bg-primary text-white border-primary shadow-sm shadow-primary/20' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-950'; ?>">Normal Üyeler</a>
    <a href="?filter=admins&q=<?php echo escape($search); ?>" class="px-4 py-2 text-xs font-bold rounded-lg border transition-all duration-150 <?php echo $filter === 'admins' ? 'bg-primary text-white border-primary shadow-sm shadow-primary/20' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-950'; ?>">Yetkililer</a>
</div>

<?php file_put_contents(__DIR__ . '/debug_users.txt', print_r($result, true)); ?>
<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl overflow-hidden">
    <div style="font-size: 10px; color: #666; padding: 2px;">DEBUG_ROWS: <?php echo count($result['users']); ?> / <?php echo $result['total']; ?></div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Kullanıcı</th><th class="px-6 py-3">E-posta</th><th class="px-6 py-3">Rol</th><th class="px-6 py-3">Durum</th><th class="px-6 py-3">Kayıt</th><th class="px-6 py-3">İşlem</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($result['users'] as $u):
                    $isBanned = $u['banned_until'] && strtotime($u['banned_until']) > time();
                    $isPrem = !empty($u['is_premium']);
                ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $u['id']; ?></td>
                    <td class="px-6 py-3">
                        <a href="<?php echo BASE_URL; ?>/admin/user-detail?id=<?php echo $u['id']; ?>" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                            <?php echo avatarHtml($u['avatar'] ?? null, $u['username'] ?? 'Bilinmeyen', '32'); ?>
                            <div>
                                <div class="font-semibold text-on-surface flex items-center gap-1">
                                    <?php echo escape($u['username'] ?? 'Bilinmeyen'); ?>
                                    <?php if($isPrem):?><span class="material-symbols-outlined text-amber-400 text-[14px]" data-weight="fill">workspace_premium</span><?php endif;?>
                                </div>
                                <?php if (!empty($u['tag'])): ?><div class="text-xs text-slate-500">@<?php echo escape($u['tag']); ?></div><?php endif; ?>
                            </div>
                        </a>
                    </td>
                    <td class="px-6 py-3 text-slate-400 text-xs"><?php echo escape($u['email']); ?></td>
                    <td class="px-6 py-3">
                        <?php if(!empty($u['admin_role'])):?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border bg-purple-500/10 text-purple-400 border-purple-500/20"><?php echo escape(ucfirst(str_replace('_',' ',$u['admin_role'])));?></span>
                        <?php elseif($u['is_admin']):?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border bg-purple-500/10 text-purple-400 border-purple-500/20">Admin</span>
                        <?php else:?>
                            <span class="text-xs text-slate-500">Üye</span>
                        <?php endif;?>
                    </td>
                    <td class="px-6 py-3">
                        <?php if($isBanned):?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border bg-red-500/10 text-red-400 border-red-500/20">Banlı</span>
                        <?php else:?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aktif</span>
                        <?php endif;?>
                    </td>
                    <td class="px-6 py-3 text-slate-500 text-xs"><?php echo formatDate($u['created_at']); ?></td>
                    <td class="px-6 py-3">
                        <div class="flex gap-1">
                            <a href="<?php echo BASE_URL;?>/admin/user-detail?id=<?php echo $u['id'];?>" class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 flex items-center justify-center transition-colors" title="Detay">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </a>
                            <?php if(Auth::canWrite() && (int)$u['id'] !== Auth::id()):?>
                            <?php if(!$isBanned):?>
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="user_id" value="<?php echo $u['id'];?>"><input type="hidden" name="action" value="ban"><input type="hidden" name="days" value="7">
                                <button class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 flex items-center justify-center transition-colors" title="7 Gün Ban"><span class="material-symbols-outlined text-[18px]">block</span></button></form>
                            <?php else:?>
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="user_id" value="<?php echo $u['id'];?>"><input type="hidden" name="action" value="unban">
                                <button class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 flex items-center justify-center transition-colors" title="Ban Kaldır"><span class="material-symbols-outlined text-[18px]">check_circle</span></button></form>
                            <?php endif;?>
                            
                            <?php if((Auth::user()['admin_role'] ?? '') === 'super_admin'): ?>
                            <?php if(!$isPrem): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Kullanıcıya 30 günlük premium vermek istediğinize emin misiniz?');"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="user_id" value="<?php echo $u['id'];?>"><input type="hidden" name="action" value="grant_premium">
                                <button class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 hover:bg-amber-500/20 flex items-center justify-center transition-colors" title="Premium Ver (30 Gün)"><span class="material-symbols-outlined text-[18px]">workspace_premium</span></button></form>
                            <?php else: ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Kullanıcının premium üyeliğini iptal etmek istediğinize emin misiniz?');"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="user_id" value="<?php echo $u['id'];?>"><input type="hidden" name="action" value="revoke_premium">
                                <button class="w-8 h-8 rounded-lg bg-slate-500/10 text-slate-500 hover:bg-slate-500/20 flex items-center justify-center transition-colors" title="Premium İptal"><span class="material-symbols-outlined text-[18px]">remove_moderator</span></button></form>
                            <?php endif; ?>

                            <form method="POST" class="inline" onsubmit="return confirm('Kullanıcının bakiyesini sıfırlamak istediğinize emin misiniz?');"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="user_id" value="<?php echo $u['id'];?>"><input type="hidden" name="action" value="reset_balance">
                                <button class="w-8 h-8 rounded-lg bg-yellow-500/10 text-yellow-400 hover:bg-yellow-500/20 flex items-center justify-center transition-colors" title="Bakiyeyi Sıfırla"><span class="material-symbols-outlined text-[18px]">money_off</span></button></form>
                            <form method="POST" class="inline" onsubmit="return confirm('Kullanıcıyı tamamen silmek istediğinize emin misiniz? Bu işlem geri alınamaz!');"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="user_id" value="<?php echo $u['id'];?>"><input type="hidden" name="action" value="delete">
                                <button class="w-8 h-8 rounded-lg bg-red-600/10 text-red-500 hover:bg-red-600/20 flex items-center justify-center transition-colors" title="Kullanıcıyı Sil"><span class="material-symbols-outlined text-[18px]">delete_forever</span></button></form>
                            <?php endif; ?>
                            
                            <?php endif;?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($result['pages'] > 1): ?>
<div class="flex justify-center gap-2 mt-6">
    <?php for ($p = 1; $p <= $result['pages']; $p++):
        $pgCls = $p === $page ? 'bg-primary text-white' : 'bg-white/5 text-slate-400 hover:bg-white/10'; ?>
    <a href="?q=<?php echo escape($search); ?>&filter=<?php echo escape($filter); ?>&page=<?php echo $p; ?>" class="<?php echo $pgCls; ?> px-3 py-1.5 rounded-lg text-label-md"><?php echo $p; ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/_footer.php'; ?>
