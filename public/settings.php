<?php
/**
 * Sociaera — Ayarlar (Settings)
 * Basit PRG: POST → işle → redirect → GET → göster
 */
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
$user      = $userModel->getById(Auth::id());
$redirectTo = BASE_URL . '/settings';

/* ── POST handler ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF */
    if (!Csrf::verifyRequest()) {
        Auth::setFlash('error', 'Güvenlik hatası. Sayfayı yenileyip tekrar deneyin.');
        header('Location: ' . $redirectTo); exit;
    }

    $action = $_POST['action'] ?? '';

    /* Profil bilgilerini güncelle */
    if ($action === 'update_profile') {

        $username = trim($_POST['username'] ?? $user['username']);
        $tag      = trim(ltrim($_POST['tag'] ?? $user['tag'] ?? '', '@'));
        $bio      = trim($_POST['bio'] ?? '');

        // GTA hesaplarında email değiştirilemesin
        $email = !empty($user['gta_user_id'])
            ? $user['email']
            : trim($_POST['email'] ?? $user['email']);

        // Model'e gönder (username boş gelmez çünkü forma mevcut değeri dolduruyoruz)
        $result = $userModel->updateProfile(Auth::id(), [
            'username'     => $username ?: $user['username'],
            'tag'          => $tag,
            'email'        => $email ?: $user['email'],
            'bio'          => $bio,
            'bank_account' => $user['bank_account'] ?? '', // değiştirme
        ]);

        if ($result['ok']) {
            Auth::refresh(['username' => $username ?: $user['username']]);
            Auth::setFlash('success', 'Profil güncellendi ✓');
        } else {
            Auth::setFlash('error', $result['error']);
        }
        header('Location: ' . $redirectTo); exit;

    /* Avatar */
    } elseif ($action === 'update_avatar') {
        if (!empty($_FILES['avatar']['name'])) {
            $uploader = new ImageUploader();
            $old      = $user['avatar'];
            $result   = $uploader->upload($_FILES['avatar'], 'avatars', [
                'maxSize'      => MAX_AVATAR_SIZE,
                'maxWidth'     => AVATAR_MAX_W,
                'maxHeight'    => AVATAR_MAX_H,
                'outputFormat' => 'webp',
            ]);
            if ($result['success']) {
                $userModel->updateAvatar(Auth::id(), $result['filename']);
                if ($old) $uploader->delete('avatars', $old);
                Auth::refresh(['avatar' => $result['filename']]);
                Auth::setFlash('success', 'Avatar güncellendi ✓');
            } else {
                Auth::setFlash('error', $result['error']);
            }
        }
        header('Location: ' . $redirectTo); exit;

    /* Banner */
    } elseif ($action === 'update_banner') {
        if (!empty($_FILES['banner']['name'])) {
            $uploader = new ImageUploader();
            $old      = $user['banner'];
            $result   = $uploader->upload($_FILES['banner'], 'banners', [
                'maxSize'      => MAX_BANNER_SIZE,
                'maxWidth'     => BANNER_MAX_W,
                'maxHeight'    => BANNER_MAX_H,
                'outputFormat' => 'webp',
            ]);
            if ($result['success']) {
                $userModel->updateBanner(Auth::id(), $result['filename']);
                if ($old) $uploader->delete('banners', $old);
                Auth::setFlash('success', 'Banner güncellendi ✓');
            } else {
                Auth::setFlash('error', $result['error']);
            }
        }
        header('Location: ' . $redirectTo); exit;

    /* Banka hesabı (tek zorunlu alan) */
    } elseif ($action === 'update_bank') {
        $bank = trim($_POST['bank_account'] ?? '');
        if (empty($bank)) {
            Auth::setFlash('error', 'Banka hesap numarası boş bırakılamaz.');
        } else {
            $userModel->updateField(Auth::id(), 'bank_account', $bank);
            Auth::setFlash('success', 'Banka hesap numarası kaydedildi ✓');
        }
        header('Location: ' . $redirectTo); exit;

    /* Şifre */
    } elseif ($action === 'change_password') {
        if (!empty($user['gta_user_id'])) {
            Auth::setFlash('error', 'GTA karakterleri için şifre değiştirilemez.');
        } else {
            $result = $userModel->changePassword(
                Auth::id(),
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? ''
            );
            if ($result['ok']) { Auth::setFlash('success', 'Şifre değiştirildi ✓'); }
            else                { Auth::setFlash('error',   $result['error']); }
        }
        header('Location: ' . $redirectTo); exit;

    /* Rozet (Premium) */
    } elseif ($action === 'update_badge') {
        if (!UserModel::isPremiumActive($user)) {
            Auth::setFlash('error', 'Rozet değiştirmek için Premium üyelik gerekir.');
        } else {
            $badge  = $_POST['badge'] ?? null;
            $badges = UserModel::availableBadges();
            if ($badge && !isset($badges[$badge])) {
                Auth::setFlash('error', 'Geçersiz rozet.');
            } else {
                $userModel->updateBadge(Auth::id(), $badge ?: null);
                Auth::setFlash('success', 'Rozet güncellendi ✓');
            }
        }
        header('Location: ' . $redirectTo); exit;
    }

    /* Bilinmeyen action */
    header('Location: ' . $redirectTo); exit;
}

/* ── GET — sayfa render ───────────────────────────────── */
$pageTitle = 'Ayarlar';
$activeNav = 'settings';
require_once __DIR__ . '/partials/app_header.php';

$maxBio     = UserModel::isPremiumActive($user) ? 500 : 280;
$pAvatar    = safeAvatarUrl($user['avatar'] ?? null, $user['username']);
$pBanner    = bannerUrl($user['banner'] ?? null);

/* Flash mesajı göster */
$flashType = null; $flashMsg = null;
if (!empty($_SESSION['flash'])) {
    foreach ((array)$_SESSION['flash'] as $ft => $fm) {
        $flashType = $ft; $flashMsg = $fm; break;
    }
    unset($_SESSION['flash']);
}
?>

<div style="min-width:0;display:flex;flex-direction:column;gap:16px;padding-bottom:40px;">

    <?php if ($flashMsg): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:12px;font-size:14px;font-weight:600;
                background:<?php echo $flashType === 'success' ? '#f0fdf4' : '#fef2f2'; ?>;
                border:1.5px solid <?php echo $flashType === 'success' ? '#86efac' : '#fca5a5'; ?>;
                color:<?php echo $flashType === 'success' ? '#166534' : '#991b1b'; ?>;">
        <span class="material-symbols-outlined" style="font-size:20px;font-variation-settings:'FILL' 1;">
            <?php echo $flashType === 'success' ? 'check_circle' : 'error'; ?>
        </span>
        <?php echo htmlspecialchars($flashMsg); ?>
    </div>
    <?php endif; ?>

    <!-- Başlık -->
    <h1 style="font-size:1.4rem;font-weight:800;color:var(--text-1);display:flex;align-items:center;gap:8px;margin:0;">
        <span class="material-symbols-outlined" style="font-size:24px;color:var(--color-primary);">settings</span>
        Ayarlar
    </h1>

    <!-- ── AVATAR ──────────────────────────────────────── -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;">
        <h2 style="font-size:14px;font-weight:700;color:var(--text-1);margin:0 0 14px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">account_circle</span>
            Avatar
        </h2>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <img src="<?php echo $pAvatar; ?>"
                 style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,.12);flex-shrink:0;">
            <form method="POST" enctype="multipart/form-data" style="flex:1;min-width:180px;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_avatar">
                <label style="display:flex;align-items:center;gap:8px;border:1.5px dashed var(--border);border-radius:10px;
                              padding:12px 16px;cursor:pointer;background:var(--bg-section);width:100%;box-sizing:border-box;">
                    <span class="material-symbols-outlined" style="font-size:20px;color:var(--text-3);">upload</span>
                    <span style="font-size:13px;color:var(--text-2);">Fotoğraf seç
                        <span style="color:var(--text-3);font-size:11px;">(JPG/PNG/WebP, max 10MB)</span>
                    </span>
                    <input type="file" name="avatar" accept="image/*" style="display:none;" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>

    <!-- ── BANNER ─────────────────────────────────────── -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;">
        <h2 style="font-size:14px;font-weight:700;color:var(--text-1);margin:0 0 12px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">image</span>
            Banner
        </h2>
        <div style="height:100px;border-radius:10px;overflow:hidden;margin-bottom:12px;background:var(--bg-section);border:1px solid var(--border);">
            <?php if ($pBanner): ?>
                <img src="<?php echo $pBanner; ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,#FFF3EB,#F5F4F0);display:flex;align-items:center;justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:28px;color:var(--text-3);opacity:.4;">wallpaper</span>
                </div>
            <?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_banner">
            <label style="flex:1;min-width:160px;display:flex;align-items:center;gap:8px;border:1px solid var(--border);
                          border-radius:10px;padding:9px 14px;cursor:pointer;background:var(--bg-section);">
                <span class="material-symbols-outlined" style="font-size:18px;color:var(--text-3);">upload</span>
                <span id="bnrName" style="font-size:13px;color:var(--text-2);">Banner seç...</span>
                <input type="file" name="banner" accept="image/*" style="display:none;"
                       onchange="document.getElementById('bnrName').textContent=this.files[0].name; this.form.querySelector('button').click();">
            </label>
            <button type="submit" style="display:none;"></button>
        </form>
        <p style="font-size:11px;color:var(--text-3);margin:6px 0 0;">Önerilen: 1500×500px, max 10MB</p>
    </div>

    <!-- ── PROFİL BİLGİLERİ ───────────────────────────── -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;">
        <h2 style="font-size:14px;font-weight:700;color:var(--text-1);margin:0 0 16px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">contact_mail</span>
            Profil Bilgileri
        </h2>
        <form method="POST" action="<?php echo $redirectTo; ?>" style="display:flex;flex-direction:column;gap:12px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_profile">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:5px;">Kullanıcı Adı</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                           style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);
                                  border-radius:10px;padding:9px 13px;font-size:14px;color:var(--text-1);outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:5px;">Etiket (@tag)</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-weight:800;color:var(--color-primary);pointer-events:none;">@</span>
                        <input type="text" name="tag" value="<?php echo htmlspecialchars($user['tag'] ?? ''); ?>"
                               pattern="[a-zA-Z0-9_]{3,30}" title="3-30 karakter, harf/rakam/alt çizgi"
                               style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);
                                      border-radius:10px;padding:9px 13px 9px 26px;font-size:14px;color:var(--text-1);outline:none;font-family:inherit;"
                               onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
                    </div>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:5px;">E-posta</label>
                <?php if (!empty($user['gta_user_id'])): ?>
                <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" readonly
                       style="width:100%;box-sizing:border-box;background:var(--bg-input);border:1.5px solid var(--border);
                              border-radius:10px;padding:9px 13px;font-size:14px;color:var(--text-3);cursor:not-allowed;font-family:inherit;">
                <p style="font-size:11px;color:var(--text-3);margin:4px 0 0;">GTA karakterleri için e-posta değiştirilemez.</p>
                <?php else: ?>
                <input type="text" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                       style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);
                              border-radius:10px;padding:9px 13px;font-size:14px;color:var(--text-1);outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
                <?php endif; ?>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:5px;">Biyografi</label>
                <textarea name="bio" rows="3" maxlength="<?php echo $maxBio; ?>"
                          style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);
                                 border-radius:10px;padding:9px 13px;font-size:14px;color:var(--text-1);outline:none;
                                 resize:vertical;font-family:inherit;line-height:1.5;"
                          onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                <p style="font-size:11px;color:var(--text-3);margin:4px 0 0;">Maks. <?php echo $maxBio; ?> karakter<?php echo $maxBio===500?' (Premium 💎)':''; ?></p>
            </div>

            <div>
                <button type="submit"
                        style="background:var(--color-primary);color:#fff;border:none;cursor:pointer;
                               padding:10px 22px;border-radius:10px;font-size:14px;font-weight:700;
                               display:inline-flex;align-items:center;gap:7px;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <span class="material-symbols-outlined" style="font-size:17px;font-variation-settings:'FILL' 1;">save</span>
                    Bilgileri Kaydet
                </button>
            </div>
        </form>
    </div>

    <!-- ── BANKA HESABI ───────────────────────────────── -->
    <div style="background:#fff;border:2px solid var(--color-primary);border-radius:16px;padding:20px 24px;
                box-shadow:0 4px 20px rgba(240,109,31,.08);">
        <h2 style="font-size:14px;font-weight:700;color:var(--text-1);margin:0 0 4px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">account_balance</span>
            Banka Hesap Numarası
        </h2>
        <p style="font-size:12px;color:var(--text-3);margin:0 0 12px;">Bakiye çekim işlemlerinin gönderileceği IBAN / hesap numarası.</p>
        <form method="POST" action="<?php echo $redirectTo; ?>" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_bank">
            <input type="text" name="bank_account"
                   value="<?php echo htmlspecialchars($user['bank_account'] ?? ''); ?>"
                   placeholder="TR00 0000 0000 0000 0000 0000 00"
                   required
                   style="flex:1;min-width:180px;background:var(--bg-section);border:1.5px solid var(--border);
                          border-radius:10px;padding:10px 14px;font-size:14px;font-family:monospace;
                          color:var(--text-1);outline:none;letter-spacing:.06em;box-sizing:border-box;"
                   onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
            <button type="submit"
                    style="background:var(--color-primary);color:#fff;border:none;cursor:pointer;
                           padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;
                           display:inline-flex;align-items:center;gap:6px;white-space:nowrap;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1;">save</span>
                Kaydet
            </button>
        </form>
    </div>

    <!-- ── ŞİFRE DEĞİŞTİR ─────────────────────────────── -->
    <?php if (empty($user['gta_user_id'])): ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;">
        <h2 style="font-size:14px;font-weight:700;color:var(--text-1);margin:0 0 16px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">lock</span>
            Şifre Değiştir
        </h2>
        <form method="POST" action="<?php echo $redirectTo; ?>" style="display:flex;flex-direction:column;gap:12px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_password">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:5px;">Mevcut Şifre</label>
                    <input type="password" name="current_password" required
                           style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);
                                  border-radius:10px;padding:9px 13px;font-size:14px;color:var(--text-1);outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:5px;">Yeni Şifre</label>
                    <input type="password" name="new_password" required minlength="6"
                           style="width:100%;box-sizing:border-box;background:var(--bg-section);border:1.5px solid var(--border);
                                  border-radius:10px;padding:9px 13px;font-size:14px;color:var(--text-1);outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
                </div>
            </div>
            <div>
                <button type="submit"
                        style="background:var(--bg-section);color:var(--text-1);border:1.5px solid var(--border);cursor:pointer;
                               padding:9px 20px;border-radius:10px;font-size:14px;font-weight:700;
                               display:inline-flex;align-items:center;gap:6px;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                    <span class="material-symbols-outlined" style="font-size:17px;">update</span>
                    Şifreyi Güncelle
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ── ROZET (Premium) ────────────────────────────── -->
    <?php if (UserModel::isPremiumActive($user)): ?>
    <div style="background:#fff;border:1.5px solid rgba(123,208,255,.4);border-radius:16px;padding:20px 24px;">
        <h2 style="font-size:14px;font-weight:700;color:var(--text-1);margin:0 0 4px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;color:#7bd0ff;">workspace_premium</span>
            Profil Rozeti
            <span style="background:rgba(123,208,255,.2);color:#7bd0ff;font-size:10px;font-weight:800;
                         border-radius:99px;padding:1px 7px;border:1px solid rgba(123,208,255,.35);">PREMIUM</span>
        </h2>
        <p style="font-size:12px;color:var(--text-2);margin:0 0 12px;">Adının yanında görünecek özel rozeti seç.</p>
        <form method="POST" action="<?php echo $redirectTo; ?>" style="display:flex;flex-direction:column;gap:12px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_badge">
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <label>
                    <input type="radio" name="badge" value="" <?php echo empty($user['badge'])?'checked':''; ?> style="display:none;">
                    <div style="padding:9px 14px;border-radius:10px;border:1.5px solid var(--border);background:var(--bg-section);
                                cursor:pointer;font-size:12px;font-weight:700;color:var(--text-3);">Yok</div>
                </label>
                <?php foreach (UserModel::availableBadges() as $bk => $bv): ?>
                <label>
                    <input type="radio" name="badge" value="<?php echo $bk; ?>"
                           <?php echo ($user['badge']??'') === $bk ? 'checked' : ''; ?> style="display:none;">
                    <div style="padding:9px 14px;border-radius:10px;border:1.5px solid var(--border);background:var(--bg-section);
                                cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:3px;min-width:56px;">
                        <span class="material-symbols-outlined" style="font-size:22px;color:<?php echo $bv['color']; ?>;"><?php echo $bv['icon']; ?></span>
                        <span style="font-size:10px;font-weight:700;color:var(--text-2);"><?php echo $bv['label']; ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <div>
                <button type="submit"
                        style="background:rgba(123,208,255,.15);color:#7bd0ff;border:1.5px solid rgba(123,208,255,.4);
                               cursor:pointer;padding:9px 20px;border-radius:10px;font-size:14px;font-weight:700;
                               display:inline-flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">save</span>
                    Rozeti Kaydet
                </button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:20px 24px;">
        <h2 style="font-size:14px;font-weight:700;color:var(--text-2);margin:0 0 6px;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:18px;color:var(--text-3);">workspace_premium</span>
            Profil Rozeti
        </h2>
        <p style="font-size:13px;color:var(--text-3);margin:0 0 10px;">Rozet seçmek için Premium üyelik gerekir.</p>
        <a href="<?php echo BASE_URL; ?>/premium"
           style="display:inline-flex;align-items:center;gap:6px;background:rgba(123,208,255,.15);color:#7bd0ff;
                  border:1.5px solid rgba(123,208,255,.4);padding:8px 16px;border-radius:10px;font-size:13px;
                  font-weight:700;text-decoration:none;">
            <span class="material-symbols-outlined" style="font-size:15px;">diamond</span>
            Premium'a Geç
        </a>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
