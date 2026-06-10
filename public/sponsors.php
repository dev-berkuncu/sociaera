<?php
/**
 * Sociaera — Sponsorlarımız Sayfası
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Notification.php';

Auth::requireLogin();

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

// Sponsor verileri — ileride DB'den çekilebilir
$sponsors = [
    ['name' => 'COLOSSEUM', 'logo' => 'assets/img/sponsors/colosseum.png', 'url' => 'https://face-tr.gta.world/page/colosseum'],
    ['name' => 'Paradise Group', 'logo' => 'assets/img/sponsors/paradise-group.png', 'url' => 'https://face-tr.gta.world/page/paradise'],
];

$pageTitle = 'Sponsorlarımız';
$activeNav = 'sponsors';
require_once __DIR__ . '/partials/app_header.php';
?>

<section style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:768px; width:100%; padding-bottom:40px;">

    <!-- Page Header -->
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:4px;">
        <div style="width:48px; height:48px; background:linear-gradient(135deg, var(--color-primary), #ff9e7d); border-radius:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 8px 20px -5px rgba(240,109,31,0.35); transform:rotate(-3deg);">
            <span class="material-symbols-outlined" style="font-size:28px; color:#fff;">campaign</span>
        </div>
        <div>
            <h1 style="font-size:1.8rem; font-weight:900; color:var(--text-1); letter-spacing:-.02em; margin:0 0 4px;">Sponsorlarımız</h1>
            <p style="color:var(--text-3); font-size:13px; margin:0;"><?php echo APP_NAME; ?>'yı destekleyen markalar</p>
        </div>
    </div>

    <!-- Sponsors Grid -->
    <?php if (!empty($sponsors)): ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:16px;">
        <?php foreach ($sponsors as $sp): ?>
        <a href="<?php echo escape($sp['url'] ?? '#'); ?>" target="_blank" rel="noopener"
           style="background:#fff; border:1.5px solid var(--border); border-radius:16px; overflow:hidden; display:block; aspect-ratio:1; position:relative; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:border-color .2s, box-shadow .2s;"
           onmouseover="this.style.borderColor='var(--color-primary)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,.1)';"
           onmouseout="this.style.borderColor='var(--border)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,.06)';">
            <?php if (!empty($sp['logo'])): ?>
                <img src="<?php echo BASE_URL . '/' . escape($sp['logo']); ?>" alt="<?php echo escape($sp['name']); ?>"
                     style="position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding:12px; box-sizing:border-box;"
                     width="300" height="300" loading="lazy">
            <?php else: ?>
                <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;">
                    <span class="material-symbols-outlined" style="color:var(--text-3); font-size:48px;">store</span>
                </div>
            <?php endif; ?>
            <span style="position:absolute; bottom:0; left:0; right:0; background:linear-gradient(to top, rgba(0,0,0,0.6), transparent); padding:8px 10px 8px; font-size:11px; font-weight:700; color:#fff; text-align:center; display:block;">
                <?php echo escape($sp['name']); ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:16px; padding:40px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="width:80px; height:80px; margin:0 auto 16px; border-radius:20px; background:var(--bg-section); border:1px solid var(--border); display:flex; align-items:center; justify-content:center;">
            <span class="material-symbols-outlined" style="color:var(--text-3); font-size:40px;">storefront</span>
        </div>
        <h3 style="font-size:1.1rem; font-weight:800; color:var(--text-1); margin:0 0 8px;">Henüz Sponsor Bulunmuyor</h3>
        <p style="color:var(--text-3); font-size:13px; max-width:320px; margin:0 auto; line-height:1.6;">İlk sponsor sen ol! Markanı binlerce oyuncuya tanıtmak için bizimle iletişime geç.</p>
    </div>
    <?php endif; ?>

    <!-- CTA: Sponsor Ol -->
    <div style="background:linear-gradient(135deg, #fff8f5, #fff); border:1.5px solid rgba(240,109,31,0.2); border-radius:16px; padding:28px; text-align:center; position:relative; overflow:hidden; box-shadow:0 4px 20px rgba(240,109,31,0.08);">
        <div style="position:absolute; right:-20px; top:-20px; opacity:.05; line-height:1; pointer-events:none; user-select:none;">
            <span class="material-symbols-outlined" style="font-size:120px; color:var(--color-primary);">handshake</span>
        </div>
        <div style="position:relative; z-index:1;">
            <div style="width:64px; height:64px; margin:0 auto 16px; background:linear-gradient(135deg, var(--color-primary), #ff9e7d); border-radius:18px; display:flex; align-items:center; justify-content:center; box-shadow:0 10px 25px -5px rgba(240,109,31,0.4); transform:rotate(3deg);">
                <span class="material-symbols-outlined" style="font-size:32px; color:#fff;">rocket_launch</span>
            </div>
            <h2 style="font-size:1.5rem; font-weight:900; color:var(--text-1); margin:0 0 8px; letter-spacing:-.01em;">Sponsor Olmak İster Misin?</h2>
            <p style="color:var(--text-3); font-size:13px; max-width:360px; margin:0 auto 24px; line-height:1.6;">Markanı binlerce oyuncuya tanıt. Reklam alanlarımız hakkında bilgi almak için bizimle iletişime geç.</p>
            <div style="display:flex; flex-wrap:wrap; gap:12px; justify-content:center; align-items:center; max-width:400px; margin:0 auto;">
                <a href="mailto:info@sociaera.online"
                   style="display:inline-flex; align-items:center; justify-content:center; gap:8px; background:var(--color-primary); color:#fff; padding:12px 28px; border-radius:12px; font-weight:700; font-size:14px; text-decoration:none; box-shadow:0 4px 20px rgba(240,109,31,0.25); transition:opacity .15s;"
                   onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                    <span class="material-symbols-outlined" style="font-size:20px;">mail</span>
                    İletişime Geç
                </a>
                <a href="https://discord.gg/sociaera" target="_blank" rel="noopener"
                   style="display:inline-flex; align-items:center; justify-content:center; gap:8px; background:#fff; color:var(--text-1); border:1.5px solid var(--border); padding:12px 28px; border-radius:12px; font-weight:700; font-size:14px; text-decoration:none; transition:background .15s;"
                   onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background='#fff'">
                    <span class="material-symbols-outlined" style="font-size:20px;">forum</span>
                    Discord
                </a>
            </div>
        </div>
    </div>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
