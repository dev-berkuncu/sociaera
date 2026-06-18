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
require_once __DIR__ . '/../../app/Models/Wallet.php';
Auth::requireAccess('ads');
$adModel = new AdModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title    = trim($_POST['title'] ?? '');
        $linkUrl  = trim($_POST['link_url'] ?? '');
        $position = $_POST['position'] ?? 'carousel';
        $sort     = (int)($_POST['sort_order'] ?? 0);
        $mediaType= $_POST['media_type'] ?? 'image';
        if (!in_array($mediaType, ['image', 'video', 'youtube'])) $mediaType = 'image';

        $imagePath = '';
        if ($mediaType === 'youtube') {
            $youtubeUrl = trim($_POST['youtube_url'] ?? '');
            if (empty($youtubeUrl) || !filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
                Auth::setFlash('error', 'LÃ¼tfen geÃ§erli bir YouTube linki girin.');
                header('Location: ' . BASE_URL . '/admin/ads'); exit;
            }
            $imagePath = $youtubeUrl;
            $adModel->create($title, $imagePath, $linkUrl, $position, $sort, $mediaType);
            Logger::adminAudit('create', 'ad', null, $title);
            Auth::setFlash('success', 'Sponsorlu iÃ§erik eklendi.');
        } else {
            if (!empty($_FILES['image']['name'])) {
                $uploader = new ImageUploader();
                $result = $uploader->upload($_FILES['image'], 'ads', [
                    'outputFormat' => 'webp',
                    'maxSize' => ($mediaType === 'video') ? 20 * 1024 * 1024 : 5 * 1024 * 1024
                ]);
                if ($result['success']) {
                    $adModel->create($title, $result['path'], $linkUrl, $position, $sort, $mediaType);
                    Logger::adminAudit('create', 'ad', null, $title);
                    Auth::setFlash('success', 'Sponsorlu iÃ§erik eklendi.');
                } else {
                    Auth::setFlash('error', $result['error']);
                }
            } else {
                Auth::setFlash('error', 'Resim/Video dosyasÄ± gerekli.');
            }
        }
    } elseif ($action === 'update') {
        $adId = (int)($_POST['ad_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $linkUrl  = trim($_POST['link_url'] ?? '');
        $position = $_POST['position'] ?? 'carousel';
        $sort     = (int)($_POST['sort_order'] ?? 0);
        $mediaType= $_POST['media_type'] ?? 'image';
        if (!in_array($mediaType, ['image', 'video', 'youtube'])) $mediaType = 'image';

        $imagePath = null;
        if ($mediaType === 'youtube') {
            $youtubeUrl = trim($_POST['youtube_url'] ?? '');
            if (!empty($youtubeUrl) && filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
                $imagePath = $youtubeUrl;
            }
        } else {
            if (!empty($_FILES['image']['name'])) {
                $uploader = new ImageUploader();
                $result = $uploader->upload($_FILES['image'], 'ads', [
                    'outputFormat' => 'webp',
                    'maxSize' => ($mediaType === 'video') ? 20 * 1024 * 1024 : 5 * 1024 * 1024
                ]);
                if ($result['success']) {
                    $imagePath = $result['path'];
                } else {
                    Auth::setFlash('error', $result['error']);
                    header('Location: ' . BASE_URL . '/admin/ads?edit=' . $adId); exit;
                }
            }
        }
        $adModel->update($adId, $title, $imagePath, $linkUrl, $position, $sort, $mediaType);
        Logger::adminAudit('update', 'ad', $adId, $title);
        Auth::setFlash('success', 'Sponsorlu iÃ§erik baÅŸarÄ±yla gÃ¼ncellendi.');
    } elseif ($action === 'toggle') {
        $adModel->toggleActive((int)($_POST['ad_id'] ?? 0));
        Auth::setFlash('success', 'Reklam durumu deÄŸiÅŸtirildi.');
    } elseif ($action === 'delete') {
        $adId = (int)($_POST['ad_id'] ?? 0);
        $adModel->delete($adId);
        Logger::adminAudit('delete', 'ad', $adId);
        Auth::setFlash('success', 'Reklam silindi.');
    } elseif ($action === 'approve') {
        $adId = (int)($_POST['ad_id'] ?? 0);
        $ad = $adModel->getById($adId);
        if ($ad && $ad['status'] === 'pending') {
            $wallet = new WalletModel();
            if ($wallet->getBalance($ad['user_id']) >= 10000.00) {
                if ($wallet->pay($ad['user_id'], 10000.00, 'Feed Reklam OnayÄ±: ' . $ad['title'])) {
                    $adModel->approve($adId);
                    Auth::setFlash('success', 'Reklam onaylandÄ± ve bakiye dÃ¼ÅŸÃ¼ldÃ¼.');
                } else {
                    Auth::setFlash('error', 'Ã–deme alÄ±namadÄ±.');
                }
            } else {
                Auth::setFlash('error', 'KullanÄ±cÄ±nÄ±n bakiyesi yetersiz ($10.000 gerekli).');
            }
        }
    } elseif ($action === 'reject') {
        $adId = (int)($_POST['ad_id'] ?? 0);
        $adModel->reject($adId);
        Auth::setFlash('success', 'Reklam reddedildi.');
    }
    header('Location: ' . BASE_URL . '/admin/ads'); exit;
}

$ads = $adModel->getAll();
$pendingVenues = (new VenueModel())->getPendingCount();

$editAd = null;
if (!empty($_GET['edit'])) {
    $editAd = $adModel->getById((int)$_GET['edit']);
}

$pageTitle = 'Sponsorlu Ä°Ã§erik YÃ¶netimi';
$adminPage = 'ads';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">campaign</span> Sponsorlu Ä°Ã§erik
    </h1>
</div>

<!-- Yeni Reklam Formu -->
<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-on-surface"><?php echo $editAd ? 'Sponsorlu Ä°Ã§eriÄŸi DÃ¼zenle' : 'Yeni Sponsorlu Ä°Ã§erik Ekle'; ?></h2>
        <?php if ($editAd): ?>
            <a href="<?php echo BASE_URL; ?>/admin/ads" class="text-sm text-primary hover:underline">Ä°ptal ve Yeni Ekle</a>
        <?php endif; ?>
    </div>
    <form method="POST" action="<?php echo BASE_URL; ?>/admin/ads" enctype="multipart/form-data" class="space-y-4">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="<?php echo $editAd ? 'update' : 'create'; ?>">
        <?php if ($editAd): ?>
        <input type="hidden" name="ad_id" value="<?php echo $editAd['id']; ?>">
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-label-md text-slate-400 mb-1">BaÅŸlÄ±k</label>
                <input type="text" name="title" value="<?php echo escape($editAd['title'] ?? ''); ?>" required class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">GÃ¶sterim Yeri</label>
                <select name="position" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors font-sans">
                    <option value="carousel" <?php echo ($editAd && $editAd['position']==='carousel')?'selected':''; ?> class="bg-background">ğŸŸï¸ SponsorlarÄ±mÄ±z (Logo Slider)</option>
                    <option value="sidebar_right" <?php echo ($editAd && $editAd['position']==='sidebar_right')?'selected':''; ?> class="bg-background">ğŸ—ºï¸ Reklam AlanÄ± (GeniÅŸ Ekran - 300x500)</option>
                    <option value="feed" <?php echo ($editAd && $editAd['position']==='feed')?'selected':''; ?> class="bg-background">ğŸ“° AkÄ±ÅŸ ArasÄ± (Feed ReklamÄ±)</option>
                </select>
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Link URL</label>
                <input type="url" name="link_url" value="<?php echo escape($editAd['link_url'] ?? ''); ?>" placeholder="https://..." class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">SÄ±ra</label>
                <input type="number" name="sort_order" value="<?php echo escape($editAd['sort_order'] ?? '0'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
        </div>
        <div>
            <label class="block text-label-md text-slate-400 mb-1">Medya TÃ¼rÃ¼</label>
            <div class="flex gap-4 mb-3">
                <?php $mType = $editAd['media_type'] ?? 'image'; ?>
                <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                    <input type="radio" name="media_type" value="image" <?php echo $mType==='image'?'checked':''; ?> onchange="toggleAdminMedia()"> GÃ¶rsel
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                    <input type="radio" name="media_type" value="video" <?php echo $mType==='video'?'checked':''; ?> onchange="toggleAdminMedia()"> Video
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                    <input type="radio" name="media_type" value="youtube" <?php echo $mType==='youtube'?'checked':''; ?> onchange="toggleAdminMedia()"> YouTube Linki
                </label>
            </div>
            
            <div id="adminFileContainer" style="<?php echo $mType==='youtube'?'display:none;':'display:block;'; ?>">
                <input type="file" name="image" id="adminImage" accept="<?php echo $mType==='video'?'video/mp4,video/webm':'image/*'; ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-primary-container file:text-white transition-colors">
                <?php if ($editAd && $mType !== 'youtube' && !empty($editAd['image_url'])): ?>
                    <p class="text-xs text-slate-500 mt-2">Åu anki medya: <a href="<?php echo BASE_URL . '/' . ltrim($editAd['image_url'], '/'); ?>" target="_blank" class="text-primary hover:underline">GÃ¶rÃ¼ntÃ¼le</a> (DeÄŸiÅŸtirmek istemiyorsanÄ±z boÅŸ bÄ±rakÄ±n)</p>
                <?php endif; ?>
            </div>
            <div id="adminYtContainer" style="<?php echo $mType==='youtube'?'display:block;':'display:none;'; ?>">
                <input type="url" name="youtube_url" id="adminYtUrl" value="<?php echo $mType==='youtube'?escape($editAd['image_url']??''):''; ?>" placeholder="https://youtube.com/watch?v=..." class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
        </div>
        <script>
        function toggleAdminMedia() {
            const type = document.querySelector('input[name="media_type"]:checked').value;
            const fileCont = document.getElementById('adminFileContainer');
            const ytCont = document.getElementById('adminYtContainer');
            const fileInp = document.getElementById('adminImage');
            const ytInp = document.getElementById('adminYtUrl');
            
            if(type === 'youtube') {
                fileCont.style.display = 'none'; ytCont.style.display = 'block';
                fileInp.removeAttribute('required');
            } else {
                fileCont.style.display = 'block'; ytCont.style.display = 'none';
                if(type === 'video') fileInp.setAttribute('accept', 'video/mp4,video/webm');
                else fileInp.setAttribute('accept', 'image/*');
            }
        }
        </script>
        <button type="submit" class="bg-primary-container text-white px-6 py-2.5 rounded-lg text-label-md font-semibold hover:bg-primary-container/90 transition-colors shadow-[0_0_10px_rgba(255,107,53,0.2)]">
            <?php echo $editAd ? 'Sponsorlu Ä°Ã§eriÄŸi GÃ¼ncelle' : 'Sponsorlu Ä°Ã§erik Ekle'; ?>
        </button>
    </form>
</div>

<!-- Mevcut Reklamlar -->
<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">GÃ¶rsel</th><th class="px-6 py-3">BaÅŸlÄ±k</th><th class="px-6 py-3">Pozisyon</th><th class="px-6 py-3">Durum</th><th class="px-6 py-3">Ä°ÅŸlem</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($ads as $ad): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $ad['id']; ?></td>
                    <td class="px-6 py-3">
                        <?php if ($ad['media_type'] === 'youtube'): ?>
                            <div class="w-12 h-8 bg-red-500/20 text-red-500 flex items-center justify-center rounded text-xs font-bold">YT</div>
                        <?php elseif ($ad['media_type'] === 'video'): ?>
                            <div class="w-12 h-8 bg-blue-500/20 text-blue-500 flex items-center justify-center rounded text-xs font-bold">VID</div>
                        <?php elseif (!empty($ad['image_url'])): ?>
                            <img src="<?php echo BASE_URL . '/' . ltrim($ad['image_url'], '/'); ?>" class="w-12 h-8 object-cover rounded border border-white/10" alt="ad">
                        <?php else: ?>
                            <div class="w-12 h-8 bg-white/5 rounded"></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-3 font-semibold text-on-surface"><?php echo escape($ad['title']); ?></td>
                    <td class="px-6 py-3">
                        <span class="bg-white/5 text-slate-300 text-xs px-2 py-1 rounded">
                            <?php 
                            echo match($ad['position']) {
                                'carousel' => 'SponsorlarÄ±mÄ±z (Slider)',
                                'sidebar_right' => 'Reklam AlanÄ± (GeniÅŸ Ekran)',
                                'feed' => 'AkÄ±ÅŸ ArasÄ± (Feed)',
                                default => escape($ad['position'])
                            };
                            ?>
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <?php if ($ad['status'] === 'pending'): ?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border bg-yellow-500/10 text-yellow-400 border-yellow-500/20">Bekliyor</span>
                        <?php elseif ($ad['status'] === 'rejected'): ?>
                            <span class="text-xs font-semibold px-2 py-1 rounded border bg-red-500/10 text-red-400 border-red-500/20">Reddedildi</span>
                        <?php else: ?>
                            <?php if ($ad['is_active']): ?>
                                <span class="text-xs font-semibold px-2 py-1 rounded border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Aktif</span>
                            <?php else: ?>
                                <span class="text-xs font-semibold px-2 py-1 rounded border bg-slate-500/10 text-slate-400 border-slate-500/20">Pasif</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex gap-1">
                            <?php if ($ad['status'] === 'pending'): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('ReklamÄ± onaylamak istiyor musunuz? KullanÄ±cÄ±dan $10.000 Ã§ekilecek.')"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>"><input type="hidden" name="action" value="approve"><button class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 flex items-center justify-center transition-colors" title="Onayla"><span class="material-symbols-outlined text-[18px]">check</span></button></form>
                                <form method="POST" class="inline" onsubmit="return confirm('ReklamÄ± reddetmek istiyor musunuz?')"><input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>"><input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>"><input type="hidden" name="action" value="reject"><button class="w-8 h-8 rounded-lg bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 flex items-center justify-center transition-colors" title="Reddet"><span class="material-symbols-outlined text-[18px]">close</span></button></form>
                            <?php endif; ?>
                            <a href="?edit=<?php echo $ad['id']; ?>" class="w-8 h-8 rounded-lg bg-white/5 text-slate-300 hover:bg-white/10 flex items-center justify-center transition-colors" title="DÃ¼zenle"><span class="material-symbols-outlined text-[18px]">edit</span></a>
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
