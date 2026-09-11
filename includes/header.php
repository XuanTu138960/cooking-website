<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Cookio - Nấu ngon mỗi ngày', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/user.css">
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
        window.CSRF_TOKEN = '<?= csrf_token() ?>';
    </script>
    <script src="<?= BASE_URL ?>/assets/js/enterprise-features.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="header-container">
        <!-- Brand Logo -->
        <a href="<?= BASE_URL ?>/index.php" class="brand" aria-label="Trang chủ Cookio">
            <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Cookio Logo">
        </a>

        <!-- Header Quick Search Bar -->
        <div class="header-search">
            <form method="get" action="<?= BASE_URL ?>/index.php">
                <input type="text" name="q" placeholder="Tìm tên món ăn, nguyên liệu..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
                <button type="submit" aria-label="Tìm kiếm">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Navigation Menu -->
        <nav class="site-nav" aria-label="Điều hướng chính">
            <button type="button" class="button" onclick="openMealDeciderModal()" style="padding: 0.45rem 0.85rem; border: 1.5px solid #fdba74; color: #c2410c; background: #fff7ed; border-radius: 9999px; font-weight: 700; font-size: 0.88rem; cursor: pointer;">
                <span>🎲 Hôm nay ăn gì?</span>
            </button>

            <a href="<?= BASE_URL ?>/views/smart-fridge.php" title="Tìm món theo nguyên liệu">
                <span>🧊 Tủ lạnh</span>
            </a>
            
            <a href="<?= BASE_URL ?>/views/cookbooks.php" title="Sổ tay ẩm thực">
                <span>📚 Sổ tay</span>
            </a>

            <a href="<?= BASE_URL ?>/views/saved-recipes.php" title="Các món đã lưu">
                <span>❤️ Món đã lưu</span>
            </a>

            <a href="<?= BASE_URL ?>/views/create-recipe.php" class="button button-create" style="padding: 0.5rem 1rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Viết món mới</span>
            </a>

            <!-- Dark Mode Toggle Button -->
            <button type="button" class="dark-mode-toggle" id="btnToggleDarkMode" title="Chuyển chế độ sáng/tối" aria-label="Chuyển chế độ sáng/tối">
                <span class="theme-icon">🌙</span>
            </button>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Social Notifications Center -->
                <div class="notification-wrapper">
                    <button type="button" class="notification-btn" id="btnNotifications" title="Thông báo hoạt động" aria-label="Thông báo">
                        <span style="font-size: 1.15rem;">🔔</span>
                        <span class="notif-badge" id="notifBadge" style="display: none;">0</span>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                        <div class="notification-header">
                            <span>🔔 Thông báo của bạn</span>
                            <button type="button" class="btn-mark-all-read" onclick="markAllNotificationsRead()">Đã đọc tất cả</button>
                        </div>
                        <div class="notification-tabs">
                            <button type="button" class="notif-tab is-active" data-filter="all" onclick="filterNotifications('all', this)">Tất cả</button>
                            <button type="button" class="notif-tab" data-filter="like" onclick="filterNotifications('like', this)">❤️ Tim</button>
                            <button type="button" class="notif-tab" data-filter="comment" onclick="filterNotifications('comment', this)">💬 Bình luận</button>
                            <button type="button" class="notif-tab" data-filter="follow" onclick="filterNotifications('follow', this)">👥 Theo dõi</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div style="padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.85rem;">Đang tải thông báo...</div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <a href="<?= BASE_URL ?>/views/profile.php" class="user-menu-chip" title="Xem hồ sơ">
                        <span class="user-avatar-circle"><?= mb_substr((string) $_SESSION['username'], 0, 1) ?></span>
                        <span><?= e((string) $_SESSION['username']) ?></span>
                    </a>

                    <a href="<?= BASE_URL ?>/views/my-recipes.php" style="font-size: 0.9rem; font-weight: 600;">Món của tôi</a>

                    <?php if (is_admin()): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php" style="color: #ea580c; font-weight: 800; font-size: 0.88rem; background: #fff7ed; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid #fed7aa;">
                            Admin
                        </a>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/logout.php" style="font-size: 0.88rem; color: #9ca3af;" title="Đăng xuất">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>
                </div>
            <?php else: ?>
                <button type="button" class="button button-outline" data-open-login style="padding: 0.5rem 1rem;">
                    Đăng nhập
                </button>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="page-content">
