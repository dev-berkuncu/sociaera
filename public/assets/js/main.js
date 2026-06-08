/* ============================================
   STITCH INTERACTIONS — Sociaera
   ============================================ */

// Global triggerCheckin function
window.triggerCheckin = function() {
    const modal = document.getElementById('composeModal');
    if (modal) {
        modal.classList.remove('hidden');
        const textarea = document.getElementById('composeNote');
        if (textarea) textarea.focus();
    } else {
        window.location.href = (window.BASE_URL || '') + '/dashboard?checkin=1';
    }
};

document.addEventListener('DOMContentLoaded', function() {

    // ── Auto-open Check-in Modal from URL parameters ──
    const modal = document.getElementById('composeModal');
    if (modal) {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('checkin') || urlParams.has('venue_id')) {
            modal.classList.remove('hidden');
            const textarea = document.getElementById('composeNote');
            if (textarea) textarea.focus();
        }
    }

    // ── Card & Button micro-interaction ──
    document.querySelectorAll('article, button').forEach(function(el) {
        el.addEventListener('mousedown', function() {
            el.style.transform = 'scale(0.98)';
            el.style.transition = 'transform 0.1s ease';
        });
        el.addEventListener('mouseup', function() {
            el.style.transform = 'scale(1)';
        });
        el.addEventListener('mouseleave', function() {
            el.style.transform = 'scale(1)';
        });
    });

    // ── Search Bar Focus Effect ──
    var searchInput = document.querySelector('.stitch-search-input');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            searchInput.parentElement.classList.add('scale-105');
            searchInput.parentElement.style.transition = 'transform 0.2s ease';
        });
        searchInput.addEventListener('blur', function() {
            searchInput.parentElement.classList.remove('scale-105');
        });
    }

    // ── Flash Message auto-dismiss ──
    document.querySelectorAll('.flash-message').forEach(function(msg) {
        setTimeout(function() {
            msg.style.opacity = '0';
            msg.style.transform = 'translateX(100%)';
            setTimeout(function() { msg.remove(); }, 300);
        }, 4000);
    });
});
