<?php
/**
 * Post Card Partial — Swarm Bento Design (Two-Column Layout)
 * $post dizisi dışarıdan gelir
 */
if (!isset($post)) return;

// Dynamic category icons mapping
$categoryIcons = [
    'restoran'  => 'restaurant',
    'kafe'      => 'coffee',
    'bar'       => 'local_bar',
    'otel'      => 'hotel',
    'alisveris' => 'shopping_bag',
    'eglence'   => 'theater_comedy',
    'spor'      => 'fitness_center',
    'saglik'    => 'spa',
    'kultur'    => 'museum',
    'diger'     => 'explore',
];
$categoryIcon = $categoryIcons[$post['venue_category']] ?? 'explore';

// Dynamic action taglines mapping
$actionText = "'da check-in yaptı";
if (!empty($post['is_mystery_shopper'])) {
    $actionText = " mekanını denetledi";
} elseif (!empty($post['image'])) {
    $actionText = " için bir fotoğraf paylaştı";
} elseif (!empty($post['note'])) {
    $noteLower = mb_strtolower($post['note']);
    $recommendKeywords = ['öner', 'tavsiye', 'harika', 'mükemmel', 'tavsiye ederim', 'on numara', '10/10', 'muazzam', 'enfes', '10 numara', 'harikaydı', 'öneririm'];
    $isRecommendation = false;
    foreach ($recommendKeywords as $keyword) {
        if (strpos($noteLower, $keyword) !== false) {
            $isRecommendation = true;
            break;
        }
    }
    if ($isRecommendation) {
        $actionText = " mekanını önerdi";
    }
}
?>
<article class="post-card bg-surface-container-low rounded-xl border border-outline-variant/10 p-md hover:bg-surface-container transition-colors group" id="post-<?php echo $post['id']; ?>">
    
    <?php if (!empty($post['reposted_by'])): ?>
    <div class="flex items-center gap-2 text-[#ffb97c]/80 font-mono text-[10px] mb-3 tracking-widest uppercase">
        <span class="material-symbols-outlined text-[14px]">settings_input_antenna</span>
        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?>" class="hover:text-white transition-colors">@<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?></a> TELSİZ YAYINI
    </div>
    <?php endif; ?>

    <!-- Header Row: User Info & Tagline -->
    <div class="flex justify-between items-start mb-md">
        <div class="flex gap-md min-w-0">
            <!-- Avatar -->
            <div class="relative flex-shrink-0">
                <?php 
                $avatarUrl = !empty($post['is_mystery_shopper']) 
                    ? 'https://ui-avatars.com/api/?name=GM&background=6900b3&color=ffffff' 
                    : safeAvatarUrl($post['avatar'] ?? null, $post['username'] ?? 'User');
                ?>
                <img alt="<?php echo escape($post['username']); ?>" class="w-10 h-10 rounded-full" src="<?php echo $avatarUrl; ?>" />
                <?php if (empty($post['is_mystery_shopper']) && !empty($post['is_premium'])): ?>
                    <div class="absolute -bottom-1 -right-1 bg-primary text-[8px] font-bold px-1 rounded-full text-on-primary">VIP</div>
                <?php endif; ?>
            </div>
            
            <div class="min-w-0">
                <!-- User Meta Row -->
                <div class="flex items-center gap-xs">
                    <?php if (!empty($post['is_mystery_shopper'])): ?>
                        <span class="font-bold">Gizli Müşteri</span>
                        <span class="material-symbols-outlined text-purple-400 text-sm" style="font-variation-settings: 'FILL' 1;" title="Gizli Müşteri">visibility_off</span>
                    <?php else: ?>
                        <span class="font-bold"><?php echo escape($post['username']); ?></span>
                        <?php if (!empty($post['is_premium'])): ?>
                            <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings: 'FILL' 1;" title="Premium Üye">workspace_premium</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="text-on-surface-variant text-label-md">• <?php echo timeAgo($post['created_at']); ?></span>
                </div>

                <!-- Tagline -->
                <p class="text-body-md mt-1">
                    <span class="text-primary font-bold"><?php echo escape($post['venue_name']); ?></span><?php echo $actionText; ?>
                </p>

                <!-- Review / Note -->
                <?php if (!empty($post['note'])): ?>
                <p class="text-on-surface-variant italic mt-2">
                    “<?php echo linkify(parseMentions($post['note'])); ?>”
                </p>
                <?php endif; ?>

                <!-- Category & Location Pills -->
                <div class="flex gap-xs mt-3">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container-highest text-[10px] text-on-surface-variant border border-outline-variant/30">
                        <span class="material-symbols-outlined text-xs">location_on</span> 
                        <?php echo escape($post['venue_address'] ?: 'Los Santos'); ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container-highest text-[10px] text-on-surface-variant border border-outline-variant/30">
                        <span class="material-symbols-outlined text-xs"><?php echo $categoryIcon; ?></span>
                        <?php echo escape(VenueModel::categories()[$post['venue_category']] ?? ($post['venue_category'] ?? 'KEŞİF NOKTASI')); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Options / Delete -->
        <div class="flex items-center gap-1 shrink-0">
            <?php if (!empty($post['is_own'])): ?>
                <button onclick="App.deletePost(this, <?php echo $post['id']; ?>)" class="text-on-surface-variant hover:text-red-400 transition-colors p-1 rounded-lg hover:bg-surface-container-highest" title="Sil">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                </button>
            <?php endif; ?>
            <button class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">more_horiz</span></button>
        </div>
    </div>

    <!-- Attached Image -->
    <?php if (!empty($post['image'])): ?>
    <div class="relative rounded-xl overflow-hidden aspect-video bg-surface-container-highest">
        <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?php echo uploadUrl('posts', $post['image']); ?>" loading="lazy"/>
    </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mt-md">
        <div class="flex gap-lg">
            <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)" class="flex items-center gap-xs text-on-surface-variant hover:text-secondary transition-colors <?php echo !empty($post['viewer_liked']) ? 'liked text-secondary' : ''; ?>">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' <?php echo !empty($post['viewer_liked']) ? '1' : '0'; ?>;">favorite</span>
                <span class="text-label-md font-bold action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>
            </button>
            <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)" class="flex items-center gap-xs text-on-surface-variant hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-xl">chat_bubble</span>
                <span class="text-label-md font-bold action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
            </button>
        </div>
        <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>'); App.flash('Link kopyalandı!', 'success');" class="flex items-center gap-xs text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-xl">bookmark</span>
            <span class="text-label-md font-bold">Kaydet</span>
        </button>
    </div>

    <!-- Inline Comments Section -->
    <div class="post-comments-section mt-3 pt-3 border-t border-dashed border-white/10" id="comments-section-<?php echo $post['id']; ?>" style="display:none;">
        <div class="radio-log-container mb-3 max-h-64 overflow-y-auto pr-2" id="comments-list-<?php echo $post['id']; ?>">
            <div class="text-center text-slate-500 text-xs py-2 font-mono">Yükleniyor...</div>
        </div>
        <?php if (Auth::check()): ?>
        <form class="flex gap-2 items-center" onsubmit="App.submitInlineComment(this, <?php echo $post['id']; ?>); return false;">
            <input type="text" class="comment-input-inline bg-surface-container border-none rounded-lg pl-3 pr-10 py-1.5 text-body-md w-full focus:ring-1 focus:ring-primary transition-all" placeholder="Yorum yaz..." maxlength="500" required>
            <button type="submit" class="comment-send-btn flex items-center justify-center w-8 h-8 rounded-lg bg-primary/20 text-primary hover:bg-primary hover:text-white transition-all flex-shrink-0">
                <span class="material-symbols-outlined text-[16px]">send</span>
            </button>
        </form>
        <?php endif; ?>
    </div>
</article>
