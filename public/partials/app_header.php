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
<style>
/* ── CRITICAL INLINE CSS — dış dosyaya bağımlı değil ── */
:root{--cp:#F06D1F;--cp-hover:#D95E10;--cp-light:#FFA633;--cp-bg:#FFF3EB;
--bg-app:#F5F4F0;--bg-card:#fff;--bg-section:#F8F7F5;--bg-input:#F2F1EE;
--border:#E8E7E3;--border-l:#F0EFEc;--t1:#1A1A1A;--t2:#5C5C5C;--t3:#A0A0A0;
--r-card:16px;--r-btn:24px;--r-inp:12px;
--sh-card:0 1px 3px rgba(0,0,0,.08),0 4px 12px rgba(0,0,0,.05);
--sh-hover:0 4px 12px rgba(0,0,0,.12),0 8px 24px rgba(0,0,0,.08);
--font:'Plus Jakarta Sans','Inter',sans-serif;}

/* Dark mode sıfırla */
@media(prefers-color-scheme:dark){html,body{background:#F5F4F0!important;color:#1A1A1A!important}}

html,body{background:#F5F4F0!important;color:#1A1A1A!important;font-family:var(--font);margin:0;padding:0}

/* NAV */
nav.swarm-topnav{
  position:sticky;top:0;z-index:200;
  background:#fff!important;border-bottom:2px solid #E8E7E3;
  padding:10px 16px;display:flex!important;align-items:center;gap:12px;min-height:56px;
  box-shadow:0 1px 4px rgba(0,0,0,.06);}
.swarm-topnav-logo{font-weight:800;font-size:18px;color:#F06D1F!important;
  text-decoration:none;display:flex;align-items:center;gap:6px;flex-shrink:0;}
.swarm-topnav input{flex:1;background:#F2F1EE;border:1.5px solid transparent;
  border-radius:12px;padding:8px 14px 8px 36px;font-size:14px;outline:none;color:#1A1A1A;}
.swarm-topnav input:focus{border-color:#F06D1F;background:#fff;}
.swarm-icon-btn{width:38px;height:38px;border-radius:50%;border:none;background:transparent;
  cursor:pointer;display:flex;align-items:center;justify-content:center;color:#5C5C5C;
  text-decoration:none;position:relative;}
.swarm-icon-btn:hover{background:#F8F7F5;}
.swarm-avatar-btn{width:34px;height:34px;border-radius:50%;overflow:hidden;cursor:pointer;
  border:2px solid #E8E7E3;display:block;}
.swarm-avatar-btn img{width:100%;height:100%;object-fit:cover;}

/* LAYOUT — CSS Grid ile kesin çözüm */
.swarm-layout{
  display:grid!important;
  grid-template-columns:minmax(0,1fr);
  gap:24px;max-width:920px;margin:0 auto;padding:16px 16px 90px;
  align-items:start;}
@media(min-width:1024px){
  .swarm-layout{grid-template-columns:minmax(0,600px) 280px;max-width:920px;}}

.swarm-right-panel{display:none!important;flex-direction:column;gap:12px;
  position:sticky;top:72px;max-height:calc(100vh - 88px);overflow-y:auto;}
@media(min-width:1024px){.swarm-right-panel{display:flex!important;}}

/* CARDS */
.swarm-card,.checkin-card,.venue-card,.right-panel-card{
  background:#fff!important;border-radius:16px;
  box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 12px rgba(0,0,0,.05);
  overflow:hidden;}
.swarm-card:hover,.checkin-card:hover,.venue-card:hover{
  box-shadow:0 4px 12px rgba(0,0,0,.12),0 8px 24px rgba(0,0,0,.08);}

/* BOTTOM NAV */
.swarm-bottom-nav{
  position:fixed;bottom:0;left:0;right:0;z-index:150;
  background:#fff!important;border-top:1.5px solid #E8E7E3;
  display:flex!important;align-items:center;justify-content:space-around;
  padding:6px 0 calc(6px + env(safe-area-inset-bottom));height:60px;}
@media(min-width:1024px){.swarm-bottom-nav{display:none!important;}}
.swarm-nav-item{display:flex;flex-direction:column;align-items:center;
  gap:2px;font-size:10px;font-weight:600;color:#A0A0A0;text-decoration:none;
  padding:4px 12px;border-radius:12px;}
.swarm-nav-item.active,.swarm-nav-item:hover{color:#F06D1F;}
.swarm-nav-fab-item{display:flex;align-items:center;justify-content:center;}
.swarm-nav-fab-inner{width:48px;height:48px;border-radius:50%;
  background:linear-gradient(135deg,#F06D1F,#FFA633);color:#fff;
  display:flex;align-items:center;justify-content:center;text-decoration:none;
  box-shadow:0 4px 16px rgba(240,109,31,.4);}

/* FAB (desktop) */
.swarm-fab{position:fixed;right:24px;bottom:24px;z-index:150;
  width:56px;height:56px;border-radius:50%;
  background:linear-gradient(135deg,#F06D1F,#FFA633);color:#fff;
  display:flex;align-items:center;justify-content:center;text-decoration:none;
  box-shadow:0 4px 16px rgba(240,109,31,.4);}
@media(max-width:1023px){.swarm-fab{display:none!important;}}

/* BTN */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;
  border-radius:24px;font-weight:700;font-size:13px;cursor:pointer;
  border:1.5px solid transparent;text-decoration:none;transition:all .15s;}
.btn-primary{background:#F06D1F!important;color:#fff!important;border-color:#F06D1F!important;}
.btn-primary:hover{background:#D95E10!important;}
.btn-ghost{background:transparent;color:#5C5C5C;border-color:#E8E7E3;}
.btn-ghost:hover{background:#F8F7F5;}
.btn-sm{padding:6px 14px;font-size:12px;}
.btn-block{width:100%;justify-content:center;}
.btn-lg{padding:13px 24px;font-size:15px;}

/* Tailwind compat - eski siyah renkleri beyaza */
.glass-panel,.swarm-glass-card,.receipt-card,.glass-effect{
  background:#fff!important;backdrop-filter:none!important;border-color:#E8E7E3!important;}
.bg-\[#2a2a2b\],.bg-\[#2a2a2b\]\/80,.bg-\[#2a2a2b\]\/90,.bg-\[#0b0c10\],
.bg-\[#131314\],.bg-\[#060a13\],.bg-obsidian,.bg-cyber-dark{background:#fff!important;}
.text-on-surface,.text-white{color:#1A1A1A!important;}
.text-on-surface-variant,.text-slate-400,.text-slate-500{color:#5C5C5C!important;}
.text-primary,.text-primary-container,.text-cyber-orange{color:#F06D1F!important;}
.text-secondary{color:#FFA633!important;}
.bg-surface-container,.bg-surface-container\/60,.bg-surface-container\/30{background:#F8F7F5!important;}
.bg-primary-container{background:#F06D1F!important;color:#fff!important;}
.border-white\/5,.border-white\/10,.border-white\/20,.border-white\/0{border-color:#E8E7E3!important;}
.scanlines,.map-grid,.cybermap-marker-pulse{display:none!important;}
.backdrop-blur-xl,.backdrop-blur-sm,.backdrop-blur{backdrop-filter:none!important;}
</style>
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title($pageTitle ?? 'Sociaera'); ?></title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>

<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script id="tailwind-config">
tailwind.config = {
    darkMode: 'class',
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

<!-- stitch.css yüklendi -->
<script defer src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</head>
<body class="ready">

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
    <!-- Feed area starts — page content renders directly here -->

<?php else: ?>
<!-- Guest: sadece içerik alanı -->
<main>
<?php endif; ?>

