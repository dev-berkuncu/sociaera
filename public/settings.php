<?php
ob_start(); // Output buffering — header() her zaman çalışsın
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
        // GTA hesaplarında email değiştirilemesin
        if (!empty($user['gta_user_id'])) {
            $_POST['email'] = $user['email'];
        }
        $result = $userModel->updateProfile(Auth::id(), $_POST);
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            if ($result['ok']) {
                $u = $userModel->getById(Auth::id());
                Auth::refresh(['username' => $u['username']]);
                echo json_encode(['ok' => true,  'message' => 'Profil güncellendi.']);
            } else {
                echo json_encode(['ok' => false, 'error'   => $result['error']]);
            }
            exit;
        }
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
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            if (empty($bank)) {
                echo json_encode(['ok' => false, 'error' => 'Banka hesap numarası boş bırakılamaz.']);
            } else {
                $userModel->updateField(Auth::id(), 'bank_account', $bank);
                echo json_encode(['ok' => true, 'message' => 'Banka hesap numarası kaydedildi ✓']);
            }
            exit;
        }
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

<div style="min-width:0;display:flex;flex-direction:column;gap:16px;">

    <!-- Başlık -->
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;color:var(--text-1);display:flex;align-items:center;gap:8px;margin:0 0 4px;">
            <span class="material-symbols-outlined" style="font-size:26px;color:var(--color-primary);">settings</span>
            Ayarlar
        </h1>
    </div>

    <!-- Avatar -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <h2 style="font-size:15px;font-weight:700;color:var(--text-1);display:flex;align-items:center;gap:6px;margin:0 0 16px;">
            <span class="material-symbols-outlined" style="font-size:20px;color:var(--color-primary);">account_circle</span>
            Avatar
        </h2>
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <?php $pAvatar = safeAvatarUrl($user['avatar'] ?? null, $user['username']); ?>
            <img src="<?php echo $pAvatar; ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 2px 12px rgba(0,0,0,.12);flex-shrink:0;">
            <form method="POST" enctype="multipart/form-data" style="flex:1;min-width:200px;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_avatar">
                <label style="display:flex;align-items:center;gap:10px;border:1.5px dashed var(--border);border-radius:12px;padding:14px 18px;cursor:pointer;background:var(--bg-section);transition:border-color .15s;" onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='var(--border)'">
                    <span class="material-symbols-outlined" style="font-size:22px;color:var(--text-3);">upload</span>
                    <span style="font-size:13px;color:var(--text-2);">Yeni fotoğraf seç <span style="color:var(--text-3);font-size:11px;">(JPEG/PNG/WebP, max 10MB)</span></span>
                    <input type="file" name="avatar" accept="image/*" required style="display:none;" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>

    <!-- Banner -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <h2 style="font-size:15px;font-weight:700;color:var(--text-1);display:flex;align-items:center;gap:6px;margin:0 0 14px;">
            <span class="material-symbols-outlined" style="font-size:20px;color:var(--color-primary);">image</span>
            Banner
        </h2>
        <div style="height:120px;border-radius:10px;overflow:hidden;margin-bottom:14px;background:var(--bg-section);border:1px solid var(--border);">
            <?php if (bannerUrl($user['banner'])): ?>
                <img src="<?php echo bannerUrl($user['banner']); ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#FFF3EB,#F5F4F0);display:flex;align-items:center;justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:32px;color:var(--text-3);opacity:.4;">wallpaper</span>
                </div>
            <?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_banner">
            <label style="flex:1;min-width:180px;display:flex;align-items:center;gap:8px;border:1px solid var(--border);border-radius:10px;padding:10px 14px;cursor:pointer;background:var(--bg-section);">
                <span class="material-symbols-outlined" style="font-size:18px;color:var(--text-3);">upload</span>
                <span id="bannerFileName" style="font-size:13px;color:var(--text-2);">Banner seç...</span>
                <input type="file" name="banner" accept="image/*" required style="display:none;" onchange="document.getElementById('bannerFileName').textContent=this.files[0].name">
            </label>
            <button type="submit" style="background:var(--color-primary);color:#fff;border:none;cursor:pointer;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px;">
                <span class="material-symbols-outlined" style="font-size:16px;">cloud_upload</span> Yükle
            </button>
        </form>
    </div>

    <!-- Profil Bilgileri — AJAX -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <h2 style="font-size:15px;font-weight:700;color:var(--text-1);display:flex;align-items:center;gap:6px;margin:0 0 18px;">
            <span class="material-symbols-outlined" style="font-size:20px;color:var(--color-primary);">contact_mail</span>
            Profil Bilgileri
        </h2>
        <form id="profileForm" action="<?php echo BASE_URL; ?>/settings" method="POST" style="display:flex;flex-direction:column;gap:14px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="ajax" value="1">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:6px;">Kullanıcı Adı</label>
                    <input type="text" name="username" value="<?php echo escape($user['username']); ?>"
                           style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px;font-size:14px;color:var(--text-1);outline:none;"
                           onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:6px;">Etiket (@tag)</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-weight:800;color:var(--color-primary);font-size:14px;">@</span>
                        <input type="text" name="tag" value="<?php echo escape($user['tag'] ?? ''); ?>" pattern="[a-zA-Z0-9_]{3,30}"
                               style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px 10px 28px;font-size:14px;color:var(--text-1);outline:none;"
                               onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
                    </div>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:6px;">E-posta</label>
                <?php if (!empty($user['gta_user_id'])): ?>
                <input type="text" name="email" value="<?php echo escape($user['email']); ?>" readonly
                       style="width:100%;box-sizing:border-box;background:var(--bg-input);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px;font-size:14px;color:var(--text-3);cursor:not-allowed;">
                <p style="font-size:11px;color:var(--text-3);margin:4px 0 0;">GTA karakterleri için e-posta değiştirilemez.</p>
                <?php else: ?>
                <input type="email" name="email" value="<?php echo escape($user['email']); ?>"
                       style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px;font-size:14px;color:var(--text-1);outline:none;"
                       onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
                <?php endif; ?>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:6px;">Biyografi</label>
                <?php $maxBio = UserModel::isPremiumActive($user) ? 500 : 280; ?>
                <textarea name="bio" rows="3" maxlength="<?php echo $maxBio; ?>"
                          style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px;font-size:14px;color:var(--text-1);outline:none;resize:vertical;font-family:inherit;"
                          onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'"><?php echo escape($user['bio'] ?? ''); ?></textarea>
                <div style="font-size:11px;color:var(--text-3);margin-top:4px;">Maks. <?php echo $maxBio; ?> karakter<?php echo $maxBio===500?' (Premium 💎)':''; ?></div>
            </div>

            <div>
                <button type="submit" id="profileSaveBtn"
                        style="background:var(--color-primary);color:#fff;border:none;cursor:pointer;padding:11px 24px;border-radius:10px;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:7px;">
                    <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1;">save</span>
                    Bilgileri Kaydet
                </button>
            </div>
        </form>
    </div>

    <!-- Banka Hesabı — AJAX -->
    <div style="background:#fff;border:2px solid var(--color-primary);border-radius:16px;padding:20px 24px;box-shadow:0 4px 20px rgba(240,109,31,.10);">
        <h2 style="font-size:15px;font-weight:700;color:var(--text-1);display:flex;align-items:center;gap:6px;margin:0 0 6px;">
            <span class="material-symbols-outlined" style="font-size:20px;color:var(--color-primary);">account_balance</span>
            Banka Hesap Numarası
        </h2>
        <p style="font-size:13px;color:var(--text-3);margin:0 0 14px;">Bakiye çekim işlemlerinin gönderileceği hesap numarası.</p>
        <form id="bankForm" action="<?php echo BASE_URL; ?>/settings" method="POST" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_bank">
            <input type="hidden" name="ajax" value="1">
            <input type="text" name="bank_account"
                   value="<?php echo escape($user['bank_account'] ?? ''); ?>"
                   placeholder="0300 8108 7"
                   style="flex:1;min-width:160px;background:var(--bg-section);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px;font-size:15px;font-family:monospace;color:var(--text-1);outline:none;letter-spacing:.08em;"
                   onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
            <button type="submit" id="bankSaveBtn"
                    style="background:var(--color-primary);color:#fff;border:none;cursor:pointer;padding:11px 20px;border-radius:10px;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;">
                <span class="material-symbols-outlined" style="font-size:17px;font-variation-settings:'FILL' 1;">save</span>
                Kaydet
            </button>
        </form>
    </div>

    <!-- Şifre Değiştir -->
    <?php if (empty($user['gta_user_id'])): ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <h2 style="font-size:15px;font-weight:700;color:var(--text-1);display:flex;align-items:center;gap:6px;margin:0 0 18px;">
            <span class="material-symbols-outlined" style="font-size:20px;color:var(--color-primary);">lock</span>
            Şifre Değiştir
        </h2>
        <form method="POST" action="<?php echo BASE_URL; ?>/settings" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_password">
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:6px;">Mevcut Şifre</label>
                <input type="password" name="current_password" required
                       style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px;font-size:14px;color:var(--text-1);outline:none;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:6px;">Yeni Şifre</label>
                <input type="password" name="new_password" required minlength="6"
                       style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);border-radius:10px;padding:10px 14px;font-size:14px;color:var(--text-1);outline:none;">
            </div>
            <div style="grid-column:1/-1;">
                <button type="submit"
                        style="background:var(--bg-section);color:var(--text-1);border:1.5px solid var(--border);cursor:pointer;padding:10px 22px;border-radius:10px;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:7px;">
                    <span class="material-symbols-outlined" style="font-size:17px;">update</span>
                    Şifreyi Güncelle
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Rozet (Premium) -->
    <?php if (UserModel::isPremiumActive($user)): ?>
    <div style="background:#fff;border:1.5px solid rgba(123,208,255,.5);border-radius:16px;padding:20px 24px;box-shadow:0 4px 20px rgba(123,208,255,.10);">
        <h2 style="font-size:15px;font-weight:700;color:var(--text-1);display:flex;align-items:center;gap:6px;margin:0 0 6px;">
            <span class="material-symbols-outlined" style="font-size:20px;color:#7bd0ff;">workspace_premium</span>
            Profil Rozeti
            <span style="background:rgba(123,208,255,.2);color:#7bd0ff;font-size:10px;font-weight:800;border-radius:99px;padding:2px 8px;border:1px solid rgba(123,208,255,.4);">PREMIUM</span>
        </h2>
        <p style="font-size:13px;color:var(--text-2);margin:0 0 14px;">Adının yanında görünecek özel rozeti seç.</p>
        <form method="POST" action="<?php echo BASE_URL; ?>/settings" style="display:flex;flex-direction:column;gap:14px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_badge">
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <label style="cursor:pointer;">
                    <input type="radio" name="badge" value="" <?php echo empty($user['badge']) ? 'checked' : ''; ?> style="display:none;" class="badgeRadio">
                    <div class="badgeCard" style="padding:10px 14px;border-radius:10px;border:1.5px solid var(--border);background:var(--bg-section);text-align:center;font-size:12px;font-weight:700;color:var(--text-3);">Yok</div>
                </label>
                <?php foreach (UserModel::availableBadges() as $key => $badge): ?>
                <label style="cursor:pointer;">
                    <input type="radio" name="badge" value="<?php echo $key; ?>" <?php echo ($user['badge'] ?? '') === $key ? 'checked' : ''; ?> style="display:none;" class="badgeRadio">
                    <div class="badgeCard" style="padding:10px 14px;border-radius:10px;border:1.5px solid var(--border);background:var(--bg-section);display:flex;flex-direction:column;align-items:center;gap:4px;min-width:60px;">
                        <span class="material-symbols-outlined" style="font-size:24px;color:<?php echo $badge['color']; ?>;"><?php echo $badge['icon']; ?></span>
                        <span style="font-size:10px;font-weight:700;color:var(--text-2);"><?php echo $badge['label']; ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <div>
                <button type="submit" style="background:rgba(123,208,255,.15);color:#7bd0ff;border:1.5px solid rgba(123,208,255,.4);cursor:pointer;padding:10px 22px;border-radius:10px;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:7px;">
                    <span class="material-symbols-outlined" style="font-size:17px;">save</span> Rozeti Kaydet
                </button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;box-shadow:0 1px 4px rgba(0,0,0,.06);">
        <h2 style="font-size:15px;font-weight:700;color:var(--text-2);display:flex;align-items:center;gap:6px;margin:0 0 8px;">
            <span class="material-symbols-outlined" style="font-size:20px;color:var(--text-3);">workspace_premium</span>
            Profil Rozeti
        </h2>
        <p style="font-size:13px;color:var(--text-3);margin:0 0 12px;">Rozet seçmek için Premium üye olman gerekir.</p>
        <a href="<?php echo BASE_URL; ?>/premium" style="display:inline-flex;align-items:center;gap:6px;background:rgba(123,208,255,.15);color:#7bd0ff;border:1.5px solid rgba(123,208,255,.4);padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            <span class="material-symbols-outlined" style="font-size:16px;">diamond</span> Premium'a Geç
        </a>
    </div>
    <?php endif; ?>

</div>

<script>
// Rozet seçim efekti
document.querySelectorAll('.badgeRadio').forEach(function(r){
    r.addEventListener('change', function(){
        document.querySelectorAll('.badgeCard').forEach(function(c){
            c.style.borderColor='var(--border)';
            c.style.background='var(--bg-section)';
        });
        var card = this.parentElement.querySelector('.badgeCard');
        if(card){ card.style.borderColor='#7bd0ff'; card.style.background='rgba(123,208,255,.1)'; }
    });
    // Sayfa yüklendiğinde seçili olanı işaretle
    if(r.checked){
        var card = r.parentElement.querySelector('.badgeCard');
        if(card){ card.style.borderColor='#7bd0ff'; card.style.background='rgba(123,208,255,.1)'; }
    }
});

// AJAX toast helper (App bağımsız)
function showToast(msg, type){
    if(window.App && App.flash){ App.flash(msg, type); return; }
    var t = document.createElement('div');
    t.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;background:'+(type==='success'?'#22c55e':'#ef4444')+';color:#fff;padding:12px 20px;border-radius:12px;font-size:14px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px;';
    t.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">'+(type==='success'?'check_circle':'error')+'</span>'+msg;
    document.body.appendChild(t);
    setTimeout(function(){ t.style.opacity='0'; t.style.transition='opacity .4s'; setTimeout(function(){ t.remove(); },400); }, 3500);
}

// Profil formu AJAX
document.getElementById('profileForm').addEventListener('submit', function(e){
    e.preventDefault();
    var btn = document.getElementById('profileSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">hourglass_top</span> Kaydediliyor...';
    fetch(this.action, { method:'POST', body: new FormData(this) })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d.ok){ showToast(d.message||'Profil güncellendi.','success'); }
            else     { showToast(d.error||'Bir hata oluştu.','error'); }
        })
        .catch(function(){ showToast('Bağlantı hatası.','error'); })
        .finally(function(){
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:\'FILL\' 1;">save</span> Bilgileri Kaydet';
        });
});

// Banka formu AJAX
document.getElementById('bankForm').addEventListener('submit', function(e){
    e.preventDefault();
    var btn = document.getElementById('bankSaveBtn');
    btn.disabled = true;
    fetch(this.action, { method:'POST', body: new FormData(this) })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d.ok){ showToast(d.message||'Banka hesabı kaydedildi.','success'); }
            else     { showToast(d.error||'Hata: '+d.error,'error'); }
        })
        .catch(function(){ showToast('Bağlantı hatası.','error'); })
        .finally(function(){ btn.disabled = false; });
});
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
