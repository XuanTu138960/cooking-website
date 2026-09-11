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

$recipes = db()->query(
    'SELECT r.id, r.title, r.status, r.created_at, u.username AS author_name 
     FROM recipes r 
     INNER JOIN users u ON u.id = r.author_id 
     ORDER BY r.created_at DESC'
)->fetchAll();

$pageTitle = 'Quản lý công thức - Cookio';
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
    <h1>Quản lý tất cả công thức</h1>

    <?php if (isset($_GET['success'])): ?>
        <p class="notice success" style="margin-bottom: 1rem;">
            <?= match ($_GET['success']) {
                'deleted' => 'Đã xóa công thức thành công.',
                'updated' => 'Cập nhật trạng thái công thức thành công.',
                default => 'Thao tác hoàn tất.'
            } ?>
        </p>
    <?php endif; ?>

    <div class="admin-table-box">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên món</th>
                        <th>Tác giả</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recipes as $recipe): ?>
                    <tr>
                        <td>#<?= (int) $recipe['id'] ?></td>
                        <td>
                            <strong><?= e($recipe['title']) ?></strong>
                            <br>
                            <small><a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>" target="_blank">Xem trang chi tiết</a></small>
                        </td>
                        <td><?= e($recipe['author_name']) ?></td>
                        <td>
                            <span class="status-badge status-<?= e($recipe['status']) ?>">
                                <?= match ($recipe['status']) {
                                    'approved' => 'Đã duyệt',
                                    'rejected' => 'Từ chối',
                                    default => 'Chờ duyệt',
                                } ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime((string) $recipe['created_at'])) ?></td>
                        <td>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <?php if ($recipe['status'] !== 'approved'): ?>
                                    <form method="post" action="<?= BASE_URL ?>/actions/admin_action.php" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                                        <input type="hidden" name="return_url" value="admin/manage-recipes.php">
                                        <button type="submit" name="status" value="approved" class="button-sm">Duyệt</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($recipe['status'] !== 'rejected'): ?>
                                    <form method="post" action="<?= BASE_URL ?>/actions/admin_action.php" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                                        <input type="hidden" name="return_url" value="admin/manage-recipes.php">
                                        <button type="submit" name="status" value="rejected" class="button-sm danger">Từ chối</button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" action="<?= BASE_URL ?>/actions/admin_action.php" style="display:inline" onsubmit="return confirm('Bạn có chắc muốn xóa vĩnh viễn công thức này?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_recipe">
                                    <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                                    <input type="hidden" name="return_url" value="admin/manage-recipes.php">
                                    <button type="submit" class="button-sm danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recipes): ?>
                    <tr><td colspan="6" style="text-align: center;">Chưa có công thức nào.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
