const picker = document.querySelector('#ingredientPicker');
const resultCards = [...document.querySelectorAll('.fridge-recipe')];
const message = document.querySelector('#fridgeMessage');
const noMatchBox = document.querySelector('#noMatchMessage');
const customInput = document.querySelector('#customIngredientInput');
const btnAdd = document.querySelector('#btnAddIngredient');
const btnClearAll = document.querySelector('#btnClearAll');
const customGroup = document.querySelector('#customGroup');
const customChips = document.querySelector('#customChips');

function getMatchMode() {
    const checkedRadio = document.querySelector('input[name="match_mode"]:checked');
    return checkedRadio ? checkedRadio.value : 'any';
}

function filterRecipes() {
    const selected = [...picker.querySelectorAll('input:checked')].map((input) => input.value.trim().toLowerCase());
    const mode = getMatchMode();
    let visibleCount = 0;

    resultCards.forEach((card) => {
        const ingredients = (card.dataset.ingredients || '').toLowerCase();
        const badgeContainer = card.querySelector('.match-badge-container');

        if (selected.length === 0) {
            card.classList.remove('hidden');
            if (badgeContainer) badgeContainer.innerHTML = '';
            return;
        }

        // Count how many selected ingredients match
        let matchedCount = 0;
        selected.forEach((item) => {
            if (ingredients.includes(item)) {
                matchedCount++;
            }
        });

        const isMatch = (mode === 'all') 
            ? (matchedCount === selected.length)
            : (matchedCount > 0);

        card.classList.toggle('hidden', !isMatch);

        if (isMatch) {
            visibleCount++;
            if (badgeContainer) {
                const isFull = matchedCount === selected.length;
                const bgStyle = isFull ? 'background:#dcfce7; color:#15803d;' : 'background:#fff7ed; color:#ea580c;';
                const icon = isFull ? '✅' : '✨';
                badgeContainer.innerHTML = `<span style="display:inline-block; font-size:0.78rem; font-weight:700; ${bgStyle} padding:0.2rem 0.65rem; border-radius:99px;">${icon} Khớp ${matchedCount}/${selected.length} nguyên liệu</span>`;
            }
        } else {
            if (badgeContainer) badgeContainer.innerHTML = '';
        }
    });

    if (selected.length === 0) {
        if (message) {
            message.textContent = 'Hãy chọn ít nhất một nguyên liệu ở trên để bắt đầu tìm món phù hợp.';
            message.className = 'notice';
        }
        if (noMatchBox) noMatchBox.classList.add('hidden');
    } else {
        if (message) {
            const modeText = mode === 'all' ? 'khớp trọn vẹn tất cả' : 'chứa ít nhất một';
            message.textContent = `Tìm thấy ${visibleCount} món ${modeText} nguyên liệu bạn đã chọn.`;
            message.className = visibleCount > 0 ? 'notice success' : 'notice';
        }
        if (noMatchBox) {
            noMatchBox.classList.toggle('hidden', visibleCount > 0);
        }
    }
}

function addCustomIngredient() {
    const val = customInput?.value.trim();
    if (!val) return;

    // Check if already exists
    const existing = [...picker.querySelectorAll('input')].some(
        (input) => input.value.toLowerCase() === val.toLowerCase()
    );

    if (existing) {
        if (window.showToast) {
            window.showToast('Nguyên liệu này đã có trong danh sách!', 'info');
        } else {
            alert('Nguyên liệu này đã có trong danh sách!');
        }
        customInput.value = '';
        return;
    }

    if (customGroup) customGroup.classList.remove('hidden');

    const label = document.createElement('label');
    label.className = 'fridge-chip';
    label.innerHTML = `<input type="checkbox" value="${val}" checked><span>${val}</span>`;
    
    if (customChips) {
        customChips.appendChild(label);
    } else {
        picker.appendChild(label);
    }

    customInput.value = '';
    if (window.showToast) {
        window.showToast(`Đã thêm nguyên liệu "${val}"!`);
    }
    filterRecipes();
}

function clearAllIngredients() {
    picker.querySelectorAll('input:checked').forEach((input) => {
        input.checked = false;
    });
    filterRecipes();
    if (window.showToast) {
        window.showToast('Đã bỏ chọn tất cả nguyên liệu.');
    }
}

picker?.addEventListener('change', filterRecipes);
document.querySelectorAll('input[name="match_mode"]').forEach((radio) => {
    radio.addEventListener('change', filterRecipes);
});
btnClearAll?.addEventListener('click', clearAllIngredients);
btnAdd?.addEventListener('click', addCustomIngredient);
customInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        addCustomIngredient();
    }
});
