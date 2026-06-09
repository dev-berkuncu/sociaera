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

// Generate realistic mock coordinates from venue ID
$vId = (int)$post['venue_id'];
$mockX = round(sin($vId) * 1500, 1);
$mockY = round(cos($vId * 2) * 1800, 1);
$mockZ = round(abs(sin($vId * 3)) * 45 + 15, 1);
$mockCoords = "X: {$mockX} / Y: {$mockY} / Z: {$mockZ}";
?>
<article class="receipt-card p-5 group relative overflow-visible flex flex-col gap-3 mb-6" id="post-<?php echo $post['id']; ?>">
    
    <!-- Stamp Indicator -->
    <div class="ticket-stamp-wrapper">
        <?php if (!empty($post['is_mystery_shopper'])): ?>
            <div class="ticket-stamp stamp-mystery">DENETLENDİ</div>
        <?php elseif (!empty($post['is_premium'])): ?>
            <div class="ticket-stamp stamp-vip">GOLD VIP</div>
        <?php else: ?>
            <div class="ticket-stamp stamp-approved">APPROVED</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($post['reposted_by'])): ?>
    <div class="flex items-center gap-2 text-cyber-orange font-mono text-[10px] mb-1 tracking-widest uppercase">
        <span class="material-symbols-outlined text-[14px]">settings_input_antenna</span>
        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?>" class="hover:text-white transition-colors">@<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?></a> TELSİZ YAYINI
    </div>
    <?php endif; ?>

    <!-- Header Row: User Info & Tagline -->
    <div class="flex justify-between items-start mb-1 z-10">
        <div class="flex gap-md min-w-0">
            <!-- Avatar -->
            <div class="relative flex-shrink-0">
                <?php 
                $avatarUrl = !empty($post['is_mystery_shopper']) 
                    ? 'https://ui-avatars.com/api/?name=GM&background=6900b3&color=ffffff' 
                    : safeAvatarUrl($post['avatar'] ?? null, $post['username'] ?? 'User');
                ?>
                <img alt="<?php echo escape($post['username']); ?>" class="w-10 h-10 rounded-full border border-white/5 object-cover" src="<?php echo $avatarUrl; ?>" />
                <?php if (empty($post['is_mystery_shopper']) && !empty($post['is_premium'])): ?>
                    <div class="absolute -bottom-1 -right-1 bg-cyber-orange text-[8px] font-bold px-1 rounded-full text-white shadow-md">VIP</div>
                <?php endif; ?>
            </div>
            
            <div class="min-w-0">
                <!-- User Meta Row -->
                <div class="flex items-center gap-xs flex-wrap">
                    <?php if (!empty($post['is_mystery_shopper'])): ?>
                        <span class="font-bold text-cyber-purple text-sm">Gizli Müşteri</span>
                        <span class="material-symbols-outlined text-cyber-purple text-sm" style="font-variation-settings: 'FILL' 1;" title="Gizli Müşteri">visibility_off</span>
                    <?php else: ?>
                        <span class="font-bold text-cyber-orange text-sm"><?php echo escape($post['username']); ?></span>
                        <?php if (!empty($post['is_premium'])): ?>
                            <span class="material-symbols-outlined text-cyber-orange text-sm shadow-neonOrange" style="font-variation-settings: 'FILL' 1;" title="Premium Üye">workspace_premium</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="text-slate-500 text-xs font-mono">• <?php echo timeAgo($post['created_at']); ?></span>
                </div>

                <!-- Tagline -->
                <p class="text-xs text-slate-300 mt-1">
                    <span class="text-white font-bold"><?php echo escape($post['venue_name']); ?></span><?php echo $actionText; ?>
                </p>

                <!-- Review / Note -->
                <?php if (!empty($post['note'])): ?>
                <p class="text-slate-300 italic mt-2 text-sm pl-2 border-l-2 border-cyber-orange/30 py-0.5">
                    “<?php echo linkify(parseMentions($post['note'])); ?>”
                </p>
                <?php endif; ?>

                <!-- Monospace Ticket Grid (Receipt UI element) -->
                <div class="ticket-meta-grid mt-3 mb-2 font-mono text-[9px] bg-slate-950/50 border border-white/5 rounded p-2.5">
                    <div>OPERATOR: @<?php echo escape($post['tag'] ?: $post['username']); ?></div>
                    <div>TICKET ID: #CK-<?php echo sprintf('%05d', $post['id']); ?></div>
                    <div class="col-span-2">COORDS: <?php echo $mockCoords; ?></div>
                    <div class="col-span-2">ADDRESS: <?php echo escape($post['venue_address'] ?: 'Los Santos'); ?></div>
                    
                    <!-- Barcode graphic inside ticket metadata -->
                    <div class="col-span-2 flex flex-col items-center mt-2 border-t border-white/5 pt-2 gap-1 select-none">
                        <div class="w-full h-5 barcode-stripes opacity-40"></div>
                        <span class="text-[7px] text-slate-500 tracking-[0.2em] font-mono">*CK-<?php echo $post['id']; ?>-SOCIAERA*</span>
                    </div>
                </div>

                <!-- Category & Location Pills -->
                <div class="flex flex-wrap gap-xs mt-3">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-900 border border-white/5 text-[9px] text-slate-400">
                        <span class="material-symbols-outlined text-[10px]">location_on</span> 
                        <?php echo escape($post['venue_address'] ?: 'Los Santos'); ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-900 border border-cyber-orange/20 text-[9px] text-cyber-orange">
                        <span class="material-symbols-outlined text-[10px]"><?php echo $categoryIcon; ?></span>
                        <?php echo escape(VenueModel::categories()[$post['venue_category']] ?? ($post['venue_category'] ?? 'KEŞİF NOKTASI')); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Options / Delete -->
        <div class="flex items-center gap-1 shrink-0 z-10">
            <?php if (!empty($post['is_own']) || (int)($post['user_id'] ?? 0) === Auth::id()): ?>
                <button onclick="App.deletePost(this, <?php echo $post['id']; ?>)" class="text-slate-500 hover:text-red-400 transition-colors p-1 rounded hover:bg-white/5" title="Sil">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
            <?php endif; ?>
            <button onclick="App.openReportModal('checkin', <?php echo $post['id']; ?>)" class="text-slate-500 hover:text-slate-300 p-1 rounded hover:bg-white/5 transition-colors" title="Raporla"><span class="material-symbols-outlined text-[18px]">more_horiz</span></button>
        </div>
    </div>

    <!-- Attached Image -->
    <?php if (!empty($post['image'])): ?>
    <div class="relative rounded-lg overflow-hidden aspect-video bg-slate-950 border border-white/5 z-10">
        <img class="w-full h-full object-cover group-hover:scale-[1.01] transition-transform duration-700" src="<?php echo uploadUrl('posts', $post['image']); ?>" loading="lazy"/>
    </div>
    <?php endif; ?>

    <!-- Action Buttons (Like / Comment) -->
    <div class="flex justify-between items-center mt-2 border-t border-white/5 pt-3 text-xs font-mono">
        <div class="flex gap-4">
            <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)" class="flex items-center gap-1 text-slate-400 hover:text-cyber-pink transition-colors <?php echo !empty($post['viewer_liked']) ? 'liked text-cyber-pink' : ''; ?>">
                <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' <?php echo !empty($post['viewer_liked']) ? '1' : '0'; ?>;">favorite</span>
                <span class="font-bold action-count"><?php echo (int)($post['like_count'] ?? 0); ?> LIKES</span>
            </button>
            <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)" class="flex items-center gap-1 text-slate-400 hover:text-cyber-cyan transition-colors">
                <span class="material-symbols-outlined text-lg">chat_bubble</span>
                <span class="font-bold action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?> RADIO LOGS</span>
            </button>
        </div>
        <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>'); App.flash('Link kopyalandı!', 'success');" class="flex items-center gap-1 text-slate-400 hover:text-cyber-orange transition-colors">
            <span class="material-symbols-outlined text-lg">bookmark</span>
            <span class="font-bold">ENCODE LINK</span>
        </button>
    </div>

    <!-- Inline Radio Logs (Comments Section) -->
    <div class="post-comments-section mt-3 pt-3 border-t border-dashed border-white/10 hidden" id="comments-section-<?php echo $post['id']; ?>">
        <!-- Comments logs list -->
        <div class="radio-log-container mb-3 max-h-64 overflow-y-auto pr-2" id="comments-list-<?php echo $post['id']; ?>">
            <div class="text-center text-slate-500 text-xs py-2 font-mono">Yükleniyor...</div>
        </div>
        
        <!-- Post Comment Form -->
        <?php if (Auth::check()): ?>
        <form class="flex gap-2 items-center" onsubmit="App.submitInlineComment(this, <?php echo $post['id']; ?>); return false;">
            <span class="text-cyber-cyan font-mono text-[10px] select-none uppercase">RADIO_IN:</span>
            <input type="text" class="comment-input-inline flex-grow radio-input-line bg-transparent border-none text-xs text-white focus:ring-0 focus:outline-none placeholder-slate-700" placeholder="Telsiz mesajı girin..." maxlength="500" required>
            <button type="submit" class="comment-send-btn p-1 text-cyber-cyan hover:text-cyber-orange transition-all flex-shrink-0">
                <span class="material-symbols-outlined text-base">send</span>
            </button>
        </form>
        <?php endif; ?>
    </div>
</article>
<!-- Receipt Jagged Border Separation -->
<div class="receipt-card-tear -mt-6 opacity-60 mb-6 select-none"></div>
