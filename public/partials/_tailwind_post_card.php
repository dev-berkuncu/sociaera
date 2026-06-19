<?php
/**
 * Post Card Partial — Swarm Edition (Check-in odaklı kart)
 * $post dizisi dışarıdan gelir
 */
if (!isset($post)) return;

$catIcons = [
    'restoran'  => 'restaurant',   'kafe'      => 'local_cafe',
    'bar'       => 'sports_bar',   'otel'      => 'hotel',
    'alisveris' => 'shopping_bag', 'eglence'   => 'theaters',
    'spor'      => 'fitness_center','saglik'   => 'spa',
    'kultur'    => 'museum',       'diger'     => 'place',
];
$catColors = [
    'restoran'  => '#F06D1F', 'kafe'      => '#92400E',
    'bar'       => '#7C3AED', 'otel'      => '#4F46E5',
    'alisveris' => '#2563EB', 'eglence'   => '#D97706',
    'spor'      => '#DC2626', 'saglik'    => '#DB2777',
    'kultur'    => '#0D9488', 'diger'     => '#6B7280',
];
$catKey   = $post['venue_category'] ?? 'diger';
$catIcon  = $catIcons[$catKey]  ?? 'place';
$catColor = $catColors[$catKey] ?? '#6B7280';
$catLabel = VenueModel::categories()[$catKey] ?? ucfirst($catKey);

$cardAvatar = !empty($post['is_mystery_shopper'])
    ? 'https://ui-avatars.com/api/?name=GM&background=7a1d47&color=ffffff'
    : safeAvatarUrl($post['avatar'] ?? null, $post['username'] ?? 'User');
?>
<article class="checkin-card" id="post-<?php echo $post['id']; ?>">

    <?php if (!empty($post['reposted_by'])): ?>
    <div style="padding:8px 14px 0;font-size:11px;font-weight:700;color:var(--text-3);display:flex;align-items:center;gap:5px;">
        <span class="material-symbols-outlined" style="font-size:14px;">repeat</span>
        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?>"
           style="color:var(--color-primary);text-decoration:none;">@<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?></a>
        yeniden paylaştı
    </div>
    <?php endif; ?>

    <!-- Header: Avatar + Kim nereye gitti -->
    <div class="checkin-card-header">
        <!-- Avatar -->
        <div class="checkin-card-avatar relative inline-block" data-user-id="<?php echo $post['user_id']; ?>">
            <img src="<?php echo $cardAvatar; ?>" alt="<?php echo escape($post['username']); ?>" width="38" height="38" style="border-radius:50%;">
            <span class="online-indicator hidden absolute bottom-0 left-0 w-3 h-3 rounded-full border-2 border-white z-10 transition-colors" style="background-color: #22c55e;"></span>
            <div class="checkin-card-cat-dot" style="background:<?php echo $catColor; ?>;">
                <span class="material-symbols-outlined" style="font-size:7px;color:#fff;font-variation-settings:'FILL' 1;"><?php echo $catIcon; ?></span>
            </div>
        </div>

        <!-- Metin -->
        <div class="checkin-card-meta">
            <div class="checkin-card-who">
                <?php if (!empty($post['is_mystery_shopper'])): ?>
                    <strong>Gizli Müşteri</strong>
                    <span class="material-symbols-outlined" style="font-size:13px;color:#7A1D47;vertical-align:middle;font-variation-settings:'FILL' 1;">visibility_off</span>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo urlencode($post['tag'] ?: $post['username']); ?>"
                       style="font-weight:700;color:var(--text-1);text-decoration:none;"><?php echo escape($post['username']); ?></a>
                    <?php if (!empty($post['is_premium'])): ?>
                    <span class="material-symbols-outlined" style="font-size:13px;color:#4F46E5;vertical-align:middle;font-variation-settings:'FILL' 1;">verified</span>
                    <?php endif; ?>
                    <span style="color:var(--text-3);"> check-in yaptı</span>
                <?php endif; ?>
            </div>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)$post['venue_id']; ?>"
               class="checkin-card-venue" style="color:<?php echo $catColor; ?>;text-decoration:none;">
                <?php echo escape($post['venue_name']); ?>
            </a>
        </div>

        <!-- Zaman + aksiyonlar -->
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
            <div class="checkin-card-time"><?php echo timeAgo($post['created_at']); ?></div>
            <div style="display:flex;gap:2px;">
                <?php if (!empty($post['is_own'])): ?>
                <button onclick="App.deletePost(this, <?php echo $post['id']; ?>)"
                        style="background:none;border:none;cursor:pointer;padding:4px;border-radius:6px;color:var(--text-3);"
                        title="Sil" onmouseover="this.style.color='var(--color-danger)'" onmouseout="this.style.color='var(--text-3)'">
                    <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                </button>
                <?php endif; ?>
                <button onclick="App.openReportModal('checkin', <?php echo $post['id']; ?>)"
                        style="background:none;border:none;cursor:pointer;padding:4px;border-radius:6px;color:var(--text-3);"
                        onmouseover="this.style.color='var(--text-2)'" onmouseout="this.style.color='var(--text-3)'">
                    <span class="material-symbols-outlined" style="font-size:16px;">more_horiz</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Not -->
    <?php if (!empty($post['note'])): ?>
    <div class="checkin-card-note">
        "<?php echo linkify(parseMentions($post['note'])); ?>"
    </div>
    <?php endif; ?>

    <!-- Fotoğraf -->
    <?php if (!empty($post['image'])): ?>
    <img src="<?php echo uploadUrl('posts', $post['image']); ?>"
         class="checkin-card-photo" alt="">
    <?php endif; ?>

    <!-- Alt: Venue link + Beğeni/Yorum -->
    <div class="checkin-card-footer">
        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)$post['venue_id']; ?>"
           class="checkin-card-venue-link">
            <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1;color:<?php echo $catColor; ?>;"><?php echo $catIcon; ?></span>
            <span><?php echo escape($catLabel); ?></span>
            <?php if (!empty($post['venue_address'])): ?>
            <span style="color:var(--text-3);">· <?php echo escape(truncate($post['venue_address'], 28)); ?></span>
            <?php endif; ?>
        </a>

        <div class="checkin-card-actions">
            <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)"
                    class="checkin-action-btn <?php echo !empty($post['viewer_liked']) ? 'liked' : ''; ?>">
                <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' <?php echo !empty($post['viewer_liked']) ? '1' : '0'; ?>;">favorite</span>
                <span class="action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>
            </button>
            <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)"
                    class="checkin-action-btn" style="color:var(--text-3);" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-3)'">
                <span class="material-symbols-outlined" style="font-size:18px;">chat_bubble</span>
                <span class="action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
            </button>
            <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>');App.flash('Link kopyalandı!','success')"
                    class="checkin-action-btn" style="color:var(--text-3);" onmouseover="this.style.color='var(--text-2)'" onmouseout="this.style.color='var(--text-3)'">
                <span class="material-symbols-outlined" style="font-size:18px;">share</span>
            </button>
        </div>
    </div>

    <!-- Yorumlar (gizli, toggle ile açılır) -->
    <div class="post-comments-section" id="comments-section-<?php echo $post['id']; ?>"
         style="display:none;border-top:1px solid var(--border-light);padding:10px 14px 12px;">
        <div id="comments-list-<?php echo $post['id']; ?>" style="display:flex;flex-direction:column;gap:6px;max-height:240px;overflow-y:auto;margin-bottom:10px;">
            <div style="text-align:center;font-size:12px;color:var(--text-3);padding:8px;">Yükleniyor...</div>
        </div>
        <?php if (Auth::check()): ?>
        <form style="display:flex;gap:8px;align-items:center;"
              onsubmit="App.submitInlineComment(this, <?php echo $post['id']; ?>); return false;">
            <input type="text" placeholder="Yorum yaz…" maxlength="500" required
                   class="comment-input-inline"
                   style="flex:1;background:var(--bg-input);border:1.5px solid transparent;border-radius:20px;padding:7px 14px;font-size:13px;font-family:var(--font);outline:none;color:var(--text-1);"
                   onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='transparent'">
            <button type="submit" class="comment-send-btn"
                    style="width:32px;height:32px;border-radius:50%;background:var(--color-primary);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:15px;font-variation-settings:'FILL' 1;">send</span>
            </button>
        </form>
        <?php endif; ?>
    </div>

</article>
