<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$selectedCategory = trim((string) ($_GET['cat'] ?? ''));
$sortBy = trim((string) ($_GET['sort'] ?? 'newest'));
$filterTime = trim((string) ($_GET['time'] ?? ''));
$filterCal = trim((string) ($_GET['cal'] ?? ''));
$filterDiet = trim((string) ($_GET['diet'] ?? ''));

$categoryList = [
    '' => '🍳 Tất cả món',
    'Món xào' => '🥘 Món xào',
    'Món canh' => '🥣 Món canh',
    'Món kho' => '🍲 Món kho',
    'Món hấp' => '♨️ Món hấp',
    'Món chiên' => '🍤 Món chiên',
    'Món chay' => '🥗 Món chay',
];

$where = ["r.status = 'approved'"];
$params = [];

if ($searchQuery !== '') {
    $where[] = "(r.title LIKE ? OR r.ingredients LIKE ? OR r.description LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($selectedCategory !== '') {
    $where[] = "r.category = ?";
    $params[] = $selectedCategory;
}

// Multi-Criteria Smart Filters
if ($filterTime === 'quick') {
    $where[] = "CAST(r.cooking_time AS UNSIGNED) > 0 AND CAST(r.cooking_time AS UNSIGNED) <= 15";
} elseif ($filterTime === 'medium') {
    $where[] = "CAST(r.cooking_time AS UNSIGNED) > 15 AND CAST(r.cooking_time AS UNSIGNED) <= 30";
} elseif ($filterTime === 'long') {
    $where[] = "CAST(r.cooking_time AS UNSIGNED) > 30";
}

if ($filterCal === 'low') {
    $where[] = "r.calories IS NOT NULL AND r.calories < 300";
} elseif ($filterCal === 'medium') {
    $where[] = "r.calories >= 300 AND r.calories <= 500";
} elseif ($filterCal === 'high') {
    $where[] = "r.calories > 500";
}

if ($filterDiet === 'eatclean') {
    $where[] = "r.dietary_tags LIKE ?";
    $params[] = '%Eat Clean%';
} elseif ($filterDiet === 'protein') {
    $where[] = "r.dietary_tags LIKE ?";
    $params[] = '%Protein%';
} elseif ($filterDiet === 'lowcarb') {
    $where[] = "(r.dietary_tags LIKE ? OR r.dietary_tags LIKE ?)";
    $params[] = '%Low-Carb%';
    $params[] = '%Ít Calo%';
} elseif ($filterDiet === 'vegan') {
    $where[] = "(r.dietary_tags LIKE ? OR r.category = 'Món chay')";
    $params[] = '%Chay%';
}

$buildFilterUrl = function (array $overrides) use ($searchQuery, $selectedCategory, $sortBy, $filterTime, $filterCal, $filterDiet): string {
    $state = [
        'q' => $searchQuery,
        'cat' => $selectedCategory,
        'sort' => $sortBy !== 'newest' ? $sortBy : '',
        'time' => $filterTime,
        'cal' => $filterCal,
        'diet' => $filterDiet,
    ];
    $merged = array_merge($state, $overrides);
    $clean = array_filter($merged, fn($v) => $v !== null && $v !== '');
    return BASE_URL . '/index.php' . (!empty($clean) ? '?' . http_build_query($clean) : '');
};

$orderBy = match ($sortBy) {
    'popular'   => 'r.views_count DESC, r.created_at DESC',
    'top_rated' => 'avg_rating DESC, review_count DESC, r.created_at DESC',
    default     => 'r.created_at DESC',
};

$sql = "SELECT r.*, u.username AS author_name, 
        COALESCE((SELECT ROUND(AVG(c.rating), 1) FROM comments c WHERE c.recipe_id = r.id), 5.0) AS avg_rating,
        (SELECT COUNT(*) FROM comments c WHERE c.recipe_id = r.id) AS review_count
        FROM recipes r 
        INNER JOIN users u ON u.id = r.author_id 
        WHERE " . implode(' AND ', $where) . " 
        ORDER BY " . $orderBy;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// Saved recipes and liked recipes for current user
$userSavedIds = [];
$userLikedIds = [];
if (is_logged_in()) {
    $uid = (int) $_SESSION['user_id'];
    $stmtSaved = db()->prepare('SELECT recipe_id FROM saved_recipes WHERE user_id = ?');
    $stmtSaved->execute([$uid]);
    $userSavedIds = $stmtSaved->fetchAll(PDO::FETCH_COLUMN);

    $stmtLiked = db()->prepare('SELECT recipe_id FROM recipe_likes WHERE user_id = ?');
    $stmtLiked->execute([$uid]);
    $userLikedIds = $stmtLiked->fetchAll(PDO::FETCH_COLUMN);
}

// Recently viewed recipes from session
$recentRecipes = [];
if (!empty($_SESSION['recent_recipes'])) {
    $rIds = array_map('intval', $_SESSION['recent_recipes']);
    $placeholders = implode(',', array_fill(0, count($rIds), '?'));
    $stmtRecent = db()->prepare(
        "SELECT id, title, image_url, category, cooking_time 
         FROM recipes 
         WHERE id IN ($placeholders) AND status = 'approved'"
    );
    $stmtRecent->execute($rIds);
    $fetchedRecent = $stmtRecent->fetchAll();
    
    $recentMap = [];
    foreach ($fetchedRecent as $recItem) {
        $recentMap[(int) $recItem['id']] = $recItem;
    }
    foreach ($rIds as $rid) {
        if (isset($recentMap[$rid])) {
            $recentRecipes[] = $recentMap[$rid];
        }
    }
}

$pageTitle = 'Cookio - Nấu ngon mỗi ngày cùng cộng đồng bếp';
require __DIR__ . '/includes/header.php';
?>
<?php if (isset($_GET['success'])): ?>
    <p class="notice success">
        <?= match ($_GET['success']) {
            'login' => 'Chào mừng bạn quay trở lại với Cookio!',
            'registered' => 'Đăng ký tài khoản thành công! Hãy bắt đầu khám phá món ngon.',
            default => 'Thao tác thành công!'
        } ?>
    </p>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <p class="notice error">
        <?= match ($_GET['error']) {
            'login' => 'Tài khoản hoặc mật khẩu không chính xác.',
            'register' => 'Thông tin đăng ký không hợp lệ hoặc mật khẩu xác nhận không khớp.',
            'user-exists' => 'Tên tài khoản này đã tồn tại. Vui lòng chọn tên khác.',
            'csrf' => 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.',
            default => 'Đã có lỗi xảy ra. Vui lòng thử lại.'
        } ?>
    </p>
<?php endif; ?>

<!-- Hero Auto-Sliding Carousel Slider (3 Banners) -->
<div class="hero-slider-container" id="heroSlider">
    <!-- Slide 1: Bếp Nhà Sum Vầy & Tìm Kiếm -->
    <div class="hero-slide hero-slide-1 is-active">
        <div style="width: 100%; max-width: 820px; margin: 0 auto; text-align: center;">
            <span style="font-size: 0.85rem; font-weight: 800; background: rgba(255,255,255,0.25); padding: 0.3rem 0.85rem; border-radius: 9999px; display: inline-block; margin-bottom: 0.75rem; letter-spacing: 0.5px;">
                🥘 BẾP NHÀ SUM VẦY • NẤU NGON MỖI NGÀY
            </span>
            <h1 style="font-size: 2.3rem; font-weight: 900; margin: 0 0 0.6rem; line-height: 1.25; color: #ffffff;">
                Hôm Nay Bếp Nhà Nấu Món Gì?
            </h1>
            <p style="font-size: 1.05rem; opacity: 0.95; margin: 0 auto 1.5rem; max-width: 650px; line-height: 1.5;">
                Khám phá hơn 16+ công thức nấu ăn ngon, chuẩn vị cơm mẹ nấu cho bữa cơm gia đình luôn đầm ấm và ngập tràn yêu thương.
            </p>
            
            <div class="hero-search-box">
                <form method="get" action="<?= BASE_URL ?>/index.php">
                    <?php if ($selectedCategory !== ''): ?>
                        <input type="hidden" name="cat" value="<?= e($selectedCategory) ?>">
                    <?php endif; ?>
                    <?php if ($sortBy !== 'newest'): ?>
                        <input type="hidden" name="sort" value="<?= e($sortBy) ?>">
                    <?php endif; ?>
                    <?php if ($filterTime !== ''): ?>
                        <input type="hidden" name="time" value="<?= e($filterTime) ?>">
                    <?php endif; ?>
                    <?php if ($filterCal !== ''): ?>
                        <input type="hidden" name="cal" value="<?= e($filterCal) ?>">
                    <?php endif; ?>
                    <?php if ($filterDiet !== ''): ?>
                        <input type="hidden" name="diet" value="<?= e($filterDiet) ?>">
                    <?php endif; ?>
                    <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="Nhập tên món ăn hoặc nguyên liệu bạn có..." autocomplete="off">
                    <button type="submit" class="button button-create">Tìm công thức</button>
                </form>
            </div>

            <div style="margin-top: 0.85rem;">
                <button type="button" class="button" onclick="openMealDeciderModal()" style="background: #ffffff; color: #ea580c; border: 1.5px solid #fdba74; padding: 0.5rem 1.2rem; border-radius: 9999px; font-weight: 700; font-size: 0.92rem; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.12);">
                    🎲 Bạn chưa biết hôm nay ăn gì? Bấm để Cookio gợi ý ngay &rarr;
                </button>
            </div>

            <!-- Trending Tags -->
            <div class="trending-tags" style="margin-top: 1.2rem; display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                <span style="opacity: 0.9; font-size: 0.85rem; font-weight: 600;">Gợi ý tìm nhanh:</span>
                <a href="<?= BASE_URL ?>/index.php?q=phở" class="trending-tag" style="background: rgba(255,255,255,0.22); color: #fff; border-color: rgba(255,255,255,0.4);">#phở_bò</a>
                <a href="<?= BASE_URL ?>/index.php?q=sườn" class="trending-tag" style="background: rgba(255,255,255,0.22); color: #fff; border-color: rgba(255,255,255,0.4);">#sườn_xào</a>
                <a href="<?= BASE_URL ?>/index.php?q=kho" class="trending-tag" style="background: rgba(255,255,255,0.22); color: #fff; border-color: rgba(255,255,255,0.4);">#thịt_kho_tàu</a>
                <a href="<?= BASE_URL ?>/index.php?cat=M%C3%B3n+canh" class="trending-tag" style="background: rgba(255,255,255,0.22); color: #fff; border-color: rgba(255,255,255,0.4);">#canh_chua</a>
                <a href="<?= BASE_URL ?>/views/smart-fridge.php" class="trending-tag" style="background: #ffffff; color: #ea580c; font-weight: 800;">🧊 Tủ lạnh thông minh</a>
            </div>
        </div>
    </div>

    <!-- Slide 2: Tủ Lạnh Thông Minh -->
    <div class="hero-slide hero-slide-2">
        <div style="width: 100%; max-width: 820px; margin: 0 auto; text-align: center;">
            <span style="font-size: 0.85rem; font-weight: 800; background: rgba(255,255,255,0.25); padding: 0.3rem 0.85rem; border-radius: 9999px; display: inline-block; margin-bottom: 0.75rem; letter-spacing: 0.5px;">
                🧊 SMART FRIDGE • CHỐNG LÃNG PHÍ THỰC PHẨM
            </span>
            <h2 style="font-size: 2.3rem; font-weight: 900; margin: 0 0 0.6rem; line-height: 1.25; color: #ffffff;">
                Tủ Lạnh Thông Minh: Có Gì Nấu Nấy!
            </h2>
            <p style="font-size: 1.05rem; opacity: 0.95; margin: 0 auto 1.5rem; max-width: 650px; line-height: 1.5;">
                Bạn có thịt bò, trứng gà, rau củ còn sót trong tủ lạnh? Hãy chọn những nguyên liệu bạn có, Cookio sẽ gợi ý món ngon hoàn hảo chỉ trong 1 giây!
            </p>
            <div>
                <a href="<?= BASE_URL ?>/views/smart-fridge.php" class="button" style="background: #ffffff; color: #047857; font-weight: 800; padding: 0.75rem 2rem; border-radius: 9999px; font-size: 1.05rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.18);">
                    <span>🧊</span> Mở Tủ Lạnh Thông Minh Ngay &rarr;
                </a>
            </div>
            <div style="margin-top: 1.25rem; display: flex; justify-content: center; gap: 0.6rem; flex-wrap: wrap;">
                <span class="trending-tag" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.35);">✓ Tận dụng nguyên liệu sẵn có</span>
                <span class="trending-tag" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.35);">✓ Tiết kiệm chi phí gia đình</span>
                <span class="trending-tag" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.35);">✓ Nấu ngon không lãng phí</span>
            </div>
        </div>
    </div>

    <!-- Slide 3: Sổ Tay & Thực Đơn 7 Ngày -->
    <div class="hero-slide hero-slide-3">
        <div style="width: 100%; max-width: 820px; margin: 0 auto; text-align: center;">
            <span style="font-size: 0.85rem; font-weight: 800; background: rgba(255,255,255,0.25); padding: 0.3rem 0.85rem; border-radius: 9999px; display: inline-block; margin-bottom: 0.75rem; letter-spacing: 0.5px;">
                📚 COOKBOOKS • THỰC ĐƠN TUYỂN CHỌN
            </span>
            <h2 style="font-size: 2.3rem; font-weight: 900; margin: 0 0 0.6rem; line-height: 1.25; color: #ffffff;">
                Sổ Tay Ẩm Thực & Thực Đơn Cả Tuần
            </h2>
            <p style="font-size: 1.05rem; opacity: 0.95; margin: 0 auto 1.5rem; max-width: 650px; line-height: 1.5;">
                Giải phóng thời gian nội trợ với các bộ thực đơn chuẩn dinh dưỡng: Bữa cơm 7 ngày sum vầy, Eat Clean giảm cân và Bữa sáng cấp tốc.
            </p>
            <div>
                <a href="<?= BASE_URL ?>/views/cookbooks.php" class="button" style="background: #ffffff; color: #4f46e5; font-weight: 800; padding: 0.75rem 2rem; border-radius: 9999px; font-size: 1.05rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.18);">
                    <span>📚</span> Khám Phá Các Bộ Sưu Tập Ngay &rarr;
                </a>
            </div>
            <div style="margin-top: 1.25rem; display: flex; justify-content: center; gap: 0.6rem; flex-wrap: wrap;">
                <span class="trending-tag" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.35);">✓ Bữa cơm gia đình 7 ngày</span>
                <span class="trending-tag" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.35);">✓ Gom nguyên liệu đi chợ 1-Click</span>
                <span class="trending-tag" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.35);">✓ Cân bằng calo & đạm sạch</span>
            </div>
        </div>
    </div>

    <!-- Navigation Arrows -->
    <button type="button" class="hero-slider-prev" aria-label="Slide trước">&lsaquo;</button>
    <button type="button" class="hero-slider-next" aria-label="Slide tiếp">&rsaquo;</button>

    <!-- Pagination Dots -->
    <div class="hero-slider-dots">
        <button type="button" class="hero-dot is-active" aria-label="Chuyển đến slide 1"></button>
        <button type="button" class="hero-dot" aria-label="Chuyển đến slide 2"></button>
        <button type="button" class="hero-dot" aria-label="Chuyển đến slide 3"></button>
    </div>
</div>

<!-- Recently Viewed Row (Cookpad Signature Feature) -->
<?php if (!empty($recentRecipes) && $searchQuery === '' && $selectedCategory === ''): ?>
    <section style="margin-bottom: 2rem;">
        <h3 style="margin: 0 0 0.85rem; font-size: 1.1rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
            <span>🕒 Món bạn vừa xem gần đây</span>
        </h3>
        <div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
            <?php foreach ($recentRecipes as $rec): ?>
                <?php $img = !empty($rec['image_url']) ? BASE_URL . '/' . e($rec['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg'; ?>
                <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $rec['id'] ?>" 
                   style="display: flex; align-items: center; gap: 0.75rem; background: #ffffff; padding: 0.5rem 0.85rem; border-radius: 0.75rem; border: 1.5px solid var(--border); text-decoration: none; color: inherit; min-width: 210px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.03); transition: border-color 0.2s;"
                   onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                    <img src="<?= $img ?>" alt="<?= e($rec['title']) ?>" style="width: 48px; height: 48px; border-radius: 0.5rem; object-fit: cover; flex-shrink: 0;">
                    <div style="overflow: hidden;">
                        <strong style="display: block; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main);">
                            <?= e($rec['title']) ?>
                        </strong>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">
                            ⏱️ <?= e($rec['cooking_time']) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- WEEKLY SIGNATURE BANNER (CHƯA BIẾT TUẦN NÀY NẤU GÌ? - MATCHING IMAGE 2) -->
<section class="weekly-curated-banner mb-6">
    <div class="weekly-banner-inner">
        <div class="weekly-banner-left">
            <h2 class="weekly-banner-title">Chưa biết tuần này nấu gì?</h2>
            <p class="weekly-banner-sub">Gợi ý ngon và dễ làm cho cả tuần.</p>
            <a href="<?= BASE_URL ?>/views/cookbooks.php" class="weekly-banner-cta">
                <span>Xem công thức tuần này</span>
                <span class="cta-hand">👆</span>
            </a>
        </div>
        <div class="weekly-banner-right">
            <img src="<?= BASE_URL ?>/assets/images/trending/banner-dishes.svg" alt="Món ngon cả tuần" class="weekly-banner-img">
        </div>
    </div>
    <div class="weekly-newsletter-bar">
        <span class="newsletter-icon">✉️</span>
        <span>Muốn có cảm hứng nấu ăn mới mỗi tuần? <strong>Bật nhận bản tin:</strong> Hồ sơ &rarr; Cài đặt &rarr; Tùy chọn thông báo &rarr; Bản tin</span>
    </div>
    <div class="weekly-banner-dots">
        <span class="w-dot active"></span>
        <span class="w-dot"></span>
        <span class="w-dot"></span>
        <span class="w-dot"></span>
    </div>
</section>

<!-- TRENDING KEYWORDS 8-CARD GRID (TỪ KHÓA THỊNH HÀNH - AUTHENTIC VISUALS MATCHING IMAGE 2) -->
<section class="trending-keywords-section mb-6">
    <div class="trending-keywords-header">
        <h2 class="trending-keywords-title">Từ Khóa Thịnh Hành</h2>
        <span class="trending-timestamp">Cập nhật <?= date('H:i') ?></span>
    </div>
    <div class="trending-cards-grid">
        <!-- 1. tôm -->
        <a href="<?= BASE_URL ?>/index.php?q=tôm" class="trend-card">
            <img src="<?= BASE_URL ?>/assets/images/trending/tom.svg" alt="tôm" loading="lazy">
            <span class="trend-card-title">tôm</span>
        </a>

        <!-- 2. trứng -->
        <a href="<?= BASE_URL ?>/index.php?q=trứng" class="trend-card">
            <img src="<?= BASE_URL ?>/assets/images/trending/trung.svg" alt="trứng" loading="lazy">
            <span class="trend-card-title">trứng</span>
        </a>

        <!-- 3. ức gà -->
        <a href="<?= BASE_URL ?>/index.php?q=ức+gà" class="trend-card">
            <img src="<?= BASE_URL ?>/assets/images/trending/uc-ga.svg" alt="ức gà" loading="lazy">
            <span class="trend-card-title">ức gà</span>
        </a>

        <!-- 4. thịt lợn -->
        <a href="<?= BASE_URL ?>/index.php?q=thịt" class="trend-card">
            <img src="<?= BASE_URL ?>/assets/images/trending/thit-lon.svg" alt="thịt lợn" loading="lazy">
            <span class="trend-card-title">thịt lợn</span>
        </a>

        <!-- 5. gà -->
        <a href="<?= BASE_URL ?>/index.php?q=gà" class="trend-card">
            <img src="<?= BASE_URL ?>/assets/images/trending/ga.svg" alt="gà" loading="lazy">
            <span class="trend-card-title">gà</span>
        </a>

        <!-- 6. bánh ngọt đơn giản -->
        <a href="<?= BASE_URL ?>/index.php?q=bánh" class="trend-card">
            <img src="<?= BASE_URL ?>/assets/images/trending/banh-ngot.svg" alt="bánh ngọt đơn giản" loading="lazy">
            <span class="trend-card-title">bánh ngọt đơn giản</span>
        </a>

        <!-- 7. nấu ăn hàng ngày -->
        <a href="<?= BASE_URL ?>/index.php?cat=Món+kho" class="trend-card">
            <img src="<?= BASE_URL ?>/assets/images/trending/nau-an-hang-ngay.svg" alt="nấu ăn hàng ngày" loading="lazy">
            <span class="trend-card-title">nấu ăn hàng ngày</span>
        </a>

        <!-- 8. tốn cơm -->
        <a href="<?= BASE_URL ?>/index.php?sort=popular" class="trend-card">
            <img src="<?= BASE_URL ?>/assets/images/trending/ton-com.svg" alt="tốn cơm" loading="lazy">
            <span class="trend-card-title">tốn cơm</span>
        </a>
    </div>
</section>

<!-- YUMMYDAY SIGNATURE: MÂM CƠM GIA ĐÌNH CHUẨN VỊ (COMBO 3 MÓN ĐỦ CHẤT) -->
<?php if ($searchQuery === '' && $selectedCategory === ''): ?>
<section class="family-meals-section mb-8">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <span style="font-size: 0.8rem; font-weight: 800; color: #ea580c; background: #fff7ed; padding: 0.25rem 0.65rem; border-radius: 9999px; border: 1px solid #fed7aa;">
                🍱 YUMMYDAY FAMILY COMBO
            </span>
            <h2 style="font-size: 1.45rem; font-weight: 900; color: var(--text-main); margin: 0.35rem 0 0;">
                Gợi Ý Mâm Cơm Gia Đình Hôm Nay
            </h2>
        </div>
        <a href="<?= BASE_URL ?>/views/cookbooks.php" style="color: #ea580c; font-size: 0.88rem; font-weight: 700; text-decoration: none;">
            Xem tất cả bộ thực đơn &rarr;
        </a>
    </div>

    <div class="family-meals-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.25rem;">
        <!-- Mâm Cơm 1 -->
        <div class="meal-combo-card" style="background: var(--bg-card, #ffffff); border: 1.5px solid #fed7aa; border-radius: 1.25rem; padding: 1.25rem; box-shadow: 0 4px 16px rgba(234, 88, 12, 0.06);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <span class="meal-badge" style="background: #ea580c; color: #fff; font-size: 0.76rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 6px;">
                    MÂM CƠM 1 • ĐẬM ĐÀ ĐƯA CƠM
                </span>
                <span style="font-size: 0.82rem; font-weight: 700; color: #047857; background: #ecfdf5; padding: 0.25rem 0.6rem; border-radius: 9999px;">
                    💰 ~75.000đ / 4 người
                </span>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0 0 0.85rem; color: var(--text-main);">
                Tôm Rim Ba Chỉ &bull; Canh Chua Cá Lóc &bull; Đậu Sốt Cà
            </h3>
            <div class="combo-dishes-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.6rem; margin-bottom: 1rem;">
                <div class="combo-dish-thumb" style="text-align: center;">
                    <img src="<?= BASE_URL ?>/assets/images/recipes/tom-rim-ba-chi.svg" alt="Tôm rim" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--text-main); display: block; margin-top: 0.25rem;">Tôm rim ba chỉ</span>
                </div>
                <div class="combo-dish-thumb" style="text-align: center;">
                    <img src="<?= BASE_URL ?>/assets/images/recipes/canh-chua-ca-loc.svg" alt="Canh chua" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--text-main); display: block; margin-top: 0.25rem;">Canh chua cá lóc</span>
                </div>
                <div class="combo-dish-thumb" style="text-align: center;">
                    <img src="<?= BASE_URL ?>/assets/images/recipes/dau-phu-sot-ca-chua.svg" alt="Đậu sốt cà" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--text-main); display: block; margin-top: 0.25rem;">Đậu sốt cà chua</span>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: space-between; align-items: center; border-top: 1px dashed #fed7aa; padding-top: 0.75rem;">
                <span style="font-size: 0.8rem; color: #64748b;">⏱️ 35 phút &bull; 3 món chuẩn vị</span>
                <button type="button" class="button button-create" onclick="openGroceryModal('- 300g tôm tươi\n- 350g thịt ba chỉ\n- 500g cá lóc đồng\n- 1/4 quả dứa, 2 quả cà chua, đậu bắp, dọc mùng\n- 4 bìa đậu phụ, 200g thịt nạc heo băm\n- Hành lá, tỏi, ớt, nước mắm, đường, dầu hào', 'Danh Sách Đi Chợ - Mâm Cơm Đậm Đà')" style="padding: 0.4rem 0.85rem; font-size: 0.82rem; font-weight: 700;">
                    📋 Gom nguyên liệu đi chợ
                </button>
            </div>
        </div>

        <!-- Mâm Cơm 2 -->
        <div class="meal-combo-card" style="background: var(--bg-card, #ffffff); border: 1.5px solid #bbf7d0; border-radius: 1.25rem; padding: 1.25rem; box-shadow: 0 4px 16px rgba(4, 120, 87, 0.06);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <span class="meal-badge" style="background: #059669; color: #fff; font-size: 0.76rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 6px;">
                    MÂM CƠM 2 • THANH NHẸ & HEALTHY
                </span>
                <span style="font-size: 0.82rem; font-weight: 700; color: #047857; background: #ecfdf5; padding: 0.25rem 0.6rem; border-radius: 9999px;">
                    💰 ~60.000đ / 3-4 người
                </span>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0 0 0.85rem; color: var(--text-main);">
                Ức Gà Teriyaki &bull; Trứng Hấp Vân Hoa &bull; Canh Bò Cải Xanh
            </h3>
            <div class="combo-dishes-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.6rem; margin-bottom: 1rem;">
                <div class="combo-dish-thumb" style="text-align: center;">
                    <img src="<?= BASE_URL ?>/assets/images/recipes/uc-ga-teriyaki.svg" alt="Ức gà" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--text-main); display: block; margin-top: 0.25rem;">Ức gà teriyaki</span>
                </div>
                <div class="combo-dish-thumb" style="text-align: center;">
                    <img src="<?= BASE_URL ?>/assets/images/recipes/trung-hap-van-hoa.svg" alt="Trứng hấp" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--text-main); display: block; margin-top: 0.25rem;">Trứng hấp vân hoa</span>
                </div>
                <div class="combo-dish-thumb" style="text-align: center;">
                    <img src="<?= BASE_URL ?>/assets/images/recipes/canh-bo-rau-xanh.svg" alt="Canh cải" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--text-main); display: block; margin-top: 0.25rem;">Canh bò cải xanh</span>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: space-between; align-items: center; border-top: 1px dashed #bbf7d0; padding-top: 0.75rem;">
                <span style="font-size: 0.8rem; color: #64748b;">⏱️ 25 phút &bull; Ít béo & giàu đạm</span>
                <button type="button" class="button button-create" onclick="openGroceryModal('- 400g ức gà tươi\n- 4 quả trứng gà\n- 200g thịt bò băm\n- 1 bó rau cải xanh non\n- Mật ong rừng, nước tương, dầu mè, hạt tiêu, tỏi gừng', 'Danh Sách Đi Chợ - Mâm Cơm Thanh Nhẹ')" style="padding: 0.4rem 0.85rem; font-size: 0.82rem; font-weight: 700; background: #059669;">
                    📋 Gom nguyên liệu đi chợ
                </button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Category Filter Pills (Cookpad Tabs) -->
<div class="category-pills-container">
    <div class="category-pills">
        <?php foreach ($categoryList as $catVal => $label): ?>
            <?php
            $isActive = ($selectedCategory === $catVal);
            $catUrl = $buildFilterUrl(['cat' => $catVal]);
            ?>
            <a href="<?= $catUrl ?>" class="category-pill <?= $isActive ? 'active' : '' ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<section class="recipe-section">
    <div class="section-heading" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem;">
        <div>
            <?php $hasAnyFilter = ($searchQuery !== '' || $selectedCategory !== '' || $filterTime !== '' || $filterCal !== '' || $filterDiet !== ''); ?>
            <?php if ($hasAnyFilter): ?>
                <h2 style="margin:0;">
                    Kết quả tìm kiếm
                    <?php if ($selectedCategory !== ''): ?> &bull; Danh mục "<em><?= e($selectedCategory) ?></em>"<?php endif; ?>
                    <?php if ($searchQuery !== ''): ?> &bull; Từ khóa "<em><?= e($searchQuery) ?></em>"<?php endif; ?> 
                    <span style="font-size: 1.1rem; color: #6b7280; font-weight: 600;">(<?= count($recipes) ?> món)</span>
                </h2>
                <a href="<?= BASE_URL ?>/index.php" class="link-button" style="display:inline-block; margin-top:0.25rem;">&times; Xóa toàn bộ bộ lọc</a>
            <?php else: ?>
                <h2 style="margin:0;">Khám phá món ngon</h2>
                <a href="<?= BASE_URL ?>/views/smart-fridge.php" style="color:var(--primary); font-size:0.9rem; font-weight:600; text-decoration:none;">Xem gợi ý theo nguyên liệu &rarr;</a>
            <?php endif; ?>
        </div>

        <!-- Sorting Tabs -->
        <div style="display:flex; align-items:center; gap:0.5rem; background:#fff; padding:0.25rem; border-radius:0.6rem; border:1px solid var(--border);">
            <span style="font-size:0.82rem; font-weight:700; color:var(--text-muted); padding-left:0.5rem;">Sắp xếp:</span>
            <?php
            $sortOptions = [
                'newest'    => '🕒 Mới nhất',
                'popular'   => '🔥 Xem nhiều',
                'top_rated' => '⭐ Đánh giá cao',
            ];
            foreach ($sortOptions as $sKey => $sLabel):
                $isSortActive = ($sortBy === $sKey);
                $sUrl = $buildFilterUrl(['sort' => ($sKey !== 'newest' ? $sKey : '')]);
            ?>
                <a href="<?= $sUrl ?>" 
                    style="font-size:0.85rem; font-weight:600; text-decoration:none; padding:0.35rem 0.75rem; border-radius:0.45rem; transition:all 0.15s; <?= $isSortActive ? 'background:var(--primary); color:#fff;' : 'color:var(--text-main);' ?>">
                    <?= $sLabel ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Multi-Criteria Smart Filter Bar -->
    <div class="smart-filter-bar">
        <div class="filter-group">
            <span class="filter-label">⏱️ Thời gian:</span>
            <a href="<?= $buildFilterUrl(['time' => ($filterTime === 'quick' ? '' : 'quick')]) ?>" 
               class="filter-chip <?= $filterTime === 'quick' ? 'is-active' : '' ?>">Dưới 15 phút</a>
            <a href="<?= $buildFilterUrl(['time' => ($filterTime === 'medium' ? '' : 'medium')]) ?>" 
               class="filter-chip <?= $filterTime === 'medium' ? 'is-active' : '' ?>">15 - 30 phút</a>
            <a href="<?= $buildFilterUrl(['time' => ($filterTime === 'long' ? '' : 'long')]) ?>" 
               class="filter-chip <?= $filterTime === 'long' ? 'is-active' : '' ?>">Trên 30 phút</a>
        </div>
        <div class="filter-group">
            <span class="filter-label">🔥 Năng lượng:</span>
            <a href="<?= $buildFilterUrl(['cal' => ($filterCal === 'low' ? '' : 'low')]) ?>" 
               class="filter-chip <?= $filterCal === 'low' ? 'is-active' : '' ?>">&lt; 300 kcal</a>
            <a href="<?= $buildFilterUrl(['cal' => ($filterCal === 'medium' ? '' : 'medium')]) ?>" 
               class="filter-chip <?= $filterCal === 'medium' ? 'is-active' : '' ?>">300 - 500 kcal</a>
            <a href="<?= $buildFilterUrl(['cal' => ($filterCal === 'high' ? '' : 'high')]) ?>" 
               class="filter-chip <?= $filterCal === 'high' ? 'is-active' : '' ?>">&gt; 500 kcal</a>
        </div>
        <div class="filter-group">
            <span class="filter-label">🥗 Chế độ ăn:</span>
            <a href="<?= $buildFilterUrl(['diet' => ($filterDiet === 'eatclean' ? '' : 'eatclean')]) ?>" 
               class="filter-chip <?= $filterDiet === 'eatclean' ? 'is-active' : '' ?>">Eat Clean</a>
            <a href="<?= $buildFilterUrl(['diet' => ($filterDiet === 'protein' ? '' : 'protein')]) ?>" 
               class="filter-chip <?= $filterDiet === 'protein' ? 'is-active' : '' ?>">Giàu Protein</a>
            <a href="<?= $buildFilterUrl(['diet' => ($filterDiet === 'lowcarb' ? '' : 'lowcarb')]) ?>" 
               class="filter-chip <?= $filterDiet === 'lowcarb' ? 'is-active' : '' ?>">Low-Carb</a>
            <a href="<?= $buildFilterUrl(['diet' => ($filterDiet === 'vegan' ? '' : 'vegan')]) ?>" 
               class="filter-chip <?= $filterDiet === 'vegan' ? 'is-active' : '' ?>">Món chay</a>
        </div>
        <?php if ($filterTime !== '' || $filterCal !== '' || $filterDiet !== ''): ?>
            <div style="margin-left: auto;">
                <a href="<?= $buildFilterUrl(['time' => '', 'cal' => '', 'diet' => '']) ?>" style="font-size: 0.82rem; color: #ea580c; font-weight: 700; text-decoration: none;">
                    &times; Bỏ chọn bộ lọc
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="recipe-grid">
        <?php foreach ($recipes as $recipe): ?>
            <?php 
            $isSaved = in_array((int) $recipe['id'], $userSavedIds, true);
            $imgSrc = !empty($recipe['image_url']) ? BASE_URL . '/' . e($recipe['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
            ?>
            <article class="recipe-card">
                <div class="recipe-card-media">
                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                        <img src="<?= $imgSrc ?>" alt="<?= e($recipe['title']) ?>" loading="lazy">
                    </a>
                    
                    <span class="card-category-badge"><?= e($recipe['category'] ?? 'Món chính') ?></span>
                    <?php
                    $estCost = match($recipe['category'] ?? '') {
                        'Món kho' => '~45k - 70k',
                        'Món xào' => '~35k - 55k',
                        'Món canh' => '~25k - 45k',
                        'Món hấp' => '~30k - 50k',
                        'Món chiên' => '~40k - 65k',
                        default => '~35k - 60k',
                    };
                    ?>
                    <span class="badge-cal-pill" style="position: absolute; bottom: 0.65rem; left: 0.65rem; z-index: 2; display: flex; gap: 0.35rem; align-items: center;">
                        <?php if (!empty($recipe['calories'])): ?>
                            <span>🔥 <?= (int)$recipe['calories'] ?> kcal</span>
                            <span>&bull;</span>
                        <?php endif; ?>
                        <span>💰 <?= $estCost ?></span>
                    </span>

                    <!-- Quick Bookmark Button -->
                    <?php if (is_logged_in()): ?>
                        <form method="post" action="<?= BASE_URL ?>/actions/saved_action.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
                            <input type="hidden" name="return_url" value="index.php">
                            <button type="submit" class="card-save-btn" title="<?= $isSaved ? 'Bỏ lưu' : 'Lưu vào yêu thích' ?>" aria-label="Lưu công thức">
                                <?= $isSaved ? '❤️' : '🤍' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="card-save-btn" data-open-login title="Đăng nhập để lưu món" aria-label="Lưu công thức">
                            🤍
                        </button>
                    <?php endif; ?>
                </div>

                <div class="recipe-card-body">
                    <div class="card-meta-row" style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <span>⏱️ <?= e($recipe['cooking_time'] ?? '30 phút') ?></span>
                            <span>⭐ <?= e((string) $recipe['avg_rating']) ?></span>
                            <span>👁️ <?= number_format((int) ($recipe['views_count'] ?? 0)) ?></span>
                        </div>
                        <?php $isLiked = in_array((int)$recipe['id'], $userLikedIds, true); ?>
                        <button type="button" class="js-like-btn <?= $isLiked ? 'is-liked' : '' ?>" data-recipe-id="<?= (int)$recipe['id'] ?>" title="Thả tim món này">
                            <span>❤️</span> <span class="like-count"><?= (int)($recipe['likes_count'] ?? 0) ?></span>
                        </button>
                    </div>

                    <h3>
                        <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                            <?= e($recipe['title']) ?>
                        </a>
                    </h3>

                    <?php if (!empty($recipe['description'])): ?>
                        <p class="card-desc"><?= e(mb_strimwidth((string) $recipe['description'], 0, 95, '...')) ?></p>
                    <?php endif; ?>

                    <!-- Author Chip & Chef Badges -->
                    <div class="card-author-chip" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.35rem;">
                        <a href="<?= BASE_URL ?>/views/author.php?id=<?= (int) $recipe['author_id'] ?>" style="display:inline-flex; align-items:center; gap:0.4rem; text-decoration:none; color:inherit;">
                            <span class="author-mini-avatar"><?= mb_substr((string) $recipe['author_name'], 0, 1) ?></span>
                            <span>Bởi <strong><?= e($recipe['author_name']) ?></strong></span>
                        </a>
                        <?php 
                        $chefBadges = get_chef_badges((int)$recipe['author_id']); 
                        if (!empty($chefBadges)): 
                        ?>
                            <span class="chef-badge <?= $chefBadges[0]['class'] ?>" title="<?= $chefBadges[0]['label'] ?>">
                                <?= $chefBadges[0]['icon'] ?> <?= $chefBadges[0]['label'] ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Add To Cookbook Action -->
                    <div style="margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px dashed var(--border, #e2e8f0); display: flex; justify-content: space-between; align-items: center;">
                        <?php if (is_logged_in()): ?>
                            <button type="button" class="btn-card-cookbook" onclick="openCookbookSelectModal(<?= (int)$recipe['id'] ?>, '<?= e(addslashes($recipe['title'])) ?>')" title="Lưu món này vào sổ tay thực đơn">
                                <span>📚</span> + Sổ tay
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-card-cookbook" data-open-login title="Đăng nhập để thêm vào sổ tay">
                                <span>📚</span> + Sổ tay
                            </button>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int)$recipe['id'] ?>" class="btn-card-view-detail">
                            Xem cách làm &rarr;
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$recipes): ?>
            <div class="empty-state-box" style="grid-column: 1 / -1; padding: 3rem 2rem;">
                <p style="font-size: 1.2rem; font-weight: 700; color: #374151;">Chưa tìm thấy món ăn phù hợp</p>
                <p style="color: #6b7280; margin-bottom: 1.5rem;">Hãy thử tìm kiếm với từ khóa khác hoặc khám phá kho nguyên liệu trong Tủ lạnh thông minh.</p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a class="button button-outline" href="<?= BASE_URL ?>/index.php">Xem tất cả món</a>
                    <a class="button button-create" href="<?= BASE_URL ?>/views/create-recipe.php">+ Đóng góp công thức này</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- YUMMYDAY SIGNATURE: MẸO VẶT BẾP HAY NỔI BẬT -->
<section class="kitchen-tips-highlight mb-10" style="margin-top: 2.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <span style="font-size: 0.8rem; font-weight: 800; color: #ea580c; background: #fff7ed; padding: 0.25rem 0.65rem; border-radius: 9999px; border: 1px solid #fed7aa;">
                💡 KITCHEN HACKS
            </span>
            <h2 style="font-size: 1.45rem; font-weight: 900; color: var(--text-main); margin: 0.35rem 0 0;">
                Mẹo Vặt Nhà Bếp & Bí Quyết Nấu Ngon
            </h2>
        </div>
        <a href="<?= BASE_URL ?>/views/kitchen-tips.php" style="color: #ea580c; font-size: 0.88rem; font-weight: 700; text-decoration: none;">
            Xem tất cả mẹo bếp &rarr;
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
        <a href="<?= BASE_URL ?>/views/kitchen-tips.php" class="card p-4" style="background: var(--bg-card, #ffffff); border: 1.5px solid var(--border, #e2e8f0); border-radius: 1rem; text-decoration: none; color: inherit; display: block; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s, border-color 0.2s;">
            <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">🍳</div>
            <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.35rem;">Mẹo chiên rán không bắn dầu & không tróc da</h4>
            <p style="font-size: 0.86rem; color: var(--text-muted); margin: 0; line-height: 1.4;">Thấm khô thực phẩm, xát lát gừng quanh lòng chảo và rắc một chút muối hạt vào dầu nóng...</p>
        </a>
        <a href="<?= BASE_URL ?>/views/kitchen-tips.php" class="card p-4" style="background: var(--bg-card, #ffffff); border: 1.5px solid var(--border, #e2e8f0); border-radius: 1rem; text-decoration: none; color: inherit; display: block; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s, border-color 0.2s;">
            <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">🥦</div>
            <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.35rem;">Bí quyết luộc rau muống xanh mướt giòn rụm</h4>
            <p style="font-size: 0.86rem; color: var(--text-muted); margin: 0; line-height: 1.4;">Nước sôi ngập thêm muối hạt, luộc mở nắp vung và sốc nhiệt ngay vào âu nước đá lạnh...</p>
        </a>
        <a href="<?= BASE_URL ?>/views/kitchen-tips.php" class="card p-4" style="background: var(--bg-card, #ffffff); border: 1.5px solid var(--border, #e2e8f0); border-radius: 1rem; text-decoration: none; color: inherit; display: block; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s, border-color 0.2s;">
            <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">🥩</div>
            <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.35rem;">Ướp sườn, thịt nướng mềm mọng không khô</h4>
            <p style="font-size: 0.86rem; color: var(--text-muted); margin: 0; line-height: 1.4;">Thêm 1 thìa sữa đặc có đường hoặc nước ép lê/táo và khóa ẩm bằng dầu ăn...</p>
        </a>
    </div>
</section>

<!-- YUMMYDAY SIGNATURE: BẢNG VÀNG ĐẦU BẾP TUẦN NÀY (CREATOR LEADERBOARD) -->
<section class="chef-leaderboard-section mb-10" style="background: linear-gradient(135deg, #fff7ed 0%, #fffcf8 100%); border: 1.5px solid #fed7aa; border-radius: 1.25rem; padding: 1.75rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <span style="font-size: 0.8rem; font-weight: 800; color: #ea580c; background: #ffffff; padding: 0.25rem 0.65rem; border-radius: 9999px; border: 1px solid #fed7aa;">
                🏆 YUMMYDAY LEADERBOARD
            </span>
            <h2 style="font-size: 1.35rem; font-weight: 900; color: #7c2d12; margin: 0.35rem 0 0;">
                Bảng Vàng Đầu Bếp Tích Cực Tuần Này
            </h2>
        </div>
        <span style="font-size: 0.85rem; color: #9a3412; font-weight: 600;">Cập nhật tự động mỗi tuần</span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
        <!-- Top 1 -->
        <div style="background: #ffffff; border: 1.5px solid #fed7aa; border-radius: 1rem; padding: 1rem; display: flex; align-items: center; gap: 0.85rem; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.08);">
            <div style="position: relative; flex-shrink: 0;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #ea580c; color: #fff; font-size: 1.3rem; font-weight: 900; display: flex; align-items: center; justify-content: center;">
                    L
                </div>
                <span style="position: absolute; top: -8px; left: -8px; font-size: 1.4rem;">🥇</span>
            </div>
            <div style="overflow: hidden; flex: 1;">
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <strong style="font-size: 0.95rem; color: #1e293b;">Chef Lan</strong>
                    <span style="font-size: 0.72rem; background: #fff7ed; color: #ea580c; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 4px;">Quán Quân</span>
                </div>
                <span style="font-size: 0.78rem; color: #64748b; display: block;">@chef_lan &bull; 8 món ngon</span>
                <span style="font-size: 0.76rem; color: #ea580c; font-weight: 700;">❤️ 420 lượt thích</span>
            </div>
            <a href="<?= BASE_URL ?>/views/author.php?id=2" class="button button-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 9999px;">Bếp &rarr;</a>
        </div>

        <!-- Top 2 -->
        <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 1rem; padding: 1rem; display: flex; align-items: center; gap: 0.85rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div style="position: relative; flex-shrink: 0;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #0284c7; color: #fff; font-size: 1.3rem; font-weight: 900; display: flex; align-items: center; justify-content: center;">
                    B
                </div>
                <span style="position: absolute; top: -8px; left: -8px; font-size: 1.4rem;">🥈</span>
            </div>
            <div style="overflow: hidden; flex: 1;">
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <strong style="font-size: 0.95rem; color: #1e293b;">Mẹ Bống</strong>
                    <span style="font-size: 0.72rem; background: #e0f2fe; color: #0284c7; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 4px;">Á Quân</span>
                </div>
                <span style="font-size: 0.78rem; color: #64748b; display: block;">@me_bong &bull; 5 món ngon</span>
                <span style="font-size: 0.76rem; color: #0284c7; font-weight: 700;">❤️ 290 lượt thích</span>
            </div>
            <a href="<?= BASE_URL ?>/views/author.php?id=5" class="button button-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 9999px;">Bếp &rarr;</a>
        </div>

        <!-- Top 3 -->
        <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 1rem; padding: 1rem; display: flex; align-items: center; gap: 0.85rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div style="position: relative; flex-shrink: 0;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #16a34a; color: #fff; font-size: 1.3rem; font-weight: 900; display: flex; align-items: center; justify-content: center;">
                    N
                </div>
                <span style="position: absolute; top: -8px; left: -8px; font-size: 1.4rem;">🥉</span>
            </div>
            <div style="overflow: hidden; flex: 1;">
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <strong style="font-size: 0.95rem; color: #1e293b;">Chú Năm Cook</strong>
                    <span style="font-size: 0.72rem; background: #dcfce7; color: #16a34a; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 4px;">Hạng Ba</span>
                </div>
                <span style="font-size: 0.78rem; color: #64748b; display: block;">@chu_nam_cook &bull; 4 món ngon</span>
                <span style="font-size: 0.76rem; color: #16a34a; font-weight: 700;">❤️ 185 lượt thích</span>
            </div>
            <a href="<?= BASE_URL ?>/views/author.php?id=7" class="button button-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 9999px;">Bếp &rarr;</a>
        </div>
    </div>
</section>

<?php
require __DIR__ . '/includes/modal-login.php';
require __DIR__ . '/includes/footer.php';
