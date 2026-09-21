<?php

declare(strict_types=1);

$pageTitle = 'Mẹo Vặt Nhà Bếp & Bí Quyết Nấu Ăn - Cookio';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$tips = [
    [
        'id' => 1,
        'category' => 'chien_xao',
        'category_name' => 'Chiên & Rán',
        'icon' => '🍳',
        'title' => 'Mẹo chiên cá, thịt không bao giờ bắn dầu và không dính chảo',
        'summary' => 'Trước khi cho dầu vào, xát một lát gừng tươi quanh lòng chảo, hoặc rắc 1/2 thìa cà phê bột bắp/muối hạt vào chảo dầu nóng trước khi thả thực phẩm vào.',
        'content' => "1. **Làm khô thực phẩm:** Luôn dùng khăn giấy thấm thật khô bề mặt cá, thịt hoặc đậu phụ trước khi rán. Nước gặp dầu sôi là nguyên nhân chính gây nổ bắn dầu.\n2. **Dùng bột bắp hoặc muối hạt:** Rắc một nhúm muối hạt hoặc rây 1/2 thìa bột bắp mịn vào chảo dầu khi dầu vừa nóng. Bột sẽ hút hết hơi ẩm dư thừa giúp dầu êm ru.\n3. **Mẹo chảo chống dính tự nhiên:** Đun nóng chảo trước, dùng một lát gừng tươi chà đều khắp đáy chảo, sau đó mới rót dầu ăn vào. Mẹo này giúp cá da giòn rụm không bao giờ tróc da.",
        'author' => 'Chef Lan',
        'read_time' => '2 phút đọc',
        'badge' => 'Cực kỳ hữu ích'
    ],
    [
        'id' => 2,
        'category' => 'so_che',
        'category_name' => 'Sơ chế & Khử tanh',
        'icon' => '🐟',
        'title' => 'Bí quyết khử sạch mùi tanh của cá, mực và mùi gây của thịt bò',
        'summary' => 'Dùng rượu trắng + gừng đập dập, hoặc nước vo gạo và nước cốt chanh để rửa hải sản; ngâm thịt bò trong nước muối ấm loãng 10 phút trước khi ướp.',
        'content' => "1. **Khử tanh cá đồng & cá biển:** Dùng muối hột chà sạch nhớt, rửa lại bằng nước vo gạo hoặc nước có pha chút giấm/rượu trắng. Với cá lóc, cá chép, nhớ bóc bỏ màng đen trong bụng và gân máu sát xương sống.\n2. **Khử tanh mực & bạch tuộc:** Bóp mực với gừng tươi đập dập và 1 chén nhỏ rượu trắng trong 2 phút rồi xả lại bằng nước lạnh. Mực khi xào sẽ giòn sần sật và thơm nức.\n3. **Khử mùi gây của thịt bò:** Nướng sơ 1 củ gừng, cạo vỏ đập dập rồi chà xát lên miếng thịt bò, sau đó rửa lại bằng nước ấm. Thịt xào mềm thơm và giữ màu hồng tự nhiên.",
        'author' => 'Mẹ Bống',
        'read_time' => '3 phút đọc',
        'badge' => 'Bí quyết gia truyền'
    ],
    [
        'id' => 3,
        'category' => 'luoc_canh',
        'category_name' => 'Luộc & Nấu canh',
        'icon' => '🥦',
        'title' => 'Tuyệt chiêu luộc rau muống, súp lơ xanh mướt giòn sần sật như nhà hàng',
        'summary' => 'Nước phải sôi bùng với nhiều muối, luộc ngập nước mở nắp vung, và chuẩn bị ngay một thau nước đá lạnh để ngâm rau sau khi vớt.',
        'content' => "1. **Nước phải thật sôi:** Cho nước ngập rau và thêm 1 thìa cà phê muối hạt. Muối làm tăng nhiệt độ sôi của nước và giữ màu diệp lục của rau xanh tươi.\n2. **Mở nắp vung khi luộc:** Không đậy nắp vung để các hợp chất axit bay hơi, tránh làm rau bị vàng úa xỉn màu.\n3. **Sốc nhiệt nước đá lạnh:** Vừa vớt rau chín tới ra là thả ngay vào âu nước đá lạnh có vài viên đá. Ngâm 3 phút rồi vớt ra để ráo, rau sẽ giữ được màu xanh ngọc bích và độ giòn sần sật suốt nhiều giờ liền.",
        'author' => 'Chú Năm Cook',
        'read_time' => '2 phút đọc',
        'badge' => 'Mẹo nhà hàng'
    ],
    [
        'id' => 4,
        'category' => 'uop_gia_vi',
        'category_name' => 'Ướp & Nêm nếm',
        'icon' => '🥩',
        'title' => 'Mẹo ướp thịt nướng, sườn ram mềm tan mọng nước, không bị khô cứng',
        'summary' => 'Thêm 1 thìa sữa đặc có đường, nước cốt lê/táo hoặc sữa chua không đường vào sốt ướp; ướp cùng 1 thìa dầu ăn để khóa ẩm cho thớ thịt.',
        'content' => "1. **Enzyme làm mềm thịt tự nhiên:** Nước ép quả lê, táo hoặc dứa có chứa enzyme phá vỡ sợi cơ dai, làm thớ thịt bò/heo mềm mọng tự nhiên mà không cần dùng bột ngọt.\n2. **Bí quyết sữa đặc/sữa chua:** Cho 1 thìa canh sữa đặc Ông Thọ vào sốt ướp sườn nướng hoặc thịt kho. Thịt khi nướng lên màu cánh gián vàng óng, ngậy thơm béo nhẹ mà không bị ngọt gắt.\n3. **Khóa ẩm bằng dầu ăn:** Luôn cho dầu ăn vào bước cuối cùng của gia vị ướp. Lớp dầu sẽ phủ kín mặt thịt, ngăn không cho nước ngọt bên trong miếng thịt bị thoát ra ngoài.",
        'author' => 'Lan Anh Kitchen',
        'read_time' => '3 phút đọc',
        'badge' => 'Đầu bếp khuyên dùng'
    ],
    [
        'id' => 5,
        'category' => 'bao_quan',
        'category_name' => 'Bảo quản thực phẩm',
        'icon' => '🥬',
        'title' => 'Cách bảo quản hành lá, ngò rí và rau củ tươi ngon cả tuần không úng',
        'summary' => 'Rau củ không rửa trước khi cất, bọc trong khăn giấy sạch thấm ẩm rồi cho vào hộp kín hoặc túi zip có lỗ thoáng khí.',
        'content' => "1. **Hành lá & rau thơm:** Nhặt bỏ lá úa, giữ nguyên gốc rễ khô ráo (tuyệt đối không rửa nước). Dùng khăn giấy khô quấn quanh bó rau rồi đặt vào hộp nhựa kín để ngăn mát tủ lạnh, rau tươi rói suốt 10-14 ngày.\n2. **Hành củ & tỏi khô:** Không để trong túi nilon kín hay trong tủ lạnh vì dễ mọc mầm và nấm mốc. Treo ở nơi thoáng gió, khô ráo.\n3. **Bảo quản chanh ớt thừa:** Chanh cắt dở úp mặt cắt xuống đĩa có rải chút muối ăn, hoặc bọc kín màng bọc thực phẩm; ớt bỏ cuống rửa sạch lau khô rồi bỏ ngăn đá bảo quản được cả năm.",
        'author' => 'Bếp Hoa',
        'read_time' => '2 phút đọc',
        'badge' => 'Tiết kiệm chi phí'
    ],
    [
        'id' => 6,
        'category' => 'so_che',
        'category_name' => 'Sơ chế & Khử tanh',
        'icon' => '🦐',
        'title' => 'Bí quyết chọn tôm, cua và hải sản tươi sống chắc thịt, không ngậm nước',
        'summary' => 'Tôm thân cong đều, đuôi cụp, ấn vào săn chắc đàn hồi; cua bóp yếm cứng chắc không lún, màu mai đậm và càng linh hoạt.',
        'content' => "1. **Cách chọn tôm tươi:** Chọn con tôm còn bơi hoặc thân cong tròn tự nhiên, đầu dính chặt vào thân, vỏ trơn bóng trong suốt. Tránh những con tôm thân thẳng đơ, đuôi xòe rộng hoặc đầu phù nước vì thường là tôm đã bị bơm tạp chất.\n2. **Cách chọn cua chắc thịt:** Dùng ngón tay ấn mạnh vào yếm cua (hình tam giác dưới bụng). Nếu yếm cứng chắc không bị lõm là cua dày thịt, gạch béo. Nếu yếm mềm lún xuống là cua ốp, ít thịt nhiều nước.\n3. **Cách chọn nghêu, sò, ốc:** Chọn con còn mở miệng hé hé, chạm tay vào là khép miệng ngay. Tránh con ngậm miệng chặt nhưng bốc mùi hôi lạ.",
        'author' => 'Chef Lan',
        'read_time' => '3 phút đọc',
        'badge' => 'Kinh nghiệm chợ búa'
    ]
];
?>

<main class="container py-8">
    <!-- Breadcrumb -->
    <nav class="breadcrumb mb-4">
        <a href="<?= BASE_URL ?>/index.php">Trang chủ</a> &rsaquo;
        <span>Mẹo vặt nhà bếp</span>
    </nav>

    <!-- Page Header Banner (YummyDay Style) -->
    <div class="kitchen-tips-hero mb-8" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1.5px solid #fed7aa; border-radius: 1.25rem; padding: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
        <div>
            <span style="background: #ea580c; color: #ffffff; font-size: 0.82rem; font-weight: 800; padding: 0.3rem 0.85rem; border-radius: 9999px; display: inline-block; margin-bottom: 0.6rem;">
                💡 YUMMYDAY KITCHEN HACKS
            </span>
            <h1 style="font-size: 2.1rem; font-weight: 900; color: #7c2d12; margin: 0 0 0.5rem; line-height: 1.25;">
                Mẹo Vặt Nhà Bếp & Bí Quyết Nấu Ngon
            </h1>
            <p style="font-size: 1rem; color: #431407; margin: 0; max-width: 650px; line-height: 1.5;">
                Tổng hợp những kinh nghiệm thực tế đúc kết từ các đầu bếp gia đình: Khử tanh hải sản, chiên rán không bắn dầu, luộc rau xanh mướt và ướp thịt mềm mọng.
            </p>
        </div>
        <div style="font-size: 4.5rem; line-height: 1;">
            👩‍🍳
        </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="tips-filter-bar mb-6" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button type="button" class="category-pill active" onclick="filterTips('all', this)">Tất cả mẹo (<?= count($tips) ?>)</button>
        <button type="button" class="category-pill" onclick="filterTips('chien_xao', this)">🍳 Chiên & Rán</button>
        <button type="button" class="category-pill" onclick="filterTips('so_che', this)">🐟 Sơ chế & Khử tanh</button>
        <button type="button" class="category-pill" onclick="filterTips('luoc_canh', this)">🥦 Luộc & Nấu canh</button>
        <button type="button" class="category-pill" onclick="filterTips('uop_gia_vi', this)">🥩 Ướp & Nêm nếm</button>
        <button type="button" class="category-pill" onclick="filterTips('bao_quan', this)">🥬 Bảo quản thực phẩm</button>
    </div>

    <!-- Tips Grid -->
    <div class="tips-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.25rem;">
        <?php foreach ($tips as $t): ?>
            <article class="tip-card js-tip-card" data-cat="<?= e($t['category']) ?>" style="background: var(--bg-card, #ffffff); border: 1.5px solid var(--border, #e2e8f0); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 4px 14px rgba(0,0,0,0.04); display: flex; flex-direction: column; transition: transform 0.2s, border-color 0.2s;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.85rem;">
                    <span style="font-size: 2rem;"><?= $t['icon'] ?></span>
                    <span style="font-size: 0.76rem; font-weight: 700; color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; padding: 0.25rem 0.65rem; border-radius: 9999px;">
                        <?= e($t['badge']) ?>
                    </span>
                </div>

                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main, #1e293b); margin: 0 0 0.5rem; line-height: 1.35;">
                    <?= e($t['title']) ?>
                </h3>

                <p style="font-size: 0.9rem; color: var(--text-muted, #64748b); line-height: 1.5; margin: 0 0 1rem; flex: 1;">
                    <?= e($t['summary']) ?>
                </p>

                <!-- Collapsible detail steps -->
                <div class="tip-details" id="tipDetail_<?= $t['id'] ?>" style="display: none; background: #f8fafc; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; font-size: 0.88rem; line-height: 1.6; border: 1px dashed #cbd5e1;">
                    <?= nl2br(e($t['content'])) ?>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.85rem; border-top: 1px solid #f1f5f9; font-size: 0.82rem; color: #94a3b8;">
                    <span>Bởi <strong><?= e($t['author']) ?></strong> &bull; <?= e($t['read_time']) ?></span>
                    <button type="button" class="button button-outline" onclick="toggleTipDetail(<?= $t['id'] ?>, this)" style="padding: 0.35rem 0.75rem; font-size: 0.82rem; border-radius: 9999px;">
                        Xem chi tiết &darr;
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>

<script>
function filterTips(category, btn) {
    document.querySelectorAll('.tips-filter-bar .category-pill').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    document.querySelectorAll('.js-tip-card').forEach(card => {
        const cardCat = card.getAttribute('data-cat');
        if (category === 'all' || cardCat === category) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function toggleTipDetail(id, btn) {
    const el = document.getElementById('tipDetail_' + id);
    if (!el) return;
    if (el.style.display === 'none') {
        el.style.display = 'block';
        btn.textContent = 'Thu gọn ↑';
        btn.style.borderColor = '#ea580c';
        btn.style.color = '#ea580c';
    } else {
        el.style.display = 'none';
        btn.textContent = 'Xem chi tiết ↓';
        btn.style.borderColor = '';
        btn.style.color = '';
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
