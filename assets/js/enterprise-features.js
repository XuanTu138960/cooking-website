/**
 * Cookio Enterprise Features (Cookpad / Tasty Grade)
 * - Live Instant Search
 * - AJAX 1-Click Like Reaction
 * - "Hôm Nay Ăn Gì?" Interactive Decider Modal
 * - Add to Custom Cookbook Modal
 */

document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    initLiveSearch();
    initAjaxLikes();
    initMealDecider();
    initNotifications();
});

/* ==========================================================================
   1. LIVE AJAX INSTANT SEARCH
   ========================================================================== */
function initLiveSearch() {
    const searchInput = document.querySelector('.header-search input[name="q"]');
    if (!searchInput) return;

    const searchForm = searchInput.closest('form');
    if (!searchForm) return;

    // Create results dropdown container
    let dropdown = document.querySelector('.header-search-results');
    if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.className = 'header-search-results';
        dropdown.style.display = 'none';
        searchForm.style.position = 'relative';
        searchForm.appendChild(dropdown);
    }

    let debounceTimer = null;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 1) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchQuickSearch(query, dropdown);
        }, 250);
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchForm.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });
}

async function fetchQuickSearch(query, dropdown) {
    try {
        const baseUrl = window.BASE_URL || '';
        const res = await fetch(`${baseUrl}/actions/quick_search_api.php?action=search&q=${encodeURIComponent(query)}`);
        const data = await res.json();

        if (!data.success || !data.results || data.results.length === 0) {
            dropdown.innerHTML = `
                <div class="search-empty-state">
                    <span>🔍</span> Không tìm thấy món phù hợp với "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            dropdown.style.display = 'block';
            return;
        }

        let html = '<div class="search-results-header">Gợi ý món ăn nhanh</div><div class="search-results-list">';
        data.results.forEach(r => {
            const thumb = r.image_url 
                ? `<img src="${r.image_url}" alt="${escapeHtml(r.title)}" class="search-item-thumb">`
                : `<div class="search-item-thumb-placeholder">🍲</div>`;
            const cal = r.calories ? `<span class="search-item-cal">${r.calories} kcal</span>` : '';

            html += `
                <a href="${r.url}" class="search-item">
                    ${thumb}
                    <div class="search-item-info">
                        <div class="search-item-title">${escapeHtml(r.title)}</div>
                        <div class="search-item-meta">
                            <span>⏱️ ${escapeHtml(r.cooking_time)}</span>
                            <span>📂 ${escapeHtml(r.category)}</span>
                            ${cal}
                        </div>
                    </div>
                    <span class="search-item-arrow">&rarr;</span>
                </a>
            `;
        });
        html += '</div>';
        dropdown.innerHTML = html;
        dropdown.style.display = 'block';
    } catch (err) {
        console.error('Quick search error:', err);
    }
}

/* ==========================================================================
   2. AJAX 1-CLICK LIKE REACTION
   ========================================================================== */
function initAjaxLikes() {
    document.addEventListener('click', async (e) => {
        const likeBtn = e.target.closest('.js-like-btn');
        if (!likeBtn) return;

        e.preventDefault();
        e.stopPropagation();

        const recipeId = likeBtn.dataset.recipeId;
        const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        const baseUrl = window.BASE_URL || '';

        if (!recipeId) return;

        // Visual bounce animation
        likeBtn.classList.add('heart-bouncing');
        setTimeout(() => likeBtn.classList.remove('heart-bouncing'), 400);

        try {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('recipe_id', recipeId);
            formData.append('csrf_token', csrfToken);

            const res = await fetch(`${baseUrl}/actions/like_action.php`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.status === 401) {
                // Not logged in -> trigger login modal
                if (typeof openModal === 'function') {
                    openModal('loginModal');
                } else {
                    const loginM = document.getElementById('loginModal');
                    if (loginM) loginM.style.display = 'flex';
                }
                if (typeof showToast === 'function') {
                    showToast('Vui lòng đăng nhập để thả tim món ăn!', 'warning');
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                // Update button UI
                if (data.liked) {
                    likeBtn.classList.add('is-liked');
                } else {
                    likeBtn.classList.remove('is-liked');
                }

                // Update count text
                const countSpan = likeBtn.querySelector('.like-count');
                if (countSpan) {
                    countSpan.textContent = data.likes_count;
                }

                if (typeof showToast === 'function') {
                    showToast(data.message, data.liked ? 'success' : 'info');
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Không thể thực hiện', 'warning');
                }
            }
        } catch (err) {
            console.error('Like error:', err);
        }
    });
}

/* ==========================================================================
   3. "HÔM NAY ĂN GÌ?" (MEAL DECIDER & RANDOMIZER MODAL)
   ========================================================================== */
function initMealDecider() {
    // Inject modal if not present
    if (!document.getElementById('mealDeciderModal')) {
        const modalHtml = `
            <div id="mealDeciderModal" class="modal-backdrop" style="display: none;">
                <div class="modal-dialog meal-decider-dialog">
                    <div class="modal-header">
                        <h3 class="modal-title">🎲 Hôm Nay Ăn Gì?</h3>
                        <button type="button" class="modal-close" onclick="closeMealDecider()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p class="meal-decider-subtitle">Băn khoăn chưa biết nấu món gì cho hôm nay? Chọn gu ẩm thực và để Cookio gợi ý ngay cho bạn nhé!</p>
                        
                        <div class="meal-mood-tabs mb-4">
                            <button type="button" class="mood-tab is-active" data-mood="any">✨ Bất kỳ</button>
                            <button type="button" class="mood-tab" data-mood="quick">⚡ Nhanh < 30p</button>
                            <button type="button" class="mood-tab" data-mood="healthy">🥗 Eat Clean / Ít Calo</button>
                            <button type="button" class="mood-tab" data-mood="comfort">🍲 Canh / Ấm Bụng</button>
                        </div>

                        <div id="mealDeciderResult" class="meal-decider-result">
                            <div class="meal-decider-placeholder">
                                <div class="meal-decider-icon">🥘</div>
                                <p>Bấm nút "Quay Món Ngẫu Nhiên" để bắt đầu!</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="text-muted" style="font-size: 0.85rem;">Từ kho hơn 1,000+ món ngon Cookio</span>
                        <button type="button" id="btnRollMeal" class="button button-primary" onclick="rollRandomMeal()">
                            🎲 Gợi ý món khác
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Bind mood tabs
        document.querySelectorAll('#mealDeciderModal .mood-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('#mealDeciderModal .mood-tab').forEach(t => t.classList.remove('is-active'));
                tab.classList.add('is-active');
                rollRandomMeal();
            });
        });
    }
}

window.openMealDeciderModal = function() {
    const modal = document.getElementById('mealDeciderModal');
    if (modal) {
        modal.style.display = 'flex';
        // Auto-roll first time if empty
        const resBox = document.getElementById('mealDeciderResult');
        if (resBox && resBox.querySelector('.meal-decider-placeholder')) {
            rollRandomMeal();
        }
    }
};

window.closeMealDecider = function() {
    const modal = document.getElementById('mealDeciderModal');
    if (modal) modal.style.display = 'none';
};

window.rollRandomMeal = async function() {
    const btn = document.getElementById('btnRollMeal');
    const resBox = document.getElementById('mealDeciderResult');
    const activeTab = document.querySelector('#mealDeciderModal .mood-tab.is-active');
    const mood = activeTab ? activeTab.dataset.mood : 'any';
    const baseUrl = window.BASE_URL || '';

    if (btn) btn.disabled = true;
    resBox.innerHTML = `
        <div class="meal-decider-loading">
            <div class="spinner-roller">🍳</div>
            <p>Đang chọn món ngon hợp vị cho bạn...</p>
        </div>
    `;

    try {
        const res = await fetch(`${baseUrl}/actions/quick_search_api.php?action=random_meal&mood=${mood}`);
        const data = await res.json();

        setTimeout(() => {
            if (btn) btn.disabled = false;
            if (data.success && data.recipe) {
                const r = data.recipe;
                const thumb = r.image_url 
                    ? `<img src="${r.image_url}" alt="${escapeHtml(r.title)}" class="decider-recipe-img">`
                    : `<div class="decider-recipe-img-placeholder">🍲</div>`;
                const calBadge = r.calories ? `<span class="badge-cal-pill">🔥 ${r.calories} kcal</span>` : '';
                const tagsBadge = r.dietary_tags ? `<span class="badge-diet-pill">🌿 ${escapeHtml(r.dietary_tags)}</span>` : '';

                resBox.innerHTML = `
                    <div class="decider-card animate-fadeIn">
                        <div class="decider-card-img-wrap">
                            ${thumb}
                            <span class="decider-card-cat">${escapeHtml(r.category)}</span>
                        </div>
                        <div class="decider-card-content">
                            <div class="decider-card-tags">${calBadge} ${tagsBadge}</div>
                            <h3 class="decider-card-title">${escapeHtml(r.title)}</h3>
                            <p class="decider-card-desc">${escapeHtml(r.description || 'Món ngon đậm vị gia đình, cách làm chi tiết dễ thực hiện.')}</p>
                            <div class="decider-card-meta">
                                <span>⏱️ ${escapeHtml(r.cooking_time)}</span>
                                <span>👥 ${escapeHtml(r.servings)}</span>
                                <span>👨‍🍳 Bởi ${escapeHtml(r.author_name)}</span>
                            </div>
                            <div class="decider-card-actions">
                                <a href="${r.url}" class="button button-primary" style="flex: 1; text-align: center;">
                                    👩‍🍳 Xem Công Thức & Vào Bếp
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                resBox.innerHTML = `<div class="p-4 text-center text-muted">${data.message || 'Chưa tìm thấy món phù hợp.'}</div>`;
            }
        }, 300);
    } catch (err) {
        if (btn) btn.disabled = false;
        console.error('Decider error:', err);
    }
};

/* ==========================================================================
   4. ADD TO CUSTOM COOKBOOK MODAL
   ========================================================================== */
window.openAddToCookbookModal = async function(recipeId) {
    let modal = document.getElementById('addToCookbookModal');
    if (!modal) {
        const html = `
            <div id="addToCookbookModal" class="modal-backdrop" style="display: none;">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3 class="modal-title">📚 Lưu Vào Sổ Tay Ẩm Thực</h3>
                        <button type="button" class="modal-close" onclick="closeAddToCookbookModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-4" style="font-size: 0.9rem;">Chọn các sổ tay của bạn để thêm món ăn này vào, hoặc tạo ngay sổ tay mới.</p>
                        <div id="cookbookListContainer" class="cookbook-modal-list mb-4">
                            <div class="text-center py-4 text-muted">Đang tải danh sách sổ tay...</div>
                        </div>
                        <div class="quick-create-cookbook-box">
                            <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem;">➕ Tạo sổ tay mới</div>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" id="newCookbookTitleInput" class="form-control" placeholder="Tên sổ tay mới (VD: Món đãi tiệc...)" style="flex: 1;">
                                <button type="button" class="button button-primary" onclick="quickCreateCookbook(${recipeId})">Tạo & Lưu</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);
        modal = document.getElementById('addToCookbookModal');
    }

    modal.style.display = 'flex';
    loadUserCookbooksForModal(recipeId);
};

window.closeAddToCookbookModal = function() {
    const modal = document.getElementById('addToCookbookModal');
    if (modal) modal.style.display = 'none';
};

async function loadUserCookbooksForModal(recipeId) {
    const container = document.getElementById('cookbookListContainer');
    const baseUrl = window.BASE_URL || '';

    try {
        const res = await fetch(`${baseUrl}/actions/cookbook_action.php?action=list_for_recipe&recipe_id=${recipeId}`);
        if (res.status === 401) {
            container.innerHTML = `
                <div class="p-4 text-center">
                    <p class="mb-3">Vui lòng đăng nhập để sử dụng tính năng Sổ tay ẩm thực!</p>
                    <button type="button" class="button button-primary" onclick="closeAddToCookbookModal(); if (typeof openModal === 'function') openModal('loginModal');">Đăng nhập ngay</button>
                </div>
            `;
            return;
        }

        const data = await res.json();
        if (data.success) {
            if (!data.cookbooks || data.cookbooks.length === 0) {
                container.innerHTML = `<div class="p-3 text-center text-muted" style="background: var(--color-surface); border-radius: 0.5rem;">Bạn chưa có sổ tay nào. Hãy nhập tên ở bên dưới để tạo ngay nhé!</div>`;
                return;
            }

            let listHtml = '';
            data.cookbooks.forEach(cb => {
                const checked = cb.has_recipe == 1;
                listHtml += `
                    <div class="cookbook-modal-item ${checked ? 'is-included' : ''}" onclick="toggleRecipeInCookbook(${cb.id}, ${recipeId}, this)">
                        <div class="cookbook-modal-item-info">
                            <span class="cookbook-modal-item-title">${escapeHtml(cb.title)}</span>
                            <span class="cookbook-modal-item-meta">${cb.total_recipes} món</span>
                        </div>
                        <span class="cookbook-check-icon">${checked ? '✅ Đã lưu' : '➕ Thêm'}</span>
                    </div>
                `;
            });
            container.innerHTML = listHtml;
        }
    } catch (err) {
        console.error('Error loading cookbooks:', err);
    }
}

window.toggleRecipeInCookbook = async function(cookbookId, recipeId, itemEl) {
    const baseUrl = window.BASE_URL || '';
    const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';

    const formData = new FormData();
    formData.append('action', 'toggle_recipe');
    formData.append('cookbook_id', cookbookId);
    formData.append('recipe_id', recipeId);
    formData.append('csrf_token', csrfToken);
    formData.append('ajax', '1');

    try {
        const res = await fetch(`${baseUrl}/actions/cookbook_action.php`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            if (data.added) {
                itemEl.classList.add('is-included');
                itemEl.querySelector('.cookbook-check-icon').textContent = '✅ Đã lưu';
            } else {
                itemEl.classList.remove('is-included');
                itemEl.querySelector('.cookbook-check-icon').textContent = '➕ Thêm';
            }
            if (typeof showToast === 'function') {
                showToast(data.message, data.added ? 'success' : 'info');
            }
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Lỗi xử lý', 'warning');
            }
        }
    } catch (err) {
        console.error('Toggle recipe error:', err);
    }
};

window.quickCreateCookbook = async function(recipeId) {
    const input = document.getElementById('newCookbookTitleInput');
    const title = input ? input.value.trim() : '';
    if (!title) {
        if (typeof showToast === 'function') showToast('Vui lòng nhập tên sổ tay!', 'warning');
        return;
    }

    const baseUrl = window.BASE_URL || '';
    const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';

    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('title', title);
    formData.append('recipe_id', recipeId);
    formData.append('csrf_token', csrfToken);
    formData.append('ajax', '1');

    try {
        const res = await fetch(`${baseUrl}/actions/cookbook_action.php`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            if (input) input.value = '';
            if (typeof showToast === 'function') showToast(data.message, 'success');
            loadUserCookbooksForModal(recipeId);
        } else {
            if (typeof showToast === 'function') showToast(data.message || 'Không thể tạo sổ tay', 'warning');
        }
    } catch (err) {
        console.error('Quick create cookbook error:', err);
    }
};

// Helper: Escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
/* ==========================================================================
   5. DARK MODE TOGGLE & PERSISTENCE
   ========================================================================== */
function initDarkMode() {
    const savedTheme = localStorage.getItem('cookio_theme');
    const toggleBtn = document.getElementById('btnToggleDarkMode');

    const setTheme = (theme) => {
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('cookio_theme', 'dark');
            if (toggleBtn) toggleBtn.querySelector('.theme-icon').textContent = '☀️';
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('cookio_theme', 'light');
            if (toggleBtn) toggleBtn.querySelector('.theme-icon').textContent = '🌙';
        }
    };

    // Apply saved theme
    if (savedTheme === 'dark') {
        setTheme('dark');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            setTheme(current === 'dark' ? 'light' : 'dark');
        });
    }
}

/* ==========================================================================
   6. SOCIAL NOTIFICATIONS CENTER
   ========================================================================== */
function initNotifications() {
    const notifBtn = document.getElementById('btnNotifications');
    const notifDropdown = document.getElementById('notificationDropdown');
    const notifBadge = document.getElementById('notifBadge');
    const notifList = document.getElementById('notificationList');

    if (!notifBtn || !notifDropdown) return;

    // Toggle dropdown
    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = notifDropdown.style.display === 'block';
        notifDropdown.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) {
            loadNotifications();
        }
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.style.display = 'none';
        }
    });

    // Initial load
    loadNotifications();
}

window.currentNotifFilter = 'all';

async function loadNotifications(filterType = window.currentNotifFilter || 'all') {
    window.currentNotifFilter = filterType;
    const baseUrl = window.BASE_URL || '';
    const notifBadge = document.getElementById('notifBadge');
    const notifList = document.getElementById('notificationList');

    try {
        const res = await fetch(`${baseUrl}/actions/notification_action.php?action=list&type=${encodeURIComponent(filterType)}`);
        if (res.status === 401) return; // not logged in

        const data = await res.json();
        if (data.success) {
            // Update badge
            if (notifBadge && filterType === 'all') {
                if (data.unread_count > 0) {
                    notifBadge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    notifBadge.style.display = 'flex';
                } else {
                    notifBadge.style.display = 'none';
                }
            }

            // Render list
            if (notifList) {
                if (!data.notifications || data.notifications.length === 0) {
                    notifList.innerHTML = `<div style="padding: 2rem 1rem; text-align: center; color: #94a3b8; font-size: 0.88rem;">Chưa có thông báo nào trong mục này.</div>`;
                    return;
                }

                let html = '';
                data.notifications.forEach(n => {
                    html += `
                        <a href="${n.target_url}" class="notification-item ${!n.is_read ? 'is-unread' : ''}" onclick="markNotificationRead(${n.id})">
                            <span class="notif-icon">${n.icon}</span>
                            <div class="notif-body">
                                <strong>${escapeHtml(n.actor_name)}</strong> ${escapeHtml(n.content)}
                                <span class="notif-time">🕒 ${escapeHtml(n.created_at)}</span>
                            </div>
                        </a>
                    `;
                });
                notifList.innerHTML = html;
            }
        }
    } catch (err) {
        console.error('Notification load error:', err);
    }
}

window.filterNotifications = function(type, btnEl) {
    document.querySelectorAll('.notif-tab').forEach(el => el.classList.remove('is-active'));
    if (btnEl) btnEl.classList.add('is-active');
    loadNotifications(type);
};

window.markNotificationRead = async function(notifId) {
    const baseUrl = window.BASE_URL || '';
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('notif_id', notifId);
    try {
        await fetch(`${baseUrl}/actions/notification_action.php`, {
            method: 'POST',
            body: formData
        });
    } catch (err) {
        console.error('Mark read error:', err);
    }
};

window.markAllNotificationsRead = async function() {
    const baseUrl = window.BASE_URL || '';
    const formData = new FormData();
    formData.append('action', 'mark_all_read');
    try {
        const res = await fetch(`${baseUrl}/actions/notification_action.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            const notifBadge = document.getElementById('notifBadge');
            if (notifBadge) notifBadge.style.display = 'none';
            document.querySelectorAll('.notification-item.is-unread').forEach(el => el.classList.remove('is-unread'));
            if (typeof showToast === 'function') showToast('Đã đánh dấu đọc tất cả thông báo!', 'success');
        }
    } catch (err) {
        console.error('Mark all read error:', err);
    }
};

/* ==========================================================================
   7. NESTED COMMENT REPLY TOGGLER
   ========================================================================== */
window.toggleReplyForm = function(commentId) {
    const formBox = document.getElementById(`reply-box-${commentId}`);
    if (formBox) {
        const isOpen = formBox.style.display === 'block';
        formBox.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) {
            const input = formBox.querySelector('textarea, input[type="text"]');
            if (input) input.focus();
        }
    }
};

/* ==========================================================================
   PHASE 5: PERSONAL CHEF NOTES & GROCERY SHOPPING CHECKLIST
   ========================================================================== */

window.saveRecipeNote = async function(recipeId, btnEl) {
    const box = document.getElementById(`personal-note-box-${recipeId}`);
    if (!box) return;
    const textarea = box.querySelector('.personal-note-textarea');
    if (!textarea) return;
    const note = textarea.value.trim();
    const originalText = btnEl.textContent;
    btnEl.disabled = true;
    btnEl.textContent = 'Đang lưu...';

    const baseUrl = window.BASE_URL || '';
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrf = csrfMeta ? csrfMeta.content : '';

    const formData = new FormData();
    formData.append('action', 'save_note');
    formData.append('recipe_id', recipeId);
    formData.append('note', note);
    formData.append('csrf_token', csrf);
    formData.append('ajax', '1');

    try {
        const res = await fetch(`${baseUrl}/actions/saved_action.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            btnEl.textContent = '✓ Đã lưu!';
            btnEl.style.background = '#16a34a';
            if (typeof showToast === 'function') {
                showToast('Đã lưu ghi chú bếp thành công!', 'success');
            }
            setTimeout(() => {
                btnEl.disabled = false;
                btnEl.textContent = 'Lưu ghi chú';
                btnEl.style.background = '';
            }, 2000);
        } else {
            throw new Error(data.message || 'Lỗi');
        }
    } catch (err) {
        console.error('Save note error:', err);
        btnEl.disabled = false;
        btnEl.textContent = originalText;
        if (typeof showToast === 'function') {
            showToast('Không thể lưu ghi chú. Vui lòng thử lại!', 'error');
        }
    }
};

window.openGroceryModal = function(ingredientsText, title = 'Danh Sách Đi Chợ') {
    let modal = document.getElementById('groceryListModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'groceryListModal';
        modal.className = 'modal-overlay';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;';
        modal.innerHTML = `
            <div class="modal-dialog" style="max-width: 520px; width: 92%; background: #ffffff; border-radius: 1.25rem; padding: 1.75rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
                    <h3 id="groceryModalTitle" style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                        📋 Danh Sách Đi Chợ
                    </h3>
                    <button type="button" onclick="closeGroceryModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">&times;</button>
                </div>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 0; margin-bottom: 0.85rem;">
                    Tích chọn những món bạn đã có hoặc đã mua tại chợ / siêu thị:
                </p>
                <div class="grocery-checklist-container" id="groceryChecklistBody"></div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.25rem; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                    <button type="button" class="button button-outline" onclick="copyGroceryChecklist()">
                        📋 Sao chép danh sách
                    </button>
                    <button type="button" class="button button-create" onclick="closeGroceryModal()">
                        Xong
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const titleEl = document.getElementById('groceryModalTitle');
    if (titleEl) titleEl.innerHTML = `📋 ${escapeHtml(title)}`;

    const container = document.getElementById('groceryChecklistBody');
    if (container) {
        const rawLines = ingredientsText.split(/\r?\n/).map(l => l.trim()).filter(l => l.length > 0);
        const unique = Array.from(new Set(rawLines));
        let html = '';
        unique.forEach((line, idx) => {
            html += `
                <div class="grocery-item-row" id="grocery-item-${idx}" onclick="toggleGroceryItem(this)">
                    <input type="checkbox" style="width: 18px; height: 18px; accent-color: #ea580c; cursor: pointer;" onclick="event.stopPropagation(); toggleGroceryItem(this.parentElement);">
                    <span style="flex: 1;">${escapeHtml(line)}</span>
                </div>
            `;
        });
        container.innerHTML = html || '<p style="color: #94a3b8; text-align: center;">Chưa có nguyên liệu nào.</p>';
    }

    modal.style.display = 'flex';
};

window.closeGroceryModal = function() {
    const modal = document.getElementById('groceryListModal');
    if (modal) modal.style.display = 'none';
};

window.toggleGroceryItem = function(rowEl) {
    if (!rowEl) return;
    const checkbox = rowEl.querySelector('input[type="checkbox"]');
    const isChecked = !rowEl.classList.contains('is-checked');
    if (isChecked) {
        rowEl.classList.add('is-checked');
        if (checkbox) checkbox.checked = true;
    } else {
        rowEl.classList.remove('is-checked');
        if (checkbox) checkbox.checked = false;
    }
};

window.copyGroceryChecklist = function() {
    const rows = document.querySelectorAll('#groceryChecklistBody .grocery-item-row');
    const lines = [];
    rows.forEach(r => {
        const text = r.querySelector('span') ? r.querySelector('span').textContent.trim() : '';
        const isDone = r.classList.contains('is-checked');
        if (text) {
            lines.push((isDone ? '[x] ' : '[ ] ') + text);
        }
    });

    if (lines.length === 0) return;
    const content = lines.join('\n');
    navigator.clipboard.writeText(content).then(() => {
        if (typeof showToast === 'function') {
            showToast('Đã sao chép danh sách đi chợ vào bộ nhớ tạm!', 'success');
        } else {
            alert('Đã sao chép danh sách đi chợ!');
        }
    }).catch(() => {
        prompt('Sao chép danh sách đi chợ:', content);
    });
};

/* ==========================================================================
   HERO AUTO-SLIDING CAROUSEL LOGIC
   ========================================================================== */
function initHeroSlider() {
    const sliderContainer = document.getElementById('heroSlider');
    if (!sliderContainer) return;
    const slides = sliderContainer.querySelectorAll('.hero-slide');
    const dots = sliderContainer.querySelectorAll('.hero-dot');
    if (slides.length <= 1) return;

    let currentSlide = 0;
    let timer = null;

    function showSlide(index) {
        slides.forEach((s, i) => {
            s.classList.toggle('is-active', i === index);
        });
        dots.forEach((d, i) => {
            d.classList.toggle('is-active', i === index);
        });
        currentSlide = index;
    }

    function nextSlide() {
        const next = (currentSlide + 1) % slides.length;
        showSlide(next);
    }

    function prevSlide() {
        const prev = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(prev);
    }

    function startTimer() {
        stopTimer();
        timer = setInterval(nextSlide, 5000);
    }

    function stopTimer() {
        if (timer) clearInterval(timer);
    }

    const nextBtn = sliderContainer.querySelector('.hero-slider-next');
    const prevBtn = sliderContainer.querySelector('.hero-slider-prev');
    if (nextBtn) nextBtn.addEventListener('click', (e) => { e.preventDefault(); nextSlide(); startTimer(); });
    if (prevBtn) prevBtn.addEventListener('click', (e) => { e.preventDefault(); prevSlide(); startTimer(); });

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => { showSlide(i); startTimer(); });
    });

    sliderContainer.addEventListener('mouseenter', stopTimer);
    sliderContainer.addEventListener('mouseleave', startTimer);

    startTimer();
}

document.addEventListener('DOMContentLoaded', () => {
    initHeroSlider();
});

/* ==========================================================================
   COOKPAD SIDEBAR & EXPANDING SEARCH & QUICK LOGIN & COOKBOOK MODAL
   ========================================================================== */

// 1. Cookpad Sidebar Drawer Toggle
window.toggleCookpadSidebar = function(open) {
    const sidebar = document.getElementById('cookpadSidebar');
    const overlay = document.getElementById('cookpadOverlay');
    if (!sidebar || !overlay) return;

    if (open) {
        sidebar.classList.add('is-open');
        overlay.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    } else {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        document.body.style.overflow = '';
    }
};

window.focusHeaderSearch = function(e) {
    if (e) e.preventDefault();
    toggleCookpadSidebar(false);
    const searchInput = document.getElementById('headerSearchInput');
    if (searchInput) {
        searchInput.focus();
        const wrapper = document.getElementById('headerSearchWrapper');
        if (wrapper) wrapper.classList.add('is-expanded');
    }
};

// 2. Expanding Search Bar on Click/Focus
function initExpandingSearch() {
    const wrapper = document.getElementById('headerSearchWrapper');
    const searchInput = document.getElementById('headerSearchInput');
    const clearBtn = document.getElementById('headerSearchClear');
    const dropdown = document.getElementById('searchExpandDropdown');
    const recentSec = document.getElementById('recentSearchesSection');
    const recentList = document.getElementById('recentSearchesList');
    const liveSec = document.getElementById('liveSearchSection');
    const liveResults = document.getElementById('liveSearchResults');
    const form = document.getElementById('headerSearchForm');

    if (!wrapper || !searchInput || !dropdown) return;

    let debounceTimer = null;

    function renderRecentSearches() {
        if (!recentSec || !recentList) return;
        let recent = [];
        try {
            recent = JSON.parse(localStorage.getItem('cookio_recent_searches') || '[]');
        } catch (err) {
            recent = [];
        }

        if (recent.length === 0) {
            recentSec.style.display = 'none';
            recentList.innerHTML = '';
            return;
        }

        recentSec.style.display = 'block';
        recentList.innerHTML = recent.map(item => `
            <a href="${window.BASE_URL}/index.php?q=${encodeURIComponent(item)}" class="recent-tag">
                ${escapeHtml(item)}
            </a>
        `).join('');
    }

    function saveRecentSearch(query) {
        if (!query || query.trim().length === 0) return;
        query = query.trim();
        let recent = [];
        try {
            recent = JSON.parse(localStorage.getItem('cookio_recent_searches') || '[]');
        } catch (e) {
            recent = [];
        }
        recent = recent.filter(item => item.toLowerCase() !== query.toLowerCase());
        recent.unshift(query);
        if (recent.length > 8) recent = recent.slice(0, 8);
        localStorage.setItem('cookio_recent_searches', JSON.stringify(recent));
    }

    window.clearRecentSearches = function() {
        localStorage.removeItem('cookio_recent_searches');
        renderRecentSearches();
    };

    window.clearHeaderSearch = function() {
        searchInput.value = '';
        if (clearBtn) clearBtn.style.display = 'none';
        if (liveSec) liveSec.style.display = 'none';
        searchInput.focus();
    };

    // Open & Expand on Focus / Click
    searchInput.addEventListener('focus', () => {
        wrapper.classList.add('is-expanded');
        dropdown.style.display = 'block';
        renderRecentSearches();
        if (searchInput.value.trim().length > 0 && clearBtn) {
            clearBtn.style.display = 'block';
        }
    });

    searchInput.addEventListener('click', () => {
        wrapper.classList.add('is-expanded');
        dropdown.style.display = 'block';
    });

    // Handle Input Typing
    searchInput.addEventListener('input', (e) => {
        const q = e.target.value.trim();
        if (clearBtn) clearBtn.style.display = q.length > 0 ? 'block' : 'none';

        clearTimeout(debounceTimer);
        if (q.length === 0) {
            if (liveSec) liveSec.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`${window.BASE_URL}/actions/quick_search_api.php?action=search&q=${encodeURIComponent(q)}`);
                const data = await res.json();
                if (data.success && data.results && data.results.length > 0) {
                    if (liveSec) liveSec.style.display = 'block';
                    if (liveResults) {
                        liveResults.innerHTML = data.results.map(r => `
                            <a href="${r.url}" class="live-search-item">
                                <img src="${r.image_url}" alt="${escapeHtml(r.title)}" class="live-search-thumb">
                                <div class="live-search-info">
                                    <div class="live-search-title">${escapeHtml(r.title)}</div>
                                    <div class="live-search-sub">${escapeHtml(r.category)} • ⏱️ ${escapeHtml(r.cooking_time)} • ❤️ ${r.likes_count}</div>
                                </div>
                            </a>
                        `).join('');
                    }
                } else {
                    if (liveSec) liveSec.style.display = 'block';
                    if (liveResults) {
                        liveResults.innerHTML = '<div style="padding: 0.5rem; font-size: 0.82rem; color: #94a3b8;">Không tìm thấy món ăn phù hợp. Nhấn Enter để tìm tất cả.</div>';
                    }
                }
            } catch (err) {
                console.error('Quick search error:', err);
            }
        }, 180);
    });

    // Save on form submit
    if (form) {
        form.addEventListener('submit', () => {
            saveRecentSearch(searchInput.value);
        });
    }

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            wrapper.classList.remove('is-expanded');
            dropdown.style.display = 'none';
        }
    });

    // Escape key closes
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            wrapper.classList.remove('is-expanded');
            dropdown.style.display = 'none';
            toggleCookpadSidebar(false);
        }
    });
}

// 3. Quick Saved Accounts & 1-Click Login
window.quickLoginUser = function(username, password, roleName) {
    const userInp = document.getElementById('loginUsernameInput');
    const passInp = document.getElementById('loginPasswordInput');
    const form = document.getElementById('formLogin');
    const chk = document.getElementById('chkRememberAccount');

    if (!userInp || !passInp || !form) return;

    userInp.value = username;
    passInp.value = password;

    // If remember is checked, save to localStorage
    if (!chk || chk.checked) {
        saveAccountToDevice(username, roleName || username);
    }

    // 1-Click Instant Submit!
    form.submit();
};

function saveAccountToDevice(username, displayName) {
    try {
        let list = JSON.parse(localStorage.getItem('cookio_saved_accounts') || '[]');
        list = list.filter(a => a.username !== username);
        list.unshift({ username: username, name: displayName, savedAt: new Date().toISOString() });
        if (list.length > 5) list = list.slice(0, 5);
        localStorage.setItem('cookio_saved_accounts', JSON.stringify(list));
    } catch (e) {
        console.error('Save account error:', e);
    }
}

window.handleLoginSubmit = function(e) {
    const userInp = document.getElementById('loginUsernameInput');
    const chk = document.getElementById('chkRememberAccount');
    if (userInp && chk && chk.checked && userInp.value.trim()) {
        saveAccountToDevice(userInp.value.trim(), userInp.value.trim());
    }
};

window.switchAuthTab = function(tab) {
    const loginForm = document.getElementById('formLogin');
    const registerForm = document.getElementById('formRegister');
    const tabs = document.querySelectorAll('.auth-tab');

    tabs.forEach(t => {
        t.classList.toggle('active', t.getAttribute('data-auth-tab') === tab);
    });

    if (tab === 'login') {
        if (loginForm) loginForm.classList.remove('hidden');
        if (registerForm) registerForm.classList.add('hidden');
    } else {
        if (loginForm) loginForm.classList.add('hidden');
        if (registerForm) registerForm.classList.remove('hidden');
    }
};

function renderCustomSavedAccounts() {
    const container = document.getElementById('customSavedAccountsList');
    if (!container) return;

    let saved = [];
    try {
        saved = JSON.parse(localStorage.getItem('cookio_saved_accounts') || '[]');
    } catch (e) {
        saved = [];
    }

    // Default accounts already displayed in markup
    const defaults = ['admin1111', 'chef_lan', 'me_bong', 'chu_nam_cook', 'lan_anh_kitchen'];
    const customAccounts = saved.filter(a => !defaults.includes(a.username));

    if (customAccounts.length === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = `
        <div style="font-size: 0.78rem; font-weight: 700; color: #64748b; margin: 0.6rem 0 0.35rem;">
            Tài khoản bạn đã lưu trên máy này:
        </div>
        ${customAccounts.map(acc => `
            <div class="quick-account-card" onclick="quickLoginUser('${escapeHtml(acc.username)}', '1', '${escapeHtml(acc.name)}')">
                <div class="quick-avatar" style="background: #f1f5f9; color: #475569;">👤</div>
                <div class="quick-info">
                    <strong class="quick-name">${escapeHtml(acc.username)}</strong>
                    <span class="quick-role">Đã lưu trên trình duyệt</span>
                </div>
                <button type="button" class="btn-quick-go">Vào &rarr;</button>
                <button type="button" onclick="event.stopPropagation(); removeSavedAccount('${escapeHtml(acc.username)}');" style="background: none; border: none; color: #94a3b8; font-size: 1.1rem; cursor: pointer; padding: 0 0.25rem;" title="Xóa tài khoản này khỏi máy">&times;</button>
            </div>
        `).join('')}
    `;
}

window.removeSavedAccount = function(username) {
    try {
        let list = JSON.parse(localStorage.getItem('cookio_saved_accounts') || '[]');
        list = list.filter(a => a.username !== username);
        localStorage.setItem('cookio_saved_accounts', JSON.stringify(list));
        renderCustomSavedAccounts();
    } catch (e) {
        console.error(e);
    }
};

// 4. Modal "+ Sổ Tay" (Quick Add Recipe to Cookbook)
let activeCookbookRecipeId = null;
window.openCookbookSelectModal = async function(recipeId, recipeTitle) {
    activeCookbookRecipeId = recipeId;
    let modal = document.getElementById('cookbookSelectModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'cookbookSelectModal';
        modal.className = 'cookpad-sidebar-overlay is-visible';
        modal.innerHTML = `
            <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--bg-card, #ffffff); width: 92%; max-width: 460px; border-radius: 1.25rem; border: 1.5px solid var(--border, #fed7aa); padding: 1.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); z-index: 10001; max-height: 85vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.65rem;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main, #1e293b);">📚 Thêm Vào Sổ Tay</h3>
                        <p id="cbModalRecipeTitle" style="margin: 0.2rem 0 0; font-size: 0.85rem; color: #ea580c; font-weight: 600;"></p>
                    </div>
                    <button type="button" onclick="closeCookbookSelectModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8;">&times;</button>
                </div>
                <div id="cbModalBody">
                    <div style="padding: 1.5rem; text-align: center; color: #64748b;">Đang tải danh sách sổ tay...</div>
                </div>
                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0;">
                    <button type="button" class="button button-outline" onclick="showInlineCreateCookbook()" style="width: 100%; padding: 0.55rem; font-size: 0.88rem; font-weight: 700;">
                        ➕ Tạo sổ tay thực đơn mới
                    </button>
                    <div id="inlineCreateCookbookBox" style="display: none; margin-top: 0.75rem; background: #f8fafc; padding: 0.75rem; border-radius: 0.65rem;">
                        <input type="text" id="inlineCookbookTitle" placeholder="Tên sổ tay mới (vd: Thực đơn đãi tiệc...)" style="width: 100%; padding: 0.5rem; border: 1.5px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.88rem; margin-bottom: 0.5rem;">
                        <button type="button" class="button button-create" onclick="submitInlineCookbook()" style="width: 100%; padding: 0.5rem; font-size: 0.88rem; font-weight: 700;">Tạo và lưu món ngay</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    } else {
        modal.classList.add('is-visible');
        modal.style.display = 'block';
    }

    const titleEl = document.getElementById('cbModalRecipeTitle');
    if (titleEl) titleEl.textContent = recipeTitle;

    // Fetch user's cookbooks
    try {
        const res = await fetch(`${window.BASE_URL}/actions/cookbook_action.php?action=list_for_recipe&recipe_id=${recipeId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        const bodyEl = document.getElementById('cbModalBody');
        if (!bodyEl) return;

        if (data.success && data.cookbooks && data.cookbooks.length > 0) {
            bodyEl.innerHTML = `
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    ${data.cookbooks.map(cb => `
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.85rem; background: #f8fafc; border: 1.5px solid ${cb.has_recipe ? '#fdba74' : '#e2e8f0'}; border-radius: 0.65rem; cursor: pointer; transition: all 0.15s ease;">
                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                <input type="checkbox" ${cb.has_recipe ? 'checked' : ''} onchange="toggleRecipeInCookbook(${cb.id}, ${recipeId}, this)" style="accent-color: #ea580c; width: 18px; height: 18px;">
                                <div>
                                    <strong style="font-size: 0.92rem; color: #1e293b; display: block;">${escapeHtml(cb.title)}</strong>
                                    <span style="font-size: 0.78rem; color: #64748b;">${cb.total_recipes} món trong sổ tay</span>
                                </div>
                            </div>
                            <span style="font-size: 0.78rem; font-weight: 700; color: ${cb.has_recipe ? '#ea580c' : '#94a3b8'};">
                                ${cb.has_recipe ? '✓ Đã thêm' : '+ Thêm'}
                            </span>
                        </label>
                    `).join('')}
                </div>
            `;
        } else {
            bodyEl.innerHTML = `
                <div style="text-align: center; padding: 1.5rem 0; color: #64748b;">
                    <div style="font-size: 2.2rem; margin-bottom: 0.35rem;">📖</div>
                    <p style="font-size: 0.9rem; margin: 0 0 0.5rem;">Bạn chưa có sổ tay nào.</p>
                    <p style="font-size: 0.8rem; color: #94a3b8; margin: 0;">Hãy tạo sổ tay đầu tiên ở bên dưới để lưu món này nhé!</p>
                </div>
            `;
        }
    } catch (e) {
        console.error('Fetch cookbooks error:', e);
    }
};

window.closeCookbookSelectModal = function() {
    const modal = document.getElementById('cookbookSelectModal');
    if (modal) {
        modal.classList.remove('is-visible');
        modal.style.display = 'none';
    }
};

window.toggleRecipeInCookbook = async function(cookbookId, recipeId, checkbox) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_recipe');
        formData.append('cookbook_id', cookbookId);
        formData.append('recipe_id', recipeId);
        formData.append('csrf_token', window.CSRF_TOKEN || '');
        formData.append('ajax', '1');

        const res = await fetch(`${window.BASE_URL}/actions/cookbook_action.php`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || 'Cập nhật sổ tay thành công!');
        } else {
            checkbox.checked = !checkbox.checked;
            alert(data.message || 'Không thể cập nhật sổ tay!');
        }
    } catch (e) {
        console.error(e);
        checkbox.checked = !checkbox.checked;
    }
};

window.showInlineCreateCookbook = function() {
    const box = document.getElementById('inlineCreateCookbookBox');
    if (box) {
        box.style.display = box.style.display === 'none' ? 'block' : 'none';
        const inp = document.getElementById('inlineCookbookTitle');
        if (inp) inp.focus();
    }
};

window.submitInlineCookbook = async function() {
    const inp = document.getElementById('inlineCookbookTitle');
    if (!inp || !inp.value.trim()) {
        alert('Vui lòng nhập tên sổ tay!');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('title', inp.value.trim());
        formData.append('recipe_id', activeCookbookRecipeId || '');
        formData.append('csrf_token', window.CSRF_TOKEN || '');
        formData.append('ajax', '1');

        const res = await fetch(`${window.BASE_URL}/actions/cookbook_action.php`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || 'Đã tạo sổ tay và lưu món!');
            closeCookbookSelectModal();
        } else {
            alert(data.message || 'Có lỗi xảy ra.');
        }
    } catch (e) {
        console.error(e);
    }
};

// Helper: Escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ==========================================================================
   YUMMYDAY SIGNATURE: HANDS-FREE KITCHEN TIMER
   ========================================================================== */
let kitchenTimerSeconds = 15 * 60;
let kitchenTimerInitialSeconds = 15 * 60;
let kitchenTimerInterval = null;

function formatKitchenTimerDisplay(sec) {
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function updateKitchenTimerDisplay() {
    const disp = document.getElementById('kitchenTimerDisplay');
    if (disp) {
        disp.textContent = formatKitchenTimerDisplay(kitchenTimerSeconds);
    }
}

window.setCookingTimer = function(minutes) {
    if (kitchenTimerInterval) {
        clearInterval(kitchenTimerInterval);
        kitchenTimerInterval = null;
    }
    kitchenTimerSeconds = minutes * 60;
    kitchenTimerInitialSeconds = kitchenTimerSeconds;
    updateKitchenTimerDisplay();

    const banner = document.getElementById('timerAlarmBanner');
    if (banner) banner.style.display = 'none';

    const btnStart = document.getElementById('btnStartTimer');
    const btnPause = document.getElementById('btnPauseTimer');
    if (btnStart) btnStart.style.display = 'inline-block';
    if (btnPause) btnPause.style.display = 'none';

    if (typeof showToast === 'function') {
        showToast(`Đã hẹn giờ bếp: ${minutes} phút`);
    }
};

window.startCookingTimer = function() {
    if (kitchenTimerSeconds <= 0) {
        kitchenTimerSeconds = kitchenTimerInitialSeconds || 15 * 60;
    }
    const banner = document.getElementById('timerAlarmBanner');
    if (banner) banner.style.display = 'none';

    const btnStart = document.getElementById('btnStartTimer');
    const btnPause = document.getElementById('btnPauseTimer');
    if (btnStart) btnStart.style.display = 'none';
    if (btnPause) btnPause.style.display = 'inline-block';

    if (kitchenTimerInterval) clearInterval(kitchenTimerInterval);

    kitchenTimerInterval = setInterval(() => {
        if (kitchenTimerSeconds > 0) {
            kitchenTimerSeconds--;
            updateKitchenTimerDisplay();
        } else {
            clearInterval(kitchenTimerInterval);
            kitchenTimerInterval = null;
            if (btnStart) btnStart.style.display = 'inline-block';
            if (btnPause) btnPause.style.display = 'none';
            if (banner) banner.style.display = 'block';
            playKitchenTimerAlarm();
        }
    }, 1000);
};

window.pauseCookingTimer = function() {
    if (kitchenTimerInterval) {
        clearInterval(kitchenTimerInterval);
        kitchenTimerInterval = null;
    }
    const btnStart = document.getElementById('btnStartTimer');
    const btnPause = document.getElementById('btnPauseTimer');
    if (btnStart) btnStart.style.display = 'inline-block';
    if (btnPause) btnPause.style.display = 'none';
};

window.resetCookingTimer = function() {
    if (kitchenTimerInterval) {
        clearInterval(kitchenTimerInterval);
        kitchenTimerInterval = null;
    }
    kitchenTimerSeconds = kitchenTimerInitialSeconds;
    updateKitchenTimerDisplay();

    const banner = document.getElementById('timerAlarmBanner');
    if (banner) banner.style.display = 'none';

    const btnStart = document.getElementById('btnStartTimer');
    const btnPause = document.getElementById('btnPauseTimer');
    if (btnStart) btnStart.style.display = 'inline-block';
    if (btnPause) btnPause.style.display = 'none';
};

function playKitchenTimerAlarm() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();
        
        // 4 chime notes: 880Hz, 1046Hz, 1318Hz, 1760Hz
        const notes = [880, 1046.5, 1318.5, 1760];
        notes.forEach((freq, idx) => {
            const delay = idx * 0.22;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + delay);
            gain.gain.setValueAtTime(0.25, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.3);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + 0.35);
        });
    } catch (e) {
        console.warn('AudioContext alarm error:', e);
    }
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    initExpandingSearch();
    renderCustomSavedAccounts();
});
/* ==========================================================================
   HORIZONTAL FULL-WIDTH SEARCH OVERLAY LOGIC
   ========================================================================== */
let hSearchDebounceTimer = null;

window.openHorizontalSearch = function() {
    const overlay = document.getElementById('horizontalSearchOverlay');
    if (!overlay) return;
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    renderHorizontalRecentSearches();

    const input = document.getElementById('horizontalSearchInput');
    if (input) {
        setTimeout(() => input.focus(), 80);
    }
};

window.closeHorizontalSearch = function() {
    const overlay = document.getElementById('horizontalSearchOverlay');
    if (!overlay) return;
    overlay.style.display = 'none';
    document.body.style.overflow = '';
};

window.clearHorizontalSearch = function() {
    const input = document.getElementById('horizontalSearchInput');
    if (input) {
        input.value = '';
        input.focus();
    }
    const clearBtn = document.getElementById('hSearchClear');
    if (clearBtn) clearBtn.style.display = 'none';

    const liveWrap = document.getElementById('hLiveResultsWrap');
    if (liveWrap) liveWrap.style.display = 'none';

    const recentWrap = document.getElementById('hRecentWrap');
    if (recentWrap) recentWrap.style.display = 'block';
};

window.handleHorizontalSearchInput = function(query) {
    const q = (query || '').trim();
    const clearBtn = document.getElementById('hSearchClear');
    if (clearBtn) {
        clearBtn.style.display = q.length > 0 ? 'flex' : 'none';
    }

    clearTimeout(hSearchDebounceTimer);

    const liveWrap = document.getElementById('hLiveResultsWrap');
    const recentWrap = document.getElementById('hRecentWrap');

    if (q.length < 1) {
        if (liveWrap) liveWrap.style.display = 'none';
        if (recentWrap) recentWrap.style.display = 'block';
        return;
    }

    hSearchDebounceTimer = setTimeout(async () => {
        try {
            const baseUrl = window.BASE_URL || '';
            const res = await fetch(`${baseUrl}/actions/quick_search_api.php?action=search&q=${encodeURIComponent(q)}`);
            const data = await res.json();

            const countEl = document.getElementById('hResultsCount');
            const gridEl = document.getElementById('hResultsGrid');

            if (!gridEl) return;

            if (!data.success || !data.results || data.results.length === 0) {
                if (countEl) countEl.textContent = '(0 món)';
                gridEl.innerHTML = `
                    <div style="grid-column: 1 / -1; padding: 2rem; text-align: center; color: #64748b;">
                        🔍 Không tìm thấy món ăn nào với từ khóa "<strong>${escapeHtml(q)}</strong>".<br>
                        <a href="${baseUrl}/index.php" style="color: #ea580c; font-weight: 700; margin-top: 0.5rem; display: inline-block;">Khám phá tất cả công thức &rarr;</a>
                    </div>
                `;
            } else {
                if (countEl) countEl.textContent = `(${data.results.length} món)`;
                gridEl.innerHTML = data.results.map(r => {
                    const thumb = r.image_url 
                        ? `<img src="${r.image_url}" alt="${escapeHtml(r.title)}" class="h-result-thumb">`
                        : `<div class="h-result-thumb" style="display:flex;align-items:center;justify-content:center;background:#fed7aa;font-size:1.5rem;">🍲</div>`;
                    return `
                        <a href="${r.url}" class="h-result-card" onclick="saveRecentSearch('${escapeHtml(r.title)}')">
                            ${thumb}
                            <div class="h-result-info">
                                <div class="h-result-title">${escapeHtml(r.title)}</div>
                                <div class="h-result-meta">
                                    <span>⏱️ ${escapeHtml(r.cooking_time || '30p')}</span>
                                    <span>📂 ${escapeHtml(r.category || 'Món chính')}</span>
                                    ${r.calories ? `<span>🔥 ${r.calories} kcal</span>` : ''}
                                </div>
                            </div>
                        </a>
                    `;
                }).join('');
            }

            if (liveWrap) liveWrap.style.display = 'block';
            if (recentWrap) recentWrap.style.display = 'none';
        } catch (e) {
            console.error('Horizontal search error:', e);
        }
    }, 200);
};

function renderHorizontalRecentSearches() {
    const wrap = document.getElementById('hRecentWrap');
    const tagsContainer = document.getElementById('hRecentTags');
    if (!wrap || !tagsContainer) return;

    try {
        const searches = JSON.parse(localStorage.getItem('cookio_recent_searches') || '[]');
        if (searches.length === 0) {
            wrap.style.display = 'none';
            return;
        }

        wrap.style.display = 'block';
        const baseUrl = window.BASE_URL || '';
        tagsContainer.innerHTML = searches.map(term => `
            <a href="${baseUrl}/index.php?q=${encodeURIComponent(term)}" class="h-tag-pill" style="background:#f1f5f9; color:#334155; border-color:#cbd5e1;">
                🕒 ${escapeHtml(term)}
            </a>
        `).join('');
    } catch (e) {
        wrap.style.display = 'none';
    }
}

// Global Keyboard Shortcut: Ctrl+K or / to open Horizontal Search, Escape to close
document.addEventListener('keydown', (e) => {
    const overlay = document.getElementById('horizontalSearchOverlay');
    const isOverlayOpen = overlay && overlay.style.display !== 'none';

    if (e.key === 'Escape' && isOverlayOpen) {
        closeHorizontalSearch();
        return;
    }

    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (isOverlayOpen) {
            closeHorizontalSearch();
        } else {
            openHorizontalSearch();
        }
        return;
    }

    // Quick "/" key to search when not currently typing in an input/textarea
    if (e.key === '/' && !isOverlayOpen && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        openHorizontalSearch();
    }
});
/* ==========================================================================
   YUMMYDAY HOMEPAGE 3D COVERFLOW & INGREDIENT SLIDER
   ========================================================================== */
function initCookioInteractions() {
    // 1. Coverflow 3D Carousel with Smooth Auto-Slide
    const track = document.getElementById('coverflowTrack');
    const slides = document.querySelectorAll('.coverflow-slide');
    const btnPrev = document.getElementById('btnCoverflowPrev');
    const btnNext = document.getElementById('btnCoverflowNext');
    const wrapper = document.querySelector('.coverflow-wrapper');

    if (track && slides.length > 0) {
        let activeIdx = 2; // Default center slide
        let autoSlideTimer = null;

        function updateCoverflow() {
            slides.forEach((slide, idx) => {
                slide.classList.remove('is-active');
                if (idx === activeIdx) {
                    slide.classList.add('is-active');
                }
            });
        }

        function nextSlide() {
            activeIdx = (activeIdx + 1) % slides.length;
            updateCoverflow();
        }

        function prevSlide() {
            activeIdx = (activeIdx - 1 + slides.length) % slides.length;
            updateCoverflow();
        }

        function startAutoSlide() {
            stopAutoSlide();
            autoSlideTimer = setInterval(nextSlide, 3500);
        }

        function stopAutoSlide() {
            if (autoSlideTimer) {
                clearInterval(autoSlideTimer);
                autoSlideTimer = null;
            }
        }

        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                prevSlide();
                startAutoSlide(); // Reset timer after manual click
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', () => {
                nextSlide();
                startAutoSlide(); // Reset timer after manual click
            });
        }

        slides.forEach((slide, idx) => {
            slide.addEventListener('click', () => {
                activeIdx = idx;
                updateCoverflow();
                startAutoSlide();
            });
        });

        // Pause auto-slide on hover
        const targetHover = wrapper || track;
        targetHover.addEventListener('mouseenter', stopAutoSlide);
        targetHover.addEventListener('mouseleave', startAutoSlide);

        // Start auto-slide immediately
        startAutoSlide();
    }

    // 2. Ingredients Slider (Image 3)
    const ingTrack = document.getElementById('ingredientsTrack');
    const btnIngPrev = document.getElementById('btnIngPrev');
    const btnIngNext = document.getElementById('btnIngNext');

    if (ingTrack) {
        if (btnIngPrev) {
            btnIngPrev.addEventListener('click', () => {
                ingTrack.scrollBy({ left: -220, behavior: 'smooth' });
            });
        }
        if (btnIngNext) {
            btnIngNext.addEventListener('click', () => {
                ingTrack.scrollBy({ left: 220, behavior: 'smooth' });
            });
        }
    }

    // 3. Floating Back to Top Button
    const btnBackToTop = document.getElementById('btnBackToTop');
    if (btnBackToTop) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                btnBackToTop.style.display = 'flex';
            } else {
                btnBackToTop.style.display = 'none';
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initCookioInteractions();
});