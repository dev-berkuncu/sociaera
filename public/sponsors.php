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

    <!-- Sponsor Tiers -->

    <!-- Gold Sponsors -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-[#FFD700]/20 rounded-2xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <div class="bg-gradient-to-r from-[#FFD700]/10 to-transparent px-6 py-4 border-b border-[#FFD700]/10 flex items-center gap-3">
            <span class="material-symbols-outlined text-[#FFD700] text-[24px]">workspace_premium</span>
            <h2 class="font-headline-md text-headline-md text-[#FFD700] tracking-tight">Altın Sponsorlar</h2>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-center py-8 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-16 h-16 rounded-full bg-[#FFD700]/10 border-2 border-dashed border-[#FFD700]/30 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[#FFD700]/50 text-[32px]">add_circle</span>
                    </div>
                    <p class="text-slate-400 text-sm max-w-xs">Bu alan sizin markanız için hazır. Altın sponsor olarak binlerce oyuncuya ulaşın.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Silver Sponsors -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-[#C0C0C0]/15 rounded-2xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <div class="bg-gradient-to-r from-[#C0C0C0]/10 to-transparent px-6 py-4 border-b border-[#C0C0C0]/10 flex items-center gap-3">
            <span class="material-symbols-outlined text-[#C0C0C0] text-[24px]">military_tech</span>
            <h2 class="font-headline-md text-headline-md text-[#C0C0C0] tracking-tight">Gümüş Sponsorlar</h2>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-center py-8 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-16 h-16 rounded-full bg-[#C0C0C0]/10 border-2 border-dashed border-[#C0C0C0]/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[#C0C0C0]/50 text-[32px]">add_circle</span>
                    </div>
                    <p class="text-slate-400 text-sm max-w-xs">Gümüş sponsor olarak platformumuzda markanızı öne çıkarın.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bronze Sponsors -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-[#cd7f32]/15 rounded-2xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <div class="bg-gradient-to-r from-[#cd7f32]/10 to-transparent px-6 py-4 border-b border-[#cd7f32]/10 flex items-center gap-3">
            <span class="material-symbols-outlined text-[#cd7f32] text-[24px]">shield</span>
            <h2 class="font-headline-md text-headline-md text-[#cd7f32] tracking-tight">Bronz Sponsorlar</h2>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-center py-8 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-16 h-16 rounded-full bg-[#cd7f32]/10 border-2 border-dashed border-[#cd7f32]/20 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[#cd7f32]/50 text-[32px]">add_circle</span>
                    </div>
                    <p class="text-slate-400 text-sm max-w-xs">Bronz sponsorluk ile topluluğumuza katkı sağlayın.</p>
                </div>
            </div>
        </div>
    </div>

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
            <p class="text-slate-400 mb-8 max-w-md mx-auto">Markanı binlerce oyuncuya tanıt. Platformumuzdaki reklam alanlarımız hakkında bilgi almak için bizimle iletişime geç.</p>

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

            <p class="text-xs text-slate-500 mt-6 uppercase tracking-wider font-semibold">Sponsorluk paketleri hakkında detaylı bilgi alın</p>
        </div>
    </div>

    <!-- Sponsor Benefits Info -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <h3 class="font-headline-md text-headline-md text-on-surface mb-6 flex items-center gap-2">
            Sponsor Avantajları <span class="material-symbols-outlined text-primary-container text-[22px]">stars</span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
            $benefits = [
                ['icon' => 'visibility', 'title' => 'Marka Görünürlüğü', 'desc' => 'Platformdaki tüm kullanıcılara ulaşın'],
                ['icon' => 'ads_click', 'title' => 'Reklam Alanları', 'desc' => 'Sidebar ve feed\'de özel banner alanları'],
                ['icon' => 'groups', 'title' => 'Topluluk Desteği', 'desc' => 'Aktif oyuncu topluluğuna doğrudan erişim'],
                ['icon' => 'trending_up', 'title' => 'Analiz & Raporlama', 'desc' => 'Reklam performansınızı takip edin'],
                ['icon' => 'verified', 'title' => 'Onaylı Marka Profili', 'desc' => 'Platformda doğrulanmış marka sayfası'],
                ['icon' => 'event', 'title' => 'Etkinlik Desteği', 'desc' => 'Özel etkinlik ve kampanyalar düzenleyin'],
            ];
            foreach ($benefits as $b):
            ?>
            <div class="flex items-start gap-4 p-4 rounded-xl bg-white/[0.03] border border-white/5 hover:border-primary-container/20 hover:bg-white/[0.05] transition-all duration-300 group">
                <div class="w-10 h-10 rounded-lg bg-primary-container/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-container/20 transition-colors">
                    <span class="material-symbols-outlined text-primary-container text-[20px]"><?php echo $b['icon']; ?></span>
                </div>
                <div>
                    <h4 class="font-label-md text-label-md text-on-surface mb-1"><?php echo $b['title']; ?></h4>
                    <p class="text-slate-400 text-sm leading-relaxed"><?php echo $b['desc']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
