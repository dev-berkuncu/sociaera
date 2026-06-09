<?php
/**
 * Sociaera — Premium Satın Alma Sayfası
 */
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

$userModel   = new UserModel();
$walletModel = new WalletModel();

$user = $userModel->getById(Auth::id());
if (!$user) { Auth::logout(); header('Location: ' . BASE_URL . '/login'); exit; }

$balance = $walletModel->getBalance(Auth::id());
$premiumPrice = 20.00; // 7 günlük fiyat

$isPremiumActive = UserModel::isPremiumActive($user);
$hadPremium      = !empty($user['premium_until']);

// POST — Premium Satın Al
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    
    if ($isPremiumActive) {
        // Süre uzat
        if ($balance < $premiumPrice) {
            Auth::setFlash('error', 'Cüzdanınızda yeterli bakiye bulunmuyor.');
        } else {
            $walletModel->withdraw(Auth::id(), $premiumPrice, 'Sociaera Premium süresi uzatıldı (7 gün)');
            $userModel->extendPremium(Auth::id(), 7);
            Auth::setFlash('success', 'Premium süreniz 7 gün daha uzatıldı! 💎');
        }
    } else {
        // Yeni alım
        if ($balance < $premiumPrice) {
            Auth::setFlash('error', 'Cüzdanınızda yeterli bakiye bulunmuyor.');
        } else {
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

<div style="min-width:0;" class="flex-1 flex flex-col gap-6 max-w-xl w-full mx-auto mt-4">

    <?php if ($isPremiumActive): ?>
    <!-- Aktif Premium -->
    <div class="rounded-2xl p-8 md:p-12 text-center relative overflow-hidden" style="background:#fff; border:1.5px solid #4F46E5; box-shadow:0 12px 30px rgba(79,70,229,0.06);">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 leading-none select-none" style="color:#4f46e5;">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center text-white mb-6 transform -rotate-6 shadow-[0_10px_25px_rgba(79,70,229,0.25)]" style="background:linear-gradient(135deg, #4f46e5, #818cf8);">
                <span class="material-symbols-outlined text-[40px]">diamond</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black mb-2" style="color:var(--text-1);">Premium Üye</h1>
            <p class="text-lg mb-2 font-medium" style="color:#4F46E5;">Tüm premium ayrıcalıkların aktif! ✨</p>

            <!-- Kalan Süre -->
            <div class="rounded-xl p-4 mb-6 inline-flex items-center gap-3 border" style="background:rgba(79,70,229,0.06); border-color:rgba(79,70,229,0.2);">
                <span class="material-symbols-outlined" style="color:#4F46E5;">timer</span>
                <div class="text-left">
                    <div class="text-xs uppercase tracking-wider font-bold" style="color:var(--text-3);">Kalan Süre</div>
                    <div class="text-lg font-black" style="color:#4F46E5;"><?php echo UserModel::premiumRemainingText($user); ?></div>
                </div>
                <?php if (!empty($user['premium_until'])): ?>
                <div class="text-left ml-3 pl-3 border-l" style="border-color:var(--border);">
                    <div class="text-xs uppercase tracking-wider font-bold" style="color:var(--text-3);">Bitiş</div>
                    <div class="text-sm font-semibold" style="color:var(--text-2);"><?php echo date('d.m.Y H:i', strtotime($user['premium_until'])); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="rounded-xl p-6 text-left space-y-3 mb-6 border" style="background:var(--bg-section); border-color:var(--border);">
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
                <div class="flex items-center gap-3" style="color:#16a34a;">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    <span class="text-sm" style="color:var(--text-2);"><?php echo $pf['text']; ?></span>
                    <span class="ml-auto text-[10px] font-bold">AKTİF</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?php echo BASE_URL; ?>/settings" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold border hover:bg-gray-50 transition-colors" style="background:#fff; color:var(--text-2); border-color:var(--border); text-decoration:none;">
                    <span class="material-symbols-outlined">tune</span> Rozet Ayarları
                </a>
                <?php if ($balance >= $premiumPrice): ?>
                <form method="POST" class="inline">
                    <?php echo csrfField(); ?>
                    <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice, 0, ',', '.'); ?> karşılığında 7 gün daha eklenmesini onaylıyor musunuz?')" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold border transition-colors cursor-pointer" style="background:rgba(22,163,74,0.08); color:#16a34a; border-color:rgba(22,163,74,0.25);">
                        <span class="material-symbols-outlined">add_circle</span> Süre Uzat (+7 gün)
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php elseif ($hadPremium): ?>
    <!-- Süresi Dolmuş Premium -->
    <div class="rounded-2xl p-8 md:p-12 text-center relative overflow-hidden" style="background:#fff; border:1.5px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 leading-none select-none" style="color:var(--text-3);">
            <span class="material-symbols-outlined" style="font-size:inherit;">timer_off</span>
        </div>
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center text-white mb-6 transform -rotate-6 shadow-[0_10px_25px_rgba(245,158,11,0.2)]" style="background:linear-gradient(135deg, #f59e0b, #ef4444);">
                <span class="material-symbols-outlined text-[40px]">timer_off</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black mb-2" style="color:var(--text-1);">Premium Süresi Doldu</h1>
            <p class="text-lg mb-6 font-medium" style="color:#f59e0b;">Premium ayrıcalıkların pasif durumda</p>

            <div class="rounded-xl p-6 text-left space-y-3 mb-8 border" style="background:var(--bg-section); border-color:var(--border);">
                <?php
                $expiredFeatures = ['Reklamsız deneyim','Profil rozeti seçimi','Özel profil temaları','Yüksek yükleme limiti (20MB)','Yarı cooldown & 2.5x rate limit','2x check-in ödülü','Uzun bio','Kampanya erken erişim','Mekan favorileri','Detaylı istatistikler','Profilime kim baktı','Premium rozetler','Sıralama öne çıkma'];
                foreach ($expiredFeatures as $ef):
                ?>
                <div class="flex items-center gap-3" style="color:#ef4444;">
                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                    <span class="line-through text-sm" style="color:var(--text-3);"><?php echo $ef; ?></span>
                    <span class="ml-auto text-[10px] font-bold">PASİF</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="my-6">
                <span class="text-5xl font-black" style="color:var(--text-1);">$<?php echo number_format($premiumPrice, 0, ',', '.'); ?></span>
                <span class="text-lg ml-1" style="color:var(--text-3);">/ 7 gün</span>
            </div>

            <div class="rounded-xl p-4 mb-4 flex items-center justify-between border" style="background:var(--bg-section); border-color:var(--border-light);">
                <span class="text-sm" style="color:var(--text-3);">Cüzdan Bakiyen</span>
                <span class="font-black text-xl <?php echo $balance >= $premiumPrice ? 'text-emerald-500' : 'text-red-500'; ?>">$<?php echo number_format($balance, 2, ',', '.'); ?></span>
            </div>

            <?php if ($balance >= $premiumPrice): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice, 0, ',', '.'); ?> ile 7 günlük Premium\'u yeniden aktifleştirmek istiyor musunuz?')" class="w-full text-white py-4 rounded-xl font-black text-lg flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-[0_4px_16px_rgba(240,109,31,0.25)]" style="background:var(--color-primary); border:none; cursor:pointer;">
                    <span class="material-symbols-outlined">restart_alt</span> Premium'u Yenile
                </button>
            </form>
            <?php else: ?>
            <div class="space-y-3">
                <button disabled class="w-full py-4 rounded-xl font-bold flex items-center justify-center gap-2 cursor-not-allowed border" style="background:var(--bg-section); color:var(--text-3); border-color:var(--border);">
                    <span class="material-symbols-outlined">account_balance_wallet</span> Yetersiz Bakiye
                </button>
                <a href="<?php echo BASE_URL; ?>/wallet" class="block text-center font-bold text-sm hover:underline" style="color:var(--color-primary); text-decoration:none;">Cüzdana git ve bakiye yükle →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- İlk Kez Satın Alma -->
    <div class="rounded-2xl p-8 md:p-12 text-center relative overflow-hidden" style="background:#fff; border:1.5px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 leading-none select-none" style="color:var(--color-primary);">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center text-white mb-6 transform -rotate-6 shadow-[0_10px_25px_rgba(240,109,31,0.25)]" style="background:linear-gradient(135deg, var(--color-primary), #ff9e7d);">
                <span class="material-symbols-outlined text-[40px]">diamond</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black mb-2" style="color:var(--text-1);"><?php echo APP_NAME; ?> Premium</h1>
            <p class="text-lg mb-2 font-medium" style="color:var(--color-primary);">Deneyimini bir üst seviyeye taşı</p>

            <div class="my-6">
                <span class="text-5xl font-black" style="color:var(--text-1);">$<?php echo number_format($premiumPrice, 0, ',', '.'); ?></span>
                <span class="text-lg ml-1" style="color:var(--text-3);">/ 7 gün</span>
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
                <li class="flex items-center gap-3" style="color:var(--text-1);">
                    <span class="material-symbols-outlined text-[20px]" style="color:var(--color-primary);"><?php echo $nf['icon']; ?></span>
                    <span class="text-sm" style="color:var(--text-2);"><?php echo $nf['text']; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="rounded-xl p-4 mb-4 flex items-center justify-between border" style="background:var(--bg-section); border-color:var(--border-light);">
                <span class="text-sm" style="color:var(--text-3);">Cüzdan Bakiyen</span>
                <span class="font-black text-xl <?php echo $balance >= $premiumPrice ? 'text-emerald-500' : 'text-red-500'; ?>">$<?php echo number_format($balance, 2, ',', '.'); ?></span>
            </div>

            <?php if ($balance >= $premiumPrice): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice, 0, ',', '.'); ?> cüzdanınızdan çekilecek. 7 günlük Premium başlayacak. Onaylıyor musunuz?')" class="w-full text-white py-4 rounded-xl font-black text-lg flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-[0_4px_16px_rgba(240,109,31,0.25)]" style="background:var(--color-primary); border:none; cursor:pointer;">
                    <span class="material-symbols-outlined">diamond</span> Premium'a Geç (7 Gün)
                </button>
            </form>
            <?php else: ?>
            <div class="space-y-3">
                <button disabled class="w-full py-4 rounded-xl font-bold flex items-center justify-center gap-2 cursor-not-allowed border" style="background:var(--bg-section); color:var(--text-3); border-color:var(--border);">
                    <span class="material-symbols-outlined">account_balance_wallet</span> Yetersiz Bakiye
                </button>
                <a href="<?php echo BASE_URL; ?>/wallet" class="block text-center font-bold text-sm hover:underline" style="color:var(--color-primary); text-decoration:none;">Cüzdana git ve bakiye yükle →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
