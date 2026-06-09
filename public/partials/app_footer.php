<?php
/**
 * Sociaera — App Footer (Tailwind Design)
 */
$hideSidebar = $hideSidebar ?? false;
?>
        </section>
        
        <?php if (!$hideSidebar && Auth::check() && isset($currentUser)): ?>
        <!-- Right Sidebar: Discovery Rail (Bento Widgets) -->
        <aside class="hidden lg:flex flex-col col-span-12 lg:col-span-4 xl:col-span-4 space-y-lg sticky top-20 h-[calc(100vh-100px)] overflow-y-auto custom-scrollbar pl-2 pb-6 z-40">
            
            <!-- Bento Widget 1: CHARACTER IDENTITY BIOMETRICS -->
            <div class="cyber-panel p-5 rounded-xl border border-white/5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-cyber-orange/5 rounded-full blur-2xl"></div>
                <h3 class="text-[9px] font-mono text-slate-500 uppercase tracking-widest mb-3">CHARACTER_BIOMETRICS</h3>
                
                <div class="flex items-center gap-4 mb-4">
                    <div class="relative w-12 h-12 rounded-full p-[2px] <?php echo $levelRingClass ?? 'bg-gradient-to-tr from-slate-700 to-slate-800'; ?> shadow-lg flex-shrink-0">
                        <?php $avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']); ?>
                        <img alt="<?php echo escape($currentUser['username']); ?>" class="w-full h-full rounded-full border border-[#0c0d10] object-cover" src="<?php echo $avatarUrl; ?>"/>
                        <div class="absolute -bottom-1 -right-1 bg-cyber-orange text-[8px] font-bold px-1.5 rounded-full text-white shadow-md">
                            <?php echo $userLevel ?? 1; ?>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-sm text-slate-100 truncate" style="color: #ffb778;"><?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?></h4>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-[9px] font-mono bg-cyber-orange/10 border border-cyber-orange/20 text-cyber-orange px-1.5 py-0.2 rounded font-bold uppercase"><?php echo escape($currentUser['tag'] ?: 'PLAYER'); ?></span>
                            <span class="text-[9px] font-mono text-slate-500">ID: #<?php echo sprintf('%04d', $currentUser['id']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Streak & Checkin Stats Grid -->
                <div class="grid grid-cols-2 gap-2 font-mono text-[10px] border-t border-white/5 pt-3 mb-4">
                    <div class="bg-slate-950/40 p-2 rounded border border-white/5">
                        <div class="text-slate-500 uppercase">Check-ins</div>
                        <div class="text-[11px] font-bold text-white mt-0.5"><?php echo (int)($stats['checkins'] ?? 0); ?></div>
                    </div>
                    <div class="bg-slate-950/40 p-2 rounded border border-white/5">
                        <div class="text-slate-500 uppercase">Seri</div>
                        <div class="text-[11px] font-bold text-cyber-orange mt-0.5 flex items-center gap-0.5">
                            <span class="material-symbols-outlined text-[12px] streak-pulse" style="font-variation-settings: 'FILL' 1; color:#ff9100;">local_fire_department</span>
                            <span><?php echo $streak ?? 0; ?> Gün</span>
                        </div>
                    </div>
                </div>

                <!-- Weekly Goal Progress Bar -->
                <div class="mt-2 text-left font-mono text-[9px]">
                    <div class="flex justify-between text-slate-400 mb-1">
                        <span>HAFTALIK HEDEF: 5 CHECK-IN</span>
                        <span class="text-cyber-cyan font-bold"><?php echo min(5, $weeklyCheckins ?? 0); ?> / 5</span>
                    </div>
                    <div class="h-1 bg-slate-950 rounded-full overflow-hidden border border-white/5">
                        <div class="h-full bg-cyber-cyan shadow-neonCyan" style="width: <?php echo min(100, (($weeklyCheckins ?? 0) / 5) * 100); ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Bento Widget 2: OWNED VENUES (İşletmelerim) & CHARACTER SWAP -->
            <?php
            $userVenues = [];
            try {
                $userVenues = (new VenueModel())->getByOwner(Auth::id());
            } catch (Exception $e) {}
            
            $otherCharacters = [];
            if (!empty($currentUser['gta_user_id'])) {
                try {
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT id, username, gta_character_name, avatar, tag FROM users WHERE gta_user_id = ? AND id != ? AND is_active = 1");
                    $stmt->execute([$currentUser['gta_user_id'], $currentUser['id']]);
                    $otherCharacters = $stmt->fetchAll();
                } catch (Exception $e) {}
            }

            if (!empty($userVenues) || !empty($otherCharacters)):
            ?>
            <div class="cyber-panel p-4 rounded-xl border border-white/5 space-y-3">
                <?php if (!empty($userVenues)): ?>
                <div>
                    <h3 class="text-[9px] text-slate-500 uppercase tracking-widest mb-2 font-mono">ISLETMELERIM</h3>
                    <ul class="flex flex-col gap-1">
                        <?php foreach ($userVenues as $uv): ?>
                        <li>
                            <a class="w-full bg-[#10141e]/50 hover:bg-slate-900 border border-white/5 text-slate-300 hover:text-white py-1.5 px-3 rounded-lg flex items-center justify-between gap-2.5 transition-all text-xs font-mono" href="<?php echo BASE_URL; ?>/venue-manage?id=<?php echo $uv['id']; ?>">
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="material-symbols-outlined text-[14px]">storefront</span>
                                    <span class="truncate"><?php echo escape($uv['name']); ?></span>
                                </div>
                                <?php if ($uv['status'] === 'pending'): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0 shadow-[0_0_6px_#f59e0b]"></span>
                                <?php elseif (!empty($uv['is_open'])): ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyber-green shrink-0 shadow-[0_0_6px_#10b981]"></span>
                                <?php else: ?>
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0 shadow-[0_0_6px_#ef4444]"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($otherCharacters)): ?>
                <div>
                    <h3 class="text-[9px] text-slate-500 uppercase tracking-widest mb-2 font-mono">KARAKTER DEGISTIR</h3>
                    <ul class="flex flex-col gap-1">
                        <?php foreach ($otherCharacters as $oc): ?>
                        <li>
                            <form action="<?php echo BASE_URL; ?>/switch-character" method="POST" class="m-0">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="target_user_id" value="<?php echo $oc['id']; ?>">
                                <button type="submit" class="w-full bg-[#10141e]/50 hover:bg-slate-900 border border-white/5 text-slate-400 hover:text-slate-200 py-1.5 px-3 rounded-lg flex items-center gap-2 transition-all text-xs text-left font-mono">
                                    <?php $ocAvatarUrl = safeAvatarUrl($oc['avatar'] ?? null, $oc['username']); ?>
                                    <img src="<?php echo $ocAvatarUrl; ?>" alt="Avatar" class="w-4 h-4 rounded-full object-cover border border-white/10" width="16" height="16">
                                    <span class="truncate"><?php echo escape($oc['gta_character_name'] ?: $oc['username']); ?></span>
                                </button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Bento Widget 3: TACTICAL GPS RADAR VIEW -->
            <div class="cyber-panel rounded-xl border border-white/5 overflow-hidden relative">
                <div class="p-4 border-b border-white/5 flex justify-between items-center font-mono">
                    <h3 class="text-[9px] text-slate-500 uppercase tracking-widest">RADAR_TARGETS</h3>
                    <span class="text-[8px] text-cyber-cyan bg-cyber-cyan/10 px-2 py-0.5 rounded border border-cyber-cyan/20">LIVE METRO GPS</span>
                </div>
                <div class="h-44 relative bg-slate-950 overflow-hidden cursor-pointer group" onclick="window.location.href='<?php echo BASE_URL; ?>/venues'">
                    <!-- Grid Overlay -->
                    <div class="absolute inset-0 map-grid opacity-30"></div>
                    <!-- Scanning scanline sweep -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-cyber-cyan/5 to-transparent pointer-events-none origin-bottom-left animate-pulse"></div>
                    <!-- Pulsing map points -->
                    <div class="absolute top-1/4 left-1/3 p-1 bg-cyber-purple rounded-full shadow-[0_0_8px_#a855f7] cybermap-marker-pulse"></div>
                    <div class="absolute top-1/2 left-2/3 p-1 bg-cyber-cyan rounded-full shadow-[0_0_8px_#00f0ff] cybermap-marker-pulse" style="animation-delay:0.3s;"></div>
                    <div class="absolute bottom-1/3 left-1/2 p-1 bg-cyber-orange rounded-full shadow-[0_0_8px_#ff6a00] cybermap-marker-pulse" style="animation-delay:0.6s;"></div>
                    
                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-cyber-dark/95 border border-cyber-cyan/40 px-3 py-1 rounded-full text-[9px] font-mono font-bold text-white shadow-2xl group-hover:scale-105 transition-transform duration-300">
                        HARITAYI BUYUT (GPS HUD)
                    </div>
                </div>
            </div>

            <!-- Bento Widget 4: ROZETLERIM & YAKIN ARKADASLARIM -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Rozetlerim -->
                <div class="cyber-panel p-4 rounded-xl border border-white/5 font-mono text-[9px]">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-slate-500 uppercase tracking-wider font-bold">ROZETLER</h3>
                        <a href="<?php echo BASE_URL; ?>/missions" class="text-cyber-orange hover:underline font-bold">ALL</a>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5">
                        <div class="aspect-square bg-slate-950/60 rounded-lg flex items-center justify-center relative border border-white/5" title="Günlük Seri">
                            <span class="material-symbols-outlined text-cyber-orange text-sm streak-pulse" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                            <div class="absolute -bottom-1 -right-1 bg-slate-900 px-1 rounded text-[7px] font-bold border border-white/10"><?php echo $streak ?? 0; ?></div>
                        </div>
                        <div class="aspect-square bg-slate-950/60 rounded-lg flex items-center justify-center relative border border-white/5" title="Fotoğrafçı">
                            <span class="material-symbols-outlined text-slate-500 text-sm" style="font-variation-settings: 'FILL' 1;">photo_camera</span>
                        </div>
                        <div class="aspect-square bg-slate-950/60 rounded-lg flex items-center justify-center relative border border-white/5" title="Kaptan">
                            <span class="material-symbols-outlined text-slate-500 text-sm" style="font-variation-settings: 'FILL' 1;">hive</span>
                        </div>
                    </div>
                </div>

                <!-- Yakın Arkadaşlar -->
                <div class="cyber-panel p-4 rounded-xl border border-white/5 font-mono text-[9px]">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-slate-500 uppercase tracking-wider font-bold">TAKIP</h3>
                        <a href="<?php echo BASE_URL; ?>/members" class="text-cyber-orange hover:underline font-bold">ALL</a>
                    </div>
                    <div class="flex -space-x-2.5 overflow-hidden py-1">
                        <?php foreach (array_slice($followingUsers ?? [], 0, 4) as $fu): ?>
                            <?php $fuAvatar = safeAvatarUrl($fu['avatar'] ?? null, $fu['username']); ?>
                            <img alt="<?php echo escape($fu['username']); ?>" class="inline-block h-6 w-6 rounded-full ring-2 ring-[#0c0d10] object-cover" src="<?php echo $fuAvatar; ?>" width="24" height="24" title="c" />
                        <?php endforeach; ?>
                        <?php if (count($followingUsers ?? []) > 4): ?>
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 ring-2 ring-[#0c0d10] text-[8px] font-bold text-slate-300">+<?php echo count($followingUsers) - 4; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bento Widget 5: SPONSOR CAROUSEL -->
            <?php
            if (!class_exists('AdModel')) {
                require_once dirname(__DIR__, 2) . '/app/Models/Ad.php';
            }
            $rightSidebarSponsors = [];
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
            } catch (Exception $e) {}

            if (empty($rightSidebarSponsors)) {
                $rightSidebarSponsors = [
                    ['name' => 'COLOSSEUM', 'logo' => 'assets/img/sponsors/colosseum.png', 'url' => '#'],
                    ['name' => 'Paradise Group', 'logo' => 'assets/img/sponsors/paradise-group.png', 'url' => '#'],
                ];
            }
            ?>
            <div class="cyber-panel p-4 rounded-xl border border-white/5 overflow-hidden">
                <h3 class="text-[9px] font-mono font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                    Sponsorlarımız <span class="material-symbols-outlined text-cyber-orange text-[14px]">campaign</span>
                </h3>
                <div class="relative w-full rounded-xl overflow-hidden group bg-slate-950 border border-white/5" style="height: 120px;">
                    <?php foreach ($rightSidebarSponsors as $index => $sp): ?>
                    <a href="<?php echo escape($sp['url'] ?? '#'); ?>" target="_blank" rel="noopener" 
                       class="sponsor-slide absolute inset-0 flex flex-col items-center justify-center p-2 transition-opacity duration-1000 ease-in-out <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'; ?>"
                       data-index="<?php echo $index; ?>">
                        <?php if (!empty($sp['logo'])): ?>
                            <img src="<?php echo BASE_URL . '/' . escape($sp['logo']); ?>" alt="<?php echo escape($sp['name']); ?>" class="transition-transform duration-500 group-hover:scale-105" style="width: 100%; height: 100%; object-fit: contain;" loading="lazy">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-slate-500 text-[32px]">store</span>
                        <?php endif; ?>
                        
                        <div class="absolute inset-0 bg-[#060a13]/85 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center backdrop-blur-sm z-20 font-mono">
                            <span class="font-bold text-xs text-cyber-orange text-center mb-0.5"><?php echo escape($sp['name']); ?></span>
                            <span class="text-[9px] text-slate-500 tracking-wider uppercase">Official Sponsor</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </aside>
        <?php endif; ?>

</main>

<!-- ── MOBILE BOTTOM NAVIGATION BAR ── -->
<?php if (Auth::check() && isset($currentUser)): ?>
<nav class="fixed bottom-0 left-0 right-0 z-50 bg-[#0b0c10]/95 backdrop-blur-xl border-t border-white/5 md:hidden safe-area-bottom">
    <div class="flex items-center justify-around h-16">
        <?php
        $mobileNavItems = [
            'dashboard'     => ['icon' => 'explore',        'label' => 'Feed'],
            'venues'        => ['icon' => 'storefront',     'label' => 'Venues'],
            'leaderboard'   => ['icon' => 'military_tech',  'label' => 'Ranks'],
            'notifications' => ['icon' => 'notifications',  'label' => 'Notifs'],
            'profile'       => ['icon' => 'person',         'label' => 'Profile'],
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
                ? 'flex flex-col items-center justify-center gap-0.5 text-cyber-orange transition-colors relative w-12'
                : 'flex flex-col items-center justify-center gap-0.5 text-slate-400 hover:text-slate-200 transition-colors relative w-12';
        ?>
        <a href="<?php echo BASE_URL . ($mobileUrls[$mKey] ?? ''); ?>" class="<?php echo $mClass; ?>">
            <?php if ($mActive): ?>
            <div class="absolute -top-px left-1/2 -translate-x-1/2 w-6 h-0.5 bg-cyber-orange rounded-full"></div>
            <?php endif; ?>
            <span class="material-symbols-outlined text-[22px]" <?php echo $mActive ? 'data-weight="fill"' : ''; ?>><?php echo $mItem['icon']; ?></span>
            <span class="text-[9px] font-mono font-bold tracking-wide leading-none"><?php echo $mItem['label']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

<!-- ── GLOBAL FAB (FLOATING ACTION BUTTON) ── -->
<button class="fixed bottom-6 right-6 md:bottom-8 md:right-8 z-50 w-14 h-14 rounded-full bg-gradient-to-r from-cyber-orange to-cyber-orangeLight text-white flex items-center justify-center shadow-neonOrange hover:scale-105 active:scale-95 transition-transform cursor-pointer fab-glow"
    onclick="openPortalCheckinModal()" title="Hızlı Check-in Yap">
    <span class="material-symbols-outlined text-[28px] font-bold" style="font-variation-settings: 'FILL' 1;">add_location_alt</span>
</button>

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
        <div class="bg-[#1c1b1c] border border-white/10 rounded-2xl w-full max-w-md shadow-2xl relative animate-[modalIn_0.2s_ease-out]">
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
                    <button type="button" onclick="App.closeReportModal()" class="flex-1 py-3 rounded-xl bg-white/5 text-slate-300 font-bold text-sm border border-white/10 hover:bg-white/10 transition-colors font-mono text-xs">
                        IPTAL
                    </button>
                    <button type="submit" id="reportSubmitBtn" class="flex-1 py-3 rounded-xl bg-red-500/20 text-red-400 font-bold text-sm border border-red-500/30 hover:bg-red-500/30 transition-colors flex items-center justify-center gap-2 font-mono text-xs">
                        <span class="material-symbols-outlined text-[18px]">send</span> RAPOR GONDER
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── TERMINAL CHECK-IN MODAL (FLOATING HUD) ── -->
<?php if (Auth::check()): ?>
<?php
$approvedPortalVenues = [];
try {
    $approvedPortalVenues = (new VenueModel())->getApproved('', '', 40);
} catch (Exception $e) {}
?>
<div id="portalCheckinModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
    <!-- Backdrop blur -->
    <div class="absolute inset-0 bg-black/85 backdrop-blur-md" onclick="closePortalCheckinModal()"></div>
    
    <!-- Modal Content panel -->
    <div class="cyber-panel w-full max-w-lg rounded-xl border border-cyber-orange/40 overflow-hidden relative shadow-neonOrange animate-[modalIn_0.25s_ease-out]">
        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-cyber-orange to-cyber-cyan"></div>
        
        <div class="flex items-center justify-between p-4 border-b border-white/5">
            <div class="flex items-center gap-2 font-mono text-xs text-cyber-orange">
                <span class="material-symbols-outlined text-sm">terminal</span>
                <span>SECURE_TERMINAL_CHECKIN</span>
            </div>
            <button onclick="closePortalCheckinModal()" class="text-slate-400 hover:text-white font-bold text-lg">×</button>
        </div>

        <form id="portalCheckinForm" enctype="multipart/form-data" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            
            <!-- Venue selection console input -->
            <div>
                <label class="block font-mono text-[10px] text-slate-500 uppercase mb-1">SELECT_VENUE_TARGET:</label>
                <select id="portal-venue-select" name="venue_id" class="w-full bg-slate-950/80 border border-white/5 rounded px-3 py-2.5 font-mono text-xs text-slate-100 focus:border-cyber-orange focus:ring-0 focus:outline-none" onchange="updatePortalCoords()" required>
                    <option value="" disabled selected>-- MEKAN SECIN --</option>
                    <?php foreach ($approvedPortalVenues as $v): 
                        $mockX = round(sin($v['id']) * 1500, 1);
                        $mockY = round(cos($v['id'] * 2) * 1800, 1);
                        $mockZ = round(abs(sin($v['id'] * 3)) * 45 + 15, 1);
                        $mockCoords = "X: {$mockX}, Y: {$mockY}, Z: {$mockZ}";
                    ?>
                        <option value="<?php echo $v['id']; ?>" data-coords="<?php echo $mockCoords; ?>"><?php echo escape($v['name']); ?> [<?php echo (int)$mockX; ?>, <?php echo (int)$mockY; ?>]</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Coords readout -->
            <div class="font-mono text-[9px] text-cyber-cyan bg-slate-950/60 border border-white/5 p-2.5 rounded flex justify-between">
                <span>GPS LOCK: OK</span>
                <span id="portal-coords-readout">COORDS: AWAITING_LOCK</span>
            </div>

            <!-- Input area -->
            <div class="bg-slate-950 p-3 rounded border border-white/5 focus-within:border-cyber-orange transition-all font-mono">
                <span class="text-cyber-orange font-bold text-xs select-none">C:\SOCIAERA\CONSOLE&gt;</span>
                <textarea id="portal-note-input" name="note" rows="3" 
                    class="w-full bg-transparent border-none p-0 text-slate-200 text-xs focus:ring-0 focus:outline-none resize-none placeholder-slate-700 mt-1"
                    placeholder="/checkin [durum notu yazın veya /me komutu kullanın...]" required></textarea>
            </div>

            <!-- Photo attach -->
            <div class="flex justify-between items-center pt-2">
                <label class="flex items-center gap-1.5 px-3 py-1.5 rounded bg-slate-950 border border-white/5 text-[11px] font-mono hover:text-cyber-cyan transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-sm">photo_camera</span>
                    <span>ATTACH IMAGE</span>
                    <input type="file" name="image" id="portal-image-input" accept="image/*" class="hidden" onchange="previewPortalImage()">
                </label>
                
                <div id="portal-attached-preview" class="hidden relative h-8 w-14 rounded overflow-hidden border border-cyber-cyan">
                    <img id="portal-attached-img" src="" class="w-full h-full object-cover">
                    <button type="button" class="absolute inset-0 bg-black/40 flex items-center justify-center text-red-400 font-bold" onclick="clearPortalImage()">×</button>
                </div>
            </div>

            <!-- Footer buttons -->
            <div class="flex gap-3 pt-3 border-t border-white/5">
                <button type="button" onclick="closePortalCheckinModal()" class="flex-grow py-2 rounded bg-slate-900 border border-white/5 font-mono text-xs font-bold text-slate-400 hover:text-white transition-colors">
                    ABORT_VOUCHER
                </button>
                <button type="submit" id="portalCheckinSubmitBtn" class="flex-grow py-2 rounded bg-gradient-to-r from-cyber-orange to-cyber-orangeLight font-mono text-xs font-bold text-white shadow-neonOrange flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">add_location_alt</span>
                    TRANSMIT_CHECKIN
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPortalCheckinModal() {
    const modal = document.getElementById('portalCheckinModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        updatePortalCoords();
        setTimeout(() => {
            const ta = document.getElementById('portal-note-input');
            if (ta) ta.focus();
        }, 150);
    }
}

function closePortalCheckinModal() {
    const modal = document.getElementById('portalCheckinModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        document.getElementById('portal-note-input').value = "";
        clearPortalImage();
    }
}

function updatePortalCoords() {
    const select = document.getElementById('portal-venue-select');
    if (select.selectedIndex <= 0) {
        document.getElementById('portal-coords-readout').textContent = "COORDS: AWAITING_LOCK";
        return;
    }
    const option = select.options[select.selectedIndex];
    const coords = option.getAttribute('data-coords');
    document.getElementById('portal-coords-readout').textContent = `COORDS: ${coords}`;
}

function previewPortalImage() {
    const input = document.getElementById('portal-image-input');
    const preview = document.getElementById('portal-attached-preview');
    const img = document.getElementById('portal-attached-img');
    if (input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function clearPortalImage() {
    const input = document.getElementById('portal-image-input');
    const preview = document.getElementById('portal-attached-preview');
    if (input) input.value = '';
    if (preview) preview.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('portalCheckinForm');
    const btn  = document.getElementById('portalCheckinSubmitBtn');
    if (!form || !btn) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> TRANSMITTING...';

        const formData = new FormData(form);
        const res = await App.post(App.baseUrl + '/api/create-post', formData);

        if (res.ok) {
            closePortalCheckinModal();
            if (res.data && res.data.earned_campaigns && res.data.earned_campaigns.length > 0) {
                // If there is campaign rewards modal, show it (defined in venue-detail or globally)
                if (typeof showVenueCampaignModal === 'function') {
                    showVenueCampaignModal(res.data.earned_campaigns);
                } else {
                    App.flash(res.message || 'Check-in başarılı! 📍', 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                App.flash(res.message || 'Check-in başarılı! 📍', 'success');
                setTimeout(() => location.reload(), 800);
            }
        } else {
            App.flash(res.error || 'Hata oluştu.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-sm" style="font-variation-settings:\'FILL\' 1;">add_location_alt</span> TRANSMIT_CHECKIN';
        }
    });
});
</script>
<?php endif; ?>

<!-- Sponsors Cycle Script -->
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

</div> <!-- End crt-monitor -->

<script src="<?php echo asset('js/app.js'); ?>"></script>
</body>
</html>
