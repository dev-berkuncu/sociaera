<?php
/**
 * Sociaera — Sponsorlarımız Sayfası (Tailwind Design)
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
    ['name' => 'COLOSSEUM', 'logo' => 'assets/img/sponsors/colosseum.png', 'url' => 'https://cfx.re/join/j7e8ba'],
    ['name' => 'Örnek Marka 2', 'logo' => 'assets/img/sponsors/marka2.png', 'url' => 'https://example.com'],
];

$pageTitle = 'Sponsorlarımız';
$activeNav = 'sponsors';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto">

    <!-- Page Header -->
    <div class="flex items-center gap-4 mb-2">
        <div class="w-12 h-12 bg-gradient-to-br from-primary-container to-[#ff9e7d] rounded-xl flex items-center justify-center text-white shadow-[0_8px_20px_-5px_rgba(255,107,53,0.4)] transform -rotate-3">
            <span class="material-symbols-outlined text-[28px]">campaign</span>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-black text-on-surface tracking-tight">Sponsorlarımız</h1>
            <p class="text-slate-400 font-label-md text-label-md"><?php echo APP_NAME; ?>'yı destekleyen markalar</p>
        </div>
    </div>

    <!-- Sponsors Grid -->
    <?php if (!empty($sponsors)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        <?php foreach ($sponsors as $sp): ?>
        <a href="<?php echo escape($sp['url'] ?? '#'); ?>" target="_blank" rel="noopener"
           class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 flex flex-col items-center justify-center gap-3 hover:border-primary-container/40 hover:bg-white/[0.06] transition-all duration-300 group shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)]">
            <div class="w-16 h-16 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden group-hover:border-primary-container/30 transition-colors">
                <?php if (!empty($sp['logo'])): ?>
                    <img src="<?php echo BASE_URL . '/' . escape($sp['logo']); ?>" alt="<?php echo escape($sp['name']); ?>" class="w-12 h-12 object-contain">
                <?php else: ?>
                    <span class="material-symbols-outlined text-slate-500 text-[28px]">store</span>
                <?php endif; ?>
            </div>
            <span class="font-label-md text-label-md text-on-surface text-center group-hover:text-primary-container transition-colors truncate w-full"><?php echo escape($sp['name']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-10 text-center shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <div class="w-20 h-20 mx-auto rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mb-5">
            <span class="material-symbols-outlined text-slate-500 text-[40px]">storefront</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Henüz Sponsor Bulunmuyor</h3>
        <p class="text-slate-400 max-w-sm mx-auto">İlk sponsor sen ol! Markanı binlerce oyuncuya tanıtmak için bizimle iletişime geç.</p>
    </div>
    <?php endif; ?>

    <!-- CTA: Sponsor Ol -->
    <div class="bg-gradient-to-br from-[#1E293B]/80 to-surface-container border border-primary-container/20 rounded-2xl p-8 md:p-10 text-center shadow-[0_20px_40px_-15px_rgba(255,107,53,0.15)] relative overflow-hidden">
        <div class="absolute -right-8 -top-8 text-[120px] opacity-5 text-primary-container leading-none select-none">
            <span class="material-symbols-outlined" style="font-size:inherit;">handshake</span>
        </div>
        <div class="relative z-10">
            <div class="w-16 h-16 mx-auto bg-gradient-to-br from-primary-container to-[#ff9e7d] rounded-2xl flex items-center justify-center text-white mb-5 shadow-[0_10px_25px_-5px_rgba(255,107,53,0.5)] transform rotate-3">
                <span class="material-symbols-outlined text-[32px]">rocket_launch</span>
            </div>
            <h2 class="text-2xl md:text-3xl font-black text-on-surface mb-2">Sponsor Olmak İster Misin?</h2>
            <p class="text-slate-400 mb-8 max-w-md mx-auto">Markanı binlerce oyuncuya tanıt. Reklam alanlarımız hakkında bilgi almak için bizimle iletişime geç.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center items-center max-w-md mx-auto">
                <a href="mailto:info@sociaera.online" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-primary-container hover:bg-primary-container/90 text-white px-8 py-3.5 rounded-xl font-bold shadow-[0_0_20px_rgba(255,107,53,0.3)] hover:shadow-[0_0_30px_rgba(255,107,53,0.4)] transition-all active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[20px]">mail</span>
                    İletişime Geç
                </a>
                <a href="https://discord.gg/sociaera" target="_blank" rel="noopener" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 text-on-surface border border-white/10 px-8 py-3.5 rounded-xl font-bold transition-all active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[20px]">forum</span>
                    Discord
                </a>
            </div>
        </div>
    </div>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
