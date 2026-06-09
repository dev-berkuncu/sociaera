<?php
/**
 * Sociaera — App Header (Swarm/Foursquare Edition)
 */

// Gerekli model'ler yüklenmediyse yükle
$baseDir = dirname(__DIR__, 2);
if (!class_exists('UserModel'))     { require_once $baseDir . '/app/Models/User.php'; }
if (!class_exists('VenueModel'))    { require_once $baseDir . '/app/Models/Venue.php'; }
if (!class_exists('CheckinModel'))  { require_once $baseDir . '/app/Models/Checkin.php'; }
if (!class_exists('NotificationModel')) { require_once $baseDir . '/app/Models/Notification.php'; }
if (!class_exists('WalletModel'))   { require_once $baseDir . '/app/Models/Wallet.php'; }
if (!class_exists('LeaderboardModel')) { require_once $baseDir . '/app/Models/Leaderboard.php'; }

if (!isset($currentUser) && Auth::check()) {
    $currentUser = (new UserModel())->getById(Auth::id());
}

// Sidebar verileri
$headerStreak = 0;
$headerWeekly = 0;
$headerRank   = null;
$headerStats  = ['checkins' => 0, 'following' => 0, 'followers' => 0];
$headerTrend  = [];
$notifCount   = 0;
$avatarUrl    = '';

if (Auth::check() && isset($currentUser)) {
    $avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']);

    try {
        $headerStats   = (new UserModel())->getStats($currentUser['id']);
        $headerWeekly  = (new CheckinModel())->getWeeklyCheckinCount($currentUser['id']);
        $headerRank    = (new LeaderboardModel())->getUserRank($currentUser['id']);
    } catch (Exception $e) {}

    try {
        $db_h = Database::getConnection();
        $s = $db_h->prepare("SELECT DISTINCT DATE(created_at) as d FROM checkins WHERE user_id = ? AND is_deleted = 0 ORDER BY d DESC LIMIT 60");
        $s->execute([$currentUser['id']]);
        $dates = $s->fetchAll(PDO::FETCH_COLUMN);
        $today = new DateTime();
        foreach ($dates as $i => $d) {
            $expected = (clone $today)->modify("-{$i} days")->format('Y-m-d');
            if ($d === $expected) { $headerStreak++; } else { break; }
        }
    } catch (Exception $e) {}

    try {
        $notifCount = (new NotificationModel())->getUnreadCount($currentUser['id']);
    } catch (Exception $e) {}

    try {
        $headerTrend = (new VenueModel())->getTrending(5);
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="<?php echo csrfToken(); ?>"/>
<style>body{opacity:0}body.ready{opacity:1;transition:opacity .15s ease-in}</style>
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title($pageTitle ?? 'Sociaera'); ?></title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>

<!-- Tailwind (sadece utility class'lar için) -->
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script id="tailwind-config">
tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary:  '#F06D1F',
                secondary:'#FFA633',
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
            }
        }
    }
}
</script>

<!-- Swarm Design System -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/stitch.css"/>

<script>
(function(){
    var t = setTimeout(function(){ document.body.classList.add('ready'); }, 1500);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(function(){ clearTimeout(t); document.body.classList.add('ready'); });
    } else {
        clearTimeout(t); document.body.classList.add('ready');
    }
})();
</script>
<script defer src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</head>
<body>

<?php if (Auth::check() && isset($currentUser)): ?>

<!-- ── TOP NAV ────────────────────────────────────────────── -->
<nav class="swarm-topnav">

    <!-- Logo -->
    <a href="<?php echo BASE_URL; ?>/dashboard" class="swarm-topnav-logo">
        <span class="material-symbols-outlined" style="color:var(--color-primary);font-size:22px;font-variation-settings:'FILL' 1;">hive</span>
        <span>Sociaera</span>
    </a>

    <!-- Arama -->
    <div class="swarm-topnav-search" style="position:relative;flex:1;max-width:360px;">
        <span class="material-symbols-outlined" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:18px;pointer-events:none;">search</span>
        <input
            type="text"
            class="swarm-topnav-search"
            placeholder="Mekan ara…"
            style="padding-left:36px;"
            onkeydown="if(event.key==='Enter')window.location.href='<?php echo BASE_URL; ?>/venues?q='+encodeURIComponent(this.value)"
        />
    </div>

    <!-- Sağ aksiyonlar -->
    <div class="swarm-topnav-actions">

        <!-- Bildirimler -->
        <a href="<?php echo BASE_URL; ?>/notifications" class="swarm-icon-btn" aria-label="Bildirimler">
            <span class="material-symbols-outlined" style="font-size:22px;<?php echo $notifCount > 0 ? "font-variation-settings:'FILL' 1;color:var(--color-primary);" : ''; ?>">notifications</span>
            <?php if ($notifCount > 0): ?>
            <span style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:var(--color-primary);border-radius:50%;border:2px solid #fff;"></span>
            <?php endif; ?>
        </a>

        <!-- Ayarlar -->
        <a href="<?php echo BASE_URL; ?>/settings" class="swarm-icon-btn" aria-label="Ayarlar">
            <span class="material-symbols-outlined" style="font-size:20px;">settings</span>
        </a>

        <!-- Avatar + dropdown -->
        <div style="position:relative;" class="group">
            <a href="<?php echo BASE_URL; ?>/profile" class="swarm-avatar-btn" aria-label="Profil">
                <img src="<?php echo $avatarUrl; ?>" alt="Profil" width="34" height="34"/>
            </a>

            <!-- Dropdown -->
            <div style="position:absolute;right:0;top:calc(100% + 8px);width:200px;background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,0.12);padding:6px;z-index:200;display:none;" class="group-hover:block hover:block">
                <a href="<?php echo BASE_URL; ?>/profile" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;color:var(--text-1);font-size:13px;font-weight:600;transition:background .12s;" onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''"><?php
                ?><span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">person</span> Profilim<?php
                ?></a>
                <a href="<?php echo BASE_URL; ?>/wallet" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;color:var(--text-1);font-size:13px;font-weight:600;transition:background .12s;" onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''"><?php
                ?><span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">account_balance_wallet</span> Cüzdanım<?php
                ?></a>
                <a href="<?php echo BASE_URL; ?>/missions" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;color:var(--text-1);font-size:13px;font-weight:600;transition:background .12s;" onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''"><?php
                ?><span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">military_tech</span> Görevler<?php
                ?></a>
                <a href="<?php echo BASE_URL; ?>/kampanyalar" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;color:var(--text-1);font-size:13px;font-weight:600;transition:background .12s;" onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''"><?php
                ?><span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">campaign</span> Kampanyalar<?php
                ?></a>
                <a href="<?php echo BASE_URL; ?>/premium" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;color:var(--text-1);font-size:13px;font-weight:600;transition:background .12s;" onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''"><?php
                ?><span class="material-symbols-outlined" style="font-size:18px;color:#4F46E5;">diamond</span> Premium<?php
                ?></a>
                <a href="<?php echo BASE_URL; ?>/mystery-shopper" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;color:var(--text-1);font-size:13px;font-weight:600;transition:background .12s;" onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''"><?php
                ?><span class="material-symbols-outlined" style="font-size:18px;color:var(--text-3);">visibility_off</span> Gizli Müşteri<?php
                ?></a>
                <hr style="border:none;border-top:1px solid var(--border-light);margin:4px 0;"/>
                <a href="<?php echo BASE_URL; ?>/character-select" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;color:var(--text-1);font-size:13px;font-weight:600;transition:background .12s;" onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''"><?php
                ?><span class="material-symbols-outlined" style="font-size:18px;color:var(--text-3);">switch_account</span> Karakter Değiştir<?php
                ?></a>
                <a href="<?php echo BASE_URL; ?>/logout" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;text-decoration:none;color:var(--color-danger);font-size:13px;font-weight:600;transition:background .12s;" onmouseover="this.style.background='#FEF2F2'" onmouseout="this.style.background=''"><?php
                ?><span class="material-symbols-outlined" style="font-size:18px;">logout</span> Çıkış Yap<?php
                ?></a>
            </div>
        </div>

    </div>
</nav>

<!-- ── MAIN LAYOUT ────────────────────────────────────────── -->
<div class="swarm-layout">

    <!-- Feed (center) -->
    <div class="swarm-feed">
        <!-- Page content renders here -->

<?php else: ?>
<!-- Guest: sadece içerik alanı -->
<main>
<?php endif; ?>
