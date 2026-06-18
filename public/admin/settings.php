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

Auth::requireAccess('settings');
$settingsModel = new SettingsModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
    Csrf::requireValid();
    $action = $_POST['action'] ?? 'settings';

    if ($action === 'change_password') {
        // Admin kendi ÅŸifresini deÄŸiÅŸtiriyor
        $currentPw  = $_POST['current_password'] ?? '';
        $newPw      = $_POST['new_password'] ?? '';
        $confirmPw  = $_POST['confirm_password'] ?? '';

        $db      = Database::getConnection();
        $stmt    = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([Auth::id()]);
        $hash    = $stmt->fetchColumn();

        if (!$hash || !password_verify($currentPw, $hash)) {
            Auth::setFlash('error', 'Mevcut ÅŸifre yanlÄ±ÅŸ.');
        } elseif (strlen($newPw) < 8) {
            Auth::setFlash('error', 'Yeni ÅŸifre en az 8 karakter olmalÄ±.');
        } elseif ($newPw !== $confirmPw) {
            Auth::setFlash('error', 'Yeni ÅŸifre ve onay ÅŸifre eÅŸleÅŸmiyor.');
        } else {
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
               ->execute([password_hash($newPw, PASSWORD_DEFAULT), Auth::id()]);
            Auth::setFlash('success', 'Åifre baÅŸarÄ±yla deÄŸiÅŸtirildi.');
        }
        header('Location: ' . BASE_URL . '/admin/settings'); exit;
    }

    // Site ayarlarÄ± â€” only whitelisted keys
    $allowedKeys = [
        'site_name', 'site_description', 'site_email',
        'checkin_cooldown', 'checkin_rate_limit', 'checkin_rate_window',
        'login_max_attempts', 'login_window_seconds',
        'maintenance_mode',
    ];
    foreach ($allowedKeys as $key) {
        if (isset($_POST[$key])) {
            $settingsModel->set($key, $_POST[$key]);
        }
    }
    Auth::setFlash('success', 'Ayarlar kaydedildi.');
    header('Location: ' . BASE_URL . '/admin/settings'); exit;
}

$settings = $settingsModel->getAll();
$pendingVenues = (new VenueModel())->getPendingCount();

$pageTitle = 'Site AyarlarÄ±';
$adminPage = 'settings';
require_once __DIR__ . '/_header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">settings</span> Site AyarlarÄ±
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
                <label class="block text-label-md text-slate-400 mb-1">Site AdÄ±</label>
                <input type="text" name="site_name" value="<?php echo escape($settings['site_name'] ?? 'Sociaera'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Site AÃ§Ä±klamasÄ±</label>
                <input type="text" name="site_description" value="<?php echo escape($settings['site_description'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Ä°letiÅŸim E-posta</label>
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
                <p class="text-xs text-slate-500 mt-1">AynÄ± mekana tekrar check-in sÃ¼resi</p>
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Rate Limit (adet)</label>
                <input type="number" name="checkin_rate_limit" value="<?php echo escape($settings['checkin_rate_limit'] ?? '10'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                <p class="text-xs text-slate-500 mt-1">Pencere sÃ¼resindeki max check-in</p>
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Rate Penceresi (saniye)</label>
                <input type="number" name="checkin_rate_window" value="<?php echo escape($settings['checkin_rate_window'] ?? '3600'); ?>" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>
        </div>
    </div>

    <!-- GÃ¼venlik -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-[20px]">security</span> GÃ¼venlik
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

    <!-- BakÄ±m Modu -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
        <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-[20px]">construction</span> BakÄ±m Modu
        </h2>
        <div>
            <label class="block text-label-md text-slate-400 mb-1">BakÄ±m Modu</label>
            <select name="maintenance_mode" class="w-full md:w-64 bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                <option value="0" class="bg-background" <?php echo ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>KapalÄ±</option>
                <option value="1" class="bg-background" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>AÃ§Ä±k</option>
            </select>
        </div>
    </div>

    <button type="submit" class="bg-primary-container text-white px-8 py-3 rounded-xl text-label-md font-semibold hover:bg-primary-container/90 transition-colors shadow-[0_0_15px_rgba(255,107,53,0.3)] flex items-center gap-2">
        <span class="material-symbols-outlined text-[20px]">save</span> AyarlarÄ± Kaydet
    </button>
</form>

<!-- Åifre DeÄŸiÅŸtir -->
<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-amber-500/20 rounded-xl p-6 shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] mt-6">
    <h2 class="text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-amber-400 text-[20px]">lock_reset</span> Åifre DeÄŸiÅŸtir
    </h2>
    <form method="POST" class="space-y-4">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="change_password">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Mevcut Åifre</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400/40 transition-colors">
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Yeni Åifre</label>
                <input type="password" name="new_password" required minlength="8" autocomplete="new-password"
                       class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400/40 transition-colors">
                <p class="text-xs text-slate-500 mt-1">En az 8 karakter</p>
            </div>
            <div>
                <label class="block text-label-md text-slate-400 mb-1">Yeni Åifre Onay</label>
                <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"
                       class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400/40 transition-colors">
            </div>
        </div>
        <button type="submit" class="bg-amber-500/20 text-amber-400 border border-amber-500/30 hover:bg-amber-500/30 px-6 py-2.5 rounded-xl text-label-md font-semibold transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">lock_reset</span> Åifreyi DeÄŸiÅŸtir
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
