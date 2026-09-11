<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$authorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$authorId) {
    redirect('index.php');
}

$stmtUser = db()->prepare('SELECT id, username, role, bio, created_at FROM users WHERE id = ?');
$stmtUser->execute([$authorId]);
$author = $stmtUser->fetch();

if (!$author) {
    redirect('index.php');
}

// Fetch approved recipes by this author
$stmtRecipes = db()->prepare(
    "SELECT r.*, u.username AS author_name,
     COALESCE((SELECT ROUND(AVG(c.rating), 1) FROM comments c WHERE c.recipe_id = r.id), 5.0) AS avg_rating,
     (SELECT COUNT(*) FROM comments c WHERE c.recipe_id = r.id) AS review_count
     FROM recipes r
     INNER JOIN users u ON u.id = r.author_id
     WHERE r.author_id = ? AND r.status = 'approved'
     ORDER BY r.created_at DESC"
);
$stmtRecipes->execute([$authorId]);
$authorRecipes = $stmtRecipes->fetchAll();

// Author stats
$totalRecipes = count($authorRecipes);
$totalViews = array_sum(array_column($authorRecipes, 'views_count'));
$avgRating = $totalRecipes > 0 ? round(array_sum(array_column($authorRecipes, 'avg_rating')) / $totalRecipes, 1) : 5.0;

// Followers count & state
$followerCount = (int) db()->query("SELECT COUNT(*) FROM follows WHERE author_id = $authorId")->fetchColumn();
$isFollowing = false;
if (is_logged_in() && (int) $_SESSION['user_id'] !== $authorId) {
    $stmtCheckFollow = db()->prepare('SELECT id FROM follows WHERE follower_id = ? AND author_id = ?');
    $stmtCheckFollow->execute([(int) $_SESSION['user_id'], $authorId]);
    $isFollowing = (bool) $stmtCheckFollow->fetch();
}

// User saved recipes
$userSavedIds = [];
if (is_logged_in()) {
    $stmtSaved = db()->prepare('SELECT recipe_id FROM saved_recipes WHERE user_id = ?');
    $stmtSaved->execute([(int) $_SESSION['user_id']]);
    $userSavedIds = $stmtSaved->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = 'Bếp của ' . e($author['username']) . ' - Cookio';
require __DIR__ . '/../includes/header.php';
?>
<div class="recipe-detail-container" style="padding-top: 2rem; padding-bottom: 3rem;">

    <!-- Breadcrumb -->
    <nav class="breadcrumb-nav" style="margin-bottom: 1.5rem;">
        <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>
        <span>›</span>
        <span>Đầu bếp</span>
        <span>›</span>
        <span><?= e($author['username']) ?></span>
    </nav>

    <?php if (isset($_GET['follow'])): ?>
        <p class="notice success" style="margin-bottom: 1.5rem;">
            <?= $_GET['follow'] === 'followed' ? '✅ Bạn đã bắt đầu theo dõi đầu bếp ' . e($author['username']) . '!' : 'Đã hủy theo dõi đầu bếp.' ?>
        </p>
    <?php endif; ?>

    <!-- Author Profile Hero Card -->
    <div style="background: #ffffff; border-radius: 1.25rem; border: 1px solid var(--border); padding: 2.25rem; margin-bottom: 2rem; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
        <div style="display: flex; align-items: center; gap: 1.75rem; flex-wrap: wrap;">
            <!-- Big Avatar -->
            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #c2410c); display: flex; align-items: center; justify-content: center; font-size: 2.8rem; font-weight: 800; color: #ffffff; flex-shrink: 0; box-shadow: 0 6px 20px rgba(234, 88, 12, 0.3);">
                <?= mb_strtoupper(mb_substr($author['username'], 0, 1)) ?>
            </div>

            <!-- Bio & Details -->
            <div style="flex: 1; min-width: 240px;">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
                    <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main); margin: 0;"><?= e($author['username']) ?></h1>
                    <span style="display: inline-block; font-size: 0.8rem; font-weight: 700; padding: 0.2rem 0.75rem; border-radius: 99px;
                        <?= $author['role'] === 'admin' ? 'background:#fef3c7; color:#d97706;' : 'background:var(--primary-light); color:var(--primary);' ?>">
                        <?= $author['role'] === 'admin' ? '👑 Quản trị viên' : '🍳 Đầu bếp Cookio' ?>
                    </span>

                    <?php if (is_logged_in() && (int) $_SESSION['user_id'] !== $authorId): ?>
                        <form method="post" action="<?= BASE_URL ?>/actions/follow_action.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="author_id" value="<?= $authorId ?>">
                            <input type="hidden" name="return_url" value="views/author.php?id=<?= $authorId ?>">
                            <button type="submit" class="button <?= $isFollowing ? 'button-saved' : 'button-outline' ?>" style="padding: 0.35rem 0.9rem; font-size: 0.85rem; border-radius: 99px; font-weight: 700;">
                                <?= $isFollowing ? '✓ Đang theo dõi' : '+ Theo dõi đầu bếp' ?>
                            </button>
                        </form>
                    <?php elseif (!is_logged_in()): ?>
                        <button type="button" class="button button-outline" data-open-login style="padding: 0.35rem 0.9rem; font-size: 0.85rem; border-radius: 99px; font-weight: 700;">
                            + Theo dõi đầu bếp
                        </button>
                    <?php endif; ?>
                </div>

                <?php 
                $chefBadges = get_chef_badges((int)$authorId);
                if (!empty($chefBadges)):
                ?>
                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.65rem;">
                        <?php foreach ($chefBadges as $cb): ?>
                            <span class="chef-badge <?= $cb['class'] ?>" style="font-size: 0.8rem; padding: 0.2rem 0.65rem;">
                                <?= $cb['icon'] ?> <?= $cb['label'] ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p style="margin: 0 0 0.75rem; font-size: 0.95rem; color: #4b5563; line-height: 1.6;">
                    <?= !empty($author['bio']) ? nl2br(e($author['bio'])) : 'Thành viên đam mê nấu nướng và chia sẻ công thức tại cộng đồng Cookio.' ?>
                </p>

                <span style="font-size: 0.85rem; color: var(--text-muted);">
                    📅 Gia nhập từ ngày <?= date('d/m/Y', strtotime((string) $author['created_at'])) ?>
                </span>
            </div>
        </div>

        <!-- Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem; margin-top: 1.75rem; padding-top: 1.5rem; border-top: 1px solid #f1ede8;">
            <div style="text-align: center;">
                <strong style="display: block; font-size: 1.75rem; font-weight: 800; color: var(--primary);"><?= $totalRecipes ?></strong>
                <span style="font-size: 0.82rem; color: var(--text-muted);">Công thức món</span>
            </div>
            <div style="text-align: center;">
                <strong style="display: block; font-size: 1.75rem; font-weight: 800; color: #16a34a;"><?= number_format($totalViews) ?></strong>
                <span style="font-size: 0.82rem; color: var(--text-muted);">Tổng lượt xem</span>
            </div>
            <div style="text-align: center;">
                <strong style="display: block; font-size: 1.75rem; font-weight: 800; color: #7c3aed;"><?= $followerCount ?></strong>
                <span style="font-size: 0.82rem; color: var(--text-muted);">Người theo dõi</span>
            </div>
            <div style="text-align: center;">
                <strong style="display: block; font-size: 1.75rem; font-weight: 800; color: #f59e0b;">⭐ <?= $avgRating ?></strong>
                <span style="font-size: 0.82rem; color: var(--text-muted);">Điểm đánh giá</span>
            </div>
        </div>
    </div>

    <!-- Author's Recipe Collection Heading -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); margin: 0;">
            🍲 Công thức đã chia sẻ (<?= $totalRecipes ?> món)
        </h2>
    </div>

    <?php if (empty($authorRecipes)): ?>
        <div style="text-align: center; padding: 3rem 1rem; background: #fff; border-radius: 1rem; border: 2px dashed var(--border);">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🍳</div>
            <h3 style="margin: 0 0 0.5rem; font-size: 1.25rem; color: var(--text-main);">Chưa có công thức công khai nào</h3>
            <p style="color: var(--text-muted); margin: 0 0 1.5rem;">Đầu bếp này chưa xuất bản công thức món ăn nào.</p>
            <a href="<?= BASE_URL ?>/index.php" class="button button-create">Khám phá các món ăn khác</a>
        </div>
    <?php else: ?>
        <div class="recipe-grid">
            <?php foreach ($authorRecipes as $recipe): ?>
                <?php 
                $isSaved = in_array((int) $recipe['id'], $userSavedIds, true);
                $imgSrc = !empty($recipe['image_url']) ? BASE_URL . '/' . e($recipe['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                ?>
                <article class="recipe-card">
                    <div class="recipe-card-media">
                        <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                            <img src="<?= $imgSrc ?>" alt="<?= e($recipe['title']) ?>" loading="lazy">
                        </a>
                        <span class="card-category-badge"><?= e($recipe['category'] ?? 'Món chính') ?></span>

                        <!-- Quick Bookmark Button -->
                        <?php if (is_logged_in()): ?>
                            <form method="post" action="<?= BASE_URL ?>/actions/saved_action.php" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                                <input type="hidden" name="return_url" value="views/author.php?id=<?= (int) $author['id'] ?>">
                                <button type="submit" class="card-save-btn" title="<?= $isSaved ? 'Bỏ lưu' : 'Lưu vào yêu thích' ?>">
                                    <?= $isSaved ? '❤️' : '🤍' ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="card-save-btn" data-open-login title="Đăng nhập để lưu món">
                                🤍
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="recipe-card-body">
                        <div class="card-meta-row">
                            <span>⏱️ <?= e($recipe['cooking_time'] ?? '30 phút') ?></span>
                            <span>⭐ <?= e((string) $recipe['avg_rating']) ?> (<?= (int) $recipe['review_count'] ?>)</span>
                            <span>👁️ <?= number_format((int) ($recipe['views_count'] ?? 0)) ?></span>
                        </div>

                        <h3>
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                                <?= e($recipe['title']) ?>
                            </a>
                        </h3>

                        <?php if (!empty($recipe['description'])): ?>
                            <p class="card-desc"><?= e(mb_strimwidth((string) $recipe['description'], 0, 95, '...')) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require __DIR__ . '/../includes/modal-login.php';
require __DIR__ . '/../includes/footer.php';