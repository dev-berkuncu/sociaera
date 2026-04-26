<?php
/**
 * Sponsorlu İçerik — Feed Kartı (Tailwind)
 * Kullanım: $sponsoredAd değişkeniyle include edilir
 */
if (empty($sponsoredAd)) return;
?>
<div class="post-card bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] overflow-hidden">
    <!-- Sponsored Badge -->
    <div class="px-5 pt-4 pb-2 flex items-center justify-between">
        <span class="inline-flex items-center gap-1.5 bg-primary-container/10 text-primary-container text-label-sm font-semibold px-3 py-1 rounded-full border border-primary-container/20">
            <span class="material-symbols-outlined text-[14px]">campaign</span>
            Sponsorlu İçerik
        </span>
    </div>

    <!-- Content -->
    <a href="<?php echo escape($sponsoredAd['link_url'] ?? '#'); ?>" target="_blank" rel="noopener" class="block group">
        <?php if (!empty($sponsoredAd['image_url'])): ?>
        <div class="px-5 pb-3">
            <div class="rounded-xl overflow-hidden border border-white/5">
                <img src="<?php echo BASE_URL . '/' . escape($sponsoredAd['image_url']); ?>" 
                     alt="<?php echo escape($sponsoredAd['title']); ?>" 
                     class="w-full h-auto object-cover group-hover:scale-[1.02] transition-transform duration-300"
                     loading="lazy">
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($sponsoredAd['title'])): ?>
        <div class="px-5 pb-4">
            <h3 class="text-on-surface font-semibold text-base group-hover:text-primary-container transition-colors"><?php echo escape($sponsoredAd['title']); ?></h3>
            <?php if (!empty($sponsoredAd['link_url'])): ?>
            <p class="text-slate-500 text-xs mt-1 truncate"><?php echo parse_url($sponsoredAd['link_url'], PHP_URL_HOST) ?? ''; ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </a>
</div>
