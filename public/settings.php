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
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4 text-on-surface"><span class="material-symbols-outlined text-primary-container">account_circle</span> Avatar</h2>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
            <?php $pAvatar = $user['avatar'] ? BASE_URL . '/uploads/avatars/' . $user['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=random'; ?>
            <img src="<?php echo $pAvatar; ?>" class="w-24 h-24 rounded-full object-cover border-2 border-white/10 flex-shrink-0">
            
            <form method="POST" enctype="multipart/form-data" class="flex-1 w-full">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_avatar">
                
                <div class="flex flex-col gap-2">
                    <input type="file" name="avatar" accept="image/*" required class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container/20 file:text-primary-container hover:file:bg-primary-container/30 cursor-pointer">
                    <div class="text-xs text-slate-500">Maks. 10MB, JPEG / PNG / WebP</div>
                    <button type="submit" class="w-fit mt-2 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors border border-white/10">Yükle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Banner -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4 text-on-surface"><span class="material-symbols-outlined text-primary-container">image</span> Banner</h2>
        <div class="h-32 rounded-xl overflow-hidden bg-surface-container relative mb-4 border border-white/10">
            <?php if (bannerUrl($user['banner'])): ?>
                <img src="<?php echo bannerUrl($user['banner']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full bg-gradient-to-r from-primary-container/40 to-surface-container-high"></div>
            <?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_banner">
            
            <div class="flex flex-col gap-2">
                <input type="file" name="banner" accept="image/*" required class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container/20 file:text-primary-container hover:file:bg-primary-container/30 cursor-pointer">
                <div class="text-xs text-slate-500">Maks. 10MB, önerilen 1500x500px</div>
                <button type="submit" class="w-fit mt-2 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors border border-white/10">Yükle</button>
            </div>
        </form>
    </div>

    <!-- Rozet Seçimi (Premium) -->
    <?php if (UserModel::isPremiumActive($user)): ?>
    <div class="bg-gradient-to-br from-[#1E293B]/80 to-surface-container border border-[#7bd0ff]/20 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(123,208,255,0.1)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4 text-on-surface">
            <span class="material-symbols-outlined text-[#7bd0ff]">workspace_premium</span> Profil Rozeti
            <span class="bg-[#7bd0ff]/20 text-[#7bd0ff] text-[10px] font-bold px-2 py-0.5 rounded border border-[#7bd0ff]/30 uppercase tracking-wider ml-1">Premium</span>
        </h2>
        <p class="text-slate-400 text-sm mb-4">Profilinde görünecek rozeti seç. Rozet kullanıcı adının yanında gösterilir.</p>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_badge">
            <div class="grid grid-cols-5 gap-3 mb-4">
                <!-- Rozet yok seçeneği -->
                <label class="cursor-pointer">
                    <input type="radio" name="badge" value="" <?php echo empty($user['badge']) ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-white/10 peer-checked:border-[#7bd0ff]/50 peer-checked:bg-[#7bd0ff]/10 hover:bg-white/5 transition-all">
                        <span class="material-symbols-outlined text-[28px] text-slate-500">block</span>
                        <span class="text-[10px] text-slate-500 font-semibold">Yok</span>
                    </div>
                </label>
                <?php foreach (UserModel::availableBadges() as $key => $badge): ?>
                <label class="cursor-pointer">
                    <input type="radio" name="badge" value="<?php echo $key; ?>" <?php echo ($user['badge'] ?? '') === $key ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-white/10 peer-checked:border-[#7bd0ff]/50 peer-checked:bg-[#7bd0ff]/10 hover:bg-white/5 transition-all">
                        <span class="material-symbols-outlined text-[28px]" style="color: <?php echo $badge['color']; ?>"><?php echo $badge['icon']; ?></span>
                        <span class="text-[10px] text-slate-400 font-semibold"><?php echo $badge['label']; ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="bg-[#7bd0ff]/20 text-[#7bd0ff] px-6 py-2.5 rounded-lg font-bold border border-[#7bd0ff]/30 hover:bg-[#7bd0ff]/30 transition-colors active:scale-95 w-fit">Rozeti Kaydet</button>
        </form>
    </div>
    <?php else: ?>
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4 text-on-surface">
            <span class="material-symbols-outlined text-slate-500">workspace_premium</span> Profil Rozeti
        </h2>
        <p class="text-slate-400 text-sm mb-4">Profil rozeti seçmek için Premium üye olmanız gerekir.</p>
        <a href="<?php echo BASE_URL; ?>/premium" class="inline-flex items-center gap-2 bg-primary-container/20 text-primary-container px-4 py-2 rounded-lg font-bold text-sm border border-primary-container/30 hover:bg-primary-container/30 transition-colors">
            <span class="material-symbols-outlined text-[18px]">diamond</span> Premium'a Geç
        </a>
    </div>
    <?php endif; ?>

    <!-- Profil Bilgileri -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4 text-on-surface"><span class="material-symbols-outlined text-primary-container">contact_mail</span> Profil Bilgileri</h2>
        <form method="POST" class="flex flex-col gap-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_profile">
            
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-bold text-slate-300">Kullanıcı Adı</label>
                <input type="text" name="username" value="<?php echo escape($user['username']); ?>" required class="bg-background border border-white/10 rounded-lg px-4 py-2 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm">
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-bold text-slate-300">Etiket (@tag)</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold">@</span>
                    <input type="text" name="tag" value="<?php echo escape($user['tag'] ?? ''); ?>" pattern="[a-zA-Z0-9_]{3,30}" class="w-full bg-background border border-white/10 rounded-lg pl-9 pr-4 py-2 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm">
                </div>
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-bold text-slate-300">E-posta</label>
                <input type="email" name="email" value="<?php echo escape($user['email']); ?>" required class="bg-background border border-white/10 rounded-lg px-4 py-2 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm">
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-bold text-slate-300">Biyografi</label>
                <textarea name="bio" rows="3" maxlength="280" class="bg-background border border-white/10 rounded-lg px-4 py-2 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm resize-y"><?php echo escape($user['bio'] ?? ''); ?></textarea>
                <div class="text-xs text-slate-500">Maks 280 karakter</div>
            </div>
            
            <button type="submit" class="mt-2 bg-primary-container text-white px-6 py-2.5 rounded-lg font-bold shadow-[0_0_10px_rgba(255,107,53,0.2)] hover:bg-primary-container/90 transition-all active:scale-95 w-fit">Kaydet</button>
        </form>
    </div>

    <!-- Şifre Değiştir -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-4 text-on-surface"><span class="material-symbols-outlined text-primary-container">lock</span> Şifre Değiştir</h2>
        <form method="POST" class="flex flex-col gap-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_password">
            
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-bold text-slate-300">Mevcut Şifre</label>
                <input type="password" name="current_password" required class="bg-background border border-white/10 rounded-lg px-4 py-2 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm">
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-bold text-slate-300">Yeni Şifre</label>
                <input type="password" name="new_password" required minlength="6" class="bg-background border border-white/10 rounded-lg px-4 py-2 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm">
            </div>
            
            <button type="submit" class="mt-2 bg-white/10 hover:bg-white/20 text-white border border-white/10 px-6 py-2.5 rounded-lg font-bold transition-all active:scale-95 w-fit">Şifreyi Güncelle</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
