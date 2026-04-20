<?php
/**
 * Footer Partial
 */
?>

    <!-- Footer Ad -->
    <?php if (!empty($footerAds)): ?>
    <div class="footer-ad-banner">
        <?php $fAd = $footerAds[0]; ?>
        <a href="<?php echo escape($fAd['link_url'] ?: '#'); ?>" target="_blank" rel="noopener">
            <img src="<?php echo BASE_URL . '/' . escape($fAd['image_url']); ?>" alt="<?php echo escape($fAd['title']); ?>">
        </a>
    </div>
    <?php endif; ?>

    <footer class="site-footer">
        <div class="container-xl">
            <div class="footer-grid">
                <div class="footer-about">
                    <h3><i class="bi bi-pin-map-fill"></i> <?php echo APP_NAME; ?></h3>
                    <p>GTA World TR için sosyal keşif & check-in platformu. Favori mekanlarını keşfet, anlarını paylaş.</p>
                </div>
                <div class="footer-links">
                    <h4>Keşfet</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/venues">Mekanlar</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/leaderboard">Sıralama</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/members">Üyeler</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/sponsors">Sponsorlar</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Hesap</h4>
                    <ul>
                        <?php if (Auth::check()): ?>
                            <li><a href="<?php echo BASE_URL; ?>/profile">Profil</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/settings">Ayarlar</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/wallet">Cüzdan</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>/login">Giriş Yap</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/register">Kayıt Ol</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sociaera JS -->
    <script src="<?php echo asset('js/app.js'); ?>"></script>
</body>
</html>
