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

        const bgMap = { success: '#16a34a', error: '#ef4444', info: '#2563EB' };
        const iconMap = { success: 'check_circle', error: 'error', info: 'info' };
        const div = document.createElement('div');
        div.id = 'flashMessage';
        div.style.cssText = [
            'position:fixed',
            'top:76px',
            'right:20px',
            'z-index:99999',
            `background:${bgMap[type] || '#1a1a1a'}`,
            'color:#fff',
            'padding:12px 16px',
            'border-radius:12px',
            'display:flex',
            'align-items:center',
            'gap:10px',
            'max-width:340px',
            'width:auto',
            'min-width:160px',
            'font-size:13px',
            'font-weight:600',
            'font-family:inherit',
            'box-shadow:0 8px 24px rgba(0,0,0,0.22)',
            'animation:slideInRight 0.3s ease forwards',
            'transition:opacity .3s',
        ].join(';');
        div.innerHTML = `
            <span class="material-symbols-outlined" style="font-size:18px;flex-shrink:0;font-variation-settings:'FILL' 1;">${escapeHtml(iconMap[type] || 'info')}</span>
            <span style="flex:1;line-height:1.4;">${escapeHtml(message)}</span>
            <button onclick="this.parentElement.remove()" style="background:none;border:none;color:rgba(255,255,255,0.8);cursor:pointer;padding:0;margin-left:4px;display:flex;align-items:center;flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:18px;">close</span>
            </button>
        `;
        document.body.appendChild(div);
        setTimeout(() => { div.style.opacity = '0'; }, 4000);
        setTimeout(() => div.remove(), 4400);
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
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">person_check</span> Takip Ediliyor';
                btn.style.background = '#F8F7F5';
                btn.style.color = '#5C5C5C';
                btn.style.borderColor = '#E8E7E3';
            } else {
                btn.classList.remove('following');
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">person_add</span> Takip Et';
                btn.style.background = '#F06D1F';
                btn.style.color = '#fff';
                btn.style.borderColor = '#F06D1F';
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
            const card = btn.closest('.checkin-card') || btn.closest('.post-card') || btn.closest('article');
            if (card) {
                const h = card.offsetHeight;
                card.style.overflow = 'hidden';
                card.style.transition = 'opacity 0.25s ease, transform 0.25s ease, max-height 0.35s ease, margin 0.35s ease, padding 0.35s ease';
                card.style.maxHeight = h + 'px';
                // Force reflow so the browser registers the initial max-height
                card.offsetHeight; // eslint-disable-line no-unused-expressions
                requestAnimationFrame(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.97)';
                    card.style.maxHeight = '0';
                    card.style.marginTop = '0';
                    card.style.marginBottom = '0';
                    card.style.paddingTop = '0';
                    card.style.paddingBottom = '0';
                });
                setTimeout(() => card.remove(), 380);
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
                const res = await this.get(`${this.baseUrl}/api/venue-search?q=${encodeURIComponent(q)}`);
                if (res.ok && res.data?.length) {
                    dropdownEl.style.cssText = 'display:block;position:absolute;left:0;right:0;top:100%;z-index:400;background:#fff;border:1.5px solid #E8E7E3;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow:hidden;margin-top:4px;';
                    dropdownEl.innerHTML = res.data.map(v =>
                        `<div class="venue-picker-item" data-id="${escapeHtml(v.id)}" data-name="${escapeHtml(v.name)}" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #F2F1EE;display:flex;align-items:center;gap:8px;transition:background .1s;" onmouseover="this.style.background='#F8F7F5'" onmouseout="this.style.background=''">
                            <span class="material-symbols-outlined" style="font-size:18px;color:#F06D1F;">place</span>
                            <div><div style="font-weight:600;font-size:13px;color:#1A1A1A;">${escapeHtml(v.name)}</div><div style="font-size:11px;color:#A0A0A0;">${escapeHtml(v.category || '')}</div></div>
                        </div>`
                    ).join('');
                    dropdownEl.querySelectorAll('.venue-picker-item').forEach(item => {
                        item.addEventListener('click', () => {
                            onSelect(item.dataset.id, item.dataset.name);
                            dropdownEl.style.display = 'none';
                            inputEl.value = '';
                        });
                    });
                } else {
                    dropdownEl.style.cssText = 'display:block;position:absolute;left:0;right:0;top:100%;z-index:400;background:#fff;border:1.5px solid #E8E7E3;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow:hidden;margin-top:4px;';
                    dropdownEl.innerHTML = `
                        <div style="padding:16px;text-align:center;display:flex;flex-direction:column;gap:8px;align-items:center;">
                            <span style="color:#A0A0A0;font-size:13px;">Aradığınız mekan bulunamadı.</span>
                            <a href="${App.baseUrl}/add-venue" style="display:inline-flex;align-items:center;gap:4px;background:#10b981;color:#fff;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
                                <span class="material-symbols-outlined" style="font-size:16px;">add_location_alt</span>
                                Yeni Mekan Ekle
                            </a>
                        </div>
                    `;
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
        if (input) {
            input.value = '';
            input.dispatchEvent(new Event('change'));
        }
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
            if (list) list.innerHTML = '<div class="empty-state" style="text-align:center;padding:2rem;color:#64748b;"><span class="material-symbols-outlined" style="font-size:2rem;">notifications_off</span><p style="margin-top:8px;">Bildirim yok</p></div>';
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
        const safeUsername = escapeHtml(c.username || 'U');
        const safeTag = escapeHtml(c.tag || c.username);
        const safeComment = escapeHtml(c.comment);
        const safeTimeAgo = escapeHtml(c.time_ago || '');
        const avatarUrl = escapeHtml(c.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(c.username||'U')}&background=F06D1F&color=fff&size=32&bold=true`);
        
        return `
            <div style="display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid #F2F1EE;">
                <img src="${avatarUrl}" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;" />
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <span style="font-weight:700;font-size:12px;color:#1A1A1A;">${safeUsername}</span>
                        <span style="font-size:11px;color:#A0A0A0;">@${safeTag}</span>
                        <span style="font-size:10px;color:#A0A0A0;margin-left:auto;">${safeTimeAgo}</span>
                    </div>
                    <p style="font-size:12px;color:#5C5C5C;margin:3px 0 0;line-height:1.4;">${safeComment}</p>
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
        modal.style.display = 'block';  // footer uses display:none inline
        document.body.style.overflow = 'hidden';
    },

    closeReportModal() {
        const modal = document.getElementById('reportModal');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
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

    // Smooth interaction scale for post cards, aside modules, and buttons
    document.querySelectorAll('.post-card, aside > div, button, .btn-glow').forEach(el => {
        el.addEventListener('mousedown', () => {
            el.style.transform = 'scale(0.97)';
            el.style.transition = 'transform 0.1s ease';
        });
        el.addEventListener('mouseup', () => {
            el.style.transform = '';
        });
        el.addEventListener('mouseleave', () => {
            el.style.transform = '';
        });
    });
});
