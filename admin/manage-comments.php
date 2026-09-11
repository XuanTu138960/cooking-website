<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!is_admin()) {
    redirect('index.php');
}

$comments = db()->query(
    'SELECT c.id, c.content, c.rating, c.image_url, c.created_at, 
            u.username AS author_name, 
            r.id AS recipe_id, r.title AS recipe_title 
     FROM comments c 
     INNER JOIN users u ON u.id = c.user_id 
     INNER JOIN recipes r ON r.id = c.recipe_id 
     ORDER BY c.created_at DESC'
)->fetchAll();

$pageTitle = 'Quản lý bình luận - Cookio Admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-page">
<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
<main class="admin-content">
    <h1>Quản lý bình luận & đánh giá (<?= count($comments) ?>)</h1>

    <?php if (isset($_GET['comment']) && $_GET['comment'] === 'deleted'): ?>
        <p class="notice success" style="margin-bottom: 1rem;">Đã xóa bình luận thành công.</p>
    <?php endif; ?>

    <div class="admin-table-box">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Người gửi</th>
                        <th>Công thức</th>
                        <th>Đánh giá</th>
                        <th>Nội dung & Cooksnap</th>
                        <th>Thời gian</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($comments as $comm): ?>
                    <tr>
                        <td>#<?= (int) $comm['id'] ?></td>
                        <td><strong><?= e($comm['author_name']) ?></strong></td>
                        <td>
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $comm['recipe_id'] ?>" target="_blank" style="color: #ea580c; font-weight: 600;">
                                <?= e($comm['recipe_title']) ?>
                            </a>
                        </td>
                        <td>
                            <span style="color:#f59e0b; white-space:nowrap;">
                                <?= str_repeat('⭐', max(1, min(5, (int) ($comm['rating'] ?? 5)))) ?>
                            </span>
                        </td>
                        <td>
                            <div><?= nl2br(e($comm['content'])) ?></div>
                            <?php if (!empty($comm['image_url'])): ?>
                                <div style="margin-top:0.4rem;">
                                    <a href="<?= BASE_URL . '/' . e($comm['image_url']) ?>" target="_blank">
                                        <img src="<?= BASE_URL . '/' . e($comm['image_url']) ?>" alt="Cooksnap" style="height:45px; border-radius:4px; object-fit:cover; border:1px solid #ddd;">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime((string) $comm['created_at'])) ?></td>
                        <td>
                            <form method="post" action="<?= BASE_URL ?>/actions/comment_action.php" onsubmit="return confirm('Bạn có chắc muốn xóa bình luận này?')">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="comment_id" value="<?= (int) $comm['id'] ?>">
                                <input type="hidden" name="return_url" value="admin/manage-comments.php">
                                <button type="submit" class="button-sm danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($comments)): ?>
                    <tr><td colspan="6" style="text-align: center;">Chưa có bình luận nào trên hệ thống.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
