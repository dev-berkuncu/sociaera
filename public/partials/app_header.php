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
<style id="cls-guard">body{opacity:0}body.ready{opacity:1;transition:opacity .2s ease-in}</style>
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title($pageTitle ?? 'Sociaera'); ?></title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "tertiary-fixed": "#c4e7ff",
                        "on-error": "#690005",
                        "inverse-on-surface": "#283044",
                        "tertiary-container": "#00a5de",
                        "primary-fixed-dim": "#ffb59d",
                        "on-tertiary-container": "#00364b",
                        "primary": "#ffb59d",
                        "error": "#ffb4ab",
                        "surface-tint": "#ffb59d",
                        "primary-fixed": "#ffdbd0",
                        "on-surface": "#dae2fd",
                        "secondary-fixed-dim": "#bcc7de",
                        "surface-container-high": "#222a3d",
                        "on-primary": "#5d1900",
                        "on-primary-fixed": "#390c00",
                        "surface-bright": "#31394d",
                        "on-secondary-fixed": "#111c2d",
                        "secondary": "#bcc7de",
                        "background": "#0b1326",
                        "on-surface-variant": "#e1bfb5",
                        "surface-container-highest": "#2d3449",
                        "secondary-fixed": "#d8e3fb",
                        "outline": "#a98a80",
                        "inverse-primary": "#ab3500",
                        "on-tertiary-fixed-variant": "#004c69",
                        "on-secondary": "#263143",
                        "tertiary": "#7bd0ff",
                        "surface-container": "#171f33",
                        "on-error-container": "#ffdad6",
                        "on-background": "#dae2fd",
                        "on-secondary-fixed-variant": "#3c475a",
                        "surface-variant": "#2d3449",
                        "secondary-container": "#3e495d",
                        "error-container": "#93000a",
                        "surface-dim": "#0b1326",
                        "outline-variant": "#594139",
                        "tertiary-fixed-dim": "#7bd0ff",
                        "on-primary-fixed-variant": "#832600",
                        "surface-container-lowest": "#060e20",
                        "on-tertiary": "#00354a",
                        "surface": "#0b1326",
                        "primary-container": "#ff6b35",
                        "surface-container-low": "#131b2e",
                        "on-tertiary-fixed": "#001e2c",
                        "on-primary-container": "#5f1900",
                        "on-secondary-container": "#aeb9d0",
                        "inverse-surface": "#dae2fd"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
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
                    "fontFamily": {
                        "headline-md": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "display-lg": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "headline-sm": ["Plus Jakarta Sans"],
                        "label-sm": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"]
                    },
                    "fontSize": {
                        "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "display-lg": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "500"}],
                        "headline-sm": ["16px", {"lineHeight": "24px", "fontWeight": "600"}],
                        "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "400"}],
                        "headline-lg": ["32px", { "lineHeight": "1.3", "letterSpacing": "-0.01em", "fontWeight": "600" }]
                    }
                },
            },
        }
    </script>
<style>
    body { background-color: #0b1326; color: #dae2fd; font-family: 'Plus Jakarta Sans', sans-serif; }
    
    .hero-pattern {
        background: linear-gradient(to bottom, rgba(255,107,53,0.18) 0%, rgba(11,19,38,0) 100%),
                    url('https://www.transparenttextures.com/patterns/carbon-fibre.png');
    }
    
    .glass-effect {
        backdrop-filter: blur(12px);
        background: rgba(23, 31, 51, 0.75);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #0b1326; }
    ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: #ff6b35; }

    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .material-symbols-outlined[data-weight="fill"] {
        font-variation-settings: 'FILL' 1;
    }

    /* Flash Messages */
    .flash-message {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        background: #1e293b;
        color: #fff;
        padding: 12px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease forwards;
        border-left: 4px solid #ff6b35;
    }
    .flash-message.flash-success { border-color: #10b981; }
    .flash-message.flash-error { border-color: #ef4444; }
    .flash-message .flash-close {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
    }
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* Comments & Utilities */
    .comment-item { display: flex; gap: 12px; align-items: flex-start; padding: 8px 0; }
    .comment-body { background: rgba(255,255,255,0.05); padding: 10px 14px; border-radius: 12px; flex-grow: 1; }
    .comment-author { font-weight: 600; color: #ffb59d; margin-right: 8px; text-decoration: none; }
    .comment-text { color: #dae2fd; }
    .comment-time { display: block; font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }
    
    .venue-picker-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .venue-picker-item:hover { background: rgba(255,255,255,0.05); }
    .venue-picker-name { font-weight: 500; color: #fff; }
    .venue-picker-cat { font-size: 0.8rem; color: #94a3b8; }
    
    .remove-preview {
        position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.5); color: #fff; border: none;
        border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer;
    }
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    .liked .material-symbols-outlined { font-variation-settings: 'FILL' 1; color: #ffb59d; }
    
    /* Scrollbar */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

    /* Sponsor Marquee */
    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .animate-marquee {
        animation: marquee 12s linear infinite;
    }
    .animate-marquee:hover {
        animation-play-state: paused;
    }

    /* Font yüklenene kadar simge adlarının taşarak tasarımı bozmasını engeller */
    .material-symbols-outlined {
        display: inline-block;
        width: 1em;
        height: 1em;
        overflow: hidden;
        white-space: nowrap;
        word-wrap: normal;
    }

    /* ── CUSTOM EXPLORER HUD & EXPERIENCE TICKET SYSTEM ── */
    
    /* Receipt Card Base */
    .receipt-card {
        background: rgba(23, 31, 51, 0.85);
        border: 1px solid rgba(255, 107, 53, 0.15);
        border-radius: 12px;
        position: relative;
        overflow: visible !important; /* Allow stamps to float outside if needed */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .receipt-card:hover {
        border-color: rgba(255, 107, 53, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6), 0 0 15px rgba(255, 107, 53, 0.05);
    }
    
    /* Jagged Edge effect for tickets */
    .receipt-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, transparent 50%, #ff6b35 50%), linear-gradient(225deg, transparent 50%, #ff6b35 50%);
        background-size: 8px 8px;
        background-repeat: repeat-x;
        opacity: 0.9;
        z-index: 10;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    /* Ticket Metadata Grid */
    .ticket-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        background: rgba(0, 0, 0, 0.3);
        border: 1px dashed rgba(255, 255, 255, 0.08);
        border-radius: 8px;
        padding: 10px 14px;
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.75rem;
        color: #94a3b8;
    }

    /* Ticket Stamp (Damga) */
    .ticket-stamp-wrapper {
        position: absolute;
        top: 15px;
        right: 20px;
        z-index: 20;
        pointer-events: none;
    }
    
    .ticket-stamp {
        font-family: 'Courier New', Courier, monospace;
        font-weight: 900;
        text-transform: uppercase;
        border: 3px double currentColor;
        padding: 4px 10px;
        border-radius: 6px;
        display: inline-block;
        transform: rotate(-12deg);
        letter-spacing: 0.12em;
        font-size: 0.7rem;
        box-shadow: 0 0 10px currentColor;
        opacity: 0.85;
        background: rgba(15, 23, 42, 0.9);
        backdrop-filter: blur(2px);
    }
    .stamp-mystery {
        color: #c084fc; /* purple-400 */
        text-shadow: 0 0 5px rgba(192, 132, 252, 0.5);
    }
    .stamp-vip {
        color: #fbbf24; /* yellow-400 */
        text-shadow: 0 0 5px rgba(251, 191, 36, 0.5);
    }
    .stamp-approved {
        color: #34d399; /* emerald-400 */
        text-shadow: 0 0 5px rgba(52, 211, 153, 0.5);
    }

    /* monospaced radio chatter / logs */
    .radio-log-container {
        font-family: 'Courier New', Courier, monospace;
        background-color: rgba(6, 14, 32, 0.5) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 8px;
        padding: 12px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
    }
    .radio-log-item {
        font-size: 0.8rem;
        line-height: 1.4;
        color: #94a3b8;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.03);
        padding: 6px 0;
        display: flex;
        align-items: flex-start;
        gap: 6px;
    }
    .radio-log-item:last-child {
        border-bottom: none;
    }
    .radio-tag {
        color: #ffb59d;
        font-weight: bold;
        flex-shrink: 0;
    }
    .radio-msg {
        color: #e2e8f0;
        word-break: break-word;
    }
    .radio-time {
        color: #64748b;
        margin-left: auto;
        font-size: 0.7rem;
        flex-shrink: 0;
    }
    .radio-input-line {
        font-family: 'Courier New', Courier, monospace;
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 0 !important;
        padding: 8px 4px !important;
        color: #e2e8f0 !important;
        font-size: 0.85rem !important;
    }
    .radio-input-line:focus {
        border-color: #ff6b35 !important;
        box-shadow: none !important;
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
</style>
<script>
(function(){
    var t=setTimeout(function(){document.body.classList.add('ready')},3000);
    if(document.fonts&&document.fonts.ready){
        document.fonts.ready.then(function(){clearTimeout(t);document.body.classList.add('ready')});
    } else {
        window.addEventListener('load',function(){clearTimeout(t);document.body.classList.add('ready')});
    }
})();
</script>
</head>
<body class="bg-background text-on-background font-body-md text-body-md min-h-screen antialiased flex selection:bg-primary-container/30 selection:text-primary-container">

<?php if (Auth::check() && $currentUser): ?>
<!-- Top Navigation Bar -->
<header class="bg-background sticky top-0 z-50 flex justify-between items-center w-full px-6 py-3 max-w-[1920px] mx-auto border-b border-white/5 backdrop-blur-md">
    <div class="flex items-center gap-6">
        <a href="<?php echo BASE_URL; ?>/dashboard" class="text-sm sm:text-base font-bold text-primary flex items-center gap-1.5 hover:opacity-90 transition-all font-sans uppercase tracking-wider">
            <span class="material-symbols-outlined text-primary text-2xl" style="font-variation-settings: 'FILL' 1;">hive</span>
            Sociaera
        </a>
        <div class="hidden md:flex relative group">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
            <input class="bg-surface-container-low border border-white/5 rounded-lg pl-10 pr-12 py-1.5 text-xs w-[320px] focus:ring-1 focus:ring-primary focus:border-primary transition-all text-on-surface placeholder:text-slate-500" placeholder="Mekan, kategori veya arkadaş ara..." type="text" onkeyup="if(event.key === 'Enter') window.location.href='<?php echo BASE_URL; ?>/venues?search=' + encodeURIComponent(this.value)"/>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <!-- Notification Bell -->
        <?php
        $notifCount = 0;
        try {
            $notifCount = (new NotificationModel())->getUnreadCount($currentUser['id']);
        } catch (Exception $e) {}
        ?>
        <a href="<?php echo BASE_URL; ?>/notifications" class="relative p-2 hover:bg-surface-container rounded-lg transition-colors active:scale-95 duration-150 text-slate-400 hover:text-white block">
            <span class="material-symbols-outlined text-xl">notifications</span>
            <?php if ($notifCount > 0): ?>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-secondary rounded-full"></span>
            <?php endif; ?>
        </a>
        
        <!-- User Pill -->
        <a href="<?php echo BASE_URL; ?>/profile" class="flex items-center gap-2 bg-surface-container-low border border-white/5 px-3 py-1.5 rounded-full cursor-pointer hover:bg-surface-container transition-all">
            <div class="relative flex-shrink-0">
                <?php $avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']); ?>
                <img alt="<?php echo escape($currentUser['username']); ?>" class="w-8 h-8 rounded-full border border-primary object-cover" src="<?php echo $avatarUrl; ?>"/>
                <?php if (!empty($currentUser['is_premium'])): ?>
                    <div class="absolute -bottom-1 -right-1 bg-primary text-[7px] font-bold px-1 rounded-full text-on-primary">VIP</div>
                <?php endif; ?>
            </div>
            <div class="hidden sm:block text-left max-w-[100px]">
                <div class="text-xs font-bold leading-none text-on-surface truncate"><?php echo escape($currentUser['username']); ?></div>
                <div class="text-[9px] text-primary mt-0.5">Seviye <?php 
                    $stats = (new UserModel())->getStats($currentUser['id']);
                    echo floor(($stats['checkins'] ?? 0) / 15) + 1; 
                ?></div>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant text-sm">expand_more</span>
        </a>
    </div>
</header>
<?php endif; ?>

<!-- Main Grid Container -->
<main class="max-w-[1920px] mx-auto px-6 grid grid-cols-12 gap-lg mt-4 w-full flex-grow">
    
    <?php if (Auth::check() && $currentUser): ?>
    <!-- Sol Sidebar: Identity Rail -->
    <aside class="hidden lg:flex flex-col col-span-12 lg:col-span-3 xl:col-span-2 space-y-lg sticky top-24 h-[calc(100vh-120px)] overflow-y-auto custom-scrollbar pr-2 pb-6">
        
        <!-- Profile Card -->
        <div class="bg-surface-container-low rounded-xl overflow-hidden relative border border-outline-variant/10 shadow-md flex-shrink-0">
            <div class="h-24 hero-pattern relative flex items-center justify-center">
                <div class="relative mt-8">
                    <img alt="Profil Fotoğrafı" class="w-16 h-16 rounded-full border-4 border-surface-container-low shadow-lg object-cover" src="<?php echo $avatarUrl; ?>"/>
                    <?php if (!empty($currentUser['is_premium'])): ?>
                        <div class="absolute bottom-0 right-0 bg-primary p-0.5 rounded-full text-on-primary border-2 border-surface-container-low">
                            <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="p-4 text-center pt-8">
                <h2 class="text-sm font-bold text-on-surface truncate px-2" title="<?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?>"><?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?></h2>
                <p class="text-[10px] text-on-surface-variant font-mono">@<?php echo escape($currentUser['tag'] ?: $currentUser['username']); ?></p>
                
                <div class="grid grid-cols-2 gap-2 mt-4">
                    <div class="bg-surface-container px-2 py-2 rounded-lg text-left border border-white/5">
                        <div class="flex items-center gap-1 text-primary mb-0.5">
                            <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                            <span class="text-xs font-bold">16</span>
                        </div>
                        <span class="text-[8px] text-on-surface-variant uppercase tracking-wider font-semibold">Seri</span>
                    </div>
                    <div class="bg-surface-container px-2 py-2 rounded-lg text-left border border-white/5">
                        <div class="flex items-center gap-1 text-primary mb-0.5">
                            <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">location_on</span>
                            <span class="text-xs font-bold"><?php echo $stats['checkins'] ?? 0; ?></span>
                        </div>
                        <span class="text-[8px] text-on-surface-variant uppercase tracking-wider font-semibold">Check-in</span>
                    </div>
                </div>
                
                <div class="mt-4 text-left">
                    <div class="flex justify-between text-[10px] mb-1">
                        <span class="text-on-surface-variant">Haftalık Hedef: 5</span>
                        <span class="text-primary font-bold"><?php echo min(5, $stats['checkins'] ?? 0); ?> / 5</span>
                    </div>
                    <div class="h-1 bg-surface-container-highest rounded-full overflow-hidden">
                        <div class="h-full bg-primary" style="width: <?php echo min(100, (($stats['checkins'] ?? 0) / 5) * 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Bento Links -->
        <div class="space-y-1">
            <?php
            $navItems = [
                'dashboard'    => ['icon' => 'home',                   'label' => 'Ana Sayfa',       'url' => '/dashboard'],
                'venues'       => ['icon' => 'explore',                'label' => 'Mekan Keşfet',    'url' => '/venues'],
                'missions'     => ['icon' => 'assignment',             'label' => 'Görevler',        'url' => '/missions'],
                'mystery-shopper'=>['icon' => 'visibility_off',        'label' => 'Gizli Müşteri',   'url' => '/mystery-shopper'],
                'leaderboard'  => ['icon' => 'leaderboard',            'label' => 'Liderlik Tablosu','url' => '/leaderboard'],
                'wallet'       => ['icon' => 'account_balance_wallet',  'label' => 'Cüzdan',          'url' => '/wallet'],
                'premium'      => ['icon' => 'diamond',                 'label' => 'Premium Geçiş',   'url' => '/premium'],
                'kampanyalar'  => ['icon' => 'campaign',                'label' => 'Kampanyalar',     'url' => '/kampanyalar'],
                'settings'     => ['icon' => 'settings',                'label' => 'Ayarlar',         'url' => '/settings'],
            ];
            $activeNav = $activeNav ?? '';
            foreach ($navItems as $key => $item):
                $isActive = $activeNav === $key;
                $linkClass = $isActive
                    ? 'w-full bg-primary text-on-primary font-bold py-2.5 rounded-lg flex items-center justify-start px-4 gap-3 transition-all duration-150'
                    : 'w-full bg-surface-container-low border border-white/5 text-on-surface font-semibold py-2.5 rounded-lg flex items-center justify-start px-4 gap-3 hover:bg-surface-container transition-colors duration-150 group';
                $iconClass = $isActive ? 'text-on-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors';
            ?>
            <a class="<?php echo $linkClass; ?>" href="<?php echo BASE_URL . $item['url']; ?>">
                <span class="material-symbols-outlined text-[18px] <?php echo $iconClass; ?>" <?php echo $isActive ? 'data-weight="fill"' : ''; ?>><?php echo $item['icon']; ?></span>
                <span class="text-xs font-bold"><?php echo $item['label']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Owned Venues (İşletmelerim) -->
        <?php
        $userVenues = [];
        try {
            $userVenues = (new VenueModel())->getByOwner(Auth::id());
        } catch (Exception $e) {}
        if (!empty($userVenues)):
        ?>
        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/10 shadow-md">
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
        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/10 shadow-md">
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
        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/10 shadow-md">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-[9px] text-on-surface-variant font-bold uppercase tracking-wider font-mono">Rozetlerim</h3>
                <span class="text-primary text-[9px] font-bold">4 Rozet</span>
            </div>
            <div class="grid grid-cols-4 gap-1.5">
                <div class="aspect-square bg-surface-container rounded-lg flex items-center justify-center relative group cursor-help border border-white/5" title="16 Günlük Seri">
                    <span class="material-symbols-outlined text-primary text-base" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                </div>
                <div class="aspect-square bg-surface-container rounded-lg flex items-center justify-center relative border border-white/5" title="Fotoğrafçı">
                    <span class="material-symbols-outlined text-on-surface-variant text-base" style="font-variation-settings: 'FILL' 1;">photo_camera</span>
                </div>
                <div class="aspect-square bg-surface-container rounded-lg flex items-center justify-center relative border border-white/5" title="Denetleyici">
                    <span class="material-symbols-outlined text-on-surface-variant text-base" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                </div>
                <div class="aspect-square bg-surface-container rounded-lg flex items-center justify-center relative border border-white/5" title="Gezgin">
                    <span class="material-symbols-outlined text-on-surface-variant text-base" style="font-variation-settings: 'FILL' 1;">hive</span>
                </div>
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
    <?php $feedCols = !empty($hideSidebar) ? 'col-span-12 lg:col-span-9 xl:col-span-10' : 'col-span-12 lg:col-span-9 xl:col-span-7'; ?>
    <section class="<?php echo Auth::check() ? $feedCols : 'col-span-12'; ?> flex flex-col gap-6 pb-6">
        <!-- Page Content Starts Here -->
