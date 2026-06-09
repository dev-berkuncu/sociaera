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
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title($pageTitle ?? 'Sociaera'); ?></title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>

<!-- Tailwind CDN (darkMode:class — OS dark mode etkilemez) -->
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
tailwind.config = {
    darkMode: 'class',
    theme: { extend: {
        colors: { primary:'#F06D1F', secondary:'#FFA633' },
        fontFamily: { sans: ['Plus Jakarta Sans','Inter','sans-serif'] }
    }}
}
</script>

<!-- Swarm Design System (stitch.css — Tailwind'den sonra gelir, override eder) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/stitch.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/stitch.css') ?: time(); ?>"/>

<!-- CRITICAL INLINE — dış CSS yüklenmese bile çalışır -->
<style>
/* CSS DEĞİŞKENLERİ — hem --color-primary hem --cp biçiminde */
:root{
  --color-primary:#F06D1F;  --color-primary-hover:#D95E10;
  --color-primary-light:#FFA633; --color-primary-bg:#FFF3EB;
  --color-danger:#EF4444;   --color-success:#16A34A;
  --bg-app:#F5F4F0;         --bg-card:#fff;
  --bg-section:#F8F7F5;     --bg-input:#F2F1EE;
  --border:#E8E7E3;         --border-light:#F0EFEb;
  --text-1:#1A1A1A;         --text-2:#5C5C5C; --text-3:#A0A0A0;
  --radius-card:16px;       --radius-btn:24px; --radius-input:12px;
  --shadow-card:0 1px 3px rgba(0,0,0,.08),0 4px 12px rgba(0,0,0,.05);
  --shadow-card-hover:0 4px 12px rgba(0,0,0,.12),0 8px 24px rgba(0,0,0,.08);
  --shadow-fab:0 4px 16px rgba(240,109,31,.4);
  --font:'Plus Jakarta Sans','Inter','Helvetica Neue',sans-serif;
}
/* Dark mode Tailwind override — OS dark modunu sıfırla */
@media(prefers-color-scheme:dark){
  html,body{background:#F5F4F0!important;color:#1A1A1A!important;}
  .bg-\[#2a2a2b\],.bg-\[#0b0c10\],.bg-\[#131314\],.bg-\[#060a13\]{background:#fff!important;}
}
html,body{background:#F5F4F0!important;color:#1A1A1A!important;
  font-family:var(--font);margin:0;padding:0;min-height:100vh;}

/* ── TOP NAV ── */
nav.swarm-topnav{
  position:sticky;top:0;z-index:500;
  background:#ffffff!important;border-bottom:2px solid #E8E7E3!important;
  padding:8px 20px;display:flex!important;align-items:center;gap:16px;
  min-height:58px;box-shadow:0 2px 8px rgba(0,0,0,.07);}
.swarm-topnav-logo{
  font-weight:800;font-size:18px;color:#F06D1F!important;
  text-decoration:none;display:flex;align-items:center;gap:6px;
  flex-shrink:0;letter-spacing:-0.3px;white-space:nowrap;}
.swarm-topnav-logo span.material-symbols-outlined{color:#F06D1F!important;}
.swarm-topnav-search-wrap{position:relative;flex:1;max-width:380px;}
.swarm-topnav-search-wrap .search-icon{
  position:absolute;left:11px;top:50%;transform:translateY(-50%);
  color:#A0A0A0;font-size:18px;pointer-events:none;}
.swarm-topnav-search-wrap input{
  width:100%;background:#F2F1EE;border:1.5px solid transparent;
  border-radius:12px;padding:8px 14px 8px 36px;font-size:14px;
  font-family:var(--font);outline:none;color:#1A1A1A;transition:all .15s;}
.swarm-topnav-search-wrap input:focus{border-color:#F06D1F;background:#fff;}
.swarm-topnav-actions{display:flex;align-items:center;gap:4px;flex-shrink:0;margin-left:auto;}
.swarm-icon-btn{
  width:38px;height:38px;border-radius:50%;border:none;background:transparent;
  cursor:pointer;display:flex!important;align-items:center;justify-content:center;
  color:#5C5C5C!important;text-decoration:none;position:relative;transition:background .15s;}
.swarm-icon-btn:hover{background:#F2F1EE;}
.swarm-avatar-btn{
  width:34px;height:34px;border-radius:50%;overflow:hidden;cursor:pointer;
  border:2px solid #E8E7E3;display:block;flex-shrink:0;transition:border-color .15s;}
.swarm-avatar-btn:hover{border-color:#F06D1F;}
.swarm-avatar-btn img{width:100%;height:100%;object-fit:cover;display:block;}

/* ── MAIN LAYOUT — CSS GRID ── */
.swarm-layout{
  display:grid!important;
  grid-template-columns:minmax(0,1fr);
  gap:20px;max-width:920px;margin:0 auto;
  padding:20px 16px 100px;align-items:start;box-sizing:border-box;}
@media(min-width:1024px){
  .swarm-layout{grid-template-columns:minmax(0,1fr) 280px;padding:24px 20px 100px;}}

/* ── RIGHT PANEL ── */
.swarm-right-panel{
  display:none!important;flex-direction:column;gap:12px;
  position:sticky;top:70px;max-height:calc(100vh - 86px);overflow-y:auto;}
@media(min-width:1024px){.swarm-right-panel{display:flex!important;}}

/* ── CARDS ── */
.swarm-card,.checkin-card,.venue-card,.right-panel-card{
  background:#fff!important;border-radius:16px!important;
  box-shadow:0 1px 3px rgba(0,0,0,.08),0 4px 12px rgba(0,0,0,.05)!important;
  overflow:hidden;}
.swarm-card:hover,.checkin-card:hover,.venue-card:hover{
  box-shadow:0 4px 16px rgba(0,0,0,.12)!important;transform:translateY(-1px);}
.swarm-card-body{padding:16px;}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;
  border-radius:24px;font-weight:700;font-size:13px;cursor:pointer;
  border:1.5px solid transparent;text-decoration:none;transition:all .15s;
  font-family:var(--font);}
.btn-primary{background:#F06D1F!important;color:#fff!important;border-color:#F06D1F!important;}
.btn-primary:hover{background:#D95E10!important;transform:translateY(-1px);}
.btn-ghost{background:transparent;color:#5C5C5C!important;border-color:#E8E7E3;}
.btn-ghost:hover{background:#F8F7F5;}
.btn-sm{padding:6px 14px;font-size:12px;}
.btn-block{width:100%;justify-content:center;}
.btn-lg{padding:13px 24px;font-size:15px;}

/* ── BOTTOM NAV ── */
.swarm-bottom-nav{
  position:fixed;bottom:0;left:0;right:0;z-index:300;
  background:#fff!important;border-top:1.5px solid #E8E7E3;
  display:flex!important;align-items:center;justify-content:space-around;
  padding:6px 8px calc(6px + env(safe-area-inset-bottom));height:60px;}
@media(min-width:1024px){.swarm-bottom-nav{display:none!important;}}
.swarm-nav-item{display:flex;flex-direction:column;align-items:center;
  gap:1px;font-size:9px;font-weight:600;color:#A0A0A0!important;
  text-decoration:none;padding:4px 8px;border-radius:10px;min-width:44px;}
.swarm-nav-item.active{color:#F06D1F!important;}
.swarm-nav-item .material-symbols-outlined{font-size:22px;}
.swarm-nav-fab-item{display:flex;align-items:center;justify-content:center;}
.swarm-nav-fab-inner{width:46px;height:46px;border-radius:50%;
  background:linear-gradient(135deg,#F06D1F,#FFA633)!important;color:#fff!important;
  display:flex;align-items:center;justify-content:center;text-decoration:none;
  box-shadow:0 4px 16px rgba(240,109,31,.4);}

/* ── FAB (DESKTOP) ── */
.swarm-fab{position:fixed;right:24px;bottom:24px;z-index:300;
  width:56px;height:56px;border-radius:50%;
  background:linear-gradient(135deg,#F06D1F,#FFA633)!important;
  color:#fff!important;display:flex;align-items:center;justify-content:center;
  text-decoration:none;box-shadow:0 4px 16px rgba(240,109,31,.4);
  transition:transform .15s;}
.swarm-fab:hover{transform:scale(1.08);}
@media(max-width:1023px){.swarm-fab{display:none!important;}}

/* ── TAILWIND DARK COMPAT ── */
.glass-panel,.swarm-glass-card,.receipt-card,.glass-effect{
  background:#fff!important;backdrop-filter:none!important;border-color:#E8E7E3!important;}
[class*="bg-[#2a"],[class*="bg-[#0b"],[class*="bg-[#13"],[class*="bg-[#06"],
.bg-obsidian,.bg-cyber-dark{background:#fff!important;}
.text-on-surface{color:#1A1A1A!important;}
.text-on-surface-variant{color:#5C5C5C!important;}
.text-white{color:#1A1A1A!important;}
.text-slate-300,.text-slate-200{color:#5C5C5C!important;}
.text-slate-400,.text-slate-500{color:#A0A0A0!important;}
.text-primary,.text-primary-container{color:#F06D1F!important;}
.text-secondary{color:#FFA633!important;}
.bg-surface-container{background:#F8F7F5!important;}
.bg-primary-container{background:#F06D1F!important;color:#fff!important;}
.border-white\/5,.border-white\/10,.border-white\/20{border-color:#E8E7E3!important;}
.scanlines,.map-grid,.cybermap-marker-pulse{display:none!important;}
.backdrop-blur-xl,.backdrop-blur-sm,.backdrop-blur{backdrop-filter:none!important;}

/* ── FLASH MESSAGES ── */
.flash-message{
  position:fixed;bottom:80px;left:50%;transform:translateX(-50%);
  background:#fff;border:1px solid #E8E7E3;border-radius:12px;
  padding:12px 16px;display:flex;align-items:center;gap:10px;
  box-shadow:0 4px 16px rgba(0,0,0,.12);z-index:1000;
  font-size:13px;font-weight:600;min-width:260px;max-width:90vw;
  animation:slideUp .2s ease-out;}
.flash-success{border-color:#16A34A;color:#15803D;}
.flash-error{border-color:#EF4444;color:#DC2626;}
.flash-close{background:none;border:none;cursor:pointer;color:#A0A0A0;margin-left:auto;}
.flash-hide{opacity:0;transition:opacity .3s;pointer-events:none;}
@keyframes slideUp{from{opacity:0;transform:translateX(-50%) translateY(10px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}

/* ── PROGRESS BAR ── */
.progress-bar-track{height:6px;background:#F2F1EE;border-radius:99px;overflow:hidden;}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,#F06D1F,#FFA633);border-radius:99px;transition:width .4s ease;}

/* ── STREAK WIDGET ── */
.streak-widget{display:flex;align-items:center;gap:10px;background:linear-gradient(135deg,#FFF3EB,#FFDDC4);
  border-radius:12px;padding:10px 14px;border:1px solid rgba(240,109,31,.2);}
.streak-widget .material-symbols-outlined{color:#F06D1F;font-size:22px;}

/* ── SECTION LABEL ── */
.swarm-section-label{display:flex;align-items:center;gap:6px;
  font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;
  color:#A0A0A0;padding:0 2px;margin-bottom:6px;}

/* ── SWARM TABS ── */
.swarm-tabs{display:flex;gap:4px;background:#F2F1EE;border-radius:12px;padding:4px;}
.swarm-tab{padding:7px 14px;border-radius:9px;font-size:13px;font-weight:600;
  color:#5C5C5C!important;text-decoration:none;transition:all .15s;white-space:nowrap;}
.swarm-tab.active,.swarm-tab:hover{background:#fff!important;color:#F06D1F!important;
  box-shadow:0 1px 4px rgba(0,0,0,.1);}

/* ── VENUE CARD ── */
.venue-card{display:flex;flex-direction:column;text-decoration:none;color:inherit;
  transition:all .2s;cursor:pointer;}
.venue-card-img{position:relative;background:#F2F1EE;aspect-ratio:4/3;overflow:hidden;}
.venue-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.venue-card:hover .venue-card-img img{transform:scale(1.04);}
.venue-card-cat-badge{position:absolute;bottom:8px;left:8px;width:32px;height:32px;
  border-radius:8px;display:flex;align-items:center;justify-content:center;}
.venue-card-checkin-count{position:absolute;bottom:8px;right:8px;background:rgba(255,255,255,.9);
  border-radius:8px;padding:3px 8px;font-size:11px;font-weight:700;display:flex;
  align-items:center;gap:3px;color:#1A1A1A;}
.venue-card-status{position:absolute;top:8px;right:8px;border-radius:8px;padding:3px 8px;
  font-size:10px;font-weight:700;background:rgba(255,255,255,.9);}
.venue-card-status.open{color:#16A34A;} .venue-card-status.closed{color:#EF4444;}
.venue-card-info{padding:12px 14px;}
.venue-card-name{font-size:14px;font-weight:700;color:#1A1A1A;margin-bottom:3px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.venue-card-cat-label{font-size:11px;font-weight:600;margin-bottom:3px;}
.venue-card-address{font-size:11px;color:#A0A0A0;display:flex;align-items:center;gap:2px;}

/* ── CHECKIN CARD ── */
.checkin-card{display:flex;flex-direction:column;}
.checkin-card-header{display:flex;align-items:flex-start;gap:10px;padding:12px 14px 8px;}
.checkin-card-avatar{width:40px;height:40px;border-radius:50%;overflow:hidden;
  position:relative;flex-shrink:0;background:#F2F1EE;}
.checkin-card-avatar img{width:100%;height:100%;object-fit:cover;}
.checkin-card-cat-dot{position:absolute;bottom:-2px;right:-2px;width:14px;height:14px;
  border-radius:50%;border:2px solid #fff;display:flex;align-items:center;justify-content:center;}
.checkin-card-meta{flex:1;min-width:0;}
.checkin-card-who{font-size:13px;color:#5C5C5C;line-height:1.3;}
.checkin-card-venue{font-size:14px;font-weight:700;display:block;margin-top:1px;line-height:1.3;}
.checkin-card-time{font-size:11px;color:#A0A0A0;white-space:nowrap;}
.checkin-card-note{padding:0 14px 10px;font-size:13px;color:#5C5C5C;
  font-style:italic;background:#FAFAF9;margin:0 14px;border-radius:10px;padding:8px 12px;}
.checkin-card-photo{width:100%;max-height:280px;object-fit:cover;display:block;}
.checkin-card-footer{display:flex;align-items:center;justify-content:space-between;
  padding:8px 14px 12px;border-top:1px solid #F2F1EE;margin-top:6px;}
.checkin-card-venue-link{display:flex;align-items:center;gap:5px;font-size:11px;
  font-weight:600;color:#A0A0A0;text-decoration:none;}
.checkin-card-actions{display:flex;align-items:center;gap:8px;}
.checkin-action-btn{display:flex;align-items:center;gap:4px;background:none;border:none;
  cursor:pointer;font-size:12px;font-weight:600;color:#A0A0A0;padding:4px;
  transition:color .15s;font-family:var(--font);}
.checkin-action-btn:hover,.checkin-action-btn.liked{color:#F06D1F;}

/* ── RIGHT PANEL CARD ── */
.right-panel-card{background:#fff;border-radius:14px;
  box-shadow:0 1px 3px rgba(0,0,0,.08),0 2px 8px rgba(0,0,0,.04);overflow:hidden;}
.right-panel-card-header{padding:12px 14px 0;font-size:11px;font-weight:700;
  text-transform:uppercase;letter-spacing:0.8px;color:#A0A0A0;}
.right-panel-card-body{padding:10px 14px 14px;}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:48px 24px;}
.empty-state-icon{font-size:48px;color:#D0CEC9;display:block;margin-bottom:12px;}
.empty-state-title{font-size:15px;font-weight:700;color:#5C5C5C;margin:0 0 8px;}

/* ── AD CARD ── */
.ad-card-label{background:#F06D1F;color:#fff;font-size:9px;font-weight:700;
  padding:2px 6px;border-radius:4px;text-transform:uppercase;}

/* ── MISC ── */
.swarm-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
@keyframes modalIn{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:scale(1)}}
@keyframes streakPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}
.streak-fire{animation:streakPulse 1.5s ease-in-out infinite;}
</style>

<script defer src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</head>
<body>

<?php if (Auth::check() && isset($currentUser)): ?>

<!-- ── TOP NAV ── -->
<nav class="swarm-topnav" id="swarm-topnav">

    <a href="<?php echo BASE_URL; ?>/dashboard" class="swarm-topnav-logo">
        <span class="material-symbols-outlined" style="font-size:22px;font-variation-settings:'FILL' 1;">hive</span>
        Sociaera
    </a>

    <div class="swarm-topnav-search-wrap">
        <span class="material-symbols-outlined search-icon">search</span>
        <input type="text" placeholder="Mekan ara…"
               onkeydown="if(event.key==='Enter')window.location.href='<?php echo BASE_URL; ?>/venues?q='+encodeURIComponent(this.value)"/>
    </div>

    <div class="swarm-topnav-actions">
        <a href="<?php echo BASE_URL; ?>/notifications" class="swarm-icon-btn" aria-label="Bildirimler">
            <span class="material-symbols-outlined" style="font-size:22px;<?php echo $notifCount > 0 ? "color:#F06D1F;font-variation-settings:'FILL' 1;" : ''; ?>">notifications</span>
            <?php if ($notifCount > 0): ?>
            <span style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:#F06D1F;border-radius:50%;border:2px solid #fff;"></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo BASE_URL; ?>/settings" class="swarm-icon-btn" aria-label="Ayarlar">
            <span class="material-symbols-outlined" style="font-size:20px;">settings</span>
        </a>

        <!-- Avatar + dropdown -->
        <div style="position:relative;">
            <button class="swarm-avatar-btn" onclick="document.getElementById('nav-dropdown').classList.toggle('nav-dropdown-open')" style="border:none;padding:0;cursor:pointer;">
                <img src="<?php echo $avatarUrl; ?>" alt="Profil" width="34" height="34"/>
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

