<?php
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/ImageUploader.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';
require_once __DIR__ . '/../../app/Models/Ad.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

Auth::requireAdmin();
$adModel = new AdModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title    = trim($_POST['title'] ?? '');
        $linkUrl  = trim($_POST['link_url'] ?? '');
        $position = $_POST['position'] ?? 'carousel';
        $sort     = (int)($_POST['sort_order'] ?? 0);

        if (!empty($_FILES['image']['name'])) {
            $uploader = new ImageUploader();
            $result = $uploader->upload($_FILES['image'], 'ads', ['maxSize' => MAX_AD_SIZE]);
            if ($result['success']) {
                $adModel->create($title, $result['path'], $linkUrl, $position, $sort);
                Logger::adminAudit('create', 'ad', null, $title);
                Auth::setFlash('success', 'Reklam eklendi.');
            } else {
                Auth::setFlash('error', $result['error']);
            }
        } else {
            Auth::setFlash('error', 'Resim dosyası gerekli.');
        }
    } elseif ($action === 'toggle') {
        $adModel->toggleActive((int)($_POST['ad_id'] ?? 0));
        Auth::setFlash('success', 'Reklam durumu değiştirildi.');
    } elseif ($action === 'delete') {
        $adId = (int)($_POST['ad_id'] ?? 0);
        $adModel->delete($adId);
        Logger::adminAudit('delete', 'ad', $adId);
        Auth::setFlash('success', 'Reklam silindi.');
    }
    header('Location: ' . BASE_URL . '/admin/ads'); exit;
}

$ads = $adModel->getAll();
$pendingVenues = (new VenueModel())->getPendingCount();

$pageTitle = 'Sponsorlu İçerik Yönetimi';
$adminPage = 'ads';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">campaign</span> Sponsorlu İçerik
    </h1>
</div>

<!-- Yeni Reklam Formu -->
<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] mb-6">
    <h2 class="text-lg font-bold text-on-surface mb-4">Yeni Sponsorlu İçerik Ekle</h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="create">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Başlık</label>
                <input type="text" name="title" required class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Gösterim Yeri</label>
                <input type="hidden" name="position" value="feed">
                <div class="w-full bg-white/5 border border-white/10 text-slate-300 rounded-lg px-4 py-2.5 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-primary-container">dynamic_feed</span>
                    Feed — Postlar Arasında
                </div>
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Link URL</label>
                <input type="url" name="link_url" placeholder="https://..." class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Sıra</label>
                <input type="number" name="sort_order" value="0" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
        </div>
        <div>
            <label class="block text-label-md text-slate-400 mb-1">Resim</label>
            <input type="file" name="image" accept="image/*" required class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-primary-container file:text-white transition-colors">
        </div>
        <button type="submit" class="bg-primary-container text-white px-6 py-2.5 rounded-lg text-label-md font-semibold hover:bg-primary-container/90 transition-colors shadow-[0_0_10px_rgba(255,107,53,0.2)]">Sponsorlu İçerik Ekle</button>
    </form>
</div>

<!-- Mevcut Reklamlar -->
<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Başlık</th><th class="px-6 py-3">Pozisyon</th><th class="px-6 py-3">Durum</th><th class="px-6 py-3">İşlem</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($ads as $ad): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $ad['id']; ?></td>
                    <td class="px-6 py-3 font-semibold text-on-surface"><?php echo escape($ad['title']); ?></td>
                    <td class="px-6 py-3"><span class="bg-white/5 text-slate-300 text-xs px-2 py-1 rounded"><?php echo escape($ad['position']); ?></span></td>
                    <td class="px-6 py-3">
                        <?php if ($ad['is_active']): ?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aktif</span>
                        <?php else: ?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border bg-red-500/10 text-red-400 border-red-500/20">Pasif</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex gap-1">
                            <form method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>"><input type="hidden" name="action" value="toggle"><button class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 flex items-center justify-center transition-colors" title="Aktif/Pasif"><span class="material-symbols-outlined text-[18px]">toggle_on</span></button></form>
                            <form method="POST" class="inline" onsubmit="return confirm('Silmek?')"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>"><input type="hidden" name="action" value="delete"><button class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 flex items-center justify-center transition-colors" title="Sil"><span class="material-symbols-outlined text-[18px]">delete</span></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
