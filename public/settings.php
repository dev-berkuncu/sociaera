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
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $result = $userModel->updateProfile(Auth::id(), $_POST);
        if ($result['ok']) {
            $user = $userModel->getById(Auth::id());
            Auth::refresh(['username' => $user['username']]);
            $success = 'Profil güncellendi.';
        } else {
            $error = $result['error'];
        }
    } elseif ($action === 'update_avatar') {
        if (!empty($_FILES['avatar']['name'])) {
            $uploader = new ImageUploader();
            $old = $user['avatar'];
            $result = $uploader->upload($_FILES['avatar'], 'avatars', [
                'maxSize' => MAX_AVATAR_SIZE,
                'maxWidth' => AVATAR_MAX_W,
                'maxHeight' => AVATAR_MAX_H,
                'outputFormat' => 'webp',
            ]);
            if ($result['success']) {
                $userModel->updateAvatar(Auth::id(), $result['filename']);
                if ($old) $uploader->delete('avatars', $old);
                Auth::refresh(['avatar' => $result['filename']]);
                $success = 'Avatar güncellendi.';
                $user = $userModel->getById(Auth::id());
            } else {
                $error = $result['error'];
            }
        }
    } elseif ($action === 'update_banner') {
        if (!empty($_FILES['banner']['name'])) {
            $uploader = new ImageUploader();
            $old = $user['banner'];
            $result = $uploader->upload($_FILES['banner'], 'banners', [
                'maxSize' => MAX_BANNER_SIZE,
                'maxWidth' => BANNER_MAX_W,
                'maxHeight' => BANNER_MAX_H,
                'outputFormat' => 'webp',
            ]);
            if ($result['success']) {
                $userModel->updateBanner(Auth::id(), $result['filename']);
                if ($old) $uploader->delete('banners', $old);
                $success = 'Banner güncellendi.';
                $user = $userModel->getById(Auth::id());
            } else {
                $error = $result['error'];
            }
        }
    } elseif ($action === 'change_password') {
        $result = $userModel->changePassword(Auth::id(), $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
        if ($result['ok']) { $success = 'Şifre değiştirildi.'; }
        else { $error = $result['error']; }
    } elseif ($action === 'update_badge') {
        if (!UserModel::isPremiumActive($user)) {
            $error = 'Rozet değiştirmek için Premium üye olmanız gerekir.';
        } else {
            $badge = $_POST['badge'] ?? null;
            $badges = UserModel::availableBadges();
            if ($badge && !isset($badges[$badge])) {
                $error = 'Geçersiz rozet seçimi.';
            } else {
                $userModel->updateBadge(Auth::id(), $badge ?: null);
                $success = 'Rozet güncellendi.';
                $user = $userModel->getById(Auth::id());
            }
        }
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

<section class="flex-1 flex flex-col gap-stack-md max-w-2xl w-full mx-auto lg:mx-0">
    <div class="mb-4">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[32px]">settings</span> Ayarlar</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-error/10 border border-error/50 text-error px-4 py-3 rounded-lg mb-2 flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <span><?php echo escape($error); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-[#10b981]/10 border border-[#10b981]/50 text-[#10b981] px-4 py-3 rounded-lg mb-2 flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span><?php echo escape($success); ?></span>
        </div>
    <?php endif; ?>

    <!-- Avatar -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.5)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-6 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[24px]">account_circle</span> Avatar</h2>
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <?php $pAvatar = $user['avatar'] ? BASE_URL . '/uploads/avatars/' . $user['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=random'; ?>
            <div class="relative group">
                <img src="<?php echo $pAvatar; ?>" class="w-32 h-32 rounded-full object-cover border-4 border-[#1E293B] shadow-xl flex-shrink-0 relative z-10 bg-[#1E293B]">
                <div class="absolute inset-0 rounded-full bg-primary-container blur-md -z-10 opacity-20 group-hover:opacity-40 transition-opacity"></div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="flex-1 w-full flex flex-col gap-3">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_avatar">
                
                <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-white/20 rounded-xl cursor-pointer bg-white/5 hover:bg-white/10 hover:border-primary-container/50 transition-colors">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <span class="material-symbols-outlined text-slate-400 mb-1">upload</span>
                        <p class="text-sm text-slate-400"><span class="font-bold text-primary-container">Tıkla</span> veya sürükle</p>
                        <p class="text-xs text-slate-500 mt-1">Maks. 10MB, JPEG / PNG / WebP</p>
                    </div>
                    <input type="file" name="avatar" accept="image/*" required class="hidden" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>

    <!-- Banner -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.5)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-6 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[24px]">image</span> Banner</h2>
        <div class="h-40 rounded-xl overflow-hidden bg-surface-container relative mb-5 border border-white/10 shadow-inner group">
            <?php if (bannerUrl($user['banner'])): ?>
                <img src="<?php echo bannerUrl($user['banner']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full bg-gradient-to-r from-primary-container/40 to-surface-container-high"></div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                <span class="material-symbols-outlined text-white text-[32px]">wallpaper</span>
            </div>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_banner">
            
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <label class="flex-1 w-full flex items-center justify-center gap-3 h-12 border border-white/20 rounded-xl cursor-pointer bg-white/5 hover:bg-white/10 hover:border-primary-container/50 transition-colors px-4">
                    <span class="material-symbols-outlined text-slate-400 text-[20px]">upload</span>
                    <span class="text-sm text-slate-300 font-medium whitespace-nowrap overflow-hidden text-ellipsis">Yeni Banner Seç...</span>
                    <input type="file" name="banner" accept="image/*" required class="hidden" onchange="this.nextElementSibling.innerText = this.files[0].name">
                    <span class="hidden"></span>
                </label>
                <button type="submit" class="w-full sm:w-auto h-12 bg-primary-container hover:bg-primary-container/90 text-white px-6 rounded-xl text-sm font-bold transition-all shadow-[0_0_15px_rgba(255,107,53,0.3)] active:scale-95 shrink-0 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">cloud_upload</span> Yükle
                </button>
            </div>
            <div class="text-xs text-slate-500 mt-3 text-center sm:text-left">Maks. 10MB, önerilen 1500x500px</div>
        </form>
    </div>

    <!-- Rozet Seçimi (Premium) -->
    <?php if (UserModel::isPremiumActive($user)): ?>
    <div class="bg-[#1E293B]/90 backdrop-blur-[20px] border border-[#7bd0ff]/40 rounded-2xl p-6 md:p-8 shadow-[0_0_30px_-5px_rgba(123,208,255,0.2)] relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[#7bd0ff]/5 to-transparent pointer-events-none"></div>
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] opacity-30 mix-blend-overlay pointer-events-none"></div>
        
        <h2 class="text-2xl font-black flex items-center gap-2 mb-2 text-white drop-shadow-md relative z-10">
            <span class="material-symbols-outlined text-[#7bd0ff] text-[28px]">workspace_premium</span> Profil Rozeti
            <span class="bg-[#7bd0ff]/20 text-[#7bd0ff] text-[10px] font-black px-2 py-0.5 rounded border border-[#7bd0ff]/30 uppercase tracking-widest ml-2 shadow-[0_0_10px_rgba(123,208,255,0.3)]">Premium</span>
        </h2>
        <p class="text-slate-300 text-sm mb-6 relative z-10 font-medium">Profilinde adının yanında görünecek özel premium rozetini seç.</p>
        <form method="POST" class="relative z-10">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_badge">
            <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3 mb-6">
                <!-- Rozet yok seçeneği -->
                <label class="cursor-pointer">
                    <input type="radio" name="badge" value="" <?php echo empty($user['badge']) ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border border-white/10 bg-white/5 peer-checked:border-[#7bd0ff]/60 peer-checked:bg-[#7bd0ff]/15 peer-checked:shadow-[0_0_15px_rgba(123,208,255,0.3)] hover:bg-white/10 transition-all h-20">
                        <span class="material-symbols-outlined text-[24px] text-slate-500">block</span>
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Yok</span>
                    </div>
                </label>
                <?php foreach (UserModel::availableBadges() as $key => $badge): ?>
                <label class="cursor-pointer">
                    <input type="radio" name="badge" value="<?php echo $key; ?>" <?php echo ($user['badge'] ?? '') === $key ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border border-white/10 bg-white/5 peer-checked:border-[#7bd0ff]/60 peer-checked:bg-[#7bd0ff]/15 peer-checked:shadow-[0_0_15px_rgba(123,208,255,0.3)] hover:bg-white/10 transition-all h-20">
                        <span class="material-symbols-outlined text-[28px]" style="color: <?php echo $badge['color']; ?>; text-shadow: 0 0 10px <?php echo $badge['color']; ?>80;"><?php echo $badge['icon']; ?></span>
                        <span class="text-[9px] text-slate-300 font-bold tracking-wide truncate w-full text-center"><?php echo $badge['label']; ?></span>
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
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-br from-transparent via-[#7bd0ff]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4 text-on-surface">
            <span class="material-symbols-outlined text-slate-500 text-[24px]">workspace_premium</span> Profil Rozeti
        </h2>
        <p class="text-slate-400 text-sm mb-6 leading-relaxed">Profil rozeti seçmek ve adının yanında havalı bir ikonla görünmek için Premium üye olman gerekir.</p>
        <a href="<?php echo BASE_URL; ?>/premium" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-[#7bd0ff]/20 to-[#7bd0ff]/10 text-[#7bd0ff] px-6 py-3 rounded-xl font-black text-sm border border-[#7bd0ff]/30 hover:bg-[#7bd0ff]/30 transition-all hover:shadow-[0_0_20px_rgba(123,208,255,0.2)]">
            <span class="material-symbols-outlined text-[18px]">diamond</span> Premium'a Geç
        </a>
    </div>
    <?php endif; ?>

    <!-- Profil Bilgileri -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.5)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-6 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[24px]">contact_mail</span> Profil Bilgileri</h2>
        <form method="POST" class="flex flex-col gap-5">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_profile">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-300 ml-1">Kullanıcı Adı</label>
                    <input type="text" name="username" value="<?php echo escape($user['username']); ?>" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-on-surface focus:border-primary-container focus:bg-white/10 outline-none transition-all shadow-inner">
                </div>
                
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-300 ml-1">Etiket (@tag)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-primary-container font-black">@</span>
                        <input type="text" name="tag" value="<?php echo escape($user['tag'] ?? ''); ?>" pattern="[a-zA-Z0-9_]{3,30}" class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-3 text-on-surface focus:border-primary-container focus:bg-white/10 outline-none transition-all shadow-inner">
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold text-slate-300 ml-1">E-posta</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[18px]">mail</span>
                    <input type="email" name="email" value="<?php echo escape($user['email']); ?>" required class="w-full bg-white/5 border border-white/10 rounded-xl pl-12 pr-4 py-3 text-on-surface focus:border-primary-container focus:bg-white/10 outline-none transition-all shadow-inner">
                </div>
            </div>
            
            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold text-slate-300 ml-1">Biyografi</label>
                <textarea name="bio" rows="3" maxlength="280" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-on-surface focus:border-primary-container focus:bg-white/10 outline-none transition-all shadow-inner resize-y"><?php echo escape($user['bio'] ?? ''); ?></textarea>
                <div class="text-xs text-slate-500 font-medium ml-1 flex items-center justify-between">
                    <span>Kendinizden kısaca bahsedin.</span>
                    <span>Maks 280 karakter</span>
                </div>
            </div>
            
            <button type="submit" class="mt-4 bg-primary-container text-white px-8 py-3 rounded-xl font-bold shadow-[0_0_20px_rgba(255,107,53,0.3)] hover:bg-primary-container/90 transition-all active:scale-95 w-full sm:w-auto flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">save</span> Bilgileri Kaydet
            </button>
        </form>
    </div>

    <!-- Şifre Değiştir -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.5)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-6 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[24px]">lock</span> Şifre Değiştir</h2>
        <form method="POST" class="flex flex-col gap-5">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_password">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-300 ml-1">Mevcut Şifre</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[18px]">key</span>
                        <input type="password" name="current_password" required class="w-full bg-white/5 border border-white/10 rounded-xl pl-12 pr-4 py-3 text-on-surface focus:border-primary-container focus:bg-white/10 outline-none transition-all shadow-inner">
                    </div>
                </div>
                
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-300 ml-1">Yeni Şifre</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[18px]">lock_reset</span>
                        <input type="password" name="new_password" required minlength="6" class="w-full bg-white/5 border border-white/10 rounded-xl pl-12 pr-4 py-3 text-on-surface focus:border-primary-container focus:bg-white/10 outline-none transition-all shadow-inner">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="mt-2 bg-white/5 hover:bg-white/10 text-white border border-white/10 px-8 py-3 rounded-xl font-bold transition-all active:scale-95 w-full sm:w-auto flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">update</span> Şifreyi Güncelle
            </button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
