const loginModal = document.querySelector('#loginModal');
const methodsView = document.querySelector('#authMethodsView');
const formView = document.querySelector('#authFormView');
const loginForm = document.querySelector('#formLogin');
const registerForm = document.querySelector('#formRegister');

document.querySelectorAll('[data-open-login]').forEach((button) => {
    button.addEventListener('click', () => loginModal?.showModal());
});
document.querySelectorAll('[data-close-modal]').forEach((button) => {
    button.addEventListener('click', () => loginModal?.close());
});
document.querySelector('[data-show-form]')?.addEventListener('click', () => {
    methodsView?.classList.add('hidden');
    formView?.classList.remove('hidden');
});
document.querySelector('[data-show-methods]')?.addEventListener('click', () => {
    formView?.classList.add('hidden');
    methodsView?.classList.remove('hidden');
});
document.querySelectorAll('[data-auth-tab]').forEach((tab) => {
    tab.addEventListener('click', () => {
        const isLogin = tab.dataset.authTab === 'login';
        document.querySelectorAll('[data-auth-tab]').forEach((item) => item.classList.toggle('active', item === tab));
        loginForm?.classList.toggle('hidden', !isLogin);
        registerForm?.classList.toggle('hidden', isLogin);
    });
});

/* ==========================================================================
   TOAST NOTIFICATION SYSTEM
   ========================================================================== */
function showToast(message, type = 'success') {
    let container = document.querySelector('#toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-message ${type}`;
    const icon = type === 'success' ? '✅' : '📢';
    toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px) scale(0.95)';
        setTimeout(() => toast.remove(), 350);
    }, 3200);
}
window.showToast = showToast;

/* ==========================================================================
   RECIPE DETAIL INTERACTIVE HELPERS
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Copy ingredients list
    const copyIngredientsBtn = document.querySelector('.js-copy-ingredients');
    if (copyIngredientsBtn) {
        copyIngredientsBtn.addEventListener('click', () => {
            const recipeTitle = document.querySelector('.recipe-detail-hero-card h1')?.textContent?.trim() || 'Món ăn';
            const rows = document.querySelectorAll('.ingredient-row');
            const items = [];
            rows.forEach((row) => {
                const name = row.querySelector('.ingredient-item-name')?.textContent?.trim() || '';
                const amount = row.querySelector('.ingredient-item-amount')?.textContent?.trim() || '';
                if (name) {
                    items.push(`• ${name}: ${amount}`);
                }
            });

            if (items.length === 0) {
                showToast('Không có nguyên liệu nào để sao chép.', 'info');
                return;
            }

            const text = `🛒 Danh sách nguyên liệu món "${recipeTitle}":\n` + items.join('\n') + `\n\nNguồn: ${window.location.href}`;
            navigator.clipboard.writeText(text).then(() => {
                showToast('Đã sao chép danh sách nguyên liệu vào bộ nhớ tạm!');
            }).catch(() => {
                showToast('Không thể sao chép, vui lòng thử lại.', 'info');
            });

            if (typeof openGroceryModal === 'function') {
                const rawItems = items.map(i => i.replace(/^•\s*/, '')).join('\n');
                openGroceryModal(rawItems, `Nguyên liệu món: ${recipeTitle}`);
            }
        });
    }

    // 2. Share recipe link
    const shareBtn = document.querySelector('.js-share-recipe');
    if (shareBtn) {
        shareBtn.addEventListener('click', () => {
            const url = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Đã sao chép liên kết công thức món ăn!');
                }).catch(() => {
                    showToast('Liên kết: ' + url, 'info');
                });
            } else {
                showToast('Liên kết: ' + url, 'info');
            }
        });
    }
});
