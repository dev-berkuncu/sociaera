<?php
/**
 * Sociaera — App Header (Tailwind Design)
 */
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

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
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title($pageTitle ?? 'Sociaera'); ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@500;600;700;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
                        "stack-sm": "12px",
                        "container-padding": "32px",
                        "gutter": "24px",
                        "base": "8px",
                        "stack-lg": "48px",
                        "stack-md": "24px"
                    },
                    "fontFamily": {
                        "display-lg": ["Manrope"],
                        "label-sm": ["Inter"],
                        "label-md": ["Inter"],
                        "body-lg": ["Inter"],
                        "headline-lg": ["Manrope"],
                        "headline-md": ["Manrope"],
                        "body-md": ["Inter"]
                    },
                    "fontSize": {
                        "display-lg": ["48px", { "lineHeight": "1.2", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "label-sm": ["12px", { "lineHeight": "1.2", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "label-md": ["14px", { "lineHeight": "1.2", "letterSpacing": "0.01em", "fontWeight": "500" }],
                        "body-lg": ["18px", { "lineHeight": "1.6", "fontWeight": "400" }],
                        "headline-lg": ["32px", { "lineHeight": "1.3", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                        "headline-md": ["24px", { "lineHeight": "1.4", "fontWeight": "600" }],
                        "body-md": ["16px", { "lineHeight": "1.6", "fontWeight": "400" }]
                    }
                }
            }
        }
    </script>
<style>
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
</style>
</head>
<body class="bg-background text-on-background font-body-md text-body-md min-h-screen antialiased flex selection:bg-primary-container/30 selection:text-primary-container">

<?php if (Auth::check() && $currentUser): ?>
<!-- SideNavBar -->
<nav class="hidden md:flex flex-col fixed left-0 top-0 h-full p-8 bg-[#1E293B]/80 backdrop-blur-lg font-manrope antialiased w-72 border-r border-white/10 shadow-[30px_0_30px_-15px_rgba(15,23,42,0.15)] z-50 overflow-y-auto">
    <div class="mb-stack-lg flex items-center gap-4">
        <?php $avatarUrl = $currentUser['avatar'] ? BASE_URL . '/uploads/avatars/' . $currentUser['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['username']) . '&background=random'; ?>
        <a href="<?php echo BASE_URL; ?>/profile"><img alt="User Avatar" class="w-12 h-12 rounded-full border-2 border-white/10 object-cover shadow-lg" src="<?php echo $avatarUrl; ?>" width="48" height="48"/></a>
        <div>
            <h1 class="text-2xl font-black tracking-tight text-[#FF6B35]"><?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?></h1>
            <a href="<?php echo BASE_URL; ?>/profile" class="text-label-sm font-label-sm text-slate-400 hover:text-white transition-colors">@<?php echo escape($currentUser['tag'] ?: $currentUser['username']); ?></a>
        </div>
    </div>
    <ul class="flex flex-col gap-2 flex-grow">
        <?php
        $navItems = [
            'dashboard' => ['icon' => 'home', 'label' => 'Ana Sayfa', 'url' => '/dashboard'],
            'venues' => ['icon' => 'location_on', 'label' => 'Mekanlar', 'url' => '/venues'],
            'leaderboard' => ['icon' => 'leaderboard', 'label' => 'Liderlik', 'url' => '/leaderboard'],
            'missions' => ['icon' => 'emoji_events', 'label' => 'Görevler', 'url' => '/missions'],
            'members' => ['icon' => 'people', 'label' => 'Üyeler', 'url' => '/members'],
            'wallet' => ['icon' => 'account_balance_wallet', 'label' => 'Cüzdan', 'url' => '/wallet'],
            'premium' => ['icon' => 'diamond', 'label' => 'Premium', 'url' => '/premium'],
            'notifications' => ['icon' => 'notifications', 'label' => 'Bildirimler', 'url' => '/notifications'],
            'settings' => ['icon' => 'settings', 'label' => 'Ayarlar', 'url' => '/settings']
        ];
        $activeNav = $activeNav ?? '';
        foreach ($navItems as $key => $item):
            $isActive = $activeNav === $key;
            $linkClass = $isActive 
                ? 'flex items-center gap-4 px-4 py-3 bg-[#FF6B35] text-white rounded-lg shadow-[0_0_5px_rgba(255,107,53,0.2)] active:scale-[0.98] transition-transform font-label-md text-label-md'
                : 'flex items-center gap-4 px-4 py-3 text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all duration-200 rounded-lg active:scale-[0.98] font-label-md text-label-md';
        ?>
        <li>
            <a class="<?php echo $linkClass; ?>" href="<?php echo BASE_URL . $item['url']; ?>">
                <span class="material-symbols-outlined" <?php echo $isActive ? 'data-weight="fill"' : ''; ?>><?php echo $item['icon']; ?></span>
                <?php echo $item['label']; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php
    // İşletmelerim — Kullanıcının sahip olduğu mekanlar
    $userVenues = [];
    try {
        $userVenues = (new VenueModel())->getByOwner(Auth::id());
    } catch (Exception $e) {}
    if (!empty($userVenues)):
    ?>
    <div class="mt-4 pt-4 border-t border-white/5">
        <h3 class="text-label-sm text-slate-500 uppercase tracking-wider px-4 mb-2">İşletmelerim</h3>
        <ul class="flex flex-col gap-1">
            <?php foreach ($userVenues as $uv):
                $isVenueActive = ($activeNav ?? '') === 'venue_manage_' . $uv['id'];
                $venueLinkClass = $isVenueActive
                    ? 'flex items-center gap-3 px-4 py-2.5 bg-[#FF6B35] text-white rounded-lg shadow-[0_0_5px_rgba(255,107,53,0.2)] text-label-md'
                    : 'flex items-center gap-3 px-4 py-2.5 text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all duration-200 rounded-lg text-label-md';
            ?>
            <li>
                <a class="<?php echo $venueLinkClass; ?>" href="<?php echo BASE_URL; ?>/venue-manage?id=<?php echo $uv['id']; ?>">
                    <span class="material-symbols-outlined text-[18px]" <?php echo $isVenueActive ? 'data-weight="fill"' : ''; ?>>storefront</span>
                    <span class="truncate flex-grow"><?php echo escape($uv['name']); ?></span>
                    <?php if ($uv['status'] === 'pending'): ?>
                        <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                    <?php elseif (!empty($uv['is_open'])): ?>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                    <?php else: ?>
                        <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="mt-4 pt-4 border-t border-white/5">
        <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-error hover:bg-error/10 transition-all duration-200 rounded-lg active:scale-[0.98] font-label-md text-label-md">
            <span class="material-symbols-outlined">logout</span>
            Çıkış Yap
        </a>
    </div>
</nav>
<?php endif; ?>

<!-- Main Content Canvas -->
<main class="<?php echo Auth::check() ? 'ml-0 md:ml-72' : ''; ?> flex-grow flex flex-col min-h-screen">
    <?php if (Auth::check()): ?>
    <!-- TopAppBar (Mobile) -->
    <header class="flex items-center justify-between px-8 py-4 bg-[#0F172A]/50 backdrop-blur-md font-manrope font-medium w-full sticky top-0 z-40 border-b border-white/10 md:hidden">
        <div class="flex items-center gap-2 text-xl font-bold text-[#FF6B35]">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Logo" class="h-6 w-auto opacity-90" width="24" height="24">
            Sociaera
        </div>
        <div class="flex items-center gap-4 text-slate-400">
            <a href="<?php echo BASE_URL; ?>/profile" class="text-slate-400 hover:text-[#FF6B35] transition-colors"><span class="material-symbols-outlined">account_circle</span></a>
            <a href="<?php echo BASE_URL; ?>/settings" class="text-slate-400 hover:text-[#FF6B35] transition-colors"><span class="material-symbols-outlined">tune</span></a>
        </div>
    </header>
    <?php endif; ?>

    <div class="flex-grow flex p-gutter gap-gutter max-w-7xl mx-auto w-full">
        <!-- Page Content Starts Here -->
