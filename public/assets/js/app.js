/**
 * Sociaera — Global JavaScript
 * AJAX helpers, modals, compose box, interactions
 */

const App = {
    baseUrl: window.BASE_URL || '',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

    // ── AJAX Helper ───────────────────────────────────────
    async post(url, data = {}) {
        const formData = data instanceof FormData ? data : null;
        const headers = { 'X-CSRF-Token': this.csrfToken, 'X-Requested-With': 'XMLHttpRequest' };

        if (!formData) {
            headers['Content-Type'] = 'application/json';
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers,
                body: formData || JSON.stringify(data),
            });
            return await res.json();
        } catch (e) {
            console.error('API Error:', e);
            return { ok: false, error: 'Bağlantı hatası.' };
        }
    },

    async get(url) {
        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            return await res.json();
        } catch (e) {
            return { ok: false, error: 'Bağlantı hatası.' };
        }
    },

    // ── Flash Message ─────────────────────────────────────
    flash(message, type = 'success') {
        const existing = document.querySelector('.flash-message');
        if (existing) existing.remove();

        const iconMap = { success: 'check-circle-fill', error: 'exclamation-circle-fill', info: 'info-circle-fill' };
        const div = document.createElement('div');
        div.className = `flash-message flash-${type}`;
        div.id = 'flashMessage';
        div.innerHTML = `
            <div class="flash-content">
                <i class="bi bi-${iconMap[type] || 'info-circle-fill'}"></i>
                <span>${message}</span>
            </div>
            <button class="flash-close" onclick="this.closest('.flash-message').remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        document.body.appendChild(div);
        setTimeout(() => div.classList.add('flash-hide'), 4000);
        setTimeout(() => div.remove(), 4500);
    },

    // ── Like Toggle ───────────────────────────────────────
    async toggleLike(btn, checkinId) {
        const icon = btn.querySelector('i');
        const countEl = btn.querySelector('.action-count');
        const isLiked = btn.classList.contains('liked');

        btn.disabled = true;
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', isLiked ? 'unlike' : 'like');
        formData.append('checkin_id', checkinId);

        const res = await this.post(this.baseUrl + '/api/interactions', formData);

        if (res.ok) {
            btn.classList.toggle('liked');
            if (icon) icon.className = btn.classList.contains('liked') ? 'bi bi-heart-fill' : 'bi bi-heart';
            if (countEl) {
                let c = parseInt(countEl.textContent) || 0;
                countEl.textContent = btn.classList.contains('liked') ? c + 1 : Math.max(0, c - 1);
            }
        } else {
            this.flash(res.error || 'Hata oluştu.', 'error');
        }
        btn.disabled = false;
    },

    // ── Repost Toggle ─────────────────────────────────────
    async toggleRepost(btn, checkinId) {
        const isReposted = btn.classList.contains('reposted');
        const countEl = btn.querySelector('.action-count');

        btn.disabled = true;
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', isReposted ? 'unrepost' : 'repost');
        formData.append('checkin_id', checkinId);

        const res = await this.post('api/interactions', formData);

        if (res.ok) {
            btn.classList.toggle('reposted');
            const icon = btn.querySelector('i');
            if (icon) icon.className = btn.classList.contains('reposted') ? 'bi bi-arrow-repeat' : 'bi bi-arrow-repeat';
            if (countEl) {
                let c = parseInt(countEl.textContent) || 0;
                countEl.textContent = btn.classList.contains('reposted') ? c + 1 : Math.max(0, c - 1);
            }
        } else {
            this.flash(res.error || 'Hata oluştu.', 'error');
        }
        btn.disabled = false;
    },

    // ── Follow Toggle ─────────────────────────────────────
    async toggleFollow(btn, userId) {
        const isFollowing = btn.classList.contains('following');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'follow');
        formData.append('user_id', userId);

        const res = await this.post('api/interactions', formData);

        if (res.ok) {
            if (res.data?.following) {
                btn.classList.add('following');
                btn.innerHTML = '<i class="bi bi-person-check-fill"></i> Takip Ediliyor';
                btn.className = btn.className.replace('btn-primary-orange', 'btn-outline-orange');
            } else {
                btn.classList.remove('following');
                btn.innerHTML = '<i class="bi bi-person-plus"></i> Takip Et';
                btn.className = btn.className.replace('btn-outline-orange', 'btn-primary-orange');
            }
        } else {
            this.flash(res.error || 'Hata oluştu.', 'error');
        }
        btn.disabled = false;
    },

    // ── Delete Post ───────────────────────────────────────
    async deletePost(btn, checkinId) {
        if (!confirm('Bu gönderiyi silmek istediğinize emin misiniz?')) return;

        btn.disabled = true;
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'delete');
        formData.append('checkin_id', checkinId);

        const res = await this.post('api/interactions', formData);

        if (res.ok) {
            const card = btn.closest('.post-card');
            if (card) {
                card.style.transition = 'opacity 0.3s, transform 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 300);
            }
            this.flash('Gönderi silindi.', 'success');
        } else {
            this.flash(res.error || 'Hata oluştu.', 'error');
        }
        btn.disabled = false;
    },

    // ── Comment Submit ────────────────────────────────────
    async submitComment(form, checkinId) {
        const input = form.querySelector('.comment-input');
        const comment = input?.value?.trim();
        if (!comment) return;

        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'comment');
        formData.append('checkin_id', checkinId);
        formData.append('comment', comment);

        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput?.files[0]) {
            formData.append('image', fileInput.files[0]);
        }

        const res = await this.post('api/interactions', formData);

        if (res.ok) {
            input.value = '';
            if (fileInput) fileInput.value = '';
            this.flash('Yorum eklendi.', 'success');
            // Yorumları yenile
            if (typeof loadComments === 'function') loadComments(checkinId);
            // Yorum sayısını güncelle
            const countEl = document.querySelector(`[data-comment-count="${checkinId}"]`);
            if (countEl) countEl.textContent = parseInt(countEl.textContent || 0) + 1;
        } else {
            this.flash(res.error || 'Hata oluştu.', 'error');
        }
        if (btn) btn.disabled = false;
    },

    // ── Venue Search (Autocomplete) ───────────────────────
    venueSearchTimer: null,
    initVenueSearch(inputEl, dropdownEl, onSelect) {
        inputEl.addEventListener('input', () => {
            clearTimeout(this.venueSearchTimer);
            const q = inputEl.value.trim();
            if (q.length < 2) { dropdownEl.classList.remove('show'); return; }

            this.venueSearchTimer = setTimeout(async () => {
                const res = await this.get(`${this.baseUrl}/api/venue-search?q=${encodeURIComponent(q)}`);
                if (res.ok && res.data?.length) {
                    dropdownEl.innerHTML = res.data.map(v =>
                        `<div class="venue-picker-item" data-id="${v.id}" data-name="${v.name}">
                            <div class="venue-picker-name">${v.name}</div>
                            <div class="venue-picker-cat">${v.category || ''}</div>
                        </div>`
                    ).join('');
                    dropdownEl.classList.add('show');

                    dropdownEl.querySelectorAll('.venue-picker-item').forEach(item => {
                        item.addEventListener('click', () => {
                            onSelect(item.dataset.id, item.dataset.name);
                            dropdownEl.classList.remove('show');
                            inputEl.value = '';
                        });
                    });
                } else {
                    dropdownEl.classList.remove('show');
                }
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!inputEl.contains(e.target) && !dropdownEl.contains(e.target)) {
                dropdownEl.classList.remove('show');
            }
        });
    },

    // ── User Search (Autocomplete for mentions) ───────────
    userSearchTimer: null,
    async searchUsers(query) {
        if (query.length < 2) return [];
        const res = await this.get(`${this.baseUrl}/api/search-users?q=${encodeURIComponent(query)}`);
        return res.ok ? (res.data || []) : [];
    },

    // ── Compose Box Image Preview ─────────────────────────
    initImagePreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if (!input || !preview) return;

        input.addEventListener('change', () => {
            if (input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button class="remove-preview" onclick="App.clearImagePreview('${inputId}', '${previewId}')" type="button">
                            <i class="bi bi-x"></i>
                        </button>
                    `;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
    },

    clearImagePreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if (input) input.value = '';
        if (preview) { preview.innerHTML = ''; preview.style.display = 'none'; }
    },

    // ── Clear Notifications ───────────────────────────────
    async clearNotifications(btn) {
        btn.disabled = true;
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'clear');

        const res = await this.post(this.baseUrl + '/api/notifications', formData);
        if (res.ok) {
            const list = document.querySelector('.notif-list');
            if (list) list.innerHTML = '<div class="empty-state"><i class="bi bi-bell-slash"></i><p>Bildirim yok</p></div>';
            this.flash('Bildirimler temizlendi.', 'success');
        }
        btn.disabled = false;
    },

    // ── Textarea Auto Resize ──────────────────────────────
    initAutoResize(textarea) {
        if (!textarea) return;
        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
        });
    }
};

// ── DOM Ready ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Auto-resize textareas
    document.querySelectorAll('.compose-textarea, .auto-resize').forEach(el => App.initAutoResize(el));

    // Image previews
    App.initImagePreview('composeImage', 'composePreview');
});
