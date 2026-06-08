<?php
/**
 * Sociaera — App Footer (Tailwind Design)
 */
$hideSidebar = $hideSidebar ?? false;
?>
        </section>
        <?php if (!$hideSidebar): ?>
        <!-- Right Sidebar: Discovery Rail -->
        <aside class="hidden lg:flex flex-col col-span-12 lg:col-span-3 xl:col-span-3 space-y-lg sticky top-24 h-[calc(100vh-120px)] overflow-y-auto custom-scrollbar pl-2 pb-6">
            <!-- Popular Places -->
            <?php if (!empty($trendVenues)): ?>
            <div class="bg-surface-container-low p-5 rounded-xl border border-outline-variant/10 shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Popüler Mekanlar</h3>
                    <a href="<?php echo BASE_URL; ?>/venues" class="text-[#ff9100] text-[10px] font-bold hover:underline">Tümünü Gör</a>
                </div>
                <div class="space-y-4">
                    <?php foreach ($trendVenues as $i => $tv): ?>
                    <div class="flex items-center gap-3 group cursor-pointer" onclick="window.location.href='<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $tv['id']; ?>'">
                        <div class="text-xs font-black text-slate-600 w-4"><?php echo $i + 1; ?></div>
                        
                        <div class="w-12 h-12 rounded-lg overflow-hidden bg-surface-container flex items-center justify-center text-[#ff9100] border border-white/5 group-hover:border-[#ff9100]/40 transition-colors flex-shrink-0 relative">
                            <?php if (!empty($tv['cover_image'])): ?>
                                <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($tv['cover_image']); ?>" class="w-full h-full object-cover" width="48" height="48" loading="lazy">
                            <?php elseif (!empty($tv['image'])): ?>
                                <img src="<?php echo uploadUrl('posts', $tv['image']); ?>" class="w-full h-full object-cover" width="48" height="48" loading="lazy">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-[20px]">store</span>
                            <?php endif; ?>
                            <?php if (isset($tv['is_open']) && $tv['is_open']): ?>
                                <div class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-emerald-400 rounded-full border border-[#0e0e0f]"></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-grow min-w-0">
                            <div class="font-bold text-xs text-on-surface group-hover:text-[#ff9100] transition-colors truncate"><?php echo escape($tv['name']); ?></div>
                            <div class="text-[10px] text-slate-500 mt-0.5 truncate flex items-center gap-1.5">
                                <?php if (!empty($tv['category'])): ?>
                                    <span class="text-[#ff9100] font-semibold"><?php echo escape(VenueModel::categories()[$tv['category']] ?? $tv['category']); ?></span>
                                <?php endif; ?>
                                <span>• <?php echo $tv['weekly_checkins']; ?></span>
                            </div>
                        </div>
                        
                        <!-- Rating score badge -->
                        <div class="bg-[#ff9100]/15 text-[#ff9100] text-[10px] font-bold px-1.5 py-0.5 rounded flex-shrink-0 border border-[#ff9100]/25">
                            <?php echo number_format(9.5 - ($i * 0.2), 1); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Map View Bento Widget -->
            <div class="bg-surface-container-low rounded-xl border border-outline-variant/10 overflow-hidden shadow-md flex flex-col relative">
                <div class="p-4 flex justify-between items-center">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono">Keşif Radarı</h3>
                    <a href="<?php echo BASE_URL; ?>/venues" class="text-[#ff9100] text-[10px] font-bold hover:underline">Genişlet</a>
                </div>
                <div class="h-32 relative bg-surface-container-highest/60 cursor-pointer overflow-hidden group" onclick="window.location.href='<?php echo BASE_URL; ?>/venues'">
                    <img class="w-full h-full object-cover opacity-35 grayscale-[0.2] invert-[0.9] group-hover:scale-105 transition-transform duration-700" src="https://lh3.googleusercontent.com/aida/AP1WRLudjIkYlGBmWTPYJUvLFzH2Tw0cGp8ikU9WEO9mqsjg7gsgTevDFlnp2dkPXUro1NNq4mTrbxUvyIxDMPZBe60dHROByG9EheR2Gbi3nAH-wyKDQsdWm1yunx-ZqK9Sz-a_FPJJp29JteU3WWba1-_UkQtdFpYlWjgRj5k6m2Ibqu3P4VbGVL-xL6pheN38RhYZyrEtz-en_Au81D2NNMcT0IbPxd9hXc-JIRF6xlDNhqwX3kxs7Kns6v4" alt="City Map" loading="lazy">
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute top-1/4 left-1/3 p-1 bg-[#ff9100] rounded-full border border-white shadow-lg animate-pulse">
                            <span class="material-symbols-outlined text-[10px] text-white" style="display:block;">restaurant</span>
                        </div>
                        <div class="absolute top-1/2 left-1/2 p-1 bg-purple-500 rounded-full border border-white shadow-lg">
                            <span class="material-symbols-outlined text-[10px] text-white" style="display:block;">local_cafe</span>
                        </div>
                        <div class="absolute bottom-1/4 right-1/4 p-1 bg-emerald-500 rounded-full border border-white shadow-lg">
                            <span class="material-symbols-outlined text-[10px] text-white" style="display:block;">shopping_bag</span>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-black/20 group-hover:bg-black/5 transition-all duration-300"></div>
                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 glass-effect px-4 py-1 rounded-full text-[10px] font-bold border border-white/10 whitespace-nowrap text-white">
                        Haritayı Aç
                    </div>
                </div>
            </div>

            <!-- Weekly Leaderboard -->
            <?php if (!empty($miniLeaderboard)): ?>
            <div class="bg-surface-container-low p-5 rounded-xl border border-outline-variant/10 shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Haftalık Liderler</h3>
                    <a href="<?php echo BASE_URL; ?>/leaderboard" class="text-[#ff9100] text-[10px] font-bold hover:underline">Tümünü Gör</a>
                </div>
                <div class="space-y-4">
                    <?php foreach ($miniLeaderboard as $i => $lb): ?>
                    <div class="flex items-center gap-3 group cursor-pointer" onclick="window.location.href='<?php echo BASE_URL; ?>/profile?u=<?php echo escape($lb['tag'] ?: $lb['username']); ?>'">
                        <div class="text-xs font-black text-slate-600 w-4"><?php echo $i + 1; ?></div>
                        
                        <div class="relative flex-shrink-0">
                            <?php $lbAvatar = safeAvatarUrl($lb['avatar'] ?? null, $lb['username']); ?>
                            <img alt="Leader avatar" class="w-10 h-10 rounded-full object-cover border border-white/10 group-hover:border-[#ff9100]/40 transition-colors" src="<?php echo $lbAvatar; ?>" width="40" height="40" loading="lazy"/>
                        </div>
                        
                        <div class="flex-grow min-w-0">
                            <div class="font-bold text-xs text-on-surface group-hover:text-[#ff9100] transition-colors truncate"><?php echo escape($lb['username']); ?></div>
                            <div class="text-[10px] text-slate-500 mt-0.5 truncate">
                                @<?php echo escape($lb['tag'] ?: $lb['username']); ?>
                            </div>
                        </div>
                        
                        <div class="bg-[#ff9100]/10 text-[#ff9100] text-[10px] font-bold px-2.5 py-0.5 rounded-full flex-shrink-0 border border-[#ff9100]/20">
                            <?php echo $lb['checkin_count']; ?> Puan
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
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
            <div class="bg-surface-container-low border border-outline-variant/10 rounded-xl p-5 shadow-lg overflow-hidden">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                    Sponsorlarımız <span class="material-symbols-outlined text-[#ff9100] text-[16px]">campaign</span>
                </h3>
                <?php if (!empty($rightSidebarSponsors)): ?>
                <div class="relative w-full h-24 rounded-xl overflow-hidden group bg-surface border border-white/5 shadow-inner">
                    <?php foreach ($rightSidebarSponsors as $index => $sp): ?>
                    <a href="<?php echo escape($sp['url'] ?? '#'); ?>" target="_blank" rel="noopener" 
                       class="sponsor-slide absolute inset-0 flex flex-col items-center justify-center p-4 transition-opacity duration-1000 ease-in-out <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'; ?>"
                       data-index="<?php echo $index; ?>">
                        
                        <?php if (!empty($sp['logo'])): ?>
                            <img src="<?php echo BASE_URL . '/' . escape($sp['logo']); ?>" alt="<?php echo escape($sp['name']); ?>" class="w-full h-full object-contain filter drop-shadow-md transition-transform duration-500 group-hover:scale-105" width="280" height="96" loading="lazy">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-slate-500 text-[32px] transition-transform duration-500 group-hover:scale-110">store</span>
                        <?php endif; ?>
                        
                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-background/80 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center backdrop-blur-sm z-20">
                            <span class="font-bold text-xs text-[#ff9100] text-center mb-0.5"><?php echo escape($sp['name']); ?></span>
                            <span class="text-[9px] text-slate-400 mb-1.5 tracking-widest uppercase opacity-80">Resmi Sponsor</span>
                            <div class="w-7 h-7 rounded-full bg-[#ff9100] text-white flex items-center justify-center shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                                <span class="material-symbols-outlined text-[14px]">arrow_outward</span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const slides = document.querySelectorAll('.sponsor-slide');
                    if (slides.length <= 1) return;
                    
                    let currentIndex = 0;
                    setInterval(() => {
                        slides[currentIndex].classList.remove('opacity-100', 'z-10');
                        slides[currentIndex].classList.add('opacity-0', 'z-0', 'pointer-events-none');
                        
                        currentIndex = (currentIndex + 1) % slides.length;
                        
                        slides[currentIndex].classList.remove('opacity-0', 'z-0', 'pointer-events-none');
                        slides[currentIndex].classList.add('opacity-100', 'z-10');
                    }, 5000);
                });
                </script>
                <?php else: ?>
                <p class="text-slate-500 text-xs text-center py-2">Henüz sponsor yok</p>
                <?php endif; ?>
            </div>

            <!-- Ad Space -->
            <?php if (!empty($sidebarRightAds)): 
                $activeShowcase = $sidebarRightAds[0];
            ?>
            <div class="bg-surface-container-low border border-outline-variant/10 rounded-xl overflow-hidden shadow-lg flex flex-col relative w-full h-[500px] group hover:border-[#ff9100]/30 transition-colors">
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
            <div class="bg-surface-container-low border border-dashed border-white/10 rounded-xl overflow-hidden shadow-lg flex flex-col relative w-full h-[500px] group hover:border-[#ff9100]/30 transition-colors">
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
<nav class="fixed bottom-0 left-0 right-0 z-50 bg-[#0F172A]/95 backdrop-blur-xl border-t border-white/10 md:hidden safe-area-bottom">
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
                : 'flex flex-col items-center justify-center gap-0.5 text-slate-500 hover:text-slate-300 transition-colors relative';
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
        <div class="bg-[#1E293B] border border-white/10 rounded-2xl w-full max-w-md shadow-2xl relative animate-[modalIn_0.2s_ease-out]">
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
