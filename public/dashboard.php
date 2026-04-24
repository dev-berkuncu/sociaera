<?php
/**
 * Sociaera — Dashboard (Ana Sayfa / Feed) - Tailwind Design
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/RateLimit.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Services/ImageUploader.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Checkin.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$feedFilter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));

$checkinModel = new CheckinModel();

if ($feedFilter === 'following') {
    $posts = $checkinModel->getFollowingFeed(Auth::id(), $page);
} else {
    $posts = $checkinModel->getGlobalFeed($page, 20, Auth::id());
}

$userModel = new UserModel();
$currentUser = $userModel->getById(Auth::id());

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Ana Sayfa';
$activeNav = 'dashboard';
?>
<!DOCTYPE html>
<html class="dark" lang="tr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="<?php echo csrfToken(); ?>">
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title($pageTitle); ?></title>
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
</style>
</head>
<body class="bg-background text-on-background font-body-md text-body-md min-h-screen antialiased flex selection:bg-primary-container/30 selection:text-primary-container">

<!-- SideNavBar -->
<nav class="hidden md:flex flex-col fixed left-0 top-0 h-full p-8 bg-[#1E293B]/80 backdrop-blur-lg font-manrope antialiased w-72 border-r border-white/10 shadow-[30px_0_30px_-15px_rgba(15,23,42,0.15)] z-50">
    <div class="mb-stack-lg flex items-center gap-4">
        <?php $avatarUrl = $currentUser['avatar'] ? BASE_URL . '/uploads/avatars/' . $currentUser['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['username']) . '&background=random'; ?>
        <img alt="User Avatar" class="w-12 h-12 rounded-full border-2 border-white/10 object-cover shadow-lg" src="<?php echo $avatarUrl; ?>"/>
        <div>
            <h1 class="text-2xl font-black tracking-tight text-[#FF6B35]">Sociaera</h1>
            <p class="text-label-sm font-label-sm text-slate-400">@<?php echo escape($currentUser['tag'] ?: $currentUser['username']); ?></p>
        </div>
    </div>
    <ul class="flex flex-col gap-2 flex-grow">
        <li>
            <a class="flex items-center gap-4 px-4 py-3 bg-[#FF6B35] text-white rounded-lg shadow-[0_0_5px_rgba(255,107,53,0.2)] active:scale-[0.98] transition-transform font-label-md text-label-md" href="<?php echo BASE_URL; ?>/dashboard">
                <span class="material-symbols-outlined" data-weight="fill">home</span>
                Ana Sayfa
            </a>
        </li>
        <li>
            <a class="flex items-center gap-4 px-4 py-3 text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all duration-200 rounded-lg active:scale-[0.98] transition-transform font-label-md text-label-md" href="<?php echo BASE_URL; ?>/venues">
                <span class="material-symbols-outlined">location_on</span>
                Mekanlar
            </a>
        </li>
        <li>
            <a class="flex items-center gap-4 px-4 py-3 text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all duration-200 rounded-lg active:scale-[0.98] transition-transform font-label-md text-label-md" href="<?php echo BASE_URL; ?>/leaderboard">
                <span class="material-symbols-outlined">leaderboard</span>
                Liderlik
            </a>
        </li>
        <li>
            <a class="flex items-center gap-4 px-4 py-3 text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all duration-200 rounded-lg active:scale-[0.98] transition-transform font-label-md text-label-md" href="<?php echo BASE_URL; ?>/members">
                <span class="material-symbols-outlined">people</span>
                Üyeler
            </a>
        </li>
        <li>
            <a class="flex items-center gap-4 px-4 py-3 text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all duration-200 rounded-lg active:scale-[0.98] transition-transform font-label-md text-label-md" href="<?php echo BASE_URL; ?>/settings">
                <span class="material-symbols-outlined">settings</span>
                Ayarlar
            </a>
        </li>
    </ul>
    <div class="mt-auto pt-8">
        <button onclick="document.getElementById('composeNote').focus()" class="w-full bg-primary-container text-white py-3 rounded-lg font-label-md text-label-md shadow-[0_0_15px_rgba(255,107,53,0.2)] hover:bg-primary-container/90 transition-colors flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Yeni Gönderi
        </button>
    </div>
</nav>

<!-- Main Content Canvas -->
<main class="ml-0 md:ml-72 flex-grow flex flex-col min-h-screen">
    <!-- TopAppBar (Mobile) -->
    <header class="flex items-center justify-between px-8 py-4 bg-[#0F172A]/50 backdrop-blur-md font-manrope font-medium w-full sticky top-0 z-40 border-b border-white/10 md:hidden">
        <div class="text-xl font-bold text-[#FF6B35]">Sociaera</div>
        <div class="flex items-center gap-4 text-slate-400">
            <a href="<?php echo BASE_URL; ?>/profile" class="text-slate-400 hover:text-[#FF6B35] transition-colors"><span class="material-symbols-outlined">account_circle</span></a>
            <a href="<?php echo BASE_URL; ?>/settings" class="text-slate-400 hover:text-[#FF6B35] transition-colors"><span class="material-symbols-outlined">tune</span></a>
        </div>
    </header>

    <div class="flex-grow flex p-gutter gap-gutter max-w-7xl mx-auto w-full">
        <!-- Center Main Feed -->
        <section class="flex-1 flex flex-col gap-stack-md max-w-3xl">
            <!-- Feed Filter Tabs -->
            <div class="flex items-center gap-8 border-b border-white/10 pb-2">
                <a href="?filter=all" class="<?php echo $feedFilter === 'all' ? 'text-primary-container font-label-md text-label-md border-b-2 border-primary-container pb-2 px-2 -mb-[10px]' : 'text-slate-400 hover:text-on-surface font-label-md text-label-md pb-2 px-2 transition-colors'; ?>">Herkes</a>
                <a href="?filter=following" class="<?php echo $feedFilter === 'following' ? 'text-primary-container font-label-md text-label-md border-b-2 border-primary-container pb-2 px-2 -mb-[10px]' : 'text-slate-400 hover:text-on-surface font-label-md text-label-md pb-2 px-2 transition-colors'; ?>">Takip</a>
            </div>

            <!-- Compose Box -->
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
                <form id="composeForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    <input type="hidden" name="venue_id" id="selectedVenueId" value="">
                    <div class="flex gap-4">
                        <img alt="User avatar" class="w-10 h-10 rounded-full object-cover border border-white/10 flex-shrink-0" src="<?php echo $avatarUrl; ?>"/>
                        <div class="flex-grow">
                            <div id="selectedVenueDisplay" class="flex items-center gap-2 mb-2 bg-white/5 w-fit px-3 py-1 rounded-full border border-white/10" style="display:none;">
                                <span class="material-symbols-outlined text-[16px] text-primary-container">location_on</span>
                                <span id="selectedVenueName" class="font-label-sm text-label-sm text-slate-300"></span>
                                <button type="button" onclick="removeVenue()" class="text-slate-500 hover:text-error transition-colors"><span class="material-symbols-outlined text-[16px]">close</span></button>
                            </div>
                            <textarea class="w-full bg-transparent border-none text-on-surface placeholder:text-slate-500 font-body-md text-body-md focus:ring-0 resize-none outline-none" name="note" id="composeNote" placeholder="Neredesin? Ne yapıyorsun?" rows="2"></textarea>
                            <div id="composePreview" class="mt-2 rounded-xl overflow-hidden border border-white/10 relative" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between pt-4 border-t border-white/5 relative">
                        <div class="flex items-center gap-2">
                            <label class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-white/5 text-secondary transition-colors cursor-pointer" title="Fotoğraf ekle">
                                <span class="material-symbols-outlined">image</span>
                                <input type="file" name="image" id="composeImage" accept="image/*" class="hidden">
                            </label>
                            <button type="button" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-white/5 text-secondary transition-colors" title="Mekan seç" onclick="document.getElementById('venueSearchWrap').style.display='block'; document.getElementById('venueSearchInput').focus();">
                                <span class="material-symbols-outlined">location_on</span>
                            </button>
                        </div>
                        
                        <!-- Venue Search Dropdown -->
                        <div id="venueSearchWrap" style="display:none;" class="absolute top-12 left-10 w-64 bg-[#1E293B] border border-white/10 rounded-lg shadow-xl z-50 p-2">
                            <input type="text" id="venueSearchInput" class="w-full bg-background border border-white/10 rounded p-2 text-on-surface text-sm focus:outline-none mb-2" placeholder="Mekan ara..." autocomplete="off">
                            <div id="venueDropdown" class="max-h-48 overflow-y-auto"></div>
                        </div>

                        <button type="submit" id="composeSubmitBtn" disabled class="bg-primary-container text-white px-6 py-2 rounded-lg font-label-md text-label-md shadow-[0_0_10px_rgba(255,107,53,0.2)] hover:bg-primary-container/90 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">Post</button>
                    </div>
                </form>
            </div>

            <!-- Feed Cards -->
            <div class="flex flex-col gap-stack-md pb-container-padding">
                <?php if (empty($posts)): ?>
                    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 text-center text-slate-400">
                        <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">campaign</span>
                        <p><?php echo $feedFilter === 'following' ? 'Takip ettiğin kullanıcıların henüz bir gönderi yok.' : 'Henüz hiç gönderi yok. İlk check-in\'ini yap!'; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="post-card bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] flex flex-col gap-4" id="post-<?php echo $post['id']; ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <?php $pAvatar = $post['avatar'] ? BASE_URL . '/uploads/avatars/' . $post['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=random'; ?>
                                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['tag'] ?: $post['username']); ?>">
                                        <img alt="User avatar" class="w-10 h-10 rounded-full object-cover border border-white/10" src="<?php echo $pAvatar; ?>"/>
                                    </a>
                                    <div>
                                        <div class="font-label-md text-label-md text-on-surface flex items-center gap-2">
                                            <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['tag'] ?: $post['username']); ?>" class="hover:text-primary-container transition-colors"><?php echo escape($post['username']); ?></a>
                                            <?php if (!empty($post['is_premium'])): ?>
                                                <span class="material-symbols-outlined text-[14px] text-[#7bd0ff]" title="Premium">diamond</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="font-label-sm text-label-sm text-slate-400"><?php echo timeAgo($post['created_at']); ?></div>
                                    </div>
                                </div>
                                <?php if (!empty($post['is_own'])): ?>
                                <button onclick="App.deletePost(this, <?php echo $post['id']; ?>)" class="text-slate-400 hover:text-error transition-colors"><span class="material-symbols-outlined">delete</span></button>
                                <?php endif; ?>
                            </div>

                            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $post['venue_id']; ?>" class="flex items-center gap-2 w-fit bg-white/5 hover:bg-white/10 transition-colors border border-white/10 px-3 py-1.5 rounded-full">
                                <span class="material-symbols-outlined text-[16px] text-primary-container">location_on</span>
                                <span class="font-label-sm text-label-sm text-slate-300"><?php echo escape($post['venue_name']); ?></span>
                            </a>

                            <?php if (!empty($post['note'])): ?>
                            <p class="font-body-md text-body-md text-on-surface"><?php echo linkify(parseMentions($post['note'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($post['image'])): ?>
                            <div class="rounded-xl overflow-hidden border border-white/10 shadow-lg mt-2">
                                <img alt="Venue photo" class="w-full h-auto object-cover max-h-[400px]" src="<?php echo uploadUrl('posts', $post['image']); ?>" loading="lazy"/>
                            </div>
                            <?php endif; ?>

                            <div class="flex items-center gap-6 mt-2 pt-4 border-t border-white/5 text-slate-400">
                                <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)" class="flex items-center gap-2 hover:text-primary-container transition-colors <?php echo !empty($post['viewer_liked']) ? 'liked text-primary-container' : ''; ?>">
                                    <span class="material-symbols-outlined"><?php echo !empty($post['viewer_liked']) ? 'favorite' : 'favorite'; ?></span> 
                                    <span class="font-label-sm text-label-sm action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>
                                </button>
                                <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)" class="flex items-center gap-2 hover:text-on-surface transition-colors">
                                    <span class="material-symbols-outlined">chat_bubble</span> 
                                    <span class="font-label-sm text-label-sm action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
                                </button>
                                <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>'); App.flash('Link kopyalandı!', 'success');" class="flex items-center gap-2 hover:text-on-surface transition-colors ml-auto">
                                    <span class="material-symbols-outlined">share</span>
                                </button>
                            </div>
                            
                            <!-- Inline Comments Section -->
                            <div class="post-comments-section mt-4 pt-4 border-t border-white/5" id="comments-section-<?php echo $post['id']; ?>" style="display:none;">
                                <div class="post-comments-list space-y-3 mb-3 max-h-64 overflow-y-auto pr-2" id="comments-list-<?php echo $post['id']; ?>">
                                    <div class="text-center text-slate-500 text-sm">Yorumlar yükleniyor...</div>
                                </div>
                                <?php if (Auth::check()): ?>
                                <form class="flex gap-2 items-center" onsubmit="App.submitInlineComment(this, <?php echo $post['id']; ?>); return false;">
                                    <input type="text" class="comment-input-inline w-full bg-background border border-white/10 rounded-full px-4 py-2 text-on-surface text-sm focus:outline-none focus:border-primary-container" placeholder="Yorumunu yaz..." maxlength="500" required>
                                    <button type="submit" class="comment-send-btn flex items-center justify-center w-10 h-10 rounded-full bg-primary-container text-white hover:bg-primary-container/90 transition-colors flex-shrink-0">
                                        <span class="material-symbols-outlined text-[18px]">send</span>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if (count($posts) >= 20): ?>
                        <div class="text-center mt-4">
                            <a href="?filter=<?php echo $feedFilter; ?>&page=<?php echo $page + 1; ?>" class="inline-flex items-center gap-2 bg-white/5 hover:bg-white/10 text-on-surface px-6 py-2 rounded-full transition-colors border border-white/10">
                                <span class="material-symbols-outlined">arrow_downward</span> Daha Fazla
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Right Sidebar: Trending -->
        <aside class="hidden lg:flex flex-col w-80 gap-stack-md">
            <?php if (!empty($trendVenues)): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-6">Popüler Mekanlar</h2>
                <ul class="flex flex-col gap-4">
                    <?php foreach ($trendVenues as $tv): ?>
                    <li class="flex items-center gap-4 group cursor-pointer" onclick="window.location.href='<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $tv['id']; ?>'">
                        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary-container border border-white/5 group-hover:border-primary-container/50 transition-colors flex-shrink-0">
                            <span class="material-symbols-outlined">restaurant</span>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="font-label-md text-label-md text-on-surface group-hover:text-primary-container transition-colors truncate"><?php echo escape($tv['name']); ?></div>
                            <div class="font-label-sm text-label-sm text-slate-400 truncate"><?php echo escape($tv['category'] ?? 'Mekan'); ?> • <?php echo $tv['weekly_checkins']; ?> Check-in</div>
                        </div>
                        <span class="material-symbols-outlined text-slate-500 group-hover:text-on-surface transition-colors text-[20px]">chevron_right</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo BASE_URL; ?>/venues" class="block text-center w-full mt-6 py-2 text-primary-container font-label-md text-label-md hover:bg-white/5 rounded-lg transition-colors">Tümünü Gör</a>
            </div>
            <?php endif; ?>

            <?php if (!empty($miniLeaderboard)): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-6 flex items-center gap-2">
                    Liderlik <span class="material-symbols-outlined text-primary-container text-[20px]">military_tech</span>
                </h2>
                <ul class="flex flex-col gap-4">
                    <?php foreach ($miniLeaderboard as $i => $lb): ?>
                    <li class="flex items-center gap-3 cursor-pointer" onclick="window.location.href='<?php echo BASE_URL; ?>/profile?u=<?php echo escape($lb['tag'] ?: $lb['username']); ?>'">
                        <div class="relative flex-shrink-0">
                            <?php $lbAvatar = $lb['avatar'] ? BASE_URL . '/uploads/avatars/' . $lb['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($lb['username']) . '&background=random'; ?>
                            <img alt="Leader avatar" class="w-10 h-10 rounded-full object-cover border border-white/10" src="<?php echo $lbAvatar; ?>"/>
                            <?php 
                            $badgeClass = 'bg-slate-700 text-white';
                            if ($i === 0) $badgeClass = 'bg-primary-container text-white';
                            elseif ($i === 1) $badgeClass = 'bg-slate-300 text-slate-800';
                            elseif ($i === 2) $badgeClass = 'bg-[#cd7f32] text-white'; 
                            ?>
                            <div class="absolute -top-1 -right-1 <?php echo $badgeClass; ?> text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full border border-background"><?php echo $i + 1; ?></div>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="font-label-md text-label-md text-on-surface truncate"><?php echo escape($lb['username']); ?></div>
                            <div class="font-label-sm text-label-sm text-slate-400 truncate"><?php echo $lb['checkin_count']; ?> Puan</div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo BASE_URL; ?>/leaderboard" class="block text-center w-full mt-6 py-2 text-primary-container font-label-md text-label-md hover:bg-white/5 rounded-lg transition-colors">Tümünü Gör</a>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</main>

<script src="<?php echo asset('js/app.js'); ?>?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const composeForm = document.getElementById('composeForm');
    const composeNote = document.getElementById('composeNote');
    const composeBtn = document.getElementById('composeSubmitBtn');
    const venueIdInput = document.getElementById('selectedVenueId');

    if (!composeForm || !composeNote) return;

    function checkCompose() {
        composeBtn.disabled = !(venueIdInput.value && composeNote.value.trim());
    }
    composeNote.addEventListener('input', checkCompose);

    const venueInput = document.getElementById('venueSearchInput');
    const venueDropdown = document.getElementById('venueDropdown');

    if (venueInput && venueDropdown) {
        App.initVenueSearch(venueInput, venueDropdown, (id, name) => {
            venueIdInput.value = id;
            document.getElementById('selectedVenueName').textContent = name;
            document.getElementById('selectedVenueDisplay').style.display = 'inline-flex';
            document.getElementById('venueSearchWrap').style.display = 'none';
            checkCompose();
        });
    }

    window.removeVenue = function() {
        venueIdInput.value = '';
        document.getElementById('selectedVenueDisplay').style.display = 'none';
        checkCompose();
    };

    composeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        composeBtn.disabled = true;
        composeBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[20px]">progress_activity</span>';

        const formData = new FormData(composeForm);
        const res = await App.post(App.baseUrl + '/api/create-post', formData);

        if (res.ok) {
            App.flash('Check-in başarılı! 📍', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            App.flash(res.error || 'Hata oluştu.', 'error');
            composeBtn.disabled = false;
            composeBtn.innerHTML = 'Post';
        }
    });
});
</script>
</body>
</html>
