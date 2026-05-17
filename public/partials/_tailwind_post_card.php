<?php
/**
 * Post Card Partial — Tailwind Design
 * $post dizisi dışarıdan gelir
 */
if (!isset($post)) return;
?>
<article class="post-card bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] flex flex-col gap-4" id="post-<?php echo $post['id']; ?>">
    <?php if (!empty($post['reposted_by'])): ?>
    <div class="flex items-center gap-2 text-slate-400 font-label-sm text-label-sm mb-[-8px]">
        <span class="material-symbols-outlined text-[16px]">repeat</span>
        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?>" class="hover:text-white transition-colors"><?php echo escape($post['reposted_by']); ?></a> paylaştı
    </div>
    <?php endif; ?>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <?php $pAvatar = safeAvatarUrl($post['avatar'] ?? null, $post['username']); ?>
            <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['tag'] ?: $post['username']); ?>">
                <img alt="User avatar" class="w-10 h-10 rounded-full object-cover border border-white/10" src="<?php echo $pAvatar; ?>" width="40" height="40" loading="lazy"/>
            </a>
            <div>
                <div class="font-label-md text-label-md text-on-surface flex items-center gap-2">
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['tag'] ?: $post['username']); ?>" class="hover:text-primary-container transition-colors font-bold"><?php echo escape($post['username']); ?></a>
                    <?php if (!empty($post['is_premium'])): ?>
                        <?php
                        $userBadge = $post['badge'] ?? null;
                        $badges = UserModel::availableBadges();
                        if ($userBadge && isset($badges[$userBadge])):
                            $b = $badges[$userBadge];
                        ?>
                        <span class="material-symbols-outlined text-[16px]" style="color: <?php echo $b['color']; ?>" title="Premium — <?php echo $b['label']; ?>" data-weight="fill"><?php echo $b['icon']; ?></span>
                        <?php else: ?>
                        <span class="material-symbols-outlined text-[14px] text-[#7bd0ff]" title="Premium" data-weight="fill">diamond</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($post['tag'])): ?>
                        <span class="text-slate-500 font-normal">@<?php echo escape($post['tag']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="font-label-sm text-label-sm text-slate-400"><?php echo timeAgo($post['created_at']); ?></div>
            </div>
        </div>
        <?php if (!empty($post['is_own'])): ?>
        <button onclick="App.deletePost(this, <?php echo $post['id']; ?>)" class="text-slate-400 hover:text-error transition-colors"><span class="material-symbols-outlined">delete</span></button>
        <?php endif; ?>
    </div>

    <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $post['venue_id']; ?>" class="flex items-center gap-2 w-fit bg-white/5 hover:bg-white/10 transition-colors border border-white/10 px-3 py-1.5 rounded-full">
        <span class="material-symbols-outlined text-[16px] text-primary-container">location_on</span>
        <span class="font-label-sm text-label-sm text-slate-300"><?php echo escape($post['venue_name']); ?></span>
    </a>

    <?php if (!empty($post['note'])): ?>
    <p class="font-body-md text-body-md text-on-surface"><?php echo linkify(parseMentions($post['note'])); ?></p>
    <?php endif; ?>

    <?php if (!empty($post['image'])): ?>
    <div class="rounded-xl overflow-hidden border border-white/10 shadow-lg mt-2 max-h-[500px] bg-black/10">
        <img alt="Venue photo" class="block w-full max-w-full h-auto max-h-[500px] object-contain" src="<?php echo uploadUrl('posts', $post['image']); ?>" width="640" height="400" loading="lazy"/>
    </div>
    <?php endif; ?>

    <div class="flex items-center gap-6 mt-2 pt-4 border-t border-white/5 text-slate-400">
        <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)" class="flex items-center gap-2 hover:text-primary-container transition-colors <?php echo !empty($post['viewer_liked']) ? 'liked text-primary-container' : ''; ?>">
            <span class="material-symbols-outlined"><?php echo !empty($post['viewer_liked']) ? 'favorite' : 'favorite'; ?></span> 
            <span class="font-label-sm text-label-sm action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>
        </button>
        <button onclick="App.toggleRepost(this, <?php echo $post['id']; ?>)" class="flex items-center gap-2 hover:text-primary-container transition-colors <?php echo !empty($post['viewer_reposted']) ? 'reposted text-primary-container' : ''; ?>">
            <span class="material-symbols-outlined">repeat</span> 
            <span class="font-label-sm text-label-sm action-count"><?php echo (int)($post['repost_count'] ?? 0); ?></span>
        </button>
        <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)" class="flex items-center gap-2 hover:text-on-surface transition-colors">
            <span class="material-symbols-outlined">chat_bubble</span> 
            <span class="font-label-sm text-label-sm action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
        </button>
        <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>'); App.flash('Link kopyalandı!', 'success');" class="flex items-center gap-2 hover:text-on-surface transition-colors ml-auto">
            <span class="material-symbols-outlined">share</span>
        </button>
    </div>
    
    <!-- Inline Comments Section -->
    <div class="post-comments-section mt-4 pt-4 border-t border-white/5" id="comments-section-<?php echo $post['id']; ?>" style="display:none;">
        <div class="post-comments-list space-y-3 mb-3 max-h-64 overflow-y-auto pr-2" id="comments-list-<?php echo $post['id']; ?>">
            <div class="text-center text-slate-500 text-sm">Yorumlar yükleniyor...</div>
        </div>
        <?php if (Auth::check()): ?>
        <form class="flex gap-2 items-center" onsubmit="App.submitInlineComment(this, <?php echo $post['id']; ?>); return false;">
            <input type="text" class="comment-input-inline w-full bg-background border border-white/10 rounded-full px-4 py-2 text-on-surface text-sm focus:outline-none focus:border-primary-container" placeholder="Yorumunu yaz..." maxlength="500" required>
            <button type="submit" class="comment-send-btn flex items-center justify-center w-10 h-10 rounded-full bg-primary-container text-white hover:bg-primary-container/90 transition-colors flex-shrink-0">
                <span class="material-symbols-outlined text-[18px]">send</span>
            </button>
        </form>
        <?php endif; ?>
    </div>
</article>
