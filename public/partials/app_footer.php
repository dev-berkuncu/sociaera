<?php
/**
 * Sociaera — App Footer (Tailwind Design)
 */
$hideSidebar = $hideSidebar ?? false;
?>
        <?php if (!$hideSidebar): ?>
        <!-- Right Sidebar (Conditionally shown if variables exist) -->
        <aside class="hidden lg:flex flex-col w-80 gap-stack-md ml-auto">
            <?php if (!empty($trendVenues)): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.5)]">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-container text-[20px]">local_fire_department</span> Popüler Mekanlar
                </h2>
                <ul class="flex flex-col gap-4">
                    <?php foreach ($trendVenues as $tv): ?>
                    <li class="flex items-center gap-4 group cursor-pointer p-2 -mx-2 rounded-xl hover:bg-white/5 transition-all" onclick="window.location.href='<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $tv['id']; ?>'">
                        <div class="w-14 h-14 rounded-xl overflow-hidden bg-surface-container flex items-center justify-center text-primary-container border border-white/10 group-hover:border-primary-container/50 transition-colors flex-shrink-0 relative shadow-sm">
                            <?php if (!empty($tv['cover_image'])): ?>
                                <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($tv['cover_image']); ?>" class="w-full h-full object-contain p-1" width="56" height="56" loading="lazy">
                            <?php elseif (!empty($tv['image'])): ?>
                                <img src="<?php echo uploadUrl('posts', $tv['image']); ?>" class="w-full h-full object-contain p-1" width="56" height="56" loading="lazy">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-[24px]">store</span>
                            <?php endif; ?>
                            <?php if (isset($tv['is_open']) && $tv['is_open']): ?>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-emerald-400 rounded-full border-2 border-[#1E293B] shadow-[0_0_5px_rgba(16,185,129,0.5)]"></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="font-bold text-base text-on-surface group-hover:text-primary-container transition-colors truncate drop-shadow-sm"><?php echo escape($tv['name']); ?></div>
                            <div class="text-xs font-medium text-slate-400 mt-0.5 truncate flex items-center gap-1.5">
                                <?php if (!empty($tv['category'])): ?>
                                    <span class="bg-primary-container/10 text-primary-container px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wider border border-primary-container/20"><?php echo escape(VenueModel::categories()[$tv['category']] ?? $tv['category']); ?></span>
                                <?php endif; ?>
                                <span><?php echo $tv['weekly_checkins']; ?> Check-in</span>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all -translate-x-2 group-hover:translate-x-0">
                            <span class="material-symbols-outlined text-primary-container text-[18px]">chevron_right</span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo BASE_URL; ?>/venues" class="block text-center w-full mt-4 py-2.5 text-primary-container font-bold text-sm bg-white/5 hover:bg-white/10 rounded-xl transition-colors border border-white/5">Tümünü Gör</a>
            </div>
            <?php endif; ?>

            <?php if (!empty($miniLeaderboard)): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.5)]">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-container text-[20px]">military_tech</span> Liderlik
                </h2>
                <ul class="flex flex-col gap-4">
                    <?php foreach ($miniLeaderboard as $i => $lb): ?>
                    <li class="flex items-center gap-4 group cursor-pointer p-2 -mx-2 rounded-xl hover:bg-white/5 transition-all" onclick="window.location.href='<?php echo BASE_URL; ?>/profile?u=<?php echo escape($lb['tag'] ?: $lb['username']); ?>'">
                        <div class="relative flex-shrink-0">
                            <?php $lbAvatar = safeAvatarUrl($lb['avatar'] ?? null, $lb['username']); ?>
                            <img alt="Leader avatar" class="w-12 h-12 rounded-full object-cover border-2 border-[#1E293B] shadow-sm group-hover:border-primary-container/30 transition-colors" src="<?php echo $lbAvatar; ?>" width="48" height="48" loading="lazy"/>
                            <?php 
                            $badgeClass = 'bg-slate-700 text-white border-slate-600';
                            if ($i === 0) $badgeClass = 'bg-primary-container text-white border-[#1E293B] shadow-[0_0_8px_rgba(255,107,53,0.5)]';
                            elseif ($i === 1) $badgeClass = 'bg-slate-300 text-slate-800 border-[#1E293B]';
                            elseif ($i === 2) $badgeClass = 'bg-[#cd7f32] text-white border-[#1E293B]'; 
                            ?>
                            <div class="absolute -top-1 -right-1 <?php echo $badgeClass; ?> text-[10px] font-black w-5 h-5 flex items-center justify-center rounded-full border-2"><?php echo $i + 1; ?></div>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="font-bold text-base text-on-surface group-hover:text-primary-container transition-colors truncate drop-shadow-sm"><?php echo escape($lb['username']); ?></div>
                            <div class="text-xs font-medium text-slate-400 mt-0.5 truncate flex items-center gap-1.5">
                                <span class="bg-surface-container-high text-slate-300 px-1.5 py-0.5 rounded text-[10px] font-bold"><?php echo $lb['checkin_count']; ?> Puan</span>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all -translate-x-2 group-hover:translate-x-0">
                            <span class="material-symbols-outlined text-primary-container text-[18px]">chevron_right</span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo BASE_URL; ?>/leaderboard" class="block text-center w-full mt-4 py-2.5 text-primary-container font-bold text-sm bg-white/5 hover:bg-white/10 rounded-xl transition-colors border border-white/5">Tümünü Gör</a>
            </div>
            <?php endif; ?>

            <!-- Sponsorlarımız -->
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

            // Eğer veritabanında hiç sponsor yoksa, varsayılanları göster (boş kalmaması için)
            if (empty($rightSidebarSponsors)) {
                $rightSidebarSponsors = [
                    ['name' => 'COLOSSEUM', 'logo' => 'assets/img/sponsors/colosseum.png', 'url' => 'https://face-tr.gta.world/page/colosseum'],
                    ['name' => 'Paradise Group', 'logo' => 'assets/img/sponsors/paradise-group.png', 'url' => 'https://face-tr.gta.world/page/paradise'],
                ];
            }
            ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] overflow-hidden">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-4 flex items-center gap-2">
                    Sponsorlarımız <span class="material-symbols-outlined text-primary-container text-[20px]">campaign</span>
                </h2>
                <?php if (!empty($rightSidebarSponsors)): ?>
                <div class="relative w-full h-32 rounded-xl overflow-hidden group bg-surface border border-white/5 shadow-inner">
                    <?php foreach ($rightSidebarSponsors as $index => $sp): ?>
                    <a href="<?php echo escape($sp['url'] ?? '#'); ?>" target="_blank" rel="noopener" 
                       class="sponsor-slide absolute inset-0 flex flex-col items-center justify-center p-4 transition-opacity duration-1000 ease-in-out <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'; ?>"
                       data-index="<?php echo $index; ?>">
                        
                        <?php if (!empty($sp['logo'])): ?>
                            <img src="<?php echo BASE_URL . '/' . escape($sp['logo']); ?>" alt="<?php echo escape($sp['name']); ?>" class="w-full h-full object-contain filter drop-shadow-md transition-transform duration-500 group-hover:scale-105" width="280" height="128" loading="lazy">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-slate-500 text-[48px] transition-transform duration-500 group-hover:scale-110">store</span>
                        <?php endif; ?>
                        
                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-background/80 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center backdrop-blur-sm z-20">
                            <span class="font-headline-md text-headline-md text-primary-container text-center mb-1"><?php echo escape($sp['name']); ?></span>
                            <span class="text-xs text-slate-300 mb-3 tracking-widest uppercase opacity-80">Resmi Sponsor</span>
                            <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                                <span class="material-symbols-outlined text-[20px]">arrow_outward</span>
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
                <p class="text-slate-500 text-sm text-center py-2">Henüz sponsor yok</p>
                <?php endif; ?>
            </div>

            <!-- Showcase Area / Reklam Alanı -->
            <?php if (!empty($sidebarRightAds)): 
                // İlk aktif reklamı göster
                $activeShowcase = $sidebarRightAds[0];
            ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] flex flex-col relative w-full h-[600px] group hover:border-primary-container/30 transition-colors">
                <a href="<?php echo escape($activeShowcase['link_url'] ?? '#'); ?>" target="_blank" rel="noopener" class="block w-full h-full relative">
                    <img src="<?php echo BASE_URL . '/' . escape($activeShowcase['logo'] ?? $activeShowcase['image_url']); ?>" alt="<?php echo escape($activeShowcase['title']); ?>" class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-500" loading="lazy">
                    <!-- Hover overlay for premium touch -->
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[2px]">
                        <div class="w-12 h-12 rounded-full bg-primary-container text-white flex items-center justify-center shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                            <span class="material-symbols-outlined text-[24px]">arrow_outward</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php else: ?>
            <!-- Varsayılan Reklam Alanı Taslağı (Placeholder) -->
            <div class="bg-[#1E293B]/40 backdrop-blur-[20px] border border-dashed border-white/20 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] flex flex-col relative w-full h-[600px] group hover:border-primary-container/50 transition-colors">
                <a href="mailto:info@sociaera.online" class="absolute inset-0 flex flex-col items-center justify-center p-6 transition-colors text-center cursor-pointer z-10">
                    <span class="material-symbols-outlined text-slate-600 text-[48px] mb-4 group-hover:scale-110 group-hover:text-primary-container transition-all duration-300">view_carousel</span>
                    <span class="font-black text-xl text-slate-500 group-hover:text-white transition-colors tracking-wide">REKLAM ALANI</span>
                    <span class="text-xs text-slate-400 mt-3 font-mono bg-black/40 px-4 py-1.5 rounded-full border border-white/5 shadow-inner">300 x 600</span>
                    
                    <div class="absolute bottom-8 w-12 h-12 rounded-full bg-primary-container text-white flex items-center justify-center opacity-0 group-hover:opacity-100 group-hover:-translate-y-2 transition-all duration-300 shadow-[0_0_15px_rgba(255,107,53,0.4)]">
                        <span class="material-symbols-outlined text-[24px]">ads_click</span>
                    </div>
                </a>
                
                <!-- Background decoration -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary-container/5 rounded-full blur-3xl group-hover:bg-primary-container/10 transition-colors"></div>
                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/10 transition-colors"></div>
                </div>
            </div>
            <?php endif; ?>
        </aside>
        <?php endif; /* !$hideSidebar */ ?>
    </div> <!-- flex-grow flex p-gutter ... -->
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
