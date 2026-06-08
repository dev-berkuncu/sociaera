<?php
/**
 * Post Card Partial — Swarm Bento Design
 * $post dizisi dışarıdan gelir
 */
if (!isset($post)) return;
?>
<article class="post-card bg-surface-container-low rounded-xl border border-outline-variant/10 p-4 hover:bg-surface-container transition-all duration-300 group relative flex flex-col gap-4" id="post-<?php echo $post['id']; ?>">
    
    <?php if (!empty($post['reposted_by'])): ?>
    <div class="flex items-center gap-2 text-[#ffb59d]/80 font-mono text-[10px] mb-[-4px] tracking-widest uppercase">
        <span class="material-symbols-outlined text-[14px]">settings_input_antenna</span>
        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?>" class="hover:text-white transition-colors">@<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?></a> TELSİZ YAYINI
    </div>
    <?php endif; ?>

    <!-- Main Header & Avatar -->
    <div class="flex justify-between items-start">
        <div class="flex gap-3">
            <!-- Avatar -->
            <?php 
            $avatarUrl = !empty($post['is_mystery_shopper']) 
                ? 'https://ui-avatars.com/api/?name=GM&background=6900b3&color=ffffff' 
                : safeAvatarUrl($post['avatar'] ?? null, $post['username'] ?? 'User');
            ?>
            <div class="relative flex-shrink-0">
                <img alt="<?php echo escape($post['username']); ?>" class="w-10 h-10 rounded-full object-cover border border-outline-variant/30" src="<?php echo $avatarUrl; ?>" />
                <?php if (empty($post['is_mystery_shopper']) && !empty($post['is_premium'])): ?>
                    <div class="absolute -bottom-1 -right-1 bg-primary text-[8px] font-bold px-1 rounded-full text-on-primary border border-surface-container-low" title="VIP Seviyesi">VIP</div>
                <?php endif; ?>
            </div>

            <div>
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
                            <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings: 'FILL' 1;" title="VIP">workspace_premium</span>
                        <?php endif; ?>
                        <span class="text-on-surface-variant/70 text-xs">@<?php echo escape($post['tag'] ?: $post['username']); ?></span>
                    <?php endif; ?>
                    <span class="text-on-surface-variant text-[11px]">• <?php echo timeAgo($post['created_at']); ?></span>
                </div>

                <!-- Action tagline -->
                <p class="text-body-md mt-1 text-on-surface/90">
                    <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $post['venue_id']; ?>" class="text-primary font-bold hover:underline">
                        <?php echo escape($post['venue_name']); ?>
                    </a>'da check-in yaptı
                </p>

                <!-- Review / Note -->
                <?php if (!empty($post['note'])): ?>
                <p class="text-on-surface-variant italic mt-2 text-sm leading-relaxed">
                    “<?php echo linkify(parseMentions($post['note'])); ?>”
                </p>
                <?php endif; ?>

                <!-- Category & Location Pills -->
                <div class="flex flex-wrap gap-1.5 mt-3">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-highest text-[10px] text-on-surface-variant border border-outline-variant/30">
                        <span class="material-symbols-outlined text-xs">location_on</span> 
                        <?php echo escape($post['venue_address'] ?: 'Los Santos'); ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-highest text-[10px] text-on-surface-variant border border-outline-variant/30 font-semibold uppercase">
                        <?php echo escape(VenueModel::categories()[$post['venue_category']] ?? ($post['venue_category'] ?? 'KEŞİF NOKTASI')); ?>
                    </span>
                    
                    <?php if (!empty($post['is_mystery_shopper'])): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-950/50 text-[10px] text-purple-300 border border-purple-500/30 font-bold tracking-wider">
                            GİZLİ MÜŞTERİ DENETİMİ
                        </span>
                    <?php elseif (!empty($post['is_premium'])): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-orange-950/50 text-[10px] text-orange-300 border border-orange-500/30 font-bold tracking-wider">
                            VIP CHECK-IN
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Side Options (Delete button) -->
        <div class="flex items-center gap-1 shrink-0">
            <?php if (!empty($post['is_own'])): ?>
                <button onclick="App.deletePost(this, <?php echo $post['id']; ?>)" class="text-on-surface-variant hover:text-red-400 transition-colors p-1.5 rounded-lg hover:bg-surface-container-highest" title="Sil">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attached Image -->
    <?php if (!empty($post['image'])): ?>
    <div class="relative rounded-xl overflow-hidden aspect-video bg-surface-container-highest border border-outline-variant/10 shadow-md">
        <img alt="Check-in Fotoğrafı" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?php echo uploadUrl('posts', $post['image']); ?>" loading="lazy"/>
    </div>
    <?php endif; ?>

    <!-- Actions Footer -->
    <div class="flex justify-between items-center mt-2 pt-3 border-t border-outline-variant/10 text-on-surface-variant">
        <div class="flex gap-4 flex-wrap">
            <!-- Saygı (Like) -->
            <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)" class="flex items-center gap-1 hover:text-secondary transition-colors <?php echo !empty($post['viewer_liked']) ? 'liked text-secondary' : ''; ?>">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' <?php echo !empty($post['viewer_liked']) ? '1' : '0'; ?>;">favorite</span>
                <span class="text-label-md font-bold action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>
            </button>
            
            <!-- Telsiz Logu (Comments) -->
            <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)" class="flex items-center gap-1 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-xl">chat_bubble</span>
                <span class="text-label-md font-bold action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
            </button>

            <!-- Telsizle (Repost) -->
            <button onclick="App.toggleRepost(this, <?php echo $post['id']; ?>)" class="flex items-center gap-1 hover:text-primary transition-colors <?php echo !empty($post['viewer_reposted']) ? 'reposted text-primary' : ''; ?>">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' <?php echo !empty($post['viewer_reposted']) ? '1' : '0'; ?>;">settings_input_antenna</span>
                <span class="text-label-md font-bold action-count"><?php echo (int)($post['repost_count'] ?? 0); ?></span>
            </button>

            <!-- Bahşiş (Tip) -->
            <button onclick="App.flash('Bahşiş özelliği yakında aktif edilecek! 💸', 'info')" class="flex items-center gap-1 hover:text-green-400 transition-colors opacity-70 hover:opacity-100">
                <span class="material-symbols-outlined text-xl">payments</span>
                <span class="text-label-md font-bold">Bahşiş</span>
            </button>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>'); App.flash('Link kopyalandı!', 'success');" class="flex items-center gap-1 hover:text-primary transition-colors" title="Paylaş">
                <span class="material-symbols-outlined text-xl">share</span>
                <span class="hidden sm:inline text-label-md font-bold">Paylaş</span>
            </button>
            <?php if (Auth::check() && empty($post['is_own'])): ?>
            <button onclick="App.openReportModal('checkin', <?php echo $post['id']; ?>)" class="flex items-center gap-1 hover:text-red-400 transition-colors" title="Raporla">
                <span class="material-symbols-outlined text-xl">flag</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Inline Comments Section (Radio log) -->
    <div class="post-comments-section mt-4 pt-3 border-t border-dashed border-white/10" id="comments-section-<?php echo $post['id']; ?>" style="display:none;">
        <div class="radio-log-container mb-3 max-h-64 overflow-y-auto pr-2" id="comments-list-<?php echo $post['id']; ?>">
            <div class="text-center text-slate-500 text-xs py-2 font-mono">Telsiz kayıtları alınıyor...</div>
        </div>
        <?php if (Auth::check()): ?>
        <form class="flex gap-2 items-center" onsubmit="App.submitInlineComment(this, <?php echo $post['id']; ?>); return false;">
            <span class="text-[#ff6b35] font-mono text-xs flex-shrink-0">&gt;</span>
            <input type="text" class="comment-input-inline radio-input-line w-full text-xs focus:outline-none" placeholder="Telsiz anonsu geç..." maxlength="500" required>
            <button type="submit" class="comment-send-btn flex items-center justify-center w-8 h-8 rounded-full bg-[#ff6b35]/20 text-[#ff6b35] hover:bg-[#ff6b35] hover:text-white transition-all flex-shrink-0">
                <span class="material-symbols-outlined text-[14px]">send</span>
            </button>
        </form>
        <?php endif; ?>
    </div>
</article>
