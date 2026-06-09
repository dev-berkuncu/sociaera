<?php
/**
 * Sociaera — App Header (Tailwind Design)
 */

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
<!-- Fonts: Plus Jakarta Sans for UI, Share Tech Mono for receipt elements -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Share+Tech+Mono&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        cyber: {
                            bg: "#060a13",        // Derin gece mavisi arka plan
                            dark: "#0b0c10",      // Kart/Panel içi koyu zemin
                            panel: "rgba(11, 12, 16, 0.65)",
                            glass: "rgba(16, 20, 30, 0.45)",
                            orange: "#ff6a00",    // Neon Turuncu (Primary Accent)
                            orangeLight: "#ff9100",
                            cyan: "#00f0ff",      // Camgöbeği (Secondary Accent)
                            purple: "#a855f7",    // Birlik/Gizli Müşteri Moru
                            pink: "#ec4899",      // Beğeni/Kalp Pembesi
                            green: "#10b981",     // Aktif/Para Yeşili
                            border: "rgba(255, 255, 255, 0.06)",
                            borderGlow: "rgba(255, 106, 0, 0.15)"
                        },
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
                        "primary": "#ffb778",
                        "on-error-container": "#ffdad6",
                        "surface-container-high": "#2a2a2b",
                        "tertiary": "#deb9ff",
                        "surface-variant": "#353436",
                        "surface-container-highest": "#353436",
                        "primary-container": "#ff9100",
                        "surface-dim": "#131314",
                        "on-primary-fixed": "#2e1500",
                        "outline-variant": "#564334",
                        "on-secondary-container": "#ffc2c4",
                        "outline": "#a48c7a",
                        "surface-container-lowest": "#0e0e0f",
                        "on-primary": "#4c2700",
                        "on-primary-container": "#633500"
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    spacing: {
                        "md": "16px",
                        "base": "4px",
                        "margin": "24px",
                        "gutter": "16px",
                        "xl": "32px",
                        "sm": "12px",
                        "xs": "8px",
                        "lg": "24px",
                        "stack-sm": "12px",
                        "container-padding": "32px",
                        "stack-lg": "48px",
                        "stack-md": "24px"
                    },
                    fontFamily: {
                        "headline-md": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "display-lg": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "headline-sm": ["Plus Jakarta Sans"],
                        "label-sm": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"],
                        sans: ["Plus Jakarta Sans", "sans-serif"],
                        mono: ["Share Tech Mono", "Courier New", "monospace"]
                    },
                    fontSize: {
                        "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "display-lg": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "500"}],
                        "headline-sm": ["16px", {"lineHeight": "24px", "fontWeight": "600"}],
                        "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "400"}],
                        "headline-lg": ["32px", { "lineHeight": "1.3", "letterSpacing": "-0.01em", "fontWeight": "600" }]
                    },
                    boxShadow: {
                        neonOrange: "0 0 15px rgba(255, 106, 0, 0.3), 0 0 30px rgba(255, 106, 0, 0.15)",
                        neonCyan: "0 0 15px rgba(0, 240, 255, 0.3), 0 0 30px rgba(0, 240, 255, 0.15)",
                        neonPurple: "0 0 15px rgba(168, 85, 247, 0.35), 0 0 30px rgba(168, 85, 247, 0.15)"
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
<body class="dark overflow-x-hidden bg-[#060a13] font-sans antialiased text-slate-200">

<!-- Scanline Overlay -->
<div class="scanlines"></div>

<!-- Stylized Map Background -->
<div class="fixed inset-0 z-0 overflow-hidden pointer-events-none bg-[#040810]">
    <svg class="absolute inset-0 w-full h-full opacity-25 select-none" xmlns="http://www.w3.org/2000/svg" id="tactical-map">
        <defs>
            <radialGradient id="map-glow" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#00f0ff" stop-opacity="0.12"/>
                <stop offset="100%" stop-color="#060a13" stop-opacity="0"/>
            </radialGradient>
        </defs>
        <rect width="100%" height="100%" fill="url(#map-glow)" />
        <path d="M -100 800 C 300 600, 500 800, 900 650 S 1400 300, 2200 400" fill="none" stroke="rgba(0, 240, 255, 0.1)" stroke-width="3" stroke-dasharray="10 5" />
        <path d="M 400 -100 C 600 300, 300 700, 800 1200" fill="none" stroke="rgba(255, 106, 0, 0.06)" stroke-width="2.5" />
        <path d="M 1200 -100 C 900 400, 1100 800, 1500 1300" fill="none" stroke="rgba(0, 240, 255, 0.06)" stroke-width="2" />
        <path d="M -100 300 C 500 200, 800 500, 2100 100" fill="none" stroke="rgba(168, 85, 247, 0.06)" stroke-width="1.5" />
        <path d="M -200 1500 Q 300 1100, 500 900 T 1000 850 T 1600 950 Q 1900 1200, 2200 1500" fill="rgba(0, 240, 255, 0.01)" stroke="rgba(0, 240, 255, 0.15)" stroke-width="1" />
        <line id="radar-sweep" x1="0" y1="0" x2="2000" y2="2000" stroke="rgba(0, 240, 255, 0.05)" stroke-width="1.5" />
    </svg>
    <div class="absolute inset-0 map-grid opacity-30 pointer-events-none"></div>
</div>

<script>
    // Simple rotation for radar sweep
    let angle = 0;
    function rotateRadar() {
        angle = (angle + 0.3) % 360;
        const line = document.getElementById("radar-sweep");
        if (line) {
            line.setAttribute("transform", `rotate(${angle} 960 540)`);
        }
        requestAnimationFrame(rotateRadar);
    }
    document.addEventListener("DOMContentLoaded", rotateRadar);
</script>

<!-- Wrap layout inside a monitor container -->
<div class="relative z-10 flex flex-col min-h-screen crt-monitor">

<?php if (Auth::check() && $currentUser): ?>
<!-- Top Navigation Bar -->
<header class="bg-[#0b0c10]/85 backdrop-blur-xl sticky top-0 z-50 flex justify-between items-center w-full px-6 py-3 border-b border-cyber-border/40">
    <div class="flex items-center gap-xl">
        <a href="<?php echo BASE_URL; ?>/dashboard" class="text-headline-md font-headline-md font-bold text-cyber-orange flex items-center gap-xs tracking-wider uppercase">
            <span class="material-symbols-outlined text-cyber-orange text-3xl" style="font-variation-settings: 'FILL' 1;">hive</span>
            Sociaera <span class="text-xs text-cyber-cyan font-mono font-normal">v3.8-RP</span>
        </a>
        <div class="hidden md:flex relative group">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-body-lg">search</span>
            <input class="bg-[#10141e]/60 border border-white/5 rounded-lg pl-10 pr-12 py-2 text-body-md w-[400px] text-slate-200 placeholder-slate-500 focus:ring-1 focus:ring-cyber-orange focus:border-cyber-orange focus:bg-slate-900 transition-all font-mono text-xs" placeholder="Mekan veya arkadaş ara..." type="text" onkeyup="if(event.key === 'Enter') window.location.href='<?php echo BASE_URL; ?>/venues?q=' + encodeURIComponent(this.value)"/>
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-label-sm text-slate-500 border border-white/10 px-1.5 rounded-md font-mono">CMD K</div>
        </div>
    </div>
    
    <div class="flex items-center gap-md">
        <!-- Notification Bell -->
        <?php
        $notifCount = 0;
        try {
            $notifCount = (new NotificationModel())->getUnreadCount($currentUser['id']);
        } catch (Exception $e) {}
        ?>
        <a href="<?php echo BASE_URL; ?>/notifications" class="relative p-2 hover:bg-white/5 rounded-lg transition-colors active:scale-95 duration-150">
            <span class="material-symbols-outlined text-cyber-cyan">notifications</span>
            <?php if ($notifCount > 0): ?>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-cyber-orange rounded-full shadow-neonOrange"></span>
            <?php endif; ?>
        </a>
        
        <!-- User Pill with Hover Dropdown -->
        <?php 
            $stats = (new UserModel())->getStats($currentUser['id']);
            $streak = 0;
            $weeklyCheckins = 0;
            $followingUsers = [];
            try {
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
        ?>
        <?php
            $userLevel = floor(($stats['checkins'] ?? 0) / 15) + 1;
            $levelRingClass = 'bg-gradient-to-tr from-slate-700 to-slate-800';
            if ($userLevel >= 20) {
                $levelRingClass = 'level-ring-gold';
            } elseif ($userLevel >= 10) {
                $levelRingClass = 'level-ring-purple';
            } elseif ($userLevel >= 3) {
                $levelRingClass = 'level-ring-bronze';
            }
        ?>
        <div class="relative group">
            <button class="flex items-center gap-sm bg-slate-900/60 border border-white/5 px-3 py-1.5 rounded-full cursor-pointer hover:bg-slate-800 transition-all focus:outline-none">
                <div class="relative flex-shrink-0 p-[2px] rounded-full <?php echo $levelRingClass; ?>">
                    <?php $avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']); ?>
                    <img alt="<?php echo escape($currentUser['username']); ?>" class="w-8 h-8 rounded-full border-2 border-[#131314] object-cover" src="<?php echo $avatarUrl; ?>"/>
                    <div class="absolute -bottom-1 -right-1 bg-cyber-orange text-[8px] font-bold px-1 rounded-full text-white shadow-md">
                        <?php echo $userLevel; ?>
                    </div>
                </div>
                <div class="hidden lg:block text-left font-mono">
                    <div class="text-label-md font-bold leading-none text-slate-200"><?php echo escape($currentUser['username']); ?></div>
                    <div class="text-[10px] text-cyber-orange mt-0.5">LVL <?php echo $userLevel; ?></div>
                </div>
                <span class="material-symbols-outlined text-slate-400 text-sm">expand_more</span>
            </button>
            <div class="absolute right-0 mt-1 w-52 bg-[#0c0d10]/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl py-2 hidden group-hover:block hover:block z-50 animate-[modalIn_0.15s_ease-out] font-mono">
                <a href="<?php echo BASE_URL; ?>/profile" class="flex items-center gap-3 px-4 py-2 text-xs text-slate-200 hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-cyber-orange">person</span>
                    <span>PROFILIM</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/wallet" class="flex items-center gap-3 px-4 py-2 text-xs text-slate-200 hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-cyber-orange">account_balance_wallet</span>
                    <span>CUZDANIM</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/missions" class="flex items-center gap-3 px-4 py-2 text-xs text-slate-200 hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-cyber-orange">assignment</span>
                    <span>GOREVLER</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/leaderboard" class="flex items-center gap-3 px-4 py-2 text-xs text-slate-200 hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-cyber-orange">leaderboard</span>
                    <span>RANK TABLOSU</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/settings" class="flex items-center gap-3 px-4 py-2 text-xs text-slate-200 hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-base text-cyber-orange">settings</span>
                    <span>AYARLAR</span>
                </a>
                <div class="border-t border-white/5 my-1"></div>
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-4 py-2 text-xs text-red-400 hover:bg-red-500/10 transition-colors">
                    <span class="material-symbols-outlined text-base">logout</span>
                    <span>CIKIS YAP</span>
                </a>
            </div>
        </div>
    </div>
</header>
<?php endif; ?>

<!-- Main Grid Container -->
<main class="max-w-[1920px] mx-auto px-4 md:px-6 grid grid-cols-12 gap-gutter mt-md w-full flex-grow relative">
    
    <?php if (Auth::check() && $currentUser): ?>
    <!-- Sol Sidebar: Thin Navigation Rail (Desktop) -->
    <aside class="hidden md:flex flex-col justify-between items-center col-span-12 lg:col-span-1 xl:col-span-1 bg-[#0b0c10]/40 backdrop-blur-xl border border-white/5 py-6 rounded-xl sticky top-20 h-[calc(100vh-100px)] z-40">
        <!-- Navigation Items -->
        <nav class="flex flex-col gap-5 w-full px-1.5 font-mono">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="group flex flex-col items-center justify-center p-2 rounded-xl transition-all <?php echo $activeNav === 'dashboard' ? 'bg-cyber-orange/10 border border-cyber-orange/30 text-cyber-orange shadow-neonOrange' : 'text-slate-400 hover:text-cyber-orange hover:bg-cyber-orange/5'; ?>" title="Ana Sayfa">
                <span class="material-symbols-outlined text-[24px]">explore</span>
                <span class="text-[9px] font-bold mt-1 uppercase tracking-wider">Feed</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/venues" class="group flex flex-col items-center justify-center p-2 rounded-xl transition-all <?php echo $activeNav === 'venues' ? 'bg-cyber-orange/10 border border-cyber-orange/30 text-cyber-orange shadow-neonOrange' : 'text-slate-400 hover:text-cyber-orange hover:bg-cyber-orange/5'; ?>" title="Mekanlar">
                <span class="material-symbols-outlined text-[24px]">storefront</span>
                <span class="text-[9px] font-bold mt-1 uppercase tracking-wider">Venues</span>
            </a>

            <a href="<?php echo BASE_URL; ?>/leaderboard" class="group flex flex-col items-center justify-center p-2 rounded-xl transition-all <?php echo $activeNav === 'leaderboard' ? 'bg-cyber-orange/10 border border-cyber-orange/30 text-cyber-orange shadow-neonOrange' : 'text-slate-400 hover:text-cyber-orange hover:bg-cyber-orange/5'; ?>" title="Sıralama">
                <span class="material-symbols-outlined text-[24px]">military_tech</span>
                <span class="text-[9px] font-bold mt-1 uppercase tracking-wider">Ranks</span>
            </a>

            <a href="<?php echo BASE_URL; ?>/notifications" class="group flex flex-col items-center justify-center p-2 rounded-xl transition-all <?php echo $activeNav === 'notifications' ? 'bg-cyber-orange/10 border border-cyber-orange/30 text-cyber-orange shadow-neonOrange' : 'text-slate-400 hover:text-cyber-orange hover:bg-cyber-orange/5'; ?>" title="Bildirimler">
                <span class="material-symbols-outlined text-[24px]">notifications</span>
                <span class="text-[9px] font-bold mt-1 uppercase tracking-wider">Notifs</span>
            </a>

            <a href="<?php echo BASE_URL; ?>/profile" class="group flex flex-col items-center justify-center p-2 rounded-xl transition-all <?php echo $activeNav === 'profile' ? 'bg-cyber-orange/10 border border-cyber-orange/30 text-cyber-orange shadow-neonOrange' : 'text-slate-400 hover:text-cyber-orange hover:bg-cyber-orange/5'; ?>" title="Profil">
                <span class="material-symbols-outlined text-[24px]">person</span>
                <span class="text-[9px] font-bold mt-1 uppercase tracking-wider">Profile</span>
            </a>
        </nav>

        <!-- Footer Operations -->
        <div class="flex flex-col gap-4 w-full px-2">
            <a href="<?php echo BASE_URL; ?>/settings" class="group flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-200 transition-colors" title="Sistem Ayarları">
                <span class="material-symbols-outlined text-[20px]">settings</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/logout" class="group flex flex-col items-center justify-center p-2 rounded-xl text-red-500/40 hover:text-red-400 transition-colors" title="Bağlantıyı Kes">
                <span class="material-symbols-outlined text-[20px]">power_settings_new</span>
            </a>
        </div>
    </aside>
    <?php endif; ?>

    <!-- Center Content Panel (Feed) -->
    <?php 
    // Set Center column size: taking 7 columns if Right Sidebar is visible, else 11 columns on desktop
    $feedCols = !empty($hideSidebar) ? 'col-span-12 lg:col-span-11 xl:col-span-11' : 'col-span-12 lg:col-span-7 xl:col-span-7'; 
    ?>
    <section class="<?php echo Auth::check() ? $feedCols : 'col-span-12'; ?> flex flex-col gap-6 pb-6">
        <!-- Page Content Starts Here -->
