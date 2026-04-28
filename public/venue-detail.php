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

    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl overflow-hidden shadow-[0_20px_40px_-15px_rgba(15,23,42,0.5)] mb-6 relative">
        <!-- Banner Image -->
        <div class="h-64 md:h-80 w-full bg-surface-container relative">
            <div class="absolute inset-0 bg-gradient-to-t from-[#1E293B]/90 via-[#1E293B]/40 to-transparent z-10"></div>
            <?php if (!empty($venue['cover_image'])): ?>
                <div class="absolute inset-0 bg-cover bg-center blur-2xl opacity-40 scale-110" style="background-image: url('<?php echo BASE_URL . '/uploads/venues/' . escape($venue['cover_image']); ?>')"></div>
                <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($venue['cover_image']); ?>" class="w-full h-full object-contain p-4 relative z-10">
            <?php elseif (!empty($venue['image'])): ?>
                <div class="absolute inset-0 bg-cover bg-center blur-2xl opacity-40 scale-110" style="background-image: url('<?php echo uploadUrl('posts', $venue['image']); ?>')"></div>
                <img src="<?php echo uploadUrl('posts', $venue['image']); ?>" class="w-full h-full object-contain p-4 relative z-10">
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
                <a href="<?php echo BASE_URL; ?>/?venue_id=<?php echo $venue['id']; ?>" class="flex items-center justify-center gap-2 bg-primary-container text-white px-6 py-3 rounded-xl font-bold hover:bg-primary-container/90 transition-all shadow-[0_0_20px_rgba(255,107,53,0.3)] active:scale-95 group shrink-0">
                    <span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">pin_drop</span>
                    Burada Check-in Yap
                </a>
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
                            <a href="<?php echo escape($venue['website']); ?>" target="_blank" class="text-slate-300 hover:text-[#3b82f6] transition-colors truncate">Resmi Web Sitesi</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($venue['facebrowser_url']): ?>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[24px] text-[#3b5998]">link</span> 
                            <a href="<?php echo escape($venue['facebrowser_url']); ?>" target="_blank" class="text-slate-300 hover:text-[#3b5998] transition-colors truncate">Facebrowser Sayfası</a>
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
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_20px_40px_-15px_rgba(15,23,42,0.5)] mb-6" id="venueRatingSection">
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

    <div class="flex flex-col gap-stack-md pb-container-padding">
        <?php if (empty($posts)): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-8 text-center text-slate-400">
                <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">pin_drop</span>
                <p>Bu mekanda henüz check-in yok.</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/partials/_tailwind_post_card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

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
                    labelEl.innerHTML = 'Senin puanın: <strong>' + starVal + '/5</strong>';
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
