<?php
/**
 * Admin — Kullanıcı Detay Sayfası
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
require_once __DIR__ . '/../../app/Models/Wallet.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Report.php';

Auth::requireAdmin();
$userId = (int)($_GET['id'] ?? 0);
if (!$userId) { header('Location: ' . BASE_URL . '/admin/users'); exit; }

$userModel = new UserModel();
$user = $userModel->getById($userId);
if (!$user) { Auth::setFlash('error', 'Kullanıcı bulunamadı.'); header('Location: ' . BASE_URL . '/admin/users'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $db = Database::getConnection();
    switch ($action) {
        case 'ban':
            $days = max(1, (int)($_POST['days'] ?? 7));
            $until = date('Y-m-d H:i:s', strtotime("+{$days} days"));
            $userModel->ban($userId, $until);
            Logger::adminAudit('ban', 'user', $userId, "{$days} gün ban");
            Auth::setFlash('success', "Kullanıcı {$days} gün banlandı.");
            break;
        case 'unban':
            $userModel->unban($userId);
            Logger::adminAudit('unban', 'user', $userId);
            Auth::setFlash('success', 'Ban kaldırıldı.');
            break;
        case 'set_role':
            $role = $_POST['role'] ?? null;
            $valid = ['super_admin','moderator','finance_admin','business_admin','readonly_admin',''];
            if (in_array($role, $valid)) {
                $nr = $role ?: null;
                $db->prepare("UPDATE users SET admin_role=?, is_admin=? WHERE id=?")->execute([$nr, $nr?1:0, $userId]);
                Logger::adminAudit('set_role','user',$userId,'Rol değiştirildi',$user['admin_role']??'none',$role?:'none');
                Auth::setFlash('success','Admin rolü güncellendi.');
            }
            break;
        case 'premium_add':
            $days = max(1, (int)($_POST['days'] ?? 7));
            $userModel->setPremium($userId, $days);
            Logger::adminAudit('premium_add','user',$userId,"{$days} gün premium");
            Auth::setFlash('success',"Premium {$days} gün eklendi.");
            break;
        case 'premium_remove':
            $db->prepare("UPDATE users SET is_premium=0, premium_until=NULL WHERE id=?")->execute([$userId]);
            Logger::adminAudit('premium_remove','user',$userId);
            Auth::setFlash('success','Premium kaldırıldı.');
            break;
    }
    header('Location: ' . BASE_URL . '/admin/user-detail?id=' . $userId); exit;
}

$stats = $userModel->getStats($userId);
$balance = (new WalletModel())->getBalance($userId);
$transactions = (new WalletModel())->getTransactions($userId, 10);
$checkins = (new CheckinModel())->getUserCheckins($userId, 1, 10);
$isBanned = $user['banned_until'] && strtotime($user['banned_until']) > time();
$isPremium = UserModel::isPremiumActive($user);
$pendingVenues = (new VenueModel())->getPendingCount();
$pageTitle = $user['username'];
$adminPage = 'users';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center gap-2 text-sm text-slate-400 mb-6">
    <a href="<?php echo BASE_URL; ?>/admin/users" class="hover:text-primary-container">Kullanıcılar</a>
    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
    <span class="text-on-surface"><?php echo escape($user['username']); ?></span>
</div>

<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl overflow-hidden mb-6">
    <?php if ($user['banner']): ?>
    <div class="h-32 bg-cover bg-center" style="background-image:url('<?php echo escapeUrl(bannerUrl($user['banner'])); ?>')"></div>
    <?php else: ?>
    <div class="h-32 bg-gradient-to-r from-primary-container/20 to-purple-500/20"></div>
    <?php endif; ?>
    <div class="px-6 pb-6 -mt-10">
        <div class="flex items-end gap-4 mb-4">
            <div class="w-20 h-20 rounded-xl border-4 border-[#1E293B] overflow-hidden"><?php echo avatarHtml($user['avatar']??null,$user['username'],'80'); ?></div>
            <div class="flex-grow">
                <h2 class="text-xl font-black text-on-surface flex items-center gap-2"><?php echo escape($user['username']); ?>
                    <?php if($isPremium):?><span class="material-symbols-outlined text-amber-400 text-[20px]" data-weight="fill">workspace_premium</span><?php endif;?>
                    <?php if($user['is_admin']):?><span class="text-xs bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded-full">Admin</span><?php endif;?>
                </h2>
                <?php if($user['tag']):?><div class="text-sm text-slate-400">@<?php echo escape($user['tag']);?></div><?php endif;?>
            </div>
            <?php if($isBanned):?>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-lg border bg-red-500/10 text-red-400 border-red-500/20">Banlı — <?php echo formatDate($user['banned_until']);?></span>
            <?php else:?>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-lg border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aktif</span>
            <?php endif;?>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><span class="text-slate-500 block">ID</span><span class="font-semibold text-on-surface">#<?php echo $user['id'];?></span></div>
            <div><span class="text-slate-500 block">E-posta</span><span class="font-semibold text-on-surface"><?php echo escape($user['email']);?></span></div>
            <div><span class="text-slate-500 block">Kayıt</span><span class="font-semibold text-on-surface"><?php echo formatDate($user['created_at']);?></span></div>
            <div><span class="text-slate-500 block">Son Giriş</span><span class="font-semibold text-on-surface"><?php echo $user['last_login_at']?timeAgo($user['last_login_at']):'-';?></span></div>
            <div><span class="text-slate-500 block">Check-in</span><span class="font-semibold text-primary-container"><?php echo $stats['checkins'];?></span></div>
            <div><span class="text-slate-500 block">Takipçi</span><span class="font-semibold text-on-surface"><?php echo $stats['followers'];?></span></div>
            <div><span class="text-slate-500 block">Cüzdan</span><span class="font-semibold text-yellow-400">$<?php echo number_format($balance,2);?></span></div>
            <div><span class="text-slate-500 block">Premium</span><span class="font-semibold <?php echo $isPremium?'text-amber-400':'text-slate-500';?>"><?php echo $isPremium?UserModel::premiumRemainingText($user):'Yok';?></span></div>
        </div>
    </div>
</div>

<?php if(Auth::canWrite()):?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
        <h4 class="text-sm font-bold text-on-surface mb-3"><span class="material-symbols-outlined text-[16px] text-red-400">block</span> Ban</h4>
        <?php if($isBanned):?>
        <form method="POST"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="action" value="unban">
            <button class="w-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-emerald-500/20">Ban Kaldır</button></form>
        <?php else:?>
        <form method="POST" class="flex gap-2"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="action" value="ban">
            <input type="number" name="days" value="7" min="1" class="w-20 bg-white/5 border border-white/10 text-on-surface rounded-lg px-3 py-2 text-sm">
            <button class="flex-grow bg-red-500/10 text-red-400 border border-red-500/20 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-500/20">Banla</button></form>
        <?php endif;?>
    </div>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
        <h4 class="text-sm font-bold text-on-surface mb-3"><span class="material-symbols-outlined text-[16px] text-purple-400">shield</span> Rol</h4>
        <form method="POST" class="flex gap-2"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="action" value="set_role">
            <select name="role" class="flex-grow bg-white/5 border border-white/10 text-on-surface rounded-lg px-3 py-2 text-sm">
                <option value="" class="bg-background" <?php echo empty($user['admin_role'])?'selected':'';?>>Kullanıcı</option>
                <option value="super_admin" class="bg-background" <?php echo ($user['admin_role']??'')==='super_admin'?'selected':'';?>>Super Admin</option>
                <option value="moderator" class="bg-background" <?php echo ($user['admin_role']??'')==='moderator'?'selected':'';?>>Moderatör</option>
                <option value="finance_admin" class="bg-background" <?php echo ($user['admin_role']??'')==='finance_admin'?'selected':'';?>>Finans</option>
                <option value="readonly_admin" class="bg-background" <?php echo ($user['admin_role']??'')==='readonly_admin'?'selected':'';?>>Read-only</option>
            </select>
            <button class="bg-purple-500/10 text-purple-400 border border-purple-500/20 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-purple-500/20">Kaydet</button></form>
    </div>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
        <h4 class="text-sm font-bold text-on-surface mb-3"><span class="material-symbols-outlined text-[16px] text-amber-400">workspace_premium</span> Premium</h4>
        <div class="flex gap-2">
            <form method="POST" class="flex gap-2 flex-grow"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="action" value="premium_add">
                <input type="number" name="days" value="30" min="1" class="w-20 bg-white/5 border border-white/10 text-on-surface rounded-lg px-3 py-2 text-sm">
                <button class="flex-grow bg-amber-500/10 text-amber-400 border border-amber-500/20 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-amber-500/20">Ekle</button></form>
            <?php if($isPremium):?>
            <form method="POST"><input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>"><input type="hidden" name="action" value="premium_remove">
                <button class="bg-red-500/10 text-red-400 border border-red-500/20 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-red-500/20">Kaldır</button></form>
            <?php endif;?>
        </div>
    </div>
</div>
<?php endif;?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5"><h3 class="font-bold text-on-surface">Son Check-in'ler</h3></div>
        <?php if(empty($checkins)):?><div class="p-6 text-center text-slate-400">Yok.</div>
        <?php else:?><div class="divide-y divide-white/5"><?php foreach($checkins as $ci):?>
        <div class="px-6 py-3 flex items-center gap-3 hover:bg-white/[0.02]">
            <div class="flex-grow"><div class="font-semibold text-on-surface text-sm"><?php echo escape($ci['venue_name']);?></div><div class="text-xs text-slate-500"><?php echo escape(truncate($ci['note']??'',40));?></div></div>
            <span class="text-xs text-slate-500"><?php echo timeAgo($ci['created_at']);?></span>
        </div><?php endforeach;?></div><?php endif;?>
    </div>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5"><h3 class="font-bold text-on-surface">Cüzdan Hareketleri</h3></div>
        <?php if(empty($transactions)):?><div class="p-6 text-center text-slate-400">Yok.</div>
        <?php else:?><div class="divide-y divide-white/5"><?php foreach($transactions as $t):?>
        <div class="px-6 py-3 flex items-center gap-3 hover:bg-white/[0.02]">
            <span class="material-symbols-outlined text-[18px] <?php echo $t['type']==='deposit'?'text-emerald-400':'text-red-400';?>"><?php echo $t['type']==='deposit'?'arrow_downward':'arrow_upward';?></span>
            <div class="flex-grow"><div class="text-sm text-on-surface"><?php echo escape(truncate($t['description']??'',40));?></div></div>
            <span class="font-bold text-sm <?php echo $t['type']==='deposit'?'text-emerald-400':'text-red-400';?>">$<?php echo number_format($t['amount'],2);?></span>
        </div><?php endforeach;?></div><?php endif;?>
    </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
