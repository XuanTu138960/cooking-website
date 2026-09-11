<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statement = db()->prepare(
    "SELECT r.*, u.username AS author_name 
     FROM recipes r 
     INNER JOIN users u ON u.id = r.author_id 
     WHERE r.id = ? AND r.status = 'approved'"
);
$statement->execute([$recipeId ?: 0]);
$recipe = $statement->fetch();

$isSaved = false;
$isFollowing = false;
$followerCount = 0;
$comments = [];
$avgRating = 5.0;
$ratingCount = 0;

if ($recipe) {
    // 1. Increment view count (throttled by session)
    if (!isset($_SESSION['viewed_recipes'])) {
        $_SESSION['viewed_recipes'] = [];
    }
    if (!in_array((int) $recipe['id'], $_SESSION['viewed_recipes'], true)) {
        $_SESSION['viewed_recipes'][] = (int) $recipe['id'];
        db()->prepare('UPDATE recipes SET views_count = views_count + 1 WHERE id = ?')->execute([(int) $recipe['id']]);
        $recipe['views_count'] = (int) ($recipe['views_count'] ?? 0) + 1;
    }

    // 2. Track into Recently Viewed history
    if (!isset($_SESSION['recent_recipes'])) {
        $_SESSION['recent_recipes'] = [];
    }
    $_SESSION['recent_recipes'] = array_diff($_SESSION['recent_recipes'], [(int) $recipe['id']]);
    array_unshift($_SESSION['recent_recipes'], (int) $recipe['id']);
    $_SESSION['recent_recipes'] = array_slice($_SESSION['recent_recipes'], 0, 8);

    // 3. Saved recipe state
    if (is_logged_in()) {
        $stmtSaved = db()->prepare('SELECT id FROM saved_recipes WHERE user_id = ? AND recipe_id = ?');
        $stmtSaved->execute([(int) $_SESSION['user_id'], (int) $recipe['id']]);
        $isSaved = (bool) $stmtSaved->fetch();

        // Check if following author
        if ((int) $_SESSION['user_id'] !== (int) $recipe['author_id']) {
            $stmtFollow = db()->prepare('SELECT id FROM follows WHERE follower_id = ? AND author_id = ?');
            $stmtFollow->execute([(int) $_SESSION['user_id'], (int) $recipe['author_id']]);
            $isFollowing = (bool) $stmtFollow->fetch();
        }
    }

    $followerCount = (int) db()->query("SELECT COUNT(*) FROM follows WHERE author_id = " . (int) $recipe['author_id'])->fetchColumn();

    $stmtComments = db()->prepare(
        "SELECT c.*, u.username AS author_name 
         FROM comments c 
         INNER JOIN users u ON u.id = c.user_id 
         WHERE c.recipe_id = ? 
         ORDER BY c.created_at ASC"
    );
    $stmtComments->execute([(int) $recipe['id']]);
    $comments = $stmtComments->fetchAll();

    $topLevelComments = [];
    $repliesByParent = [];
    foreach ($comments as $commItem) {
        if (!empty($commItem['parent_id'])) {
            $repliesByParent[(int) $commItem['parent_id']][] = $commItem;
        } else {
            $topLevelComments[] = $commItem;
        }
    }

    $ratingData = db()->query(
        "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM comments WHERE recipe_id = " . (int) $recipe['id']
    )->fetch();
    if ($ratingData && (int) $ratingData['count'] > 0) {
        $avgRating = round((float) $ratingData['avg_rating'], 1);
        $ratingCount = (int) $ratingData['count'];
    }

    // 4. Like state
    $isLiked = false;
    if (is_logged_in()) {
        $stmtLiked = db()->prepare('SELECT id FROM recipe_likes WHERE user_id = ? AND recipe_id = ?');
        $stmtLiked->execute([(int) $_SESSION['user_id'], (int) $recipe['id']]);
        $isLiked = (bool) $stmtLiked->fetch();
    }

    // 5. Smart related recipes
    $stmtRelated = db()->prepare("
        SELECT r.*, u.username AS author_name,
               (SELECT COUNT(*) FROM comments c WHERE c.recipe_id = r.id) AS review_count,
               (SELECT AVG(rating) FROM comments c WHERE c.recipe_id = r.id) AS avg_rating
        FROM recipes r
        JOIN users u ON u.id = r.author_id
        WHERE r.status = 'approved' AND r.id != ? AND (r.category = ? OR r.category = 'Món chính')
        ORDER BY r.views_count DESC, r.created_at DESC
        LIMIT 3
    ");
    $stmtRelated->execute([(int)$recipe['id'], $recipe['category']]);
    $relatedRecipes = $stmtRelated->fetchAll();
}

// Parse ingredients lines
$ingredientLines = [];
if ($recipe && !empty($recipe['ingredients'])) {
    $rawLines = preg_split('/\r\n|\r|\n/', (string) $recipe['ingredients']);
    foreach ($rawLines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $ingredientLines[] = $line;
        }
    }
}

// Parse instruction steps
$instructionSteps = [];
if ($recipe && !empty($recipe['instructions'])) {
    $rawSteps = preg_split('/\r\n|\r|\n/', (string) $recipe['instructions']);
    foreach ($rawSteps as $step) {
        $step = trim($step);
        if ($step !== '') {
            // Strip leading "1. ", "Bước 1: ", etc. if already numbered
            $cleaned = preg_replace('/^(\d+[\.\)]|Bước\s*\d+:?)\s*/iu', '', $step);
            $instructionSteps[] = $cleaned ?: $step;
        }
    }
}

$pageTitle = ($recipe ? e($recipe['title']) . ' - ' : '') . 'Cookio';
require __DIR__ . '/../includes/header.php';
?>
<div class="recipe-detail-container">
    <?php if (!$recipe): ?>
        <div class="empty-state-box" style="padding: 4rem 2rem;">
            <h1>Không tìm thấy công thức</h1>
            <p>Công thức này không tồn tại hoặc đang chờ phê duyệt.</p>
            <a href="<?= BASE_URL ?>/index.php" class="button button-create">Quay lại trang chủ</a>
        </div>
    <?php else: ?>
        <!-- Breadcrumbs -->
        <nav style="font-size: 0.88rem; color: #6b7280; margin-bottom: 1.25rem;">
            <a href="<?= BASE_URL ?>/index.php">Trang chủ</a> &rsaquo;
            <a href="<?= BASE_URL ?>/index.php?cat=<?= urlencode($recipe['category'] ?? 'Món chính') ?>"><?= e($recipe['category'] ?? 'Món chính') ?></a> &rsaquo;
            <span style="color: #111827; font-weight: 600;"><?= e($recipe['title']) ?></span>
        </nav>

        <?php if (isset($_GET['bookmark'])): ?>
            <p class="notice success">
                <?= $_GET['bookmark'] === 'saved' ? '❤️ Đã lưu công thức vào bộ sưu tập của bạn!' : 'Đã xóa món khỏi danh sách lưu.' ?>
            </p>
        <?php endif; ?>

        <?php if (isset($_GET['comment'])): ?>
            <p class="notice success">
                <?= $_GET['comment'] === 'success' ? 'Cảm ơn bạn đã gửi đánh giá và chia sẻ kinh nghiệm nấu món này!' : 'Đã xóa bình luận.' ?>
            </p>
        <?php endif; ?>

        <!-- Main Recipe Presentation Card -->
        <article class="recipe-detail-hero-card">
            <span class="card-category-badge" style="position: static; display: inline-block; margin-bottom: 0.5rem;">
                <?= e($recipe['category'] ?? 'Món chính') ?>
            </span>

            <h1><?= e($recipe['title']) ?></h1>

            <!-- Author & Actions Bar -->
            <div class="detail-author-bar" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="<?= BASE_URL ?>/views/author.php?id=<?= (int) $recipe['author_id'] ?>" class="author-profile-link" style="text-decoration:none; color:inherit;">
                        <div class="author-large-avatar">
                            <?= mb_substr((string) $recipe['author_name'], 0, 1) ?>
                        </div>
                        <div class="author-details-text">
                            <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                <strong><?= e($recipe['author_name']) ?></strong>
                                <?php 
                                $chefBadges = get_chef_badges((int) $recipe['author_id']); 
                                if (!empty($chefBadges)): 
                                ?>
                                    <span class="chef-badge <?= $chefBadges[0]['class'] ?>" title="<?= $chefBadges[0]['label'] ?>">
                                        <?= $chefBadges[0]['icon'] ?> <?= $chefBadges[0]['label'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span>Chia sẻ ngày <?= date('d/m/Y', strtotime((string) $recipe['created_at'])) ?> &bull; <?= $followerCount ?> người theo dõi</span>
                        </div>
                    </a>

                    <?php if (is_logged_in() && (int) $_SESSION['user_id'] !== (int) $recipe['author_id']): ?>
                        <form method="post" action="<?= BASE_URL ?>/actions/follow_action.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="author_id" value="<?= (int) $recipe['author_id'] ?>">
                            <input type="hidden" name="return_url" value="views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                            <button type="submit" class="button <?= $isFollowing ? 'button-saved' : 'button-outline' ?>" style="padding: 0.35rem 0.85rem; font-size: 0.82rem; border-radius: 99px;">
                                <?= $isFollowing ? '✓ Đang theo dõi' : '+ Theo dõi' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="recipe-actions-bar" style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                    <!-- Cookpad Cooking Mode button -->
                    <button type="button" class="button button-create" id="btnOpenCookingMode" style="padding: 0.55rem 1.1rem; display: flex; align-items: center; gap: 0.4rem; font-weight: 700;">
                        👩‍🍳 Bắt đầu nấu
                    </button>

                    <!-- Like button (AJAX 1-Click) -->
                    <button type="button" class="js-like-btn <?= $isLiked ? 'is-liked' : '' ?>" data-recipe-id="<?= (int) $recipe['id'] ?>" style="padding: 0.55rem 0.95rem;">
                        <span>❤️</span> <span class="like-count"><?= (int)($recipe['likes_count'] ?? 0) ?></span> Thả tim
                    </button>

                    <!-- Custom Cookbook button -->
                    <button type="button" class="button button-outline" onclick="openAddToCookbookModal(<?= (int) $recipe['id'] ?>)" style="padding: 0.55rem 0.95rem;" title="Thêm vào bộ sưu tập cá nhân">
                        📚 Sổ tay
                    </button>

                    <?php if (is_logged_in()): ?>
                        <form method="post" action="<?= BASE_URL ?>/actions/saved_action.php" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                            <input type="hidden" name="return_url" value="views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                            <button type="submit" class="button <?= $isSaved ? 'button-saved' : 'button-outline' ?>" style="padding: 0.55rem 1rem;">
                                <?= $isSaved ? '🔖 Đã lưu' : '🔖 Lưu món' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="button button-outline" data-open-login style="padding: 0.55rem 1rem;">
                            🔖 Lưu món
                        </button>
                    <?php endif; ?>

                    <button type="button" class="button button-outline js-copy-ingredients" title="Sao chép danh sách nguyên liệu" style="padding: 0.55rem 0.85rem;">
                        📋 Đi chợ
                    </button>

                    <button type="button" class="button button-outline js-share-recipe" title="Sao chép liên kết món ăn" style="padding: 0.55rem 0.85rem;">
                        🔗 Chia sẻ
                    </button>

                    <button type="button" class="button button-outline" onclick="window.print()" title="In công thức này" style="padding: 0.55rem 0.85rem;">
                        🖨️ In
                    </button>
                </div>
            </div>

            <!-- Dish Main Image -->
            <?php 
            $imgSrc = !empty($recipe['image_url']) ? BASE_URL . '/' . e($recipe['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
            ?>
            <img class="detail-main-photo" src="<?= $imgSrc ?>" alt="<?= e($recipe['title']) ?>">

            <!-- Recipe Story / Intro -->
            <?php if (!empty($recipe['description'])): ?>
                <div class="detail-intro-quote">
                    <?= nl2br(e($recipe['description'])) ?>
                </div>
            <?php endif; ?>

            <!-- Cooking Specs Grid -->
            <div class="cooking-specs-grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));">
                <div class="spec-box">
                    <small>⏱️ Thời gian</small>
                    <strong><?= e($recipe['cooking_time'] ?? '30 phút') ?></strong>
                </div>
                <div class="spec-box">
                    <small>👥 Khẩu phần</small>
                    <strong><?= e($recipe['servings'] ?? '2 - 4 người') ?></strong>
                </div>
                <div class="spec-box">
                    <small>🏷️ Danh mục</small>
                    <strong><?= e($recipe['category'] ?? 'Món chính') ?></strong>
                </div>
                <div class="spec-box">
                    <small>⭐ Đánh giá</small>
                    <strong><?= $avgRating ?>/5</strong> (<?= $ratingCount ?>)
                </div>
                <div class="spec-box">
                    <small>👁️ Lượt xem</small>
                    <strong><?= number_format((int) ($recipe['views_count'] ?? 0)) ?></strong>
                </div>
            </div>
        </article>

        <!-- Dotted Ingredients Card with Servings Scaler -->
        <?php
        preg_match('/\d+/', (string) ($recipe['servings'] ?? '2'), $servMatch);
        $baseServings = !empty($servMatch[0]) ? (int) $servMatch[0] : 2;
        ?>
        <section class="ingredients-card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h2 style="margin: 0; font-size: 1.25rem;">
                    🛒 Nguyên liệu chuẩn bị
                </h2>

                <!-- Dynamic Servings Scaler -->
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Khẩu phần:</span>
                    <div style="display: flex; align-items: center; gap: 0.35rem; background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 99px; padding: 0.2rem 0.65rem;">
                        <button type="button" id="btnDecServing" style="border:none; background:transparent; font-size:1.15rem; font-weight:bold; cursor:pointer; padding:0 0.4rem; color:var(--primary); line-height:1;">−</button>
                        <strong id="servingsDisplay" data-base="<?= $baseServings ?>" style="color:var(--primary); font-size:0.92rem; min-width: 50px; text-align: center;">
                            <?= $baseServings ?> người
                        </strong>
                        <button type="button" id="btnIncServing" style="border:none; background:transparent; font-size:1.15rem; font-weight:bold; cursor:pointer; padding:0 0.4rem; color:var(--primary); line-height:1;">+</button>
                    </div>
                </div>
            </div>

            <div class="ingredients-dotted-table">
                <?php foreach ($ingredientLines as $ing): ?>
                    <?php
                    $parts = preg_split('/\s{2,}|\t|\s+(?=\d)/u', $ing, 2);
                    $name = $parts[0] ?? $ing;
                    $amount = $parts[1] ?? 'vừa đủ';
                    ?>
                    <label class="ingredient-row" style="cursor: pointer;">
                        <input type="checkbox" style="width: 18px; height: 18px; margin-right: 10px; accent-color: #ea580c;">
                        <span class="ingredient-item-name"><?= e($name) ?></span>
                        <span class="ingredient-dots"></span>
                        <span class="ingredient-item-amount" data-raw="<?= e($amount) ?>"><?= e($amount) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Nutrition Facts Card (FDA/Tasty Style) -->
        <?php if (!empty($recipe['calories'])): ?>
            <section class="card mb-6" style="padding: 1.5rem; background: #ffffff; border-radius: 1rem; border: 1.5px solid #fed7aa;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
                    <div style="flex: 1; min-width: 260px;">
                        <h3 style="font-size: 1.25rem; font-weight: 800; margin: 0 0 0.5rem 0; color: #0f172a; display: flex; align-items: center; gap: 0.5rem;">
                            🥗 Bảng Giá Trị Dinh Dưỡng <span style="font-size: 0.8rem; font-weight: normal; color: #64748b;">(Ước tính / 1 người)</span>
                        </h3>
                        <p style="font-size: 0.88rem; color: #64748b; margin: 0 0 1rem 0; line-height: 1.5;">
                            Thông tin dinh dưỡng tiêu chuẩn giúp bạn tính toán macro, calo nạp vào cơ thể và duy trì lối sống lành mạnh.
                        </p>
                        <?php if (!empty($recipe['dietary_tags'])): ?>
                            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                <?php 
                                $tags = array_map('trim', explode(',', (string) $recipe['dietary_tags']));
                                foreach ($tags as $t):
                                ?>
                                    <span class="badge-diet-pill">🌿 <?= e($t) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="nutrition-facts-card" style="min-width: 230px; flex-shrink: 0;">
                        <div class="nutrition-facts-header">
                            <div class="nutrition-facts-title">Nutrition Facts</div>
                            <div class="nutrition-facts-subtitle">Khẩu phần: 1 người</div>
                        </div>
                        <div class="nutrition-cal-row">
                            <span class="nutrition-cal-label">Năng lượng</span>
                            <span class="nutrition-cal-val"><?= (int)$recipe['calories'] ?> <span style="font-size: 1rem; font-weight: normal; color: #64748b;">kcal</span></span>
                        </div>
                        <div class="nutrition-macro-row">
                            <span class="nutrition-macro-label">🥩 Chất đạm (Protein)</span>
                            <span class="nutrition-macro-val"><?= (int)($recipe['protein'] ?? 0) ?> g</span>
                        </div>
                        <div class="nutrition-macro-row">
                            <span class="nutrition-macro-label">🌾 Tinh bột (Carbs)</span>
                            <span class="nutrition-macro-val"><?= (int)($recipe['carbs'] ?? 0) ?> g</span>
                        </div>
                        <div class="nutrition-macro-row" style="border-bottom: 3px solid #0f172a;">
                            <span class="nutrition-macro-label">🥑 Chất béo (Fat)</span>
                            <span class="nutrition-macro-val"><?= (int)($recipe['fat'] ?? 0) ?> g</span>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Step-by-Step Cooking Instructions -->
        <section class="steps-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <h2 style="margin: 0; font-size: 1.25rem;">
                    👩‍🍳 Các bước thực hiện (<?= count($instructionSteps) ?> bước)
                </h2>
                <small style="color: var(--text-muted); font-size: 0.82rem;">Tích vào bước đã hoàn thành để dễ theo dõi</small>
            </div>

            <div class="steps-list">
                <?php foreach ($instructionSteps as $index => $stepContent): ?>
                    <div class="step-item" id="step-row-<?= $index ?>" style="cursor: pointer; transition: all 0.2s;">
                        <div class="step-badge" id="step-badge-<?= $index ?>"><?= $index + 1 ?></div>
                        <div class="step-content" style="flex:1;">
                            <?= nl2br(e($stepContent)) ?>
                        </div>
                        <input type="checkbox" class="step-checkbox" data-step-index="<?= $index ?>" style="width: 20px; height: 20px; accent-color: #16a34a; cursor: pointer;" title="Đánh dấu đã xong bước này">
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Author Tips / Bí quyết nấu ngon -->
        <?php if (!empty($recipe['tips'])): ?>
            <section class="recipe-detail-hero-card" style="margin-bottom: 2rem; background: #fffaf0; border: 2px solid #fed7aa;">
                <h2 style="font-size: 1.15rem; font-weight: 800; color: #ea580c; margin: 0 0 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    💡 Bí quyết nấu ngon từ tác giả
                </h2>
                <div style="font-size: 0.95rem; color: #4b5563; line-height: 1.65;">
                    <?= nl2br(e($recipe['tips'])) ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Community Reviews & Cooksnap Section -->
        <section class="reviews-card" id="comments">
            <h2>
                <span>💬 Nhận xét & Đánh giá</span>
                <span style="font-size: 1rem; font-weight: normal; color: #6b7280;">(<?= count($comments) ?> nhận xét)</span>
            </h2>

            <!-- Review Form -->
            <?php if (is_logged_in()): ?>
                <div class="review-form-box">
                    <form method="post" action="<?= BASE_URL ?>/actions/comment_action.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                        <input type="hidden" name="return_url" value="views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">

                        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <label style="font-size: 0.95rem; font-weight: 700; margin: 0;">
                                Chấm điểm món ăn:
                            </label>
                            <select name="rating" style="width: auto; padding: 0.5rem 1rem; border-radius: 9999px; border: 1.5px solid #fed7aa; font-weight: 700; color: #ea580c; background: #ffffff;">
                                <option value="5" selected>⭐⭐⭐⭐⭐ Tuyệt hảo (5 sao)</option>
                                <option value="4">⭐⭐⭐⭐ Rất ngon (4 sao)</option>
                                <option value="3">⭐⭐⭐ Hài lòng (3 sao)</option>
                                <option value="2">⭐⭐ Bình thường (2 sao)</option>
                                <option value="1">⭐ Cần cải thiện (1 sao)</option>
                            </select>
                        </div>

                        <label>
                            <textarea name="content" rows="3" placeholder="Chia sẻ cảm nhận, lời khuyên hoặc mẹo nấu món này của bạn..." required style="background: #ffffff;"></textarea>
                        </label>

                        <!-- Cooksnap Photo Attachment -->
                        <label style="margin-top: -0.25rem;">
                            <span style="font-size: 0.88rem; font-weight: 600; color: var(--text-main); display: block; margin-bottom: 0.35rem;">
                                📸 Đính kèm ảnh Cooksnap (Ảnh thành phẩm bạn đã nấu - Tùy chọn):
                            </span>
                            <input type="file" name="cooksnap_image" accept="image/jpeg,image/png,image/webp" style="padding: 0.4rem 0; font-size: 0.88rem;">
                            <small style="color: var(--text-muted); font-size: 0.78rem;">Định dạng: JPG, PNG, WEBP &bull; Tối đa 5MB</small>
                        </label>

                        <button type="submit" class="button button-create" style="justify-self: start; margin-top: 0.5rem;">
                            Gửi đánh giá & Cooksnap
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state-box" style="margin-bottom: 2rem; padding: 2rem;">
                    <p style="font-size: 1.1rem; font-weight: 700; color: #374151;">Bạn đã nấu thử món này chưa?</p>
                    <p style="color: #6b7280; margin-bottom: 1.25rem;">Đăng nhập để chia sẻ cảm nhận, gửi ảnh Cooksnap và để lại đánh giá cho tác giả nhé!</p>
                    <button type="button" class="button button-create" data-open-login>Đăng nhập ngay</button>
                </div>
            <?php endif; ?>

            <!-- Comments List -->
            <div class="reviews-list">
                <?php foreach ($topLevelComments as $comm): ?>
                    <article class="review-item-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem;">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <div class="author-mini-avatar" style="width: 32px; height: 32px; font-size: 0.9rem;">
                                    <?= mb_substr((string) $comm['author_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <strong style="display: block; color: #1f2937; font-size: 0.95rem;">
                                        <?= e($comm['author_name']) ?>
                                        <?php if ((int)$comm['user_id'] === (int)$recipe['author_id']): ?>
                                            <span class="author-tag-badge">👑 Tác giả</span>
                                        <?php endif; ?>
                                    </strong>
                                    <div style="color: #f59e0b; font-size: 0.85rem;">
                                        <?= str_repeat('⭐', max(1, min(5, (int) ($comm['rating'] ?? 5)))) ?>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 0.82rem; color: #9ca3af;"><?= date('d/m/Y H:i', strtotime((string) $comm['created_at'])) ?></span>
                                <?php if (is_logged_in() && ((int) $comm['user_id'] === (int) $_SESSION['user_id'] || is_admin())): ?>
                                    <form method="post" action="<?= BASE_URL ?>/actions/comment_action.php" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa bình luận này?')">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="comment_id" value="<?= (int) $comm['id'] ?>">
                                        <input type="hidden" name="return_url" value="views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>#comments">
                                        <button type="submit" class="comment-delete-btn" title="Xóa">&times; Xóa</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p style="color: #4b5563; line-height: 1.6; margin: 0; font-size: 0.95rem; padding-left: 2.6rem;">
                            <?= nl2br(e($comm['content'])) ?>
                        </p>

                        <!-- Cooksnap Image Display -->
                        <?php if (!empty($comm['image_url'])): ?>
                            <div style="padding-left: 2.6rem; margin-top: 0.75rem;">
                                <a href="<?= BASE_URL . '/' . e($comm['image_url']) ?>" target="_blank" title="Xem ảnh phóng to">
                                    <img src="<?= BASE_URL . '/' . e($comm['image_url']) ?>" alt="Ảnh Cooksnap từ <?= e($comm['author_name']) ?>"
                                        style="max-width: 220px; max-height: 160px; object-fit: cover; border-radius: 0.6rem; border: 2px solid #fed7aa; display: block; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                                </a>
                                <small style="display: block; font-size: 0.75rem; color: var(--primary); font-weight: 600; margin-top: 0.3rem;">
                                    🍳 Cooksnap của <?= e($comm['author_name']) ?>
                                </small>
                            </div>
                        <?php endif; ?>

                        <!-- Reply action button -->
                        <div>
                            <?php if (is_logged_in()): ?>
                                <button type="button" class="btn-reply-comment" onclick="toggleReplyForm(<?= (int) $comm['id'] ?>)">
                                    💬 Trả lời
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-reply-comment" data-open-login>
                                    💬 Trả lời
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Reply Box (Hidden by default) -->
                        <div id="reply-box-<?= (int) $comm['id'] ?>" class="reply-form-box">
                            <form method="post" action="<?= BASE_URL ?>/actions/comment_action.php">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                                <input type="hidden" name="parent_id" value="<?= (int) $comm['id'] ?>">
                                <input type="hidden" name="return_url" value="views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>#reply-box-<?= (int) $comm['id'] ?>">
                                <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                    <textarea name="content" rows="2" placeholder="Trả lời nhận xét của <?= e($comm['author_name']) ?>..." required style="flex: 1; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1.5px solid #fed7aa; font-size: 0.88rem;"></textarea>
                                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                        <button type="submit" class="button button-create" style="padding: 0.45rem 0.85rem; font-size: 0.82rem;">Gửi</button>
                                        <button type="button" class="button button-outline" onclick="toggleReplyForm(<?= (int) $comm['id'] ?>)" style="padding: 0.35rem 0.65rem; font-size: 0.78rem;">Hủy</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Nested Replies List -->
                        <?php if (!empty($repliesByParent[(int) $comm['id']])): ?>
                            <div class="comment-replies-list" style="margin-left: 2.6rem; margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                <?php foreach ($repliesByParent[(int) $comm['id']] as $rep): ?>
                                    <?php $isRepAuthor = ((int) $rep['user_id'] === (int) $recipe['author_id']); ?>
                                    <div class="comment-reply-item">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
                                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                                <strong style="font-size: 0.88rem; color: #1f2937;"><?= e($rep['author_name']) ?></strong>
                                                <?php if ($isRepAuthor): ?>
                                                    <span class="author-tag-badge">👑 Tác giả</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <span style="font-size: 0.75rem; color: #9ca3af;"><?= date('d/m/Y H:i', strtotime((string) $rep['created_at'])) ?></span>
                                                <?php if (is_logged_in() && ((int) $rep['user_id'] === (int) $_SESSION['user_id'] || is_admin())): ?>
                                                    <form method="post" action="<?= BASE_URL ?>/actions/comment_action.php" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa phản hồi này?')">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="comment_id" value="<?= (int) $rep['id'] ?>">
                                                        <input type="hidden" name="return_url" value="views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>#comments">
                                                        <button type="submit" class="comment-delete-btn" title="Xóa" style="font-size: 0.75rem;">&times; Xóa</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p style="color: #4b5563; font-size: 0.88rem; margin: 0; line-height: 1.5;">
                                            <?= nl2br(e($rep['content'])) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>

                <?php if (empty($topLevelComments)): ?>
                    <p style="text-align: center; color: #9ca3af; padding: 2rem 0; font-style: italic;">
                        Chưa có nhận xét nào cho món này. Hãy là người đầu tiên nấu thử và chấm điểm nhé!
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Smart Related Recipes Section -->
        <?php if (!empty($relatedRecipes)): ?>
            <section class="related-recipes-section mb-8" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                    <h2 style="font-size: 1.35rem; font-weight: 800; margin: 0; color: #1e293b;">
                        ✨ Có thể bạn cũng thích món này
                    </h2>
                    <a href="<?= BASE_URL ?>/index.php?cat=<?= urlencode($recipe['category']) ?>" style="font-size: 0.9rem; color: var(--primary); font-weight: 600; text-decoration: none;">
                        Xem thêm món <?= e($recipe['category']) ?> &rarr;
                    </a>
                </div>
                <div class="recipe-grid">
                    <?php foreach ($relatedRecipes as $rel): ?>
                        <?php 
                        $rImg = !empty($rel['image_url']) ? BASE_URL . '/' . e($rel['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                        ?>
                        <article class="recipe-card">
                            <div class="recipe-card-media">
                                <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int)$rel['id'] ?>">
                                    <img src="<?= $rImg ?>" alt="<?= e($rel['title']) ?>" loading="lazy">
                                </a>
                                <span class="card-category-badge"><?= e($rel['category']) ?></span>
                                <?php if (!empty($rel['calories'])): ?>
                                    <span class="badge-cal-pill" style="position:absolute; bottom:0.65rem; left:0.65rem; z-index:2;">🔥 <?= (int)$rel['calories'] ?> kcal</span>
                                <?php endif; ?>
                            </div>
                            <div class="recipe-card-body">
                                <div class="card-meta-row">
                                    <span>⏱️ <?= e($rel['cooking_time']) ?></span>
                                    <span>⭐ <?= round((float)($rel['avg_rating'] ?? 5), 1) ?> (<?= (int)$rel['review_count'] ?>)</span>
                                </div>
                                <h3>
                                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int)$rel['id'] ?>">
                                        <?= e($rel['title']) ?>
                                    </a>
                                </h3>
                                <div class="card-author-chip">
                                    <span>Bởi <strong><?= e($rel['author_name']) ?></strong></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ==========================================================================
     COOKPAD-STYLE HANDS-FREE COOKING MODE & KITCHEN TIMER OVERLAY
     ========================================================================== -->
<div id="cookingModeOverlay" class="hidden" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(17, 24, 39, 0.92); z-index:999999; backdrop-filter:blur(8px); display:flex; align-items:center; justify-content:center; padding:1.25rem; box-sizing:border-box;">
    <div style="background:#ffffff; width:100%; max-width:760px; max-height:92vh; border-radius:1.5rem; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        
        <!-- Header -->
        <div style="padding:1.25rem 1.75rem; background:#fff7ed; border-bottom:1.5px solid #fed7aa; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <span style="font-size:0.8rem; font-weight:800; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:block;">
                    🍳 Chế độ nấu bếp rảnh tay
                </span>
                <h3 style="margin:0.2rem 0 0; font-size:1.15rem; color:var(--text-main); font-weight:800;" id="cmRecipeTitle">
                    <?= e($recipe['title'] ?? '') ?>
                </h3>
            </div>
            <button type="button" id="btnCloseCookingMode" style="border:none; background:#fee2e2; color:#dc2626; width:36px; height:36px; border-radius:50%; font-size:1.4rem; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; line-height:1;">
                &times;
            </button>
        </div>

        <!-- Body -->
        <div style="padding:2rem 2.25rem; flex:1; overflow-y:auto;">
            <!-- Step Badge & Counter -->
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
                <span id="cmStepBadge" style="background:var(--primary); color:#ffffff; font-weight:800; padding:0.3rem 0.9rem; border-radius:99px; font-size:0.95rem;">
                    Bước 1
                </span>
                <span id="cmStepProgress" style="font-size:0.9rem; color:var(--text-muted); font-weight:600;">
                    1 / <?= count($instructionSteps) ?>
                </span>
            </div>

            <!-- Big Step Instruction Text -->
            <div id="cmStepContent" style="font-size:1.45rem; line-height:1.65; color:#111827; font-weight:600; min-height:140px; padding:1rem 0;">
                <!-- Step content injected here -->
            </div>

            <!-- Smart Kitchen Timer Box -->
            <div style="margin-top:1.5rem; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:1rem; padding:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
                    <strong style="font-size:0.95rem; color:#334155; display:flex; align-items:center; gap:0.4rem;">
                        ⏱️ Hẹn giờ nấu ăn
                    </strong>
                    <!-- Preset chips -->
                    <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                        <button type="button" class="cm-preset-btn" data-time="60">1p</button>
                        <button type="button" class="cm-preset-btn" data-time="180">3p</button>
                        <button type="button" class="cm-preset-btn" data-time="300">5p</button>
                        <button type="button" class="cm-preset-btn" data-time="600">10p</button>
                        <button type="button" class="cm-preset-btn" data-time="900">15p</button>
                    </div>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                    <div id="cmTimerDisplay" style="font-size:2.4rem; font-weight:800; font-family:monospace; color:#ea580c; letter-spacing:0.05em;">
                        05:00
                    </div>
                    <div style="display:flex; gap:0.5rem;">
                        <button type="button" id="btnTimerStart" style="padding:0.6rem 1.25rem; background:var(--primary); color:#fff; border:none; border-radius:0.5rem; font-weight:700; cursor:pointer;">
                            ▶️ Bắt đầu
                        </button>
                        <button type="button" id="btnTimerPause" style="padding:0.6rem 1rem; background:#e2e8f0; color:#334155; border:none; border-radius:0.5rem; font-weight:700; cursor:pointer;">
                            ⏸️ Dừng
                        </button>
                        <button type="button" id="btnTimerReset" style="padding:0.6rem 1rem; background:#fee2e2; color:#dc2626; border:none; border-radius:0.5rem; font-weight:700; cursor:pointer;">
                            🔄 Đặt lại
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Navigation Controls -->
        <div style="padding:1.25rem 2rem; background:#f9fafb; border-top:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; gap:1rem;">
            <button type="button" id="cmPrevStep" style="padding:0.85rem 1.75rem; border:2px solid #cbd5e1; border-radius:0.75rem; background:#fff; font-size:1.05rem; font-weight:700; color:#475569; cursor:pointer;">
                &larr; Bước trước
            </button>
            <button type="button" id="cmNextStep" style="padding:0.85rem 2rem; border:none; border-radius:0.75rem; background:var(--primary); font-size:1.05rem; font-weight:700; color:#ffffff; cursor:pointer; box-shadow:0 4px 12px rgba(234,88,12,0.3);">
                Bước tiếp theo &rarr;
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dynamic Servings Scaler
    const servingsDisplay = document.querySelector('#servingsDisplay');
    const btnInc = document.querySelector('#btnIncServing');
    const btnDec = document.querySelector('#btnDecServing');

    if (servingsDisplay && btnInc && btnDec) {
        const baseServings = parseInt(servingsDisplay.dataset.base, 10) || 2;
        let currentServings = baseServings;

        const updateServings = (newServings) => {
            if (newServings < 1 || newServings > 20) return;
            currentServings = newServings;
            servingsDisplay.textContent = currentServings + ' người';

            const ratio = currentServings / baseServings;
            document.querySelectorAll('.ingredient-item-amount').forEach((el) => {
                const raw = el.dataset.raw || el.textContent;
                // Match leading numbers (integers or decimals)
                const match = raw.match(/^([\d\.,]+)(\s*.*)$/);
                if (match) {
                    const originalNum = parseFloat(match[1].replace(',', '.'));
                    if (!isNaN(originalNum)) {
                        let scaled = originalNum * ratio;
                        // Format nicely: round to 1 decimal place if has decimals
                        scaled = scaled >= 10 ? Math.round(scaled) : Math.round(scaled * 10) / 10;
                        el.textContent = scaled + match[2];
                    }
                }
            });
        };

        btnInc.addEventListener('click', () => updateServings(currentServings + 1));
        btnDec.addEventListener('click', () => updateServings(currentServings - 1));
    }

    // 2. Step Completion Checkboxes
    document.querySelectorAll('.step-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', (e) => {
            const idx = e.target.dataset.stepIndex;
            const row = document.querySelector('#step-row-' + idx);
            const badge = document.querySelector('#step-badge-' + idx);
            if (e.target.checked) {
                row.style.opacity = '0.55';
                badge.style.background = '#16a34a';
                badge.textContent = '✓';
            } else {
                row.style.opacity = '1';
                badge.style.background = 'var(--primary)';
                badge.textContent = parseInt(idx, 10) + 1;
            }
        });
    });

    // 3. Hands-Free Cooking Mode
    const stepsData = <?= json_encode($instructionSteps, JSON_UNESCAPED_UNICODE) ?>;
    const cookingOverlay = document.querySelector('#cookingModeOverlay');
    const btnOpenCM = document.querySelector('#btnOpenCookingMode');
    const btnCloseCM = document.querySelector('#btnCloseCookingMode');
    const cmStepBadge = document.querySelector('#cmStepBadge');
    const cmStepProgress = document.querySelector('#cmStepProgress');
    const cmStepContent = document.querySelector('#cmStepContent');
    const cmPrevStep = document.querySelector('#cmPrevStep');
    const cmNextStep = document.querySelector('#cmNextStep');

    let currentStepIdx = 0;

    function renderCookingStep(idx) {
        if (!stepsData || stepsData.length === 0) return;
        currentStepIdx = Math.max(0, Math.min(idx, stepsData.length - 1));
        cmStepBadge.textContent = `Bước ${currentStepIdx + 1}`;
        cmStepProgress.textContent = `${currentStepIdx + 1} / ${stepsData.length}`;
        cmStepContent.innerHTML = stepsData[currentStepIdx].replace(/\n/g, '<br>');

        cmPrevStep.disabled = currentStepIdx === 0;
        cmPrevStep.style.opacity = currentStepIdx === 0 ? '0.4' : '1';

        if (currentStepIdx === stepsData.length - 1) {
            cmNextStep.textContent = '🎉 Hoàn tất nấu ăn!';
            cmNextStep.style.background = '#16a34a';
        } else {
            cmNextStep.textContent = 'Bước tiếp theo →';
            cmNextStep.style.background = 'var(--primary)';
        }
    }

    if (btnOpenCM) {
        btnOpenCM.addEventListener('click', () => {
            cookingOverlay.classList.remove('hidden');
            renderCookingStep(0);
        });
    }

    if (btnCloseCM) {
        btnCloseCM.addEventListener('click', () => {
            cookingOverlay.classList.add('hidden');
        });
    }

    if (cmPrevStep) {
        cmPrevStep.addEventListener('click', () => renderCookingStep(currentStepIdx - 1));
    }

    if (cmNextStep) {
        cmNextStep.addEventListener('click', () => {
            if (currentStepIdx === stepsData.length - 1) {
                if (window.showToast) window.showToast('Chúc mừng bạn đã hoàn thành món ăn!');
                cookingOverlay.classList.add('hidden');
            } else {
                renderCookingStep(currentStepIdx + 1);
            }
        });
    }

    // 4. Kitchen Countdown Timer
    let timerTotalSeconds = 300;
    let timerRemainingSeconds = 300;
    let timerInterval = null;
    const timerDisplay = document.querySelector('#cmTimerDisplay');
    const btnTStart = document.querySelector('#btnTimerStart');
    const btnTPause = document.querySelector('#btnTimerPause');
    const btnTReset = document.querySelector('#btnTimerReset');

    function formatTime(sec) {
        const m = Math.floor(sec / 60).toString().padStart(2, '0');
        const s = (sec % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    }

    function playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880; // A5 note
            gain.gain.value = 0.5;
            osc.start();
            setTimeout(() => {
                osc.stop();
                ctx.close();
            }, 600);
        } catch (e) {}
    }

    function startTimer() {
        if (timerInterval) return;
        timerInterval = setInterval(() => {
            if (timerRemainingSeconds > 0) {
                timerRemainingSeconds--;
                timerDisplay.textContent = formatTime(timerRemainingSeconds);
            } else {
                clearInterval(timerInterval);
                timerInterval = null;
                playBeep();
                if (window.showToast) window.showToast('🔔 Hết thời gian nấu ăn!', 'info');
                timerDisplay.style.color = '#16a34a';
            }
        }, 1000);
    }

    function pauseTimer() {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    function resetTimer(sec = timerTotalSeconds) {
        pauseTimer();
        timerRemainingSeconds = sec;
        timerDisplay.textContent = formatTime(timerRemainingSeconds);
        timerDisplay.style.color = '#ea580c';
    }

    document.querySelectorAll('.cm-preset-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const time = parseInt(btn.dataset.time, 10);
            timerTotalSeconds = time;
            resetTimer(time);
        });
    });

    btnTStart?.addEventListener('click', startTimer);
    btnTPause?.addEventListener('click', pauseTimer);
    btnTReset?.addEventListener('click', () => resetTimer());
});
</script>

<style>
.cm-preset-btn {
    padding: 0.25rem 0.6rem;
    font-size: 0.8rem;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    border-radius: 99px;
    background: #fff;
    cursor: pointer;
    color: #475569;
}
.cm-preset-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}
</style>

<?php
require __DIR__ . '/../includes/modal-login.php';
require __DIR__ . '/../includes/footer.php';
