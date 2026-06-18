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
if (!class_exists('AdModel'))          { require_once $baseDir . '/app/Models/Ad.php'; }

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


<!-- Swarm Design System -->
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
@media(min-width:1200px){
  .swarm-layout{
    grid-template-columns:260px minmax(0,1fr) 280px;
    max-width:1280px;
  }
}

/* ── LEFT SIDEBAR ── */
.swarm-left-sidebar{
  display:none!important;flex-direction:column;gap:12px;
  position:sticky;top:70px;max-height:calc(100vh - 86px);overflow-y:auto;}
@media(min-width:1200px){.swarm-left-sidebar{display:flex!important;}}

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
<nav id="swarm-topnav" style="
  position:sticky;top:0;z-index:9000;
  background:#ffffff;
  border-bottom:2px solid #E8E7E3;
  box-shadow:0 2px 12px rgba(0,0,0,0.07);
  display:flex;align-items:center;gap:0;
  padding:0 20px;min-height:58px;
  font-family:'Plus Jakarta Sans','Inter',sans-serif;
">

    <!-- Logo -->
    <a href="<?php echo BASE_URL; ?>/dashboard" style="
      font-weight:800;font-size:18px;color:#F06D1F;
      text-decoration:none;display:flex;align-items:center;gap:8px;
      flex-shrink:0;letter-spacing:-0.3px;white-space:nowrap;
      padding-right:20px;margin-right:4px;border-right:1.5px solid #F2F1EE;">
        <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Sociaera Logo" style="height:28px; width:28px; object-fit:contain;" height="28" width="28">
        Sociaera
    </a>

    <!-- Ana Menü Linkleri (desktop) -->
    <?php
    $navLinks = [
        'dashboard'   => ['icon'=>'home',                    'label'=>'Ana Sayfa',     'url'=>'/dashboard'],
        'activity'    => ['icon'=>'explore',                  'label'=>'Keşfet',        'url'=>'/activity'],
        'venues'      => ['icon'=>'place',                    'label'=>'Mekanlar',      'url'=>'/venues'],
        'leaderboard' => ['icon'=>'leaderboard',              'label'=>'Sıralama',      'url'=>'/leaderboard'],
        'members'     => ['icon'=>'group',                    'label'=>'Üyeler',        'url'=>'/members'],
        'missions'    => ['icon'=>'military_tech',            'label'=>'Görevler',      'url'=>'/missions'],
        'kampanyalar' => ['icon'=>'campaign',                 'label'=>'Kampanyalar',   'url'=>'/kampanyalar'],
        'wallet'      => ['icon'=>'account_balance_wallet',   'label'=>'Cüzdan',        'url'=>'/wallet'],
        'premium'     => ['icon'=>'workspace_premium',        'label'=>'Premium',       'url'=>'/premium'],
    ];
    $currentNav = $activeNav ?? '';
    foreach ($navLinks as $key => $nl):
        $isActive = ($currentNav === $key);
        $isPremiumLink = ($key === 'premium');
    ?>
    <a href="<?php echo BASE_URL . $nl['url']; ?>"
       style="display:flex;align-items:center;gap:6px;padding:0 11px;height:58px;
              font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;
              border-bottom:3px solid <?php echo $isActive ? '#F06D1F' : 'transparent'; ?>;
              color:<?php echo $isPremiumLink ? '#F59E0B' : ($isActive ? '#F06D1F' : '#5C5C5C'); ?>;
              transition:color .15s,border-color .15s;"
       onmouseover="if(!this.style.borderBottomColor.includes('F06D1F')){this.style.color='<?php echo $isPremiumLink ? '#D97706' : '#F06D1F'; ?>';}"
       onmouseout="<?php echo $isActive ? '' : "this.style.color='" . ($isPremiumLink ? '#F59E0B' : '#5C5C5C') . "';"; ?>">
        <span class="material-symbols-outlined" style="font-size:18px;<?php echo $isPremiumLink ? "font-variation-settings:'FILL' 1;" : ''; ?>"><?php echo $nl['icon']; ?></span>
        <span class="nav-label" style="display:none;"><?php echo $nl['label']; ?></span>
    </a>
    <?php endforeach; ?>

    <?php /* ── Yakında: Gizli Müşteri ── */ ?>
    <span title="Yakında geliyor!"
          style="display:flex;align-items:center;gap:6px;padding:0 11px;height:58px;
                 font-size:13px;font-weight:700;white-space:nowrap;cursor:not-allowed;
                 color:#C0C0C0;border-bottom:3px solid transparent;position:relative;"
          onmouseover="this.querySelector('.nav-coming-soon').style.display='block'"
          onmouseout="this.querySelector('.nav-coming-soon').style.display='none'">
        <span class="material-symbols-outlined" style="font-size:18px;">person_search</span>
        <span class="nav-label" style="display:none;">Gizli Müşteri</span>
        <span class="nav-coming-soon" style="display:none;position:absolute;top:46px;left:50%;transform:translateX(-50%);
               background:#1A1A1A;color:#fff;font-size:11px;font-weight:600;padding:5px 10px;
               border-radius:8px;white-space:nowrap;z-index:999;pointer-events:none;
               box-shadow:0 4px 12px rgba(0,0,0,.2);">🚧 Yakında geliyor!</span>
    </span>

    <style>
    @media(min-width:1100px){ nav.swarm-topnav .nav-label, #swarm-topnav .nav-label { display:inline!important; } }
    </style>

    <!-- Spacer -->
    <div style="flex:1;"></div>

    <!-- Arama -->
    <div style="position:relative;max-width:240px;width:100%;margin-right:8px;">
        <span class="material-symbols-outlined" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#A0A0A0;font-size:17px;pointer-events:none;">search</span>
        <input type="text" placeholder="Mekan ara…"
               onkeydown="if(event.key==='Enter')window.location.href='<?php echo BASE_URL; ?>/venues?q='+encodeURIComponent(this.value)"
               style="width:100%;background:#F2F1EE;border:1.5px solid transparent;border-radius:10px;
                      padding:7px 12px 7px 32px;font-size:13px;font-family:inherit;
                      outline:none;color:#1A1A1A;box-sizing:border-box;"
               onfocus="this.style.borderColor='#F06D1F';this.style.background='#fff'"
               onblur="this.style.borderColor='transparent';this.style.background='#F2F1EE'"/>
    </div>

    <!-- Bildirimler -->
    <a href="<?php echo BASE_URL; ?>/notifications" aria-label="Bildirimler" style="
      width:38px;height:38px;border-radius:50%;flex-shrink:0;
      display:flex;align-items:center;justify-content:center;
      color:#5C5C5C;text-decoration:none;position:relative;margin-right:2px;">
        <span class="material-symbols-outlined" style="font-size:22px;<?php echo $notifCount > 0 ? "color:#F06D1F;font-variation-settings:'FILL' 1;" : ''; ?>">notifications</span>
        <?php if ($notifCount > 0): ?>
        <span style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:#F06D1F;border-radius:50%;border:2px solid #fff;"></span>
        <?php endif; ?>
    </a>

    <!-- Avatar + Dropdown -->
    <div style="position:relative;margin-left:4px;">
        <button id="nav-avatar-btn" onclick="
          var dd=document.getElementById('nav-dropdown');
          dd.style.display=(dd.style.display==='block')?'none':'block';"
          style="width:36px;height:36px;border-radius:50%;overflow:hidden;cursor:pointer;
                 border:2px solid #E8E7E3;padding:0;background:none;flex-shrink:0;">
            <img src="<?php echo $avatarUrl; ?>" alt="Profil" width="36" height="36"
                 style="width:100%;height:100%;object-fit:cover;display:block;"/>
        </button>

        <div id="nav-dropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);
          width:210px;background:#fff;border:1.5px solid #E8E7E3;border-radius:14px;
          box-shadow:0 8px 24px rgba(0,0,0,0.12);padding:6px;z-index:9999;">
            <?php
            $ddLinks = [
                ['url'=>'/profile',         'icon'=>'person',                 'label'=>'Profilim',          'color'=>'#F06D1F'],
                ['url'=>'/wallet',           'icon'=>'account_balance_wallet', 'label'=>'Cüzdanım',          'color'=>'#F06D1F'],
                ['url'=>'/add-venue',        'icon'=>'add_business',           'label'=>'Mekan Öner/Ekle',   'color'=>'#10b981'],
                ['url'=>'/missions',         'icon'=>'military_tech',          'label'=>'Görevler',          'color'=>'#F06D1F'],
                ['url'=>'/kampanyalar',      'icon'=>'campaign',               'label'=>'Kampanyalar',       'color'=>'#F06D1F'],
                ['url'=>'/premium',          'icon'=>'diamond',                'label'=>'Premium',           'color'=>'#4F46E5'],
                ['url'=>'/mystery-shopper',  'icon'=>'visibility_off',         'label'=>'Gizli Müşteri',     'color'=>'#A0A0A0'],
                ['url'=>'/character-select', 'icon'=>'switch_account',         'label'=>'Karakter Değiştir', 'color'=>'#A0A0A0'],
                ['url'=>'/settings',         'icon'=>'settings',               'label'=>'Ayarlar',           'color'=>'#A0A0A0'],
            ];
            foreach($ddLinks as $dl): ?>
            <a href="<?php echo BASE_URL.$dl['url']; ?>"
               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;
                      text-decoration:none;color:#1A1A1A;font-size:13px;font-weight:600;"
               onmouseover="this.style.background='#F8F7F5'" onmouseout="this.style.background=''">
                <span class="material-symbols-outlined" style="font-size:18px;color:<?php echo $dl['color']; ?>;"><?php echo $dl['icon']; ?></span>
                <?php echo $dl['label']; ?>
            </a>
            <?php endforeach; ?>
            <hr style="border:none;border-top:1px solid #F2F1EE;margin:4px 0;"/>
            <a href="#" onclick="App.openReportModal('system', 0); return false;"
               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;
                      text-decoration:none;color:#A0A0A0;font-size:13px;font-weight:600;"
               onmouseover="this.style.background='#F8F7F5'" onmouseout="this.style.background=''">
                <span class="material-symbols-outlined" style="font-size:18px;">bug_report</span>
                Geri Bildirim / Hata
            </a>
            <hr style="border:none;border-top:1px solid #F2F1EE;margin:4px 0;"/>
            <a href="<?php echo BASE_URL; ?>/logout"
               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;
                      text-decoration:none;color:#EF4444;font-size:13px;font-weight:600;"
               onmouseover="this.style.background='#FEF2F2'" onmouseout="this.style.background=''">
                <span class="material-symbols-outlined" style="font-size:18px;">logout</span>
                Çıkış Yap
            </a>
        </div>
    </div>

</nav>

<?php if (!empty($hideSidebar)): ?>
<!-- Full-width centered layout (no sidebars) -->
<div style="max-width:720px;margin:0 auto;padding:24px 16px 100px;">
<?php else: ?>
<!-- ── MAIN LAYOUT ────────────────────────────────────────── -->
<div class="swarm-layout">
    
    <!-- ── SOL SIDEBAR (Sponsorlar ve Reklamlar) ────────────────── -->
    <?php
    $leftAds = [];
    $sidebarRightAds = [];
    try {
        if (class_exists('AdModel')) {
            $adModel = new AdModel();
            $leftAds = $adModel->getByPosition('carousel', 6);
            $sidebarRightAds = $adModel->getByPosition('sidebar_right', 1);
        }
    } catch (Exception $e) {}
    ?>
    <aside class="swarm-left-sidebar" style="display:flex; flex-direction:column; gap:16px; box-sizing:border-box;">
        <!-- ═══ Sponsor Carousel (White Card) ═══ -->
        <div class="right-panel-card" style="padding:16px; background:#ffffff; border:1.5px solid var(--border); border-radius:16px; display:flex; flex-direction:column; gap:12px; position:relative; box-sizing:border-box; overflow:hidden;">
            <div style="font-size:12px; font-weight:800; color:var(--text-1); letter-spacing:-0.2px; font-family:var(--font); padding-left:2px;">
                Sponsorlarımız
            </div>

            <?php
            // Collect all items to slide (either ads or mock sponsors)
            $sliderItems = [];
            if (!empty($leftAds)) {
                foreach ($leftAds as $lAd) {
                    $sliderItems[] = [
                        'type' => 'ad',
                        'title' => $lAd['title'],
                        'image' => $lAd['image_url'],
                        'media_type' => $lAd['media_type'] ?? 'image',
                        'url' => $lAd['link_url'],
                        'badge' => 'Sponsor'
                    ];
                }
            } else {
                $mockSponsors = [
                    ['name' => 'Tequi-la-la — Cuma Geceleri Özel Kampanyası', 'logo' => BASE_URL . '/assets/img/sponsors/colosseum.png', 'desc' => 'Sponsor', 'url' => 'https://face-tr.gta.world/page/colosseum'],
                    ['name' => 'Paradise Group — Lüks ve Eğlence Sponsoru', 'logo' => BASE_URL . '/assets/img/sponsors/paradise-group.png', 'desc' => 'Sponsor', 'url' => 'https://face-tr.gta.world/page/paradise'],
                ];
                foreach ($mockSponsors as $mock) {
                    $sliderItems[] = [
                        'type' => 'sponsor',
                        'title' => $mock['name'],
                        'image' => $mock['logo'],
                        'media_type' => 'image',
                        'url' => $mock['url'],
                        'badge' => $mock['desc']
                    ];
                }
            }
            ?>

            <!-- Carousel Container (Padded to isolate arrows) -->
            <div class="carousel-container" style="position:relative; width:100%; overflow:hidden;">
                <!-- Track -->
                <div class="carousel-track" style="display:flex; transition:transform 0.4s ease-in-out; width:100%;">
                    <?php foreach ($sliderItems as $index => $item): ?>
                    <div class="carousel-slide-item" style="flex:0 0 100%; width:100%; box-sizing:border-box; padding:0 24px;">
                        <a href="<?php echo escape($item['url'] ?? '#'); ?>" target="_blank" rel="noopener" 
                           style="display:block; background:linear-gradient(135deg, #0f2b46, #1a365d); border-radius:12px; height:120px; padding:16px; box-sizing:border-box; text-decoration:none; position:relative; overflow:hidden;">
                            
                            <!-- Media Background -->
                            <?php 
                            $bgUrl = escape($item['image'] ?? '');
                            $mType = $item['media_type'] ?? 'image';
                            if ($mType === 'youtube' && $bgUrl): 
                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $bgUrl, $match);
                                $ytId = $match[1] ?? '';
                                if ($ytId):
                            ?>
                                <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?php echo $ytId; ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo $ytId; ?>" frameborder="0" style="position:absolute; inset:0; pointer-events:none;"></iframe>
                            <?php endif; elseif ($mType === 'video' && $bgUrl): ?>
                                <video src="<?php echo $bgUrl; ?>" autoplay muted loop playsinline style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;"></video>
                            <?php elseif ($bgUrl): ?>
                                <div style="position:absolute; inset:0; background:url('<?php echo $bgUrl; ?>') center/cover no-repeat, linear-gradient(135deg, #1a365d, #0f2b46);"></div>
                            <?php endif; ?>
                            
                            <!-- Gradient Overlay -->
                            <div style="position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 100%);"></div>

                            <!-- Content -->
                            <span style="position:absolute; top:12px; left:12px; font-size:7px; font-weight:800; background:#f06d1f; color:#fff; padding:3px 6px; border-radius:10px; text-transform:uppercase; letter-spacing:0.5px; z-index:2;"><?php echo escape($item['badge'] ?? 'SPONSOR'); ?></span>
                            
                            <div style="position:absolute; bottom:16px; left:16px; right:16px; z-index:2; color:#ffffff; font-family:var(--font);">
                                <div style="font-size:12px; font-weight:800; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; text-overflow:ellipsis; text-shadow:0 1px 4px rgba(0,0,0,0.8);">
                                    <?php echo escape($item['title']); ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Navigation Arrows (Positioned in padding area outside the slides) -->
                <button type="button" class="carousel-nav-btn prev" style="position:absolute; left:0px; top:50%; transform:translateY(-50%); width:24px; height:24px; border-radius:50%; background:#ffffff; border:1px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,0.15); z-index:10; color:var(--text-2); padding:0;">
                    <span class="material-symbols-outlined" style="font-size:14px; font-weight:bold;">chevron_left</span>
                </button>
                <button type="button" class="carousel-nav-btn next" style="position:absolute; right:0px; top:50%; transform:translateY(-50%); width:24px; height:24px; border-radius:50%; background:#ffffff; border:1px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,0.15); z-index:10; color:var(--text-2); padding:0;">
                    <span class="material-symbols-outlined" style="font-size:14px; font-weight:bold;">chevron_right</span>
                </button>
            </div>

            <!-- Dots -->
            <div class="carousel-dots" style="display:flex; justify-content:center; gap:6px; margin-top:2px;">
                <?php foreach ($sliderItems as $index => $item): ?>
                <span class="carousel-dot" data-slide-index="<?php echo $index; ?>" style="width:6px; height:6px; border-radius:50%; background:#dcdcdc; cursor:pointer; transition:all 0.2s;"></span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ═══ Tall Vertical Banner (Reklam Alanı) ═══ -->
        <?php if (!empty($sidebarRightAds)): $rAd = $sidebarRightAds[0]; ?>
            <div class="right-panel-card" style="background:linear-gradient(150deg, #0f2b46 0%, #1a365d 35%, #0f172a 100%) !important; border:none; border-radius:16px; padding:0; box-sizing:border-box; min-height:300px; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; position:relative; overflow:hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <?php 
                $bgUrl = escape($rAd['image_url'] ?? '');
                $mType = $rAd['media_type'] ?? 'image';
                if ($mType === 'youtube' && $bgUrl): 
                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $bgUrl, $match);
                    $ytId = $match[1] ?? '';
                    if ($ytId):
                ?>
                    <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?php echo $ytId; ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo $ytId; ?>" frameborder="0" style="position:absolute; inset:0; pointer-events:none;"></iframe>
                <?php endif; elseif ($mType === 'video' && $bgUrl): ?>
                    <video src="<?php echo $bgUrl; ?>" autoplay muted loop playsinline style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;"></video>
                <?php elseif ($bgUrl): ?>
                    <div style="position:absolute; inset:0; background:url('<?php echo $bgUrl; ?>') center/cover no-repeat;"></div>
                <?php endif; ?>

                <a href="<?php echo escape($rAd['link_url'] ?? '#'); ?>" target="_blank" rel="noopener" style="position:absolute; inset:0; z-index:10; display:flex; flex-direction:column; justify-content:flex-end; padding:20px; background:linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 60%); text-decoration:none;">
                    <span style="position:absolute; top:12px; left:12px; font-size:9px; font-weight:800; background:var(--color-primary); color:#fff; padding:4px 8px; border-radius:12px; text-transform:uppercase; letter-spacing:0.5px; box-shadow:0 2px 4px rgba(0,0,0,0.5);">REKLAM</span>
                    <div style="color:#ffffff; font-size:16px; font-weight:800; text-align:left; text-shadow:0 2px 6px rgba(0,0,0,0.9);"><?php echo escape($rAd['title'] ?? ''); ?></div>
                </a>
            </div>
        <?php else: ?>
            <div class="right-panel-card" style="border:none; border-radius:16px; padding:0; box-sizing:border-box; min-height:300px; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; position:relative; overflow:hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <!-- Fallback SparkLS Image -->
                <div style="position:absolute; inset:0; background:url('<?php echo BASE_URL; ?>/assets/img/sponsors/sparkls.jpg') center/cover no-repeat, linear-gradient(135deg, #1a365d, #0f2b46);"></div>
                
                <!-- Link Overlay -->
                <a href="https://sparkls.online" target="_blank" rel="noopener" style="position:absolute; inset:0; z-index:10; display:flex; flex-direction:column; justify-content:flex-end; padding:20px; background:linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 50%); text-decoration:none;">
                    <span style="position:absolute; top:12px; left:12px; font-size:9px; font-weight:800; background:var(--color-primary); color:#fff; padding:4px 8px; border-radius:12px; text-transform:uppercase; letter-spacing:0.5px; box-shadow:0 2px 4px rgba(0,0,0,0.5);">REKLAM</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- ═══ Brand Contact / Support Widget (Sitenin Renklerinde Orange) ═══ -->
        <div class="sidebar-widget" style="background:linear-gradient(135deg, #F06D1F 0%, #d8570e 100%); box-shadow: 0 4px 16px rgba(240,109,31,0.22); border:none; border-radius:16px; padding:18px; box-sizing:border-box; display:flex; flex-direction:column; gap:16px; position:relative; overflow:hidden;">
            <!-- Subtle background circle decorator -->
            <div style="position:absolute; right:-15px; top:-15px; width:60px; height:60px; border-radius:50%; background:rgba(255,255,255,0.08); pointer-events:none;"></div>
            
            <!-- List Section -->
            <div style="display:flex; flex-direction:column; gap:8px; width:100%; z-index:2; position:relative;">
                <!-- Single Clickable Facebrowser Card -->
                <a href="https://face-tr.gta.world/page/sociaera" target="_blank" rel="noopener" style="display:flex; align-items:center; gap:8px; text-decoration:none; color:#ffffff; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:10px; transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.transform='translateX(3px)';" onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.transform='translateX(0)';">
                    <div style="width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <span class="material-symbols-outlined" style="font-size:16px; color:#ffffff;">language</span>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:3px;">
                            <span style="font-size:11px; font-weight:800; color:#ffffff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Sociaera Facebrowser</span>
                            <span class="material-symbols-outlined" style="font-size:12px; color:#ffd54f; font-variation-settings:'FILL' 1;">verified</span>
                        </div>
                        <div style="font-size:9px; color:rgba(255,255,255,0.7); margin-top:2px;">Bizi takip et!</div>
                    </div>
                    <span class="material-symbols-outlined" style="font-size:14px; color:rgba(255,255,255,0.5);">chevron_right</span>
                </a>
            </div>
        </div>

        <!-- Carousel Script -->
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const track = document.querySelector(".carousel-track");
            const slides = Array.from(track.children);
            const dots = document.querySelectorAll(".carousel-dot");
            const nextBtn = document.querySelector(".carousel-nav-btn.next");
            const prevBtn = document.querySelector(".carousel-nav-btn.prev");
            
            if (slides.length <= 1) {
                if (nextBtn) nextBtn.style.display = "none";
                if (prevBtn) prevBtn.style.display = "none";
                return;
            }
            
            let currentIndex = 0;
            let autoPlayTimer = null;
            
            function updateCarousel(index) {
                if (index < 0) index = slides.length - 1;
                if (index >= slides.length) index = 0;
                
                currentIndex = index;
                track.style.transform = `translateX(-${currentIndex * 100}%)`;
                
                dots.forEach(dot => dot.style.background = "#dcdcdc");
                if (dots[currentIndex]) {
                    dots[currentIndex].style.background = "#F06D1F";
                }
            }
            
            function startAutoPlay() {
                stopAutoPlay();
                autoPlayTimer = setInterval(() => {
                    updateCarousel(currentIndex + 1);
                }, 5000);
            }
            
            function stopAutoPlay() {
                if (autoPlayTimer) clearInterval(autoPlayTimer);
            }
            
            if (nextBtn) {
                nextBtn.addEventListener("click", () => {
                    updateCarousel(currentIndex + 1);
                    startAutoPlay();
                });
            }
            
            if (prevBtn) {
                prevBtn.addEventListener("click", () => {
                    updateCarousel(currentIndex - 1);
                    startAutoPlay();
                });
            }
            
            dots.forEach(dot => {
                dot.addEventListener("click", (e) => {
                    const index = parseInt(e.target.getAttribute("data-slide-index"));
                    updateCarousel(index);
                    startAutoPlay();
                });
            });
            
            updateCarousel(0);
            startAutoPlay();
        });
        </script>
    </aside>

<?php endif; // hideSidebar ?>

    <!-- Feed area starts — page content renders directly here -->

<?php else: ?>
<!-- Guest: sadece içerik alanı -->
<main>
<?php endif; ?>

