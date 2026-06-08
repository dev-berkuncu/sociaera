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
<body class="dark overflow-x-hidden">

<?php if (Auth::check() && $currentUser): ?>
<!-- Top Navigation Bar -->
<header class="bg-background sticky top-0 z-50 flex justify-between items-center w-full px-6 py-3 max-w-[1920px] mx-auto">
    <div class="flex items-center gap-xl">
        <a href="<?php echo BASE_URL; ?>/dashboard" class="text-headline-md font-headline-md font-bold text-primary flex items-center gap-xs">
            <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings: 'FILL' 1;">hive</span>
            Sociaera
        </a>
        <div class="hidden md:flex relative group">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-body-lg">search</span>
            <input class="stitch-search-input bg-surface-container border-none rounded-lg pl-10 pr-12 py-2 text-body-md w-[400px] focus:ring-1 focus:ring-primary transition-all" placeholder="Mekan, kategori veya arkadaş ara..." type="text" onkeyup="if(event.key === 'Enter') window.location.href='<?php echo BASE_URL; ?>/venues?search=' + encodeURIComponent(this.value)"/>
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-label-sm text-on-surface-variant border border-outline-variant px-1.5 rounded-md font-mono">⌘ K</div>
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
        <a href="<?php echo BASE_URL; ?>/notifications" class="relative p-2 hover:bg-surface-container rounded-lg transition-colors active:scale-95 duration-150">
            <span class="material-symbols-outlined text-primary">notifications</span>
            <?php if ($notifCount > 0): ?>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-secondary rounded-full"></span>
            <?php endif; ?>
        </a>
        
        <!-- User Pill -->
        <a href="<?php echo BASE_URL; ?>/profile" class="flex items-center gap-sm bg-surface-container-high px-3 py-1.5 rounded-full cursor-pointer hover:bg-surface-variant transition-all">
            <div class="relative">
                <?php $avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']); ?>
                <img alt="<?php echo escape($currentUser['username']); ?>" class="w-8 h-8 rounded-full border border-primary" src="<?php echo $avatarUrl; ?>"/>
                <div class="absolute -bottom-1 -right-1 bg-primary text-[8px] font-bold px-1 rounded-full text-on-primary"><?php 
                    $stats = (new UserModel())->getStats($currentUser['id']);
                    echo floor(($stats['checkins'] ?? 0) / 15) + 1; 
                ?></div>
            </div>
            <div class="hidden lg:block">
                <div class="text-label-md font-bold leading-none"><?php echo escape($currentUser['username']); ?></div>
                <div class="text-[10px] text-primary">Seviye <?php echo floor(($stats['checkins'] ?? 0) / 15) + 1; ?></div>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant text-sm">expand_more</span>
        </a>
    </div>
</header>
<?php endif; ?>

<!-- Main Grid Container -->
<main class="max-w-[1920px] mx-auto px-6 grid grid-cols-12 gap-lg mt-md w-full flex-grow">
    
    <?php if (Auth::check() && $currentUser): ?>
    <!-- Sol Sidebar: Identity Rail -->
    <aside class="hidden lg:flex flex-col col-span-12 lg:col-span-3 xl:col-span-3 space-y-lg sticky top-20 h-[calc(100vh-100px)] overflow-y-auto custom-scrollbar pr-2 pb-6">
        
        <!-- Profile Card -->
        <div class="bg-surface-container-low rounded-xl overflow-hidden relative border border-outline-variant/30">
            <div class="h-32 hero-pattern relative flex items-center justify-center">
                <div class="relative mt-8">
                    <img alt="Profil Fotoğrafı" class="w-24 h-24 rounded-full border-4 border-surface-container-low shadow-xl object-cover" src="<?php echo $avatarUrl; ?>"/>
                    <?php if (!empty($currentUser['is_premium'])): ?>
                        <div class="absolute bottom-0 right-0 bg-primary-container p-1 rounded-full text-on-primary-container border-2 border-surface-container-low">
                            <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="p-md text-center pt-10">
                <h2 class="text-headline-sm font-headline-sm truncate px-2" title="<?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?>"><?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?></h2>
                <p class="text-label-md text-on-surface-variant">@<?php echo escape($currentUser['tag'] ?: $currentUser['username']); ?></p>
                
                <div class="grid grid-cols-2 gap-sm mt-lg">
                    <div class="bg-surface-container px-2 py-3 rounded-lg text-left">
                        <div class="flex items-center gap-xs text-primary mb-1">
                            <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                            <span class="text-headline-sm font-bold">16</span>
                        </div>
                        <span class="text-label-sm text-on-surface-variant">Günlük Seri</span>
                    </div>
                    <div class="bg-surface-container px-2 py-3 rounded-lg text-left">
                        <div class="flex items-center gap-xs text-primary mb-1">
                            <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">location_on</span>
                            <span class="text-xs font-bold"><?php echo $stats['checkins'] ?? 0; ?></span>
                        </div>
                        <span class="text-label-sm text-on-surface-variant">Check-in</span>
                    </div>
                </div>
                
                <div class="mt-lg text-left">
                    <div class="flex justify-between text-label-sm mb-xs">
                        <span class="text-on-surface-variant">Haftalık Hedef: 5</span>
                        <span class="text-primary font-bold"><?php echo min(5, $stats['checkins'] ?? 0); ?> / 5</span>
                    </div>
                    <div class="h-1.5 bg-surface-container-highest rounded-full overflow-hidden">
                        <div class="h-full bg-primary-container" style="width: <?php echo min(100, (($stats['checkins'] ?? 0) / 5) * 100); ?>%"></div>
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
                    ? 'w-full bg-primary-container text-on-primary-container font-bold py-3.5 rounded-lg flex items-center justify-start px-md gap-md transition-all hover:brightness-110 active:scale-95'
                    : 'w-full bg-surface-container-high text-on-surface font-semibold py-3.5 rounded-lg flex items-center justify-start px-md gap-md hover:bg-surface-variant transition-colors group';
                $iconClass = $isActive ? 'text-on-primary' : 'text-on-surface-variant group-hover:text-primary transition-colors';
            ?>
            <a class="<?php echo $linkClass; ?>" href="<?php echo BASE_URL . $item['url']; ?>">
                <span class="material-symbols-outlined text-[18px] <?php echo $iconClass; ?>" <?php echo $isActive ? 'data-weight="fill"' : ''; ?>><?php echo $item['icon']; ?></span>
                <span class="text-xs font-bold whitespace-nowrap"><?php echo $item['label']; ?></span>
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
    <?php $feedCols = !empty($hideSidebar) ? 'col-span-12 lg:col-span-9 xl:col-span-9' : 'col-span-12 lg:col-span-6 xl:col-span-6'; ?>
    <section class="<?php echo Auth::check() ? $feedCols : 'col-span-12'; ?> flex flex-col gap-6 pb-6">
        <!-- Page Content Starts Here -->
