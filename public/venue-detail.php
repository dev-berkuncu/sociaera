<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Checkin.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';
require_once __DIR__ . '/../app/Models/Campaign.php';

Auth::requireLogin();

$venueId = (int)($_GET['id'] ?? 0);
if (!$venueId) Response::notFound('Mekan bulunamadı.');

$venueModel = new VenueModel();
$venue = $venueModel->getById($venueId);
if (!$venue || $venue['status'] !== 'approved') Response::notFound('Mekan bulunamadı.');

$checkinModel = new CheckinModel();
$checkinCount = $venueModel->getCheckinCount($venueId);
$posts = $checkinModel->getVenueCheckins($venueId, 1, 30, Auth::id());

// Rating verileri
$ratingData = $venueModel->getVenueRating($venueId);
$averageRating = round((float) $ratingData['average_rating'], 1);
$ratingCount = (int) $ratingData['rating_count'];
$userRating = Auth::check() ? $venueModel->getUserRating($venueId, Auth::id()) : 0;

// Kampanya verileri
$campaignModel   = new CampaignModel();
$activeCampaigns = $campaignModel->getActiveCampaigns($venueId);
$userCheckinHere = Auth::check() ? $campaignModel->getUserCheckinCount(Auth::id(), $venueId) : 0;

// Favori durumu (Premium)
$isFavorited = false;
$favoriteCount = 0;
$currentUserData = Auth::check() ? (new UserModel())->getById(Auth::id()) : null;
$isPremiumUser = UserModel::isPremiumActive($currentUserData);
try {
    $favoriteCount = $venueModel->getFavoriteCount($venueId);
    if (Auth::check()) {
        $isFavorited = $venueModel->isFavorited(Auth::id(), $venueId);
    }
} catch (\Throwable $e) {}

// Favori toggle POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_favorite') {
    Csrf::requireValid();
    if (!$isPremiumUser) {
        Auth::setFlash('error', 'Bu özellik Premium üyelere özeldir. 💎');
    } else {
        if ($isFavorited) {
            $venueModel->removeFavorite(Auth::id(), $venueId);
            Auth::setFlash('success', 'Mekan favorilerden kaldırıldı.');
        } else {
            $venueModel->addFavorite(Auth::id(), $venueId);
            Auth::setFlash('success', 'Mekan favorilere eklendi! ⭐');
        }
    }
    header('Location: ' . BASE_URL . '/venue-detail?id=' . $venueId);
    exit;
}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = $venue['name'];
$activeNav = 'venues';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <a href="<?php echo BASE_URL; ?>/venues" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors w-fit mb-2">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span> Mekanlar
    </a>

    <div class="swarm-glass-card rounded-2xl overflow-hidden shadow-[0_20px_40px_-15px_rgba(19,19,20,0.5)] mb-6 relative">
        <!-- Banner Image -->
        <div class="h-64 md:h-80 w-full bg-surface-container relative">
            <div class="absolute inset-0 bg-gradient-to-t from-[#131314]/95 via-transparent to-transparent z-10"></div>
            <?php if (!empty($venue['cover_image'])): ?>
                <div class="absolute inset-0 bg-cover bg-center blur-2xl opacity-40 scale-110" style="background-image: url('<?php echo BASE_URL . '/uploads/venues/' . escape($venue['cover_image']); ?>')"></div>
                <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($venue['cover_image']); ?>" class="w-full h-full object-contain p-4 relative z-10" width="800" height="320">
            <?php elseif (!empty($venue['image'])): ?>
                <div class="absolute inset-0 bg-cover bg-center blur-2xl opacity-40 scale-110" style="background-image: url('<?php echo uploadUrl('posts', $venue['image']); ?>')"></div>
                <img src="<?php echo uploadUrl('posts', $venue['image']); ?>" class="w-full h-full object-contain p-4 relative z-10" width="800" height="320">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-slate-600 bg-surface-container-high relative z-10"><span class="material-symbols-outlined text-[64px]">store</span></div>
            <?php endif; ?>
            
            <!-- Category Badge overlaying banner -->
            <?php if ($venue['category']): ?>
            <div class="absolute top-4 left-4 z-20">
                <span class="bg-black/60 backdrop-blur text-white text-xs font-bold px-3 py-1.5 rounded-full border border-white/20 uppercase tracking-wider shadow-lg">
                    <?php echo escape(VenueModel::categories()[$venue['category']] ?? $venue['category']); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Open/Close Badge -->
            <?php if (isset($venue['is_open'])): ?>
            <div class="absolute top-4 right-4 z-20 flex items-center gap-2 bg-black/60 backdrop-blur text-[12px] font-bold px-3 py-1.5 rounded-full border border-white/20 shadow-lg <?php echo $venue['is_open'] ? 'text-emerald-400' : 'text-red-400'; ?>">
                <span class="w-2 h-2 rounded-full animate-pulse <?php echo $venue['is_open'] ? 'bg-emerald-400 shadow-[0_0_8px_rgba(16,185,129,0.8)]' : 'bg-red-400 shadow-[0_0_8px_rgba(239,68,68,0.8)]'; ?>"></span>
                <?php echo $venue['is_open'] ? 'Şu An Açık' : 'Şu An Kapalı'; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Venue Content -->
        <div class="p-6 md:p-8 relative z-20 -mt-20">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-4xl md:text-5xl font-black text-white drop-shadow-md tracking-tight"><?php echo escape($venue['name']); ?></h1>
                </div>
                <!-- Call to action button -->
                <div class="flex items-center gap-3">
                    <button onclick="openVenueCheckinModal()" id="btn-venue-checkin" class="flex items-center justify-center gap-2 bg-primary-container text-white px-6 py-3 rounded-xl font-bold hover:brightness-110 transition-all shadow-[0_0_20px_rgba(255,145,0,0.3)] active:scale-95 group shrink-0">
                        <span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
                        Burada Check-in Yap
                    </button>
                    <!-- Favori Butonu (Premium) -->
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                        <input type="hidden" name="action" value="toggle_favorite">
                        <button type="submit" class="flex items-center justify-center gap-1.5 <?php echo $isFavorited ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : ($isPremiumUser ? 'bg-white/5 text-slate-400 border-white/10 hover:text-amber-400 hover:border-amber-500/30' : 'bg-white/5 text-slate-600 border-white/5 cursor-not-allowed'); ?> border px-4 py-3 rounded-xl font-bold transition-all active:scale-95 shrink-0" <?php echo !$isPremiumUser ? 'title="Premium özellik 💎"' : ''; ?>>
                            <span class="material-symbols-outlined text-[20px]" <?php echo $isFavorited ? 'data-weight="fill"' : ''; ?>>star</span>
                            <span class="text-xs"><?php echo $favoriteCount; ?></span>
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($venue['description']): ?>
                <p class="text-slate-300 mb-8 leading-relaxed font-body-md text-lg max-w-2xl"><?php echo nl2brSafe($venue['description']); ?></p>
            <?php endif; ?>
            
            <!-- Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <div class="flex flex-col gap-4 bg-white/5 border border-white/10 rounded-xl p-5">
                    <?php if ($venue['address']): ?>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[24px] text-primary-container shrink-0 mt-0.5">map</span> 
                            <span class="text-slate-300"><?php echo escape($venue['address']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($venue['hours'])): ?>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[24px] text-primary-container shrink-0">schedule</span> 
                            <span class="text-slate-300"><?php echo escape($venue['hours']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($venue['phone'])): ?>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[24px] text-primary-container shrink-0">call</span> 
                            <span class="text-slate-300"><?php echo escape($venue['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-4 bg-white/5 border border-white/10 rounded-xl p-5">
                    <?php if ($venue['website']): ?>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[24px] text-[#3b82f6]">language</span> 
                            <a href="<?php echo safeHref($venue['website']); ?>" target="_blank" rel="noopener noreferrer" class="text-slate-300 hover:text-[#3b82f6] transition-colors truncate">Resmi Web Sitesi</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($venue['facebrowser_url']): ?>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[24px] text-[#3b5998]">link</span> 
                            <a href="<?php echo safeHref($venue['facebrowser_url']); ?>" target="_blank" rel="noopener noreferrer" class="text-slate-300 hover:text-[#3b5998] transition-colors truncate">Facebrowser Sayfası</a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Stats in the grid -->
                    <div class="flex items-center gap-3 mt-auto pt-2">
                        <span class="material-symbols-outlined text-[24px] text-emerald-400">verified_user</span> 
                        <span class="text-slate-300"><strong class="text-white text-lg"><?php echo $checkinCount; ?></strong> Toplam Check-in</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Section -->
    <div class="bg-[#2a2a2b]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_20px_40px_-15px_rgba(19,19,20,0.5)] mb-6" id="venueRatingSection">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <!-- Left: Average Rating Display -->
            <div class="flex items-center gap-5">
                <div class="flex flex-col items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-500/20 to-orange-500/10 border border-amber-500/20">
                    <span class="text-3xl font-black text-amber-400 leading-none" id="ratingAvgDisplay"><?php echo $averageRating > 0 ? number_format($averageRating, 1) : '—'; ?></span>
                    <span class="text-[10px] text-amber-400/70 font-semibold uppercase tracking-wider mt-0.5">/ 5</span>
                </div>
                <div class="flex flex-col gap-1">
                    <h3 class="text-lg font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-400" style="font-variation-settings: 'FILL' 1;">star</span>
                        Mekan Puanı
                    </h3>
                    <p class="text-sm text-slate-400">
                        <span id="ratingCountDisplay"><?php echo $ratingCount; ?></span> kişi puan verdi
                    </p>
                    <!-- Average Stars (read-only) -->
                    <div class="flex items-center gap-0.5 mt-1" id="averageStarsDisplay">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php
                                $fillClass = '';
                                if ($averageRating >= $i) {
                                    $fillClass = 'text-amber-400';
                                } elseif ($averageRating >= $i - 0.5) {
                                    $fillClass = 'text-amber-400/50';
                                } else {
                                    $fillClass = 'text-slate-600';
                                }
                            ?>
                            <span class="material-symbols-outlined text-[20px] <?php echo $fillClass; ?>" style="font-variation-settings: 'FILL' 1;">star</span>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Right: User Rating Interactive Stars -->
            <div class="flex flex-col items-center md:items-end gap-2">
                <?php if (Auth::check()): ?>
                    <span class="text-xs text-slate-500 font-medium uppercase tracking-wider">
                        <?php echo $userRating > 0 ? 'Puanını güncelle' : 'Puan ver'; ?>
                    </span>
                    <div class="flex items-center gap-1" id="userStarRating" data-venue-id="<?php echo $venueId; ?>" data-current="<?php echo $userRating; ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button"
                                class="star-btn group relative p-1 rounded-lg hover:bg-amber-400/10 transition-all duration-200"
                                data-star="<?php echo $i; ?>"
                                aria-label="<?php echo $i; ?> yıldız">
                                <span class="material-symbols-outlined text-[32px] transition-all duration-200 <?php echo $i <= $userRating ? 'text-amber-400 scale-110' : 'text-slate-600 group-hover:text-amber-300'; ?>"
                                    style="font-variation-settings: 'FILL' 1;">
                                    star
                                </span>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <?php if ($userRating > 0): ?>
                        <span class="text-xs text-amber-400/70" id="userRatingLabel">Senin puanın: <strong><?php echo $userRating; ?>/5</strong></span>
                    <?php else: ?>
                        <span class="text-xs text-slate-500" id="userRatingLabel">Henüz puan vermedin</span>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="flex flex-col items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-6 py-4">
                        <span class="material-symbols-outlined text-[24px] text-slate-500">lock</span>
                        <p class="text-sm text-slate-400 text-center">Puan vermek için <a href="<?php echo BASE_URL; ?>/login" class="text-primary-container hover:underline font-semibold">giriş yapın</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h2 class="text-xl font-bold text-on-surface mb-2 mt-4 flex items-center gap-2"><span class="material-symbols-outlined text-primary-container">history</span> Son Check-in'ler</h2>

    <!-- ── Aktif Kampanyalar ── -->
    <?php if (!empty($activeCampaigns)): ?>
    <div class="bg-[#2a2a2b]/80 backdrop-blur-[20px] border border-purple-500/20 rounded-xl overflow-hidden mb-2">
        <div class="px-6 py-4 border-b border-white/5 flex items-center gap-2">
            <span class="material-symbols-outlined text-purple-400 text-[20px]">campaign</span>
            <h2 class="text-base font-bold text-on-surface">Aktif Kampanyalar</h2>
        </div>
        <div class="divide-y divide-white/5">
            <?php foreach ($activeCampaigns as $c):
                $hasEarned = Auth::check() && $campaignModel->hasEarned($c['id'], Auth::id());
                $myCode    = null;
                if ($hasEarned) {
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT code FROM campaign_redemptions WHERE campaign_id = ? AND user_id = ?");
                    $stmt->execute([$c['id'], Auth::id()]);
                    $myCode = $stmt->fetchColumn() ?: null;
                }
                $target   = (int)$c['trigger_value'];
                $progress = 0;
                if ($c['trigger_type'] === 'first_checkin') {
                    $progress = $userCheckinHere >= 1 ? 100 : 0;
                } elseif ($target > 0) {
                    $progress = min(100, round(($userCheckinHere / $target) * 100));
                }
            ?>
            <div class="px-6 py-4 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl <?php echo $hasEarned ? 'bg-emerald-500/15' : 'bg-purple-500/15'; ?> flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined <?php echo $hasEarned ? 'text-emerald-400' : 'text-purple-400'; ?> text-[20px]">
                        <?php echo $hasEarned ? 'check_circle' : ($c['reward_type'] === 'free_item' ? 'redeem' : 'percent'); ?>
                    </span>
                </div>
                <div class="flex-grow min-w-0">
                    <div class="font-semibold text-on-surface"><?php echo escape($c['title']); ?></div>
                    <div class="text-sm text-slate-400 mt-0.5">
                        <?php echo escape(CampaignModel::formatTrigger($c)); ?> →
                        <span class="text-purple-400 font-semibold"><?php echo escape(CampaignModel::formatReward($c)); ?></span>
                    </div>
                    <?php if ($c['description']): ?>
                        <p class="text-xs text-slate-500 mt-1"><?php echo escape($c['description']); ?></p>
                    <?php endif; ?>

                    <?php if (!$hasEarned && $c['trigger_type'] !== 'first_checkin'): ?>
                    <div class="mt-2">
                        <div class="flex justify-between text-xs text-slate-500 mb-1">
                            <span><?php echo $userCheckinHere; ?> / <?php echo $target; ?> check-in</span>
                            <span><?php echo $progress; ?>%</span>
                        </div>
                        <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-purple-500/70 rounded-full transition-all duration-700" style="width:<?php echo $progress; ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasEarned): ?>
                    <div class="mt-2 flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-3 py-2 flex-wrap">
                        <span class="material-symbols-outlined text-emerald-400 text-[16px]">confirmation_number</span>
                        <span class="text-xs text-slate-400">Kodun:</span>
                        <code class="font-mono font-bold text-emerald-400 tracking-widest"><?php echo escape($myCode ?? '——'); ?></code>
                        <span class="text-xs text-slate-500">— kasaya göster</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Check-in Listesi (sade) -->
    <div class="swarm-glass-card rounded-xl border border-outline-variant/20 overflow-hidden mb-4">
        <?php if (empty($posts)): ?>
            <div class="p-8 text-center text-slate-400">
                <span class="material-symbols-outlined text-[40px] mb-2 block opacity-40">pin_drop</span>
                <p class="text-sm">Bu mekanda henüz check-in yok. İlk check-in'i sen yap!</p>
                <button onclick="openVenueCheckinModal()" class="mt-4 inline-flex items-center gap-2 bg-primary-container text-white px-5 py-2.5 rounded-lg font-bold text-sm hover:brightness-110 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
                    Check-in Yap
                </button>
            </div>
        <?php else: ?>
            <div class="divide-y divide-white/5">
                <?php foreach ($posts as $ci): ?>
                    <?php include __DIR__ . '/partials/_checkin_row.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Inline Check-in Modal ── -->
<div id="venueCheckinModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-md" onclick="closeVenueCheckinModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-[#1c1b1c]/95 backdrop-blur-xl border border-white/10 rounded-2xl w-full max-w-md shadow-2xl relative p-6 animate-[modalIn_0.25s_ease-out]">

            <div class="flex items-center justify-between pb-4 border-b border-white/5 mb-4">
                <div>
                    <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
                        Check-in Yap
                    </h3>
                    <p class="text-xs text-on-surface-variant mt-0.5"><?php echo escape($venue['name']); ?></p>
                </div>
                <button onclick="closeVenueCheckinModal()" class="text-slate-400 hover:text-white transition-colors p-1">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form id="venueCheckinForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">

                <?php $ciAvatarUrl = safeAvatarUrl($currentUserData['avatar'] ?? null, $currentUserData['username'] ?? 'U'); ?>
                <div class="flex gap-3 mb-4">
                    <img src="<?php echo $ciAvatarUrl; ?>" alt="" class="w-10 h-10 rounded-full object-cover border border-white/10 flex-shrink-0" width="40" height="40">
                    <textarea id="venueCheckinNote" name="note"
                        class="flex-grow bg-black/40 border border-white/5 rounded-xl px-3 py-2.5 text-on-surface placeholder:text-slate-500 text-sm focus:outline-none focus:border-primary/40 transition-colors resize-none"
                        placeholder="Bu mekanda ne yaptın? (opsiyonel)" rows="3" maxlength="500"></textarea>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-white transition-colors" title="Fotoğraf ekle">
                        <span class="material-symbols-outlined text-xl">add_a_photo</span>
                        <span class="text-xs">Fotoğraf</span>
                        <input type="file" name="image" id="venueCheckinImage" accept="image/*" class="hidden">
                    </label>
                    <button type="submit" id="venueCheckinBtn"
                        class="bg-primary-container text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:brightness-110 transition-all active:scale-95 shadow-[0_0_15px_rgba(255,145,0,0.3)] flex items-center gap-2">
                        <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;">pin_drop</span>
                        Check-in Yap
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>

<!-- Venue Rating Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const starContainer = document.getElementById('userStarRating');
    if (!starContainer) return;

    const venueId = starContainer.dataset.venueId;
    let currentRating = parseInt(starContainer.dataset.current) || 0;
    const starBtns = starContainer.querySelectorAll('.star-btn');

    // Hover preview
    starBtns.forEach(btn => {
        const starVal = parseInt(btn.dataset.star);

        btn.addEventListener('mouseenter', () => {
            starBtns.forEach(b => {
                const v = parseInt(b.dataset.star);
                const icon = b.querySelector('.material-symbols-outlined');
                if (v <= starVal) {
                    icon.className = 'material-symbols-outlined text-[32px] transition-all duration-200 text-amber-400 scale-110';
                } else {
                    icon.className = 'material-symbols-outlined text-[32px] transition-all duration-200 text-slate-600';
                }
            });
        });

        btn.addEventListener('click', async () => {
            // Disable all buttons during request
            starBtns.forEach(b => b.disabled = true);

            const formData = new FormData();
            formData.append('csrf_token', App.csrfToken);
            formData.append('venue_id', venueId);
            formData.append('rating', starVal);

            const res = await App.post(App.baseUrl + '/api/venue-rating', formData);

            if (res.ok) {
                currentRating = starVal;
                starContainer.dataset.current = starVal;

                // Update star visuals
                updateStarDisplay(starVal);

                // Update average display
                const avgEl = document.getElementById('ratingAvgDisplay');
                const countEl = document.getElementById('ratingCountDisplay');
                const labelEl = document.getElementById('userRatingLabel');

                if (avgEl) avgEl.textContent = res.data.average_rating.toFixed(1);
                if (countEl) countEl.textContent = res.data.rating_count;
                if (labelEl) {
                    labelEl.textContent = 'Senin puanın: ' + starVal + '/5';
                    labelEl.className = 'text-xs text-amber-400/70';
                }

                // Update average stars
                updateAverageStars(res.data.average_rating);

                App.flash('Puanınız kaydedildi! ⭐', 'success');
            } else {
                App.flash(res.error || 'Puan verilemedi.', 'error');
            }

            starBtns.forEach(b => b.disabled = false);
        });
    });

    // Reset stars on mouse leave to current rating
    starContainer.addEventListener('mouseleave', () => {
        updateStarDisplay(currentRating);
    });

    function updateStarDisplay(rating) {
        starBtns.forEach(b => {
            const v = parseInt(b.dataset.star);
            const icon = b.querySelector('.material-symbols-outlined');
            if (v <= rating) {
                icon.className = 'material-symbols-outlined text-[32px] transition-all duration-200 text-amber-400 scale-110';
            } else {
                icon.className = 'material-symbols-outlined text-[32px] transition-all duration-200 text-slate-600 group-hover:text-amber-300';
            }
        });
    }

    function updateAverageStars(avg) {
        const avgStars = document.getElementById('averageStarsDisplay');
        if (!avgStars) return;
        const icons = avgStars.querySelectorAll('.material-symbols-outlined');
        icons.forEach((icon, idx) => {
            const starNum = idx + 1;
            if (avg >= starNum) {
                icon.className = 'material-symbols-outlined text-[20px] text-amber-400';
            } else if (avg >= starNum - 0.5) {
                icon.className = 'material-symbols-outlined text-[20px] text-amber-400/50';
            } else {
                icon.className = 'material-symbols-outlined text-[20px] text-slate-600';
            }
            icon.style.fontVariationSettings = "'FILL' 1";
        });
    }
});
</script>

<!-- Venue Check-in Modal Script -->
<script>
function openVenueCheckinModal() {
    const modal = document.getElementById('venueCheckinModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const ta = document.getElementById('venueCheckinNote');
            if (ta) ta.focus();
        }, 150);
    }
}

function closeVenueCheckinModal() {
    const modal = document.getElementById('venueCheckinModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('venueCheckinForm');
    const btn  = document.getElementById('venueCheckinBtn');
    if (!form || !btn) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-base">progress_activity</span> Kaydediliyor...';

        const formData = new FormData(form);
        const res = await App.post(App.baseUrl + '/api/create-post', formData);

        if (res.ok) {
            closeVenueCheckinModal();
            if (res.data && res.data.earned_campaigns && res.data.earned_campaigns.length > 0) {
                showVenueCampaignModal(res.data.earned_campaigns);
            } else {
                App.flash(res.message || 'Check-in başarılı! 📍', 'success');
                setTimeout(() => location.reload(), 800);
            }
        } else {
            App.flash(res.error || 'Hata oluştu.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-base" style="font-variation-settings:\'FILL\' 1;">pin_drop</span> Check-in Yap';
        }
    });

    // ESC tuşu ile kapat
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeVenueCheckinModal();
    });
});

function showVenueCampaignModal(campaigns) {
    document.getElementById('campaignRewardOverlay')?.remove();
    const overlay = document.createElement('div');
    overlay.id = 'campaignRewardOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:1rem;';

    const cardsHtml = campaigns.map(c => `
        <div style="background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(236,72,153,0.1));border:1px solid rgba(139,92,246,0.3);border-radius:16px;padding:1.5rem;text-align:center;">
            <div style="font-size:1rem;font-weight:700;color:#e2e8f0;margin-bottom:0.25rem;">${escapeHtml(c.title)}</div>
            ${c.reward_text ? `<div style="font-size:0.8rem;color:#a78bfa;margin-bottom:1rem;">${escapeHtml(c.reward_text)}</div>` : '<div style="margin-bottom:1rem;"></div>'}
            <div style="font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;margin-bottom:0.5rem;">Ödül Kodun</div>
            <div onclick="navigator.clipboard.writeText('${escapeHtml(c.code)}'); this.querySelector('.copy-hint').textContent='Kopyalandı!'; this.style.borderColor='#10b981';"
                 style="font-family:monospace;font-size:1.4rem;font-weight:800;letter-spacing:0.2em;color:#a78bfa;background:rgba(139,92,246,0.1);border:2px dashed rgba(139,92,246,0.4);border-radius:12px;padding:0.75rem 1rem;cursor:pointer;">
                ${escapeHtml(c.code)}
                <div class="copy-hint" style="font-size:0.6rem;color:#64748b;margin-top:0.25rem;font-family:system-ui;letter-spacing:normal;">tıkla → kopyala</div>
            </div>
        </div>`).join('');

    overlay.innerHTML = `
        <div style="background:#2a2a2b;border:1px solid rgba(139,92,246,0.3);border-radius:24px;max-width:400px;width:100%;padding:2rem;position:relative;box-shadow:0 25px 60px rgba(0,0,0,0.5);">
            <div style="text-align:center;font-size:2.5rem;margin-bottom:0.5rem;">🎉</div>
            <h2 style="text-align:center;font-size:1.3rem;font-weight:800;color:#f1f5f9;margin:0 0 0.25rem;">Kampanya Kazandın!</h2>
            <p style="text-align:center;font-size:0.8rem;color:#64748b;margin:0 0 1.5rem;">Check-in'in seni ödüllendirdi</p>
            <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:1.5rem;">${cardsHtml}</div>
            <button onclick="document.getElementById('campaignRewardOverlay').remove(); location.reload();"
                    style="width:100%;padding:0.85rem;border:none;border-radius:14px;background:linear-gradient(135deg,#8b5cf6,#a855f7);color:white;font-size:0.9rem;font-weight:700;cursor:pointer;">
                Harika! 🎁
            </button>
        </div>`;

    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) { overlay.remove(); location.reload(); } });
}
</script>
