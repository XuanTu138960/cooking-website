<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$recipes = db()->query(
    "SELECT id, title, description, category, cooking_time, servings, ingredients, image_url 
     FROM recipes 
     WHERE status = 'approved' 
     ORDER BY created_at DESC"
)->fetchAll();

$ingredientGroups = [
    '🥩 Thịt & Thủy hải sản' => ['Thịt bò', 'Thịt gà', 'Thịt heo', 'Tôm', 'Cá'],
    '🥬 Rau củ quả' => ['Cà chua', 'Hành tây', 'Rau xanh', 'Cà rốt', 'Nấm'],
    '🥚 Trứng & Đậu phụ' => ['Trứng', 'Đậu phụ'],
    '🧄 Gia vị & Thảo mộc' => ['Tỏi', 'Gừng', 'Sả', 'Tiêu', 'Ớt', 'Lá chanh']
];

$loadSmartFridgeScript = true;
$pageTitle = 'Tủ lạnh thông minh - Cookio';
require __DIR__ . '/../includes/header.php';
?>
<section class="recipe-detail-container">
    <div style="text-align: center; margin-bottom: 2rem;">
        <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">🧊</span>
        <h1 style="font-size: 2rem; font-weight: 800; color: #111827; margin-bottom: 0.5rem;">Tủ lạnh thông minh</h1>
        <p style="color: #6b7280; font-size: 1.05rem; max-width: 540px; margin: 0 auto;">
            Tích chọn những nguyên liệu đang có trong gian bếp của bạn, Cookio sẽ gợi ý ngay các món ăn nấu được ngay tức thì!
        </p>
    </div>

    <!-- Fridge Drawer Card -->
    <div class="fridge-container">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1ebe1; padding-bottom: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
            <h2 style="font-size: 1.25rem; font-weight: 800; margin: 0;">
                🛒 Chọn nguyên liệu có sẵn
            </h2>

            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <!-- Matching Mode Toggle -->
                <div style="display: flex; align-items: center; gap: 0.5rem; background: #fdfbf7; padding: 0.35rem 0.65rem; border-radius: 99px; border: 1px solid var(--border); font-size: 0.85rem; font-weight: 600;">
                    <span>Chế độ:</span>
                    <label style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer; margin: 0; font-weight: 600;">
                        <input type="radio" name="match_mode" value="any" checked style="width: auto; margin: 0;"> Linh hoạt
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer; margin: 0; font-weight: 600;">
                        <input type="radio" name="match_mode" value="all" style="width: auto; margin: 0;"> Chính xác
                    </label>
                </div>

                <button type="button" id="btnClearAll" class="button button-outline" style="padding: 0.35rem 0.85rem; font-size: 0.82rem;">
                    ✕ Bỏ chọn tất cả
                </button>
            </div>
        </div>

        <div id="ingredientPicker">
            <?php foreach ($ingredientGroups as $groupTitle => $groupItems): ?>
                <div class="fridge-section-title"><?= e($groupTitle) ?></div>
                <div class="fridge-ingredients-group" style="margin-bottom: 1rem;">
                    <?php foreach ($groupItems as $item): ?>
                        <label class="fridge-chip">
                            <input type="checkbox" value="<?= e($item) ?>">
                            <span><?= e($item) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <!-- Custom Ingredients Group -->
            <div id="customGroup" class="hidden">
                <div class="fridge-section-title">✨ Nguyên liệu tùy chọn của bạn</div>
                <div id="customChips" class="fridge-ingredients-group" style="margin-bottom: 1rem;"></div>
            </div>
        </div>

        <!-- Add Custom Ingredient Input -->
        <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed #e5e7eb; display: flex; gap: 8px; max-width: 32rem;">
            <input type="text" id="customIngredientInput" placeholder="Thêm nguyên liệu khác (vd: Me chua, Nước dừa, Sả cay...)" style="border-radius: 9999px;">
            <button type="button" id="btnAddIngredient" class="button button-create" style="white-space: nowrap; padding: 0.6rem 1.25rem;">
                + Thêm
            </button>
        </div>
    </div>

    <!-- Status Message -->
    <div id="fridgeMessage" class="notice" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 1.5rem;">
        Hãy chọn ít nhất một nguyên liệu ở trên để bắt đầu tìm món phù hợp.
    </div>

    <!-- Recipe Results Grid -->
    <div class="recipe-grid" id="fridgeResults">
        <?php foreach ($recipes as $recipe): ?>
            <?php 
            $imgSrc = !empty($recipe['image_url']) ? BASE_URL . '/' . e($recipe['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
            ?>
            <article class="recipe-card fridge-recipe" data-ingredients="<?= e($recipe['ingredients']) ?>">
                <div class="recipe-card-media">
                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                        <img src="<?= $imgSrc ?>" alt="<?= e($recipe['title']) ?>" loading="lazy">
                    </a>
                    <span class="card-category-badge"><?= e($recipe['category'] ?? 'Món chính') ?></span>
                </div>

                <div class="recipe-card-body">
                    <!-- Match Badge Container -->
                    <div class="match-badge-container" style="margin-bottom: 0.5rem; min-height: 22px;"></div>

                    <div class="card-meta-row">
                        <span>⏱️ <?= e($recipe['cooking_time'] ?? '30 phút') ?></span>
                        <span>👥 <?= e($recipe['servings'] ?? '2 - 4 người') ?></span>
                    </div>

                    <h3>
                        <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                            <?= e($recipe['title']) ?>
                        </a>
                    </h3>

                    <p style="font-size: 0.85rem; color: #4b5563; line-height: 1.5; margin-bottom: 1rem; background: #fdfbf7; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #f1ebe1;">
                        <strong>Nguyên liệu:</strong> <?= e(mb_strimwidth((string) str_replace(["\r\n", "\n", "\r"], ', ', $recipe['ingredients']), 0, 75, '...')) ?>
                    </p>

                    <a class="button button-create full-width" style="text-align: center;" href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                        Xem cách nấu ngay &rarr;
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- No Match State -->
    <div id="noMatchMessage" class="empty-state-box hidden" style="margin-top: 2rem; padding: 3rem 2rem;">
        <p style="font-size: 1.25rem; font-weight: 700; color: #374151;">Chưa có món nào khớp với toàn bộ nguyên liệu bạn đã chọn</p>
        <p style="color: #6b7280; margin-bottom: 1.5rem;">Mẹo: Bạn có thể bỏ bớt một vài nguyên liệu kén người nấu, hoặc tự mình đóng góp công thức mới!</p>
        <a href="<?= BASE_URL ?>/views/create-recipe.php" class="button button-create">+ Đăng công thức với nguyên liệu này</a>
    </div>
</section>

<?php
require __DIR__ . '/../includes/modal-login.php';
require __DIR__ . '/../includes/footer.php';
