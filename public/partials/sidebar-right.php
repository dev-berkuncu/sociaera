<?php
/**
 * Sağ Sidebar — Trend Mekanlar + Mini Leaderboard + Reklam
 */
$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}
?>
<aside class="sidebar-right d-none d-xl-block">
    <!-- Carousel Reklam -->
    <?php if (!empty($carouselAds)): ?>
    <div class="sidebar-carousel">
        <div id="adCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-inner">
                <?php foreach ($carouselAds as $i => $cAd): ?>
                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                    <a href="<?php echo escape($cAd['link_url'] ?: '#'); ?>" target="_blank" rel="noopener">
                        <img src="<?php echo BASE_URL . '/' . escape($cAd['image_url']); ?>" alt="<?php echo escape($cAd['title']); ?>" class="carousel-ad-img">
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($carouselAds) > 1): ?>
            <div class="carousel-indicators-custom">
                <?php foreach ($carouselAds as $i => $cAd): ?>
                <button data-bs-target="#adCarousel" data-bs-slide-to="<?php echo $i; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Trend Mekanlar -->
    <?php if (!empty($trendVenues)): ?>
    <div class="sidebar-card">
        <h4 class="sidebar-card-title"><i class="bi bi-fire"></i> Trend Mekanlar</h4>
        <div class="sidebar-card-body">
            <?php foreach ($trendVenues as $tv): ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $tv['id']; ?>" class="trend-item">
                <div class="trend-info">
                    <span class="trend-name"><?php echo escape($tv['name']); ?></span>
                    <span class="trend-cat"><?php echo escape($tv['category'] ?? ''); ?></span>
                </div>
                <span class="trend-count"><?php echo $tv['weekly_checkins']; ?> <i class="bi bi-pin-map"></i></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mini Leaderboard -->
    <?php if (!empty($miniLeaderboard)): ?>
    <div class="sidebar-card">
        <h4 class="sidebar-card-title"><i class="bi bi-trophy"></i> Haftalık Top 5</h4>
        <div class="sidebar-card-body">
            <?php foreach ($miniLeaderboard as $i => $lb): ?>
            <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($lb['tag'] ?: $lb['username']); ?>" class="leaderboard-mini-item">
                <span class="lb-rank"><?php echo $i + 1; ?></span>
                <div class="lb-avatar-wrap">
                    <?php echo avatarHtml($lb['avatar'] ?? null, $lb['username'], '28'); ?>
                </div>
                <span class="lb-name"><?php echo escape($lb['username']); ?></span>
                <span class="lb-count"><?php echo $lb['checkin_count']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <a href="<?php echo BASE_URL; ?>/leaderboard" class="sidebar-card-footer">Tümünü Gör <i class="bi bi-arrow-right"></i></a>
    </div>
    <?php endif; ?>

    <!-- Sağ Sidebar Reklam -->
    <?php if (!empty($sidebarRightAds)): ?>
    <div class="sidebar-ad-slot">
        <?php $rAd = $sidebarRightAds[0]; ?>
        <a href="<?php echo escape($rAd['link_url'] ?: '#'); ?>" target="_blank" rel="noopener">
            <img src="<?php echo BASE_URL . '/' . escape($rAd['image_url']); ?>" alt="<?php echo escape($rAd['title']); ?>" class="sidebar-ad-img">
        </a>
    </div>
    <?php endif; ?>

    <!-- Sponsors Link -->
    <div class="sidebar-card sidebar-sponsors-link">
        <a href="<?php echo BASE_URL; ?>/sponsors" class="sidebar-card-footer">
            <i class="bi bi-megaphone"></i> Sponsorlar
        </a>
    </div>
</aside>
