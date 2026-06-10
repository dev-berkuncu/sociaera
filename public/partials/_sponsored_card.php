<?php
/**
 * Sponsorlu İçerik — Feed Kartı (inline styles, light theme)
 * Kullanım: $sponsoredAd değişkeniyle include edilir
 */
if (empty($sponsoredAd)) return;
?>
<div class="post-card" style="background:#fff; border:1px solid var(--border); border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08);">
    <!-- Sponsored Badge -->
    <div style="padding:14px 18px 8px; display:flex; align-items:center; justify-content:space-between;">
        <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(240,109,31,0.08); color:var(--color-primary); font-size:11px; font-weight:700; padding:4px 10px; border-radius:999px; border:1px solid rgba(240,109,31,0.2);">
            <span class="material-symbols-outlined" style="font-size:14px;">campaign</span>
            Sponsorlu İçerik
        </span>
    </div>

    <!-- Content -->
    <a href="<?php echo escape($sponsoredAd['link_url'] ?? '#'); ?>" target="_blank" rel="noopener" style="display:block; text-decoration:none;">
        <?php if (!empty($sponsoredAd['image_url'])): ?>
        <div style="padding:0 16px 12px;">
            <div style="border-radius:10px; overflow:hidden; border:1px solid var(--border);">
                <img src="<?php echo BASE_URL . '/' . escape($sponsoredAd['image_url']); ?>"
                     alt="<?php echo escape($sponsoredAd['title']); ?>"
                     style="width:100%; height:auto; object-fit:cover; display:block; transition:transform .3s;"
                     onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'"
                     width="640" height="320"
                     loading="lazy">
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($sponsoredAd['title'])): ?>
        <div style="padding:0 18px 16px;">
            <h3 style="font-weight:600; font-size:14px; color:var(--text-1); margin:0 0 4px; transition:color .15s;"
                onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-1)'"><?php echo escape($sponsoredAd['title']); ?></h3>
            <?php if (!empty($sponsoredAd['link_url'])): ?>
            <p style="color:var(--text-3); font-size:11px; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo parse_url($sponsoredAd['link_url'], PHP_URL_HOST) ?? ''; ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </a>
</div>
