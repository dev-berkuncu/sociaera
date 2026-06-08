<?php
/**
 * Post Card Partial — Tailwind Design
 * $post dizisi dışarıdan gelir
 */
if (!isset($post)) return;
?>
<article class="post-card receipt-card p-6 flex flex-col gap-4" id="post-<?php echo $post['id']; ?>">
    <!-- Visual RP Stamp -->
    <div class="ticket-stamp-wrapper">
        <?php if (!empty($post['is_mystery_shopper'])): ?>
            <div class="ticket-stamp stamp-mystery">GİZLİ MÜŞTERİ</div>
        <?php elseif (!empty($post['is_premium'])): ?>
            <div class="ticket-stamp stamp-vip">VIP GEÇİŞİ</div>
        <?php else: ?>
            <div class="ticket-stamp stamp-approved">KABUL EDİLDİ</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($post['reposted_by'])): ?>
    <div class="flex items-center gap-2 text-[#ffb59d]/80 font-mono text-[10px] mb-[-6px] tracking-widest uppercase">
        <span class="material-symbols-outlined text-[14px]">settings_input_antenna</span>
        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?>" class="hover:text-white transition-colors">@<?php echo escape($post['reposted_by_tag'] ?: $post['reposted_by']); ?></a> TELSİZ YAYINI
    </div>
    <?php endif; ?>

    <!-- Header: Venue Details -->
    <div class="flex flex-col gap-1 border-b border-dashed border-white/10 pb-3 mt-1">
        <div class="flex items-center justify-between">
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $post['venue_id']; ?>" class="flex items-center gap-2 text-[#ff6b35] hover:text-[#ffb59d] transition-colors">
                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">location_on</span>
                <span class="font-black text-lg tracking-wider uppercase font-['Manrope']"><?php echo escape($post['venue_name']); ?></span>
            </a>
            <?php if (!empty($post['is_own'])): ?>
            <button onclick="App.deletePost(this, <?php echo $post['id']; ?>)" class="text-slate-500 hover:text-red-400 transition-colors z-30" title="Sil"><span class="material-symbols-outlined text-[20px]">delete</span></button>
            <?php endif; ?>
        </div>
        <span class="text-[9px] font-mono text-slate-500 uppercase tracking-widest"><?php echo escape(VenueModel::categories()[$post['venue_category']] ?? ($post['venue_category'] ?? 'KEŞİF NOKTASI')); ?></span>
    </div>

    <!-- Ticket Metadata Stub -->
    <div class="ticket-meta-grid">
        <div>
            <span class="text-slate-500 block text-[8px] uppercase tracking-wider font-mono">MÜŞTERİ</span>
            <?php if (!empty($post['is_mystery_shopper'])): ?>
                <span class="text-purple-300 font-bold">ANONİM G.M.</span>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($post['tag'] ?: $post['username']); ?>" class="text-slate-200 hover:text-[#ffb59d] font-bold">@<?php echo escape($post['tag'] ?: $post['username']); ?></a>
            <?php endif; ?>
        </div>
        <div>
            <span class="text-slate-500 block text-[8px] uppercase tracking-wider font-mono">GEÇİŞ NO</span>
            <span class="text-slate-300 font-bold">#TKT-<?php echo str_pad($post['id'], 5, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div>
            <span class="text-slate-500 block text-[8px] uppercase tracking-wider font-mono">ZAMAN</span>
            <span class="text-slate-300 font-bold"><?php echo strtoupper(timeAgo($post['created_at'])); ?></span>
        </div>
        <div>
            <span class="text-slate-500 block text-[8px] uppercase tracking-wider font-mono">KARAKTER</span>
            <span class="text-slate-300 font-bold truncate block max-w-[120px]" title="<?php echo escape($post['username']); ?>">
                <?php echo !empty($post['is_mystery_shopper']) ? 'GİZLİ MÜŞTERİ' : escape($post['username']); ?>
            </span>
        </div>
    </div>

    <!-- Review / Note -->
    <?php if (!empty($post['note'])): ?>
    <div class="my-1 py-1">
        <p class="font-body-md text-body-md text-slate-200 leading-relaxed font-sans"><?php echo linkify(parseMentions($post['note'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Attached Image -->
    <?php if (!empty($post['image'])): ?>
    <div class="rounded-xl overflow-hidden border border-white/10 shadow-lg bg-black/10 mt-1 max-h-[500px]">
        <img alt="Venue photo" class="block w-full max-w-full h-auto max-h-[500px] object-contain" src="<?php echo uploadUrl('posts', $post['image']); ?>" width="640" height="400" loading="lazy"/>
    </div>
    <?php endif; ?>

    <!-- Actions Footer -->
    <div class="flex items-center justify-between gap-4 mt-2 pt-3 border-t border-dashed border-white/10 text-slate-400">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <!-- Saygı (Like) -->
            <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)" class="flex items-center gap-1.5 hover:text-[#ff6b35] transition-colors <?php echo !empty($post['viewer_liked']) ? 'liked text-[#ff6b35]' : ''; ?>">
                <span class="material-symbols-outlined text-[18px]">shield</span> 
                <span class="text-[10px] font-mono tracking-wider">SAYGI (<span class="action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>)</span>
            </button>
            
            <!-- Telsizle (Repost) -->
            <button onclick="App.toggleRepost(this, <?php echo $post['id']; ?>)" class="flex items-center gap-1.5 hover:text-[#ff6b35] transition-colors <?php echo !empty($post['viewer_reposted']) ? 'reposted text-[#ff6b35]' : ''; ?>">
                <span class="material-symbols-outlined text-[18px]">settings_input_antenna</span> 
                <span class="text-[10px] font-mono tracking-wider">TELSİZLE (<span class="action-count"><?php echo (int)($post['repost_count'] ?? 0); ?></span>)</span>
            </button>
            
            <!-- Telsiz Logu (Comments) -->
            <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)" class="flex items-center gap-1.5 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-[18px]">terminal</span> 
                <span class="text-[10px] font-mono tracking-wider">LOG (<span class="action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>)</span>
            </button>

            <!-- Bahşiş (Tip) -->
            <button onclick="App.flash('Bahşiş özelliği yakında aktif edilecek! 💸', 'info')" class="flex items-center gap-1.5 hover:text-emerald-400 transition-colors text-slate-500">
                <span class="material-symbols-outlined text-[18px]">payments</span> 
                <span class="text-[10px] font-mono tracking-wider">BAHŞİŞ</span>
            </button>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>'); App.flash('Link kopyalandı!', 'success');" class="flex items-center gap-1.5 hover:text-white transition-colors" title="Paylaş">
                <span class="material-symbols-outlined text-[18px]">share</span>
            </button>
            <?php if (Auth::check() && empty($post['is_own'])): ?>
            <button onclick="App.openReportModal('checkin', <?php echo $post['id']; ?>)" class="flex items-center gap-1.5 hover:text-red-400 transition-colors" title="Raporla">
                <span class="material-symbols-outlined text-[18px]">flag</span>
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
