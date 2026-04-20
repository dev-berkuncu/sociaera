<?php
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';
require_once __DIR__ . '/../../app/Models/Settings.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

Auth::requireAdmin();

$settingsModel = new SettingsModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token') continue;
        $settingsModel->set($key, $value);
    }
    Logger::adminAudit('update', 'settings', null, 'Site ayarları güncellendi');
    Auth::setFlash('success', 'Ayarlar kaydedildi.');
    header('Location: ' . BASE_URL . '/admin/settings'); exit;
}

$settings = $settingsModel->getAll();
$pendingVenues = (new VenueModel())->getPendingCount();

$pageTitle = 'Site Ayarları';
$adminPage = 'settings';
require_once __DIR__ . '/../../public/partials/header.php';
require_once __DIR__ . '/../../public/partials/navbar.php';
require_once __DIR__ . '/../../public/partials/flash.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <h1 style="font-size:1.3rem; font-weight:800; margin-bottom:20px;"><i class="bi bi-gear" style="color:var(--primary)"></i> Site Ayarları</h1>

        <form method="POST">
            <?php echo csrfField(); ?>

            <div class="settings-card">
                <h2>Genel</h2>
                <div class="form-group"><label>Site Adı</label><input type="text" name="site_name" class="form-control-styled" value="<?php echo escape($settings['site_name'] ?? 'Sociaera'); ?>"></div>
                <div class="form-group"><label>Site Açıklaması</label><input type="text" name="site_description" class="form-control-styled" value="<?php echo escape($settings['site_description'] ?? ''); ?>"></div>
                <div class="form-group"><label>İletişim E-posta</label><input type="email" name="site_email" class="form-control-styled" value="<?php echo escape($settings['site_email'] ?? ''); ?>"></div>
            </div>

            <div class="settings-card">
                <h2>Check-in Limitleri</h2>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group"><label>Cooldown (saniye)</label><input type="number" name="checkin_cooldown" class="form-control-styled" value="<?php echo escape($settings['checkin_cooldown'] ?? '300'); ?>"><div class="form-hint">Aynı mekana tekrar check-in süresi</div></div>
                    <div class="form-group"><label>Rate Limit (adet)</label><input type="number" name="checkin_rate_limit" class="form-control-styled" value="<?php echo escape($settings['checkin_rate_limit'] ?? '10'); ?>"><div class="form-hint">Pencere süresindeki max check-in</div></div>
                    <div class="form-group"><label>Rate Penceresi (saniye)</label><input type="number" name="checkin_rate_window" class="form-control-styled" value="<?php echo escape($settings['checkin_rate_window'] ?? '3600'); ?>"></div>
                </div>
            </div>

            <div class="settings-card">
                <h2>Güvenlik</h2>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group"><label>Login Max Deneme</label><input type="number" name="login_max_attempts" class="form-control-styled" value="<?php echo escape($settings['login_max_attempts'] ?? '8'); ?>"></div>
                    <div class="form-group"><label>Login Penceresi (saniye)</label><input type="number" name="login_window_seconds" class="form-control-styled" value="<?php echo escape($settings['login_window_seconds'] ?? '600'); ?>"></div>
                </div>
            </div>

            <div class="settings-card">
                <h2>Bakım Modu</h2>
                <div class="form-group">
                    <label>Bakım Modu</label>
                    <select name="maintenance_mode" class="form-control-styled">
                        <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Kapalı</option>
                        <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Açık</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-primary-orange btn-lg"><i class="bi bi-check-lg"></i> Ayarları Kaydet</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../public/partials/footer.php'; ?>
