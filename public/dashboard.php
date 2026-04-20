<?php
/**
 * Sociaera — Dashboard (Ana Sayfa / Feed)
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

$pageTitle = 'Ana Sayfa';
$activeNav = 'dashboard';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/partials/sidebar-left.php'; ?>

    <main class="main-feed">
        <!-- Feed Filter -->
        <div class="card-box" style="display:flex; margin-bottom:16px; overflow:hidden;">
            <a href="?filter=all" class="profile-tab <?php echo $feedFilter === 'all' ? 'active' : ''; ?>" style="border-radius:0;">
                <i class="bi bi-globe2"></i> Herkes
            </a>
            <a href="?filter=following" class="profile-tab <?php echo $feedFilter === 'following' ? 'active' : ''; ?>" style="border-radius:0;">
                <i class="bi bi-people"></i> Takip
            </a>
        </div>

        <!-- Compose Box -->
        <div class="compose-box">
            <form id="composeForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="venue_id" id="selectedVenueId" value="">
                <div class="compose-top">
                    <div class="compose-avatar">
                        <?php echo avatarHtml($_SESSION['avatar'] ?? null, Auth::username(), '44'); ?>
                    </div>
                    <div class="compose-input-area">
                        <div id="selectedVenueDisplay" class="compose-venue-display" style="display:none;">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span id="selectedVenueName"></span>
                            <span class="remove-venue" onclick="removeVenue()"><i class="bi bi-x-circle"></i></span>
                        </div>
                        <textarea class="compose-textarea" name="note" id="composeNote" placeholder="Neredesin? Ne yapıyorsun?" maxlength="500"></textarea>
                        <div class="compose-preview" id="composePreview" style="display:none;"></div>
                    </div>
                </div>
                <div class="compose-bottom">
                    <div class="compose-actions">
                        <label class="compose-action-btn" title="Fotoğraf ekle">
                            <i class="bi bi-image"></i>
                            <input type="file" name="image" id="composeImage" accept="image/*" style="display:none;">
                        </label>
                        <div style="position:relative;">
                            <button type="button" class="compose-action-btn" title="Mekan seç" onclick="document.getElementById('venueSearchInput').focus(); document.getElementById('venueSearchWrap').style.display='block';">
                                <i class="bi bi-geo-alt"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary-orange btn-sm" id="composeSubmitBtn" disabled>
                        Post
                    </button>
                </div>

                <!-- Venue Search (hidden by default) -->
                <div id="venueSearchWrap" style="display:none; margin-top:12px; position:relative;">
                    <div class="search-bar" style="margin-bottom:0;">
                        <i class="bi bi-search"></i>
                        <input type="text" id="venueSearchInput" placeholder="Mekan ara..." autocomplete="off">
                    </div>
                    <div class="venue-picker-dropdown" id="venueDropdown"></div>
                </div>
            </form>
        </div>

        <!-- Feed -->
        <?php if (empty($posts)): ?>
            <div class="card-box empty-state">
                <i class="bi bi-megaphone"></i>
                <p><?php echo $feedFilter === 'following' ? 'Takip ettiğin kullanıcıların henüz bir gönderi yok.' : 'Henüz hiç gönderi yok. İlk check-in\'ini yap!'; ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/partials/_post_card.php'; ?>
            <?php endforeach; ?>

            <?php if (count($posts) >= 20): ?>
                <div style="text-align:center; margin:20px 0;">
                    <a href="?filter=<?php echo $feedFilter; ?>&page=<?php echo $page + 1; ?>" class="btn-secondary-soft">
                        <i class="bi bi-arrow-down"></i> Daha Fazla
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/partials/sidebar-right.php'; ?>
</div>

<script>
// Compose Form
const composeForm = document.getElementById('composeForm');
const composeNote = document.getElementById('composeNote');
const composeBtn = document.getElementById('composeSubmitBtn');
const venueIdInput = document.getElementById('selectedVenueId');

// Enable/disable post button
function checkCompose() {
    composeBtn.disabled = !(venueIdInput.value && composeNote.value.trim());
}
composeNote.addEventListener('input', checkCompose);

// Venue search
App.initVenueSearch(
    document.getElementById('venueSearchInput'),
    document.getElementById('venueDropdown'),
    (id, name) => {
        venueIdInput.value = id;
        document.getElementById('selectedVenueName').textContent = name;
        document.getElementById('selectedVenueDisplay').style.display = 'inline-flex';
        document.getElementById('venueSearchWrap').style.display = 'none';
        checkCompose();
    }
);

function removeVenue() {
    venueIdInput.value = '';
    document.getElementById('selectedVenueDisplay').style.display = 'none';
    checkCompose();
}

// Submit compose
composeForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    composeBtn.disabled = true;
    composeBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

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
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
