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
    }
}

$pageTitle = 'Ayarlar';
$activeNav = 'settings';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <main class="main-feed" style="max-width:640px; margin:0 auto;">
        <div class="page-header">
            <h1><i class="bi bi-gear" style="color:var(--primary)"></i> Ayarlar</h1>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error" style="position:static; transform:none; margin-bottom:16px; width:100%;"><div class="flash-content"><i class="bi bi-exclamation-circle-fill"></i><span><?php echo escape($error); ?></span></div></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="flash-message flash-success" style="position:static; transform:none; margin-bottom:16px; width:100%;"><div class="flash-content"><i class="bi bi-check-circle-fill"></i><span><?php echo escape($success); ?></span></div></div>
        <?php endif; ?>

        <!-- Avatar -->
        <div class="settings-card">
            <h2><i class="bi bi-person-circle"></i> Avatar</h2>
            <div style="display:flex; align-items:center; gap:16px; margin-bottom:16px;">
                <?php echo avatarHtml($user['avatar'], $user['username'], '64'); ?>
                <form method="POST" enctype="multipart/form-data" style="flex:1;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_avatar">
                    <input type="file" name="avatar" accept="image/*" required class="form-control-styled" style="font-size:0.85rem;">
                    <div class="form-hint">Maks. 10MB, JPEG / PNG / WebP</div>
                    <button type="submit" class="btn-primary-orange btn-sm" style="margin-top:8px;">Yükle</button>
                </form>
            </div>
        </div>

        <!-- Banner -->
        <div class="settings-card">
            <h2><i class="bi bi-image"></i> Banner</h2>
            <div style="border-radius:var(--radius-md); height:120px; margin-bottom:12px; background:var(--primary-gradient); background-size:cover; background-position:center; <?php if (bannerUrl($user['banner'])): ?>background-image:url('<?php echo bannerUrl($user['banner']); ?>')<?php endif; ?>"></div>
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_banner">
                <input type="file" name="banner" accept="image/*" required class="form-control-styled" style="font-size:0.85rem;">
                <div class="form-hint">Maks. 10MB, önerilen 1500x500px</div>
                <button type="submit" class="btn-primary-orange btn-sm" style="margin-top:8px;">Yükle</button>
            </form>
        </div>

        <!-- Profil Bilgileri -->
        <div class="settings-card">
            <h2><i class="bi bi-person-lines-fill"></i> Profil Bilgileri</h2>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control-styled" value="<?php echo escape($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Etiket (@tag)</label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">@</span>
                        <input type="text" name="tag" class="form-control-styled" value="<?php echo escape($user['tag'] ?? ''); ?>" pattern="[a-zA-Z0-9_]{3,30}">
                    </div>
                </div>
                <div class="form-group">
                    <label>E-posta</label>
                    <input type="email" name="email" class="form-control-styled" value="<?php echo escape($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Biyografi</label>
                    <textarea name="bio" class="form-control-styled" rows="3" maxlength="280"><?php echo escape($user['bio'] ?? ''); ?></textarea>
                    <div class="form-hint">Maks 280 karakter</div>
                </div>
                <button type="submit" class="btn-primary-orange">Kaydet</button>
            </form>
        </div>

        <!-- Şifre -->
        <div class="settings-card">
            <h2><i class="bi bi-lock"></i> Şifre Değiştir</h2>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Mevcut Şifre</label>
                    <input type="password" name="current_password" class="form-control-styled" required>
                </div>
                <div class="form-group">
                    <label>Yeni Şifre</label>
                    <input type="password" name="new_password" class="form-control-styled" required minlength="6">
                </div>
                <button type="submit" class="btn-primary-orange">Şifreyi Değiştir</button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
