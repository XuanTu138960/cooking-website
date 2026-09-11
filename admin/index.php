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

$pendingRecipes = db()->query(
    "SELECT r.*, u.username AS author_name 
     FROM recipes r 
     INNER JOIN users u ON u.id = r.author_id 
     WHERE r.status = 'pending' 
     ORDER BY r.created_at ASC"
)->fetchAll();

$stats = db()->query(
    "SELECT 
        COUNT(*) AS total, 
        SUM(status = 'pending') AS pending, 
        SUM(status = 'approved') AS approved 
     FROM recipes"
)->fetch();

$userCount = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pageTitle = 'Dashboard Quản trị - Cookio';
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
    <h1>Bảng điều khiển Quản trị viên</h1>

    <?php if (isset($_GET['success'])): ?>
        <p class="notice success" style="margin-bottom: 1rem;">
            <?= match ($_GET['success']) {
                'updated' => 'Cập nhật trạng thái công thức thành công.',
                'deleted' => 'Đã xóa công thức thành công.',
                default => 'Thao tác thực hiện thành công.'
            } ?>
        </p>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <p class="notice error" style="margin-bottom: 1rem;">
            <?= match ($_GET['error']) {
                'csrf' => 'Phiên làm việc hết hạn hoặc yêu cầu không hợp lệ.',
                'invalid-action' => 'Hành động không hợp lệ.',
                default => 'Đã xảy ra lỗi khi thực hiện.'
            } ?>
        </p>
    <?php endif; ?>

    <div class="admin-stats">
        <div><strong><?= (int) ($stats['total'] ?? 0) ?></strong><span>Tổng công thức</span></div>
        <div><strong><?= (int) ($stats['pending'] ?? 0) ?></strong><span>Chờ duyệt</span></div>
        <div><strong><?= (int) ($stats['approved'] ?? 0) ?></strong><span>Đã duyệt</span></div>
        <div><strong><?= (int) $userCount ?></strong><span>Thành viên</span></div>
    </div>

    <section class="admin-table-box">
        <h2>Công thức đang chờ phê duyệt (<?= count($pendingRecipes) ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tên món ăn</th>
                        <th>Tác giả</th>
                        <th>Ngày gửi</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingRecipes as $recipe): ?>
                    <tr>
                        <td>
                            <strong><?= e($recipe['title']) ?></strong>
                            <br>
                            <small><a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>" target="_blank">Xem trước</a></small>
                        </td>
                        <td><?= e($recipe['author_name']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime((string) $recipe['created_at'])) ?></td>
                        <td class="action-buttons">
                            <form method="post" action="<?= BASE_URL ?>/actions/admin_action.php" style="display:inline-flex; gap: 6px;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                                <input type="hidden" name="return_url" value="admin/index.php">
                                <button type="submit" name="status" value="approved">Duyệt</button>
                                <button type="submit" name="status" value="rejected" class="danger">Từ chối</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$pendingRecipes): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 2rem;">Hiện không có công thức nào cần duyệt.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
