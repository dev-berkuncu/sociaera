<?php
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

$pageTitle = 'Premium';
$activeNav = 'premium';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-xl w-full mx-auto mt-8">
    <div class="bg-gradient-to-br from-[#1E293B]/80 to-surface-container border border-primary-container/20 rounded-2xl p-8 md:p-12 text-center shadow-[0_20px_40px_-15px_rgba(255,107,53,0.2)] relative overflow-hidden">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-5 text-primary-container leading-none select-none">
            <span class="material-symbols-outlined" style="font-size:inherit;">diamond</span>
        </div>
        
        <div class="relative z-10">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-container to-[#ff9e7d] rounded-2xl flex items-center justify-center text-white mb-6 shadow-[0_10px_25px_-5px_rgba(255,107,53,0.5)] transform -rotate-6">
                <span class="material-symbols-outlined text-[40px]">diamond</span>
            </div>
            
            <h1 class="text-3xl md:text-4xl font-black text-on-surface mb-2"><?php echo APP_NAME; ?> Premium</h1>
            <p class="text-primary-fixed-dim text-lg mb-8 font-medium">Deneyimini bir üst seviyeye taşı</p>
            
            <ul class="flex flex-col gap-4 text-left max-w-sm mx-auto mb-10">
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span> 
                    <span>Reklamsız deneyim</span>
                </li>
                <li class="flex items-center justify-between text-on-surface text-lg">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary-container">check_circle</span> 
                        <span>Profil rozeti</span>
                    </div>
                    <span class="bg-[#7bd0ff]/20 text-[#7bd0ff] text-xs font-bold px-2 py-1 rounded border border-[#7bd0ff]/30 uppercase tracking-wider flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">diamond</span> Premium</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span> 
                    <span>Daha yüksek yükleme limiti</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span> 
                    <span>Öncelikli destek</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span> 
                    <span>Özel profil temaları</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface text-lg">
                    <span class="material-symbols-outlined text-primary-container">check_circle</span> 
                    <span>Sıralama tablosunda öne çıkma</span>
                </li>
            </ul>
            
            <button disabled class="w-full bg-white/5 border border-white/10 text-slate-400 py-4 rounded-xl font-bold flex items-center justify-center gap-2 cursor-not-allowed">
                <span class="material-symbols-outlined">credit_card</span> Yakında...
            </button>
            <p class="text-xs text-slate-500 mt-4 uppercase tracking-wider font-semibold">Ödeme sistemi yapım aşamasındadır.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
