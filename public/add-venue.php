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
        $venueModel = new VenueModel();
        $venueModel->create([
            'name' => $name,
            'description' => $desc,
            'address' => $addr,
            'website' => $website,
            'category' => $category,
            'facebrowser_url' => $fb,
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);
        $success = true;
        Auth::setFlash('success', 'Mekan önerisi gönderildi! Admin onayından sonra yayınlanacak.');
    }
}

$categories = VenueModel::categories();

$pageTitle = 'Mekan Ekle';
$activeNav = 'venues';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <main class="main-feed" style="max-width:640px; margin:0 auto;">
        <div class="page-header">
            <a href="<?php echo BASE_URL; ?>/venues" class="btn-secondary-soft btn-sm" style="margin-bottom:16px;">
                <i class="bi bi-arrow-left"></i> Mekanlar
            </a>
            <h1><i class="bi bi-plus-circle" style="color:var(--primary)"></i> Yeni Mekan Öner</h1>
            <p>Mekan öneriniz admin onayından sonra yayınlanacaktır.</p>
        </div>

        <?php if ($success): ?>
            <div class="card-box empty-state">
                <i class="bi bi-check-circle" style="color:var(--success); opacity:1;"></i>
                <p>Mekan öneriniz başarıyla gönderildi!</p>
                <a href="<?php echo BASE_URL; ?>/venues" class="btn-primary-orange" style="margin-top:16px;">Mekanlara Dön</a>
            </div>
        <?php else: ?>
            <div class="settings-card">
                <?php if ($error): ?>
                    <div class="flash-message flash-error" style="position:static; transform:none; margin-bottom:20px; width:100%;">
                        <div class="flash-content"><i class="bi bi-exclamation-circle-fill"></i><span><?php echo escape($error); ?></span></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?php echo csrfField(); ?>
                    <div class="form-group">
                        <label for="name">Mekan Adı *</label>
                        <input type="text" id="name" name="name" class="form-control-styled" value="<?php echo escape($_POST['name'] ?? ''); ?>" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select name="category" id="category" class="form-control-styled">
                            <option value="">Seçiniz</option>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($_POST['category'] ?? '') === $key ? 'selected' : ''; ?>><?php echo escape($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Açıklama</label>
                        <textarea name="description" id="description" class="form-control-styled" rows="3"><?php echo escape($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="address">Adres</label>
                        <input type="text" id="address" name="address" class="form-control-styled" value="<?php echo escape($_POST['address'] ?? ''); ?>" placeholder="Los Santos, Vinewood Blvd.">
                    </div>
                    <div class="form-group">
                        <label for="facebrowser_url">Facebrowser URL</label>
                        <input type="url" id="facebrowser_url" name="facebrowser_url" class="form-control-styled" value="<?php echo escape($_POST['facebrowser_url'] ?? ''); ?>" placeholder="https://face.gta.world/pages/...">
                    </div>
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" class="form-control-styled" value="<?php echo escape($_POST['website'] ?? ''); ?>" placeholder="https://...">
                    </div>
                    <button type="submit" class="btn-primary-orange btn-full btn-lg">
                        <i class="bi bi-send"></i> Mekan Öner
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
