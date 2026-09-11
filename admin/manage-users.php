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

$users = db()->query(
    'SELECT u.id, u.username, u.phone, u.role, u.created_at, 
     (SELECT COUNT(*) FROM recipes WHERE author_id = u.id) AS recipe_count 
     FROM users u 
     ORDER BY u.created_at DESC'
)->fetchAll();

$pageTitle = 'Quản lý tài khoản - Cookio';
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
    <h1>Quản lý tài khoản người dùng</h1>

    <?php if (isset($_GET['success'])): ?>
        <p class="notice success" style="margin-bottom: 1rem;">
            <?= match ($_GET['success']) {
                'user-deleted' => 'Đã xóa tài khoản thành công.',
                'role-updated' => 'Đã cập nhật vai trò thành công.',
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
                        <th>Tài khoản</th>
                        <th>Số điện thoại</th>
                        <th>Vai trò</th>
                        <th>Số công thức</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?= (int) $user['id'] ?></td>
                        <td><strong><?= e($user['username']) ?></strong></td>
                        <td><?= e($user['phone'] ?? '-') ?></td>
                        <td>
                            <span class="status-badge <?= $user['role'] === 'admin' ? 'status-approved' : 'status-pending' ?>">
                                <?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Thành viên' ?>
                            </span>
                        </td>
                        <td><?= (int) $user['recipe_count'] ?></td>
                        <td><?= date('d/m/Y', strtotime((string) $user['created_at'])) ?></td>
                        <td>
                            <?php if ((int) $user['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                <div style="display: flex; gap: 6px;">
                                    <form method="post" action="<?= BASE_URL ?>/actions/admin_action.php" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="toggle_role">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <input type="hidden" name="new_role" value="<?= $user['role'] === 'admin' ? 'user' : 'admin' ?>">
                                        <button type="submit" class="button-sm">
                                            <?= $user['role'] === 'admin' ? 'Hạ cấp User' : 'Nâng cấp Admin' ?>
                                        </button>
                                    </form>

                                    <form method="post" action="<?= BASE_URL ?>/actions/admin_action.php" style="display:inline" onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này và mọi công thức liên quan?')">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <button type="submit" class="button-sm danger">Xóa</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <em>(Tài khoản của bạn)</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
