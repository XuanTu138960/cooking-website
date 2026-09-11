<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = 'Hồ sơ tài khoản - Cookio';
require __DIR__ . '/../includes/header.php';

if (!is_logged_in()) {
    echo '<section class="saved-recipes"><div class="empty-state-box"><p>Vui lòng đăng nhập để xem thông tin tài khoản.</p><button type="button" class="button" data-open-login>Đăng nhập ngay</button></div></section>';
    require __DIR__ . '/../includes/modal-login.php';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$userId = (int) $_SESSION['user_id'];
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$totalRecipes = (int) db()->query("SELECT COUNT(*) FROM recipes WHERE author_id = $userId")->fetchColumn();
$approvedRecipes = (int) db()->query("SELECT COUNT(*) FROM recipes WHERE author_id = $userId AND status = 'approved'")->fetchColumn();
$savedRecipes = (int) db()->query("SELECT COUNT(*) FROM saved_recipes WHERE user_id = $userId")->fetchColumn();
$totalComments = (int) db()->query("SELECT COUNT(*) FROM comments WHERE user_id = $userId")->fetchColumn();

// Following & Followers count
$followingCount = (int) db()->query("SELECT COUNT(*) FROM follows WHERE follower_id = $userId")->fetchColumn();
$followersCount = (int) db()->query("SELECT COUNT(*) FROM follows WHERE author_id = $userId")->fetchColumn();

// List of followed chefs
$stmtFollowed = db()->prepare("SELECT u.id, u.username FROM follows f INNER JOIN users u ON u.id = f.author_id WHERE f.follower_id = ? ORDER BY f.created_at DESC LIMIT 12");
$stmtFollowed->execute([$userId]);
$followedChefs = $stmtFollowed->fetchAll();
?>
<div class="recipe-detail-container" style="padding-top:2rem; padding-bottom:3rem;">

    <?php if (isset($_GET['success'])): ?>
        <div class="notice success" style="margin-bottom:1.25rem;">
            <?= match ($_GET['success']) {
                'info-updated'     => '✅ Cập nhật thông tin tài khoản thành công!',
                'password-updated' => '✅ Đổi mật khẩu thành công! Mật khẩu mới đã có hiệu lực.',
                default            => '✅ Thao tác thành công!'
            } ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice error" style="margin-bottom:1.25rem;">
            <?php echo match ($_GET['error']) {
                'csrf'                   => '⚠️ Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.',
                'current-password-wrong' => '⚠️ Mật khẩu hiện tại không chính xác.',
                'password-mismatch'      => '⚠️ Mật khẩu mới và mật khẩu xác nhận không trùng khớp.',
                'password-short'         => '⚠️ Mật khẩu mới phải có độ dài từ 3 ký tự trở lên.',
                default                  => '⚠️ Đã có lỗi xảy ra. Vui lòng thử lại.'
            }; ?>
        </div>
    <?php endif; ?>

    <!-- Profile hero card -->
    <div style="background:#fff; border-radius:1.2rem; border:1px solid var(--border); padding:2rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1.75rem; flex-wrap:wrap;">
        <!-- Avatar circle -->
        <div style="width:90px; height:90px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; font-size:2.4rem; font-weight:800; color:#fff; flex-shrink:0; box-shadow:0 4px 16px rgba(234,88,12,0.25);">
            <?= mb_strtoupper(mb_substr($user['username'], 0, 1)) ?>
        </div>
        <div style="flex:1; min-width:180px;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
                <div>
                    <h1 style="font-size:1.5rem; font-weight:800; color:var(--text-main); margin:0 0 0.25rem;"><?= e($user['username']) ?></h1>
                    <span style="display:inline-block; font-size:0.82rem; font-weight:700; padding:0.2rem 0.75rem; border-radius:99px;
                        <?= $user['role'] === 'admin' ? 'background:#fef3c7; color:#d97706;' : 'background:var(--primary-light); color:var(--primary);' ?>">
                        <?= $user['role'] === 'admin' ? '👑 Quản trị viên' : '🍳 Thành viên' ?>
                    </span>
                </div>
                <a href="<?= BASE_URL ?>/views/author.php?id=<?= $userId ?>" 
                    style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.55rem 1rem; border:1.5px solid var(--primary); border-radius:0.5rem; color:var(--primary); font-size:0.88rem; font-weight:700; text-decoration:none; transition:background 0.2s;"
                    onmouseover="this.style.background='var(--primary-light)'" onmouseout="this.style.background='transparent'">
                    👁️ Xem trang hồ sơ công khai &rarr;
                </a>
            </div>
            <p style="margin:0.5rem 0 0; font-size:0.88rem; color:var(--text-muted);">
                📅 Gia nhập: <?= date('d/m/Y', strtotime((string) $user['created_at'])) ?>
            </p>
            <?php if (!empty($user['bio'])): ?>
                <p style="margin:0.5rem 0 0; font-size:0.9rem; color:#4b5563; font-style:italic;">
                    "<?= e($user['bio']) ?>"
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats grid (6 items) -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:0.75rem; margin-bottom:1.5rem;">
        <div style="background:#fff; border:1px solid var(--border); border-radius:0.85rem; padding:1rem 0.75rem; text-align:center;">
            <div style="font-size:1.6rem; font-weight:800; color:var(--primary);"><?= $totalRecipes ?></div>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem; font-weight:500;">Công thức</div>
        </div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:0.85rem; padding:1rem 0.75rem; text-align:center;">
            <div style="font-size:1.6rem; font-weight:800; color:#16a34a;"><?= $approvedRecipes ?></div>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem; font-weight:500;">Đã duyệt</div>
        </div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:0.85rem; padding:1rem 0.75rem; text-align:center;">
            <div style="font-size:1.6rem; font-weight:800; color:#dc2626;"><?= $savedRecipes ?></div>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem; font-weight:500;">Món đã lưu</div>
        </div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:0.85rem; padding:1rem 0.75rem; text-align:center;">
            <div style="font-size:1.6rem; font-weight:800; color:#7c3aed;"><?= $totalComments ?></div>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem; font-weight:500;">Bình luận</div>
        </div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:0.85rem; padding:1rem 0.75rem; text-align:center;">
            <div style="font-size:1.6rem; font-weight:800; color:#ea580c;"><?= $followingCount ?></div>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem; font-weight:500;">Đang theo dõi</div>
        </div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:0.85rem; padding:1rem 0.75rem; text-align:center;">
            <div style="font-size:1.6rem; font-weight:800; color:#0284c7;"><?= $followersCount ?></div>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem; font-weight:500;">Người theo dõi</div>
        </div>
    </div>

    <!-- Followed Chefs section (if any) -->
    <?php if (!empty($followedChefs)): ?>
        <div class="recipe-detail-hero-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 1rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light);">
                👨‍🍳 Đầu bếp bạn đang theo dõi (<?= count($followedChefs) ?>)
            </h2>
            <div style="display:flex; flex-wrap:wrap; gap:0.6rem;">
                <?php foreach ($followedChefs as $chef): ?>
                    <a href="<?= BASE_URL ?>/views/author.php?id=<?= (int) $chef['id'] ?>" 
                       style="display:inline-flex; align-items:center; gap:0.5rem; background:#fdfbf7; border:1.5px solid var(--border); padding:0.4rem 0.85rem; border-radius:99px; text-decoration:none; color:inherit; font-size:0.88rem; font-weight:600; transition:all 0.2s;"
                       onmouseover="this.style.borderColor='var(--primary)'; this.style.background='#fff7ed';" onmouseout="this.style.borderColor='var(--border)'; this.style.background='#fdfbf7';">
                        <span style="width:24px; height:24px; border-radius:50%; background:var(--primary); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:800;">
                            <?= mb_strtoupper(mb_substr($chef['username'], 0, 1)) ?>
                        </span>
                        <span><?= e($chef['username']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Update info form -->
    <div class="recipe-detail-hero-card" style="margin-bottom:1.5rem;">
        <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 1.25rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light); display:flex; align-items:center; gap:0.5rem;">
            👤 Thông tin cá nhân
        </h2>
        <form method="post" action="<?= BASE_URL ?>/actions/profile_action.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_info">

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1rem;">
                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.88rem; color:var(--text-muted); display:block; margin-bottom:0.35rem;">Tên tài khoản</span>
                    <input type="text" value="<?= e($user['username']) ?>" disabled
                        style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; background:#f9fafb; color:var(--text-muted); box-sizing:border-box;">
                </label>
                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.88rem; color:var(--text-muted); display:block; margin-bottom:0.35rem;">Vai trò</span>
                    <input type="text" value="<?= $user['role'] === 'admin' ? 'Quản trị viên (Admin)' : 'Thành viên yêu bếp' ?>" disabled
                        style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; background:#f9fafb; color:var(--text-muted); box-sizing:border-box;">
                </label>
            </div>

            <label style="display:block; margin-bottom:1rem;">
                <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.35rem;">📱 Số điện thoại liên hệ</span>
                <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="Ví dụ: 0912 345 678"
                    style="width:100%; max-width:320px; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;"
                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
            </label>

            <label style="display:block; margin-bottom:1.25rem;">
                <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.35rem;">📝 Giới thiệu bản thân (Bio)</span>
                <textarea name="bio" rows="3" placeholder="Chia sẻ sở thích nấu nướng, món ăn tủ của bạn..."
                    style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit; resize:vertical;"
                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'"><?= e($user['bio'] ?? '') ?></textarea>
            </label>

            <button type="submit"
                style="background:var(--primary); color:#fff; border:none; padding:0.75rem 1.75rem; border-radius:0.55rem; font-size:0.95rem; font-weight:700; cursor:pointer; transition:background 0.2s;"
                onmouseover="this.style.background='var(--primary-hover)'" onmouseout="this.style.background='var(--primary)'">
                Cập nhật thông tin
            </button>
        </form>
    </div>

    <!-- Change password form -->
    <div class="recipe-detail-hero-card">
        <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 1.25rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light); display:flex; align-items:center; gap:0.5rem;">
            🔐 Đổi mật khẩu bảo mật
        </h2>
        <form method="post" action="<?= BASE_URL ?>/actions/profile_action.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="change_password">

            <div style="display:flex; flex-direction:column; gap:1rem; max-width:420px; margin-bottom:1.25rem;">
                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.35rem;">
                        Mật khẩu hiện tại <span style="color:#ef4444;">*</span>
                    </span>
                    <input type="password" name="current_password" required placeholder="Nhập mật khẩu đang sử dụng"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;"
                        onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                </label>

                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.35rem;">
                        Mật khẩu mới <span style="color:#ef4444;">*</span>
                    </span>
                    <input type="password" name="new_password" required placeholder="Tối thiểu 3 ký tự"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;"
                        onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                </label>

                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.35rem;">
                        Xác nhận mật khẩu mới <span style="color:#ef4444;">*</span>
                    </span>
                    <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;"
                        onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                </label>
            </div>

            <button type="submit"
                style="background:#1f2937; color:#fff; border:none; padding:0.75rem 1.75rem; border-radius:0.55rem; font-size:0.95rem; font-weight:700; cursor:pointer; transition:background 0.2s;"
                onmouseover="this.style.background='#374151'" onmouseout="this.style.background='#1f2937'">
                🔐 Đổi mật khẩu
            </button>
        </form>
    </div>
</div>

<?php
require __DIR__ . '/../includes/footer.php';
