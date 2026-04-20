<?php
/**
 * Sol Sidebar — Navigasyon + Mini Profil
 */
?>
<aside class="sidebar-left d-none d-lg-block">
    <?php if (Auth::check()):
        $sideUser = (new UserModel())->getById(Auth::id());
        $sideStats = (new UserModel())->getStats(Auth::id());
    ?>
    <!-- Mini Profil -->
    <div class="sidebar-profile-card">
        <div class="sp-banner" <?php if (bannerUrl($sideUser['banner'] ?? null)): ?>style="background-image:url('<?php echo bannerUrl($sideUser['banner']); ?>')"<?php endif; ?>></div>
        <div class="sp-body">
            <a href="<?php echo BASE_URL; ?>/profile" class="sp-avatar">
                <?php echo avatarHtml($sideUser['avatar'] ?? null, $sideUser['username'], '56'); ?>
            </a>
            <a href="<?php echo BASE_URL; ?>/profile" class="sp-name"><?php echo escape($sideUser['username']); ?></a>
            <?php if (!empty($sideUser['tag'])): ?>
                <span class="sp-tag">@<?php echo escape($sideUser['tag']); ?></span>
            <?php endif; ?>
            <div class="sp-stats">
                <div class="sp-stat">
                    <span class="sp-stat-num"><?php echo shortNumber($sideStats['following']); ?></span>
                    <span class="sp-stat-label">Takip</span>
                </div>
                <div class="sp-stat">
                    <span class="sp-stat-num"><?php echo shortNumber($sideStats['followers']); ?></span>
                    <span class="sp-stat-label">Takipçi</span>
                </div>
                <div class="sp-stat">
                    <span class="sp-stat-num"><?php echo shortNumber($sideStats['checkins']); ?></span>
                    <span class="sp-stat-label">Check-in</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Nav Links -->
    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>/dashboard" class="sidebar-nav-item <?php echo ($activeNav ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-house-door"></i> Ana Sayfa
        </a>
        <a href="<?php echo BASE_URL; ?>/venues" class="sidebar-nav-item <?php echo ($activeNav ?? '') === 'venues' ? 'active' : ''; ?>">
            <i class="bi bi-geo-alt"></i> Mekanlar
        </a>
        <a href="<?php echo BASE_URL; ?>/leaderboard" class="sidebar-nav-item <?php echo ($activeNav ?? '') === 'leaderboard' ? 'active' : ''; ?>">
            <i class="bi bi-trophy"></i> Sıralama
        </a>
        <a href="<?php echo BASE_URL; ?>/members" class="sidebar-nav-item <?php echo ($activeNav ?? '') === 'members' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Üyeler
        </a>
        <a href="<?php echo BASE_URL; ?>/wallet" class="sidebar-nav-item <?php echo ($activeNav ?? '') === 'wallet' ? 'active' : ''; ?>">
            <i class="bi bi-wallet2"></i> Cüzdan
        </a>
        <a href="<?php echo BASE_URL; ?>/premium" class="sidebar-nav-item <?php echo ($activeNav ?? '') === 'premium' ? 'active' : ''; ?>">
            <i class="bi bi-gem"></i> Premium
        </a>
        <a href="<?php echo BASE_URL; ?>/settings" class="sidebar-nav-item <?php echo ($activeNav ?? '') === 'settings' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i> Ayarlar
        </a>
    </nav>

    <!-- Sponsor Slot -->
    <?php if (!empty($sidebarLeftAds)): ?>
    <div class="sidebar-ad-slot">
        <?php $lAd = $sidebarLeftAds[0]; ?>
        <a href="<?php echo escape($lAd['link_url'] ?: '#'); ?>" target="_blank" rel="noopener">
            <img src="<?php echo BASE_URL . '/' . escape($lAd['image_url']); ?>" alt="<?php echo escape($lAd['title']); ?>" class="sidebar-ad-img">
        </a>
    </div>
    <?php endif; ?>
</aside>
