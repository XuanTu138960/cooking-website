<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Chia sẻ công thức mới - Cookio';
require __DIR__ . '/../includes/header.php';
?>
<div class="recipe-detail-container">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: 800; color: #111827; margin-bottom: 0.5rem;">Chia sẻ công thức của bạn</h1>
        <p style="color: #6b7280; font-size: 1.05rem;">
            Chia sẻ công thức nấu ăn gia đình của bạn đến cộng đồng yêu bếp Cookio. Mỗi công thức là một món quà ấm áp!
        </p>
    </div>

    <?php if (!is_logged_in()): ?>
        <div class="notice" style="background: #fff7ed; border-color: #ea580c; display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; margin-bottom: 2rem;">
            <span>Bạn đang xem ở chế độ khách. Vui lòng đăng nhập để gửi bài viết.</span>
            <button type="button" class="button button-create" data-open-login style="padding: 0.4rem 1rem;">Đăng nhập ngay</button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <p class="notice success">
            <?= $_GET['success'] === 'published' ? '🎉 Chúc mừng bạn! Công thức đã được xuất bản trực tiếp lên trang chủ.' : '🎉 Công thức của bạn đã được gửi thành công và đang chờ ban quản trị duyệt trong thời gian sớm nhất!' ?>
        </p>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <p class="notice error">
            <?php
            echo match ($_GET['error']) {
                'csrf' => 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.',
                'missing-fields' => 'Vui lòng điền đầy đủ tên món ăn, nguyên liệu và các bước làm.',
                'invalid-image' => 'Ảnh tải lên không hợp lệ hoặc vượt quá 5MB (chấp nhận JPG, PNG, WEBP).',
                'upload-failed' => 'Không thể lưu ảnh tải lên máy chủ. Vui lòng thử lại.',
                'login-required' => 'Bạn cần đăng nhập để thực hiện chức năng này.',
                default => 'Không thể gửi công thức. Vui lòng kiểm tra lại thông tin.',
            };
            ?>
        </p>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/actions/recipe_action.php" enctype="multipart/form-data" class="recipe-detail-hero-card">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <h2 style="font-size: 1.25rem; font-weight: 800; border-bottom: 1.5px solid #f1ebe1; padding-bottom: 0.75rem;">
            1. Thông tin chung món ăn
        </h2>

        <label>
            Tên món ăn <span style="color:red">*</span>
            <input type="text" name="title" placeholder="Ví dụ: Bò xào hành tây cần tây thơm nức mũi" required style="font-size: 1.05rem; font-weight: 600;">
        </label>

        <label>
            Câu chuyện / Lời mở đầu món ăn
            <textarea name="description" rows="3" placeholder="Chia sẻ đôi điều về hương vị, kỷ niệm hoặc bí quyết đặc biệt của món ăn này..."></textarea>
        </label>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
            <label>
                Danh mục món ăn
                <select name="category" style="padding: 0.75rem; border: 1.5px solid #dcd5c9; border-radius: var(--radius-sm); font-weight: 600;">
                    <option value="Món xào">🥘 Món xào</option>
                    <option value="Món canh">🥣 Món canh</option>
                    <option value="Món kho">🍲 Món kho</option>
                    <option value="Món hấp">♨️ Món hấp</option>
                    <option value="Món chiên">🍤 Món chiên</option>
                    <option value="Món nướng">🥩 Món nướng</option>
                    <option value="Món chay">🥗 Món chay</option>
                    <option value="Món tráng miệng">🍰 Món tráng miệng</option>
                    <option value="Món chính" selected>🍳 Món chính</option>
                </select>
            </label>

            <label>
                Thời gian nấu
                <input type="text" name="cooking_time" placeholder="Ví dụ: 30 phút" value="30 phút">
            </label>

            <label>
                Khẩu phần
                <input type="text" name="servings" placeholder="Ví dụ: 2 - 4 người" value="2 - 4 người">
            </label>
        </div>

        <label>
            Hình ảnh món ăn thành phẩm
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <span style="font-size: 0.85rem; color: #6b7280;">Ảnh chụp món ăn rõ nét, đẹp mắt sẽ giúp món ăn nhận được nhiều lượt xem và yêu thích hơn.</span>
        </label>

        <h2 style="font-size: 1.25rem; font-weight: 800; border-bottom: 1.5px solid #f1ebe1; padding-bottom: 0.75rem; margin-top: 1rem;">
            2. Nguyên liệu chuẩn bị
        </h2>

        <label>
            Danh sách nguyên liệu <span style="color:red">*</span>
            <span style="font-size: 0.85rem; color: #6b7280;">Mỗi dòng một nguyên liệu kèm định lượng (Ví dụ: Thịt bò 300g, Hành tây 1 củ, Tỏi băm 2 muỗng...)</span>
            <textarea name="ingredients" rows="6" required placeholder="Thịt bò 300g&#10;Hành tây 1 củ&#10;Cần tây 2 nhánh&#10;Tỏi băm 1 muỗng cà phê&#10;Dầu hào, hạt nêm, tiêu..."></textarea>
        </label>

        <h2 style="font-size: 1.25rem; font-weight: 800; border-bottom: 1.5px solid #f1ebe1; padding-bottom: 0.75rem; margin-top: 1rem;">
            3. Các bước thực hiện
        </h2>

        <label>
            Hướng dẫn các bước nấu <span style="color:red">*</span>
            <span style="font-size: 0.85rem; color: #6b7280;">Mỗi bước trên một dòng hoặc đánh số rõ ràng để người xem dễ dàng thực hành:</span>
            <textarea name="instructions" rows="8" required placeholder="Bước 1: Sơ chế thịt bò thái mỏng, ướp gia vị trong 15 phút.&#10;Bước 2: Cắt hành tây múi cau, cần tây rửa sạch cắt khúc.&#10;Bước 3: Phi thơm tỏi, xào thịt bò trên lửa lớn cho chín tới rồi trút ra đĩa.&#10;Bước 4: Xào chín hành tây và cần tây, đổ thịt bò vào đảo đều rồi tắt bếp."></textarea>
        </label>

        <h2 style="font-size: 1.25rem; font-weight: 800; border-bottom: 1.5px solid #f1ebe1; padding-bottom: 0.75rem; margin-top: 1rem;">
            4. Bí quyết & Mẹo nấu của bạn (Tùy chọn)
        </h2>

        <label>
            Lời khuyên / Mẹo vặt từ tác giả
            <span style="font-size: 0.85rem; color: #6b7280;">Chia sẻ mẹo chọn nguyên liệu tươi ngon, cách điều chỉnh lửa hoặc cách bảo quản món ăn:</span>
            <textarea name="tips" rows="3" placeholder="Ví dụ: Xào thịt bò trên lửa thật lớn để thịt giữ nước ngọt và không bị dai; có thể vắt thêm chút chanh trước khi thưởng thức..."></textarea>
        </label>

        <h2 style="font-size: 1.25rem; font-weight: 800; border-bottom: 1.5px solid #f1ebe1; padding-bottom: 0.75rem; margin-top: 1rem;">
            5. Thông tin dinh dưỡng & Thẻ chế độ ăn (Tùy chọn)
        </h2>
        <p style="font-size: 0.85rem; color: #6b7280; margin-top: -0.5rem; margin-bottom: 0.75rem;">
            Món ăn có đầy đủ thông tin dinh dưỡng sẽ được đề xuất nổi bật trên trang chủ và bảng tìm kiếm!
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <label>
                Năng lượng (Calo)
                <input type="number" name="calories" placeholder="VD: 350" min="0">
                <span style="font-size: 0.75rem; color: #9ca3af;">kcal / 1 người</span>
            </label>
            <label>
                Chất đạm (Protein)
                <input type="number" name="protein" placeholder="VD: 25" min="0">
                <span style="font-size: 0.75rem; color: #9ca3af;">gam (g)</span>
            </label>
            <label>
                Tinh bột (Carbs)
                <input type="number" name="carbs" placeholder="VD: 40" min="0">
                <span style="font-size: 0.75rem; color: #9ca3af;">gam (g)</span>
            </label>
            <label>
                Chất béo (Fat)
                <input type="number" name="fat" placeholder="VD: 12" min="0">
                <span style="font-size: 0.75rem; color: #9ca3af;">gam (g)</span>
            </label>
        </div>

        <label>
            Thẻ chế độ ăn uống (Phân cách bởi dấu phẩy)
            <input type="text" name="dietary_tags" placeholder="VD: Eat Clean, Giàu Protein, Ít Calo, Nhanh < 30p">
        </label>

        <div style="margin-top: 1rem; display: flex; gap: 1rem; align-items: center;">
            <button type="submit" class="button button-create" style="padding: 0.85rem 2rem; font-size: 1.05rem;" <?= !is_logged_in() ? 'disabled' : '' ?>>
                <?= is_admin() ? '🚀 Đăng món (Xuất bản ngay)' : '✨ Gửi công thức chờ duyệt' ?>
            </button>
            <a href="<?= BASE_URL ?>/index.php" class="link-button">Hủy bỏ</a>
        </div>
    </form>
</div>
<?php
require __DIR__ . '/../includes/modal-login.php';
require __DIR__ . '/../includes/footer.php';
