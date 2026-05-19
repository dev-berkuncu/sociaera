<?php
/**
 * Admin Login — Kullanıcı adı + şifre ile giriş
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Leaderboard.php';
require_once __DIR__ . '/../../app/Models/Ad.php';
require_once __DIR__ . '/../../app/Models/Settings.php';

// Zaten admin girişi varsa yönlendir
if (Auth::isAdmin()) {
    header('Location: ' . BASE_URL . '/admin');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gerekli.';
    } else {
        $userModel = new UserModel();
        $result = $userModel->login($username, $password);

        if (!$result['ok']) {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        } elseif (empty($result['user']['is_admin'])) {
            $error = 'Bu hesabın admin yetkisi yok.';
        } else {
            Auth::login($result['user']);
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Girişi — Sociaera</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;900&family=Inter:wght@400;500&display=swap" rel="stylesheet"/>
<style>
    body { font-family: 'Inter', sans-serif; background: #0b1326; }
    h1, h2 { font-family: 'Manrope', sans-serif; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-[#FF6B35]/15 border border-[#FF6B35]/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-[#FF6B35]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <span class="text-2xl font-black text-white">Sociaera</span>
        </div>
        <p class="text-slate-400 text-sm">Admin Paneli</p>
    </div>

    <!-- Form -->
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-2xl p-8 backdrop-blur-xl shadow-2xl">
        <h1 class="text-xl font-black text-white mb-6 text-center">Yönetici Girişi</h1>

        <?php if ($error): ?>
        <div class="mb-4 px-4 py-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1.5">Kullanıcı Adı</label>
                <input type="text" name="username" required autofocus
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       class="w-full bg-white/5 border border-white/10 text-white rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-[#FF6B35]/40 transition-colors placeholder-slate-600"
                       placeholder="admin kullanıcı adı"/>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1.5">Şifre</label>
                <input type="password" name="password" required
                       class="w-full bg-white/5 border border-white/10 text-white rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-[#FF6B35]/40 transition-colors placeholder-slate-600"
                       placeholder="••••••••"/>
            </div>
            <button type="submit"
                    class="w-full bg-[#FF6B35] hover:bg-[#e55a28] text-white py-3 rounded-xl text-sm font-bold transition-colors mt-2 shadow-[0_0_20px_rgba(255,107,53,0.3)]">
                Giriş Yap
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-slate-600 mt-6">
        <a href="<?php echo BASE_URL; ?>/dashboard" class="hover:text-slate-400 transition-colors">← Siteye dön</a>
    </p>
</div>

</body>
</html>
