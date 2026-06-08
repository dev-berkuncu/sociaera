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
<article class="post-card bg-surface-container-low rounded-xl border border-outline-variant/10 p-4 hover:bg-surface-container transition-colors duration-300 group relative" id="post-<?php echo $post['id']; ?>">
    
    <?php if (!empty($post['reposted_by'])): ?>
    <div class="flex items-center gap-2 text-[#ffb97c]/80 font-mono text-[10px] mb-3 tracking-widest uppercase">
        <span class="material-symbols-outlined text-[14px]">settings_input_antenna</span>
        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?>" class="hover:text-white transition-colors">@<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?></a> TELSİZ YAYINI
    </div>
    <?php endif; ?>

    <!-- Header Row: User Info & Tagline -->
    <div class="flex justify-between items-start mb-3">
        <div class="flex gap-3 min-w-0">
            <!-- Avatar -->
            <div class="relative flex-shrink-0">
                <?php 
                $avatarUrl = !empty($post['is_mystery_shopper']) 
                    ? 'https://ui-avatars.com/api/?name=GM&background=6900b3&color=ffffff' 
                    : safeAvatarUrl($post['avatar'] ?? null, $post['username'] ?? 'User');
                ?>
                <img alt="<?php echo escape($post['username']); ?>" class="w-10 h-10 rounded-full object-cover border border-outline-variant/30" src="<?php echo $avatarUrl; ?>" />
                <?php if (empty($post['is_mystery_shopper']) && !empty($post['is_premium'])): ?>
                    <div class="absolute -bottom-1 -right-1 bg-primary text-[8px] font-bold px-1 rounded-full text-on-primary border border-surface-container-low" title="VIP">VIP</div>
                <?php endif; ?>
            </div>
            
            <div class="min-w-0">
                <!-- User Meta Row -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <?php if (!empty($post['is_mystery_shopper'])): ?>
                        <span class="font-bold text-on-surface text-sm sm:text-base">Gizli Müşteri</span>
                        <span class="material-symbols-outlined text-purple-400 text-sm" style="font-variation-settings: 'FILL' 1;" title="Gizli Müşteri">visibility_off</span>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['tag'] ?: $post['username']); ?>" class="font-bold text-on-surface hover:text-primary transition-colors text-sm sm:text-base">
                            <?php echo escape($post['username']); ?>
                        </a>
                        <?php if (!empty($post['is_premium'])): ?>
                            <span class="material-symbols-outlined text-primary text-sm align-middle" style="font-variation-settings: 'FILL' 1;" title="Premium Üye">workspace_premium</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="text-on-surface-variant text-xs">• <?php echo timeAgo($post['created_at']); ?></span>
                </div>

                <!-- Tagline -->
                <p class="text-body-md mt-1 text-on-surface/90">
                    <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $post['venue_id']; ?>" class="text-primary font-bold hover:underline">
                        <?php echo escape($post['venue_name']); ?>
                    </a><?php echo $actionText; ?>
                </p>

                <!-- Review / Note -->
                <?php if (!empty($post['note'])): ?>
                <p class="text-on-surface-variant italic text-sm leading-relaxed mt-2">
                    “<?php echo linkify(parseMentions($post['note'])); ?>”
                </p>
                <?php endif; ?>

                <!-- Category & Location Pills -->
                <div class="flex flex-wrap gap-1.5 mt-3">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container-highest text-[10px] text-on-surface-variant border border-outline-variant/30">
                        <span class="material-symbols-outlined text-xs">location_on</span> 
                        <?php echo escape($post['venue_address'] ?: 'Los Santos'); ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container-highest text-[10px] text-on-surface-variant border border-outline-variant/30 font-semibold uppercase">
                        <span class="material-symbols-outlined text-xs"><?php echo $categoryIcon; ?></span>
                        <?php echo escape(VenueModel::categories()[$post['venue_category']] ?? ($post['venue_category'] ?? 'KEŞİF NOKTASI')); ?>
                    </span>
                    
                    <?php if (!empty($post['is_mystery_shopper'])): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-950/50 text-[10px] text-purple-300 border border-purple-500/30 font-bold tracking-wider">
                            GİZLİ MÜŞTERİ
                        </span>
                    <?php elseif (!empty($post['is_premium'])): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-950/50 text-[10px] text-orange-300 border border-orange-500/30 font-bold tracking-wider">
                            VIP
                        </span>
                    <?php endif; ?>
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
            <button class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg hover:bg-surface-container-highest">
                <span class="material-symbols-outlined text-[20px]">more_horiz</span>
            </button>
        </div>
    </div>

    <!-- Attached Image -->
    <?php if (!empty($post['image'])): ?>
    <div class="relative rounded-xl overflow-hidden aspect-video bg-surface-container-highest mb-3 border border-outline-variant/10">
        <img alt="Check-in Fotoğrafı" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?php echo uploadUrl('posts', $post['image']); ?>" loading="lazy"/>
    </div>
    <?php endif; ?>

    <!-- Actions Footer -->
    <div class="flex justify-between items-center mt-3 text-on-surface-variant text-sm font-medium">
        <div class="flex gap-lg">
            <!-- Beğeni (Like) -->
            <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)" class="flex items-center gap-xs hover:text-secondary transition-colors <?php echo !empty($post['viewer_liked']) ? 'liked text-secondary' : ''; ?>">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' <?php echo !empty($post['viewer_liked']) ? '1' : '0'; ?>;">favorite</span>
                <span class="text-label-md font-bold action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>
            </button>
            
            <!-- Yorumlar (Comments) -->
            <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)" class="flex items-center gap-xs hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-xl">chat_bubble</span>
                <span class="text-label-md font-bold action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
            </button>

            <!-- Telsizle (Repost) -->
            <button onclick="App.toggleRepost(this, <?php echo $post['id']; ?>)" class="flex items-center gap-xs hover:text-primary transition-colors <?php echo !empty($post['viewer_reposted']) ? 'reposted text-primary' : ''; ?>" title="Telsizle">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' <?php echo !empty($post['viewer_reposted']) ? '1' : '0'; ?>;">settings_input_antenna</span>
                <span class="text-label-md font-bold action-count"><?php echo (int)($post['repost_count'] ?? 0); ?></span>
            </button>

            <!-- Bahşiş (Tip) -->
            <button onclick="App.flash('Bahşiş özelliği yakında aktif edilecek! 💸', 'info')" class="flex items-center gap-xs hover:text-green-400 transition-colors opacity-70 hover:opacity-100" title="Bahşiş">
                <span class="material-symbols-outlined text-xl">payments</span>
                <span class="text-label-md font-bold hidden sm:inline">Bahşiş</span>
            </button>
        </div>

        <!-- Bookmark/Save Button -->
        <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>'); App.flash('Link kopyalandı!', 'success');" class="flex items-center gap-xs hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-xl">bookmark</span>
            <span class="text-label-md font-bold">Kaydet</span>
        </button>
    </div>

    <!-- Inline Comments Section -->
    <div class="post-comments-section mt-3 pt-3 border-t border-dashed border-white/10" id="comments-section-<?php echo $post['id']; ?>" style="display:none;">
        <div class="radio-log-container mb-3 max-h-64 overflow-y-auto pr-2" id="comments-list-<?php echo $post['id']; ?>">
            <div class="text-center text-slate-500 text-xs py-2 font-mono">Telsiz kayıtları alınıyor...</div>
        </div>
        <?php if (Auth::check()): ?>
        <form class="flex gap-2 items-center" onsubmit="App.submitInlineComment(this, <?php echo $post['id']; ?>); return false;">
            <span class="text-[#ff9100] font-mono text-xs flex-shrink-0">&gt;</span>
            <input type="text" class="comment-input-inline radio-input-line w-full text-xs focus:outline-none" placeholder="Telsiz anonsu geç..." maxlength="500" required>
            <button type="submit" class="comment-send-btn flex items-center justify-center w-8 h-8 rounded-full bg-[#ff9100]/20 text-[#ff9100] hover:bg-[#ff9100] hover:text-white transition-all flex-shrink-0">
                <span class="material-symbols-outlined text-[14px]">send</span>
            </button>
        </form>
        <?php endif; ?>
    </div>
</article>
