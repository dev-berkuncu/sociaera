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
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">settings</span> Site Ayarları
    </h1>
</div>

<form method="POST" class="space-y-6">
    <?php echo csrfField(); ?>

    <!-- Genel -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-[20px]">tune</span> Genel
        </h2>
        <div class="space-y-4">
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Site Adı</label>
                <input type="text" name="site_name" value="<?php echo escape($settings['site_name'] ?? 'Sociaera'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Site Açıklaması</label>
                <input type="text" name="site_description" value="<?php echo escape($settings['site_description'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">İletişim E-posta</label>
                <input type="email" name="site_email" value="<?php echo escape($settings['site_email'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
        </div>
    </div>

    <!-- Check-in Limitleri -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-[20px]">timer</span> Check-in Limitleri
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Cooldown (saniye)</label>
                <input type="number" name="checkin_cooldown" value="<?php echo escape($settings['checkin_cooldown'] ?? '300'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                <p class="text-xs text-slate-500 mt-1">Aynı mekana tekrar check-in süresi</p>
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Rate Limit (adet)</label>
                <input type="number" name="checkin_rate_limit" value="<?php echo escape($settings['checkin_rate_limit'] ?? '10'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                <p class="text-xs text-slate-500 mt-1">Pencere süresindeki max check-in</p>
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Rate Penceresi (saniye)</label>
                <input type="number" name="checkin_rate_window" value="<?php echo escape($settings['checkin_rate_window'] ?? '3600'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
        </div>
    </div>

    <!-- Güvenlik -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-[20px]">security</span> Güvenlik
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Login Max Deneme</label>
                <input type="number" name="login_max_attempts" value="<?php echo escape($settings['login_max_attempts'] ?? '8'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Login Penceresi (saniye)</label>
                <input type="number" name="login_window_seconds" value="<?php echo escape($settings['login_window_seconds'] ?? '600'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
        </div>
    </div>

    <!-- Bakım Modu -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-[20px]">construction</span> Bakım Modu
        </h2>
        <div>
            <label class="block text-label-md text-slate-400 mb-1">Bakım Modu</label>
            <select name="maintenance_mode" class="w-full md:w-64 bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                <option value="0" class="bg-background" <?php echo ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Kapalı</option>
                <option value="1" class="bg-background" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Açık</option>
            </select>
        </div>
    </div>

    <button type="submit" class="bg-primary-container text-white px-8 py-3 rounded-xl text-label-md font-semibold hover:bg-primary-container/90 transition-colors shadow-[0_0_15px_rgba(255,107,53,0.3)] flex items-center gap-2">
        <span class="material-symbols-outlined text-[20px]">save</span> Ayarları Kaydet
    </button>
</form>

<?php require_once __DIR__ . '/_footer.php'; ?>
