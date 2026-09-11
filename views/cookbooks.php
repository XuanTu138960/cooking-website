<?php

declare(strict_types=1);

$pageTitle = 'Sổ Tay Ẩm Thực & Bộ Sưu Tập Món Ngon - Cookio';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$viewCookbookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// 1. DETAIL VIEW: View specific cookbook
if ($viewCookbookId) {
    $stmtCb = db()->prepare("
        SELECT c.*, u.username AS author_name
        FROM cookbooks c
        JOIN users u ON u.id = c.user_id
        WHERE c.id = ? AND (c.is_public = 1 OR c.user_id = ?)
    ");
    $stmtCb->execute([$viewCookbookId, $currentUserId ?: 0]);
    $cookbook = $stmtCb->fetch();

    if (!$cookbook) {
        echo '<main class="container py-8"><div class="empty-state"><h3>Không tìm thấy sổ tay ẩm thực!</h3><p>Sổ tay này có thể đã bị xóa hoặc được đặt ở chế độ riêng tư.</p><a href="' . BASE_URL . '/views/cookbooks.php" class="button button-primary">Quay lại danh sách sổ tay</a></div></main>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }

    $isOwner = ($currentUserId !== null && (int)$cookbook['user_id'] === $currentUserId);

    // Fetch recipes in this cookbook
    $stmtRecipes = db()->prepare("
        SELECT r.*, u.username AS author_name,
               (SELECT COUNT(*) FROM comments c WHERE c.recipe_id = r.id) AS comment_count,
               (SELECT AVG(rating) FROM comments c WHERE c.recipe_id = r.id) AS avg_rating,
               (SELECT COUNT(*) FROM recipe_likes rl WHERE rl.recipe_id = r.id) AS likes_count
        FROM cookbook_recipes cr
        JOIN recipes r ON r.id = cr.recipe_id
        JOIN users u ON u.id = r.author_id
        WHERE cr.cookbook_id = ? AND r.status = 'approved'
        ORDER BY cr.created_at DESC
    ");
    $stmtRecipes->execute([$viewCookbookId]);
    $recipes = $stmtRecipes->fetchAll();

    // Compute menu statistics
    $totalCookingMinutes = 0;
    $totalCalories = 0;
    $cookbookIngredients = [];
    foreach ($recipes as $r) {
        if (!empty($r['cooking_time']) && preg_match('/(\d+)/', (string)$r['cooking_time'], $m)) {
            $totalCookingMinutes += (int)$m[1];
        }
        $totalCalories += (int)($r['calories'] ?? 0);
        if (!empty($r['ingredients'])) {
            $cookbookIngredients[] = trim((string)$r['ingredients']);
        }
    }
    $allCookbookIngredientsText = implode("\n", $cookbookIngredients);
    ?>
    <main class="container py-8">
        <nav class="breadcrumb mb-4">
            <a href="<?= BASE_URL ?>/index.php">Trang chủ</a> &rsaquo;
            <a href="<?= BASE_URL ?>/views/cookbooks.php">Sổ tay ẩm thực</a> &rsaquo;
            <span><?= e($cookbook['title']) ?></span>
        </nav>

        <div class="cookbook-header-card mb-6" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1.5px solid #fed7aa; border-radius: 1.25rem; padding: 2rem;">
            <div class="cookbook-header-content">
                <span class="badge badge-primary mb-2" style="background: #ea580c; color: #fff; font-weight: 700; border-radius: 9999px; padding: 0.25rem 0.75rem;">📚 Sổ tay ẩm thực tuyển chọn</span>
                <h1 class="cookbook-title" style="font-size: 2rem; font-weight: 800; color: #7c2d12; margin: 0.5rem 0;"><?= e($cookbook['title']) ?></h1>
                <?php if (!empty($cookbook['description'])): ?>
                    <p class="cookbook-desc" style="color: #431407; font-size: 1rem; line-height: 1.6; max-width: 800px;"><?= nl2br(e($cookbook['description'])) ?></p>
                <?php endif; ?>
                <div class="cookbook-meta" style="color: #9a3412; font-size: 0.9rem; margin-top: 1rem; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <span>Người tạo: <strong><?= e($cookbook['author_name']) ?></strong></span>
                    <span>&bull;</span>
                    <span><strong><?= count($recipes) ?></strong> món ăn trong thực đơn</span>
                    <span>&bull;</span>
                    <span>Ngày tạo: <?= date('d/m/Y', strtotime($cookbook['created_at'])) ?></span>
                </div>
            </div>
            <?php if ($isOwner): ?>
                <div class="cookbook-header-actions" style="margin-top: 1.25rem;">
                    <form method="post" action="<?= BASE_URL ?>/actions/cookbook_action.php" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sổ tay này?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="cookbook_id" value="<?= $cookbook['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="button button-outline text-danger" style="border-color: #fee2e2; color: #dc2626; background: #fff;">
                            🗑️ Xóa sổ tay này
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($recipes)): ?>
            <div class="empty-state" style="text-align: center; padding: 3rem 1rem; background: #fff; border-radius: 1rem; border: 2px dashed #fed7aa;">
                <div class="empty-icon" style="font-size: 3.5rem; margin-bottom: 0.5rem;">📖</div>
                <h3 style="font-size: 1.3rem; font-weight: 700; color: #1e293b;">Sổ tay này chưa có món ăn nào</h3>
                <p style="color: #64748b; margin-bottom: 1.5rem;">Khám phá kho món ngon Cookio và bấm nút "📚 Sổ tay" trên bất kỳ món nào để gom món vào đây nhé!</p>
                <a href="<?= BASE_URL ?>/index.php" class="button button-create" style="padding: 0.75rem 2rem;">Khám phá món ngon ngay</a>
            </div>
        <?php else: ?>

            <!-- Menu Summary Banner -->
            <div class="cookbook-menu-summary">
                <div class="menu-summary-stat">
                    <span>🍽️</span>
                    <span><strong><?= count($recipes) ?></strong> món ăn</span>
                </div>
                <?php if ($totalCookingMinutes > 0): ?>
                    <div class="menu-summary-stat">
                        <span>⏱️</span>
                        <span>Ước tính khoảng <strong><?= $totalCookingMinutes ?> phút</strong> nấu</span>
                    </div>
                <?php endif; ?>
                <?php if ($totalCalories > 0): ?>
                    <div class="menu-summary-stat">
                        <span>🔥</span>
                        <span>Tổng <strong><?= $totalCalories ?> kcal</strong></span>
                    </div>
                <?php endif; ?>
                <div style="margin-left: auto;">
                    <button type="button" class="button button-create"
                            onclick="openGroceryModal(<?= json_encode($allCookbookIngredientsText) ?>, 'Danh Sách Đi Chợ - Thực Đơn: <?= e(addslashes($cookbook['title'])) ?>')"
                            style="padding: 0.55rem 1.15rem; font-size: 0.88rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.45rem;">
                        <span>📋</span> Gom nguyên liệu đi chợ cả thực đơn
                    </button>
                </div>
            </div>

            <div class="recipe-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <?php
                    $imgSrc = !empty($recipe['image_url']) ? BASE_URL . '/' . e($recipe['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                    ?>
                    <article class="recipe-card">
                        <div class="recipe-card-media">
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= $recipe['id'] ?>">
                                <img src="<?= $imgSrc ?>" alt="<?= e($recipe['title']) ?>" loading="lazy">
                            </a>
                            <span class="card-category-badge"><?= e($recipe['category']) ?></span>
                            <?php if (!empty($recipe['calories'])): ?>
                                <span class="badge-cal-pill" style="position: absolute; bottom: 0.65rem; left: 0.65rem; z-index: 2;">🔥 <?= $recipe['calories'] ?> kcal</span>
                            <?php endif; ?>
                        </div>
                        <div class="recipe-card-body">
                            <h2 class="recipe-card-title">
                                <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= $recipe['id'] ?>"><?= e($recipe['title']) ?></a>
                            </h2>
                            <div class="card-author-chip">
                                <span class="author-mini-avatar"><?= mb_strtoupper(mb_substr($recipe['author_name'], 0, 1)) ?></span>
                                <span>Bếp: <strong><?= e($recipe['author_name']) ?></strong></span>
                            </div>
                            <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 0.4rem; font-size: 0.8rem; color: var(--text-muted);">
                                <span>⏱️ <?= e($recipe['cooking_time']) ?></span>
                                <span>❤️ <?= (int)$recipe['likes_count'] ?></span>
                            </div>
                            <?php if ($isOwner): ?>
                                <div style="margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px solid #f1f5f9;">
                                    <form method="post" action="<?= BASE_URL ?>/actions/cookbook_action.php" style="margin: 0;">
                                        <input type="hidden" name="action" value="toggle_recipe">
                                        <input type="hidden" name="cookbook_id" value="<?= $cookbook['id'] ?>">
                                        <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                                        <input type="hidden" name="return_url" value="views/cookbooks.php?id=<?= $cookbook['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <button type="submit" class="button button-text text-danger" style="font-size: 0.8rem; padding: 0.2rem 0.5rem; color: #dc2626;" title="Bỏ món này khỏi sổ tay">✕ Bỏ món</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// 2. LIST VIEW: View all cookbooks
$myCookbooks = [];
if ($currentUserId) {
    $stmtMy = db()->prepare("
        SELECT c.*, 
               COUNT(cr.recipe_id) AS total_recipes,
               (SELECT r.image_url FROM cookbook_recipes cr2 JOIN recipes r ON r.id = cr2.recipe_id WHERE cr2.cookbook_id = c.id ORDER BY cr2.created_at DESC LIMIT 1) AS thumb_url
        FROM cookbooks c
        LEFT JOIN cookbook_recipes cr ON cr.cookbook_id = c.id
        WHERE c.user_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmtMy->execute([$currentUserId]);
    $myCookbooks = $stmtMy->fetchAll();
}

$stmtPublic = db()->query("
    SELECT c.*, u.username AS author_name,
           COUNT(cr.recipe_id) AS total_recipes,
           (SELECT r.image_url FROM cookbook_recipes cr2 JOIN recipes r ON r.id = cr2.recipe_id WHERE cr2.cookbook_id = c.id ORDER BY cr2.created_at DESC LIMIT 1) AS thumb_url
    FROM cookbooks c
    JOIN users u ON u.id = c.user_id
    LEFT JOIN cookbook_recipes cr ON cr.cookbook_id = c.id
    WHERE c.is_public = 1
    GROUP BY c.id
    ORDER BY total_recipes DESC, c.created_at DESC
    LIMIT 16
");
$publicCookbooks = $stmtPublic->fetchAll();
?>

<main class="container py-8">
    <div class="page-title-row mb-6" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="page-title" style="font-size: 1.85rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.35rem;">
                📚 Sổ Tay Ẩm Thực & Thực Đơn Tuyển Chọn
            </h1>
            <p class="page-subtitle" style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">
                Tuyển tập thực đơn hoàn chỉnh theo chủ đề: Bữa cơm gia đình 7 ngày, Eat Clean giảm cân, Món tụ tập cuối tuần...
            </p>
        </div>
        <div>
            <?php if ($currentUserId): ?>
                <button type="button" class="button button-create" onclick="openCreateCookbookModal()" style="padding: 0.65rem 1.25rem;">
                    ➕ Tạo sổ tay mới
                </button>
            <?php else: ?>
                <button type="button" class="button button-create" data-open-login style="padding: 0.65rem 1.25rem;">
                    ➕ Đăng nhập để tạo sổ tay
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- MY COOKBOOKS SECTION -->
    <?php if ($currentUserId): ?>
        <section class="mb-10" style="margin-bottom: 2.5rem;">
            <h2 class="section-title mb-4" style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>📖 Sổ tay của bạn (<?= count($myCookbooks) ?>)</span>
            </h2>
            <?php if (empty($myCookbooks)): ?>
                <div class="card p-6 text-center" style="background: var(--color-surface, #fff); border: 2px dashed #fed7aa; border-radius: 1.25rem; padding: 2.5rem 1rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">📖</div>
                    <h3 style="font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">Bạn chưa tạo sổ tay nào</h3>
                    <p class="text-muted mb-4" style="color: var(--text-muted); margin-bottom: 1.25rem;">Hãy tạo sổ tay đầu tiên để gom nhóm và lưu giữ các thực đơn tâm đắc của bạn!</p>
                    <button type="button" class="button button-create" onclick="openCreateCookbookModal()">Tạo sổ tay ngay</button>
                </div>
            <?php else: ?>
                <div class="cookbook-grid">
                    <?php foreach ($myCookbooks as $cb): ?>
                        <?php 
                        $thumb = !empty($cb['thumb_url']) ? BASE_URL . '/' . e($cb['thumb_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                        ?>
                        <div class="cookbook-card">
                            <a href="<?= BASE_URL ?>/views/cookbooks.php?id=<?= $cb['id'] ?>" class="cookbook-card-cover">
                                <img src="<?= $thumb ?>" alt="<?= e($cb['title']) ?>">
                                <span class="cookbook-recipe-count"><?= (int)$cb['total_recipes'] ?> món</span>
                            </a>
                            <div class="cookbook-card-body">
                                <h3 class="cookbook-card-title">
                                    <a href="<?= BASE_URL ?>/views/cookbooks.php?id=<?= $cb['id'] ?>"><?= e($cb['title']) ?></a>
                                </h3>
                                <p class="cookbook-card-desc"><?= e(mb_substr($cb['description'] ?? 'Chưa có mô tả.', 0, 75)) ?>...</p>
                                <div class="cookbook-card-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem;">
                                    <span class="text-muted" style="font-size: 0.8rem; color: var(--text-muted);">Cập nhật: <?= date('d/m/Y', strtotime($cb['created_at'])) ?></span>
                                    <a href="<?= BASE_URL ?>/views/cookbooks.php?id=<?= $cb['id'] ?>" class="button button-outline" style="padding: 0.35rem 0.75rem; font-size: 0.82rem;">Xem thực đơn &rarr;</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- FEATURED / COMMUNITY COOKBOOKS SECTION -->
    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h2 class="section-title mb-4" style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>🌟 Bộ sưu tập & Thực đơn gợi ý từ cộng đồng</span>
            </h2>
        </div>

        <div class="cookbook-grid">
            <?php foreach ($publicCookbooks as $cb): ?>
                <?php 
                $thumb = !empty($cb['thumb_url']) ? BASE_URL . '/' . e($cb['thumb_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                ?>
                <div class="cookbook-card">
                    <a href="<?= BASE_URL ?>/views/cookbooks.php?id=<?= $cb['id'] ?>" class="cookbook-card-cover">
                        <img src="<?= $thumb ?>" alt="<?= e($cb['title']) ?>">
                        <span class="cookbook-recipe-count"><?= (int)$cb['total_recipes'] ?> món</span>
                    </a>
                    <div class="cookbook-card-body">
                        <h3 class="cookbook-card-title">
                            <a href="<?= BASE_URL ?>/views/cookbooks.php?id=<?= $cb['id'] ?>"><?= e($cb['title']) ?></a>
                        </h3>
                        <p class="cookbook-card-desc"><?= e(mb_substr($cb['description'] ?? 'Khám phá các món ăn hấp dẫn trong bộ sưu tập này.', 0, 85)) ?>...</p>
                        <div class="cookbook-card-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem;">
                            <div class="card-author-chip" style="margin: 0;">
                                <span class="author-mini-avatar" style="width: 22px; height: 22px; font-size: 0.75rem;"><?= mb_strtoupper(mb_substr($cb['author_name'], 0, 1)) ?></span>
                                <span style="font-size: 0.8rem;">Bởi <strong><?= e($cb['author_name']) ?></strong></span>
                            </div>
                            <a href="<?= BASE_URL ?>/views/cookbooks.php?id=<?= $cb['id'] ?>" class="button button-outline" style="padding: 0.35rem 0.75rem; font-size: 0.82rem;">Xem thực đơn &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';