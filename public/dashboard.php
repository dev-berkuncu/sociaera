<?php
/**
 * Sociaera — Dashboard (Ana Sayfa / Feed) - Tailwind Design
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/RateLimit.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Services/ImageUploader.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Checkin.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

// XSS: Whitelist ile güvenli hale getirildi
$allowedFilters = ['all', 'following'];
$feedFilter = in_array($_GET['filter'] ?? 'all', $allowedFilters, true) ? ($_GET['filter'] ?? 'all') : 'all';
$page = max(1, (int)($_GET['page'] ?? 1));

$checkinModel = new CheckinModel();

if ($feedFilter === 'following') {
    $posts = $checkinModel->getFollowingFeed(Auth::id(), $page);
} else {
    $posts = $checkinModel->getGlobalFeed($page, 20, Auth::id());
}

$userModel = new UserModel();
$currentUser = $userModel->getById(Auth::id());

// Hesap silinmişse oturumu kapat
if (!$currentUser) {
    Auth::logout();
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

// Sponsorlu içerikleri çek (Premium kullanıcılar reklamsız)
$sponsoredAds = [];
if (!UserModel::isPremiumActive($currentUser)) {
    try {
        $sponsoredAds = (new AdModel())->getActiveForFeed(5);
    } catch (Exception $e) {}
}

$pageTitle = 'Ana Sayfa';
$activeNav = 'dashboard';

require_once __DIR__ . '/partials/app_header.php';
?>

<!-- Center Main Feed -->
<section class="flex-1 flex flex-col gap-6 max-w-3xl">
    <!-- Feed Filter Tabs -->
    <div class="flex items-center gap-6 border-b border-white/5 pb-2">
        <a href="?filter=all" class="<?php echo $feedFilter === 'all' ? 'text-[#ff9100] font-bold text-sm border-b-2 border-[#ff9100] pb-2 px-1 -mb-[10px]' : 'text-slate-400 hover:text-white text-sm pb-2 px-1 transition-colors'; ?>">Herkes</a>
        <a href="?filter=following" class="<?php echo $feedFilter === 'following' ? 'text-[#ff9100] font-bold text-sm border-b-2 border-[#ff9100] pb-2 px-1 -mb-[10px]' : 'text-slate-400 hover:text-white text-sm pb-2 px-1 transition-colors'; ?>">Takip Ettiklerim</a>
    </div>

    <!-- Compose Box -->
    <div class="bg-surface-container-low border border-white/5 rounded-xl p-5 shadow-lg overflow-visible relative">
        <form id="composeForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="venue_id" id="selectedVenueId" value="">
            <div class="flex gap-4">
                <?php $avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']); ?>
                <img alt="User avatar" class="w-10 h-10 rounded-full object-cover border border-white/10 flex-shrink-0" src="<?php echo $avatarUrl; ?>" width="40" height="40"/>
                <div class="flex-grow relative">
                    <div id="selectedVenueDisplay" class="flex items-center gap-2 mb-2 bg-[#ff9100]/10 w-fit px-3 py-1 rounded-full border border-[#ff9100]/20" style="display:none;">
                        <span class="material-symbols-outlined text-[14px] text-[#ff9100]" style="font-variation-settings: 'FILL' 1;">location_on</span>
                        <span id="selectedVenueName" class="text-xs font-bold text-slate-300"></span>
                        <button type="button" onclick="removeVenue()" class="text-slate-500 hover:text-red-400 transition-colors"><span class="material-symbols-outlined text-[14px]">close</span></button>
                    </div>
                    <textarea class="w-full bg-transparent border-none text-on-surface placeholder:text-slate-500 text-sm focus:ring-0 resize-none outline-none p-0 mt-2" name="note" id="composeNote" placeholder="Neredesin? Ne yapıyorsun?" rows="2"></textarea>
                    <div id="composePreview" class="mt-2 rounded-xl overflow-hidden border border-white/10 relative" style="display:none;"></div>
                    
                    <!-- @ Mention Dropdown -->
                    <div id="mentionDropdown" class="absolute left-0 w-64 bg-surface-container border border-white/10 rounded-lg shadow-xl z-50 max-h-48 overflow-y-auto" style="display:none;"></div>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between pt-4 border-t border-white/5">
                <div class="flex items-center gap-2">
                    <label class="flex items-center justify-center w-9 h-9 rounded-lg bg-surface-container hover:bg-surface-variant text-slate-400 hover:text-white transition-colors cursor-pointer" title="Fotoğraf ekle">
                        <span class="material-symbols-outlined text-[18px]">image</span>
                        <input type="file" name="image" id="composeImage" accept="image/*" class="hidden">
                    </label>
                    <button type="button" id="venueToggleBtn" class="flex items-center justify-center w-9 h-9 rounded-lg bg-surface-container hover:bg-surface-variant text-slate-400 hover:text-white transition-colors" title="Mekan seç">
                        <span class="material-symbols-outlined text-[18px]">location_on</span>
                    </button>
                </div>

                <button type="submit" id="composeSubmitBtn" disabled class="bg-[#ff9100] text-white px-5 py-2 rounded-lg font-bold text-xs shadow-md hover:brightness-110 transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed">Paylaş</button>
            </div>
        </form>
    </div>

    <!-- Venue Search Dropdown (outside compose card to avoid backdrop-blur clipping) -->
    <div id="venueSearchWrap" style="display:none;" class="fixed w-72 bg-[#1c1b1c] border border-white/5 rounded-xl shadow-2xl p-3 z-[9999]">
        <input type="text" id="venueSearchInput" class="w-full bg-surface-container border border-white/10 rounded-lg px-3 py-2 text-on-surface text-sm focus:outline-none focus:border-[#ff9100]/40 mb-2" placeholder="Mekan ara..." autocomplete="off">
        <div id="venueDropdown" class="max-h-48 overflow-y-auto"></div>
    </div>

    <!-- Feed Cards -->
    <div class="flex flex-col gap-stack-md pb-container-padding">
        <?php if (empty($posts)): ?>
            <div class="bg-[#2a2a2b]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 text-center text-slate-400">
                <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">campaign</span>
                <p><?php echo $feedFilter === 'following' ? 'Takip ettiğin kullanıcıların henüz bir gönderi yok.' : 'Henüz hiç gönderi yok. İlk check-in\'ini yap!'; ?></p>
            </div>
        <?php else: ?>
            <?php 
            $sponsorIndex = 0;
            $sponsorInterval = 4; // Her 4 posttan sonra sponsorlu içerik göster
            foreach ($posts as $index => $post): 
            ?>
                <?php include __DIR__ . '/partials/_tailwind_post_card.php'; ?>
                <?php 
                // Her $sponsorInterval posttan sonra sponsorlu içerik ekle
                if (!empty($sponsoredAds) && ($index + 1) % $sponsorInterval === 0) {
                    $sponsoredAd = $sponsoredAds[$sponsorIndex % count($sponsoredAds)];
                    include __DIR__ . '/partials/_sponsored_card.php';
                    $sponsorIndex++;
                }
                ?>
            <?php endforeach; ?>

            <?php if (count($posts) >= 20): ?>
                <div class="text-center mt-4">
                    <a href="?filter=<?php echo $feedFilter; ?>&page=<?php echo $page + 1; ?>" class="inline-flex items-center gap-2 bg-white/5 hover:bg-white/10 text-on-surface px-6 py-2 rounded-full transition-colors border border-white/10">
                        <span class="material-symbols-outlined">arrow_downward</span> Daha Fazla
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const composeForm = document.getElementById('composeForm');
    const composeNote = document.getElementById('composeNote');
    const composeBtn = document.getElementById('composeSubmitBtn');
    const venueIdInput = document.getElementById('selectedVenueId');
    const mentionDropdown = document.getElementById('mentionDropdown');

    if (!composeForm || !composeNote) return;

    function checkCompose() {
        composeBtn.disabled = !(venueIdInput.value && composeNote.value.trim());
    }
    composeNote.addEventListener('input', checkCompose);

    // ── @ Mention Autocomplete ───────────────────────────
    let mentionTimer = null;
    let mentionStart = -1;

    composeNote.addEventListener('input', function() {
        const val = this.value;
        const pos = this.selectionStart;

        // Find the last @ before cursor
        let atPos = -1;
        for (let i = pos - 1; i >= 0; i--) {
            if (val[i] === '@') { atPos = i; break; }
            if (val[i] === ' ' || val[i] === '\n') break;
        }

        if (atPos === -1) {
            mentionDropdown.style.display = 'none';
            mentionStart = -1;
            return;
        }

        const query = val.substring(atPos + 1, pos);
        mentionStart = atPos;

        if (query.length < 2) {
            mentionDropdown.style.display = 'none';
            return;
        }

        clearTimeout(mentionTimer);
        mentionTimer = setTimeout(async () => {
            const users = await App.searchUsers(query);
            if (users.length === 0) {
                mentionDropdown.style.display = 'none';
                return;
            }

            mentionDropdown.innerHTML = users.map(u => {
                const safeUsername = escapeHtml(u.username || 'U');
                const safeTag = escapeHtml(u.tag || '');
                const safeAvatar = escapeHtml(u.avatar || '');
                const avatar = u.avatar
                    ? `<img src="${App.baseUrl}/uploads/avatars/${safeAvatar}" class="w-8 h-8 rounded-full object-cover border border-white/10">`
                    : `<div class="w-8 h-8 rounded-full bg-primary-container/20 text-primary-container flex items-center justify-center text-xs font-bold border border-white/10">${safeUsername[0].toUpperCase()}</div>`;
                return `<div class="mention-item flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-white/5 rounded-md transition-colors" data-tag="${safeTag || safeUsername}" data-name="${safeUsername}">
                    ${avatar}
                    <div>
                        <div class="text-sm font-semibold text-on-surface">${safeUsername}</div>
                        ${safeTag ? `<div class="text-xs text-slate-400">@${safeTag}</div>` : ''}
                    </div>
                </div>`;
            }).join('');

            mentionDropdown.style.display = 'block';

            mentionDropdown.querySelectorAll('.mention-item').forEach(item => {
                item.addEventListener('click', () => {
                    const tag = item.dataset.tag;
                    const before = composeNote.value.substring(0, mentionStart);
                    const after = composeNote.value.substring(composeNote.selectionStart);
                    composeNote.value = before + '@' + tag + ' ' + after;
                    mentionDropdown.style.display = 'none';
                    composeNote.focus();
                    const newPos = mentionStart + tag.length + 2;
                    composeNote.setSelectionRange(newPos, newPos);
                    checkCompose();
                });
            });
        }, 300);
    });

    // Close mention dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!composeNote.contains(e.target) && !mentionDropdown.contains(e.target)) {
            mentionDropdown.style.display = 'none';
        }
    });

    // ── Venue Search ─────────────────────────────────────
    const venueInput = document.getElementById('venueSearchInput');
    const venueDropdown = document.getElementById('venueDropdown');
    const venueWrap = document.getElementById('venueSearchWrap');
    const venueToggleBtn = document.getElementById('venueToggleBtn');

    if (venueToggleBtn && venueWrap) {
        venueToggleBtn.addEventListener('click', () => {
            const rect = venueToggleBtn.getBoundingClientRect();
            venueWrap.style.top = (rect.bottom + 8) + 'px';
            venueWrap.style.left = rect.left + 'px';
            venueWrap.style.display = 'block';
            venueInput.focus();
        });
    }

    if (venueInput && venueDropdown) {
        App.initVenueSearch(venueInput, venueDropdown, (id, name) => {
            venueIdInput.value = id;
            document.getElementById('selectedVenueName').textContent = name;
            document.getElementById('selectedVenueDisplay').style.display = 'inline-flex';
            venueWrap.style.display = 'none';
            checkCompose();
        });
    }

    window.removeVenue = function() {
        venueIdInput.value = '';
        document.getElementById('selectedVenueDisplay').style.display = 'none';
        checkCompose();
    };

    composeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        composeBtn.disabled = true;
        composeBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[20px]">progress_activity</span>';

        const formData = new FormData(composeForm);
        const res = await App.post(App.baseUrl + '/api/create-post', formData);

        if (res.ok) {
            // Kampanya kazanıldı mı kontrol et
            if (res.data && res.data.earned_campaigns && res.data.earned_campaigns.length > 0) {
                showCampaignRewardModal(res.data.earned_campaigns);
            } else {
                App.flash(res.message || 'Check-in başarılı! 📍', 'success');
                setTimeout(() => location.reload(), 800);
            }
        } else {
            App.flash(res.error || 'Hata oluştu.', 'error');
            composeBtn.disabled = false;
            composeBtn.innerHTML = 'Post';
        }
    });

    // ── Kampanya Ödül Modalı ──────────────────────────────
    function showCampaignRewardModal(campaigns) {
        // Varsa eski modalı kaldır
        document.getElementById('campaignRewardOverlay')?.remove();

        const overlay = document.createElement('div');
        overlay.id = 'campaignRewardOverlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;animation:fadeIn .3s ease';

        let cardsHtml = campaigns.map(c => {
            const rewardLabel = c.reward_text || c.title;
            return `
                <div style="background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(236,72,153,0.1));border:1px solid rgba(139,92,246,0.3);border-radius:16px;padding:1.5rem;text-align:center;">
                    <div style="font-size:1.1rem;font-weight:700;color:#e2e8f0;margin-bottom:0.25rem;">${escapeHtml(c.title)}</div>
                    ${c.reward_text ? `<div style="font-size:0.85rem;color:#a78bfa;margin-bottom:1rem;">${escapeHtml(c.reward_text)}</div>` : '<div style="margin-bottom:1rem;"></div>'}
                    <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;margin-bottom:0.5rem;">Ödül Kodun</div>
                    <div id="campaignCode_${escapeHtml(c.code)}" 
                         onclick="navigator.clipboard.writeText('${escapeHtml(c.code)}'); this.querySelector('.copy-hint').textContent='Kopyalandı!'; this.style.borderColor='#10b981';"
                         style="font-family:monospace;font-size:1.6rem;font-weight:800;letter-spacing:0.2em;color:#a78bfa;background:rgba(139,92,246,0.1);border:2px dashed rgba(139,92,246,0.4);border-radius:12px;padding:0.75rem 1.5rem;cursor:pointer;transition:all 0.2s;">
                        ${escapeHtml(c.code)}
                        <div class="copy-hint" style="font-size:0.65rem;color:#64748b;margin-top:0.25rem;font-family:system-ui;letter-spacing:normal;">tıkla → kopyala</div>
                    </div>
                </div>`;
        }).join('');

        overlay.innerHTML = `
            <div style="background:#2a2a2b;border:1px solid rgba(139,92,246,0.3);border-radius:24px;max-width:420px;width:100%;padding:2rem;position:relative;animation:slideUp .4s ease;box-shadow:0 25px 60px rgba(0,0,0,0.5),0 0 40px rgba(139,92,246,0.15);">
                <!-- Confetti emoji header -->
                <div style="text-align:center;font-size:3rem;margin-bottom:0.5rem;animation:bounce 0.6s ease;">🎉</div>
                <h2 style="text-align:center;font-size:1.5rem;font-weight:800;color:#f1f5f9;margin:0 0 0.25rem;">Kampanya Kazandın!</h2>
                <p style="text-align:center;font-size:0.85rem;color:#64748b;margin:0 0 1.5rem;">Check-in'in seni ödüllendirdi</p>
                
                <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:1.5rem;">
                    ${cardsHtml}
                </div>

                <p style="text-align:center;font-size:0.75rem;color:#64748b;margin-bottom:1.25rem;">
                    <span style="display:inline-flex;align-items:center;gap:4px;">
                        <span class="material-symbols-outlined" style="font-size:14px;">info</span>
                        Kodunu kasada göstererek ödülünü kullanabilirsin
                    </span>
                </p>

                <button onclick="document.getElementById('campaignRewardOverlay').remove(); location.reload();"
                        style="width:100%;padding:0.85rem;border:none;border-radius:14px;background:linear-gradient(135deg,#8b5cf6,#a855f7);color:white;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all 0.2s;box-shadow:0 0 20px rgba(139,92,246,0.3);">
                    Harika! 🎁
                </button>
            </div>
        `;

        document.body.appendChild(overlay);

        // Click overlay dışına basınca kapat
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
                location.reload();
            }
        });
    }
});
</script>
