/* ============================================
   STITCH INTERACTIONS — Sociaera
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {

    // ── CLS Guard: show body when fonts ready ──
    var t = setTimeout(function(){ document.body.classList.add('ready'); }, 3000);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(function(){ clearTimeout(t); document.body.classList.add('ready'); });
    } else {
        clearTimeout(t);
        document.body.classList.add('ready');
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
