<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!is_logged_in()) {
    redirect('index.php?error=login-required');
}

$recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$recipeId) {
    redirect('views/my-recipes.php?error=invalid-recipe');
}

$stmt = db()->prepare('SELECT * FROM recipes WHERE id = ?');
$stmt->execute([$recipeId]);
$recipe = $stmt->fetch();

if (!$recipe || ((int) $recipe['author_id'] !== (int) $_SESSION['user_id'] && !is_admin())) {
    redirect('views/my-recipes.php?error=forbidden');
}

$categories = ['Món xào', 'Món canh', 'Món kho', 'Món hấp', 'Món chiên', 'Món nướng', 'Món chay', 'Món tráng miệng', 'Món chính'];
$currentCat = $recipe['category'] ?? 'Món chính';

$pageTitle = 'Chỉnh sửa: ' . e($recipe['title']) . ' - Cookio';
require __DIR__ . '/../includes/header.php';
?>

<div class="recipe-detail-container" style="padding-top:2rem; padding-bottom:3rem;">

    <!-- Breadcrumb -->
    <nav class="breadcrumb-nav" style="margin-bottom:1.5rem;">
        <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>
        <span>›</span>
        <a href="<?= BASE_URL ?>/views/my-recipes.php">Công thức của tôi</a>
        <span>›</span>
        <span>Chỉnh sửa</span>
    </nav>

    <!-- Page header -->
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:2rem;">
        <div>
            <h1 style="font-size:1.8rem; font-weight:800; color:var(--text-main); margin:0 0 0.25rem;">✏️ Chỉnh sửa công thức</h1>
            <p style="margin:0; color:var(--text-muted); font-size:0.95rem;">Cập nhật thông tin cho món <strong><?= e($recipe['title']) ?></strong></p>
        </div>
        <a href="<?= BASE_URL ?>/views/my-recipes.php"
           style="display:inline-flex; align-items:center; gap:0.4rem; color:var(--primary); font-weight:600; text-decoration:none; font-size:0.9rem;">
            ← Quay lại danh sách
        </a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice error" style="margin-bottom:1.5rem;">
            <?php echo match ($_GET['error']) {
                'csrf'           => '⚠️ Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.',
                'missing-fields' => '⚠️ Vui lòng điền đầy đủ tiêu đề, nguyên liệu và cách làm.',
                'invalid-image'  => '⚠️ Ảnh tải lên không hợp lệ hoặc vượt quá 5MB.',
                'upload-failed'  => '⚠️ Không thể lưu ảnh tải lên máy chủ.',
                default          => '⚠️ Đã có lỗi xảy ra. Vui lòng kiểm tra lại.',
            }; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/actions/recipe_action.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">

        <!-- Section 1: General Info -->
        <div class="recipe-detail-hero-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 1.25rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light); display:flex; align-items:center; gap:0.5rem;">
                📋 Thông tin chung
            </h2>

            <label style="display:block; margin-bottom:1rem;">
                <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">
                    Tên món ăn <span style="color:#ef4444;">*</span>
                </span>
                <input type="text" name="title" value="<?= e($recipe['title']) ?>" required
                    placeholder="Ví dụ: Bò xào sả ớt, Canh chua cá lóc..."
                    style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:1rem; outline:none; box-sizing:border-box; font-family:inherit;">
            </label>

            <label style="display:block; margin-bottom:1rem;">
                <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">
                    Mô tả ngắn
                </span>
                <textarea name="description" rows="3"
                    placeholder="Giới thiệu ngắn gọn về món ăn, hương vị đặc trưng..."
                    style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; resize:vertical; outline:none; box-sizing:border-box; font-family:inherit;"><?= e($recipe['description'] ?? '') ?></textarea>
            </label>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem;">
                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">🍽️ Danh mục</span>
                    <select name="category"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; background:#fff; outline:none; cursor:pointer; font-family:inherit;">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $currentCat === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">⏱️ Thời gian nấu</span>
                    <input type="text" name="cooking_time" value="<?= e($recipe['cooking_time'] ?? '30 phút') ?>"
                        placeholder="Ví dụ: 30 phút"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;">
                </label>

                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">👥 Khẩu phần</span>
                    <input type="text" name="servings" value="<?= e($recipe['servings'] ?? '2 - 4 người') ?>"
                        placeholder="Ví dụ: 4 người"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;">
                </label>
            </div>
        </div>

        <!-- Section 2: Image -->
        <div class="recipe-detail-hero-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 1.25rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light); display:flex; align-items:center; gap:0.5rem;">
                📸 Ảnh minh họa
            </h2>

            <?php if (!empty($recipe['image_url'])): ?>
                <div style="margin-bottom:1rem;">
                    <p style="font-size:0.85rem; color:var(--text-muted); margin:0 0 0.5rem; font-weight:600;">Ảnh hiện tại:</p>
                    <img src="<?= BASE_URL . '/' . e($recipe['image_url']) ?>" alt="Ảnh hiện tại"
                        style="width:200px; height:140px; object-fit:cover; border-radius:0.6rem; border:2px solid var(--border); display:block;">
                </div>
            <?php else: ?>
                <p style="font-size:0.88rem; color:var(--text-muted); margin:0 0 0.75rem;">Chưa có ảnh minh họa.</p>
            <?php endif; ?>

            <label style="display:block;">
                <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">
                    Chọn ảnh mới (nếu muốn thay đổi)
                </span>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                    style="padding:0.5rem 0; font-size:0.9rem; color:var(--text-muted);">
                <span style="display:block; font-size:0.8rem; color:var(--text-muted); margin-top:0.35rem;">Chấp nhận JPG, PNG, WEBP — tối đa 5MB</span>
            </label>
        </div>

        <!-- Section 3: Ingredients -->
        <div class="recipe-detail-hero-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 0.4rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light); display:flex; align-items:center; gap:0.5rem;">
                🛒 Nguyên liệu
            </h2>
            <p style="font-size:0.85rem; color:var(--text-muted); margin:0 0 1rem;">Mỗi nguyên liệu trên một dòng. Ghi rõ số lượng và đơn vị.</p>

            <textarea name="ingredients" rows="7" required
                placeholder="Ví dụ:&#10;- 300g thịt bò&#10;- 2 củ sả&#10;- 1 quả ớt tươi&#10;- 2 muỗng canh dầu ăn"
                style="width:100%; padding:0.85rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; line-height:1.65; resize:vertical; outline:none; box-sizing:border-box; font-family:inherit;"><?= e($recipe['ingredients']) ?></textarea>
        </div>

        <!-- Section 4: Instructions -->
        <div class="recipe-detail-hero-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 0.4rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light); display:flex; align-items:center; gap:0.5rem;">
                👨‍🍳 Các bước thực hiện
            </h2>
            <p style="font-size:0.85rem; color:var(--text-muted); margin:0 0 1rem;">Mô tả từng bước thực hiện rõ ràng, mỗi bước trên một dòng.</p>

            <textarea name="instructions" rows="10" required
                placeholder="Ví dụ:&#10;Bước 1: Sơ chế thịt bò — rửa sạch, thái lát mỏng vừa ăn.&#10;Bước 2: Phi thơm sả và ớt với dầu ăn nóng.&#10;Bước 3: Cho thịt bò vào xào lửa lớn đến khi chín đều."
                style="width:100%; padding:0.85rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; line-height:1.65; resize:vertical; outline:none; box-sizing:border-box; font-family:inherit;"><?= e($recipe['instructions']) ?></textarea>
        </div>

        <!-- Section 5: Author Tips -->
        <div class="recipe-detail-hero-card" style="margin-bottom:2rem;">
            <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 0.4rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light); display:flex; align-items:center; gap:0.5rem;">
                💡 Bí quyết & Mẹo nấu của bạn (Tùy chọn)
            </h2>
            <p style="font-size:0.85rem; color:var(--text-muted); margin:0 0 1rem;">Mẹo chọn nguyên liệu ngon, cách canh lửa hoặc lưu ý giúp món ăn thành công.</p>

            <textarea name="tips" rows="4"
                placeholder="Ví dụ: Xào thịt bò trên lửa thật lớn để thịt giữ nước ngọt và không bị dai..."
                style="width:100%; padding:0.85rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; line-height:1.65; resize:vertical; outline:none; box-sizing:border-box; font-family:inherit;"><?= e($recipe['tips'] ?? '') ?></textarea>
        </div>

        <!-- Section 6: Nutrition Facts & Dietary Tags -->
        <div class="recipe-detail-hero-card" style="margin-bottom:2rem;">
            <h2 style="font-size:1.05rem; font-weight:700; color:var(--primary); margin:0 0 0.4rem; padding-bottom:0.75rem; border-bottom:2px solid var(--primary-light); display:flex; align-items:center; gap:0.5rem;">
                🥗 Thông tin dinh dưỡng & Thẻ chế độ ăn (Tùy chọn)
            </h2>
            <p style="font-size:0.85rem; color:var(--text-muted); margin:0 0 1rem;">Cung cấp thành phần dinh dưỡng ước tính trên 1 khẩu phần ăn.</p>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:1rem; margin-bottom:1rem;">
                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">Năng lượng (kcal)</span>
                    <input type="number" name="calories" value="<?= e((string)($recipe['calories'] ?? '')) ?>" placeholder="VD: 350" min="0"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;">
                </label>
                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">Đạm / Protein (g)</span>
                    <input type="number" name="protein" value="<?= e((string)($recipe['protein'] ?? '')) ?>" placeholder="VD: 25" min="0"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;">
                </label>
                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">Tinh bột / Carbs (g)</span>
                    <input type="number" name="carbs" value="<?= e((string)($recipe['carbs'] ?? '')) ?>" placeholder="VD: 40" min="0"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;">
                </label>
                <label style="display:block;">
                    <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">Chất béo / Fat (g)</span>
                    <input type="number" name="fat" value="<?= e((string)($recipe['fat'] ?? '')) ?>" placeholder="VD: 12" min="0"
                        style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;">
                </label>
            </div>

            <label style="display:block;">
                <span style="font-weight:600; font-size:0.9rem; color:var(--text-main); display:block; margin-bottom:0.4rem;">Thẻ chế độ ăn (phân cách bởi dấu phẩy)</span>
                <input type="text" name="dietary_tags" value="<?= e((string)($recipe['dietary_tags'] ?? '')) ?>" placeholder="VD: Eat Clean, Giàu Protein, Ít Calo, Nhanh < 30p"
                    style="width:100%; padding:0.75rem 1rem; border:1.5px solid var(--border); border-radius:0.5rem; font-size:0.95rem; outline:none; box-sizing:border-box; font-family:inherit;">
            </label>
        </div>

        <!-- Action buttons -->
        <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
            <button type="submit"
                style="background:var(--primary); color:#fff; border:none; padding:0.85rem 2.25rem; border-radius:0.6rem; font-size:1rem; font-weight:700; cursor:pointer; transition:background 0.2s;"
                onmouseover="this.style.background='var(--primary-hover)'" onmouseout="this.style.background='var(--primary)'">
                💾 Lưu thay đổi
            </button>
            <a href="<?= BASE_URL ?>/views/my-recipes.php"
                style="padding:0.85rem 1.5rem; border:1.5px solid var(--border); border-radius:0.6rem; color:var(--text-muted); font-size:0.95rem; font-weight:600; text-decoration:none;">
                Hủy bỏ
            </a>
        </div>
    </form>
</div>

<?php
require __DIR__ . '/../includes/footer.php';
