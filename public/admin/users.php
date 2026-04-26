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
$userModel = new UserModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            case 'toggle_admin':
                $userModel->toggleAdmin($targetId);
                Logger::adminAudit('toggle_admin', 'user', $targetId);
                Auth::setFlash('success', 'Admin durumu değiştirildi.');
                break;
            case 'delete':
                $userModel->delete($targetId);
                Logger::adminAudit('delete', 'user', $targetId);
                Auth::setFlash('success', 'Kullanıcı silindi.');
                break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/users'); exit;
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$result = $userModel->getAll($page, 30, $search);
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
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
        <input type="text" name="q" placeholder="Ara..." value="<?php echo escape($search); ?>" class="bg-white/5 border border-white/10 text-on-surface rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-primary-container/40 w-64 transition-colors">
    </form>
</div>

<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Kullanıcı</th><th class="px-6 py-3">E-posta</th><th class="px-6 py-3">Durum</th><th class="px-6 py-3">Kayıt</th><th class="px-6 py-3">İşlem</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($result['users'] as $u): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $u['id']; ?></td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <?php echo avatarHtml($u['avatar'] ?? null, $u['username'], '32'); ?>
                            <div>
                                <div class="font-semibold text-on-surface"><?php echo escape($u['username']); ?></div>
                                <?php if ($u['tag']): ?><div class="text-xs text-slate-500">@<?php echo escape($u['tag']); ?></div><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-slate-400 text-xs"><?php echo escape($u['email']); ?></td>
                    <td class="px-6 py-3">
                        <div class="flex gap-1">
                            <?php if ($u['is_admin']): ?><span class="text-xs font-semibold px-2 py-1 rounded border bg-purple-500/10 text-purple-400 border-purple-500/20">Admin</span><?php endif; ?>
                            <?php if ($u['banned_until'] && strtotime($u['banned_until']) > time()): ?>
                                <span class="text-xs font-semibold px-2 py-1 rounded border bg-red-500/10 text-red-400 border-red-500/20">Banlı</span>
                            <?php else: ?>
                                <span class="text-xs font-semibold px-2 py-1 rounded border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aktif</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-slate-500 text-xs"><?php echo formatDate($u['created_at']); ?></td>
                    <td class="px-6 py-3">
                        <div class="flex gap-1">
                            <?php if (!$u['is_admin'] || (int)$u['id'] !== Auth::id()): ?>
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="action" value="toggle_admin"><button class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 hover:bg-purple-500/20 flex items-center justify-center transition-colors" title="Admin Toggle"><span class="material-symbols-outlined text-[18px]">shield</span></button></form>
                            <?php if (!($u['banned_until'] && strtotime($u['banned_until']) > time())): ?>
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="action" value="ban"><input type="hidden" name="days" value="7"><button class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 flex items-center justify-center transition-colors" title="7 Gün Ban"><span class="material-symbols-outlined text-[18px]">block</span></button></form>
                            <?php else: ?>
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="action" value="unban"><button class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 flex items-center justify-center transition-colors" title="Ban Kaldır"><span class="material-symbols-outlined text-[18px]">check_circle</span></button></form>
                            <?php endif; ?>
                            <?php endif; ?>
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
        $pgCls = $p === $page
            ? 'bg-primary-container text-white px-3 py-1.5 rounded-lg text-label-md font-semibold'
            : 'bg-white/5 text-slate-400 hover:text-white hover:bg-white/10 px-3 py-1.5 rounded-lg text-label-md transition-colors';
    ?>
        <a href="?q=<?php echo escape($search); ?>&page=<?php echo $p; ?>" class="<?php echo $pgCls; ?>"><?php echo $p; ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/_footer.php'; ?>
