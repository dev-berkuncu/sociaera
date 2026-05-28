/**
 * Sociaera — Global JavaScript
 * AJAX helpers, modals, compose box, interactions
 */

// ── XSS Prevention Helper ─────────────────────────────────
function escapeHtml(str) {
    if (str == null) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

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
                credentials: 'same-origin',
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
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
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
                <i class="bi bi-${escapeHtml(iconMap[type] || 'info-circle-fill')}"></i>
                <span>${escapeHtml(message)}</span>
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
            btn.classList.toggle('text-primary-container');
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

        const res = await this.post(this.baseUrl + '/api/interactions', formData);

        if (res.ok) {
            btn.classList.toggle('reposted');
            btn.classList.toggle('text-primary-container');
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

        const res = await this.post(this.baseUrl + '/api/interactions', formData);

        if (res.ok) {
            if (res.data?.following) {
                btn.classList.add('following');
                btn.textContent = 'Takip Ediliyor';
                btn.classList.remove('bg-primary-container', 'text-white');
                btn.classList.add('bg-white/10', 'text-slate-300', 'border-white/10');
            } else {
                btn.classList.remove('following');
                btn.textContent = 'Takip Et';
                btn.classList.remove('bg-white/10', 'text-slate-300', 'border-white/10');
                btn.classList.add('bg-primary-container', 'text-white');
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

        const res = await this.post(this.baseUrl + '/api/interactions', formData);

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

        const res = await this.post(this.baseUrl + '/api/interactions', formData);

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
            if (q.length < 2) { dropdownEl.style.display = 'none'; return; }

            this.venueSearchTimer = setTimeout(async () => {
                console.log('[VenueSearch] Searching:', q);
                const res = await this.get(`${this.baseUrl}/api/venue-search?q=${encodeURIComponent(q)}`);
                console.log('[VenueSearch] Response:', res);
                if (res.ok && res.data?.length) {
                    dropdownEl.innerHTML = res.data.map(v =>
                        `<div class="venue-picker-item" data-id="${escapeHtml(v.id)}" data-name="${escapeHtml(v.name)}" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.05);">
                            <div style="font-weight:500;color:#fff;">${escapeHtml(v.name)}</div>
                            <div style="font-size:0.8rem;color:#94a3b8;">${escapeHtml(v.category || '')}</div>
                        </div>`
                    ).join('');
                    dropdownEl.style.display = 'block';
                    console.log('[VenueSearch] Showing', res.data.length, 'results');

                    dropdownEl.querySelectorAll('.venue-picker-item').forEach(item => {
                        item.addEventListener('click', () => {
                            onSelect(item.dataset.id, item.dataset.name);
                            dropdownEl.style.display = 'none';
                            inputEl.value = '';
                        });
                    });
                } else {
                    dropdownEl.innerHTML = '<div style="padding:12px;text-align:center;color:#94a3b8;font-size:0.85rem;">Sonuç bulunamadı</div>';
                    dropdownEl.style.display = 'block';
                    console.log('[VenueSearch] No results');
                }
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!inputEl.contains(e.target) && !dropdownEl.contains(e.target)) {
                dropdownEl.style.display = 'none';
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
                        <img src="${e.target.result}" alt="Preview" style="display:block; width:100%; max-width:100%; height:auto; max-height:300px; object-fit:contain; border-radius:8px;">
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
    },

    // ── Inline Comments Toggle ────────────────────────────
    toggleComments(btn, checkinId) {
        const section = document.getElementById(`comments-section-${checkinId}`);
        if (!section) return;

        if (section.style.display === 'none') {
            section.style.display = 'block';
            btn.classList.add('active-comment');
            this.loadComments(checkinId);
            // Focus input
            setTimeout(() => {
                const input = section.querySelector('.comment-input-inline');
                if (input) input.focus();
            }, 100);
        } else {
            section.style.display = 'none';
            btn.classList.remove('active-comment');
        }
    },

    // ── Load Comments via AJAX ────────────────────────────
    async loadComments(checkinId) {
        const listEl = document.getElementById(`comments-list-${checkinId}`);
        if (!listEl) return;

        const res = await this.get(this.baseUrl + `/api/interactions?action=get_comments&checkin_id=${checkinId}`);

        if (res.ok && res.data) {
            if (res.data.length === 0) {
                listEl.innerHTML = '<div style="text-align:center; padding:16px; color:var(--text-muted); font-size:0.85rem;">Henüz yorum yok. İlk yorumu sen yaz!</div>';
            } else {
                listEl.innerHTML = res.data.map(c => this.renderComment(c)).join('');
            }
        } else {
            listEl.innerHTML = '<div style="text-align:center; padding:16px; color:var(--text-muted);">Yorumlar yüklenemedi.</div>';
        }
    },

    renderComment(c) {
        const safeAvatar = escapeHtml(c.avatar);
        const safeUsername = escapeHtml(c.username || 'U');
        const safeTag = escapeHtml(c.tag || c.username);
        const safeComment = escapeHtml(c.comment);
        const safeTimeAgo = escapeHtml(c.time_ago || '');
        const avatar = c.avatar
            ? `<img src="${this.baseUrl}/uploads/avatars/${safeAvatar}" class="avatar-img" width="28" height="28" style="border-radius:50%; object-fit:cover;">`
            : `<div class="avatar-default" style="width:28px; height:28px; font-size:0.7rem;">${safeUsername[0].toUpperCase()}</div>`;
        return `
            <div class="comment-item">
                <a href="${this.baseUrl}/profile?u=${encodeURIComponent(safeTag)}">${avatar}</a>
                <div class="comment-body">
                    <a href="${this.baseUrl}/profile?u=${encodeURIComponent(safeTag)}" class="comment-author">${safeUsername}</a>
                    <span class="comment-text">${safeComment}</span>
                    <span class="comment-time">${safeTimeAgo}</span>
                </div>
            </div>
        `;
    },

    // ── Submit Inline Comment ─────────────────────────────
    async submitInlineComment(form, checkinId) {
        const input = form.querySelector('.comment-input-inline');
        const comment = input?.value?.trim();
        if (!comment) return;

        const btn = form.querySelector('.comment-send-btn');
        if (btn) btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'comment');
        formData.append('checkin_id', checkinId);
        formData.append('comment', comment);

        const res = await this.post(this.baseUrl + '/api/interactions', formData);

        if (res.ok) {
            input.value = '';
            this.loadComments(checkinId);
            // Yorum sayısını güncelle
            const countEl = document.querySelector(`[data-comment-count="${checkinId}"]`);
            if (countEl) countEl.textContent = parseInt(countEl.textContent || 0) + 1;
        } else {
            this.flash(res.error || 'Hata oluştu.', 'error');
        }
        if (btn) btn.disabled = false;
    },

    // ── Report Modal ─────────────────────────────────────
    openReportModal(entityType, entityId) {
        const modal = document.getElementById('reportModal');
        if (!modal) return;
        document.getElementById('report_entity_type').value = entityType;
        document.getElementById('report_entity_id').value = entityId;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    },

    closeReportModal() {
        const modal = document.getElementById('reportModal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        // Reset form
        const form = document.getElementById('reportForm');
        if (form) form.reset();
    },

    async submitReport(e) {
        e.preventDefault();
        const btn = document.getElementById('reportSubmitBtn');
        if (btn) btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('entity_type', document.getElementById('report_entity_type').value);
        formData.append('entity_id', document.getElementById('report_entity_id').value);

        const reason = document.querySelector('#reportForm input[name="reason"]:checked');
        if (!reason) {
            this.flash('Lütfen bir neden seçin.', 'error');
            if (btn) btn.disabled = false;
            return;
        }
        formData.append('reason', reason.value);
        formData.append('description', document.getElementById('report_description')?.value || '');

        const res = await this.post(this.baseUrl + '/api/report', formData);

        if (res.ok) {
            this.closeReportModal();
            this.flash(res.message || 'Raporunuz alındı. Teşekkürler!', 'success');
        } else {
            this.flash(res.error || 'Rapor gönderilemedi.', 'error');
        }
        if (btn) btn.disabled = false;
    }
};

// ── DOM Ready ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Auto-resize textareas
    document.querySelectorAll('.compose-textarea, .auto-resize').forEach(el => App.initAutoResize(el));

    // Image previews
    App.initImagePreview('composeImage', 'composePreview');
});
