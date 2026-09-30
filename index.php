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
        JOIN users u ON u.id = r.author_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY " . $orderBy;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// Saved recipes ids
$userSavedIds = [];
if (is_logged_in()) {
    $stmtSaved = db()->prepare('SELECT recipe_id FROM saved_recipes WHERE user_id = ?');
    $stmtSaved->execute([(int) $_SESSION['user_id']]);
    $userSavedIds = $stmtSaved->fetchAll(PDO::FETCH_COLUMN);
}

// 1. Curated Dishes for "Gợi ý thực đơn hôm nay" (Image 2)
$stmtMenuSuggest = db()->query("
    SELECT r.*, u.username AS author_name 
    FROM recipes r
    JOIN users u ON u.id = r.author_id
    WHERE r.status = 'approved' AND (
        r.title LIKE '%cơm chiên gà%' OR
        r.title LIKE '%thịt bò xào tỏi%' OR
        r.title LIKE '%nộm thịt bò%' OR
        r.title LIKE '%súp gà cho bé%'
    )
    ORDER BY FIELD(r.title, 
        'Cách làm cơm chiên gà xối mỡ ngon đậm đà thách thức vị giác',
        'Cách chế biến món thịt bò xào tỏi ngon ngất ngây',
        'Đổi khẩu vị với món nộm thịt bò hành tây thơm ngon kiểu Thái',
        'Cách nấu súp gà cho bé đơn giản thơm ngon tại nhà'
    )
    LIMIT 4
");
$menuSuggestDishes = $stmtMenuSuggest ? $stmtMenuSuggest->fetchAll() : [];

// 2. Curated Dishes for "Kinh nghiệm hay trong ngày" (Image 4)
$stmtTipsDishes = db()->query("
    SELECT r.*, u.username AS author_name 
    FROM recipes r
    JOIN users u ON u.id = r.author_id
    WHERE r.status = 'approved' AND (
        r.title LIKE '%sườn non kho trứng cút%' OR
        r.title LIKE '%gà hấp hành%' OR
        r.title LIKE '%miến xào lòng gà%' OR
        r.title LIKE '%lòng heo xào cải chua%'
    )
    ORDER BY FIELD(r.title, 
        'Sườn non kho trứng cút thơm ngon đậm vị lại cực kỳ đưa cơm',
        'Cách làm gà hấp hành thơm ngon vừa thổi vừa ăn',
        'Cách làm miến xào lòng gà - Món ăn truyền thống Việt Nam',
        'Tuyệt chiêu làm lòng heo xào cải chua thơm ngon hấp dẫn'
    )
    LIMIT 4
");
$tipsDishes = $stmtTipsDishes ? $stmtTipsDishes->fetchAll() : [];

// 3. Curated Dishes for "Công thức mới nhất" (Image 5)
$stmtLatestList = db()->query("
    SELECT r.*, u.username AS author_name 
    FROM recipes r
    JOIN users u ON u.id = r.author_id
    WHERE r.status = 'approved'
    ORDER BY r.created_at DESC, r.id DESC
    LIMIT 5
");
$latestDishes = $stmtLatestList ? $stmtLatestList->fetchAll() : [];

// 4. Ingredients for "Khám phá theo sở thích" (Image 3)
$ingredientsList = [
    ['name' => 'Bắp cải', 'q' => 'bắp cải', 'img' => 'assets/images/ingredients/bap-cai.jpg'],
    ['name' => 'Bò', 'q' => 'bò', 'img' => 'assets/images/ingredients/thit-bo.jpg'],
    ['name' => 'Bông cải', 'q' => 'bông cải', 'img' => 'assets/images/ingredients/bong-cai.jpg'],
    ['name' => 'Cá', 'q' => 'cá', 'img' => 'assets/images/ingredients/ca-hoi.jpg'],
    ['name' => 'Cà chua', 'q' => 'cà chua', 'img' => 'assets/images/ingredients/ca-chua.jpg'],
    ['name' => 'Các loại ốc', 'q' => 'ốc', 'img' => 'assets/images/ingredients/oc.jpg'],
    ['name' => 'Cua', 'q' => 'cua', 'img' => 'assets/images/ingredients/cua.jpg'],
    ['name' => 'Đậu hũ', 'q' => 'đậu', 'img' => 'assets/images/ingredients/dau-hu.jpg'],
    ['name' => 'Dứa', 'q' => 'dứa', 'img' => 'assets/images/ingredients/dua.jpg'],
    ['name' => 'Dưa chua', 'q' => 'dưa', 'img' => 'assets/images/ingredients/dua-chua.jpg'],
    ['name' => 'Tôm', 'q' => 'tôm', 'img' => 'assets/images/ingredients/tom.jpg'],
    ['name' => 'Gà', 'q' => 'gà', 'img' => 'assets/images/ingredients/thit-ga.jpg'],
];

$pageTitle = 'Cookio - Nấu ngon mỗi ngày, truyền cảm hứng bếp Việt';
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

<?php
// Only show the homepage hero and curated sections when there's no search query or category filter
$isDefaultHome = ($searchQuery === '' && $selectedCategory === '' && $filterTime === '' && $filterCal === '' && $filterDiet === '');
?>

<?php if ($isDefaultHome): ?>
    <!-- =====================================================================
         SECTION 1: HERO SUNSHINE BANNER & COVERFLOW 3D CAROUSEL (IMAGE 1)
         ===================================================================== -->
    <section class="yummy-hero-section">
        <div class="yummy-hero-container">
            <h1 class="yummy-hero-title">Hôm nay bạn muốn nấu gì?</h1>
            <p class="yummy-hero-sub">Biến nguyên liệu bình thường thành những trải nghiệm ẩm thực phi thường</p>

            <!-- 3D Coverflow Real Dishes Slider -->
            <div class="coverflow-wrapper">
                <button type="button" class="coverflow-arrow arrow-left" id="btnCoverflowPrev" aria-label="Món trước">&lsaquo;</button>
                
                <div class="coverflow-track" id="coverflowTrack">
                    <!-- Slide 1: Cơm chiên gà xối mỡ (ID 25) -->
                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=25" class="coverflow-slide" data-index="0" title="Cơm chiên gà xối mỡ">
                        <img src="<?= BASE_URL ?>/assets/images/real_dishes/hero-dish-1.jpg" alt="Cơm chiên gà xối mỡ giòn rụm" loading="eager">
                        <span class="coverflow-caption">🍗 Cơm chiên gà xối mỡ</span>
                    </a>
                    <!-- Slide 2: Sườn non kho trứng cút (ID 29) -->
                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=29" class="coverflow-slide" data-index="1" title="Sườn non kho trứng cút">
                        <img src="<?= BASE_URL ?>/assets/images/real_dishes/hero-dish-2.jpg" alt="Sườn non kho trứng cút" loading="lazy">
                        <span class="coverflow-caption">🍲 Sườn non kho trứng cút</span>
                    </a>
                    <!-- Slide 3: Nộm thịt bò hành tây sốt Thái (ID 27) -->
                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=27" class="coverflow-slide is-active" data-index="2" title="Nộm bò Thái Lan">
                        <img src="<?= BASE_URL ?>/assets/images/real_dishes/hero-dish-3.jpg" alt="Nộm bò hành tây Thái" loading="lazy">
                        <span class="coverflow-caption">🥗 Nộm bò kiểu Thái</span>
                    </a>
                    <!-- Slide 4: Thịt bò xào tỏi (ID 26) -->
                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=26" class="coverflow-slide" data-index="3" title="Thịt bò xào tỏi">
                        <img src="<?= BASE_URL ?>/assets/images/real_dishes/hero-dish-4.jpg" alt="Thịt bò xào tỏi" loading="lazy">
                        <span class="coverflow-caption">🥩 Thịt bò xào tỏi</span>
                    </a>
                    <!-- Slide 5: Súp gà cho bé (ID 28) -->
                    <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=28" class="coverflow-slide" data-index="4" title="Súp gà cho bé">
                        <img src="<?= BASE_URL ?>/assets/images/real_dishes/hero-dish-5.jpg" alt="Súp gà cho bé" loading="lazy">
                        <span class="coverflow-caption">🥣 Súp gà ngô ngọt</span>
                    </a>
                </div>

                <button type="button" class="coverflow-arrow arrow-right" id="btnCoverflowNext" aria-label="Món tiếp theo">&rsaquo;</button>
            </div>
        </div>
    </section>

    <!-- =====================================================================
         SECTION 2: GỢI Ý THỰC ĐƠN HÔM NAY (IMAGE 2)
         ===================================================================== -->
    <section class="yummy-curated-section section-menu-suggest">
        <div class="organic-blob-yellow"></div>
        <div class="section-container">
            <div class="yummy-section-header">
                <h2 class="yummy-sec-title">Gợi ý thực đơn hôm nay</h2>
                <p class="yummy-sec-sub">Đánh bay nỗi lo "Trưa nay ăn gì, tối nay nấu chi" chỉ trong 1 nốt nhạc</p>
            </div>

            <div class="yummy-cards-grid">
                <?php foreach ($menuSuggestDishes as $dish): ?>
                    <?php
                    $img = !empty($dish['image_url']) ? BASE_URL . '/' . e($dish['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                    $badgeTag = match($dish['category']) {
                        'Món chiên' => 'Chiên',
                        'Món xào' => (str_contains($dish['title'], 'nộm') ? 'Gỏi/nộm' : 'Xào'),
                        'Món canh' => 'Canh/súp',
                        default => 'Món ngon'
                    };
                    ?>
                    <article class="yummy-card">
                        <div class="yummy-card-thumb-wrap">
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $dish['id'] ?>">
                                <img src="<?= $img ?>" alt="<?= e($dish['title']) ?>" class="yummy-card-img" loading="lazy">
                            </a>
                            <span class="yummy-badge-pill"><?= $badgeTag ?></span>
                        </div>
                        <div class="yummy-card-body">
                            <h3 class="yummy-card-title">
                                <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $dish['id'] ?>">
                                    <?= e($dish['title']) ?>
                                </a>
                            </h3>
                            <div class="yummy-card-meta">
                                <span>⏱ <?= e($dish['cooking_time']) ?></span>
                                <span class="meta-sep">|</span>
                                <span>🎯 Trung bình</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =====================================================================
         SECTION 3: KHÁM PHÁ THEO SỞ THÍCH - DẢI TRÒN NGUYÊN LIỆU (IMAGE 3)
         ===================================================================== -->
    <section class="yummy-ingredients-section">
        <div class="organic-blob-brown"></div>
        <div class="section-container">
            <div class="yummy-section-header text-center">
                <h2 class="yummy-sec-title">Khám phá theo sở thích</h2>
                <p class="yummy-sec-sub">Từ nguyên liệu tươi ngon đến phong cách sống lành mạnh</p>
            </div>

            <div class="ingredients-slider-wrap">
                <button type="button" class="ing-slider-arrow ing-prev" id="btnIngPrev" aria-label="Trước">&lsaquo;</button>
                
                <div class="ingredients-track" id="ingredientsTrack">
                    <?php foreach ($ingredientsList as $ing): ?>
                        <a href="<?= BASE_URL ?>/index.php?q=<?= urlencode($ing['q']) ?>" class="ing-circle-item">
                            <div class="ing-circle-img-wrap">
                                <img src="<?= BASE_URL ?>/<?= $ing['img'] ?>" alt="<?= e($ing['name']) ?>" class="ing-circle-img" loading="lazy">
                            </div>
                            <span class="ing-circle-name"><?= e($ing['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="ing-slider-arrow ing-next" id="btnIngNext" aria-label="Sau">&rsaquo;</button>
            </div>
        </div>
    </section>

    <!-- =====================================================================
         SECTION 4: KINH NGHIỆM HAY TRONG NGÀY (IMAGE 4)
         ===================================================================== -->
    <section class="yummy-curated-section section-tips-daily">
        <div class="organic-blob-yellow-left"></div>
        <div class="section-container">
            <div class="yummy-section-header flex-between">
                <div>
                    <h2 class="yummy-sec-title">Kinh nghiệm hay trong ngày</h2>
                    <p class="yummy-sec-sub">Mỗi ngày một mẹo nhỏ, giúp việc nội trợ thêm phần nhẹ tênh</p>
                </div>
                <a href="<?= BASE_URL ?>/views/kitchen-tips.php" class="yummy-btn-more">
                    Xem thêm &rarr;
                </a>
            </div>

            <div class="yummy-cards-grid">
                <?php foreach ($tipsDishes as $dish): ?>
                    <?php
                    $img = !empty($dish['image_url']) ? BASE_URL . '/' . e($dish['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                    $badgeTag = match($dish['category']) {
                        'Món kho' => 'Kho',
                        'Món hấp' => 'Hấp',
                        'Món xào' => 'Xào',
                        default => 'Mẹo hay'
                    };
                    ?>
                    <article class="yummy-card">
                        <div class="yummy-card-thumb-wrap">
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $dish['id'] ?>">
                                <img src="<?= $img ?>" alt="<?= e($dish['title']) ?>" class="yummy-card-img" loading="lazy">
                            </a>
                            <span class="yummy-badge-pill"><?= $badgeTag ?></span>
                        </div>
                        <div class="yummy-card-body">
                            <h3 class="yummy-card-title">
                                <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $dish['id'] ?>">
                                    <?= e($dish['title']) ?>
                                </a>
                            </h3>
                            <div class="yummy-card-meta">
                                <span>⏱ <?= e($dish['cooking_time']) ?></span>
                                <span class="meta-sep">|</span>
                                <span>🎯 Trung bình</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =====================================================================
         SECTION 5: CÔNG THỨC MỚI NHẤT (RÚT GỌN 2 CỘT: ẢNH ĐẠI DIỆN & XEM THÊM)
         ===================================================================== -->
    <section class="latest-recipes-compact-section section-container" style="margin-top: 2.5rem; margin-bottom: 3.5rem;">
        <div class="yummy-section-header flex-between" style="margin-bottom: 1.5rem;">
            <div>
                <h2 class="yummy-sec-title">Công thức mới nhất</h2>
                <p class="yummy-sec-sub">Khám phá những món ngon vừa ra lò được yêu thích nhất</p>
            </div>
        </div>

        <?php
        $featuredCount = 4;
        $featuredLatest = array_slice($recipes, 0, $featuredCount);
        $sidebarMoreDishes = array_slice($recipes, $featuredCount, 6);
        ?>

        <div class="latest-compact-layout">
            <!-- Cột trái: Vài món có hình ảnh đại diện nổi bật -->
            <div class="latest-featured-grid">
                <?php foreach ($featuredLatest as $dish): ?>
                    <?php
                    $img = !empty($dish['image_url']) ? BASE_URL . '/' . e($dish['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                    $badgeTag = match($dish['category'] ?? '') {
                        'Món chiên' => 'Chiên',
                        'Món kho' => 'Kho',
                        'Món hấp' => 'Hấp',
                        'Món canh' => 'Canh',
                        default => 'Xào'
                    };
                    ?>
                    <article class="yummy-card">
                        <div class="yummy-card-thumb-wrap">
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $dish['id'] ?>">
                                <img src="<?= $img ?>" alt="<?= e($dish['title']) ?>" class="yummy-card-img" loading="lazy">
                            </a>
                            <span class="yummy-badge-pill"><?= $badgeTag ?></span>
                            
                            <!-- Quick Like Button -->
                            <button type="button" class="yummy-quick-like-btn js-like-btn" data-recipe-id="<?= (int) $dish['id'] ?>" title="Thả tim">
                                ❤️ <span class="like-count"><?= (int)($dish['likes_count'] ?? 0) ?></span>
                            </button>
                        </div>
                        <div class="yummy-card-body">
                            <h3 class="yummy-card-title">
                                <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $dish['id'] ?>">
                                    <?= e($dish['title']) ?>
                                </a>
                            </h3>
                            <div class="yummy-card-meta">
                                <span>⏱ <?= e($dish['cooking_time']) ?></span>
                                <span class="meta-sep">|</span>
                                <span>🎯 Trung bình</span>
                                <span class="meta-sep">|</span>
                                <span>⭐ <?= $dish['avg_rating'] ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Cột phải: Mục Xem thêm bao gồm các món ăn -->
            <aside class="latest-sidebar-more">
                <div class="sidebar-more-box">
                    <div class="sidebar-more-header">
                        <h3 class="sidebar-more-title">Xem thêm món ăn</h3>
                        <span class="sidebar-more-count"><?= count($recipes) ?> món</span>
                    </div>
                    <div class="sidebar-dishes-list">
                        <?php foreach ($sidebarMoreDishes as $sDish): ?>
                            <?php
                            $sImg = !empty($sDish['image_url']) ? BASE_URL . '/' . e($sDish['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                            ?>
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $sDish['id'] ?>" class="sidebar-dish-item">
                                <img src="<?= $sImg ?>" alt="<?= e($sDish['title']) ?>" class="sidebar-dish-thumb" loading="lazy">
                                <div class="sidebar-dish-info">
                                    <h4 class="sidebar-dish-name"><?= e($sDish['title']) ?></h4>
                                    <div class="sidebar-dish-meta">
                                        <span class="sidebar-dish-cat"><?= e($sDish['category']) ?></span>
                                        <span>⏱ <?= e($sDish['cooking_time']) ?></span>
                                    </div>
                                </div>
                                <span class="sidebar-dish-arrow">&rsaquo;</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="sidebar-more-footer">
                        <button type="button" onclick="openAllRecipesMenuModal()" class="btn-sidebar-view-all" style="width: 100%; border: none; cursor: pointer; background: transparent; font-family: inherit; font-size: inherit; font-weight: inherit; color: inherit; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                            📋 Xem tất cả công thức món ăn &rarr;
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </section>
<?php else: ?>
    <!-- KHI TÌM KIẾM HOẶC LỌC DANH MỤC: HIỂN THỊ ĐẦY ĐỦ KẾT QUẢ VÀ BỘ LỌC -->
    <section class="recipe-section section-container" style="margin-top: 2rem; margin-bottom: 3rem;">
        <div class="section-heading" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem;">
            <div>
                <h2 style="margin:0; font-size: 1.5rem; font-weight: 800; color: #3e1f17;">
                    Kết quả tìm kiếm
                    <?php if ($selectedCategory !== ''): ?> &bull; Danh mục "<em><?= e($selectedCategory) ?></em>"<?php endif; ?>
                    <?php if ($searchQuery !== ''): ?> &bull; Từ khóa "<em><?= e($searchQuery) ?></em>"<?php endif; ?> 
                    <span style="font-size: 1.1rem; color: #6b7280; font-weight: 600;">(<?= count($recipes) ?> món)</span>
                </h2>
                <a href="<?= BASE_URL ?>/index.php" class="link-button" style="display:inline-block; margin-top:0.25rem; color: #ea580c; font-weight: 700;">&times; Xóa toàn bộ bộ lọc</a>
            </div>

            <!-- Sorting Tabs -->
            <div style="display:flex; align-items:center; gap:0.5rem; background:#fff; padding:0.25rem; border-radius:9999px; border:1px solid #fed7aa;">
                <span style="font-size:0.82rem; font-weight:700; color:#9a3412; padding-left:0.65rem;">Sắp xếp:</span>
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
                        style="font-size:0.84rem; font-weight:700; text-decoration:none; padding:0.35rem 0.75rem; border-radius:9999px; transition:all 0.15s; <?= $isSortActive ? 'background:#ea580c; color:#fff;' : 'color:#3e1f17;' ?>">
                        <?= $sLabel ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Category Filter Pills -->
        <div class="category-pills-container" style="margin-bottom: 1.25rem;">
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

        <!-- All Recipes Grid -->
        <div class="yummy-cards-grid">
            <?php foreach ($recipes as $recipe): ?>
                <?php 
                $imgSrc = !empty($recipe['image_url']) ? BASE_URL . '/' . e($recipe['image_url']) : BASE_URL . '/assets/images/default-recipe.jpg';
                $badgeTag = match($recipe['category'] ?? '') {
                    'Món chiên' => 'Chiên',
                    'Món kho' => 'Kho',
                    'Món hấp' => 'Hấp',
                    'Món canh' => 'Canh',
                    default => 'Xào'
                };
                ?>
                <article class="yummy-card">
                    <div class="yummy-card-thumb-wrap">
                        <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                            <img src="<?= $imgSrc ?>" alt="<?= e($recipe['title']) ?>" class="yummy-card-img" loading="lazy">
                        </a>
                        <span class="yummy-badge-pill"><?= $badgeTag ?></span>
                        
                        <!-- Quick Like Button -->
                        <button type="button" class="yummy-quick-like-btn js-like-btn" data-recipe-id="<?= (int) $recipe['id'] ?>" title="Thả tim">
                            ❤️ <span class="like-count"><?= (int)($recipe['likes_count'] ?? 0) ?></span>
                        </button>
                    </div>
                    <div class="yummy-card-body">
                        <h3 class="yummy-card-title">
                            <a href="<?= BASE_URL ?>/views/recipe-detail.php?id=<?= (int) $recipe['id'] ?>">
                                <?= e($recipe['title']) ?>
                            </a>
                        </h3>
                        <div class="yummy-card-meta">
                            <span>⏱ <?= e($recipe['cooking_time']) ?></span>
                            <span class="meta-sep">|</span>
                            <span>🎯 Trung bình</span>
                            <span class="meta-sep">|</span>
                            <span>⭐ <?= $recipe['avg_rating'] ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Floating Back to Top Button (Images 2, 3, 4, 5) -->
<button type="button" class="btn-back-to-top" id="btnBackToTop" aria-label="Cuộn lên đầu trang" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
    ↑
</button>

<?php 
require_once __DIR__ . '/includes/modal-all-recipes.php';
require __DIR__ . '/includes/footer.php'; 
?>