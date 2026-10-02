<?php

declare(strict_types=1);

$pageTitle = 'Kinh Nghiệm Hay & Bí Quyết Nấu Ngon - Cookio';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// Featured tips (Image 2)
$featuredTips = [
    [
        'id' => 1,
        'title' => '5 Cách ướp gà nướng cực thơm ngon và đậm đà hương vị',
        'image' => 'assets/images/real_dishes/hero-dish-1.jpg',
        'time' => '25p',
        'difficulty' => 'Dễ',
        'summary' => 'Bí quyết ướp gà muối ớt, sa tế, mật ong nướng vàng óng, da giòn rụm bên trong mọng nước.',
        'content' => "1. **Gà nướng muối ớt:** 1 con gà (1.5kg) + 2 thìa muối hạt giã nhuyễn cùng 4 quả ớt hiểm + 1 thìa nước mắm ngon + 1/2 thìa ngũ vị hương. Xoa bóp đều trong 30 phút.\n2. **Gà nướng mật ong:** Pha 2 thìa mật ong nguyên chất + 1 thìa dầu hào + 1 thìa nước tương + 1 thìa tỏi băm. Lưu ý phết mật ong vào 10 phút cuối để tránh bị cháy khét.\n3. **Gà nướng tiêu xanh:** Tiêu xanh đập dập thơm nức kết hợp hành tím phi thơm và sốt mayonnaise tạo độ béo mọng.\n4. **Gà nướng sốt BBQ cay ngọt:** Thích hợp nướng bằng nồi chiên không dầu ở nhiệt độ 180°C trong 25 phút.\n5. **Gà nướng sốt bơ tỏi thảo mộc:** Phết đều bơ lạt đun chảy với tỏi băm và lá hương thảo lên da gà.",
        'recipe_link' => BASE_URL . '/views/recipe-detail.php?id=25'
    ],
    [
        'id' => 2,
        'title' => '2 cách ướp sườn cốt lết nướng đặc biệt thơm ngon',
        'image' => 'assets/images/real_dishes/hero-dish-2.jpg',
        'time' => '30p',
        'difficulty' => 'Dễ',
        'summary' => 'Công thức ướp sườn mềm tan không bị khô cứng bằng nước ép táo và sữa đặc.',
        'content' => "1. **Cách 1 - Ướp cốt lết cơm tấm truyền thống:** Dần nhẹ thớ thịt cốt lết. Ướp với: 1 thìa canh sữa đặc Ông Thọ + 1 thìa mật ong + 2 thìa nước mắm + gốc hành lá giã nhuyễn + 1 thìa dầu hào. Lớp sữa đặc giúp thớ thịt giữ ẩm mềm mọng như ngoài hàng.\n2. **Cách 2 - Ướp cốt lết sốt hoa quả (lê hoặc táo):** Nước ép 1/2 quả táo tươi chứa enzyme tự nhiên phân giải protein, giúp sườn mềm tan chỉ sau 20 phút ướp mà không cần bột ngọt hay chất làm mềm.",
        'recipe_link' => BASE_URL . '/views/recipe-detail.php?id=29'
    ],
    [
        'id' => 3,
        'title' => 'Cách hầm gân bò nhanh mềm siêu cấp dễ dàng cho chị em',
        'image' => 'assets/images/real_dishes/hero-dish-4.jpg',
        'time' => '45p',
        'difficulty' => 'Trung bình',
        'summary' => 'Mẹo cho đá lạnh sốc nhiệt hoặc vài lát dứa/chanh giúp gân bò mềm giòn sần sật trong nửa thời gian.',
        'content' => "1. **Sơ chế khử hôi:** Gân bò bóp kỹ với muối hạt, gừng nướng đập dập và rượu trắng. Luộc sơ qua nước sôi 3 phút rồi vớt ra xả nước lạnh.\n2. **Mẹo sốc nhiệt:** Khi gân bò đang ninh sôi sùng sục, thả vào nồi 4-5 viên đá lạnh. Hiện tượng co giãn nhiệt đột ngột làm các thớ collagen trong gân nở bung nhanh gấp đôi.\n3. **Dùng dứa tươi hoặc túi trà xanh:** Thả 2 lát dứa tươi vào nồi ninh, axit bromelain trong dứa giúp gân bò mềm ngậy mà nước dùng lại trong vắt, thơm ngọt thanh.",
        'recipe_link' => BASE_URL . '/views/recipe-detail.php?id=26'
    ],
    [
        'id' => 4,
        'title' => 'Cách ướp thịt ba chỉ nướng ngon đặc biệt ai cũng mê',
        'image' => 'assets/images/real_dishes/hero-dish-3.jpg',
        'time' => '20p',
        'difficulty' => 'Dễ',
        'summary' => 'Bí quyết ướp thịt ba chỉ riềng mẻ hoặc sốt sa tế hành tỏi thơm lừng.',
        'content' => "1. **Chọn thịt ba chỉ chuẩn:** Chọn thịt ba chỉ rút sườn, có tỷ lệ nạc mỡ đan xen đều đặn 7:3 để khi nướng mỡ chảy ra thơm ngậy mà không ngấy.\n2. **Sốt ướp đặc biệt:** 1 thìa sa tế tôm + 1 thìa dầu hào + 1 thìa sốt mayonnaise (giúp thịt bóng mềm) + 1 củ hành tím + 2 tép tỏi băm nhuyễn.\n3. **Khóa ẩm bằng dầu ăn:** Luôn trộn 1 thìa dầu ăn ở bước cuối cùng sau khi gia vị đã ngấm để thịt không bị khô cháy bề mặt.",
        'recipe_link' => BASE_URL . '/views/recipe-detail.php?id=27'
    ],
    [
        'id' => 5,
        'title' => 'Cách làm thịt heo quay giòn bì tại nhà ngon bất bại',
        'image' => 'assets/images/real_dishes/thit-chien-xu.jpg',
        'time' => '40p',
        'difficulty' => 'Trung bình',
        'summary' => 'Bí quyết xăm đều mặt bì và quét hỗn hợp giấm + muối hạt để bì nổ cốm giòn tan rôm rốp.',
        'content' => "1. **Luộc sơ phần bì:** Đặt úp miếng thịt có phần bì xuống đáy chảo nước sôi luộc 5 phút cho bì săn lại.\n2. **Xăm bì:** Dùng dĩa hoặc tăm nhọn xăm thật dày lên mặt bì (chú ý không xăm sâu chạm vào lớp mỡ). Dùng khăn giấy lau sạch dầu mỡ rỉ ra.\n3. **Quét giấm muối:** Hòa 1 thìa giấm gạo + 1/2 thìa muối tinh, quét đều lên mặt bì rồi để quạt sấy khô 30 phút trước khi nướng. Bì sẽ nổ bung giòn rụm như ngoài tiệm!",
        'recipe_link' => BASE_URL . '/views/recipe-detail.php?id=34'
    ],
    [
        'id' => 6,
        'title' => 'Bí quyết làm chân gà sốt Thái chua cay đậm đà chuẩn vị',
        'image' => 'assets/images/real_dishes/nom-bo-thai.jpg',
        'time' => '35p',
        'difficulty' => 'Dễ',
        'summary' => 'Công thức sốt Thái chua ngọt sánh sệt, chân gà giòn sần sật ngấm đẫm sả ớt cóc non.',
        'content' => "1. **Luộc chân gà giòn:** Luộc chân gà cùng sả đập dập, gừng và 1 thìa rượu trắng trong 7-10 phút. Vớt ra ngâm ngay vào âu nước đá lạnh 20 phút rồi để tủ đông 30 phút cho giòn đanh.\n2. **Nước sốt Thái thần thánh:** Nấu sôi: 1 bát nước mắm ngon + 1 bát đường thốt nốt + 1 bát nước cốt me chua + 1/2 bát tương ớt + 2 thìa ớt bột Hàn Quốc. Khuấy đều đến khi sốt sánh sệt rồi để thật nguội.\n3. **Trộn ngấm vị:** Trộn chân gà với sả thái vát, tắc thái lát bỏ hạt, ớt sừng, xoài hoặc cóc bao tử rồi rưới sốt, để ngăn mát 2-4 tiếng trước khi ăn.",
        'recipe_link' => BASE_URL . '/views/recipe-detail.php?id=27'
    ],
    [
        'id' => 7,
        'title' => 'Cách làm các món ăn vặt ngon tuyệt đỉnh',
        'image' => 'assets/images/real_dishes/mien-xao-long-ga.jpg',
        'time' => '15p',
        'difficulty' => 'Dễ',
        'summary' => 'Bộ sưu tập công thức đồ ăn vặt nhanh gọn, giòn rụm dễ làm bằng nguyên liệu sẵn có.',
        'content' => "1. **Bánh tráng nướng Đà Lạt:** Quét bơ lạt lên bánh tráng, đập 1 quả trứng cút, rải hành hoa, tép khô và xúc xích rồi nướng trên chảo chống dính lửa nhỏ.\n2. **Khoai lang lắc phô mai:** Cắt khoai lang con chì, ngâm nước muối loãng rồi áo 1 lớp mỏng bột bắp chiên giòn, sau đó lắc đều cùng bột phô mai béo ngậy.\n3. **Bắp xào bơ tôm khô:** Xào bơ thơm với tép khô, cho bắp ngọt luộc chín vào đảo nhanh tay cùng hành lá và tương ớt.",
        'recipe_link' => BASE_URL . '/views/recipe-detail.php?id=31'
    ],
    [
        'id' => 8,
        'title' => 'Mẹo ủ thịt bò bít tết chuẩn vị nhà hàng Âu',
        'image' => 'assets/images/real_dishes/bo-xao-toi.jpg',
        'time' => '20p',
        'difficulty' => 'Dễ',
        'summary' => 'Nhiệt độ phòng trước khi áp chảo, rắc muối tiêu thô và kỹ thuật rưới bơ thảo mộc (basting).',
        'content' => "1. **Đưa thịt về nhiệt độ phòng:** Lấy miếng thăn bò ra khỏi tủ lạnh 30 phút trước khi áp chảo. Thấm thật khô hai mặt bằng khăn giấy bếp.\n2. **Chảo thật nóng:** Dùng chảo gang dày, đun đến khi chảo bốc khói nhẹ rồi mới cho dầu ăn có điểm khói cao vào.\n3. **Kỹ thuật Basting:** Sau khi lật mặt thịt, cho 1 viên bơ lạt, 2 tép tỏi đập dập và cành lá rosemary vào chảo. Nghiêng chảo dùng thìa múc bơ nóng liên tục rưới lên mặt thịt.\n4. **Nghỉ thịt (Resting):** Bắt buộc để miếng thịt nghỉ trên đĩa ấm 5 phút trước khi cắt để nước ngọt tái phân bổ đều khắp thớ thịt.",
        'recipe_link' => BASE_URL . '/views/recipe-detail.php?id=26'
    ]
];

// Ingredients for circle carousel (Image 1)
$ingredients = [
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

// List of all tips (Image 3)
$allTipsList = [
    [
        'id' => 1,
        'title' => '5 Cách ướp gà nướng cực thơm ngon và đậm đà hương vị',
        'desc' => '5 Cách ướp gà nướng cực thơm ngon và đậm đà hương vị — công thức Cookio.',
        'image' => 'assets/images/real_dishes/hero-dish-1.jpg',
        'time' => '25p',
        'difficulty' => 'Dễ'
    ],
    [
        'id' => 2,
        'title' => '2 cách ướp sườn cốt lết nướng đặc biệt thơm ngon',
        'desc' => '2 cách ướp sườn cốt lết nướng đặc biệt thơm ngon — công thức Cookio.',
        'image' => 'assets/images/real_dishes/hero-dish-2.jpg',
        'time' => '30p',
        'difficulty' => 'Dễ'
    ],
    [
        'id' => 3,
        'title' => 'Cách hầm gân bò nhanh mềm siêu cấp dễ dàng cho chị em',
        'desc' => 'Cách hầm gân bò nhanh mềm siêu cấp dễ dàng cho chị em — công thức Cookio.',
        'image' => 'assets/images/real_dishes/hero-dish-4.jpg',
        'time' => '45p',
        'difficulty' => 'Trung bình'
    ],
    [
        'id' => 4,
        'title' => 'Cách ướp thịt ba chỉ nướng ngon đặc biệt ai cũng mê',
        'desc' => 'Cách ướp thịt ba chỉ nướng ngon đặc biệt ai cũng mê — công thức Cookio.',
        'image' => 'assets/images/real_dishes/hero-dish-3.jpg',
        'time' => '20p',
        'difficulty' => 'Dễ'
    ],
    [
        'id' => 5,
        'title' => 'Cách làm thịt heo quay giòn bì tại nhà ngon bất bại',
        'desc' => 'Bí quyết xăm bì và quét giấm nổ cốm giòn rụm — công thức Cookio.',
        'image' => 'assets/images/real_dishes/thit-chien-xu.jpg',
        'time' => '40p',
        'difficulty' => 'Trung bình'
    ],
    [
        'id' => 6,
        'title' => 'Bí quyết làm chân gà sốt Thái chua cay đậm đà chuẩn vị',
        'desc' => 'Sốt Thái đậm đà quyện tắc sả ớt thấm đều chân gà — công thức Cookio.',
        'image' => 'assets/images/real_dishes/nom-bo-thai.jpg',
        'time' => '35p',
        'difficulty' => 'Dễ'
    ]
];
?>

<main class="container py-8" style="max-width: 1200px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-6" style="font-size: 0.9rem; color: #6b7280; display: flex; gap: 0.5rem; align-items: center;">
        <a href="<?= BASE_URL ?>/index.php" style="color: #ea580c; text-decoration: none; font-weight: 600;">Trang chủ</a>
        <span>&rsaquo;</span>
        <span style="font-weight: 700; color: #374151;">Kinh nghiệm hay</span>
    </nav>

    <!-- =====================================================================
         IMAGE 1: TOP BROWN BANNER & INGREDIENT EXPLORER SLIDER
         ===================================================================== -->
    <section class="tips-hero-banner" style="background: #3e1713; border-radius: 16px; padding: 2rem 2.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; margin-bottom: 3.5rem; box-shadow: 0 10px 25px rgba(62,23,19,0.15); overflow: hidden; position: relative;">
        <div style="z-index: 2; max-width: 750px;">
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #ffffff; margin: 0 0 0.65rem; line-height: 1.35; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <span>Tổng hợp mẹo hay cuộc sống bạn nhất định không nên bỏ lỡ</span>
            </h1>
            <p style="font-size: 1.05rem; font-weight: 600; color: #f59e0b; margin: 0;">
                Chuyên mục này có 51 bài.
            </p>
        </div>
        <div style="z-index: 2; flex-shrink: 0; text-align: right;">
            <!-- Elegant culinary badge replacing cartoon chef -->
            <div style="width: 88px; height: 88px; border-radius: 50%; background: linear-gradient(135deg, rgba(245,158,11,0.25) 0%, rgba(245,158,11,0.05) 100%); border: 2px solid rgba(245,158,11,0.4); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(0,0,0,0.25);">
                <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                    <line x1="6" y1="17" x2="18" y2="17"/>
                </svg>
            </div>
        </div>
    </section>

    <!-- INGREDIENT EXPLORER (Image 1 bottom) -->
    <section class="ingredient-explorer mb-12" style="margin-bottom: 3.5rem;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.75rem; font-weight: 800; color: #3e1713; margin: 0 0 0.35rem;">
                Khám phá thêm nguyên liệu khác
            </h2>
            <p style="font-size: 0.95rem; color: #78350f; margin: 0; font-weight: 500;">
                Bò, gà, heo, cá, tôm, mực, vịt, ốc
            </p>
        </div>

        <div class="ingredient-slider-wrap" style="position: relative; display: flex; align-items: center; gap: 0.75rem;">
            <button type="button" class="ing-nav-btn ing-prev" id="btnIngPrev" aria-label="Trước" style="width: 42px; height: 42px; border-radius: 50%; border: 1.5px solid #d1d5db; background: #ffffff; color: #374151; font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; flex-shrink: 0;">
                &lsaquo;
            </button>

            <div class="ingredient-scroll-track" id="ingScrollTrack" style="display: flex; gap: 1.5rem; overflow-x: auto; scroll-behavior: smooth; padding: 0.75rem 0.25rem; scrollbar-width: none; -ms-overflow-style: none;">
                <?php foreach ($ingredients as $ing): ?>
                    <a href="<?= BASE_URL ?>/index.php?q=<?= urlencode($ing['q']) ?>" class="ing-circle-item" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; min-width: 90px; text-align: center; group">
                        <div style="width: 86px; height: 86px; border-radius: 50%; overflow: hidden; border: 2.5px solid #fed7aa; box-shadow: 0 4px 10px rgba(0,0,0,0.08); transition: transform 0.25s, border-color 0.25s;">
                            <img src="<?= BASE_URL ?>/<?= $ing['img'] ?>" alt="<?= e($ing['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">
                        </div>
                        <span style="margin-top: 0.65rem; font-size: 0.92rem; font-weight: 700; color: #3e1713; white-space: nowrap;">
                            <?= e($ing['name']) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <button type="button" class="ing-nav-btn ing-next" id="btnIngNext" aria-label="Sau" style="width: 42px; height: 42px; border-radius: 50%; border: 1.5px solid #d1d5db; background: #ffffff; color: #374151; font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; flex-shrink: 0;">
                &rsaquo;
            </button>
        </div>
    </section>

    <!-- =====================================================================
         IMAGE 2: CÁC MÓN TIÊU BIỂU (8 CARDS, 2 ROWS OF 4)
         ===================================================================== -->
    <section class="featured-tips-section mb-12" style="margin-bottom: 4rem;">
        <div style="margin-bottom: 1.75rem;">
            <h2 style="font-size: 1.85rem; font-weight: 800; color: #3e1713; margin: 0 0 0.35rem;">
                Các món tiêu biểu
            </h2>
            <p style="font-size: 0.98rem; color: #78350f; margin: 0;">
                Những món kinh nghiệm hay được cộng đồng yêu thích nhất
            </p>
        </div>

        <div class="featured-tips-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
            <?php foreach ($featuredTips as $t): ?>
                <article class="featured-tip-card" style="background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid #f3f4f6; display: flex; flex-direction: column; transition: transform 0.25s, box-shadow 0.25s; cursor: pointer;" onclick="openTipModal(<?= (int)$t['id'] ?>)">
                    <!-- Card Thumbnail with Badge -->
                    <div style="position: relative; width: 100%; aspect-ratio: 1/1; overflow: hidden; background: #f3f4f6;">
                        <img src="<?= BASE_URL ?>/<?= $t['image'] ?>" alt="<?= e($t['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" loading="lazy">
                        <span style="position: absolute; top: 0.75rem; left: 0.75rem; background: rgba(30, 27, 27, 0.78); backdrop-filter: blur(4px); color: #ffffff; font-size: 0.76rem; font-weight: 700; padding: 0.3rem 0.75rem; border-radius: 9999px;">
                            Kinh nghiệm hay
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div style="padding: 1.15rem; display: flex; flex-direction: column; flex: 1; justify-content: space-between;">
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: #3e1713; margin: 0 0 0.75rem; line-height: 1.38; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= e($t['title']) ?>
                        </h3>
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: #9a3412; font-weight: 600;">
                            <span>⏱ <?= e($t['time']) ?></span>
                            <span style="color: #cbd5e1;">|</span>
                            <span><?= e($t['difficulty']) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- =====================================================================
         IMAGE 3: DANH SÁCH TẤT CẢ CÔNG THỨC (HORIZONTAL CARDS)
         ===================================================================== -->
    <section class="all-tips-list-section mb-12" style="margin-bottom: 4rem;">
        <div style="text-align: center; margin-bottom: 2.25rem;">
            <h2 style="font-size: 1.95rem; font-weight: 800; color: #3e1713; margin: 0 0 0.35rem;">
                Danh sách tất cả công thức
            </h2>
            <p style="font-size: 1rem; color: #78350f; margin: 0;">
                Tổng hợp công thức kinh nghiệm hay đầy đủ, dễ làm tại nhà
            </p>
        </div>

        <div class="horizontal-tips-list" style="display: flex; flex-direction: column; gap: 1.25rem; max-width: 900px; margin: 0 auto;">
            <?php foreach ($allTipsList as $item): ?>
                <article class="horizontal-tip-card" style="display: flex; gap: 1.5rem; align-items: center; background: #ffffff; border-radius: 16px; padding: 1rem; border: 1px solid #f3f4f6; box-shadow: 0 3px 12px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onclick="openTipModal(<?= (int)$item['id'] ?>)">
                    <!-- Left Photo -->
                    <div style="position: relative; width: 190px; height: 130px; border-radius: 12px; overflow: hidden; flex-shrink: 0; background: #f3f4f6;">
                        <img src="<?= BASE_URL ?>/<?= $item['image'] ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                        <span style="position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(30, 27, 27, 0.78); backdrop-filter: blur(4px); color: #ffffff; font-size: 0.72rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px;">
                            Kinh nghiệm hay
                        </span>
                    </div>

                    <!-- Right Content -->
                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #3e1713; margin: 0 0 0.5rem; line-height: 1.35;">
                            <?= e($item['title']) ?>
                        </h3>
                        <p style="font-size: 0.92rem; color: #6b7280; margin: 0 0 0.75rem; line-height: 1.45;">
                            <?= e($item['desc']) ?>
                        </p>
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: #9a3412; font-weight: 600;">
                            <span>⏱ <?= e($item['time']) ?></span>
                            <span style="color: #cbd5e1;">|</span>
                            <span><?= e($item['difficulty']) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- =====================================================================
         IMAGE 4: VỀ CHUYÊN MỤC KINH NGHIỆM HAY (DARK TEXT CARD)
         ===================================================================== -->
    <section class="about-tips-dark-card" style="background: #2b2b2b; color: #ffffff; border-radius: 16px; padding: 2.75rem 3rem; margin-top: 3.5rem; box-shadow: 0 8px 30px rgba(0,0,0,0.18);">
        <h2 style="font-size: 1.45rem; font-weight: 800; color: #ffffff; margin: 0 0 1.35rem; letter-spacing: -0.01em;">
            Về chuyên mục Kinh nghiệm hay
        </h2>
        <div style="font-size: 0.98rem; line-height: 1.8; color: #e5e7eb; display: flex; flex-direction: column; gap: 1rem;">
            <p style="margin: 0;">
                Chuyên mục này có 51 bài. Không bài nào là công thức nấu một món cụ thể. Đây là chỗ để những thứ dùng lại được cho nhiều món.
            </p>
            <p style="margin: 0;">
                Phần lớn xoay quanh khâu ướp. Cách ướp chân gà nướng, ướp vịt nướng chao, ướp thịt dê nướng đều là bài riêng vì công thức ướp dùng chung cho hàng chục món nướng khác nhau, tách ra thì không phải lặp lại trong từng bài. Mỗi bài ghi tỷ lệ gia vị theo khối lượng thịt, nên bạn nhân lên hay chia xuống đều được.
            </p>
            <p style="margin: 0;">
                Nhóm còn lại là việc bếp núc. Khử mùi hôi tủ lạnh và cách luộc bánh chưng thuộc nhóm này, cả hai đều viết theo dạng nguyên nhân rồi tới cách xử lý, thay vì đưa một danh sách mẹo rời rạc. Bài về địa điểm ăn healthy tại Sài Gòn là ngoại lệ duy nhất, nói về chỗ ăn chứ không nói về bếp nhà.
            </p>
        </div>
    </section>
</main>

<!-- Interactive Tip Detail Modal -->
<div id="tipDetailModal" class="cookio-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; backdrop-filter: blur(5px); justify-content: center; align-items: center; padding: 1.5rem;">
    <div class="cookio-modal-content" style="background: #ffffff; border-radius: 1.25rem; max-width: 650px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2rem; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <button type="button" onclick="closeTipModal()" style="position: absolute; top: 1.25rem; right: 1.25rem; background: #f3f4f6; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.25rem; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #4b5563;">&times;</button>
        
        <span style="display: inline-block; background: #fff7ed; color: #c2410c; font-size: 0.8rem; font-weight: 800; padding: 0.3rem 0.75rem; border-radius: 9999px; margin-bottom: 0.75rem; border: 1px solid #fed7aa;">
            💡 KINH NGHIỆM ĐẦU BẾP COOKIO
        </span>

        <h2 id="modalTipTitle" style="font-size: 1.45rem; font-weight: 800; color: #3e1713; margin: 0 0 0.85rem; line-height: 1.35;"></h2>

        <div id="modalTipImageWrap" style="width: 100%; height: 260px; border-radius: 14px; overflow: hidden; margin-bottom: 1.25rem; background: #f3f4f6;">
            <img id="modalTipImg" src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <p id="modalTipSummary" style="font-size: 0.95rem; color: #6b7280; font-style: italic; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px dashed #e5e7eb;"></p>

        <h4 style="font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0 0 0.65rem;">
            📌 Các bước & Tỷ lệ gia vị chuẩn:
        </h4>
        <div id="modalTipContent" style="font-size: 0.92rem; line-height: 1.7; color: #374151; white-space: pre-line; background: #f8fafc; padding: 1.25rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;"></div>

        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; flex-wrap: wrap;">
            <a id="modalTipRecipeLink" href="#" class="button button-create" style="padding: 0.65rem 1.25rem; font-weight: 700; text-decoration: none;">
                👩‍🍳 Xem món áp dụng mẹo này
            </a>
            <button type="button" class="button button-outline" onclick="closeTipModal()" style="padding: 0.65rem 1.25rem;">
                Đóng
            </button>
        </div>
    </div>
</div>

<script>
const tipsData = <?= json_encode($featuredTips) ?>;

function openTipModal(id) {
    const tip = tipsData.find(t => t.id === id);
    if (!tip) return;

    document.getElementById('modalTipTitle').textContent = tip.title;
    document.getElementById('modalTipImg').src = '<?= BASE_URL ?>/' + tip.image;
    document.getElementById('modalTipSummary').textContent = tip.summary;
    document.getElementById('modalTipContent').textContent = tip.content;
    document.getElementById('modalTipRecipeLink').href = tip.recipe_link;

    const modal = document.getElementById('tipDetailModal');
    modal.style.display = 'flex';
}

function closeTipModal() {
    document.getElementById('tipDetailModal').style.display = 'none';
}

// Close on backdrop click
document.getElementById('tipDetailModal').addEventListener('click', function(e) {
    if (e.target === this) closeTipModal();
});

// Ingredient slider controls
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('ingScrollTrack');
    const prev = document.getElementById('btnIngPrev');
    const next = document.getElementById('btnIngNext');

    if (track && prev && next) {
        prev.addEventListener('click', () => track.scrollBy({ left: -220, behavior: 'smooth' }));
        next.addEventListener('click', () => track.scrollBy({ left: 220, behavior: 'smooth' }));
    }
});
</script>

<style>
.featured-tip-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(62,23,19,0.12);
}
.featured-tip-card:hover img {
    transform: scale(1.04);
}
.horizontal-tip-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(62,23,19,0.08);
}
.ing-circle-item:hover div {
    transform: scale(1.08);
    border-color: #ea580c !important;
}
.ing-nav-btn:hover {
    background: #ea580c !important;
    color: #ffffff !important;
    border-color: #ea580c !important;
}
@media (max-width: 900px) {
    .featured-tips-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 600px) {
    .featured-tips-grid {
        grid-template-columns: 1fr !important;
    }
    .horizontal-tip-card {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    .horizontal-tip-card div:first-child {
        width: 100% !important;
        height: 180px !important;
    }
    .tips-hero-banner {
        flex-direction: column !important;
        text-align: center !important;
    }
}
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
