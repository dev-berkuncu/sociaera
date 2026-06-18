<?php
/**
 * Admin Panel — Header (Light Design System)
 * Matches the main app's white/orange Sociaera design.
 */

if (!isset($currentUser) && Auth::check()) {
    $currentUser = (new UserModel())->getById(Auth::id());
}

$_pendingReports = 0;
try { $_pendingReports = (new ReportModel())->getPendingCount(); } catch (\Throwable $e) {}
$_pendingVenues = $pendingVenues ?? 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="<?php echo csrfToken(); ?>">
<script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>

<title><?php echo View::title(($pageTitle ?? 'Admin') . ' — Admin Panel'); ?></title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary: '#F06D1F',
                'primary-hover': '#D95E10',
                'primary-light': '#FFA633',
                'primary-bg': '#FFF3EB',
                'app-bg': '#F5F4F0',
                'card-bg': '#ffffff',
                'section-bg': '#F8F7F5',
                'input-bg': '#F2F1EE',
                border: '#E8E7E3',
                't1': '#1A1A1A',
                't2': '#5C5C5C',
                't3': '#A0A0A0',
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
            }
        }
    }
}
</script>

<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<style>
:root {
    --cp: #F06D1F; --cp-hover: #D95E10; --cp-light: #FFA633; --cp-bg: #FFF3EB;
    --bg: #F5F4F0; --card: #ffffff; --section: #F8F7F5; --input: #F2F1EE;
    --border: #E8E7E3; --border-l: #F0EFEB;
    --t1: #1A1A1A; --t2: #5C5C5C; --t3: #A0A0A0;
    --font: 'Plus Jakarta Sans','Inter',sans-serif;
    --radius: 14px; --shadow: 0 1px 3px rgba(0,0,0,.07),0 4px 12px rgba(0,0,0,.05);
    --shadow-h: 0 4px 16px rgba(0,0,0,.1);
}

/* Base */
html, body { background: var(--bg) !important; color: var(--t1) !important; font-family: var(--font); margin:0; padding:0; min-height:100vh; }

/* Material Icons */
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    display:inline-block; width:1em; height:1em; overflow:hidden; white-space:nowrap; word-wrap:normal;
}
.material-symbols-outlined[data-fill="1"] { font-variation-settings: 'FILL' 1; }

/* ── ADMIN LAYOUT ── */
.admin-wrap { display:flex; min-height:100vh; }

/* ── SIDEBAR ── */
.admin-sidebar {
    width: 240px; flex-shrink:0;
    background: #fff;
    border-right: 1.5px solid var(--border);
    display: flex; flex-direction:column;
    position: fixed; left:0; top:0; height:100vh;
    overflow-y:auto; z-index:500;
    box-shadow: 2px 0 8px rgba(0,0,0,.05);
    transition: transform .25s ease;
}
@media(max-width:767px){ .admin-sidebar { transform:translateX(-100%); } .admin-sidebar.open { transform:translateX(0); } }

.admin-sidebar-logo {
    display:flex; align-items:center; gap:10px;
    padding: 18px 18px 14px;
    border-bottom: 1.5px solid var(--border);
    flex-shrink:0;
}
.admin-sidebar-logo-icon {
    width:38px; height:38px; border-radius:10px;
    background: linear-gradient(135deg,#F06D1F,#FFA633);
    display:flex; align-items:center; justify-content:center;
    color:#fff; flex-shrink:0;
    box-shadow: 0 4px 12px rgba(240,109,31,.3);
}
.admin-sidebar-logo-text { line-height:1.2; }
.admin-sidebar-logo-title { font-size:15px; font-weight:800; color:#F06D1F; letter-spacing:-.3px; }
.admin-sidebar-logo-sub { font-size:11px; color:var(--t3); font-weight:500; }

.admin-nav { flex:1; padding:10px 10px; }
.admin-nav-section {
    font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.9px;
    color:var(--t3); padding:10px 10px 4px; margin-top:6px;
}
.admin-nav-section:first-child { margin-top:0; }

.admin-nav-link {
    display:flex; align-items:center; gap:9px;
    padding: 8px 10px; border-radius:10px;
    font-size:13px; font-weight:600; color:var(--t2);
    text-decoration:none; transition:all .13s; position:relative;
    margin-bottom:1px;
}
.admin-nav-link:hover { background:var(--section); color:var(--t1); }
.admin-nav-link.active {
    background: var(--cp-bg); color: var(--cp);
    font-weight:700;
}
.admin-nav-link.active .msym { color:var(--cp); }
.admin-nav-link .msym { font-size:18px; color:var(--t3); flex-shrink:0; transition:color .13s; }
.admin-nav-link:hover .msym { color:var(--t2); }
.admin-nav-badge {
    margin-left:auto; background:#FEE2E2; color:#DC2626;
    font-size:10px; font-weight:700; padding:1px 6px; border-radius:99px;
}

.admin-sidebar-footer { padding:12px 10px; border-top:1.5px solid var(--border); flex-shrink:0; }
.admin-sidebar-user {
    display:flex; align-items:center; gap:10px;
    padding:8px 10px; border-radius:10px; background:var(--section);
}
.admin-sidebar-user-avatar { width:32px; height:32px; border-radius:50%; overflow:hidden; background:var(--input); flex-shrink:0; }
.admin-sidebar-user-avatar img { width:100%; height:100%; object-fit:cover; }
.admin-sidebar-user-name { font-size:12px; font-weight:700; color:var(--t1); }
.admin-sidebar-user-role { font-size:10px; color:var(--t3); }

/* ── MAIN CONTENT ── */
.admin-main { margin-left:240px; flex:1; display:flex; flex-direction:column; min-height:100vh; }
@media(max-width:767px){ .admin-main { margin-left:0; } }

/* ── TOP BAR (mobile) ── */
.admin-topbar {
    display:none; align-items:center; justify-content:space-between;
    padding:12px 16px; background:#fff; border-bottom:1.5px solid var(--border);
    position:sticky; top:0; z-index:400;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}
@media(max-width:767px){ .admin-topbar { display:flex; } }
.admin-topbar-title { font-size:15px; font-weight:800; color:var(--cp); }
.admin-hamburger {
    width:36px; height:36px; border:none; background:none; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    border-radius:8px; color:var(--t2); transition:background .13s;
}
.admin-hamburger:hover { background:var(--section); }

/* ── OVERLAY ── */
.admin-overlay {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.35);
    z-index:450; backdrop-filter:blur(2px);
}
.admin-overlay.open { display:block; }

/* ── PAGE BODY ── */
.admin-page { flex:1; padding:24px 28px 40px; max-width:1300px; width:100%; box-sizing:border-box; }
@media(max-width:767px){ .admin-page { padding:16px; } }

/* ── STAT CARD ── */
.admin-stat-card {
    background:#fff; border-radius:14px; padding:18px;
    border:1.5px solid var(--border);
    box-shadow:var(--shadow);
    display:flex; flex-direction:column; gap:6px;
    transition:box-shadow .15s, transform .15s;
}
.admin-stat-card:hover { box-shadow:var(--shadow-h); transform:translateY(-1px); }
.admin-stat-icon {
    width:40px; height:40px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    margin-bottom:4px; flex-shrink:0;
}
.admin-stat-value { font-size:24px; font-weight:800; color:var(--t1); line-height:1; }
.admin-stat-label { font-size:12px; font-weight:600; color:var(--t3); }

/* ── TABLE CARD ── */
.admin-table-card {
    background:#fff; border-radius:14px; border:1.5px solid var(--border);
    box-shadow:var(--shadow); overflow:hidden;
}
.admin-table-head {
    padding:14px 18px; border-bottom:1.5px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
}
.admin-table-title { font-size:14px; font-weight:700; color:var(--t1); display:flex; align-items:center; gap:7px; }
.admin-table-link { font-size:12px; font-weight:700; color:var(--cp); text-decoration:none; }
.admin-table-link:hover { text-decoration:underline; }

/* ── BUTTON ── */
.btn-admin { display:inline-flex; align-items:center; gap:6px; padding:8px 16px;
    border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; border:1.5px solid transparent;
    text-decoration:none; transition:all .13s; font-family:var(--font); }
.btn-admin-primary { background:var(--cp); color:#fff; border-color:var(--cp); }
.btn-admin-primary:hover { background:var(--cp-hover); transform:translateY(-1px); }
.btn-admin-ghost { background:transparent; color:var(--t2); border-color:var(--border); }
.btn-admin-ghost:hover { background:var(--section); }
.btn-admin-danger { background:#FEF2F2; color:#DC2626; border-color:#FCA5A5; }
.btn-admin-danger:hover { background:#FEE2E2; }
.btn-admin-sm { padding:5px 11px; font-size:12px; }

/* ── BADGE ── */
.badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:700; }
.badge-success { background:#DCFCE7; color:#15803D; }
.badge-warning { background:#FEF9C3; color:#A16207; }
.badge-danger { background:#FEE2E2; color:#DC2626; }
.badge-neutral { background:var(--section); color:var(--t2); }
.badge-orange { background:var(--cp-bg); color:var(--cp); }

/* ── FORM INPUTS ── */
.admin-input {
    width:100%; background:var(--input); border:1.5px solid transparent;
    border-radius:10px; padding:9px 13px; font-size:13px; font-family:var(--font);
    outline:none; color:var(--t1); transition:all .13s; box-sizing:border-box;
}
.admin-input:focus { border-color:var(--cp); background:#fff; }
.admin-label { display:block; font-size:12px; font-weight:700; color:var(--t2); margin-bottom:5px; text-transform:uppercase; letter-spacing:.5px; }

/* ── FLASH ── */
.admin-flash {
    position:fixed; top:20px; right:20px; z-index:9999;
    background:#fff; border:1.5px solid var(--border);
    border-left:4px solid var(--cp); border-radius:12px;
    padding:12px 18px; display:flex; align-items:center; gap:10px;
    box-shadow:0 8px 24px rgba(0,0,0,.12); font-size:13px; font-weight:600;
    animation:slideInRight .25s ease;
}
.admin-flash.flash-success { border-left-color:#16A34A; }
.admin-flash.flash-error { border-left-color:#DC2626; }
@keyframes slideInRight { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }

/* ── TABLE ── */
.admin-table { width:100%; border-collapse:collapse; }
.admin-table th { padding:10px 16px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:var(--t3); background:var(--section); text-align:left; border-bottom:1.5px solid var(--border); }
.admin-table td { padding:11px 16px; font-size:13px; color:var(--t1); border-bottom:1px solid var(--border-l); }
.admin-table tr:last-child td { border-bottom:none; }
.admin-table tbody tr:hover { background:var(--section); }
.admin-table-overflow { overflow-x:auto; }

/* ── SECTION HEADER ── */
.admin-section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; gap:12px; flex-wrap:wrap; }
.admin-page-title { font-size:20px; font-weight:800; color:var(--t1); display:flex; align-items:center; gap:8px; }

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width:6px; height:6px; }
::-webkit-scrollbar-track { background:var(--section); }
::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }
::-webkit-scrollbar-thumb:hover { background:var(--t3); }

/* ── CHART CARDS ── */
.admin-chart-card {
    background:#fff; border-radius:14px; border:1.5px solid var(--border);
    box-shadow:var(--shadow); padding:20px; overflow:hidden;
}
.admin-chart-title { font-size:14px; font-weight:700; color:var(--t1); display:flex; align-items:center; gap:7px; margin-bottom:16px; }

/* ── MISC ── */
.admin-divider { border:none; border-top:1.5px solid var(--border); margin:4px 0; }
</style>
</head>
<body>

<div class="admin-wrap">

<!-- ── SIDEBAR ── -->
<aside class="admin-sidebar" id="adminSidebar">

    <!-- Logo -->
    <div class="admin-sidebar-logo">
        <div class="admin-sidebar-logo-icon">
            <span class="material-symbols-outlined" style="font-size:20px;" data-fill="1">shield</span>
        </div>
        <div class="admin-sidebar-logo-text">
            <div class="admin-sidebar-logo-title">Admin Panel</div>
            <div class="admin-sidebar-logo-sub">Sociaera Yönetim</div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="admin-nav">
        <?php echo adminNavHtml($adminPage ?? '', $_pendingVenues, $_pendingReports); ?>
    </nav>

    <!-- User info at bottom -->
    <div class="admin-sidebar-footer">
        <div class="admin-sidebar-user">
            <div class="admin-sidebar-user-avatar">
                <?php $avUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username'] ?? 'Admin'); ?>
                <img src="<?php echo $avUrl; ?>" alt="avatar">
            </div>
            <div>
                <div class="admin-sidebar-user-name"><?php echo escape($currentUser['username'] ?? 'Admin'); ?></div>
                <div class="admin-sidebar-user-role"><?php echo escape(ucfirst(str_replace('_',' ', Auth::adminRole() ?? 'Admin'))); ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- ── OVERLAY (mobile) ── -->
<div class="admin-overlay" id="adminOverlay" onclick="closeAdminSidebar()"></div>

<!-- ── MAIN ── -->
<main class="admin-main">

    <!-- Mobile top bar -->
    <header class="admin-topbar">
        <button class="admin-hamburger" onclick="openAdminSidebar()" aria-label="Menü">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="admin-topbar-title">Admin Panel</div>
        <a href="<?php echo BASE_URL; ?>/dashboard" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:8px;color:var(--t2);text-decoration:none;" title="Siteye Dön">
            <span class="material-symbols-outlined" style="font-size:20px;">open_in_new</span>
        </a>
    </header>

    <?php
    // Flash messages
    $flash = Auth::getFlash();
    if ($flash):
    ?>
    <div class="admin-flash flash-<?php echo $flash['type']; ?>" id="adminFlash">
        <span class="material-symbols-outlined" style="font-size:18px;color:<?php echo $flash['type']==='success'?'#16A34A':'#DC2626'; ?>">
            <?php echo $flash['type']==='success' ? 'check_circle' : 'error'; ?>
        </span>
        <span><?php echo escape($flash['message']); ?></span>
        <button onclick="this.closest('.admin-flash').remove()" style="background:none;border:none;cursor:pointer;color:var(--t3);margin-left:8px;">
            <span class="material-symbols-outlined" style="font-size:18px;">close</span>
        </button>
    </div>
    <script>setTimeout(()=>document.getElementById('adminFlash')?.remove(),4000);</script>
    <?php endif; ?>

    <div class="admin-page">

<?php
/**
 * Admin nav HTML helper — sidebar items
 */
function adminNavHtml(string $activePage, int $pendingVenues, int $pendingReports): string
{
    $sections = [
        'Genel' => [
            'dashboard'  => ['icon'=>'dashboard',    'label'=>'Dashboard',     'url'=>'/admin/'],
        ],
        'İçerik' => [
            'users'      => ['icon'=>'people',       'label'=>'Kullanıcılar',  'url'=>'/admin/users'],
            'venues'     => ['icon'=>'location_on',  'label'=>'Mekanlar',      'url'=>'/admin/venues',   'badge'=>$pendingVenues],
            'posts'      => ['icon'=>'article',      'label'=>"Check-in'ler",  'url'=>'/admin/posts'],
            'comments'   => ['icon'=>'chat_bubble',  'label'=>'Yorumlar',      'url'=>'/admin/comments'],
        ],
        'Moderasyon' => [
            'moderation' => ['icon'=>'flag',         'label'=>'Raporlar',      'url'=>'/admin/reports',  'badge'=>$pendingReports],
            'mystery'    => ['icon'=>'person_search','label'=>'Gizli Müşteri', 'url'=>'/admin/mystery'],
        ],
        'Finans' => [
            'wallet'      => ['icon'=>'account_balance_wallet','label'=>'Cüzdan İşlemleri','url'=>'/admin/wallet'],
            'withdrawals' => ['icon'=>'payments',            'label'=>'Para Çekme',    'url'=>'/admin/withdrawals'],
        ],
        'Sistem' => [
            'ads'        => ['icon'=>'campaign',     'label'=>'Sponsorlar',    'url'=>'/admin/ads'],
            'settings'   => ['icon'=>'settings',     'label'=>'Ayarlar',       'url'=>'/admin/settings'],
        ],
    ];

    $html = '';
    foreach ($sections as $groupLabel => $items) {
        $groupHtml = '';
        foreach ($items as $key => $item) {
            if (!Auth::canAccess($key)) { continue; }
            $isActive = ($activePage === $key);
            $activeClass = $isActive ? ' active' : '';
            $fill = $isActive ? ' data-fill="1"' : '';
            $groupHtml .= '<a href="' . BASE_URL . $item['url'] . '" class="admin-nav-link' . $activeClass . '">';
            $groupHtml .= '<span class="material-symbols-outlined msym"' . $fill . '>' . $item['icon'] . '</span>';
            $groupHtml .= '<span>' . htmlspecialchars($item['label']) . '</span>';
            if (!empty($item['badge']) && $item['badge'] > 0) {
                $groupHtml .= '<span class="admin-nav-badge">' . $item['badge'] . '</span>';
            }
            $groupHtml .= '</a>';
        }
        if ($groupHtml !== '') {
            $html .= '<div class="admin-nav-section">' . htmlspecialchars($groupLabel) . '</div>';
            $html .= $groupHtml;
        }
    }

    $html .= '<hr class="admin-divider" style="margin:12px 2px;">';
    $html .= '<a href="' . BASE_URL . '/dashboard" class="admin-nav-link">';
    $html .= '<span class="material-symbols-outlined msym">arrow_back</span>';
    $html .= '<span>Siteye Dön</span>';
    $html .= '</a>';

    return $html;
}
?>

<script>
function openAdminSidebar(){
    document.getElementById('adminSidebar').classList.add('open');
    document.getElementById('adminOverlay').classList.add('open');
    document.body.style.overflow='hidden';
}
function closeAdminSidebar(){
    document.getElementById('adminSidebar').classList.remove('open');
    document.getElementById('adminOverlay').classList.remove('open');
    document.body.style.overflow='';
}
</script>
