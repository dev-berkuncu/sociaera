<?php
/**
 * Check-in Satırı Bileşeni — Sade, sosyal medya hissi olmadan
 * $ci (check-in verisi) dışarıdan gelir
 * Kullanılan alanlar: id, username, tag, avatar, venue_name, venue_id, note, created_at, image
 */
if (!isset($ci)) return;

$ciAvatarUrl = !empty($ci['is_mystery_shopper'])
    ? 'https://ui-avatars.com/api/?name=GM&background=6900b3&color=ffffff'
    : safeAvatarUrl($ci['avatar'] ?? null, $ci['username'] ?? 'U');
$ciTimeAgo = timeAgo($ci['created_at']);
$ciNote    = !empty($ci['note']) ? mb_strimwidth($ci['note'], 0, 80, '…') : null;
$ciProfileUrl = BASE_URL . '/profile?u=' . urlencode($ci['tag'] ?: ($ci['username'] ?? ''));
$ciVenueUrl   = !empty($ci['venue_id']) ? BASE_URL . '/venue-detail?id=' . (int)$ci['venue_id'] : '#';
?>
<div class="flex items-center gap-3 px-4 py-3 hover:bg-surface-container/40 transition-colors group" id="ci-<?php echo (int)$ci['id']; ?>">

    <!-- Avatar -->
    <a href="<?php echo $ciProfileUrl; ?>" class="flex-shrink-0">
        <img src="<?php echo $ciAvatarUrl; ?>" alt="<?php echo escape($ci['username'] ?? ''); ?>"
             class="w-9 h-9 rounded-full object-cover border border-white/10 hover:border-primary/40 transition-colors" width="36" height="36">
    </a>

    <!-- İçerik -->
    <div class="flex-grow min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
            <a href="<?php echo $ciProfileUrl; ?>" class="font-bold text-sm text-on-surface hover:text-primary transition-colors">
                <?php echo escape($ci['username'] ?? 'Kullanıcı'); ?>
            </a>
            <span class="text-on-surface-variant text-xs">→</span>
            <a href="<?php echo $ciVenueUrl; ?>" class="text-sm text-primary font-semibold hover:underline truncate max-w-[140px]">
                <?php echo escape($ci['venue_name'] ?? ''); ?>
            </a>
            <span class="text-on-surface-variant text-[10px] ml-auto flex-shrink-0"><?php echo $ciTimeAgo; ?></span>
        </div>
        <?php if ($ciNote): ?>
        <p class="text-[11px] text-on-surface-variant mt-0.5 truncate italic">"<?php echo escape($ciNote); ?>"</p>
        <?php endif; ?>
    </div>

    <!-- Check-in Badge -->
    <div class="flex items-center gap-1 bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold px-2 py-1 rounded-full flex-shrink-0 group-hover:bg-primary/20 transition-colors">
        <span class="material-symbols-outlined text-[11px]" style="font-variation-settings:'FILL' 1;">verified</span>
        <span class="hidden sm:inline">Check-in</span>
    </div>

</div>
