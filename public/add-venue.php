<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';

Auth::requireLogin();

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $addr = trim($_POST['address'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $fb = trim($_POST['facebrowser_url'] ?? '');

    if (empty($name)) {
        $error = 'Mekan adı gereklidir.';
    } elseif (strlen($name) > 100) {
        $error = 'Mekan adı en fazla 100 karakter olabilir.';
    } else {
        try {
            $venueModel = new VenueModel();
            $isAdmin = Auth::isAdmin();
            $venueModel->create([
                'name'           => $name,
                'description'    => $desc,
                'address'        => $addr,
                'website'        => $website,
                'category'       => $category,
                'facebrowser_url'=> $fb,
                'status'         => $isAdmin ? 'approved' : 'pending',
                'created_by'     => Auth::id(),
            ]);
            $success = true;
            if ($isAdmin) {
                Auth::setFlash('success', 'Mekan başarıyla eklendi! ✅');
            } else {
                Auth::setFlash('success', 'Mekan önerisi gönderildi! Admin onayından sonra yayınlanacak.');
            }
        } catch (\Throwable $e) {
            $error = 'Mekan eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

$categories = VenueModel::categories();

$pageTitle = 'Mekan Ekle';
$activeNav = 'venues';
require_once __DIR__ . '/partials/app_header.php';
?>

<style>
.av-field { display:flex; flex-direction:column; gap:8px; }
.av-label { font-size:13px; font-weight:700; color:var(--text-2); }
.av-input {
    background:var(--bg-input);
    border:1.5px solid var(--border);
    border-radius:10px;
    padding:11px 14px;
    color:var(--text-1);
    font-size:14px;
    font-family:inherit;
    outline:none;
    transition:border-color .2s;
    width:100%;
    box-sizing:border-box;
}
.av-input:focus { border-color:var(--color-primary); }
.av-input::placeholder { color:var(--text-3); }
</style>

<div style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:680px; width:100%; padding-bottom:40px;">
    <div style="margin-bottom:8px;">
        <a href="<?php echo BASE_URL; ?>/venues"
           style="display:inline-flex; align-items:center; gap:6px; color:var(--text-3); text-decoration:none; font-size:13px; font-weight:600; margin-bottom:12px; transition:color .15s;"
           onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-3)'">
            <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Mekanlar
        </a>
        <h1 style="font-size:1.75rem; font-weight:900; display:flex; align-items:center; gap:10px; color:var(--text-1); margin:0 0 6px;">
            <span class="material-symbols-outlined" style="color:var(--color-primary); font-size:32px; font-variation-settings:'FILL' 1;">add_circle</span>
            Yeni Mekan Öner
        </h1>
        <p style="color:var(--text-3); font-size:14px; margin:0;">Mekan öneriniz admin onayından sonra yayınlanacaktır.</p>
    </div>

    <?php if ($success): ?>
        <div style="background:var(--bg-section); border:1.5px solid rgba(16,185,129,0.25); border-radius:16px; padding:40px 32px; text-align:center; box-shadow:0 4px 20px rgba(16,185,129,0.08);">
            <span class="material-symbols-outlined" style="font-size:64px; display:block; margin-bottom:16px; color:#10b981; font-variation-settings:'FILL' 1;">check_circle</span>
            <p style="font-size:1.2rem; font-weight:700; color:var(--text-1); margin:0 0 20px;">Mekan öneriniz başarıyla gönderildi!</p>
            <a href="<?php echo BASE_URL; ?>/venues"
               style="display:inline-flex; align-items:center; gap:8px; background:var(--color-primary); color:#fff; padding:12px 24px; border-radius:12px; font-weight:700; font-size:14px; text-decoration:none; box-shadow:0 4px 16px rgba(240,109,31,0.25); transition:opacity .15s;"
               onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                <span class="material-symbols-outlined" style="font-size:18px;">store</span> Mekanlara Dön
            </a>
        </div>
    <?php else: ?>
        <div style="background:var(--bg-section); border:1.5px solid var(--border-light); border-radius:16px; padding:28px; box-shadow:0 4px 16px rgba(0,0,0,0.05);">
            <?php if ($error): ?>
            <div style="background:rgba(220,38,38,0.07); border:1.5px solid rgba(220,38,38,0.25); color:#dc2626; padding:12px 16px; border-radius:10px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:600;">
                <span class="material-symbols-outlined" style="flex-shrink:0;">error</span>
                <span><?php echo escape($error); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" style="display:flex; flex-direction:column; gap:18px;">
                <?php echo csrfField(); ?>

                <div class="av-field">
                    <label for="name" class="av-label">Mekan Adı <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo escape($_POST['name'] ?? ''); ?>" required maxlength="100" class="av-input" placeholder="Mekan adını giriniz">
                </div>

                <div class="av-field">
                    <label for="category" class="av-label">Kategori</label>
                    <select name="category" id="category" class="av-input" style="cursor:pointer;">
                        <option value="">Seçiniz</option>
                        <?php foreach ($categories as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($_POST['category'] ?? '') === $key ? 'selected' : ''; ?>><?php echo escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="av-field">
                    <label for="description" class="av-label">Açıklama</label>
                    <textarea name="description" id="description" rows="3" class="av-input" style="resize:vertical; min-height:100px; line-height:1.5;"><?php echo escape($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="av-field">
                    <label for="address" class="av-label">Adres</label>
                    <input type="text" id="address" name="address" value="<?php echo escape($_POST['address'] ?? ''); ?>" placeholder="Los Santos, Vinewood Blvd." class="av-input">
                </div>

                <div class="av-field">
                    <label for="facebrowser_url" class="av-label">Facebrowser URL</label>
                    <input type="url" id="facebrowser_url" name="facebrowser_url" value="<?php echo escape($_POST['facebrowser_url'] ?? ''); ?>" placeholder="https://face.gta.world/pages/..." class="av-input">
                </div>

                <div class="av-field">
                    <label for="website" class="av-label">Website</label>
                    <input type="url" id="website" name="website" value="<?php echo escape($_POST['website'] ?? ''); ?>" placeholder="https://..." class="av-input">
                </div>

                <button type="submit"
                        style="margin-top:8px; background:var(--color-primary); color:#fff; padding:13px 24px; border-radius:12px; font-weight:700; font-size:14px; box-shadow:0 4px 16px rgba(240,109,31,0.25); transition:opacity .15s; display:flex; align-items:center; justify-content:center; gap:8px; border:none; cursor:pointer; font-family:inherit;"
                        onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                    <span class="material-symbols-outlined" style="font-size:18px;">send</span> Mekan Öner
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
