<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = 'Công thức của tôi - Cookio';
require __DIR__ . '/../includes/header.php';

if (!is_logged_in()) {
    echo '<section class="saved-recipes"><div class="empty-state-box"><p>Vui lòng đăng nhập để xem danh sách công thức của bạn.</p><button type="button" class="button" data-open-login>Đăng nhập ngay</button></div></section>';
    require __DIR__ . '/../includes/modal-login.php';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$userId = (int) $_SESSION['user_id'];
$stmt = db()->prepare('SELECT * FROM recipes WHERE author_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$myRecipes = $stmt->fetchAll();
?>
<div class="recipe-detail-container" style="padding-top:2rem; padding-bottom:3rem;">

    <!-- Page header -->
    <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:2rem;">
        <div>
            <h1 style="font-size:1.8rem; font-weight:800; color:var(--text-main); margin:0 0 0.3rem;">📖 Kho công thức của bạn</h1>
            <p style="margin:0; color:var(--text-muted); font-size:0.95rem;">Quản lý tất cả công thức bạn đã đóng góp cho cộng đồng Cookio.</p>
        </div>
        <a href="<?= BASE_URL ?>/views/create-recipe.php"
            style="display:inline-flex; align-items:center; gap:0.4rem; background:var(--primary); color:#fff; padding:0.7rem 1.4rem; border-radius:0.6rem; font-weight:700; font-size:0.9rem; text-decoration:none; white-space:nowrap; transition:background 0.2s;"
            onmouseover="this.style.background='var(--primary-hover)'" onmouseout="this.style.background='var(--primary)'">
            + Đăng công thức mới
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="notice success" style="margin-bottom:1.25rem;">
            <?= match ($_GET['success']) {
                'deleted' => '✅ Đã xóa công thức thành công.',
                'edited'  => '✅ Đã cập nhật công thức thành công. Công thức đã được gửi vào hàng chờ duyệt.',
                default   => '✅ Thao tác thành công!'
            } ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice error" style="margin-bottom:1.25rem;">
            <?= match ($_GET['error']) {
                'forbidden' => '⚠️ Bạn không có quyền chỉnh sửa công thức này.',
                'csrf'      => '⚠️ Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.',
                default     => '⚠️ Đã có lỗi xảy ra.'
            } ?>
    <?php endif; ?>

    <!-- Chef Studio Analytics Dashboard -->
    <?php
    $totalViews = 0;
    $totalLikes = 0;
    $approvedCount = 0;
    $pendingCount = 0;
    foreach ($myRecipes as $r) {
        $totalViews += (int)($r['views_count'] ?? 0);
        $totalLikes += (int)($r['likes_count'] ?? 0);
        if ($r['status'] === 'approved') $approvedCount++;
        if ($r['status'] === 'pending') $pendingCount++;
    }
    $commStmt = db()->prepare("SELECT COUNT(*) FROM comments c JOIN recipes r ON r.id = c.recipe_id WHERE r.author_id = ?");
    $commStmt->execute([$userId]);
    $totalComments = (int)$commStmt->fetchColumn();

    $followersCount = (int)db()->query("SELECT COUNT(*) FROM follows WHERE author_id = $userId")->fetchColumn();
    ?>
    <div class="chef-studio-banner" style="margin-bottom: 2.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: rgba(255,255,255,0.22); padding: 0.25rem 0.65rem; border-radius: 9999px; display: inline-block;">
                    📊 Chef Studio Analytics
                </span>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0.5rem 0 0.25rem; color: #ffffff;">
                    Hiệu Quả Gian Bếp Của Bạn
                </h2>
                <p style="margin: 0; opacity: 0.9; font-size: 0.9rem;">Thống kê tổng hợp số liệu tương tác từ cộng đồng Cookio đối với các công thức của bạn.</p>
            </div>
            <div>
                <?php 
                $badges = get_chef_badges($userId);
                if (!empty($badges)):
                ?>
                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                        <?php foreach ($badges as $b): ?>
                            <span class="chef-badge <?= $b['class'] ?>" style="font-size: 0.85rem; padding: 0.3rem 0.75rem;"><?= $b['icon'] ?> <?= $b['label'] ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="chef-studio-stats">
            <div>
                <div class="chef-studio-stat-val"><?= number_format($totalViews) ?></div>
                <div class="chef-studio-stat-label">👁️ Tổng lượt xem</div>
            </div>
            <div>
                <div class="chef-studio-stat-val"><?= number_format($totalLikes) ?></div>
                <div class="chef-studio-stat-label">❤️ Lượt thả tim</div>
            </div>
            <div>
                <div class="chef-studio-stat-val"><?= number_format($totalComments) ?></div>
                <div class="chef-studio-stat-label">💬 Đánh giá & Cooksnap</div>
            </div>
            <div>
                <div class="chef-studio-stat-val"><?= number_format($followersCount) ?></div>
                <div class="chef-studio-stat-label">👥 Người theo dõi</div>
            </div>
            <div>
                <div class="chef-studio-stat-val"><?= $approvedCount ?>/<?= count($myRecipes) ?></div>
                <div class="chef-studio-stat-label">✅ Món đã xuất bản</div>
            </div>
        </div>
    </div>

    <?php if (!is_logged_in()): ?>
        <div style="text-align:center; padding:3rem 1rem; background:#fff; border-radius:1rem; border:1px solid var(--border);">
            <div style="font-size:3.5rem; margin-bottom:1rem;">🔒</div>
            <h3 style="margin:0 0 0.5rem; font-size:1.2rem; color:var(--text-main);">Đăng nhập để xem công thức</h3>
            <p style="color:var(--text-muted); margin:0 0 1.25rem;">Bạn cần đăng nhập để quản lý công thức của mình.</p>
            <button type="button" class="button" data-open-login>Đăng nhập ngay</button>
        </div>
    <?php elseif (empty($myRecipes)): ?>
        <div style="text-align:center; padding:3rem 1rem; background:#fff; border-radius:1rem; border:2px dashed var(--border);">
            <div style="font-size:4rem; margin-bottom:1rem;">🍳</div>
            <h3 style="margin:0 0 0.5rem; font-size:1.3rem; color:var(--text-main); font-weight:700;">Bạn chưa đóng góp công thức nào</h3>
            <p style="color:var(--text-muted); margin:0 0 1.5rem;">Hãy chia sẻ bí quyết nấu ăn của bạn với cộng đồng Cookio!</p>
            <a href="<?= BASE_URL ?>/views/create-recipe.php"
                style="display:inline-block; background:var(--primary); color:#fff; padding:0.8rem 2rem; border-radius:0.6rem; font-weight:700; text-decoration:none;">
                ✨ Chia sẻ công thức đầu tiên
            </a>
        </div>
    <?php else: ?>
        <!-- Recipe count info -->
        <p style="color:var(--text-muted); font-size:0.9rem; margin:0 0 1.25rem;">
            Bạn có <strong style="color:var(--primary);"><?= count($myRecipes) ?> công thức</strong> —
            <?= count(array_filter($myRecipes, fn($r) => $r['status'] === 'approved')) ?> đã duyệt,
            <?= count(array_filter($myRecipes, fn($r) => $r['status'] === 'pending')) ?> đang chờ.
        </p>

        <div style="display:flex; flex-direction:column; gap:1rem;">
            <?php foreach ($myRecipes as $recipe): ?>
                <article style="background:#fff; border-radius:0.85rem; border:1px solid var(--border); padding:1.25rem 1.5rem; display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; transition:box-shadow 0.2s;"
                    onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">

                    <!-- Thumbnail -->
                    <?php if (!empty($recipe['image_url'])): ?>
                        <img src="<?= BASE_URL . '/' . e($recipe['image_url']) ?>" alt="<?= e($recipe['title']) ?>"
                            style="width:80px; height:60px; object-fit:cover; border-radius:0.5rem; flex-shrink:0; border:1px solid var(--border);">
                    <?php else: ?>
                        <div style="width:80px; height:60px; background:var(--primary-light); border-radius:0.5rem; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0;">🍽️</div>
                    <?php endif; ?>

                    <!-- Info -->
                    <div style="flex:1; min-width:200px;">
                        <h3 style="margin:0 0 0.3rem; font-size:1rem; font-weight:700; color:var(--text-main);"><?= e($recipe['title']) ?></h3>
                        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                            <span style="font-size:0.82rem; color:var(--text-muted);">
                                📅 <?= date('d/m/Y', strtotime((string) $recipe['created_at'])) ?>
                            </span>
                            <?php if (!empty($recipe['category'])): ?>
                                <span style="font-size:0.78rem; background:var(--primary-light); color:var(--primary); padding:0.15rem 0.6rem; border-radius:99px; font-weight:600;">
                                    <?= e($recipe['category']) ?>
                                </span>
                            <?php endif; ?>
                            <span style="font-size:0.82rem; font-weight:600; padding:0.15rem 0.65rem; border-radius:99px;
                                <?= match($recipe['status']) {
                                    'approved' => 'background:#dcfce7; color:#16a34a;',
                                    'rejected' => 'background:#fee2e2; color:#dc2626;',
                                    default    => 'background:#fef9c3; color:#a16207;'
                                } ?>">
                                <?= match($recipe['status']) {
                                    'approved' => '✅ Đã duyệt',
                                    'rejected' => '❌ Từ chối',
                                    default    => '⏳ Đang chờ duyệt'
                                } ?>
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display:flex; align-items:center; gap:0.6rem; flex-shrink:0;">
                        <?php if ($recipe['status'] === 'approved'): ?>
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>" target="_blank"
                                style="padding:0.5rem 0.9rem; border:1.5px solid var(--border); border-radius:0.45rem; font-size:0.85rem; font-weight:600; color:var(--text-main); text-decoration:none; white-space:nowrap;">
                                👁 Xem
                            </a>
                        <?php endif; ?>

                        <a href="<?= BASE_URL ?>/views/edit-recipe.php?id=<?= (int) $recipe['id'] ?>"
                            style="padding:0.5rem 0.9rem; background:var(--primary); color:#fff; border-radius:0.45rem; font-size:0.85rem; font-weight:600; text-decoration:none; white-space:nowrap;">
                            ✏️ Sửa
                        </a>

                        <form method="post" action="<?= BASE_URL ?>/actions/recipe_action.php" style="display:inline"
                            onsubmit="return confirm('Bạn có chắc muốn xóa công thức «<?= e(addslashes($recipe['title'])) ?>» vĩnh viễn?')">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_own">
                            <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                            <button type="submit"
                                style="padding:0.5rem 0.9rem; background:#fee2e2; color:#dc2626; border:none; border-radius:0.45rem; font-size:0.85rem; font-weight:600; cursor:pointer; white-space:nowrap;"
                                onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                                🗑 Xóa
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require __DIR__ . '/../includes/modal-login.php';
require __DIR__ . '/../includes/footer.php';
