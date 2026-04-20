<?php
/**
 * Navbar Partial
 * $activeNav değişkeni dışarıdan gelir: 'dashboard', 'venues', 'leaderboard', 'members', 'profile', etc.
 */
$activeNav = $activeNav ?? '';
$notifCount = 0;
if (Auth::check() && class_exists('NotificationModel')) {
    try {
        $notifModel = new NotificationModel();
        $notifCount = $notifModel->getUnreadCount(Auth::id());
    } catch (\Throwable $e) {}
}
?>
<nav class="navbar-main">
    <div class="navbar-inner container-xl">
        <!-- Logo (Centered) -->
        <a href="<?php echo BASE_URL; ?>/dashboard" class="navbar-brand navbar-brand-center">
            <i class="bi bi-pin-map-fill"></i>
            <span><?php echo APP_NAME; ?></span>
        </a>

        <!-- Right -->
        <div class="navbar-right">
            <?php if (Auth::check()): ?>
                <?php if ($notifCount > 0): ?>
                    <a href="<?php echo BASE_URL; ?>/notifications" class="btn-nav-icon" title="Bildirimler" style="position:relative;">
                        <i class="bi bi-bell-fill"></i>
                        <span class="notif-badge"><?php echo $notifCount > 9 ? '9+' : $notifCount; ?></span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/profile" class="nav-avatar" title="Profil">
                    <?php echo avatarHtml(Auth::check() ? ($_SESSION['avatar'] ?? null) : null, Auth::username() ?? 'U', '32'); ?>
                </a>
                <?php if (Auth::isAdmin()): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/" class="btn-nav-icon d-none d-md-flex" title="Admin Panel">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/logout" class="btn-nav-icon d-none d-md-flex" title="Çıkış">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/login" class="btn btn-sm btn-primary-orange">Giriş Yap</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Mobile Bottom Nav -->
<?php if (Auth::check()): ?>
<nav class="mobile-bottom-nav d-md-none">
    <a href="<?php echo BASE_URL; ?>/dashboard" class="mobile-nav-item <?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>">
        <i class="bi bi-house-door<?php echo $activeNav === 'dashboard' ? '-fill' : ''; ?>"></i>
    </a>
    <a href="<?php echo BASE_URL; ?>/venues" class="mobile-nav-item <?php echo $activeNav === 'venues' ? 'active' : ''; ?>">
        <i class="bi bi-geo-alt<?php echo $activeNav === 'venues' ? '-fill' : ''; ?>"></i>
    </a>
    <a href="<?php echo BASE_URL; ?>/leaderboard" class="mobile-nav-item <?php echo $activeNav === 'leaderboard' ? 'active' : ''; ?>">
        <i class="bi bi-trophy<?php echo $activeNav === 'leaderboard' ? '-fill' : ''; ?>"></i>
    </a>
    <a href="<?php echo BASE_URL; ?>/notifications" class="mobile-nav-item <?php echo $activeNav === 'notifications' ? 'active' : ''; ?>">
        <i class="bi bi-bell<?php echo $activeNav === 'notifications' ? '-fill' : ''; ?>"></i>
        <?php if ($notifCount > 0): ?>
            <span class="notif-badge"><?php echo $notifCount > 9 ? '9+' : $notifCount; ?></span>
        <?php endif; ?>
    </a>
    <a href="<?php echo BASE_URL; ?>/profile" class="mobile-nav-item <?php echo $activeNav === 'profile' ? 'active' : ''; ?>">
        <i class="bi bi-person<?php echo $activeNav === 'profile' ? '-fill' : ''; ?>"></i>
    </a>
</nav>
<?php endif; ?>
