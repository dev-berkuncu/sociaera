<?php
/**
 * Post Card Partial — Feed'de tekrar kullanılır
 * $post dizisi dışarıdan gelir
 */
if (!isset($post)) return;
?>
<div class="post-card" id="post-<?php echo $post['id']; ?>">
    <div class="post-header">
        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['tag'] ?: $post['username']); ?>" class="post-avatar">
            <?php echo avatarHtml($post['avatar'] ?? null, $post['username'], '44'); ?>
        </a>
        <div class="post-user-info">
            <div>
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['tag'] ?: $post['username']); ?>" class="post-username"><?php echo escape($post['username']); ?></a>
                <?php if (!empty($post['tag'])): ?>
                    <span class="post-tag">@<?php echo escape($post['tag']); ?></span>
                <?php endif; ?>
                <span class="post-time">· <?php echo timeAgo($post['created_at']); ?></span>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $post['venue_id']; ?>" class="post-venue-link">
                    <i class="bi bi-geo-alt-fill"></i> <?php echo escape($post['venue_name']); ?>
                </a>
                <?php if (!empty($post['is_premium'])): ?>
                    <span class="post-badge"><i class="bi bi-gem"></i> Premium</span>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($post['is_own'])): ?>
        <div class="dropdown">
            <button class="post-menu-btn" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="App.deletePost(this, <?php echo $post['id']; ?>)"><i class="bi bi-trash3"></i> Sil</a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($post['note'])): ?>
    <div class="post-body">
        <p class="post-text"><?php echo linkify(parseMentions($post['note'])); ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($post['image'])): ?>
        <img src="<?php echo uploadUrl('posts', $post['image']); ?>" alt="Post" class="post-image" loading="lazy">
    <?php endif; ?>

    <div class="post-actions">
        <a href="<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>" class="post-action" title="Yorumlar">
            <i class="bi bi-chat"></i>
            <span class="action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
        </a>
        <button class="post-action <?php echo !empty($post['viewer_reposted']) ? 'reposted' : ''; ?>" onclick="App.toggleRepost(this, <?php echo $post['id']; ?>)" title="Paylaş">
            <i class="bi bi-arrow-repeat"></i>
            <span class="action-count"><?php echo (int)($post['repost_count'] ?? 0); ?></span>
        </button>
        <button class="post-action <?php echo !empty($post['viewer_liked']) ? 'liked' : ''; ?>" onclick="App.toggleLike(this, <?php echo $post['id']; ?>)" title="Beğen">
            <i class="bi bi-heart<?php echo !empty($post['viewer_liked']) ? '-fill' : ''; ?>"></i>
            <span class="action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>
        </button>
        <button class="post-action" title="Paylaş" onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>'); App.flash('Link kopyalandı!', 'success');">
            <i class="bi bi-share"></i>
        </button>
    </div>
</div>
