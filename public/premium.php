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
        if ($balance < $premiumPrice) {
            Auth::setFlash('error', 'Cüzdanınızda yeterli bakiye bulunmuyor.');
        } else {
            $walletModel->withdraw(Auth::id(), $premiumPrice, 'Sociaera Premium süresi uzatıldı (7 gün)');
            $userModel->extendPremium(Auth::id(), 7);
            Auth::setFlash('success', 'Premium süreniz 7 gün daha uzatıldı! 💎');
        }
    } else {
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

<div style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:560px; width:100%; margin:0 auto; padding:16px 0 40px;">

<?php if ($isPremiumActive): ?>
    <!-- Aktif Premium -->
    <div style="background:#fff; border:1.5px solid #4F46E5; border-radius:20px; box-shadow:0 12px 30px rgba(79,70,229,0.06); padding:40px 32px; text-align:center; position:relative; overflow:hidden;">
        <div style="position:absolute; right:-30px; top:-30px; font-size:140px; opacity:0.05; color:#4f46e5; line-height:1; pointer-events:none; user-select:none;">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        <div style="position:relative;">
            <div style="width:80px; height:80px; margin:0 auto 24px; border-radius:20px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#4f46e5,#818cf8); transform:rotate(-6deg); box-shadow:0 10px 25px rgba(79,70,229,0.25);">
                <span class="material-symbols-outlined" style="font-size:40px; color:#fff; font-variation-settings:'FILL' 1;">diamond</span>
            </div>
            <h1 style="font-size:2rem; font-weight:900; color:var(--text-1); margin:0 0 8px;">Premium Üye</h1>
            <p style="font-size:1rem; font-weight:500; color:#4F46E5; margin:0 0 24px;">Tüm premium ayrıcalıkların aktif! ✨</p>

            <div style="display:inline-flex; align-items:center; gap:12px; background:rgba(79,70,229,0.06); border:1px solid rgba(79,70,229,0.2); border-radius:14px; padding:14px 20px; margin-bottom:24px;">
                <span class="material-symbols-outlined" style="color:#4F46E5;">timer</span>
                <div style="text-align:left;">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.7px; font-weight:700; color:var(--text-3);">Kalan Süre</div>
                    <div style="font-size:1.1rem; font-weight:900; color:#4F46E5;"><?php echo UserModel::premiumRemainingText($user); ?></div>
                </div>
                <?php if (!empty($user['premium_until'])): ?>
                <div style="text-align:left; margin-left:12px; padding-left:12px; border-left:1px solid var(--border);">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.7px; font-weight:700; color:var(--text-3);">Bitiş</div>
                    <div style="font-size:0.85rem; font-weight:600; color:var(--text-2);"><?php echo date('d.m.Y H:i', strtotime($user['premium_until'])); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div style="background:var(--bg-section); border:1px solid var(--border); border-radius:14px; padding:20px; text-align:left; margin-bottom:24px; display:flex; flex-direction:column; gap:10px;">
                <?php
                $premiumFeatures = [
                    ['icon'=>'block',         'text'=>'Reklamsız deneyim'],
                    ['icon'=>'badge',         'text'=>'Profil rozeti seçimi'],
                    ['icon'=>'palette',       'text'=>'Özel profil temaları (6 tema)'],
                    ['icon'=>'upload_file',   'text'=>'Yüksek yükleme limiti (20MB)'],
                    ['icon'=>'timer',         'text'=>'Yarı cooldown & 2.5x rate limit'],
                    ['icon'=>'paid',          'text'=>'2x check-in cüzdan ödülü'],
                    ['icon'=>'text_fields',   'text'=>'Uzun bio (500 karakter)'],
                    ['icon'=>'early_on',      'text'=>'Kampanyalara erken erişim (24s)'],
                    ['icon'=>'star',          'text'=>'Mekan favorileri'],
                    ['icon'=>'analytics',     'text'=>'Detaylı istatistikler & trend'],
                    ['icon'=>'visibility',    'text'=>'Profilime kim baktı'],
                    ['icon'=>'military_tech', 'text'=>'Premium-only rozetler (4 rozet)'],
                    ['icon'=>'leaderboard',   'text'=>'Sıralama tablosunda öne çıkma'],
                ];
                foreach ($premiumFeatures as $pf): ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="material-symbols-outlined" style="font-size:18px; color:#16a34a;">check_circle</span>
                    <span style="font-size:13px; color:var(--text-2); flex:1;"><?php echo $pf['text']; ?></span>
                    <span style="font-size:10px; font-weight:700; color:#16a34a; background:rgba(22,163,74,0.08); padding:2px 7px; border-radius:20px;">AKTİF</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
                <a href="<?php echo BASE_URL; ?>/settings"
                   style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:12px; font-weight:700; font-size:13px; border:1px solid var(--border); background:#fff; color:var(--text-2); text-decoration:none;"
                   onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background='#fff'">
                    <span class="material-symbols-outlined" style="font-size:18px;">tune</span> Rozet Ayarları
                </a>
                <?php if ($balance >= $premiumPrice): ?>
                <form method="POST" style="display:inline;">
                    <?php echo csrfField(); ?>
                    <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice,0,',','.'); ?> karşılığında 7 gün daha eklenmesini onaylıyor musunuz?')"
                        style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:12px; font-weight:700; font-size:13px; background:rgba(22,163,74,0.08); color:#16a34a; border:1px solid rgba(22,163,74,0.25); cursor:pointer; font-family:inherit;"
                        onmouseover="this.style.background='rgba(22,163,74,0.15)'" onmouseout="this.style.background='rgba(22,163,74,0.08)'">
                        <span class="material-symbols-outlined" style="font-size:18px;">add_circle</span> Süre Uzat (+7 gün)
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($hadPremium): ?>
    <!-- Süresi Dolmuş Premium -->
    <div style="background:#fff; border:1.5px solid var(--border); border-radius:20px; box-shadow:0 1px 3px rgba(0,0,0,.08); padding:40px 32px; text-align:center; position:relative; overflow:hidden;">
        <div style="position:absolute; right:-30px; top:-30px; font-size:140px; opacity:0.05; color:var(--text-3); line-height:1; pointer-events:none; user-select:none;">
            <span class="material-symbols-outlined" style="font-size:inherit;">timer_off</span>
        </div>
        <div style="position:relative;">
            <div style="width:80px; height:80px; margin:0 auto 24px; border-radius:20px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#f59e0b,#ef4444); transform:rotate(-6deg); box-shadow:0 10px 25px rgba(245,158,11,0.2);">
                <span class="material-symbols-outlined" style="font-size:40px; color:#fff; font-variation-settings:'FILL' 1;">timer_off</span>
            </div>
            <h1 style="font-size:2rem; font-weight:900; color:var(--text-1); margin:0 0 8px;">Premium Süresi Doldu</h1>
            <p style="font-size:1rem; font-weight:500; color:#f59e0b; margin:0 0 24px;">Premium ayrıcalıkların pasif durumda</p>

            <div style="background:var(--bg-section); border:1px solid var(--border); border-radius:14px; padding:20px; text-align:left; margin-bottom:24px; display:flex; flex-direction:column; gap:10px;">
                <?php
                $expiredFeatures = ['Reklamsız deneyim','Profil rozeti seçimi','Özel profil temaları','Yüksek yükleme limiti (20MB)','Yarı cooldown & 2.5x rate limit','2x check-in ödülü','Uzun bio','Kampanya erken erişim','Mekan favorileri','Detaylı istatistikler','Profilime kim baktı','Premium rozetler','Sıralama öne çıkma'];
                foreach ($expiredFeatures as $ef): ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="material-symbols-outlined" style="font-size:18px; color:#ef4444;">cancel</span>
                    <span style="text-decoration:line-through; font-size:13px; color:var(--text-3); flex:1;"><?php echo $ef; ?></span>
                    <span style="font-size:10px; font-weight:700; color:#ef4444; background:rgba(239,68,68,0.08); padding:2px 7px; border-radius:20px;">PASİF</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-bottom:16px;">
                <span style="font-size:3rem; font-weight:900; color:var(--text-1);">$<?php echo number_format($premiumPrice,0,',','.'); ?></span>
                <span style="font-size:1rem; color:var(--text-3); margin-left:4px;">/ 7 gün</span>
            </div>

            <div style="background:var(--bg-section); border:1px solid var(--border); border-radius:14px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                <span style="font-size:13px; color:var(--text-3);">Cüzdan Bakiyen</span>
                <span style="font-weight:900; font-size:1.2rem; color:<?php echo $balance >= $premiumPrice ? '#16a34a' : '#ef4444'; ?>;">$<?php echo number_format($balance,2,',','.'); ?></span>
            </div>

            <?php if ($balance >= $premiumPrice): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice,0,',','.'); ?> ile 7 günlük Premium\'u yeniden aktifleştirmek istiyor musunuz?')"
                    style="width:100%; padding:14px; border-radius:14px; font-weight:900; font-size:1rem; background:var(--color-primary); color:#fff; border:none; cursor:pointer; font-family:inherit; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 16px rgba(240,109,31,0.25);"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='brightness(1)'">
                    <span class="material-symbols-outlined">restart_alt</span> Premium'u Yenile
                </button>
            </form>
            <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <button disabled style="width:100%; padding:14px; border-radius:14px; font-weight:700; font-size:14px; display:flex; align-items:center; justify-content:center; gap:8px; cursor:not-allowed; border:1px solid var(--border); background:var(--bg-section); color:var(--text-3); font-family:inherit;">
                    <span class="material-symbols-outlined">account_balance_wallet</span> Yetersiz Bakiye
                </button>
                <a href="<?php echo BASE_URL; ?>/wallet" style="display:block; text-align:center; font-weight:700; font-size:13px; color:var(--color-primary); text-decoration:none;">Cüzdana git ve bakiye yükle →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- İlk Kez Satın Alma -->
    <div style="background:#fff; border:1.5px solid var(--border); border-radius:20px; box-shadow:0 1px 3px rgba(0,0,0,.08); padding:40px 32px; text-align:center; position:relative; overflow:hidden;">
        <div style="position:absolute; right:-30px; top:-30px; font-size:140px; opacity:0.05; color:var(--color-primary); line-height:1; pointer-events:none; user-select:none;">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        <div style="position:relative;">
            <div style="width:80px; height:80px; margin:0 auto 24px; border-radius:20px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,var(--color-primary),#ff9e7d); transform:rotate(-6deg); box-shadow:0 10px 25px rgba(240,109,31,0.25);">
                <span class="material-symbols-outlined" style="font-size:40px; color:#fff; font-variation-settings:'FILL' 1;">diamond</span>
            </div>
            <h1 style="font-size:2rem; font-weight:900; color:var(--text-1); margin:0 0 8px;"><?php echo APP_NAME; ?> Premium</h1>
            <p style="font-size:1rem; font-weight:500; color:var(--color-primary); margin:0 0 24px;">Deneyimini bir üst seviyeye taşı</p>

            <div style="margin-bottom:24px;">
                <span style="font-size:3rem; font-weight:900; color:var(--text-1);">$<?php echo number_format($premiumPrice,0,',','.'); ?></span>
                <span style="font-size:1rem; color:var(--text-3); margin-left:4px;">/ 7 gün</span>
            </div>

            <ul style="display:flex; flex-direction:column; gap:10px; text-align:left; max-width:440px; margin:0 auto 28px; list-style:none; padding:0;">
                <?php
                $newFeatures = [
                    ['icon'=>'block',         'text'=>'Reklamsız deneyim'],
                    ['icon'=>'badge',         'text'=>'Profil rozeti seçimi'],
                    ['icon'=>'palette',       'text'=>'6 özel profil teması'],
                    ['icon'=>'upload_file',   'text'=>'2x yükleme limiti (20MB)'],
                    ['icon'=>'timer',         'text'=>'½ cooldown & 2.5x rate limit'],
                    ['icon'=>'paid',          'text'=>'2x check-in cüzdan ödülü ($20)'],
                    ['icon'=>'text_fields',   'text'=>'Uzun bio (500 karakter)'],
                    ['icon'=>'early_on',      'text'=>'Kampanyalara 24s erken erişim'],
                    ['icon'=>'star',          'text'=>'Mekan favorileri sistemi'],
                    ['icon'=>'analytics',     'text'=>'Detaylı istatistik & trend grafik'],
                    ['icon'=>'visibility',    'text'=>'Profilime kim baktı'],
                    ['icon'=>'military_tech', 'text'=>'4 özel premium rozet'],
                    ['icon'=>'leaderboard',   'text'=>'Sıralama tablosunda öne çıkma'],
                ];
                foreach ($newFeatures as $nf): ?>
                <li style="display:flex; align-items:center; gap:10px;">
                    <span class="material-symbols-outlined" style="font-size:20px; color:var(--color-primary);"><?php echo $nf['icon']; ?></span>
                    <span style="font-size:13px; color:var(--text-2);"><?php echo $nf['text']; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <div style="background:var(--bg-section); border:1px solid var(--border); border-radius:14px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                <span style="font-size:13px; color:var(--text-3);">Cüzdan Bakiyen</span>
                <span style="font-weight:900; font-size:1.2rem; color:<?php echo $balance >= $premiumPrice ? '#16a34a' : '#ef4444'; ?>;">$<?php echo number_format($balance,2,',','.'); ?></span>
            </div>

            <?php if ($balance >= $premiumPrice): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" onclick="return confirm('$<?php echo number_format($premiumPrice,0,',','.'); ?> cüzdanınızdan çekilecek. 7 günlük Premium başlayacak. Onaylıyor musunuz?')"
                    style="width:100%; padding:14px; border-radius:14px; font-weight:900; font-size:1rem; background:var(--color-primary); color:#fff; border:none; cursor:pointer; font-family:inherit; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 16px rgba(240,109,31,0.25);"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='brightness(1)'">
                    <span class="material-symbols-outlined">diamond</span> Premium'a Geç (7 Gün)
                </button>
            </form>
            <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <button disabled style="width:100%; padding:14px; border-radius:14px; font-weight:700; font-size:14px; display:flex; align-items:center; justify-content:center; gap:8px; cursor:not-allowed; border:1px solid var(--border); background:var(--bg-section); color:var(--text-3); font-family:inherit;">
                    <span class="material-symbols-outlined">account_balance_wallet</span> Yetersiz Bakiye
                </button>
                <a href="<?php echo BASE_URL; ?>/wallet" style="display:block; text-align:center; font-weight:700; font-size:13px; color:var(--color-primary); text-decoration:none;">Cüzdana git ve bakiye yükle →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
