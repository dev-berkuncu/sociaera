<?php
/**
 * Sociaera — App Footer (Swarm Edition)
 * Bottom nav + Right panel + FAB + Modals
 */
?>

    <?php if (Auth::check() && isset($currentUser) && empty($hideSidebar)): ?>
    <!-- ── SAĞ PANEL (Desktop) ──────────────────────────── -->
    <aside class="swarm-right-panel">

        <!-- Kullanıcı Gamification Widget -->
        <div class="right-panel-card">
            <div class="right-panel-card-header">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-3);">Bu Hafta</div>
            </div>
            <div class="right-panel-card-body" style="display:flex;flex-direction:column;gap:8px;">
                <!-- Streak -->
                <div class="streak-widget">
                    <span class="material-symbols-outlined" style="color:var(--color-primary);font-variation-settings:'FILL' 1;font-size:20px;">local_fire_department</span>
                    <div>
                        <div style="font-size:16px;font-weight:800;line-height:1;"><?php echo $headerStreak; ?> gün</div>
                        <div style="font-size:11px;font-weight:500;color:var(--color-primary);opacity:0.7;">Günlük Seri</div>
                    </div>
                </div>

                <!-- Haftalık İlerleme -->
                <div style="background:var(--bg-section);border-radius:12px;padding:10px 14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-size:12px;font-weight:600;color:var(--text-2);">Haftalık Hedef</span>
                        <span style="font-size:12px;font-weight:800;color:var(--color-primary);"><?php echo min(5, $headerWeekly); ?>/5</span>
                    </div>
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" style="width:<?php echo min(100, ($headerWeekly / 5) * 100); ?>%;"></div>
                    </div>
                </div>

                <?php if ($headerRank): ?>
                <!-- Sıralama -->
                <div class="rank-widget">
                    <span class="material-symbols-outlined" style="color:#7C3AED;font-variation-settings:'FILL' 1;font-size:20px;">emoji_events</span>
                    <div>
                        <div style="font-size:16px;font-weight:800;line-height:1;">#<?php echo $headerRank; ?></div>
                        <div style="font-size:11px;color:#7C3AED;opacity:0.7;">Haftalık Sıran</div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/leaderboard" style="margin-left:auto;font-size:11px;font-weight:700;color:#7C3AED;text-decoration:none;">Gör →</a>
                </div>
                <?php endif; ?>

                <!-- Check-in yap butonu -->
                <a href="<?php echo BASE_URL; ?>/venues" class="btn btn-primary btn-block" style="justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1;">where_to_vote</span>
                    Check-in Yap
                </a>
            </div>
        </div>

        <!-- Trend Mekanlar -->
        <?php
        $footerTrendV = $headerTrend ?? [];
        if (empty($footerTrendV)) {
            try { $footerTrendV = (new VenueModel())->getTrending(5); } catch (Exception $e) {}
        }
        if (empty($footerTrendV)) {
            try { $footerTrendV = (new VenueModel())->getApproved('', '', 5); } catch (Exception $e) {}
        }
        $footerTrendV = array_slice($footerTrendV, 0, 5);
        if (!empty($footerTrendV)):
        ?>
        <div class="right-panel-card">
            <div class="right-panel-card-header" style="display:flex;align-items:center;justify-content:space-between;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-3);">🔥 Trend Mekanlar</div>
                <a href="<?php echo BASE_URL; ?>/venues" style="font-size:11px;font-weight:700;color:var(--color-primary);text-decoration:none;">Tümü →</a>
            </div>
            <div style="padding:8px 0;">
                <?php foreach ($footerTrendV as $i => $tv):
                    $tvCats = VenueModel::categories();
                    $tvCatLabel = $tvCats[$tv['category'] ?? 'diger'] ?? 'Mekan';
                    $tvCheckins = (int)($tv['weekly_checkins'] ?? $tv['checkin_count'] ?? 0);
                ?>
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $tv['id']; ?>" style="display:flex;align-items:center;gap:10px;padding:8px 14px;text-decoration:none;color:inherit;transition:background .12s;" onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''">
                    <!-- Sıra -->
                    <div style="width:20px;font-size:13px;font-weight:800;color:var(--text-3);text-align:center;flex-shrink:0;"><?php echo $i+1; ?></div>
                    <!-- Görsel -->
                    <div style="width:40px;height:40px;border-radius:10px;overflow:hidden;background:var(--bg-section);flex-shrink:0;">
                        <?php if (!empty($tv['cover_image'])): ?>
                            <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($tv['cover_image']); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php elseif (!empty($tv['image'])): ?>
                            <img src="<?php echo uploadUrl('venues', $tv['image']); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                <span class="material-symbols-outlined" style="font-size:20px;color:var(--text-3);">storefront</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- İsim + check-in -->
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:700;color:var(--text-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo escape($tv['name']); ?></div>
                        <div style="font-size:11px;color:var(--text-3);"><?php echo escape($tvCatLabel); ?></div>
                    </div>
                    <!-- Check-in sayısı -->
                    <div style="font-size:11px;font-weight:700;color:var(--color-primary);flex-shrink:0;"><?php echo $tvCheckins; ?> ✓</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </aside>
    <?php endif; ?>

</div><!-- /swarm-layout or hideSidebar wrapper -->

<?php if (Auth::check() && isset($currentUser)): ?>

<!-- ── MOBILE BOTTOM NAV ──────────────────────────────────── -->
<nav class="swarm-bottom-nav">
    <?php
    $nav = [
        'dashboard'   => ['icon' => 'home',       'label' => 'Ana Sayfa', 'url' => '/dashboard'],
        'activity'    => ['icon' => 'explore',     'label' => 'Keşfet',   'url' => '/activity'],
        '_checkin'    => ['icon' => 'add_location','label' => '',          'url' => '/venues'],
        'leaderboard' => ['icon' => 'leaderboard', 'label' => 'Sıralama', 'url' => '/leaderboard'],
        'profile'     => ['icon' => 'person',      'label' => 'Profil',   'url' => '/profile'],
    ];
    $activeNavKey = $activeNav ?? '';
    ?>
    <?php foreach ($nav as $key => $item): ?>
        <?php if ($key === '_checkin'): ?>
        <div class="swarm-nav-fab-item">
            <a href="<?php echo BASE_URL . $item['url']; ?>" class="swarm-nav-fab-inner" aria-label="Check-in Yap">
                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">where_to_vote</span>
            </a>
        </div>
        <?php else: ?>
        <?php $isActive = ($activeNavKey === $key); ?>
        <a href="<?php echo BASE_URL . $item['url']; ?>" class="swarm-nav-item <?php echo $isActive ? 'active' : ''; ?>">
            <span class="material-symbols-outlined"><?php echo $item['icon']; ?></span>
            <?php if ($item['label']): ?><span><?php echo $item['label']; ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<!-- ── DESKTOP FAB (Check-in) ────────────────────────────── -->
<a href="<?php echo BASE_URL; ?>/venues" class="swarm-fab" aria-label="Check-in Yap">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">where_to_vote</span>
</a>

<?php endif; ?>

<!-- ── FLASH MESSAGES ─────────────────────────────────────── -->
<?php
$_footerFlash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if ($_footerFlash && isset($_footerFlash['type'], $_footerFlash['message'])):
    $fType = $_footerFlash['type'];
    $fMsg  = $_footerFlash['message'];
    $fBg   = $fType === 'success' ? '#16a34a' : ($fType === 'error' ? '#ef4444' : '#2563EB');
    $fIcon = $fType === 'success' ? 'check_circle' : ($fType === 'error' ? 'error' : 'info');
?>
<div id="flashMsg" style="
    position:fixed; top:76px; right:20px; z-index:99999;
    background:<?php echo $fBg; ?>; color:#fff;
    padding:12px 16px; border-radius:12px;
    display:flex; align-items:center; gap:10px;
    max-width:340px; width:auto; min-width:160px;
    font-size:13px; font-weight:600; font-family:inherit;
    box-shadow:0 8px 24px rgba(0,0,0,0.22);
    animation:slideInRight 0.3s ease forwards;
    transition:opacity .3s;
">
    <span class="material-symbols-outlined" style="font-size:18px;flex-shrink:0;font-variation-settings:'FILL' 1;"><?php echo $fIcon; ?></span>
    <span style="flex:1;line-height:1.4;"><?php echo htmlspecialchars($fMsg); ?></span>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;color:rgba(255,255,255,0.8);cursor:pointer;padding:0;margin-left:4px;display:flex;align-items:center;flex-shrink:0;">
        <span class="material-symbols-outlined" style="font-size:18px;">close</span>
    </button>
</div>
<script>setTimeout(function(){var f=document.getElementById('flashMsg');if(f){f.style.opacity='0';setTimeout(function(){f.remove();},350);}},4000);</script>
<?php endif; ?>


<!-- ── RAPOR MODAL ────────────────────────────────────────── -->
<?php if (Auth::check()): ?>
<div id="reportModal" style="display:none;position:fixed;inset:0;z-index:9999;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);" onclick="App.closeReportModal()"></div>
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:440px;box-shadow:0 24px 48px rgba(0,0,0,0.2);animation:modalIn .2s ease-out;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--border-light);">
                <h3 style="font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px;color:var(--text-1);">
                    <span class="material-symbols-outlined" style="color:var(--color-danger);">flag</span>
                    İçeriği Raporla
                </h3>
                <button onclick="App.closeReportModal()" style="background:none;border:none;cursor:pointer;color:var(--text-3);">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="reportForm" onsubmit="App.submitReport(event)" style="padding:16px 20px 20px;">
                <input type="hidden" name="entity_type" id="report_entity_type">
                <input type="hidden" name="entity_id" id="report_entity_id">

                <label style="display:block;font-size:13px;font-weight:700;color:var(--text-2);margin-bottom:10px;">Neden raporluyorsunuz?</label>
                <div style="display:grid;gap:6px;margin-bottom:14px;">
                    <?php
                    $reasons = [
                        'spam'          => ['icon' => 'mark_email_unread', 'label' => 'Spam / Reklam'],
                        'harassment'    => ['icon' => 'report',            'label' => 'Taciz / Zorbalık'],
                        'inappropriate' => ['icon' => 'block',             'label' => 'Uygunsuz İçerik'],
                        'fake_checkin'  => ['icon' => 'location_off',      'label' => 'Sahte Check-in'],
                        'fraud'         => ['icon' => 'gpp_bad',           'label' => 'Dolandırıcılık'],
                        'bug'           => ['icon' => 'bug_report',        'label' => 'Hata Bildir (Bug)'],
                        'feedback'      => ['icon' => 'feedback',          'label' => 'Geri Bildirim'],
                        'other'         => ['icon' => 'more_horiz',        'label' => 'Diğer'],
                    ];
                    foreach ($reasons as $key => $r):
                    ?>
                    <label style="cursor:pointer;">
                        <input type="radio" name="reason" value="<?php echo $key; ?>" style="display:none;" class="report-radio" required>
                        <div class="report-option" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:12px;border:1.5px solid var(--border);background:var(--bg-section);transition:all .12s;">
                            <span class="material-symbols-outlined" style="font-size:18px;color:var(--text-3);"><?php echo $r['icon']; ?></span>
                            <span style="font-size:13px;font-weight:600;color:var(--text-2);"><?php echo $r['label']; ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <textarea name="description" id="report_description" rows="2" maxlength="500"
                    style="width:100%;border:1.5px solid var(--border);border-radius:12px;padding:10px 14px;font-size:13px;font-family:var(--font);outline:none;resize:none;margin-bottom:14px;color:var(--text-1);background:var(--bg-input);"
                    placeholder="Ek açıklama (opsiyonel)"></textarea>

                <div style="display:flex;gap:10px;">
                    <button type="button" onclick="App.closeReportModal()" class="btn btn-ghost btn-sm" style="flex:1;">İptal</button>
                    <button type="submit" id="reportSubmitBtn" class="btn btn-sm" style="flex:1;background:#FEF2F2;color:var(--color-danger);border:1.5px solid #FCA5A5;">
                        <span class="material-symbols-outlined" style="font-size:16px;">send</span>
                        Rapor Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Report radio highlight
document.querySelectorAll('.report-radio').forEach(function(r){
    r.addEventListener('change', function(){
        document.querySelectorAll('.report-option').forEach(function(o){
            o.style.borderColor = 'var(--border)';
            o.style.background = 'var(--bg-section)';
        });
        this.closest('label').querySelector('.report-option').style.borderColor = 'var(--color-danger)';
        this.closest('label').querySelector('.report-option').style.background = '#FEF2F2';
    });
});
</script>
<?php endif; ?>
<script>
// Global image fallback handler to prevent any broken images
document.addEventListener('error', function(e) {
    if (e.target && e.target.tagName && e.target.tagName.toLowerCase() === 'img') {
        if (!e.target.hasAttribute('data-failed')) {
            e.target.setAttribute('data-failed', '1');
            e.target.style.background = 'linear-gradient(135deg, #f06d1f, #ffa633)';
            e.target.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        }
    }
}, true);
</script>
<script src="<?php echo asset('js/app.js'); ?>"></script>
</body>
</html>
