</main>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <a href="<?= BASE_URL ?>/index.php" class="brand">
                <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Cookio Logo" style="height: 34px;">
            </a>
            <p>Cộng đồng chia sẻ công thức nấu ăn gia đình, tìm cảm hứng ẩm thực mỗi ngày và cùng nhau nấu những bữa ăn ngon lành, ấm cúng.</p>
        </div>

        <div class="footer-col">
            <h4>Khám phá</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/index.php">Công thức mới nhất</a></li>
                <li><a href="javascript:void(0)" onclick="openMealDeciderModal()">🎲 Hôm nay ăn gì?</a></li>
                <li><a href="<?= BASE_URL ?>/views/smart-fridge.php">🧊 Tủ lạnh thông minh</a></li>
                <li><a href="<?= BASE_URL ?>/views/cookbooks.php">📚 Sổ tay ẩm thực</a></li>
                <li><a href="<?= BASE_URL ?>/index.php?cat=M%C3%B3n+chay">Món chay bổ dưỡng</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Cộng đồng Cookio</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/views/create-recipe.php">Chia sẻ công thức</a></li>
                <li><a href="<?= BASE_URL ?>/views/saved-recipes.php">Bộ sưu tập đã lưu</a></li>
                <li><a href="<?= BASE_URL ?>/views/profile.php">Hồ sơ cá nhân</a></li>
                <?php if (is_admin()): ?>
                    <li><a href="<?= BASE_URL ?>/admin/index.php" style="color: #ea580c; font-weight: 700;">Admin Portal</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Cookio. Lấy cảm hứng từ cộng đồng ẩm thực Cookpad. Nấu ngon mỗi ngày cùng người thân yêu.</p>
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php if (isset($loadSmartFridgeScript) && $loadSmartFridgeScript): ?>
    <script src="<?= BASE_URL ?>/assets/js/smart-fridge.js"></script>
<?php endif; ?>
</body>
</html>
