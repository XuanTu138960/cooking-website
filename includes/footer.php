</main>
<footer class="yummy-site-footer">
    <div class="yummy-footer-container">
        <!-- Cột 1: Thương hiệu Cookio & Slogan Know - Love - Share -->
        <div class="y-footer-col y-footer-brand">
            <a href="<?= BASE_URL ?>/index.php" class="y-footer-logo-link" aria-label="Cookio">
                <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Cookio" class="y-footer-logo-img">
            </a>

            <div class="y-footer-motto-title">Know - Love - Share</div>
            <p class="y-footer-motto-desc"><strong>Know:</strong> Hiểu rõ điều đang làm và luôn học hỏi mỗi ngày</p>
            <p class="y-footer-motto-desc"><strong>Love:</strong> Đặt cái tâm, cái tình vào mỗi công thức</p>
            <p class="y-footer-motto-desc"><strong>Share:</strong> Chia sẻ niềm hạnh phúc qua những món ăn</p>
        </div>

        <!-- Cột 2: Khám phá (Loại bỏ các logo/icon theo yêu cầu Ảnh 1) -->
        <div class="y-footer-col">
            <h4 class="y-footer-heading">Khám phá</h4>
            <ul class="y-footer-links">
                <li><a href="<?= BASE_URL ?>/index.php">Công thức mới nhất</a></li>
                <li><a href="javascript:void(0)" onclick="openMealDeciderModal()">Hôm nay ăn gì?</a></li>
                <li><a href="<?= BASE_URL ?>/views/smart-fridge.php">Tủ lạnh thông minh</a></li>
                <li><a href="<?= BASE_URL ?>/index.php?cat=M%C3%B3n+chay">Món chay bổ dưỡng</a></li>
            </ul>
        </div>

        <!-- Cột 3: Liên hệ (Quang Trung Hà Đông Hà Nội, 0915916336, cookio.vn@gmail.com) -->
        <div class="y-footer-col">
            <h4 class="y-footer-heading">Liên hệ</h4>
            <ul class="y-footer-contact-list">
                <li class="y-contact-item">
                    <span class="y-contact-icon">📍</span>
                    <span>Quang Trung, Hà Đông, Hà Nội</span>
                </li>
                <li class="y-contact-item">
                    <span class="y-contact-icon">📞</span>
                    <a href="tel:0915916336">0915916336</a>
                </li>
                <li class="y-contact-item">
                    <span class="y-contact-icon">✉️</span>
                    <a href="mailto:cookio.vn@gmail.com">cookio.vn@gmail.com</a>
                </li>
            </ul>
        </div>

        <!-- Cột 4: Theo dõi chúng tôi & Pháp lý (Khớp Ảnh 4) -->
        <div class="y-footer-col">
            <h4 class="y-footer-heading">Theo dõi chúng tôi</h4>
            <div class="y-footer-social-row">
                <a href="https://facebook.com" target="_blank" rel="noopener" class="y-social-btn" aria-label="Facebook">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="https://linkedin.com" target="_blank" rel="noopener" class="y-social-btn" aria-label="LinkedIn">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                </a>
                <a href="https://tiktok.com" target="_blank" rel="noopener" class="y-social-btn" aria-label="TikTok">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                </a>
                <a href="https://twitter.com" target="_blank" rel="noopener" class="y-social-btn" aria-label="X (Twitter)">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
            </div>
            <ul class="y-footer-policy-links">
                <li><a href="javascript:void(0)">Giới thiệu</a></li>
                <li><a href="javascript:void(0)">Chính sách quảng cáo</a></li>
                <li><a href="javascript:void(0)">Chính sách bảo mật</a></li>
                <li><a href="javascript:void(0)">Miễn trừ trách nhiệm</a></li>
            </ul>
        </div>
    </div>

    <!-- Hàng đáy: Bản quyền & Huy hiệu DMCA -->
    <div class="yummy-footer-bottom">
        <p class="y-footer-copy">Copyright &copy; <?= date('Y') ?> Cookio. All Right Reserved.</p>
        <div class="dmca-badge" title="DMCA Protected">
            <span class="dmca-green">DMCA</span>
            <span class="dmca-white">PROTECTED</span>
        </div>
    </div>
</footer>

<!-- Nút tròn màu vàng cuộn lên đầu trang (Khớp Ảnh 4) -->
<button type="button" class="btn-back-to-top" id="btnBackToTop" aria-label="Cuộn lên đầu trang" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
    ↑
</button>

<!-- Modals toàn hệ thống: Login & Smart Fridge -->
<?php require_once __DIR__ . '/modal-login.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php if (isset($loadSmartFridgeScript) && $loadSmartFridgeScript): ?>
    <script src="<?= BASE_URL ?>/assets/js/smart-fridge.js"></script>
<?php endif; ?>
</body>
</html>
