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
require_once __DIR__ . '/../../app/Models/Checkin.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Settings.php';

Auth::requireAdmin();
$checkinModel = new CheckinModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId) {
        switch ($action) {
            case 'delete': $checkinModel->adminDelete($postId); Logger::adminAudit('delete', 'post', $postId); Auth::setFlash('success', 'Gönderi silindi.'); break;
            case 'flag': $checkinModel->toggleFlag($postId); Logger::adminAudit('flag', 'post', $postId); Auth::setFlash('success', 'İşaretleme değiştirildi.'); break;
            case 'exclude': $checkinModel->toggleExclude($postId); Logger::adminAudit('exclude_lb', 'post', $postId); Auth::setFlash('success', 'Sıralamayla ilgili durum değiştirildi.'); break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/posts'); exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$result = $checkinModel->adminGetAll($page);
$pendingVenues = (new VenueModel())->getPendingCount();

$pageTitle = 'Gönderi Yönetimi';
$adminPage = 'posts';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">article</span> Gönderiler (<?php echo $result['total']; ?>)
    </h1>
</div>

<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Kullanıcı</th><th class="px-6 py-3">Mekan</th><th class="px-6 py-3">İçerik</th><th class="px-6 py-3">Tarih</th><th class="px-6 py-3">İşlem</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($result['posts'] as $p): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $p['id']; ?></td>
                    <td class="px-6 py-3 font-semibold text-on-surface"><?php echo escape($p['username']); ?></td>
                    <td class="px-6 py-3 text-slate-300 text-xs"><?php echo escape($p['venue_name']); ?></td>
                    <td class="px-6 py-3 text-slate-400 text-xs max-w-[200px] truncate"><?php echo escape(truncate($p['note'] ?? '', 60)); ?></td>
                    <td class="px-6 py-3 text-slate-500 text-xs"><?php echo formatDate($p['created_at'], true); ?></td>
                    <td class="px-6 py-3">
                        <div class="flex gap-1">
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="post_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="action" value="flag"><button class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 flex items-center justify-center transition-colors" title="İşaretle"><span class="material-symbols-outlined text-[18px]">flag</span></button></form>
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="post_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="action" value="exclude"><button class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 hover:bg-purple-500/20 flex items-center justify-center transition-colors" title="Sıralamadan Çıkar"><span class="material-symbols-outlined text-[18px]">emoji_events</span></button></form>
                            <form method="POST" class="inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?')"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="post_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="action" value="delete"><button class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 flex items-center justify-center transition-colors" title="Sil"><span class="material-symbols-outlined text-[18px]">delete</span></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
