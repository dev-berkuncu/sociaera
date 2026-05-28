<?php
/**
 * Admin Panel — Tailwind Header (V1)
 * Rol bazlı sidebar menü, genişletilmiş navigasyon
 */

if (!isset($currentUser) && Auth::check()) {
    $currentUser = (new UserModel())->getById(Auth::id());
}

// Rapor badge sayısı
$_pendingReports = 0;
try { $_pendingReports = (new ReportModel())->getPendingCount(); } catch (\Throwable $e) {}
$_pendingVenues = $pendingVenues ?? 0;
?>
<!DOCTYPE html>
<html class="dark" lang="tr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="<?php echo csrfToken(); ?>">
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title(($pageTitle ?? 'Admin') . ' — Admin'); ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@500;600;700;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "primary-container": "#ff6b35",
                    "primary": "#ffb59d",
                    "on-surface": "#dae2fd",
                    "background": "#0b1326",
                    "surface-container": "#171f33",
                    "surface-container-high": "#222a3d",
                    "error": "#ffb4ab",
                    "on-error": "#690005",
                    "error-container": "#93000a",
                    "on-background": "#dae2fd",
                    "outline": "#a98a80",
                    "secondary": "#bcc7de",
                    "tertiary": "#7bd0ff"
                },
                "fontFamily": {
                    "display": ["Manrope"],
                    "body": ["Inter"]
                },
                "fontSize": {
                    "label-md": ["14px", { "lineHeight": "1.2", "letterSpacing": "0.01em", "fontWeight": "500" }],
                    "label-sm": ["12px", { "lineHeight": "1.2", "letterSpacing": "0.05em", "fontWeight": "600" }],
                    "headline-md": ["24px", { "lineHeight": "1.4", "fontWeight": "600" }]
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
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: #1e293b; color: #fff; padding: 12px 20px; border-radius: 8px;
        display: flex; align-items: center; gap: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease forwards;
        border-left: 4px solid #ff6b35;
    }
    .flash-message.flash-success { border-color: #10b981; }
    .flash-message.flash-error { border-color: #ef4444; }
    .flash-message .flash-close { background: transparent; border: none; color: #94a3b8; cursor: pointer; }
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

    /* Mobile menu */
    .mobile-menu-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 45; }
    .mobile-menu-overlay.active { display: block; }
    .mobile-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
    .mobile-sidebar.active { transform: translateX(0); }
    /* FOUC prevention — Tailwind CDN işleyene kadar gizle */
    body { opacity: 0; }
    body.ready { opacity: 1; transition: opacity 0.15s ease-in; }
</style>
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
</head>
<body class="bg-background text-on-background font-body text-sm min-h-screen antialiased flex selection:bg-primary-container/30 selection:text-primary-container">
<script>document.body.classList.add('ready');</script>

<!-- Admin Sidebar -->
<nav class="hidden md:flex flex-col fixed left-0 top-0 h-full p-6 bg-[#1E293B]/80 backdrop-blur-lg font-display antialiased w-64 border-r border-white/10 shadow-[30px_0_30px_-15px_rgba(15,23,42,0.15)] z-50 overflow-y-auto">
    <div class="mb-8 flex items-center gap-3">
        <div class="w-10 h-10 bg-primary-container rounded-xl flex items-center justify-center text-white shadow-lg">
            <span class="material-symbols-outlined text-[22px]">shield</span>
        </div>
        <div>
            <h1 class="text-lg font-black tracking-tight text-[#FF6B35]">Admin Panel</h1>
            <span class="text-label-sm text-slate-400"><?php echo escape($currentUser['username'] ?? 'Admin'); ?></span>
            <?php if (Auth::adminRole()): ?>
                <span class="block text-[10px] text-primary-container/60"><?php echo escape(ucfirst(str_replace('_', ' ', Auth::adminRole()))); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php echo adminNavHtml($adminPage ?? '', $_pendingVenues, $_pendingReports); ?>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu-overlay" id="mobileOverlay" onclick="closeMobileMenu()"></div>
<nav class="mobile-sidebar md:hidden fixed left-0 top-0 h-full p-6 bg-[#1E293B] font-display antialiased w-72 border-r border-white/10 z-50 overflow-y-auto">
    <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary-container rounded-xl flex items-center justify-center text-white shadow-lg">
                <span class="material-symbols-outlined text-[22px]">shield</span>
            </div>
            <h1 class="text-lg font-black tracking-tight text-[#FF6B35]">Admin</h1>
        </div>
        <button onclick="closeMobileMenu()" class="text-slate-400 hover:text-white"><span class="material-symbols-outlined">close</span></button>
    </div>
    <?php echo adminNavHtml($adminPage ?? '', $_pendingVenues, $_pendingReports); ?>
</nav>

<!-- Main Content -->
<main class="ml-0 md:ml-64 flex-grow flex flex-col min-h-screen">
    <!-- Mobile Header -->
    <header class="flex items-center justify-between px-6 py-4 bg-[#0F172A]/50 backdrop-blur-md font-display font-medium w-full sticky top-0 z-40 border-b border-white/10 md:hidden">
        <button onclick="openMobileMenu()" class="text-slate-400 hover:text-[#FF6B35] transition-colors">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="text-lg font-bold text-[#FF6B35]">Admin</div>
        <a href="<?php echo BASE_URL; ?>/dashboard" class="text-slate-400 hover:text-[#FF6B35] transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
    </header>

    <?php
    // Flash messages
    $flash = Auth::getFlash();
    if ($flash):
    ?>
    <div class="flash-message flash-<?php echo $flash['type']; ?>" id="adminFlash">
        <span><?php echo escape($flash['message']); ?></span>
        <button class="flash-close" onclick="this.closest('.flash-message').remove()">✕</button>
    </div>
    <script>setTimeout(() => document.getElementById('adminFlash')?.remove(), 4000);</script>
    <?php endif; ?>

    <div class="flex-grow p-6 md:p-8 max-w-7xl w-full mx-auto">

<?php
/**
 * Admin nav HTML — hem desktop hem mobile sidebar için
 */
function adminNavHtml(string $activePage, int $pendingVenues, int $pendingReports): string
{
    $sections = [
        'Genel' => [
            'dashboard'  => ['icon' => 'dashboard',    'label' => 'Dashboard',     'url' => '/admin/',         'section' => 'dashboard'],
        ],
        'İçerik' => [
            'users'      => ['icon' => 'people',       'label' => 'Kullanıcılar',  'url' => '/admin/users',    'section' => 'users'],
            'venues'     => ['icon' => 'location_on',  'label' => 'Mekanlar',      'url' => '/admin/venues',   'section' => 'venues',   'badge' => $pendingVenues],
            'posts'      => ['icon' => 'article',      'label' => 'Check-in\'ler', 'url' => '/admin/posts',    'section' => 'checkins'],
            'comments'   => ['icon' => 'chat_bubble',  'label' => 'Yorumlar',      'url' => '/admin/comments', 'section' => 'comments'],
        ],
        'Moderasyon' => [
            'moderation' => ['icon' => 'flag',         'label' => 'Raporlar',      'url' => '/admin/reports',  'section' => 'moderation', 'badge' => $pendingReports],
        ],
        'Finans' => [
            'wallet'     => ['icon' => 'account_balance_wallet', 'label' => 'Cüzdan',  'url' => '/admin/wallet',  'section' => 'wallet'],
        ],
        'Sistem' => [
            'ads'        => ['icon' => 'campaign',     'label' => 'Sponsorlu',     'url' => '/admin/ads',      'section' => 'dashboard'],
            'settings'   => ['icon' => 'settings',     'label' => 'Ayarlar',       'url' => '/admin/settings', 'section' => 'settings'],
            'audit'      => ['icon' => 'history',      'label' => 'Audit Log',     'url' => '/admin/audit',    'section' => 'audit'],
        ],
    ];

    $html = '<ul class="flex flex-col gap-1 flex-grow">';

    foreach ($sections as $groupLabel => $items) {
        $html .= '<li class="mt-4 first:mt-0 mb-1"><span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold px-4">' . $groupLabel . '</span></li>';
        foreach ($items as $key => $item) {
            if (!Auth::canAccess($item['section'])) continue;

            $isActive = $activePage === $key;
            $linkClass = $isActive
                ? 'flex items-center gap-3 px-4 py-2.5 bg-[#FF6B35] text-white rounded-lg shadow-[0_0_5px_rgba(255,107,53,0.2)] active:scale-[0.98] transition-transform text-label-md'
                : 'flex items-center gap-3 px-4 py-2.5 text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all duration-200 rounded-lg active:scale-[0.98] text-label-md';

            $html .= '<li><a class="' . $linkClass . '" href="' . BASE_URL . $item['url'] . '">';
            $html .= '<span class="material-symbols-outlined text-[20px]" ' . ($isActive ? 'data-weight="fill"' : '') . '>' . $item['icon'] . '</span>';
            $html .= $item['label'];

            if (!empty($item['badge']) && $item['badge'] > 0) {
                $html .= '<span class="ml-auto bg-red-500/20 text-red-400 text-[10px] font-bold px-1.5 py-0.5 rounded-full">' . $item['badge'] . '</span>';
            }

            $html .= '</a></li>';
        }
    }

    $html .= '</ul>';
    $html .= '<div class="mt-6 pt-4 border-t border-white/5">';
    $html .= '<a href="' . BASE_URL . '/dashboard" class="flex items-center gap-3 px-4 py-2.5 text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all duration-200 rounded-lg text-label-md">';
    $html .= '<span class="material-symbols-outlined text-[20px]">arrow_back</span> Siteye Dön</a></div>';

    return $html;
}
?>

<script>
function openMobileMenu() {
    document.getElementById('mobileOverlay').classList.add('active');
    document.querySelector('.mobile-sidebar').classList.add('active');
}
function closeMobileMenu() {
    document.getElementById('mobileOverlay').classList.remove('active');
    document.querySelector('.mobile-sidebar').classList.remove('active');
}
</script>
