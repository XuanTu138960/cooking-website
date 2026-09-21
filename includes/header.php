<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/daily_engagement.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Run silent daily engagement check once per day
run_daily_engagement();

// Counters for Cookpad sidebar
$sidebarTotal = (int) db()->query("SELECT COUNT(*) FROM recipes WHERE status = 'approved'")->fetchColumn();
$sidebarSaved = 0;
$sidebarMy = 0;
$sidebarLive = 0;
if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    $stmtS = db()->prepare("SELECT COUNT(*) FROM saved_recipes WHERE user_id = ?");
    $stmtS->execute([$uid]);
    $sidebarSaved = (int) $stmtS->fetchColumn();

    $stmtM = db()->prepare("SELECT COUNT(*) FROM recipes WHERE author_id = ?");
    $stmtM->execute([$uid]);
    $sidebarMy = (int) $stmtM->fetchColumn();

    $stmtL = db()->prepare("SELECT COUNT(*) FROM recipes WHERE author_id = ? AND status = 'approved'");
    $stmtL->execute([$uid]);
    $sidebarLive = (int) $stmtL->fetchColumn();
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

<!-- COOKPAD STYLE MINIMALIST SIDEBAR DRAWER (MATCHING IMAGE 1) -->
<div class="cookpad-sidebar-overlay" id="cookpadOverlay" onclick="toggleCookpadSidebar(false)"></div>
<aside class="cookpad-sidebar" id="cookpadSidebar" aria-label="Menu điều hướng Cookpad">
    <!-- Header: Logo + Collapse Arrow -->
    <div class="cookpad-sidebar-header">
        <a href="<?= BASE_URL ?>/index.php" class="cookpad-brand" aria-label="Cookio">
            <div class="cookpad-hat-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                    <line x1="6" y1="17" x2="18" y2="17"/>
                </svg>
            </div>
            <span class="cookpad-brand-text">cookpad</span>
        </a>
        <button type="button" class="cookpad-collapse-btn" id="btnCollapseSidebar" onclick="toggleCookpadSidebar(false)" title="Thu gọn" aria-label="Thu gọn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="11 17 6 12 11 7"/>
                <polyline points="18 17 13 12 18 7"/>
            </svg>
        </button>
    </div>

    <!-- Navigation Items with Minimalist Outline Stroke Icons (Image 1) -->
    <nav class="cookpad-nav-list">
        <!-- 1. Tìm kiếm (Active Orange Style) -->
        <a href="<?= BASE_URL ?>/index.php" class="cookpad-nav-item is-search-active" onclick="focusHeaderSearch(event)">
            <svg class="cookpad-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <span style="color: #ea580c; font-weight: 700;">Tìm kiếm</span>
        </a>

        <!-- 2. Premium (Ribbon bookmark with P) -->
        <a href="<?= BASE_URL ?>/views/cookbooks.php" class="cookpad-nav-item">
            <svg class="cookpad-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                <text x="12" y="13" font-size="9" font-family="sans-serif" font-weight="900" text-anchor="middle" fill="#64748b" stroke="none">P</text>
            </svg>
            <span>Premium</span>
        </a>

        <!-- 3. Thống Kê Bếp (Bar chart) -->
        <a href="<?= BASE_URL ?>/views/my-recipes.php" class="cookpad-nav-item">
            <svg class="cookpad-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            <span>Thống Kê Bếp</span>
        </a>

        <!-- 4. Thử Thách (Award medal with ribbon) -->
        <a href="<?= BASE_URL ?>/views/smart-fridge.php" class="cookpad-nav-item">
            <svg class="cookpad-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="6"/>
                <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
            </svg>
            <span>Thử Thách</span>
        </a>

        <!-- 5. Tương Tác (Notification Bell) -->
        <a href="javascript:void(0)" class="cookpad-nav-item" onclick="toggleCookpadSidebar(false); toggleNotificationsDropdown();">
            <svg class="cookpad-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span>Tương Tác</span>
        </a>

        <!-- 6. Mẹo Vặt Nhà Bếp (YummyDay Kitchen Tips) -->
        <a href="<?= BASE_URL ?>/views/kitchen-tips.php" class="cookpad-nav-item">
            <svg class="cookpad-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 18h6"/>
                <path d="M10 22h4"/>
                <path d="M12 2a7 7 0 0 0-7 7c0 2.38 1.19 4.47 3 5.74V17a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2.26c1.81-1.27 3-3.36 3-5.74a7 7 0 0 0-7-7z"/>
            </svg>
            <span>Mẹo Vặt Bếp</span>
        </a>

        <!-- 6. KHO MÓN NGON CỦA BẠN (Book section) -->
        <div class="cookpad-section-divider"></div>
        <div class="cookpad-section-header">
            <svg class="cookpad-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                <circle cx="12" cy="8" r="2"/>
            </svg>
            <strong>Kho Món Ngon Của Bạn</strong>
        </div>

        <!-- Mini Search in Repository -->
        <div class="cookpad-mini-search">
            <form method="get" action="<?= BASE_URL ?>/index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="q" placeholder="Tìm trong kho món ngon" autocomplete="off">
            </form>
        </div>

        <!-- 4 Sub-Categories with Rounded Square Icon Containers (Image 1) -->
        <div class="cookpad-sub-list">
            <!-- Tất Cả -->
            <a href="<?= BASE_URL ?>/index.php" class="cookpad-sub-item">
                <div class="cookpad-sub-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <div class="cookpad-sub-text">
                    <span class="sub-name">Tất Cả</span>
                    <span class="sub-count"><?= $sidebarTotal ?> món</span>
                </div>
            </a>

            <!-- Đã Lưu -->
            <a href="<?= BASE_URL ?>/views/saved-recipes.php" class="cookpad-sub-item">
                <div class="cookpad-sub-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div class="cookpad-sub-text">
                    <span class="sub-name">Đã Lưu</span>
                    <span class="sub-count"><?= $sidebarSaved ?> món</span>
                </div>
            </a>

            <!-- Món Của Tôi -->
            <a href="<?= BASE_URL ?>/views/my-recipes.php" class="cookpad-sub-item">
                <div class="cookpad-sub-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="cookpad-sub-text">
                    <span class="sub-name">Món Của Tôi</span>
                    <span class="sub-count"><?= $sidebarMy ?> món</span>
                </div>
            </a>

            <!-- Đã lên sóng -->
            <a href="<?= BASE_URL ?>/views/my-recipes.php" class="cookpad-sub-item">
                <div class="cookpad-sub-icon-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
                <div class="cookpad-sub-text">
                    <span class="sub-name">Đã lên sóng</span>
                    <span class="sub-count"><?= $sidebarLive ?> món</span>
                </div>
            </a>
        </div>
    </nav>
</aside>

<!-- MAIN SITE HEADER -->
<!-- MAIN SITE HEADER (YUMMYDAY AUTHENTIC THEME - MATCHING IMAGE 1) -->
<header class="yummy-site-header">
    <!-- Top Row: Logo & Search Pill Input -->
    <div class="yummy-top-bar">
        <div class="yummy-top-container">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <button type="button" class="btn-sidebar-toggle" id="btnToggleCookpadSidebar" onclick="toggleCookpadSidebar(true)" title="Menu mở rộng" aria-label="Menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4a1c17" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <a href="<?= BASE_URL ?>/index.php" class="yummy-brand" aria-label="YummyDay Trang chủ">
                    <img src="<?= BASE_URL ?>/assets/images/yummyday_logo.svg" alt="YummyDay" class="yummy-logo-img">
                </a>
            </div>

            <!-- Top Search Pill with Brown Go Button (Image 1) -->
            <div class="yummy-top-search-wrap">
                <form method="get" action="<?= BASE_URL ?>/index.php" class="yummy-search-form" id="yummySearchForm">
                    <svg class="y-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="q" placeholder="Tìm theo tên món" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" onclick="openHorizontalSearch()">
                    <button type="submit" class="btn-yummy-search-go" aria-label="Tìm kiếm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Navigation Menu Bar (Image 1) -->
    <div class="yummy-nav-bar">
        <div class="yummy-nav-container">
            <nav class="yummy-main-nav">
                <!-- 1. Trang chủ (Active Oval Pill) -->
                <a href="<?= BASE_URL ?>/index.php" class="y-nav-item is-active">
                    <span class="y-dot">●</span> Trang chủ
                </a>

                <!-- 2. Món ngon (Dropdown) -->
                <div class="y-nav-dropdown-wrap">
                    <a href="<?= BASE_URL ?>/index.php" class="y-nav-item">
                        Món ngon <span class="y-caret">⌄</span>
                    </a>
                    <div class="y-dropdown-menu">
                        <a href="<?= BASE_URL ?>/index.php?cat=Món+xào">🥘 Món xào</a>
                        <a href="<?= BASE_URL ?>/index.php?cat=Món+canh">🥣 Món canh</a>
                        <a href="<?= BASE_URL ?>/index.php?cat=Món+kho">🍲 Món kho</a>
                        <a href="<?= BASE_URL ?>/index.php?cat=Món+hấp">♨️ Món hấp</a>
                        <a href="<?= BASE_URL ?>/index.php?cat=Món+chiên">🍤 Món chiên</a>
                        <a href="<?= BASE_URL ?>/views/smart-fridge.php" style="color: #ea580c; font-weight: 700; border-top: 1px solid #fed7aa; margin-top: 0.35rem; padding-top: 0.5rem;">🧊 Tủ lạnh thông minh</a>
                    </div>
                </div>

                <!-- 3. Công thức làm bánh -->
                <a href="<?= BASE_URL ?>/index.php?cat=Bánh+-+Tráng+miệng" class="y-nav-item">
                    Công thức làm bánh
                </a>

                <!-- 4. Món chay -->
                <a href="<?= BASE_URL ?>/index.php?cat=Món+chay" class="y-nav-item">
                    Món chay
                </a>

                <!-- 5. Kinh nghiệm hay (Mẹo bếp) -->
                <a href="<?= BASE_URL ?>/views/kitchen-tips.php" class="y-nav-item">
                    Kinh nghiệm hay
                </a>

                <!-- 6. Dụng cụ bếp -->
                <a href="<?= BASE_URL ?>/views/cookbooks.php" class="y-nav-item">
                    Dụng cụ bếp
                </a>
            </nav>

            <!-- Right Action Buttons (Image 1) -->
            <div class="yummy-nav-actions">
                <button type="button" class="y-pill-btn-gray" onclick="alert('Trung tâm trợ giúp ẩm thực YummyDay - Hotline: 1900 6868\nEmail hỗ trợ: support@yummyday.vn')">
                    Liên hệ 🎧
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Notification Bell -->
                    <div class="notification-wrapper">
                        <button type="button" class="notification-btn" id="btnNotifications" title="Thông báo" aria-label="Thông báo" onclick="toggleNotificationsDropdown()">
                            <span style="font-size: 1.15rem;">🔔</span>
                            <span class="notif-badge" id="notifBadge" style="display: none;">0</span>
                        </button>
                        <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                            <div class="notification-header">
                                <span>🔔 Thông báo</span>
                                <button type="button" class="btn-mark-all-read" onclick="markAllNotificationsRead()">Đã đọc tất cả</button>
                            </div>
                            <div class="notification-tabs">
                                <button type="button" class="notif-tab is-active" data-filter="all" onclick="filterNotifications('all', this)">Tất cả</button>
                                <button type="button" class="notif-tab" data-filter="like" onclick="filterNotifications('like', this)">❤️ Tim</button>
                                <button type="button" class="notif-tab" data-filter="comment" onclick="filterNotifications('comment', this)">💬 Bình luận</button>
                                <button type="button" class="notif-tab" data-filter="follow" onclick="filterNotifications('follow', this)">👥 Theo dõi</button>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div style="padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.85rem;">Đang tải...</div>
                            </div>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>/views/profile.php" class="user-menu-chip" title="Xem hồ sơ">
                        <span class="user-avatar-circle"><?= mb_substr((string) $_SESSION['username'], 0, 1) ?></span>
                        <span><?= e((string) $_SESSION['username']) ?></span>
                    </a>

                    <a href="<?= BASE_URL ?>/views/create-recipe.php" class="y-pill-btn-brown" style="padding: 0.35rem 0.85rem; font-size: 0.82rem;">
                        + Đăng món
                    </a>

                    <a href="<?= BASE_URL ?>/logout.php" style="color: #64748b; font-size: 0.88rem;" title="Đăng xuất">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>
                <?php else: ?>
                    <button type="button" class="y-pill-btn-brown" data-open-login>
                        Đăng ký
                    </button>
                <?php endif; ?>

                <!-- Dark Mode Toggle Button -->
                <button type="button" class="dark-mode-toggle" id="btnToggleDarkMode" title="Chuyển chế độ sáng/tối" aria-label="Chuyển chế độ sáng/tối">
                    <span class="theme-icon">🌙</span>
                </button>
            </div>
        </div>
    </div>
</header>
<main class="page-content">
