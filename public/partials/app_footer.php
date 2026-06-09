<?php
/**
 * Sociaera — App Footer (Tailwind Design)
 */
$hideSidebar = $hideSidebar ?? false;
?>
        </section>
        <?php if (!$hideSidebar): ?>
        <!-- Right Sidebar: Discovery Rail -->
        <aside class="hidden lg:flex flex-col col-span-12 lg:col-span-3 xl:col-span-3 space-y-lg sticky top-20 h-[calc(100vh-100px)] overflow-y-auto custom-scrollbar pl-2 pb-6">
            <!-- Yakındaki Mekanlar (Nearby Places) -->
            <?php
            $nearbyVenues = [];
            try {
                $nearbyVenues = (new VenueModel())->getApproved('', '', 3);
            } catch (Exception $e) {}
            if (!empty($nearbyVenues)):
            ?>
            <div class="swarm-glass-card p-4 rounded-xl border border-outline-variant/20 shadow-md">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-[9px] text-on-surface-variant font-bold uppercase tracking-wider font-mono">Yakındaki Mekanlar</h3>
                    <a href="<?php echo BASE_URL; ?>/venues" class="text-primary text-[9px] font-bold hover:underline">Tümünü Gör</a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($nearbyVenues as $index => $nv): ?>
                    <div class="flex items-center gap-3 group cursor-pointer" onclick="window.location.href='<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $nv['id']; ?>'">
                        <div class="w-12 h-12 rounded-lg overflow-hidden bg-surface-container flex items-center justify-center text-primary border border-white/5 group-hover:border-primary/40 transition-colors flex-shrink-0 relative">
                            <?php if (!empty($nv['cover_image'])): ?>
                                <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($nv['cover_image']); ?>" class="w-full h-full object-cover" width="48" height="48" loading="lazy">
                            <?php elseif (!empty($nv['image'])): ?>
                                <img src="<?php echo uploadUrl('posts', $nv['image']); ?>" class="w-full h-full object-cover" width="48" height="48" loading="lazy">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-[20px]">store</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-grow min-w-0">
                            <div class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors truncate"><?php echo escape($nv['name']); ?></div>
                            <div class="text-[10px] text-on-surface-variant truncate mt-0.5">
                                <?php echo escape(VenueModel::categories()[$nv['category']] ?? ($nv['category'] ?? 'Mekan')); ?> 
                                • <?php echo (110 + ($index * 140)) . ' m'; ?>
                            </div>
                        </div>
                        
                        <!-- Rating score badge -->
                        <div class="bg-green-600 text-white text-[11px] font-bold px-1.5 py-0.5 rounded flex-shrink-0">
                            <?php 
                            $nvRating = 9.2 - ($index * 0.2);
                            try {
                                $ratingData = (new VenueModel())->getVenueRating($nv['id']);
                                if ($ratingData['average_rating'] > 0) {
                                    $nvRating = $ratingData['average_rating'];
                                }
                            } catch (Exception $e) {}
                            echo number_format($nvRating, 1); 
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Map View Bento Widget (Yakında) -->
            <div class="swarm-glass-card rounded-xl border border-outline-variant/20 overflow-hidden shadow-md flex flex-col relative">
                <div class="p-4 flex justify-between items-center">
                    <h3 class="text-[9px] text-on-surface-variant font-bold uppercase tracking-wider font-mono">Yakında</h3>
                    <a href="<?php echo BASE_URL; ?>/venues" class="text-primary text-[9px] font-bold hover:underline">Tümünü Gör</a>
                </div>
                <div class="h-48 relative bg-[#131314] cursor-pointer group overflow-hidden" onclick="window.location.href='<?php echo BASE_URL; ?>/venues'">
                    <img class="w-full h-full object-cover opacity-40 grayscale-[0.8] invert-[0.9] group-hover:scale-105 group-hover:opacity-50 transition-all duration-700" src="https://lh3.googleusercontent.com/aida/AP1WRLudjIkYlGBmWTPYJUvLFzH2Tw0cGp8ikU9WEO9mqsjg7gsgTevDFlnp2dkPXUro1NNq4mTrbxUvyIxDMPZBe60dHROByG9EheR2Gbi3nAH-wyKDQsdWm1yunx-ZqK9Sz-a_FPJJp29JteU3WWba1-_UkQtdFpYlWjgRj5k6m2Ibqu3P4VbGVL-xL6pheN38RhYZyrEtz-en_Au81D2NNMcT0IbPxd9hXc-JIRF6xlDNhqwX3kxs7Kns6v4" alt="City Map" loading="lazy">
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute top-1/4 left-1/3 p-1.5 bg-[#ff9100] rounded-full border border-white/20 shadow-[0_0_10px_#ff9100] cybermap-marker-pulse">
                            <span class="material-symbols-outlined text-[12px] text-white" style="display:block;">restaurant</span>
                        </div>
                        <div class="absolute top-1/2 left-1/2 p-1.5 bg-[#deb9ff] rounded-full border border-white/20 shadow-[0_0_10px_#deb9ff]">
                            <span class="material-symbols-outlined text-[12px] text-white" style="display:block;">local_cafe</span>
                        </div>
                        <div class="absolute bottom-1/4 right-1/4 p-1.5 bg-[#ffb778] rounded-full border border-white/20 shadow-[0_0_10px_#ffb778]">
                            <span class="material-symbols-outlined text-[12px] text-on-primary-container" style="display:block;">shopping_bag</span>
                        </div>
                    </div>
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 glass-effect px-4 py-1.5 rounded-full text-[10px] font-bold border border-white/20 whitespace-nowrap text-white">
                        Haritayı Büyüt
                    </div>
                </div>
            </div>

            <!-- Trend Olan Mekanlar (Trending Places) -->
            <?php
            $footerTrendVenues = $trendVenues ?? [];
            if (empty($footerTrendVenues)) {
                try {
                    $footerTrendVenues = (new VenueModel())->getTrending(3);
                } catch (Exception $e) {}
            }
            if (empty($footerTrendVenues)) {
                try {
                    $footerTrendVenues = (new VenueModel())->getApproved('', '', 3);
                } catch (Exception $e) {}
            }
            $footerTrendVenues = array_slice($footerTrendVenues, 0, 3);
            if (!empty($footerTrendVenues)):
            ?>
            <div class="swarm-glass-card p-4 rounded-xl border border-outline-variant/20 shadow-md">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-[9px] text-on-surface-variant font-bold uppercase tracking-wider font-mono">Trend Olan Mekanlar</h3>
                    <a href="<?php echo BASE_URL; ?>/venues" class="text-primary text-[9px] font-bold hover:underline">Tümünü Gör</a>
                </div>
                <div class="space-y-4">
                    <?php foreach ($footerTrendVenues as $i => $tv): ?>
                    <div class="flex items-center gap-3 group cursor-pointer" onclick="window.location.href='<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $tv['id']; ?>'">
                        <div class="text-headline-sm font-bold text-on-surface-variant/40 w-4"><?php echo $i + 1; ?></div>
                        
                        <div class="w-14 h-14 rounded-lg overflow-hidden bg-surface-container flex items-center justify-center text-primary border border-white/5 group-hover:border-primary/40 transition-colors flex-shrink-0 relative">
                            <?php if (!empty($tv['cover_image'])): ?>
                                <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($tv['cover_image']); ?>" class="w-full h-full object-cover" width="56" height="56" loading="lazy">
                            <?php elseif (!empty($tv['image'])): ?>
                                <img src="<?php echo uploadUrl('posts', $tv['image']); ?>" class="w-full h-full object-cover" width="56" height="56" loading="lazy">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-[24px]">store</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-grow min-w-0">
                            <div class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors truncate"><?php echo escape($tv['name']); ?></div>
                            <div class="text-[10px] text-on-surface-variant truncate mt-0.5">
                                <?php echo escape(VenueModel::categories()[$tv['category'] ?? 'kafe'] ?? ($tv['category'] ?? 'Mekan')); ?> 
                                • <?php echo escape($tv['weekly_checkins'] ?? ($tv['checkin_count'] ?? 0)); ?> Check-in
                            </div>
                        </div>
                        
                        <!-- Rating score badge -->
                        <div class="bg-red-900/40 text-secondary text-[11px] font-bold px-1.5 py-0.5 rounded flex-shrink-0">
                            <?php 
                            $tvRating = 9.4 - ($i * 0.2);
                            try {
                                $ratingData = (new VenueModel())->getVenueRating($tv['id']);
                                if ($ratingData['average_rating'] > 0) {
                                    $tvRating = $ratingData['average_rating'];
                                }
                            } catch (Exception $e) {}
                            echo number_format($tvRating, 1); 
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button onclick="window.location.href='<?php echo BASE_URL; ?>/venues'" class="w-full mt-4 border border-outline-variant py-2.5 rounded-lg text-label-md font-bold hover:bg-surface-container transition-all flex items-center justify-center gap-xs text-xs">
                    <span class="material-symbols-outlined text-sm">explore</span>
                    Keşfetmeye Devam Et
                </button>
            </div>
            <?php endif; ?>

            <!-- Sponsors -->
            <?php
            if (!class_exists('AdModel')) {
                require_once dirname(__DIR__, 2) . '/app/Models/Ad.php';
            }
            $rightSidebarSponsors = [];
            $sidebarRightAds = [];
            try {
                $adModel = new AdModel();
                $dbSponsors = $adModel->getByPosition('carousel');
                foreach ($dbSponsors as $ds) {
                    $rightSidebarSponsors[] = [
                        'name' => $ds['title'],
                        'logo' => $ds['image_url'],
                        'url'  => $ds['link_url']
                    ];
                }
                $sidebarRightAds = $adModel->getByPosition('sidebar_right');
            } catch (Exception $e) {}

            if (empty($rightSidebarSponsors)) {
                $rightSidebarSponsors = [
                    ['name' => 'COLOSSEUM', 'logo' => 'assets/img/sponsors/colosseum.png', 'url' => 'https://face-tr.gta.world/page/colosseum'],
                    ['name' => 'Paradise Group', 'logo' => 'assets/img/sponsors/paradise-group.png', 'url' => 'https://face-tr.gta.world/page/paradise'],
                ];
            }
            ?>
            <div class="swarm-glass-card border border-outline-variant/10 rounded-xl p-4 shadow-lg overflow-hidden">
                <h3 class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-3 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[#ff9100] text-[14px]" style="font-variation-settings:'FILL' 1;">campaign</span>
                    Sponsorlarımız
                </h3>
                <?php if (!empty($rightSidebarSponsors)): ?>
                <div class="relative w-full rounded-xl overflow-hidden" style="height:120px;">
                    <?php foreach ($rightSidebarSponsors as $index => $sp): ?>
                    <a href="<?php echo escape($sp['url'] ?? '#'); ?>" target="_blank" rel="noopener"
                       class="sponsor-slide absolute inset-0 flex flex-col items-center justify-center gap-2 transition-opacity duration-700 ease-in-out <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'; ?>"
                       data-index="<?php echo $index; ?>">

                        <!-- Logo — beyaz pill üzerinde -->
                        <div class="bg-white rounded-xl px-5 py-3 flex items-center justify-center shadow-md hover:shadow-lg transition-shadow" style="max-width:85%;height:72px;">
                            <?php if (!empty($sp['logo'])): ?>
                                <img src="<?php echo BASE_URL . '/' . escape($sp['logo']); ?>"
                                     alt="<?php echo escape($sp['name']); ?>"
                                     class="max-w-full max-h-full object-contain"
                                     width="200" height="60" loading="lazy">
                            <?php else: ?>
                                <span class="font-black text-base text-gray-800 tracking-wide"><?php echo escape($sp['name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Sponsor adı -->
                        <span class="text-[10px] font-semibold text-on-surface-variant tracking-wider uppercase"><?php echo escape($sp['name']); ?></span>

                    </a>
                    <?php endforeach; ?>

                    <!-- Dot göstergeler (birden fazla varsa) -->
                    <?php if (count($rightSidebarSponsors) > 1): ?>
                    <div class="absolute bottom-1 left-0 right-0 flex justify-center gap-1 z-20">
                        <?php foreach ($rightSidebarSponsors as $di => $_): ?>
                        <div class="sponsor-dot w-1.5 h-1.5 rounded-full transition-all <?php echo $di === 0 ? 'bg-primary w-3' : 'bg-white/20'; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const slides = document.querySelectorAll('.sponsor-slide');
                    const dots   = document.querySelectorAll('.sponsor-dot');
                    if (slides.length <= 1) return;

                    let cur = 0;
                    setInterval(() => {
                        slides[cur].classList.remove('opacity-100', 'z-10');
                        slides[cur].classList.add('opacity-0', 'z-0', 'pointer-events-none');
                        if (dots[cur]) { dots[cur].classList.remove('bg-primary', 'w-3'); dots[cur].classList.add('bg-white/20'); }

                        cur = (cur + 1) % slides.length;

                        slides[cur].classList.remove('opacity-0', 'z-0', 'pointer-events-none');
                        slides[cur].classList.add('opacity-100', 'z-10');
                        if (dots[cur]) { dots[cur].classList.remove('bg-white/20'); dots[cur].classList.add('bg-primary', 'w-3'); }
                    }, 4000);
                });
                </script>
                <?php else: ?>
                <p class="text-slate-500 text-xs text-center py-4">Henüz sponsor yok</p>
                <?php endif; ?>
            </div>

            <!-- Ad Space -->
            <?php if (!empty($sidebarRightAds)): 
                $activeShowcase = $sidebarRightAds[0];
            ?>
            <div class="swarm-glass-card border border-outline-variant/10 rounded-xl overflow-hidden shadow-lg flex flex-col relative w-full h-[500px] group hover:border-[#ff9100]/30 transition-colors">
                <a href="<?php echo escape($activeShowcase['link_url'] ?? '#'); ?>" target="_blank" rel="noopener" class="block w-full h-full relative">
                    <img src="<?php echo BASE_URL . '/' . escape($activeShowcase['logo'] ?? $activeShowcase['image_url']); ?>" alt="<?php echo escape($activeShowcase['title']); ?>" class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-500" loading="lazy">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[2px]">
                        <div class="w-10 h-10 rounded-full bg-[#ff9100] text-white flex items-center justify-center shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                            <span class="material-symbols-outlined text-[18px]">arrow_outward</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php else: ?>
            <div class="swarm-glass-card border border-dashed border-white/10 rounded-xl overflow-hidden shadow-lg flex flex-col relative w-full h-[500px] group hover:border-[#ff9100]/30 transition-colors">
                <a href="mailto:info@sociaera.online" class="absolute inset-0 flex flex-col items-center justify-center p-4 text-center cursor-pointer z-10">
                    <span class="material-symbols-outlined text-slate-600 text-[36px] mb-3 group-hover:scale-110 group-hover:text-[#ff9100] transition-all duration-300">view_carousel</span>
                    <span class="font-black text-sm text-slate-500 group-hover:text-white transition-colors tracking-wide">REKLAM ALANI</span>
                    <span class="text-[10px] text-slate-400 mt-2 font-mono bg-black/40 px-3 py-1 rounded-full border border-white/5 shadow-inner">300 x 500</span>
                    
                    <div class="absolute bottom-6 w-9 h-9 rounded-full bg-[#ff9100] text-white flex items-center justify-center opacity-0 group-hover:opacity-100 group-hover:-translate-y-1 transition-all duration-300 shadow-md">
                        <span class="material-symbols-outlined text-[18px]">ads_click</span>
                    </div>
                </a>
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#ff9100]/5 rounded-full blur-3xl group-hover:bg-[#ff9100]/10 transition-colors"></div>
                    <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/10 transition-colors"></div>
                </div>
            </div>
            <?php endif; ?>
        </aside>
        <?php endif; /* !$hideSidebar */ ?>
</main>

<!-- Mobile Bottom Navigation -->
<?php if (Auth::check() && isset($currentUser)): ?>
<nav class="fixed bottom-0 left-0 right-0 z-50 bg-surface-container/95 backdrop-blur-xl border-t border-outline-variant/30 md:hidden safe-area-bottom">
    <div class="flex items-center justify-around h-16">
        <?php
        $mobileNavItems = [
            'dashboard'     => ['icon' => 'home',           'label' => 'Ana Sayfa'],
            'venues'        => ['icon' => 'location_on',    'label' => 'Mekanlar'],
            'leaderboard'   => ['icon' => 'leaderboard',    'label' => 'Liderlik'],
            'notifications' => ['icon' => 'notifications',  'label' => 'Bildirim'],
            'profile'       => ['icon' => 'person',         'label' => 'Profil'],
        ];
        $mobileUrls = [
            'dashboard'     => '/dashboard',
            'venues'        => '/venues',
            'leaderboard'   => '/leaderboard',
            'notifications' => '/notifications',
            'profile'       => '/profile',
        ];
        foreach ($mobileNavItems as $mKey => $mItem):
            $mActive = ($activeNav ?? '') === $mKey;
            $mClass  = $mActive
                ? 'flex flex-col items-center justify-center gap-0.5 text-primary-container transition-colors relative'
                : 'flex flex-col items-center justify-center gap-0.5 text-on-surface-variant hover:text-on-surface transition-colors relative';
        ?>
        <a href="<?php echo BASE_URL . ($mobileUrls[$mKey] ?? ''); ?>" class="<?php echo $mClass; ?>">
            <?php if ($mActive): ?>
            <div class="absolute -top-px left-1/2 -translate-x-1/2 w-8 h-0.5 bg-primary-container rounded-full"></div>
            <?php endif; ?>
            <span class="material-symbols-outlined text-[22px]" <?php echo $mActive ? 'data-weight="fill"' : ''; ?>><?php echo $mItem['icon']; ?></span>
            <span class="text-[10px] font-semibold tracking-wide"><?php echo $mItem['label']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>
<style>
    @media (max-width: 767px) {
        body { padding-bottom: 64px !important; }
    }
    .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom, 0px); }
</style>
<?php endif; ?>

<!-- Report Modal -->
<?php if (Auth::check()): ?>
<div id="reportModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="App.closeReportModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-[#2a2a2b] border border-white/10 rounded-2xl w-full max-w-md shadow-2xl relative animate-[modalIn_0.2s_ease-out]">
            <div class="flex items-center justify-between p-6 border-b border-white/5">
                <h3 class="text-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-400">flag</span> İçeriği Raporla
                </h3>
                <button onclick="App.closeReportModal()" class="text-slate-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="reportForm" onsubmit="App.submitReport(event)" class="p-6">
                <input type="hidden" name="entity_type" id="report_entity_type">
                <input type="hidden" name="entity_id" id="report_entity_id">
                
                <label class="block text-sm font-bold text-slate-300 mb-3">Neden raporlıyorsunuz?</label>
                <div class="grid grid-cols-1 gap-2 mb-5">
                    <?php
                    $reasons = [
                        'spam' => ['icon' => 'mark_email_unread', 'label' => 'Spam / Reklam'],
                        'harassment' => ['icon' => 'report', 'label' => 'Taciz / Zorbalık'],
                        'inappropriate' => ['icon' => 'block', 'label' => 'Uygunsuz İçerik'],
                        'fake_checkin' => ['icon' => 'location_off', 'label' => 'Sahte Check-in'],
                        'fraud' => ['icon' => 'gpp_bad', 'label' => 'Dolandırıcılık'],
                        'privacy' => ['icon' => 'privacy_tip', 'label' => 'Gizlilik İhlali'],
                        'copyright' => ['icon' => 'copyright', 'label' => 'Telif Hakkı'],
                        'other' => ['icon' => 'more_horiz', 'label' => 'Diğer'],
                    ];
                    foreach ($reasons as $key => $r):
                    ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="reason" value="<?php echo $key; ?>" class="sr-only peer" required>
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-white/10 bg-white/5 peer-checked:border-red-400/50 peer-checked:bg-red-500/10 hover:bg-white/10 transition-all">
                            <span class="material-symbols-outlined text-[18px] text-slate-400 peer-checked:text-red-400"><?php echo $r['icon']; ?></span>
                            <span class="text-sm text-slate-300"><?php echo $r['label']; ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <label class="block text-sm font-bold text-slate-300 mb-2">Açıklama <span class="text-slate-500 font-normal">(opsiyonel)</span></label>
                <textarea name="description" id="report_description" rows="3" maxlength="500" 
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-on-surface text-sm focus:border-red-400/50 focus:bg-white/10 outline-none transition-all resize-none mb-5"
                    placeholder="Detay eklemek isterseniz..."></textarea>

                <div class="flex gap-3">
                    <button type="button" onclick="App.closeReportModal()" class="flex-1 py-3 rounded-xl bg-white/5 text-slate-300 font-bold text-sm border border-white/10 hover:bg-white/10 transition-colors">
                        İptal
                    </button>
                    <button type="submit" id="reportSubmitBtn" class="flex-1 py-3 rounded-xl bg-red-500/20 text-red-400 font-bold text-sm border border-red-500/30 hover:bg-red-500/30 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span> Rapor Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
@keyframes modalIn { from { opacity:0; transform:scale(0.95) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }
</style>
<?php endif; ?>

<script src="<?php echo asset('js/app.js'); ?>"></script>
</body>
</html>
