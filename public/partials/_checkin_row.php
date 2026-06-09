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
<div class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-900/40 border-b border-white/5 last:border-b-0 transition-colors group" id="ci-<?php echo (int)$ci['id']; ?>">

    <!-- Avatar + kategori pin -->
    <a href="<?php echo $ciProfileUrl; ?>" class="relative flex-shrink-0">
        <img src="<?php echo $ciAvatarUrl; ?>" alt="<?php echo escape($ci['username'] ?? ''); ?>"
             class="w-10 h-10 rounded-full object-cover border border-white/10 group-hover:border-cyber-orange/50 transition-colors" width="40" height="40">
        <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-[#0c0d10] flex items-center justify-center"
             style="background:<?php echo $ciMeta['color']; ?>;">
            <span class="material-symbols-outlined text-white" style="font-size:9px;font-variation-settings:'FILL' 1;"><?php echo $ciMeta['icon']; ?></span>
        </div>
    </a>

    <!-- İçerik -->
    <div class="flex-grow min-w-0 font-mono text-xs">
        <div class="flex items-baseline gap-1.5 flex-wrap">
            <a href="<?php echo $ciProfileUrl; ?>" class="font-bold text-cyber-orange hover:text-white transition-colors">
                <?php echo escape($ci['username'] ?? 'Kullanıcı'); ?>
            </a>
            <span class="text-slate-400 text-[10px]">check-in yaptı:</span>
            <a href="<?php echo $ciVenueUrl; ?>" class="font-bold hover:underline truncate max-w-[160px]" style="color:<?php echo $ciMeta['color']; ?>">
                <?php echo escape($ci['venue_name'] ?? ''); ?>
            </a>
        </div>
        <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-500">
            <span><?php echo $ciTimeAgo; ?></span>
            <?php if ($ciNote): ?>
            <span class="opacity-40">·</span>
            <span class="italic truncate text-slate-400">&ldquo;<?php echo escape($ciNote); ?>&rdquo;</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Check-in Badge -->
    <div class="flex items-center gap-1 text-[9px] font-mono font-bold px-2 py-0.5 rounded border border-cyber-orange/20 text-cyber-orange bg-cyber-orange/5">
        <span class="material-symbols-outlined text-[10px]" style="font-variation-settings:'FILL' 1;">verified</span>
        <span class="hidden sm:inline">CHECKIN</span>
    </div>

</div>
