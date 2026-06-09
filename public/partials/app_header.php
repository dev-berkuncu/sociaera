<?php
/**
 * Sociaera — App Header (Tailwind Design)
 */

// Load essential models if they aren't loaded yet
$baseDir = dirname(__DIR__, 2);
if (!class_exists('UserModel')) {
    require_once $baseDir . '/app/Models/User.php';
}
if (!class_exists('VenueModel')) {
    require_once $baseDir . '/app/Models/Venue.php';
}
if (!class_exists('CheckinModel')) {
    require_once $baseDir . '/app/Models/Checkin.php';
}
if (!class_exists('NotificationModel')) {
    require_once $baseDir . '/app/Models/Notification.php';
}
if (!class_exists('WalletModel')) {
    require_once $baseDir . '/app/Models/Wallet.php';
}

if (!isset($currentUser) && Auth::check()) {
    $currentUser = (new UserModel())->getById(Auth::id());
}
?>
<!DOCTYPE html>
<html class="dark" lang="tr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="<?php echo csrfToken(); ?>"/>
<style>body{opacity:0}body.ready{opacity:1;transition:opacity .15s ease-in}</style>
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title($pageTitle ?? 'Sociaera'); ?></title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-tertiary-fixed-variant": "#6900b3",
                        "on-primary-fixed-variant": "#6c3a00",
                        "error": "#ffb4ab",
                        "secondary-fixed": "#ffdadb",
                        "background": "#131314",
                        "surface-bright": "#39393a",
                        "tertiary-container": "#cb95ff",
                        "on-surface": "#e5e2e3",
                        "on-secondary-fixed": "#40000d",
                        "on-tertiary-fixed": "#2c0051",
                        "on-background": "#e5e2e3",
                        "surface": "#131314",
                        "secondary": "#ffb2b7",
                        "on-error": "#690005",
                        "surface-container-low": "#1c1b1c",
                        "tertiary-fixed-dim": "#ddb7ff",
                        "on-tertiary": "#490080",
                        "error-container": "#93000a",
                        "inverse-on-surface": "#313031",
                        "on-surface-variant": "#dcc2ae",
                        "on-secondary-fixed-variant": "#92002a",
                        "surface-container": "#201f20",
                        "inverse-surface": "#e5e2e3",
                        "primary-fixed": "#ffdcc1",
                        "tertiary-fixed": "#f0dbff",
                        "secondary-container": "#b50036",
                        "primary-fixed-dim": "#ffb778",
                        "secondary-fixed-dim": "#ffb2b7",
                        "on-secondary": "#67001b",
                        "surface-tint": "#ffb778",
                        "inverse-primary": "#8e4e00",
                        "on-tertiary-container": "#6000a4",
                        "primary": "#ffb97c",
                        "on-error-container": "#ffdad6",
                        "surface-container-high": "#2a2a2b",
                        "tertiary": "#deb9ff",
                        "surface-variant": "#353436",
                        "primary": "#ff6a00",
                        "secondary": "#00f0ff",
                        "tertiary": "#a855f7",
                        "background": "#060a13",
                        "surface": "#0b0c10",
                        "on-primary": "#ffffff",
                        "on-secondary": "#0b0c10",
                        "on-tertiary": "#ffffff",
                        "on-surface": "#f1f5f9",
                        "on-surface-variant": "#94a3b8",
                        "surface-container": "rgba(11, 16, 26, 0.65)",
                        "surface-container-high": "rgba(16, 20, 30, 0.45)",
                        "surface-container-low": "#0b0c10",
                        "surface-container-lowest": "#040810",
                        "surface-container-highest": "rgba(255, 255, 255, 0.08)",
                        "outline": "rgba(255, 255, 255, 0.08)",
                        "outline-variant": "rgba(255, 255, 255, 0.05)",
                        "primary-container": "rgba(255, 106, 0, 0.15)",
                        "secondary-container": "rgba(0, 240, 255, 0.15)",
                        "tertiary-container": "rgba(168, 85, 247, 0.15)",
                        "cyber": {
                            "bg": "#060a13",
                            "dark": "#0b0c10",
                            "panel": "rgba(11, 16, 26, 0.65)",
                            "glass": "rgba(16, 20, 30, 0.45)",
                            "orange": "#ff6a00",
                            "orangeLight": "#ff9100",
                            "cyan": "#00f0ff",
                            "purple": "#a855f7",
                            "pink": "#ec4899",
                            "green": "#10b981",
                            "border": "rgba(255, 255, 255, 0.06)",
                            "borderGlow": "rgba(255, 106, 0, 0.15)"
                        }
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "stack-lg": "32px",
                        "stack-sm": "8px",
                        "container-margin": "24px",
                        "stack-md": "16px",
                        "unit": "4px",
                        "gutter": "16px",
                        "md": "16px",
                        "base": "4px",
                        "margin": "24px",
                        "xl": "32px",
                        "sm": "12px",
                        "xs": "8px",
                        "lg": "24px",
                        "container-padding": "32px"
                    },
                    "fontFamily": {
                        "sans": ["Outfit", "sans-serif"],
                        "mono": ["Share Tech Mono", "Courier New", "monospace"],
                        "headline-lg-mobile": ["Outfit"],
                        "label-caps": ["Share Tech Mono"],
                        "title-md": ["Outfit"],
                        "body-sm": ["Outfit"],
                        "display-lg": ["Outfit"],
                        "headline-lg": ["Outfit"],
                        "body-lg": ["Outfit"],
                        "headline-md": ["Outfit"],
                        "body-md": ["Outfit"],
                        "label-md": ["Share Tech Mono"],
                        "headline-sm": ["Outfit"],
                        "label-sm": ["Outfit"]
                    },
                    "fontSize": {
                        "headline-lg-mobile": ["24px", {"lineHeight": "1.2", "fontWeight": "700"}],
                        "label-caps": ["12px", {"lineHeight": "1", "letterSpacing": "0.1em", "fontWeight": "700"}],
                        "title-md": ["20px", {"lineHeight": "1.4", "fontWeight": "600"}],
                        "body-sm": ["14px", {"lineHeight": "1.5", "fontWeight": "400"}],
                        "display-lg": ["48px", {"lineHeight": "1.1", "letterSpacing": "-0.02em", "fontWeight": "800"}],
                        "headline-lg": ["32px", {"lineHeight": "1.2", "fontWeight": "700"}],
                        "body-lg": ["16px", {"lineHeight": "1.6", "fontWeight": "400"}],
                        "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "500"}],
                        "headline-sm": ["16px", {"lineHeight": "24px", "fontWeight": "600"}],
                        "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "400"}]
                    }
                },
            },
        }
    </script>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/stitch.css"/>
<script>
(function(){
    var t=setTimeout(function(){document.body.classList.add('ready')},1500);
    if(document.fonts&&document.fonts.ready){
        document.fonts.ready.then(function(){clearTimeout(t);document.body.classList.add('ready')});
    } else {
        clearTimeout(t);document.body.classList.add('ready');
    }
})();
</script>
<script defer src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</head>
<body class="bg-obsidian text-on-surface font-body-sm min-h-screen antialiased selection:bg-primary selection:text-on-primary dark overflow-x-hidden relative">

<!-- Screen Overlay Scanlines -->
<div class="scanlines"></div>

<!-- ── MAP-FIRST WEB LAYOUT: Fullscreen Background Map ── -->
<div class="fixed inset-0 z-0 overflow-hidden pointer-events-none bg-[#040810]">
    <!-- Stylized Los Santos Neon Map (SVG & Canvas based grid for crisp look) -->
    <svg class="absolute inset-0 w-full h-full opacity-35 select-none" xmlns="http://www.w3.org/2000/svg" id="tactical-map">
        <defs>
            <radialGradient id="map-glow" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#00f0ff" stop-opacity="0.15"/>
                <stop offset="100%" stop-color="#060a13" stop-opacity="0"/>
            </radialGradient>
        </defs>
        <!-- Map Vignette Glow -->
        <rect width="100%" height="100%" fill="url(#map-glow)" />
        
        <!-- Stylized roads / topography paths representing Los Santos -->
        <!-- Major Highway routes (styled as neon paths) -->
        <path d="M -100 800 C 300 600, 500 800, 900 650 S 1400 300, 2200 400" fill="none" stroke="rgba(0, 240, 255, 0.12)" stroke-width="3" stroke-dasharray="10 5" />
        <path d="M 400 -100 C 600 300, 300 700, 800 1200" fill="none" stroke="rgba(255, 106, 0, 0.08)" stroke-width="2.5" />
        <path d="M 1200 -100 C 900 400, 1100 800, 1500 1300" fill="none" stroke="rgba(0, 240, 255, 0.08)" stroke-width="2" />
        <path d="M -100 300 C 500 200, 800 500, 2100 100" fill="none" stroke="rgba(168, 85, 247, 0.08)" stroke-width="1.5" />

        <!-- Coastline/Water outline shape -->
        <path d="M -200 1500 Q 300 1100, 500 900 T 1000 850 T 1600 950 Q 1900 1200, 2200 1500" fill="rgba(0, 240, 255, 0.015)" stroke="rgba(0, 240, 255, 0.2)" stroke-width="1" />

        <!-- Grid Coordinates & Crosshairs overlay -->
        <g class="text-[9px] font-mono" style="fill: rgba(0, 240, 255, 0.4);">
            <text x="20" y="30">LOC: 34.0522° N, 118.2437° W</text>
            <text x="20" y="45">GRID: Z-294.04 (LS-METRO)</text>
            <text x="20" y="60">SYS: LINK_OK // SE_PORTAL_ACTIVE</text>
            
            <!-- Crosshair marks -->
            <path d="M 100 100 L 120 100 M 110 90 L 110 110" stroke="rgba(0, 240, 255, 0.2)" stroke-width="1" />
            <circle cx="110" cy="100" r="15" fill="none" stroke="rgba(0, 240, 255, 0.1)" stroke-width="0.5" />
        </g>
    </svg>

    <!-- Dynamic Map Grid overlay -->
    <div class="absolute inset-0 map-grid opacity-40 z-0 pointer-events-none"></div>
</div>

<?php if (Auth::check() && $currentUser): ?>
<!-- Top Navigation Bar -->
<?php
$walletBalance = 0.0;
$streak = 0;
$weeklyCheckins = 0;
$followingUsers = [];
try {
    if (!class_exists('WalletModel')) {
        require_once dirname(__DIR__, 2) . '/app/Models/Wallet.php';
    }
    $walletBalance = (new WalletModel())->getBalance($currentUser['id']);
} catch (Exception $e) {}

try {
    $stats = (new UserModel())->getStats($currentUser['id']);
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT DISTINCT DATE(created_at) as d FROM checkins WHERE user_id = ? AND is_deleted = 0 ORDER BY d DESC LIMIT 60");
    $stmt->execute([$currentUser['id']]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $today = new DateTime();
    foreach ($dates as $i => $d) {
        $expected = (clone $today)->modify("-{$i} days")->format('Y-m-d');
        if ($d === $expected) { $streak++; } else { break; }
    }
    $weeklyCheckins = (new CheckinModel())->getWeeklyCheckinCount($currentUser['id']);
    $stmt = $db->prepare("SELECT u.id, u.username, u.avatar, u.tag, u.is_active FROM users u JOIN user_follows f ON u.id = f.following_id WHERE f.follower_id = ? AND u.is_active = 1 LIMIT 10");
    $stmt->execute([$currentUser['id']]);
    $followingUsers = $stmt->fetchAll();
    if (empty($followingUsers)) {
        $stmt = $db->prepare("SELECT u.id, u.username, u.avatar, u.tag, u.is_active, (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) as followers FROM users u WHERE u.id != ? AND u.is_active = 1 ORDER BY followers DESC, u.id DESC LIMIT 10");
        $stmt->execute([$currentUser['id']]);
        $followingUsers = $stmt->fetchAll();
    }
} catch (Exception $e) {}

$userLevel = floor(($stats['checkins'] ?? 0) / 15) + 1;
$avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']);
$levelRingClass = 'bg-gradient-to-tr from-slate-700 to-slate-800';
if ($userLevel >= 20) {
    $levelRingClass = 'level-ring-gold';
} elseif ($userLevel >= 10) {
    $levelRingClass = 'level-ring-purple';
} elseif ($userLevel >= 3) {
    $levelRingClass = 'level-ring-bronze';
}
?>
<header class="bg-cyber-dark/85 backdrop-blur-xl border-b border-cyber-border py-3 px-4 md:px-6 flex flex-wrap md:flex-nowrap justify-between items-center sticky top-0 w-full z-50 max-w-[1920px] mx-auto left-0 right-0 gap-3">
    <!-- Logo & Portal Identity -->
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded bg-gradient-to-br from-primary to-cyber-pink flex items-center justify-center shadow-neonOrange flex-shrink-0">
            <span class="material-symbols-outlined text-white text-[20px] font-bold" style="font-variation-settings: 'wght' 700;">hive</span>
        </div>
        <div class="flex flex-col">
            <h1 class="font-bold text-sm tracking-widest text-white uppercase leading-none">
                Sociaera <span class="text-xs text-secondary font-mono font-normal">v3.8-RP</span>
            </h1>
            <span class="text-[9px] font-mono text-secondary tracking-wider mt-0.5 leading-none">CONNECTED_TO: NETWORK_LOS_SANTOS</span>
        </div>
    </div>

    <!-- Navigation links -->
    <nav class="hidden xl:flex items-center gap-stack-sm">
        <a class="<?php echo ($activeNav ?? '') === 'activity' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'; ?> transition-colors duration-200 px-3 py-1 font-mono text-xs uppercase" href="<?php echo BASE_URL; ?>/activity">Keşfet</a>
        <a class="<?php echo ($activeNav ?? '') === 'venues' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'; ?> transition-colors duration-200 px-3 py-1 font-mono text-xs uppercase" href="<?php echo BASE_URL; ?>/venues">Mekânlar</a>
        <a class="<?php echo ($activeNav ?? '') === 'missions' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'; ?> transition-colors duration-200 px-3 py-1 font-mono text-xs uppercase" href="<?php echo BASE_URL; ?>/missions">Görevler</a>
        <a class="<?php echo ($activeNav ?? '') === 'kampanyalar' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'; ?> transition-colors duration-200 px-3 py-1 font-mono text-xs uppercase" href="<?php echo BASE_URL; ?>/kampanyalar">Kampanyalar</a>
        <a class="<?php echo ($activeNav ?? '') === 'leaderboard' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'; ?> transition-colors duration-200 px-3 py-1 font-mono text-xs uppercase" href="<?php echo BASE_URL; ?>/leaderboard">Liderlik</a>
        <a class="<?php echo ($activeNav ?? '') === 'wallet' ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'; ?> transition-colors duration-200 px-3 py-1 font-mono text-xs uppercase" href="<?php echo BASE_URL; ?>/wallet">Cüzdan</a>
    </nav>

    <!-- Telemetry status widget in the center -->
    <div class="hidden lg:flex items-center gap-4 font-mono text-[10px] text-slate-400">
        <div class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-cyber-green animate-pulse" style="background-color:#10b981;"></span>
            <span>LSPD NET: ONLINE</span>
        </div>
        <div class="w-px h-3 bg-white/10"></div>
        <div>
            <span>GPS: ACTIVE</span>
        </div>
        <div class="w-px h-3 bg-white/10"></div>
        <div class="flex items-center gap-1 text-primary">
            <span class="material-symbols-outlined text-xs animate-pulse">local_fire_department</span>
            <span><?php echo $streak; ?> GÜN SERİ</span>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="hidden md:flex relative group max-w-xs w-full">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-body-lg">search</span>
        <input class="stitch-search-input bg-slate-900/60 border border-white/5 rounded-lg pl-10 pr-12 py-1.5 text-xs w-full focus:ring-1 focus:ring-primary focus:bg-slate-900 focus:border-primary/30 transition-all font-mono" placeholder="Mekan veya arkadaş ara..." type="text" onkeyup="if(event.key === 'Enter') window.location.href='<?php echo BASE_URL; ?>/venues?q=' + encodeURIComponent(this.value)"/>
        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-on-surface-variant border border-white/5 px-1 rounded font-mono">⌘ K</div>
    </div>

    <!-- Right Header Actions -->
    <div class="flex items-center gap-3 font-mono">
        <!-- Cash HUD display -->
        <div class="hidden sm:flex flex-col text-right">
            <span class="text-[9px] text-slate-500 uppercase leading-none">Bakiye</span>
            <span class="text-cyber-green font-bold text-xs" style="color: #10b981;">$<?php echo number_format($walletBalance, 2, ',', '.'); ?></span>
        </div>
        <div class="w-px h-6 bg-white/10 hidden sm:block"></div>

        <!-- Checkin FAB and Notifications -->
        <div class="flex items-center gap-1 text-primary">
            <?php
            $notifCount = 0;
            try {
                $notifCount = (new NotificationModel())->getUnreadCount($currentUser['id']);
            } catch (Exception $e) {}
            ?>
            <a href="<?php echo BASE_URL; ?>/notifications" aria-label="notifications" class="hover:bg-white/5 rounded-lg transition-all duration-200 p-2 relative flex items-center justify-center">
                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 0;">notifications</span>
                <?php if ($notifCount > 0): ?>
                    <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-[#00f0ff] rounded-full"></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo BASE_URL; ?>/profile" aria-label="person_pin" class="hover:bg-white/5 rounded-lg transition-all duration-200 p-2 flex items-center justify-center">
                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 0;">person_pin</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/character-select" aria-label="swap_horiz" class="hover:bg-white/5 rounded-lg transition-all duration-200 p-2 flex items-center justify-center">
                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 0;">swap_horiz</span>
            </a>
        </div>

        <div class="relative group">
            <div class="w-8 h-8 rounded-full overflow-hidden border border-white/20 hover:border-primary transition-colors cursor-pointer flex-shrink-0">
                <img alt="Character Avatar" class="w-full h-full object-cover" src="<?php echo $avatarUrl; ?>"/>
            </div>
            <div class="absolute right-0 mt-1 w-52 bg-[#1c1b1c]/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl py-2 hidden group-hover:block hover:block z-50 animate-[fadeIn_0.15s_ease-out]">
                <a href="<?php echo BASE_URL; ?>/profile" class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-primary">person</span>
                    <span>Profilim</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/wallet" class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-primary">account_balance_wallet</span>
                    <span>Cüzdanım</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/missions" class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-primary">assignment</span>
                    <span>Görevler</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/leaderboard" class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-primary">leaderboard</span>
                    <span>Liderlik Tablosu</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/kampanyalar" class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-primary">campaign</span>
                    <span>Kampanyalar</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/premium" class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-primary">diamond</span>
                    <span>Premium Geçiş</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/mystery-shopper" class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-primary">visibility_off</span>
                    <span>Gizli Müşteri</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/settings" class="flex items-center gap-3 px-4 py-2 text-sm text-on-surface hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-primary">settings</span>
                    <span>Ayarlar</span>
                </a>
                <div class="border-t border-white/5 my-1"></div>
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 transition-colors">
                    <span class="material-symbols-outlined text-base">logout</span>
                    <span>Çıkış Yap</span>
                </a>
            </div>
        </div>
    </div>
</header>
<?php endif; ?>

<!-- Main Grid Container -->
<main class="max-w-[1920px] mx-auto px-6 grid grid-cols-12 gap-lg mt-md w-full flex-grow">
    
    <?php if (Auth::check() && $currentUser): ?>
    <!-- Sol Sidebar: Identity Rail -->
    <aside class="hidden lg:flex flex-col col-span-12 lg:col-span-3 xl:col-span-3 space-y-lg sticky top-20 h-[calc(100vh-100px)] overflow-y-auto custom-scrollbar pr-2 pb-6">
        
        <!-- Profile Card -->
        <div class="swarm-glass-card rounded-xl overflow-hidden relative border border-outline-variant/30">
            <div class="h-32 hero-pattern relative flex items-center justify-center">
                <div class="relative mt-8 p-[3px] rounded-full <?php echo $levelRingClass; ?> shadow-lg">
                    <img alt="Profil Fotoğrafı" class="w-24 h-24 rounded-full border-4 border-[#131314] shadow-xl object-cover" src="<?php echo $avatarUrl; ?>"/>
                    <?php if (!empty($currentUser['is_premium'])): ?>
                        <div class="absolute bottom-0 right-0 bg-primary-container p-1 rounded-full text-on-primary-container border-2 border-[#131314]">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="p-md text-center pt-10">
                <h2 class="text-headline-sm font-headline-sm truncate px-2" style="color: #ffb778;" title="<?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?>"><?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?></h2>
                <p class="text-label-md text-on-surface-variant">Swarm'a hoş geldin!</p>
                
                <div class="grid grid-cols-2 gap-sm mt-lg">
                    <div class="bg-surface-container/60 border border-white/5 px-2 py-3 rounded-lg text-left">
                        <div class="flex items-center gap-xs text-primary mb-1">
                            <span class="material-symbols-outlined text-sm streak-pulse" style="font-variation-settings: 'FILL' 1; color: #ff9100;">local_fire_department</span>
                            <span class="text-headline-sm font-bold text-white"><?php echo $streak; ?></span>
                        </div>
                        <span class="text-label-sm text-on-surface-variant">Günlük Seri</span>
                    </div>
                    <div class="bg-surface-container/60 border border-white/5 px-2 py-3 rounded-lg text-left">
                        <div class="flex items-center gap-xs text-primary mb-1">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1; color: #ff9100;">location_on</span>
                            <span class="text-headline-sm font-bold text-white"><?php echo $stats['checkins'] ?? 0; ?></span>
                        </div>
                        <span class="text-label-sm text-on-surface-variant">Check-in</span>
                    </div>
                </div>
                
                <div class="mt-lg text-left">
                    <div class="flex justify-between text-label-sm mb-xs">
                        <span class="text-on-surface-variant">Haftalık Hedef: 5</span>
                        <span class="text-primary font-bold"><?php echo min(5, $weeklyCheckins); ?> / 5</span>
                    </div>
                    <div class="h-1.5 bg-surface-container-highest rounded-full overflow-hidden border border-white/5">
                        <div class="h-full bg-primary-container" style="width: <?php echo min(100, ($weeklyCheckins / 5) * 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Actions -->
        <div class="space-y-sm">
            <a href="<?php echo BASE_URL; ?>/venues" class="w-full bg-primary-container text-on-primary-container font-bold py-3.5 rounded-lg flex items-center justify-center gap-sm transition-all hover:brightness-110 active:scale-95 shadow-[0_0_20px_rgba(255,145,0,0.3)]">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">add_location_alt</span>
                Mekan Seç & Check-in
            </a>
            <a href="<?php echo BASE_URL; ?>/activity"
               class="w-full border text-on-surface font-semibold py-3.5 rounded-lg flex items-center justify-start px-md gap-md transition-colors group <?php echo ($activeNav ?? '') === 'activity' ? 'bg-primary/10 border-primary/20 text-primary' : 'bg-surface-container/60 border-white/5 hover:bg-surface-container'; ?>">
                <span class="material-symbols-outlined transition-colors <?php echo ($activeNav ?? '') === 'activity' ? 'text-primary' : 'text-on-surface-variant group-hover:text-primary'; ?>" style="<?php echo ($activeNav ?? '') === 'activity' ? "font-variation-settings:'FILL' 1;" : ''; ?>">explore</span>
                Aktivite Akışı
            </a>
            <a href="<?php echo BASE_URL; ?>/venues"
               class="w-full bg-surface-container/60 border border-white/5 text-on-surface font-semibold py-3.5 rounded-lg flex items-center justify-start px-md gap-md hover:bg-surface-container transition-colors group">
                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">search</span>
                Mekan Ara
            </a>
            <a href="<?php echo BASE_URL; ?>/members" class="w-full bg-surface-container/60 border border-white/5 text-on-surface font-semibold py-3.5 rounded-lg flex items-center justify-start px-md gap-md hover:bg-surface-container transition-colors group">
                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">group</span>
                Arkadaşlarımı Gör
            </a>
        </div>

        <!-- Owned Venues (İşletmelerim) -->
        <?php
        $userVenues = [];
        try {
            $userVenues = (new VenueModel())->getByOwner(Auth::id());
        } catch (Exception $e) {}
        if (!empty($userVenues)):
        ?>
        <div class="swarm-glass-card p-4 rounded-xl border border-outline-variant/10 shadow-md">
            <h3 class="text-[9px] text-on-surface-variant uppercase tracking-widest mb-2 font-mono">İşletmelerim</h3>
            <ul class="flex flex-col gap-1">
                <?php foreach ($userVenues as $uv):
                    $isVenueActive = ($activeNav ?? '') === 'venue_manage_' . $uv['id'];
                    $venueLinkClass = $isVenueActive
                        ? 'w-full bg-primary text-on-primary font-bold py-1.5 px-3 rounded-lg flex items-center justify-start gap-2.5 transition-all text-xs'
                        : 'w-full bg-surface-container/30 hover:bg-surface-container text-on-surface-variant hover:text-on-surface font-medium py-1.5 px-3 rounded-lg flex items-center justify-start gap-2.5 transition-all text-xs';
                ?>
                <li>
                    <a class="<?php echo $venueLinkClass; ?>" href="<?php echo BASE_URL; ?>/venue-manage?id=<?php echo $uv['id']; ?>">
                        <span class="material-symbols-outlined text-[14px]">storefront</span>
                        <span class="truncate flex-grow"><?php echo escape($uv['name']); ?></span>
                        <?php if ($uv['status'] === 'pending'): ?>
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                        <?php elseif (!empty($uv['is_open'])): ?>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                        <?php else: ?>
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Character Selection -->
        <?php
        $otherCharacters = [];
        if (!empty($currentUser['gta_user_id'])) {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT id, username, gta_character_name, avatar, tag FROM users WHERE gta_user_id = ? AND id != ? AND is_active = 1");
                $stmt->execute([$currentUser['gta_user_id'], $currentUser['id']]);
                $otherCharacters = $stmt->fetchAll();
            } catch (Exception $e) {}
        }
        if (!empty($otherCharacters) || !empty($currentUser['gta_user_id'])):
        ?>
        <div class="swarm-glass-card p-4 rounded-xl border border-outline-variant/10 shadow-md">
            <h3 class="text-[9px] text-on-surface-variant uppercase tracking-widest mb-2 font-mono flex items-center justify-between">
                <span>Karakter Değiştir</span>
                <span class="material-symbols-outlined text-[14px]">switch_account</span>
            </h3>
            <ul class="flex flex-col gap-1">
                <?php foreach ($otherCharacters as $oc): ?>
                <li>
                    <form action="<?php echo BASE_URL; ?>/switch-character" method="POST" class="m-0">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="target_user_id" value="<?php echo $oc['id']; ?>">
                        <button type="submit" class="w-full bg-surface-container/30 hover:bg-surface-container text-on-surface-variant hover:text-on-surface py-1.5 px-3 rounded-lg flex items-center justify-start gap-2.5 transition-all text-xs text-left">
                            <span class="shrink-0">
                                <?php $ocAvatarUrl = safeAvatarUrl($oc['avatar'] ?? null, $oc['username']); ?>
                                <img src="<?php echo $ocAvatarUrl; ?>" alt="Avatar" class="w-4 h-4 rounded-full border border-white/10 object-cover" width="16" height="16">
                            </span>
                            <span class="truncate flex-grow"><?php echo escape($oc['gta_character_name'] ?: $oc['username']); ?></span>
                        </button>
                    </form>
                </li>
                <?php endforeach; ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/oauth-login" class="w-full hover:bg-primary/10 text-on-surface-variant hover:text-primary py-1.5 px-3 rounded-lg flex items-center justify-start gap-2.5 transition-colors text-xs font-bold">
                        <span class="material-symbols-outlined text-[14px]">add_circle</span>
                        <span>Karakter Bağla</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Rozetlerim Bento Widget -->
        <div class="swarm-glass-card p-4 rounded-xl border border-outline-variant/10 shadow-md">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-[9px] text-on-surface-variant font-bold uppercase tracking-wider font-mono">Rozetlerim</h3>
                <a href="<?php echo BASE_URL; ?>/missions" class="text-primary text-[9px] font-bold hover:underline">Tümünü Gör</a>
            </div>
            <div class="grid grid-cols-4 gap-1.5">
                <div class="aspect-square bg-surface-container/60 rounded-lg flex items-center justify-center relative group cursor-help border border-white/5" title="16 Günlük Seri">
                    <span class="material-symbols-outlined text-primary text-base streak-pulse" style="font-variation-settings: 'FILL' 1; color: #ff9100;">local_fire_department</span>
                    <div class="absolute -bottom-1 -right-1 bg-surface-container px-1 rounded-md text-[8px] font-bold border border-outline-variant/30"><?php echo $streak; ?></div>
                </div>
                <div class="aspect-square bg-surface-container/60 rounded-lg flex items-center justify-center relative border border-white/5" title="Fotoğrafçı">
                    <span class="material-symbols-outlined text-on-surface-variant text-base" style="font-variation-settings: 'FILL' 1;">photo_camera</span>
                    <div class="absolute -bottom-1 -right-1 bg-surface-container px-1 rounded-md text-[8px] font-bold border border-outline-variant/30">50</div>
                </div>
                <div class="aspect-square bg-surface-container/60 rounded-lg flex items-center justify-center relative border border-white/5" title="Denetleyici">
                    <span class="material-symbols-outlined text-on-surface-variant text-base" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                    <div class="absolute -bottom-1 -right-1 bg-surface-container px-1 rounded-md text-[8px] font-bold border border-outline-variant/30">10</div>
                </div>
                <div class="aspect-square bg-surface-container/60 rounded-lg flex items-center justify-center relative border border-white/5" title="Gezgin">
                    <span class="material-symbols-outlined text-on-surface-variant text-base" style="font-variation-settings: 'FILL' 1;">hive</span>
                    <div class="absolute -bottom-1 -right-1 bg-surface-container px-1 rounded-md text-[8px] font-bold border border-outline-variant/30">5</div>
                </div>
            </div>
        </div>

        <!-- Yakın Arkadaşlarım Bento Widget -->
        <div class="swarm-glass-card p-4 rounded-xl border border-outline-variant/10 shadow-md">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-[9px] text-on-surface-variant font-bold uppercase tracking-wider font-mono">Yakın Arkadaşlarım</h3>
                <a href="<?php echo BASE_URL; ?>/members" class="text-primary text-[9px] font-bold hover:underline">Tümünü Gör</a>
            </div>
            <div class="flex -space-x-3 overflow-hidden">
                <?php foreach (array_slice($followingUsers, 0, 5) as $fu): ?>
                    <?php $fuAvatar = safeAvatarUrl($fu['avatar'] ?? null, $fu['username']); ?>
                    <img alt="<?php echo escape($fu['username']); ?>" class="inline-block h-8 w-8 rounded-full ring-2 ring-[#131314] object-cover" src="<?php echo $fuAvatar; ?>" width="32" height="32" title="<?php echo escape($fu['username']); ?>" />
                <?php endforeach; ?>
                <?php if (count($followingUsers) > 5): ?>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-container-highest ring-2 ring-[#131314] text-[10px] font-bold text-on-surface">+<?php echo count($followingUsers) - 5; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Logout Link -->
        <a href="<?php echo BASE_URL; ?>/logout" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold py-2.5 rounded-lg flex items-center justify-start px-4 gap-3 transition-all text-xs">
            <span class="material-symbols-outlined text-[18px]">logout</span>
            <span>Çıkış Yap</span>
        </a>
    </aside>
    <?php endif; ?>

    <!-- Center Content Panel (Feed) -->
    <?php $feedCols = !empty($hideSidebar) ? 'col-span-12 lg:col-span-9 xl:col-span-9' : 'col-span-12 lg:col-span-5 xl:col-span-6'; ?>
    <section class="<?php echo Auth::check() ? $feedCols : 'col-span-12'; ?> flex flex-col gap-6 pb-6">
        <!-- Page Content Starts Here -->
