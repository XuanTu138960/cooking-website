<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$recipes = [];
$categories = [];
$allIngredientsText = '';
$selectedCat = trim((string) ($_GET['cat'] ?? ''));

if (is_logged_in()) {
    $uid = (int) $_SESSION['user_id'];
    
    // Fetch all categories for filter tabs
    $stmtCats = db()->prepare("
        SELECT DISTINCT r.category 
        FROM recipes r 
        JOIN saved_recipes s ON s.recipe_id = r.id 
        WHERE s.user_id = ? AND r.status = 'approved' AND r.category IS NOT NULL AND r.category != ''
        ORDER BY r.category ASC
    ");
    $stmtCats->execute([$uid]);
    $categories = $stmtCats->fetchAll(PDO::FETCH_COLUMN);

    $sql = "
        SELECT r.*, u.username AS author_name, s.note AS personal_note, s.created_at AS saved_at 
        FROM recipes r 
        INNER JOIN saved_recipes s ON s.recipe_id = r.id 
        INNER JOIN users u ON u.id = r.author_id 
        WHERE s.user_id = ? AND r.status = 'approved'
    ";
    $params = [$uid];

    if ($selectedCat !== '') {
        $sql .= " AND r.category = ?";
        $params[] = $selectedCat;
    }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $recipes = $stmt->fetchAll();

    // Compile ingredients for grocery modal
    $ingredientsList = [];
    foreach ($recipes as $r) {
        if (!empty($r['ingredients'])) {
            $ingredientsList[] = trim((string) $r['ingredients']);
        }
    }
    $allIngredientsText = implode("\n", $ingredientsList);
}

$pageTitle = 'Món đã lưu & Bếp của tôi - Cookio';
require __DIR__ . '/../includes/header.php';
?>
<div class="recipe-detail-container" style="padding-top: 2rem; padding-bottom: 3.5rem;">

    <!-- Page header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.35rem;">
                ❤️ Bếp Yêu Thích & Món Đã Lưu
            </h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">
                Không gian lưu giữ những món ăn tâm đắc cùng ghi chú công thức riêng của bạn.
            </p>
        </div>

        <?php if (!empty($recipes)): ?>
            <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                <button type="button" class="button button-create" 
                        onclick="openGroceryModal(<?= json_encode($allIngredientsText) ?>, 'Danh Sách Đi Chợ - <?= count($recipes) ?> Món Đã Lưu')"
                        style="padding: 0.6rem 1.15rem; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 0.45rem;">
                    <span>📋</span> Gom nguyên liệu đi chợ
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['bookmark'])): ?>
        <div class="notice success" style="margin-bottom: 1.25rem;">
            <?= match ($_GET['bookmark']) {
                'unsaved' => '✅ Đã bỏ lưu món khỏi bộ sưu tập.',
                'note_saved' => '✅ Đã lưu ghi chú bếp thành công.',
                default => '✅ Đã cập nhật bộ sưu tập yêu thích.'
            } ?>
        </div>
    <?php endif; ?>

    <?php if (!is_logged_in()): ?>
        <div style="text-align: center; padding: 3.5rem 1.5rem; background: #fff; border-radius: 1.25rem; border: 1.5px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🔒</div>
            <h3 style="margin: 0 0 0.5rem; font-size: 1.3rem; color: var(--text-main); font-weight: 800;">Đăng nhập để xem bếp yêu thích</h3>
            <p style="color: var(--text-muted); margin: 0 0 1.5rem; font-size: 0.95rem;">Bạn cần đăng nhập để xem các món ăn đã lưu cùng ghi chú cá nhân.</p>
            <button type="button" class="button button-create" data-open-login style="padding: 0.75rem 2rem;">Đăng nhập ngay</button>
        </div>
    <?php elseif (empty($recipes) && $selectedCat === ''): ?>
        <div style="text-align: center; padding: 3.5rem 1.5rem; background: #fff; border-radius: 1.25rem; border: 2px dashed var(--border);">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🫙</div>
            <h3 style="margin: 0 0 0.5rem; font-size: 1.35rem; color: var(--text-main); font-weight: 800;">Gian bếp yêu thích đang trống</h3>
            <p style="color: var(--text-muted); margin: 0 0 1.5rem; font-size: 0.95rem;">Nhấn nút ❤️ hoặc "Lưu món" trên bất kỳ công thức nào để gom về đây và viết ghi chú riêng nhé!</p>
            <a href="<?= BASE_URL ?>/index.php" class="button button-create" style="display: inline-block; padding: 0.75rem 2rem; font-weight: 700; text-decoration: none;">
                🔍 Khám phá món ngon ngay
            </a>
        </div>
    <?php else: ?>

        <!-- Category Filter Pills Bar -->
        <?php if (!empty($categories)): ?>
            <div class="saved-filter-bar">
                <a href="<?= BASE_URL ?>/views/saved-recipes.php" class="saved-filter-pill <?= $selectedCat === '' ? 'is-active' : '' ?>">
                    Tất cả món đã lưu
                </a>
                <?php foreach ($categories as $catName): ?>
                    <a href="<?= BASE_URL ?>/views/saved-recipes.php?cat=<?= urlencode($catName) ?>" 
                       class="saved-filter-pill <?= $selectedCat === $catName ? 'is-active' : '' ?>">
                        <?= e($catName) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($recipes) && $selectedCat !== ''): ?>
            <div style="text-align: center; padding: 2.5rem 1rem; background: #fff; border-radius: 1rem; border: 1px solid var(--border);">
                <p style="color: var(--text-muted); margin-bottom: 1rem;">Không có món nào thuộc danh mục "<strong><?= e($selectedCat) ?></strong>" trong danh sách đã lưu.</p>
                <a href="<?= BASE_URL ?>/views/saved-recipes.php" class="button button-outline">&larr; Xem tất cả món đã lưu</a>
            </div>
        <?php else: ?>

            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 1.25rem;">
                Đang hiển thị <strong style="color: var(--primary);"><?= count($recipes) ?> món</strong> trong danh sách yêu thích của bạn.
            </p>

            <!-- Recipe grid -->
            <div class="recipe-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <?php 
                    $imgSrc = !empty($recipe['image_url']) ? BASE_URL . '/' . e($recipe['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                    $hasNote = !empty($recipe['personal_note']);
                    ?>
                    <article class="recipe-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <!-- Card image -->
                            <div class="recipe-card-media">
                                <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                                    <img src="<?= $imgSrc ?>" alt="<?= e($recipe['title']) ?>" loading="lazy">
                                </a>
                                
                                <?php if (!empty($recipe['category'])): ?>
                                    <span class="card-category-badge"><?= e($recipe['category']) ?></span>
                                <?php endif; ?>

                                <!-- Unsave button -->
                                <form method="post" action="<?= BASE_URL ?>/actions/saved_action.php" style="position: absolute; top: 0.6rem; right: 0.6rem; z-index: 2;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                                    <input type="hidden" name="return_url" value="views/saved-recipes.php<?= $selectedCat !== '' ? '?cat=' . urlencode($selectedCat) : '' ?>">
                                    <button type="submit" class="card-save-btn saved" title="Bỏ lưu khỏi bếp yêu thích"
                                        onclick="return confirm('Bỏ lưu món «<?= e(addslashes($recipe['title'])) ?>»?')"
                                        style="background: #ea580c; border-color: #ea580c; color: #fff;">
                                        ❤️
                                    </button>
                                </form>
                            </div>

                            <!-- Card body -->
                            <div class="recipe-card-body">
                                <h3 class="recipe-card-title">
                                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>"><?= e($recipe['title']) ?></a>
                                </h3>
                                <div class="card-author-chip">
                                    <span class="author-mini-avatar"><?= mb_strtoupper(mb_substr($recipe['author_name'], 0, 1)) ?></span>
                                    <span>Bếp: <strong><?= e($recipe['author_name']) ?></strong></span>
                                </div>
                                <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 0.4rem; font-size: 0.8rem; color: var(--text-muted);">
                                    <?php if (!empty($recipe['cooking_time'])): ?>
                                        <span>⏱️ <?= e($recipe['cooking_time']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($recipe['calories'])): ?>
                                        <span>🔥 <?= (int)$recipe['calories'] ?> kcal</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Personal Chef Note Box -->
                                <div class="personal-note-box" id="personal-note-box-<?= (int) $recipe['id'] ?>">
                                    <div class="personal-note-header">
                                        <span>📝 Ghi chú bếp của bạn</span>
                                        <?php if ($hasNote): ?>
                                            <span style="font-size: 0.7rem; color: #16a34a; font-weight: 700;">✓ Có ghi chú</span>
                                        <?php endif; ?>
                                    </div>
                                    <textarea class="personal-note-textarea" placeholder="Thêm lưu ý riêng (VD: giảm cay, nấu 15 phút, chồng rất thích...)"><?= e($recipe['personal_note'] ?? '') ?></textarea>
                                    <div style="display: flex; justify-content: flex-end;">
                                        <button type="button" class="personal-note-save-btn" onclick="saveRecipeNote(<?= (int) $recipe['id'] ?>, this)">
                                            Lưu ghi chú
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer actions -->
                        <div style="padding: 0 1rem 1rem; display: flex; gap: 0.5rem;">
                            <button type="button" class="button button-outline" onclick="openAddToCookbookModal(<?= (int) $recipe['id'] ?>)" style="flex: 1; font-size: 0.8rem; padding: 0.45rem;">
                                📚 Sổ tay
                            </button>
                            <button type="button" class="button button-outline js-copy-ingredients" data-ingredients="<?= e($recipe['ingredients'] ?? '') ?>" style="flex: 1; font-size: 0.8rem; padding: 0.45rem;" title="Xem nguyên liệu món này" onclick="openGroceryModal(<?= json_encode($recipe['ingredients'] ?? '') ?>, 'Nguyên liệu: <?= e(addslashes($recipe['title'])) ?>')">
                                📋 Đi chợ
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php
require __DIR__ . '/../includes/modal-login.php';
require __DIR__ . '/../includes/footer.php';