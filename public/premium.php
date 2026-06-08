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

Auth::requireLogin();

$userModel = new UserModel();
$walletModel = new WalletModel();
$walletModel->ensureWallet(Auth::id());

$user = $userModel->getById(Auth::id());
$balance = $walletModel->getBalance(Auth::id());
$premiumPrice = 10000;
$isPremiumActive = UserModel::isPremiumActive($user);
$hadPremium = !empty($user['is_premium']); // Daha önce premium aldı mı (süresi dolmuş olabilir)

// Satın alma / Yenileme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    if ($balance < $premiumPrice) {
        Auth::setFlash('error', 'Yetersiz bakiye. Premium için $' . number_format($premiumPrice, 0, ',', '.') . ' gerekiyor.');
    } else {
        if ($isPremiumActive) {
            // Süre uzat
            $walletModel->withdraw(Auth::id(), $premiumPrice, 'Sociaera Premium yenilendi (+7 gün)');
            $userModel->renewPremium(Auth::id(), 7);
            Auth::setFlash('success', 'Premium süreniz 7 gün uzatıldı! 🎉');
        } else {
            // İlk kez veya süresi dolmuş — yeni satın alma
            $walletModel->withdraw(Auth::id(), $premiumPrice, 'Sociaera Premium satın alındı (7 gün)');
            $userModel->setPremium(Auth::id(), 7);
            if (!$hadPremium) {
                $userModel->updateBadge(Auth::id(), 'diamond');
            }
            Auth::setFlash('success', 'Tebrikler! 7 günlük Premium aktif! 🎉');
        }
        $isPremiumActive = true;
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

    <?php if ($isPremiumActive): ?>
    <!-- Aktif Premium -->
    <div class="bg-gradient-to-br from-[#2a2a2b]/80 to-surface-container border border-[#7bd0ff]/30 rounded-2xl p-8 md:p-12 text-center shadow-[0_20px_40px_-15px_rgba(123,208,255,0.2)] relative overflow-hidden">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 text-[#7bd0ff] leading-none select-none">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-[#7bd0ff] to-[#00a5de] rounded-2xl flex items-center justify-center text-white mb-6 shadow-[0_10px_25px_-5px_rgba(123,208,255,0.5)] transform -rotate-6">
                <span class="material-symbols-outlined text-[40px]">diamond</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-on-surface mb-2">Premium Üye</h1>
            <p class="text-[#7bd0ff] text-lg mb-2 font-medium">Tüm premium ayrıcalıkların aktif! ✨</p>

            <!-- Kalan Süre -->
            <div class="bg-white/5 border border-[#7bd0ff]/20 rounded-xl p-4 mb-6 inline-flex items-center gap-3">
                <span class="material-symbols-outlined text-[#7bd0ff]">timer</span>
                <div class="text-left">
                    <div class="text-xs text-slate-400 uppercase tracking-wider font-bold">Kalan Süre</div>
                    <div class="text-lg font-black text-[#7bd0ff]"><?php echo UserModel::premiumRemainingText($user); ?></div>
                </div>
                <?php if (!empty($user['premium_until'])): ?>
                <div class="text-left ml-3 pl-3 border-l border-white/10">
                    <div class="text-xs text-slate-400 uppercase tracking-wider font-bold">Bitiş</div>
                    <div class="text-sm font-semibold text-slate-300"><?php echo date('d.m.Y H:i', strtotime($user['premium_until'])); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-xl p-6 text-left space-y-3 mb-6">
                <?php
                $premiumFeatures = [
                    ['icon' => 'block', 'text' => 'Reklamsız deneyim'],
                    ['icon' => 'badge', 'text' => 'Profil rozeti seçimi'],
                    ['icon' => 'palette', 'text' => 'Özel profil temaları (6 tema)'],
                    ['icon' => 'upload_file', 'text' => 'Yüksek yükleme limiti (20MB)'],
                    ['icon' => 'timer', 'text' => 'Yarı cooldown & 2.5x rate limit'],
                    ['icon' => 'paid', 'text' => '2x check-in cüzdan ödülü'],
                    ['icon' => 'text_fields', 'text' => 'Uzun bio (500 karakter)'],
                    ['icon' => 'early_on', 'text' => 'Kampanyalara erken erişim (24s)'],
                    ['icon' => 'star', 'text' => 'Mekan favorileri'],
                    ['icon' => 'analytics', 'text' => 'Detaylı istatistikler & trend'],
                    ['icon' => 'visibility', 'text' => 'Profilime kim baktı'],
                    ['icon' => 'military_tech', 'text' => 'Premium-only rozetler (4 rozet)'],
                    ['icon' => 'leaderboard', 'text' => 'Sıralama tablosunda öne çıkma'],
                ];
                foreach ($premiumFeatures as $pf):
                ?>
                <div class="flex items-center gap-3 text-emerald-400">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    <span class="text-on-surface text-sm"><?php echo $pf['text']; ?></span>
                    <span class="ml-auto text-[10px] text-emerald-400 font-bold">AKTİF</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?php echo BASE_URL; ?>/settings" class="inline-flex items-center justify-center gap-2 bg-[#7bd0ff]/20 text-[#7bd0ff] px-6 py-3 rounded-xl font-bold border border-[#7bd0ff]/30 hover:bg-[#7bd0ff]/30 transition-colors">
                    <span class="material-symbols-outlined">tune</span> Rozet Ayarları
                </a>
                <?php if ($balance >= $premiumPrice): ?>
                <form method="POST" class="inline">
                    <?php echo csrfField(); ?>
                    <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice, 0, ',', '.'); ?> karşılığında 7 gün daha eklenmesini onaylıyor musunuz?')" class="inline-flex items-center justify-center gap-2 bg-emerald-500/20 text-emerald-400 px-6 py-3 rounded-xl font-bold border border-emerald-500/30 hover:bg-emerald-500/30 transition-colors">
                        <span class="material-symbols-outlined">add_circle</span> Süre Uzat (+7 gün)
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php elseif ($hadPremium): ?>
    <!-- Süresi Dolmuş Premium -->
    <div class="bg-gradient-to-br from-[#2a2a2b]/80 to-surface-container border border-amber-500/20 rounded-2xl p-8 md:p-12 text-center shadow-[0_20px_40px_-15px_rgba(245,158,11,0.15)] relative overflow-hidden">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 text-amber-500 leading-none select-none">
            <span class="material-symbols-outlined" style="font-size:inherit;">timer_off</span>
        </div>
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center text-white mb-6 shadow-[0_10px_25px_-5px_rgba(245,158,11,0.5)] transform -rotate-6">
                <span class="material-symbols-outlined text-[40px]">timer_off</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-on-surface mb-2">Premium Süresi Doldu</h1>
            <p class="text-amber-400 text-lg mb-6 font-medium">Premium ayrıcalıkların pasif durumda</p>

            <div class="bg-white/5 border border-white/10 rounded-xl p-6 text-left space-y-3 mb-8">
                <?php
                $expiredFeatures = ['Reklamsız deneyim','Profil rozeti seçimi','Özel profil temaları','Yüksek yükleme limiti (20MB)','Yarı cooldown & 2.5x rate limit','2x check-in ödülü','Uzun bio','Kampanya erken erişim','Mekan favorileri','Detaylı istatistikler','Profilime kim baktı','Premium rozetler','Sıralama öne çıkma'];
                foreach ($expiredFeatures as $ef):
                ?>
                <div class="flex items-center gap-3 text-red-400">
                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                    <span class="text-slate-400 line-through text-sm"><?php echo $ef; ?></span>
                    <span class="ml-auto text-[10px] text-red-400 font-bold">PASİF</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="my-6">
                <span class="text-4xl font-black text-on-surface">$<?php echo number_format($premiumPrice, 0, ',', '.'); ?></span>
                <span class="text-slate-400 text-lg ml-1">/ 7 gün</span>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-xl p-4 mb-4 flex items-center justify-between">
                <span class="text-slate-400 text-sm">Cüzdan Bakiyen</span>
                <span class="font-black text-xl <?php echo $balance >= $premiumPrice ? 'text-emerald-400' : 'text-red-400'; ?>">$<?php echo number_format($balance, 2, ',', '.'); ?></span>
            </div>

            <?php if ($balance >= $premiumPrice): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice, 0, ',', '.'); ?> ile 7 günlük Premium\'u yeniden aktifleştirmek istiyor musunuz?')" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white py-4 rounded-xl font-black text-lg shadow-[0_10px_25px_-5px_rgba(245,158,11,0.4)] hover:shadow-[0_15px_30px_-5px_rgba(245,158,11,0.5)] hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">restart_alt</span> Premium'u Yenile
                </button>
            </form>
            <?php else: ?>
            <div class="space-y-3">
                <button disabled class="w-full bg-white/5 border border-white/10 text-slate-400 py-4 rounded-xl font-bold flex items-center justify-center gap-2 cursor-not-allowed">
                    <span class="material-symbols-outlined">account_balance_wallet</span> Yetersiz Bakiye
                </button>
                <a href="<?php echo BASE_URL; ?>/wallet" class="block text-center text-amber-400 font-bold text-sm hover:underline">Cüzdana git ve bakiye yükle →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- İlk Kez Satın Alma -->
    <div class="bg-gradient-to-br from-[#2a2a2b]/80 to-surface-container border border-primary-container/20 rounded-2xl p-8 md:p-12 text-center shadow-[0_20px_40px_-15px_rgba(255,145,0,0.2)] relative overflow-hidden">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 text-primary-container leading-none select-none">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-container to-[#ff9e7d] rounded-2xl flex items-center justify-center text-white mb-6 shadow-[0_10px_25px_-5px_rgba(255,145,0,0.5)] transform -rotate-6">
                <span class="material-symbols-outlined text-[40px]">diamond</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-on-surface mb-2"><?php echo APP_NAME; ?> Premium</h1>
            <p class="text-primary-fixed-dim text-lg mb-2 font-medium">Deneyimini bir üst seviyeye taşı</p>

            <div class="my-6">
                <span class="text-5xl font-black text-on-surface">$<?php echo number_format($premiumPrice, 0, ',', '.'); ?></span>
                <span class="text-slate-400 text-lg ml-1">/ 7 gün</span>
            </div>

            <ul class="flex flex-col gap-3 text-left max-w-md mx-auto mb-8">
                <?php
                $newFeatures = [
                    ['icon' => 'block', 'text' => 'Reklamsız deneyim'],
                    ['icon' => 'badge', 'text' => 'Profil rozeti seçimi'],
                    ['icon' => 'palette', 'text' => '6 özel profil teması'],
                    ['icon' => 'upload_file', 'text' => '2x yükleme limiti (20MB)'],
                    ['icon' => 'timer', 'text' => '½ cooldown & 2.5x rate limit'],
                    ['icon' => 'paid', 'text' => '2x check-in cüzdan ödülü ($20)'],
                    ['icon' => 'text_fields', 'text' => 'Uzun bio (500 karakter)'],
                    ['icon' => 'early_on', 'text' => 'Kampanyalara 24s erken erişim'],
                    ['icon' => 'star', 'text' => 'Mekan favorileri sistemi'],
                    ['icon' => 'analytics', 'text' => 'Detaylı istatistik & trend grafik'],
                    ['icon' => 'visibility', 'text' => 'Profilime kim baktı'],
                    ['icon' => 'military_tech', 'text' => '4 özel premium rozet'],
                    ['icon' => 'leaderboard', 'text' => 'Sıralama tablosunda öne çıkma'],
                ];
                foreach ($newFeatures as $nf):
                ?>
                <li class="flex items-center gap-3 text-on-surface">
                    <span class="material-symbols-outlined text-primary-container text-[20px]"><?php echo $nf['icon']; ?></span>
                    <span class="text-sm"><?php echo $nf['text']; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="bg-white/5 border border-white/10 rounded-xl p-4 mb-4 flex items-center justify-between">
                <span class="text-slate-400 text-sm">Cüzdan Bakiyen</span>
                <span class="font-black text-xl <?php echo $balance >= $premiumPrice ? 'text-emerald-400' : 'text-red-400'; ?>">$<?php echo number_format($balance, 2, ',', '.'); ?></span>
            </div>

            <?php if ($balance >= $premiumPrice): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice, 0, ',', '.'); ?> cüzdanınızdan çekilecek. 7 günlük Premium başlayacak. Onaylıyor musunuz?')" class="w-full bg-gradient-to-r from-primary-container to-[#ff9e7d] text-white py-4 rounded-xl font-black text-lg shadow-[0_10px_25px_-5px_rgba(255,145,0,0.4)] hover:shadow-[0_15px_30px_-5px_rgba(255,145,0,0.5)] hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">diamond</span> Premium'a Geç (7 Gün)
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
