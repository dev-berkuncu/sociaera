<?php
/**
 * Admin Sidebar Partial
 */
$adminPage = $adminPage ?? '';
$pendingVenues = $pendingVenues ?? 0;
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-header">
        <h2><i class="bi bi-shield-check"></i> Admin Panel</h2>
    </div>
    <nav class="admin-nav">
        <a href="<?php echo BASE_URL; ?>/admin/" class="admin-nav-item <?php echo $adminPage === 'dashboard' ? 'active' : ''; ?>">
            <span class="admin-nav-icon"><i class="bi bi-speedometer2"></i></span> Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/users" class="admin-nav-item <?php echo $adminPage === 'users' ? 'active' : ''; ?>">
            <span class="admin-nav-icon"><i class="bi bi-people"></i></span> Kullanıcılar
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/venues" class="admin-nav-item <?php echo $adminPage === 'venues' ? 'active' : ''; ?>">
            <span class="admin-nav-icon"><i class="bi bi-geo-alt"></i></span> Mekanlar
            <?php if ($pendingVenues > 0): ?>
                <span class="admin-nav-badge"><?php echo $pendingVenues; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/posts" class="admin-nav-item <?php echo $adminPage === 'posts' ? 'active' : ''; ?>">
            <span class="admin-nav-icon"><i class="bi bi-file-text"></i></span> Gönderiler
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/ads" class="admin-nav-item <?php echo $adminPage === 'ads' ? 'active' : ''; ?>">
            <span class="admin-nav-icon"><i class="bi bi-megaphone"></i></span> Reklamlar
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/settings" class="admin-nav-item <?php echo $adminPage === 'settings' ? 'active' : ''; ?>">
            <span class="admin-nav-icon"><i class="bi bi-gear"></i></span> Ayarlar
        </a>
        <hr style="border-color:var(--border-light); margin:12px 16px;">
        <a href="<?php echo BASE_URL; ?>/dashboard" class="admin-nav-item">
            <span class="admin-nav-icon"><i class="bi bi-arrow-left"></i></span> Siteye Dön
        </a>
    </nav>
</aside>
