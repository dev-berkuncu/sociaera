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

            <!-- Sponsorlarımız -->
            <?php
            $rightSidebarSponsors = [
                ['name' => 'COLOSSEUM', 'logo' => 'assets/img/sponsors/colosseum.png', 'url' => 'https://face-tr.gta.world/page/colosseum'],
                ['name' => 'Paradise Group', 'logo' => 'assets/img/sponsors/paradise-group.png', 'url' => 'https://face-tr.gta.world/page/paradise'],
            ];
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
                            <img src="<?php echo BASE_URL . '/' . escape($sp['logo']); ?>" alt="<?php echo escape($sp['name']); ?>" class="w-full h-full object-contain filter drop-shadow-md transition-transform duration-500 group-hover:scale-105">
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
        </aside>
        <?php endif; /* !$hideSidebar */ ?>
    </div> <!-- flex-grow flex p-gutter ... -->
</main>

<script src="<?php echo asset('js/app.js'); ?>?v=<?php echo time(); ?>"></script>
</body>
</html>
