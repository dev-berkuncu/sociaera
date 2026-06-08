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
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
                        "display-lg": ["Plus Jakarta Sans"],
                        "label-sm": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"],
                        "headline-md": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"]
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
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    
    .hero-pattern {
        background: linear-gradient(to bottom, rgba(255,107,53,0.18) 0%, rgba(11,19,38,0) 100%),
                    url('https://www.transparenttextures.com/patterns/carbon-fibre.png');
    }
    
    .glass-effect {
        backdrop-filter: blur(12px);
        background: rgba(23, 31, 51, 0.75);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
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
<!-- SideNavBar: Identity Rail -->
<nav class="hidden md:flex flex-col fixed left-0 top-0 h-full p-5 w-72 bg-[#0e0e0f]/95 z-50 overflow-y-auto custom-scrollbar border-r border-white/5 gap-4">
    <!-- Profile Bento Card -->
    <div class="bg-surface-container-low rounded-xl overflow-hidden relative border border-white/5 shadow-md flex-shrink-0">
        <!-- Carbon Fibre Hero Background -->
        <div class="h-20 hero-pattern relative flex items-center justify-center">
            <div class="absolute -bottom-8">
                <?php $avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']); ?>
                <a href="<?php echo BASE_URL; ?>/profile">
                    <img alt="User Avatar" class="w-16 h-16 rounded-full border-4 border-surface-container-low object-cover shadow-lg hover:border-[#FF6B35] transition-all" src="<?php echo $avatarUrl; ?>" width="64" height="64"/>
                </a>
            </div>
        </div>
        <div class="p-4 text-center pt-10">
            <h2 class="text-sm font-bold text-on-surface truncate px-2" title="<?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?>"><?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?></h2>
            <a href="<?php echo BASE_URL; ?>/profile" class="text-[10px] font-mono text-slate-500 hover:text-white transition-colors">@<?php echo escape($currentUser['tag'] ?: $currentUser['username']); ?></a>
            
            <?php 
            // Fetch stats dynamically
            $stats = (new UserModel())->getStats($currentUser['id']);
            ?>
            <div class="grid grid-cols-2 gap-2 mt-4">
                <div class="bg-surface-container px-2 py-2 rounded-lg text-left">
                    <div class="flex items-center gap-1 text-[#FF6B35] mb-0.5">
                        <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">explore</span>
                        <span class="text-xs font-bold"><?php echo $stats['venues'] ?? 0; ?></span>
                    </div>
                    <span class="text-[8px] text-slate-500 uppercase tracking-wide">Keşif</span>
                </div>
                <div class="bg-surface-container px-2 py-2 rounded-lg text-left">
                    <div class="flex items-center gap-1 text-[#FF6B35] mb-0.5">
                        <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">location_on</span>
                        <span class="text-xs font-bold"><?php echo $stats['checkins'] ?? 0; ?></span>
                    </div>
                    <span class="text-[8px] text-slate-500 uppercase tracking-wide">Check-in</span>
                </div>
            </div>
            
            <div class="mt-4 text-left">
                <div class="flex justify-between text-[10px] mb-1">
                    <span class="text-slate-500">Haftalık Hedef: 5 check-in</span>
                    <span class="text-[#FF6B35] font-bold"><?php echo min(5, $stats['checkins'] ?? 0); ?> / 5</span>
                </div>
                <div class="h-1 bg-surface-container-highest rounded-full overflow-hidden">
                    <div class="h-full bg-[#FF6B35]" style="width: <?php echo min(100, (($stats['checkins'] ?? 0) / 5) * 100); ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation List -->
    <ul class="flex flex-col gap-1 flex-grow">
        <?php
        $navItems = [
            'dashboard'    => ['icon' => 'home',                    'label' => 'Ana Sayfa',       'url' => '/dashboard'],
            'venues'       => ['icon' => 'location_on',             'label' => 'Mekanlar',        'url' => '/venues'],
            'leaderboard'  => ['icon' => 'leaderboard',             'label' => 'Liderlik',        'url' => '/leaderboard'],
            'missions'     => ['icon' => 'emoji_events',            'label' => 'Görevler',        'url' => '/missions'],
            'members'      => ['icon' => 'people',                  'label' => 'Üyeler',          'url' => '/members'],
            'wallet'       => ['icon' => 'account_balance_wallet',  'label' => 'Cüzdan',          'url' => '/wallet'],
            'premium'      => ['icon' => 'diamond',                 'label' => 'Premium',         'url' => '/premium'],
            'kampanyalar'  => ['icon' => 'campaign',                 'label' => 'Kampanyalar',     'url' => '/kampanyalar'],
            'notifications'=> ['icon' => 'notifications',           'label' => 'Bildirimler',     'url' => '/notifications'],
            'settings'     => ['icon' => 'settings',                'label' => 'Ayarlar',         'url' => '/settings'],
        ];
        $activeNav = $activeNav ?? '';
        foreach ($navItems as $key => $item):
            $isActive = $activeNav === $key;
            if ($isActive) {
                $linkClass = 'w-full bg-[#FF6B35] text-white font-bold py-2.5 rounded-lg flex items-center justify-start px-4 gap-3 transition-all duration-150';
            } else {
                $linkClass = 'w-full bg-surface-container-high/40 hover:bg-surface-container-high text-slate-300 font-semibold py-2.5 rounded-lg flex items-center justify-start px-4 gap-3 transition-all hover:text-white duration-150 group';
            }
        ?>
        <li>
            <a class="<?php echo $linkClass; ?>" href="<?php echo BASE_URL . $item['url']; ?>">
                <span class="material-symbols-outlined text-[18px] <?php echo $isActive ? 'text-white' : 'text-slate-500 group-hover:text-[#FF6B35]'; ?> transition-colors" <?php echo $isActive ? 'data-weight="fill"' : ''; ?>><?php echo $item['icon']; ?></span>
                <span class="text-xs tracking-wide"><?php echo $item['label']; ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Owned Venues -->
    <?php
    $userVenues = [];
    try {
        $userVenues = (new VenueModel())->getByOwner(Auth::id());
    } catch (Exception $e) {}
    if (!empty($userVenues)):
    ?>
    <div class="mt-2 pt-3 border-t border-white/5 flex-shrink-0">
        <h3 class="text-[9px] text-slate-500 uppercase tracking-widest px-3 mb-2 font-mono">İşletmelerim</h3>
        <ul class="flex flex-col gap-1">
            <?php foreach ($userVenues as $uv):
                $isVenueActive = ($activeNav ?? '') === 'venue_manage_' . $uv['id'];
                $venueLinkClass = $isVenueActive
                    ? 'w-full bg-[#FF6B35] text-white font-bold py-2 rounded-lg flex items-center justify-start px-4 gap-3 transition-all'
                    : 'w-full bg-surface-container-high/20 hover:bg-surface-container-high text-slate-400 hover:text-white font-medium py-2 rounded-lg flex items-center justify-start px-4 gap-3 transition-all duration-150';
            ?>
            <li>
                <a class="<?php echo $venueLinkClass; ?>" href="<?php echo BASE_URL; ?>/venue-manage?id=<?php echo $uv['id']; ?>">
                    <span class="material-symbols-outlined text-[16px] text-slate-500" <?php echo $isVenueActive ? 'data-weight="fill"' : ''; ?>>storefront</span>
                    <span class="truncate text-xs flex-grow"><?php echo escape($uv['name']); ?></span>
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
    ?>
    <?php if (!empty($otherCharacters) || !empty($currentUser['gta_user_id'])): ?>
    <div class="mt-2 pt-3 border-t border-white/5 flex-shrink-0">
        <h3 class="text-[9px] text-slate-500 uppercase tracking-widest px-3 mb-2 font-mono flex items-center justify-between">
            <span>Karakter Değiştir</span>
            <span class="material-symbols-outlined text-[14px] text-slate-600">switch_account</span>
        </h3>
        <ul class="flex flex-col gap-1">
            <?php foreach ($otherCharacters as $oc): ?>
            <li>
                <form action="<?php echo BASE_URL; ?>/switch-character" method="POST" class="m-0">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="target_user_id" value="<?php echo $oc['id']; ?>">
                    <button type="submit" class="w-full bg-surface-container-high/20 hover:bg-surface-container-high text-slate-400 hover:text-white py-1.5 px-4 rounded-lg flex items-center justify-start gap-3 transition-all duration-150 text-left">
                        <span class="shrink-0">
                            <?php $ocAvatarUrl = safeAvatarUrl($oc['avatar'] ?? null, $oc['username']); ?>
                            <img src="<?php echo $ocAvatarUrl; ?>" alt="Avatar" class="w-5 h-5 rounded-full border border-white/10 object-cover shadow-sm" width="20" height="20">
                        </span>
                        <span class="truncate text-xs flex-grow"><?php echo escape($oc['gta_character_name'] ?: $oc['username']); ?></span>
                        <span class="material-symbols-outlined text-slate-600 text-[14px]">swap_horiz</span>
                    </button>
                </form>
            </li>
            <?php endforeach; ?>
            
            <li>
                <a href="<?php echo BASE_URL; ?>/oauth-login" class="w-full hover:bg-[#FF6B35]/15 text-slate-500 hover:text-[#FF6B35] py-2 px-4 rounded-lg flex items-center justify-start gap-3 transition-all duration-150">
                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                    <span class="text-xs font-bold">Karakter Bağla</span>
                </a>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Logout -->
    <div class="mt-auto pt-3 border-t border-white/5 flex-shrink-0">
        <a href="<?php echo BASE_URL; ?>/logout" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold py-2 rounded-lg flex items-center justify-start px-4 gap-3 transition-all duration-150">
            <span class="material-symbols-outlined text-[18px]">logout</span>
            <span class="text-xs">Çıkış Yap</span>
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
