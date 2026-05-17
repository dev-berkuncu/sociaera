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

$feedFilter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));

$checkinModel = new CheckinModel();

if ($feedFilter === 'following') {
    $posts = $checkinModel->getFollowingFeed(Auth::id(), $page);
} else {
    $posts = $checkinModel->getGlobalFeed($page, 20, Auth::id());
}

$userModel = new UserModel();
$currentUser = $userModel->getById(Auth::id());

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
<section class="flex-1 flex flex-col gap-stack-md max-w-3xl">
    <!-- Feed Filter Tabs -->
    <div class="flex items-center gap-8 border-b border-white/10 pb-2">
        <a href="?filter=all" class="<?php echo $feedFilter === 'all' ? 'text-primary-container font-label-md text-label-md border-b-2 border-primary-container pb-2 px-2 -mb-[10px]' : 'text-slate-400 hover:text-on-surface font-label-md text-label-md pb-2 px-2 transition-colors'; ?>">Herkes</a>
        <a href="?filter=following" class="<?php echo $feedFilter === 'following' ? 'text-primary-container font-label-md text-label-md border-b-2 border-primary-container pb-2 px-2 -mb-[10px]' : 'text-slate-400 hover:text-on-surface font-label-md text-label-md pb-2 px-2 transition-colors'; ?>">Takip</a>
    </div>

    <!-- Compose Box -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] overflow-visible" style="overflow:visible;">
        <form id="composeForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="venue_id" id="selectedVenueId" value="">
            <div class="flex gap-4">
                <?php $avatarUrl = safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']); ?>
                <img alt="User avatar" class="w-10 h-10 rounded-full object-cover border border-white/10 flex-shrink-0" src="<?php echo $avatarUrl; ?>" width="40" height="40"/>
                <div class="flex-grow relative">
                    <div id="selectedVenueDisplay" class="flex items-center gap-2 mb-2 bg-white/5 w-fit px-3 py-1 rounded-full border border-white/10" style="display:none;">
                        <span class="material-symbols-outlined text-[16px] text-primary-container">location_on</span>
                        <span id="selectedVenueName" class="font-label-sm text-label-sm text-slate-300"></span>
                        <button type="button" onclick="removeVenue()" class="text-slate-500 hover:text-error transition-colors"><span class="material-symbols-outlined text-[16px]">close</span></button>
                    </div>
                    <textarea class="w-full bg-transparent border-none text-on-surface placeholder:text-slate-500 font-body-md text-body-md focus:ring-0 resize-none outline-none" name="note" id="composeNote" placeholder="Neredesin? Ne yapıyorsun?" rows="2"></textarea>
                    <div id="composePreview" class="mt-2 rounded-xl overflow-hidden border border-white/10 relative" style="display:none;"></div>
                    
                    <!-- @ Mention Dropdown -->
                    <div id="mentionDropdown" class="absolute left-0 w-64 bg-[#1E293B] border border-white/10 rounded-lg shadow-xl z-50 max-h-48 overflow-y-auto" style="display:none;"></div>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between pt-4 border-t border-white/5">
                <div class="flex items-center gap-2">
                    <label class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-white/5 text-secondary transition-colors cursor-pointer" title="Fotoğraf ekle">
                        <span class="material-symbols-outlined">image</span>
                        <input type="file" name="image" id="composeImage" accept="image/*" class="hidden">
                    </label>
                    <button type="button" id="venueToggleBtn" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-white/5 text-secondary transition-colors" title="Mekan seç">
                        <span class="material-symbols-outlined">location_on</span>
                    </button>
                </div>

                <button type="submit" id="composeSubmitBtn" disabled class="bg-primary-container text-white px-6 py-2 rounded-lg font-label-md text-label-md shadow-[0_0_10px_rgba(255,107,53,0.2)] hover:bg-primary-container/90 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">Post</button>
            </div>
        </form>
    </div>

    <!-- Venue Search Dropdown (outside compose card to avoid backdrop-blur clipping) -->
    <div id="venueSearchWrap" style="display:none;" class="fixed w-72 bg-[#0F172A] border border-white/10 rounded-xl shadow-2xl p-3 z-[9999]">
        <input type="text" id="venueSearchInput" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-on-surface text-sm focus:outline-none focus:border-primary-container/40 mb-2" placeholder="Mekan ara..." autocomplete="off">
        <div id="venueDropdown" class="max-h-48 overflow-y-auto"></div>
    </div>

    <!-- Feed Cards -->
    <div class="flex flex-col gap-stack-md pb-container-padding">
        <?php if (empty($posts)): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 text-center text-slate-400">
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
                const avatar = u.avatar
                    ? `<img src="${App.baseUrl}/uploads/avatars/${u.avatar}" class="w-8 h-8 rounded-full object-cover border border-white/10">`
                    : `<div class="w-8 h-8 rounded-full bg-primary-container/20 text-primary-container flex items-center justify-center text-xs font-bold border border-white/10">${(u.username || 'U')[0].toUpperCase()}</div>`;
                return `<div class="mention-item flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-white/5 rounded-md transition-colors" data-tag="${u.tag || u.username}" data-name="${u.username}">
                    ${avatar}
                    <div>
                        <div class="text-sm font-semibold text-on-surface">${u.username}</div>
                        ${u.tag ? `<div class="text-xs text-slate-400">@${u.tag}</div>` : ''}
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
            App.flash('Check-in başarılı! 📍', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            App.flash(res.error || 'Hata oluştu.', 'error');
            composeBtn.disabled = false;
            composeBtn.innerHTML = 'Post';
        }
    });
});
</script>
