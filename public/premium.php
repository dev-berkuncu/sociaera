<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Wallet.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$userModel = new UserModel();
$walletModel = new WalletModel();
$walletModel->ensureWallet(Auth::id());

$user = $userModel->getById(Auth::id());
$balance = $walletModel->getBalance(Auth::id());
$premiumPrice = 10000;
$isPremium = !empty($user['is_premium']);

// Satın alma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isPremium) {
    Csrf::requireValid();

    if ($balance < $premiumPrice) {
        Auth::setFlash('error', 'Yetersiz bakiye. Premium için $' . number_format($premiumPrice, 0, ',', '.') . ' gerekiyor.');
    } else {
        $walletModel->withdraw(Auth::id(), $premiumPrice, 'Sociaera Premium satın alındı');
        $userModel->setPremium(Auth::id(), true);
        $userModel->updateBadge(Auth::id(), 'diamond'); // Varsayılan rozet
        Auth::setFlash('success', 'Tebrikler! Premium üye oldunuz! 🎉');
        $isPremium = true;
        $user = $userModel->getById(Auth::id());
        $balance = $walletModel->getBalance(Auth::id());
    }
    header('Location: ' . BASE_URL . '/premium'); exit;
}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Premium';
$activeNav = 'premium';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-xl w-full mx-auto mt-4">

    <?php if ($isPremium): ?>
    <!-- Zaten Premium -->
    <div class="bg-gradient-to-br from-[#1E293B]/80 to-surface-container border border-[#7bd0ff]/30 rounded-2xl p-8 md:p-12 text-center shadow-[0_20px_40px_-15px_rgba(123,208,255,0.2)] relative overflow-hidden">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 text-[#7bd0ff] leading-none select-none">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-[#7bd0ff] to-[#00a5de] rounded-2xl flex items-center justify-center text-white mb-6 shadow-[0_10px_25px_-5px_rgba(123,208,255,0.5)] transform -rotate-6">
                <span class="material-symbols-outlined text-[40px]">diamond</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-on-surface mb-2">Premium Üye</h1>
            <p class="text-[#7bd0ff] text-lg mb-6 font-medium">Tüm premium ayrıcalıkların aktif! ✨</p>

            <div class="bg-white/5 border border-white/10 rounded-xl p-6 text-left space-y-4 mb-6">
                <div class="flex items-center gap-3 text-emerald-400">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="text-on-surface">Reklamsız deneyim</span>
                    <span class="ml-auto text-xs text-emerald-400 font-bold">AKTİF</span>
                </div>
                <div class="flex items-center gap-3 text-emerald-400">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="text-on-surface">Profil rozeti</span>
                    <span class="ml-auto text-xs text-emerald-400 font-bold">AKTİF</span>
                </div>
                <div class="flex items-center gap-3 text-emerald-400">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="text-on-surface">Yüksek yükleme limiti (20MB)</span>
                    <span class="ml-auto text-xs text-emerald-400 font-bold">AKTİF</span>
                </div>
                <div class="flex items-center gap-3 text-emerald-400">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="text-on-surface">Öncelikli destek</span>
                    <span class="ml-auto text-xs text-emerald-400 font-bold">AKTİF</span>
                </div>
                <div class="flex items-center gap-3 text-emerald-400">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="text-on-surface">Özel profil temaları</span>
                    <span class="ml-auto text-xs text-emerald-400 font-bold">AKTİF</span>
                </div>
                <div class="flex items-center gap-3 text-emerald-400">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="text-on-surface">Sıralama tablosunda öne çıkma</span>
                    <span class="ml-auto text-xs text-emerald-400 font-bold">AKTİF</span>
                </div>
            </div>

            <a href="<?php echo BASE_URL; ?>/settings" class="inline-flex items-center gap-2 bg-[#7bd0ff]/20 text-[#7bd0ff] px-6 py-3 rounded-xl font-bold border border-[#7bd0ff]/30 hover:bg-[#7bd0ff]/30 transition-colors">
                <span class="material-symbols-outlined">tune</span> Rozet Ayarları
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- Satın Alma -->
    <div class="bg-gradient-to-br from-[#1E293B]/80 to-surface-container border border-primary-container/20 rounded-2xl p-8 md:p-12 text-center shadow-[0_20px_40px_-15px_rgba(255,107,53,0.2)] relative overflow-hidden">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 text-primary-container leading-none select-none">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-container to-[#ff9e7d] rounded-2xl flex items-center justify-center text-white mb-6 shadow-[0_10px_25px_-5px_rgba(255,107,53,0.5)] transform -rotate-6">
                <span class="material-symbols-outlined text-[40px]">diamond</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-on-surface mb-2"><?php echo APP_NAME; ?> Premium</h1>
            <p class="text-primary-fixed-dim text-lg mb-2 font-medium">Deneyimini bir üst seviyeye taşı</p>

            <!-- Fiyat -->
            <div class="my-6">
                <span class="text-5xl font-black text-on-surface">$<?php echo number_format($premiumPrice, 0, ',', '.'); ?></span>
                <span class="text-slate-400 text-lg ml-1">/ ömür boyu</span>
            </div>

            <ul class="flex flex-col gap-4 text-left max-w-sm mx-auto mb-8">
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span>
                    <span>Reklamsız deneyim</span>
                </li>
                <li class="flex items-center justify-between text-on-surface text-lg">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary-container">check_circle</span>
                        <span>Profil rozeti seçimi</span>
                    </div>
                    <span class="bg-[#7bd0ff]/20 text-[#7bd0ff] text-xs font-bold px-2 py-1 rounded border border-[#7bd0ff]/30 uppercase tracking-wider flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">diamond</span> Premium</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span>
                    <span>Yüksek yükleme limiti (20MB)</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span>
                    <span>Öncelikli destek</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span>
                    <span>Özel profil temaları</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span>
                    <span>Sıralama tablosunda öne çıkma</span>
                </li>
            </ul>

            <!-- Bakiye & Satın Al -->
            <div class="bg-white/5 border border-white/10 rounded-xl p-4 mb-4 flex items-center justify-between">
                <span class="text-slate-400 text-sm">Cüzdan Bakiyen</span>
                <span class="font-black text-xl <?php echo $balance >= $premiumPrice ? 'text-emerald-400' : 'text-red-400'; ?>">$<?php echo number_format($balance, 2, ',', '.'); ?></span>
            </div>

            <?php if ($balance >= $premiumPrice): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice, 0, ',', '.'); ?> cüzdanınızdan çekilecek. Onaylıyor musunuz?')" class="w-full bg-gradient-to-r from-primary-container to-[#ff9e7d] text-white py-4 rounded-xl font-black text-lg shadow-[0_10px_25px_-5px_rgba(255,107,53,0.4)] hover:shadow-[0_15px_30px_-5px_rgba(255,107,53,0.5)] hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">diamond</span> Premium'a Geç
                </button>
            </form>
            <?php else: ?>
            <div class="space-y-3">
                <button disabled class="w-full bg-white/5 border border-white/10 text-slate-400 py-4 rounded-xl font-bold flex items-center justify-center gap-2 cursor-not-allowed">
                    <span class="material-symbols-outlined">account_balance_wallet</span> Yetersiz Bakiye
                </button>
                <a href="<?php echo BASE_URL; ?>/wallet" class="block text-center text-primary-container font-bold text-sm hover:underline">Cüzdana git ve bakiye yükle →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
