<?php

declare(strict_types=1);

// Fetch all approved recipes for the interactive menu modal
$stmtAllMenu = db()->query("
    SELECT r.id, r.title, r.category, r.cooking_time, r.image_url, r.likes_count,
           COALESCE(ROUND(AVG(rv.rating), 1), 5.0) AS avg_rating
    FROM recipes r
    LEFT JOIN reviews rv ON rv.recipe_id = r.id
    WHERE r.status = 'approved'
    GROUP BY r.id
    ORDER BY r.id ASC
");
$allMenuRecipes = $stmtAllMenu ? $stmtAllMenu->fetchAll() : [];

$menuCategories = ['Tất cả', 'Món xào', 'Món canh', 'Món kho', 'Món hấp', 'Món chiên'];
?>

<!-- All Recipes Interactive Menu Modal -->
<div id="allRecipesMenuModal" class="cookio-menu-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 10000; backdrop-filter: blur(6px); justify-content: center; align-items: center; padding: 1.5rem;" aria-hidden="true">
    <div class="cookio-menu-modal-dialog" style="background: #ffffff; border-radius: 1.25rem; max-width: 1080px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); border: 1.5px solid #fed7aa; animation: modalPop 0.25s ease-out;">
        
        <!-- Modal Header -->
        <div style="padding: 1.25rem 1.75rem; border-bottom: 1.5px solid #fed7aa; background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #ea580c; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; box-shadow: 0 4px 10px rgba(234, 88, 12, 0.25);">
                    🍳
                </div>
                <div>
                    <h2 style="font-size: 1.35rem; font-weight: 800; color: #7c2d12; margin: 0; line-height: 1.2;">
                        Bảng Thực Đơn Toàn Bộ Món Ăn Cookio
                    </h2>
                    <p style="font-size: 0.85rem; color: #9a3412; margin: 0.2rem 0 0; font-weight: 600;">
                        Tổng hợp <?= count($allMenuRecipes) ?> công thức chuẩn vị &bull; Chọn món để xem hướng dẫn chi tiết
                    </p>
                </div>
            </div>
            
            <button type="button" onclick="closeAllRecipesMenuModal()" aria-label="Đóng" style="background: #ffffff; border: 1px solid #fed7aa; width: 38px; height: 38px; border-radius: 50%; font-size: 1.35rem; color: #7c2d12; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                &times;
            </button>
        </div>

        <!-- Modal Toolbar: Search & Category Filter Pills -->
        <div style="padding: 1rem 1.75rem; background: #fafaf9; border-bottom: 1px solid #e7e5e4; display: flex; flex-direction: column; gap: 0.85rem;">
            <!-- Live Search Bar -->
            <div style="position: relative; width: 100%;">
                <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 1.1rem; color: #9a3412;">🔍</span>
                <input type="text" id="menuModalSearchInput" oninput="filterMenuRecipes()" placeholder="Tìm nhanh theo tên món (VD: bò xào, cá kho, súp gà, nem rán...)" style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border-radius: 9999px; border: 1.5px solid #fed7aa; background: #ffffff; font-size: 0.92rem; outline: none; font-family: inherit; color: #1e293b; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: border-color 0.2s;">
            </div>

            <!-- Categories Tabs -->
            <div style="display: flex; gap: 0.5rem; overflow-x: auto; scrollbar-width: none; padding-bottom: 0.25rem;">
                <?php foreach ($menuCategories as $idx => $mCat): ?>
                    <button type="button" class="menu-cat-btn <?= $idx === 0 ? 'is-active' : '' ?>" data-cat="<?= e($mCat) ?>" onclick="selectMenuCategory('<?= e($mCat) ?>', this)" style="padding: 0.4rem 0.95rem; border-radius: 9999px; font-size: 0.84rem; font-weight: 700; cursor: pointer; border: 1.5px solid <?= $idx === 0 ? '#ea580c' : '#fed7aa' ?>; background: <?= $idx === 0 ? '#ea580c' : '#ffffff' ?>; color: <?= $idx === 0 ? '#ffffff' : '#7c2d12' ?>; white-space: nowrap; transition: all 0.15s; font-family: inherit;">
                        <?= e($mCat) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Modal Recipe Grid Container -->
        <div style="padding: 1.5rem 1.75rem; overflow-y: auto; flex: 1; background: #f8fafc;">
            <div id="menuModalRecipesGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.15rem;">
                <?php foreach ($allMenuRecipes as $rItem): ?>
                    <?php
                    $itemImg = !empty($rItem['image_url']) ? BASE_URL . '/' . e($rItem['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                    ?>
                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int)$rItem['id'] ?>" class="menu-recipe-card js-menu-item" data-title="<?= e(mb_strtolower($rItem['title'])) ?>" data-cat="<?= e($rItem['category'] ?? '') ?>" style="display: flex; flex-direction: column; background: #ffffff; border: 1.5px solid #f1f5f9; border-radius: 14px; overflow: hidden; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;">
                        <!-- Image -->
                        <div style="position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden; background: #f3f4f6;">
                            <img src="<?= $itemImg ?>" alt="<?= e($rItem['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" loading="lazy">
                            <span style="position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(30, 27, 27, 0.78); backdrop-filter: blur(4px); color: #ffffff; font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 9999px;">
                                <?= e($rItem['category'] ?? 'Món ngon') ?>
                            </span>
                        </div>
                        <!-- Info -->
                        <div style="padding: 0.85rem; display: flex; flex-direction: column; flex: 1; justify-content: space-between;">
                            <h4 style="margin: 0 0 0.5rem; font-size: 0.92rem; font-weight: 800; color: #1e293b; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= e($rItem['title']) ?>
                            </h4>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: #94a3b8; font-weight: 600; padding-top: 0.5rem; border-top: 1px solid #f8fafc;">
                                <span>⏱ <?= e($rItem['cooking_time']) ?></span>
                                <span style="color: #ea580c; font-weight: 700;">Xem chi tiết &rarr;</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Empty Search State inside Modal -->
            <div id="menuModalEmptyState" style="display: none; text-align: center; padding: 3rem 1rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🔍</div>
                <h3 style="margin: 0 0 0.5rem; font-size: 1.15rem; color: #1e293b; font-weight: 800;">Không tìm thấy món ăn phù hợp</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.88rem;">Hãy thử gõ từ khóa khác như "gà", "bò", "chiên", "xào", "nướng"...</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 0.85rem 1.75rem; background: #ffffff; border-top: 1px solid #e7e5e4; display: flex; justify-content: space-between; align-items: center; font-size: 0.84rem; color: #6b7280;">
            <span>💡 Nhấn vào bất kỳ món nào để mở trang công thức và nguyên liệu</span>
            <button type="button" onclick="closeAllRecipesMenuModal()" class="button button-outline" style="padding: 0.4rem 1rem; font-size: 0.84rem; border-radius: 9999px;">
                Đóng bảng thực đơn
            </button>
        </div>
    </div>
</div>

<script>
let currentSelectedCategory = 'Tất cả';

function openAllRecipesMenuModal() {
    const modal = document.getElementById('allRecipesMenuModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = document.getElementById('menuModalSearchInput');
            if (input) input.focus();
        }, 150);
    }
}

function closeAllRecipesMenuModal() {
    const modal = document.getElementById('allRecipesMenuModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function selectMenuCategory(catName, btn) {
    currentSelectedCategory = catName;
    document.querySelectorAll('.menu-cat-btn').forEach(b => {
        b.style.borderColor = '#fed7aa';
        b.style.background = '#ffffff';
        b.style.color = '#7c2d12';
        b.classList.remove('is-active');
    });
    if (btn) {
        btn.style.borderColor = '#ea580c';
        btn.style.background = '#ea580c';
        btn.style.color = '#ffffff';
        btn.classList.add('is-active');
    }
    filterMenuRecipes();
}

function filterMenuRecipes() {
    const query = (document.getElementById('menuModalSearchInput').value || '').trim().toLowerCase();
    const items = document.querySelectorAll('.js-menu-item');
    let visibleCount = 0;

    items.forEach(item => {
        const title = item.getAttribute('data-title') || '';
        const cat = item.getAttribute('data-cat') || '';

        const matchCat = (currentSelectedCategory === 'Tất cả' || cat === currentSelectedCategory);
        const matchQuery = (query === '' || title.includes(query) || cat.toLowerCase().includes(query));

        if (matchCat && matchQuery) {
            item.style.display = 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const emptyBox = document.getElementById('menuModalEmptyState');
    if (emptyBox) {
        emptyBox.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

// Close on backdrop or Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllRecipesMenuModal();
});
document.getElementById('allRecipesMenuModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAllRecipesMenuModal();
});
</script>

<style>
@keyframes modalPop {
    0% { transform: scale(0.95); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.menu-recipe-card:hover {
    transform: translateY(-4px);
    border-color: #ea580c !important;
    box-shadow: 0 10px 20px rgba(234, 88, 12, 0.12) !important;
}
.menu-recipe-card:hover img {
    transform: scale(1.05);
}
</style>
