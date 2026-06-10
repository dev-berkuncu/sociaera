<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Services/ImageUploader.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Wallet.php';

Auth::requireLogin();

$userModel = new UserModel();
$user = $userModel->getById(Auth::id());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $redirectTo = BASE_URL . '/settings';

    if ($action === 'update_profile') {
        $result = $userModel->updateProfile(Auth::id(), $_POST);
        if ($result['ok']) {
            $user = $userModel->getById(Auth::id());
            Auth::refresh(['username' => $user['username']]);
            Auth::setFlash('success', 'Profil güncellendi.');
        } else {
            Auth::setFlash('error', $result['error']);
        }
        header('Location: ' . $redirectTo); exit;

    } elseif ($action === 'update_avatar') {
        if (!empty($_FILES['avatar']['name'])) {
            $uploader = new ImageUploader();
            $old = $user['avatar'];
            $result = $uploader->upload($_FILES['avatar'], 'avatars', [
                'maxSize'      => MAX_AVATAR_SIZE,
                'maxWidth'     => AVATAR_MAX_W,
                'maxHeight'    => AVATAR_MAX_H,
                'outputFormat' => 'webp',
            ]);
            if ($result['success']) {
                $userModel->updateAvatar(Auth::id(), $result['filename']);
                if ($old) $uploader->delete('avatars', $old);
                Auth::refresh(['avatar' => $result['filename']]);
                Auth::setFlash('success', 'Avatar güncellendi.');
            } else {
                Auth::setFlash('error', $result['error']);
            }
        }
        header('Location: ' . $redirectTo); exit;

    } elseif ($action === 'update_banner') {
        if (!empty($_FILES['banner']['name'])) {
            $uploader = new ImageUploader();
            $old = $user['banner'];
            $result = $uploader->upload($_FILES['banner'], 'banners', [
                'maxSize'      => MAX_BANNER_SIZE,
                'maxWidth'     => BANNER_MAX_W,
                'maxHeight'    => BANNER_MAX_H,
                'outputFormat' => 'webp',
            ]);
            if ($result['success']) {
                $userModel->updateBanner(Auth::id(), $result['filename']);
                if ($old) $uploader->delete('banners', $old);
                Auth::setFlash('success', 'Banner güncellendi.');
            } else {
                Auth::setFlash('error', $result['error']);
            }
        }
        header('Location: ' . $redirectTo); exit;

    } elseif ($action === 'change_password') {
        $result = $userModel->changePassword(Auth::id(), $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
        if ($result['ok']) { Auth::setFlash('success', 'Şifre başarıyla değiştirildi.'); }
        else                { Auth::setFlash('error', $result['error']); }
        header('Location: ' . $redirectTo); exit;

    } elseif ($action === 'update_badge') {
        if (!UserModel::isPremiumActive($user)) {
            Auth::setFlash('error', 'Rozet değiştirmek için Premium üye olmanız gerekir.');
        } else {
            $badge  = $_POST['badge'] ?? null;
            $badges = UserModel::availableBadges();
            if ($badge && !isset($badges[$badge])) {
                Auth::setFlash('error', 'Geçersiz rozet seçimi.');
            } else {
                $userModel->updateBadge(Auth::id(), $badge ?: null);
                Auth::setFlash('success', 'Rozet güncellendi.');
            }
        }
        header('Location: ' . $redirectTo); exit;

    } elseif ($action === 'update_theme') {
        if (!UserModel::isPremiumActive($user)) {
            Auth::setFlash('error', 'Bu özellik Premium üyelere özeldir.');
        } else {
            $theme       = $_POST['theme'] ?? 'default';
            $validThemes = ['default', 'ocean', 'sunset', 'emerald', 'purple', 'crimson'];
            if (!in_array($theme, $validThemes)) $theme = 'default';
            $userModel->updateField(Auth::id(), 'profile_theme', $theme);
            Auth::setFlash('success', 'Profil teması güncellendi! 🎨');
        }
        header('Location: ' . $redirectTo); exit;

    } elseif ($action === 'update_bank') {
        $bank = trim($_POST['bank_account'] ?? '');
        if (empty($bank)) {
            Auth::setFlash('error', 'Banka hesap numarası boş bırakılamaz.');
        } else {
            $userModel->updateField(Auth::id(), 'bank_account', $bank);
            Auth::setFlash('success', 'Banka hesap numarası kaydedildi ✓');
        }
        header('Location: ' . $redirectTo); exit;
    }
}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Ayarlar';
$activeNav = 'settings';
require_once __DIR__ . '/partials/app_header.php';
?>

<div style="min-width:0;display:flex;flex-direction:column;gap:20px;max-width:680px;">
    <!-- Sayfa başlığı -->
    <div style="margin-bottom:8px;">
        <h1 style="font-size:1.6rem;font-weight:800;color:var(--text-1);display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:28px;color:var(--color-primary);">settings</span>
            Ayarlar
        </h1>
    </div>

    <!-- Avatar -->
    <div class="rounded-2xl p-6 md:p-8" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-6" style="color:var(--text-1);"><span class="material-symbols-outlined text-[24px]" style="color:var(--color-primary);">account_circle</span> Avatar</h2>
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <?php $pAvatar = safeAvatarUrl($user['avatar'] ?? null, $user['username']); ?>
            <div class="relative group">
                <img src="<?php echo $pAvatar; ?>" class="w-32 h-32 rounded-full object-cover shadow-xl flex-shrink-0 relative z-10" style="border:4px solid #fff;">
                <div class="absolute inset-0 rounded-full bg-primary-container blur-md -z-10 opacity-20 group-hover:opacity-40 transition-opacity"></div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="flex-1 w-full flex flex-col gap-3">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_avatar">
                
                <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed rounded-xl cursor-pointer transition-colors" style="border-color:var(--border);background:var(--bg-section);">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <span class="material-symbols-outlined mb-1" style="color:var(--text-3);">upload</span>
                        <p class="text-sm" style="color:var(--text-3);"><span class="font-bold" style="color:var(--color-primary);">Tıkla</span> veya sürükle</p>
                        <p class="text-xs mt-1" style="color:var(--text-3);">Maks. 10MB, JPEG / PNG / WebP</p>
                    </div>
                    <input type="file" name="avatar" accept="image/*" required class="hidden" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>

    <!-- Banner -->
    <div class="rounded-2xl p-6 md:p-8" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-6" style="color:var(--text-1);"><span class="material-symbols-outlined text-[24px]" style="color:var(--color-primary);">image</span> Banner</h2>
        <div class="h-40 rounded-xl overflow-hidden relative mb-5 shadow-inner group" style="background:var(--bg-section);border:1px solid var(--border);">
            <?php if (bannerUrl($user['banner'])): ?>
                <img src="<?php echo bannerUrl($user['banner']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#FFF3EB,#F5F4F0);display:flex;align-items:center;justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:36px;color:var(--text-3);opacity:.4;">wallpaper</span>
                </div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                <span class="material-symbols-outlined text-white text-[32px]">wallpaper</span>
            </div>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_banner">
            
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <label class="flex-1 w-full flex items-center justify-center gap-3 h-12 rounded-xl cursor-pointer transition-colors px-4" style="border:1px solid var(--border);background:var(--bg-section);">
                    <span class="material-symbols-outlined text-[20px]" style="color:var(--text-3);">upload</span>
                    <span class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis" style="color:var(--text-2);">Yeni Banner Seç...</span>
                    <input type="file" name="banner" accept="image/*" required class="hidden" onchange="this.nextElementSibling.innerText = this.files[0].name">
                    <span class="hidden"></span>
                </label>
                <button type="submit" class="w-full sm:w-auto h-12 bg-primary-container hover:bg-primary-container/90 text-white px-6 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(255,145,0,0.3)] active:scale-95 shrink-0 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">cloud_upload</span> Yükle
                </button>
            </div>
            <div class="text-xs mt-3 text-center sm:text-left" style="color:var(--text-3);">Maks. 10MB, önerilen 1500x500px</div>
        </form>
    </div>

    <!-- Rozet Seçimi (Premium) -->
    <?php if (UserModel::isPremiumActive($user)): ?>
    <div class="rounded-2xl p-6 md:p-8 relative overflow-hidden" style="background:#fff;border:1px solid rgba(123,208,255,0.4);box-shadow:0 0 30px -5px rgba(123,208,255,0.15);">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[#7bd0ff]/5 to-transparent pointer-events-none"></div>
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] opacity-30 mix-blend-overlay pointer-events-none"></div>
        
        <h2 class="text-2xl font-black flex items-center gap-2 mb-2 relative z-10" style="color:var(--text-1);">
            <span class="material-symbols-outlined text-[#7bd0ff] text-[28px]">workspace_premium</span> Profil Rozeti
            <span class="bg-[#7bd0ff]/20 text-[#7bd0ff] text-[10px] font-black px-2 py-0.5 rounded border border-[#7bd0ff]/30 uppercase tracking-widest ml-2 shadow-[0_0_10px_rgba(123,208,255,0.3)]">Premium</span>
        </h2>
        <p class="text-sm mb-6 relative z-10 font-medium" style="color:var(--text-2);">Profilinde adının yanında görünecek özel premium rozetini seç.</p>
        <form method="POST" class="relative z-10">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_badge">
            <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3 mb-6">
                <!-- Rozet yok seçeneği -->
                <label class="cursor-pointer">
                    <input type="radio" name="badge" value="" <?php echo empty($user['badge']) ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border transition-all h-20" style="border-color:var(--border);background:var(--bg-section);">
                        <span class="material-symbols-outlined text-[24px]" style="color:var(--text-3);">block</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider" style="color:var(--text-3);">Yok</span>
                    </div>
                </label>
                <?php foreach (UserModel::availableBadges() as $key => $badge): ?>
                <label class="cursor-pointer">
                    <input type="radio" name="badge" value="<?php echo $key; ?>" <?php echo ($user['badge'] ?? '') === $key ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border transition-all h-20" style="border-color:var(--border);background:var(--bg-section);">
                        <span class="material-symbols-outlined text-[28px]" style="color: <?php echo $badge['color']; ?>; text-shadow: 0 0 10px <?php echo $badge['color']; ?>80;"><?php echo $badge['icon']; ?></span>
                        <span class="text-[9px] font-bold tracking-wide truncate w-full text-center" style="color:var(--text-2);"><?php echo $badge['label']; ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="bg-gradient-to-r from-[#7bd0ff]/20 to-[#7bd0ff]/10 text-[#7bd0ff] px-8 py-3 rounded-xl font-black border border-[#7bd0ff]/40 hover:bg-[#7bd0ff]/30 transition-all active:scale-95 w-full sm:w-auto shadow-[0_0_20px_rgba(123,208,255,0.15)] flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">save</span> Rozeti Kaydet
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="rounded-2xl p-6 md:p-8 relative overflow-hidden" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div class="absolute inset-0 bg-gradient-to-br from-transparent via-[#7bd0ff]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4" style="color:var(--text-1);">
            <span class="material-symbols-outlined text-[24px]" style="color:var(--text-3);">workspace_premium</span> Profil Rozeti
        </h2>
        <p class="text-sm mb-6 leading-relaxed" style="color:var(--text-3);">Profil rozeti seçmek ve adının yanında havalı bir ikonla görünmek için Premium üye olman gerekir.</p>
        <a href="<?php echo BASE_URL; ?>/premium" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-[#7bd0ff]/20 to-[#7bd0ff]/10 text-[#7bd0ff] px-6 py-3 rounded-xl font-black text-sm border border-[#7bd0ff]/30 hover:bg-[#7bd0ff]/30 transition-all hover:shadow-[0_0_20px_rgba(123,208,255,0.2)]">
            <span class="material-symbols-outlined text-[18px]">diamond</span> Premium'a Geç
        </a>
    </div>
    <?php endif; ?>

    <!-- Profil Teması (Premium) -->
    <?php
    $themes = [
        'default'  => ['label' => 'Varsayılan', 'colors' => ['#ff9100', '#2a2a2b'], 'gradient' => 'from-[#ff9100] to-[#E05520]'],
        'ocean'    => ['label' => 'Okyanus', 'colors' => ['#0EA5E9', '#0284C7'], 'gradient' => 'from-[#0EA5E9] to-[#0369A1]'],
        'sunset'   => ['label' => 'Gün Batımı', 'colors' => ['#F59E0B', '#EF4444'], 'gradient' => 'from-[#F59E0B] to-[#EF4444]'],
        'emerald'  => ['label' => 'Zümrüt', 'colors' => ['#10B981', '#059669'], 'gradient' => 'from-[#10B981] to-[#047857]'],
        'purple'   => ['label' => 'Mor', 'colors' => ['#8B5CF6', '#7C3AED'], 'gradient' => 'from-[#8B5CF6] to-[#6D28D9]'],
        'crimson'  => ['label' => 'Kızıl', 'colors' => ['#E11D48', '#BE123C'], 'gradient' => 'from-[#E11D48] to-[#9F1239]'],
    ];
    $currentTheme = $user['profile_theme'] ?? 'default';
    ?>
    <?php if (UserModel::isPremiumActive($user)): ?>
    <div class="rounded-2xl p-6 md:p-8 relative overflow-hidden" style="background:#fff;border:1px solid rgba(123,208,255,0.4);box-shadow:0 0 30px -5px rgba(123,208,255,0.15);">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[#7bd0ff]/5 to-transparent pointer-events-none"></div>
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] opacity-30 mix-blend-overlay pointer-events-none"></div>
        
        <h2 class="text-2xl font-black flex items-center gap-2 mb-2 relative z-10" style="color:var(--text-1);">
            <span class="material-symbols-outlined text-[#7bd0ff] text-[28px]">palette</span> Profil Teması
            <span class="bg-[#7bd0ff]/20 text-[#7bd0ff] text-[10px] font-black px-2 py-0.5 rounded border border-[#7bd0ff]/30 uppercase tracking-widest ml-2 shadow-[0_0_10px_rgba(123,208,255,0.3)]">Premium</span>
        </h2>
        <p class="text-sm mb-6 relative z-10 font-medium" style="color:var(--text-2);">Profilinin renk temasını seç. Seçtiğin tema profil sayfanda görünecek.</p>
        <form method="POST" class="relative z-10">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_theme">
            <input type="hidden" name="theme" id="theme_input" value="<?php echo escape($currentTheme); ?>">
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-6">
                <?php foreach ($themes as $key => $theme): ?>
                <button type="button" onclick="document.getElementById('theme_input').value='<?php echo $key; ?>';document.querySelectorAll('[data-theme-card]').forEach(c=>c.classList.remove('ring-2','ring-[#7bd0ff]','shadow-[0_0_15px_rgba(123,208,255,0.3)]'));this.querySelector('[data-theme-card]').classList.add('ring-2','ring-[#7bd0ff]','shadow-[0_0_15px_rgba(123,208,255,0.3)]')" class="group">
                    <div data-theme-card class="flex flex-col items-center gap-2 p-3 rounded-xl border transition-all <?php echo $currentTheme === $key ? 'ring-2 ring-[#7bd0ff] shadow-[0_0_15px_rgba(123,208,255,0.3)]' : ''; ?>" style="border-color:var(--border);background:var(--bg-section);">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br <?php echo $theme['gradient']; ?> shadow-lg"></div>
                        <span class="text-[10px] font-bold tracking-wide" style="color:var(--text-2);"><?php echo $theme['label']; ?></span>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="bg-gradient-to-r from-[#7bd0ff]/20 to-[#7bd0ff]/10 text-[#7bd0ff] px-8 py-3 rounded-xl font-black border border-[#7bd0ff]/40 hover:bg-[#7bd0ff]/30 transition-all active:scale-95 w-full sm:w-auto shadow-[0_0_20px_rgba(123,208,255,0.15)] flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">save</span> Temayı Kaydet
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="rounded-2xl p-6 md:p-8 relative overflow-hidden" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div class="absolute inset-0 bg-gradient-to-br from-transparent via-[#7bd0ff]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4" style="color:var(--text-1);">
            <span class="material-symbols-outlined text-[24px]" style="color:var(--text-3);">palette</span> Profil Teması
        </h2>
        <p class="text-sm mb-6 leading-relaxed" style="color:var(--text-3);">Profil temanı değiştirmek ve sayfanı kişiselleştirmek için Premium üye olman gerekir.</p>
        <a href="<?php echo BASE_URL; ?>/premium" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-[#7bd0ff]/20 to-[#7bd0ff]/10 text-[#7bd0ff] px-6 py-3 rounded-xl font-black text-sm border border-[#7bd0ff]/30 hover:bg-[#7bd0ff]/30 transition-all hover:shadow-[0_0_20px_rgba(123,208,255,0.2)]">
            <span class="material-symbols-outlined text-[18px]">diamond</span> Premium'a Geç
        </a>
    </div>
    <?php endif; ?>

    <!-- Profil Bilgileri -->
    <div class="rounded-2xl p-6 md:p-8" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-6" style="color:var(--text-1);"><span class="material-symbols-outlined text-[24px]" style="color:var(--color-primary);">contact_mail</span> Profil Bilgileri</h2>
        <form method="POST" class="flex flex-col gap-5">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_profile">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold ml-1" style="color:var(--text-2);">Kullanıcı Adı</label>
                    <input type="text" name="username" value="<?php echo escape($user['username']); ?>" required class="w-full rounded-xl px-4 py-3 focus:outline-none transition-all shadow-inner" style="background:var(--bg-section);border:1px solid var(--border);color:var(--text-1);">
                </div>
                
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold ml-1" style="color:var(--text-2);">Etiket (@tag)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-primary-container font-black">@</span>
                        <input type="text" name="tag" value="<?php echo escape($user['tag'] ?? ''); ?>" pattern="[a-zA-Z0-9_]{3,30}" class="w-full rounded-xl pl-10 pr-4 py-3 focus:outline-none transition-all shadow-inner" style="background:var(--bg-section);border:1px solid var(--border);color:var(--text-1);">
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold ml-1" style="color:var(--text-2);">E-posta</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px]" style="color:var(--text-3);">mail</span>
                    <?php if (!empty($user['gta_user_id'])): ?>
                    <!-- GTA karakterleri için email readonly, space içerebilir -->
                    <input type="text" name="email" value="<?php echo escape($user['email']); ?>" readonly
                           class="w-full rounded-xl pl-12 pr-4 py-3 focus:outline-none"
                           style="background:var(--bg-input);border:1px solid var(--border);color:var(--text-3);cursor:not-allowed;">
                    <?php else: ?>
                    <input type="email" name="email" value="<?php echo escape($user['email']); ?>" required
                           class="w-full rounded-xl pl-12 pr-4 py-3 focus:outline-none transition-all shadow-inner"
                           style="background:var(--bg-section);border:1px solid var(--border);color:var(--text-1);">
                    <?php endif; ?>
                </div>
                <?php if (!empty($user['gta_user_id'])): ?>
                <p class="text-[11px] ml-1" style="color:var(--text-3);">GTA karakterleri için e-posta değiştirilemez.</p>
                <?php endif; ?>
            </div>


            
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold ml-1" style="color:var(--text-2);">Biyografi</label>
                <?php $maxBio = UserModel::isPremiumActive($user) ? 500 : 280; ?>
                <textarea name="bio" rows="3" maxlength="<?php echo $maxBio; ?>" class="w-full rounded-xl px-4 py-3 focus:outline-none transition-all shadow-inner resize-y" style="background:var(--bg-section);border:1px solid var(--border);color:var(--text-1);"><?php echo escape($user['bio'] ?? ''); ?></textarea>
                <div class="text-xs font-medium ml-1 flex items-center justify-between" style="color:var(--text-3);">
                    <span>Kendinizden kısaca bahsedin.</span>
                    <span><?php echo $maxBio === 500 ? 'Maks 500 karakter (Premium 💎)' : 'Maks 280 karakter'; ?></span>
                </div>
            </div>
            
            <button type="submit" class="mt-4 bg-primary-container text-white px-8 py-3 rounded-xl font-bold shadow-[0_0_20px_rgba(255,145,0,0.3)] hover:bg-primary-container/90 transition-all active:scale-95 w-full sm:w-auto flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">save</span> Bilgileri Kaydet
            </button>
        </form>
    </div>

    <!-- Banka Hesabı (ayrı form) -->
    <div class="rounded-2xl p-6 md:p-8" style="background:#fff;border:1.5px solid var(--color-primary);box-shadow:0 4px 20px rgba(240,109,31,.12);">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-2" style="color:var(--text-1);">
            <span class="material-symbols-outlined text-[24px]" style="color:var(--color-primary);">account_balance</span>
            Banka Hesap Numarası
        </h2>
        <p class="text-sm mb-5" style="color:var(--text-3);">Bakiye çekim işlemlerinizin gönderileceği banka hesap numarası.</p>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_bank">
            <div class="relative mb-4">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px]" style="color:var(--text-3);">account_balance</span>
                <input type="text" name="bank_account"
                       value="<?php echo escape($user['bank_account'] ?? ''); ?>"
                       placeholder="0300 8108 7"
                       class="w-full rounded-xl pl-12 pr-4 py-3 focus:outline-none transition-all font-mono"
                       style="background:var(--bg-section);border:1.5px solid var(--border);color:var(--text-1);font-size:16px;letter-spacing:.05em;"
                       onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
            </div>
            <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1;">save</span>
                Banka Hesabını Kaydet
            </button>
        </form>
    </div>

    <!-- Şifre Değiştir -->
    <div class="rounded-2xl p-6 md:p-8" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-6" style="color:var(--text-1);"><span class="material-symbols-outlined text-[24px]" style="color:var(--color-primary);">lock</span> Şifre Değiştir</h2>
        <form method="POST" class="flex flex-col gap-5">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_password">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold ml-1" style="color:var(--text-2);">Mevcut Şifre</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px]" style="color:var(--text-3);">key</span>
                        <input type="password" name="current_password" required class="w-full rounded-xl pl-12 pr-4 py-3 focus:outline-none transition-all shadow-inner" style="background:var(--bg-section);border:1px solid var(--border);color:var(--text-1);">
                    </div>
                </div>
                
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold ml-1" style="color:var(--text-2);">Yeni Şifre</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px]" style="color:var(--text-3);">lock_reset</span>
                        <input type="password" name="new_password" required minlength="6" class="w-full rounded-xl pl-12 pr-4 py-3 focus:outline-none transition-all shadow-inner" style="background:var(--bg-section);border:1px solid var(--border);color:var(--text-1);">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="mt-2 border px-8 py-3 rounded-xl font-bold transition-all active:scale-95 w-full sm:w-auto flex justify-center items-center gap-2" style="background:var(--bg-section);color:var(--text-1);border-color:var(--border);">
                <span class="material-symbols-outlined text-[20px]">update</span> Şifreyi Güncelle
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
