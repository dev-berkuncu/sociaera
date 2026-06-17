<?php
/**
 * Check-in Satırı Bileşeni (Swarm-style)
 * $ci (check-in verisi) dışarıdan gelir
 */
if (!isset($ci)) return;

$categoryMeta = [
    'restoran'  => ['icon' => 'restaurant',     'color' => '#ff6b35'],
    'kafe'      => ['icon' => 'local_cafe',      'color' => '#c47c4a'],
    'bar'       => ['icon' => 'sports_bar',      'color' => '#f59e0b'],
    'otel'      => ['icon' => 'hotel',           'color' => '#6366f1'],
    'alisveris' => ['icon' => 'shopping_bag',    'color' => '#3b82f6'],
    'eglence'   => ['icon' => 'theaters',        'color' => '#8b5cf6'],
    'spor'      => ['icon' => 'fitness_center',  'color' => '#ef4444'],
    'saglik'    => ['icon' => 'spa',             'color' => '#ec4899'],
    'kultur'    => ['icon' => 'museum',          'color' => '#14b8a6'],
    'diger'     => ['icon' => 'place',           'color' => '#64748b'],
];

$ciCat    = $ci['venue_category'] ?? ($ci['category'] ?? 'diger');
$ciMeta   = $categoryMeta[$ciCat] ?? $categoryMeta['diger'];
$ciAvatarUrl = !empty($ci['is_mystery_shopper'])
    ? 'https://ui-avatars.com/api/?name=GM&background=6900b3&color=ffffff'
    : safeAvatarUrl($ci['avatar'] ?? null, $ci['username'] ?? 'U');
$ciTimeAgo = timeAgo($ci['created_at']);
$ciNote    = !empty($ci['note']) ? mb_strimwidth($ci['note'], 0, 80, '…') : null;
$ciProfileUrl = BASE_URL . '/profile?u=' . urlencode($ci['tag'] ?: ($ci['username'] ?? ''));
$ciVenueUrl   = !empty($ci['venue_id']) ? BASE_URL . '/venue-detail?id=' . (int)$ci['venue_id'] : '#';
?>
<div style="display:flex; align-items:center; gap:12px; padding:14px 16px; border-bottom:1px solid var(--border-light); transition:background 0.15s; text-decoration:none;" id="ci-<?php echo (int)$ci['id']; ?>" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='transparent'">

    <!-- Avatar + kategori pin -->
    <a href="<?php echo $ciProfileUrl; ?>" style="position:relative; flex-shrink:0; text-decoration:none;">
        <img src="<?php echo $ciAvatarUrl; ?>" alt="<?php echo escape($ci['username'] ?? ''); ?>"
             style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,0.1); transition:border-color 0.15s;" width="40" height="40" onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">
        <div style="position:absolute; bottom:-4px; right:-4px; width:20px; height:20px; border-radius:50%; border:2px solid var(--bg-body); display:flex; align-items:center; justify-content:center; background:<?php echo $ciMeta['color']; ?>;">
            <span class="material-symbols-outlined" style="font-size:9px; color:#fff; font-variation-settings:'FILL' 1;"><?php echo $ciMeta['icon']; ?></span>
        </div>
    </a>

    <!-- İçerik -->
    <div style="flex-grow:1; min-width:0;">
        <div style="display:flex; align-items:baseline; gap:6px; flex-wrap:wrap; line-height:1.2;">
            <a href="<?php echo $ciProfileUrl; ?>" style="font-weight:700; font-size:14px; color:var(--text-1); text-decoration:none; transition:color 0.15s;" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-1)'">
                <?php echo escape($ci['username'] ?? 'Kullanıcı'); ?>
            </a>
            <span style="color:var(--text-3); font-size:11px;">check-in yaptı:</span>
            <a href="<?php echo $ciVenueUrl; ?>" style="font-size:14px; font-weight:600; text-decoration:none; color:<?php echo $ciMeta['color']; ?>; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; display:inline-block; vertical-align:bottom;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                <?php echo escape($ci['venue_name'] ?? ''); ?>
            </a>
        </div>
        <div style="display:flex; align-items:center; gap:6px; margin-top:4px; font-size:11px; color:var(--text-3);">
            <span><?php echo $ciTimeAgo; ?></span>
            <?php if ($ciNote): ?>
            <span style="opacity:0.4;">·</span>
            <span style="font-style:italic; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">&ldquo;<?php echo escape($ciNote); ?>&rdquo;</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Check-in Badge -->
    <div style="display:flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:4px 8px; border-radius:12px; flex-shrink:0; border:1px solid <?php echo $ciMeta['color']; ?>40; color:<?php echo $ciMeta['color']; ?>; background:<?php echo $ciMeta['color']; ?>18;">
        <span class="material-symbols-outlined" style="font-size:12px; font-variation-settings:'FILL' 1;">verified</span>
        <span>Check-in</span>
    </div>

</div>
