<dialog id="loginModal" class="login-modal">
    <button type="button" class="close-modal" data-close-modal aria-label="Đóng">&times;</button>
    
    <div class="login-modal-header" style="text-align: center; margin-bottom: 1.25rem;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #fff7ed; border-radius: 50%; margin-bottom: 0.5rem; border: 1.5px solid #fed7aa;">
            <span style="font-size: 1.5rem;">👨‍🍳</span>
        </div>
        <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--text-main, #1e293b); margin: 0 0 0.25rem;">Đăng Nhập Cookio</h2>
        <p style="font-size: 0.88rem; color: var(--text-muted, #64748b); margin: 0;">Khám phá & chia sẻ công thức nấu ăn ngon cùng cộng đồng bếp</p>
    </div>

    <!-- QUICK SAVED ACCOUNTS SECTION (CHỌN TÀI KHOẢN CÓ SẴN ĐỂ ĐĂNG NHẬP 1-CLICK) -->
    <div class="quick-accounts-container" id="quickAccountsContainer">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
            <span style="font-size: 0.85rem; font-weight: 700; color: #ea580c; display: flex; align-items: center; gap: 0.35rem;">
                <span>⚡</span> Tài khoản đăng nhập nhanh (1-Click)
            </span>
            <span style="font-size: 0.78rem; color: #94a3b8;">Bấm để vào ngay</span>
        </div>

        <div class="quick-accounts-list" id="quickAccountsList">
            <!-- Account 1: Admin -->
            <div class="quick-account-card" onclick="quickLoginUser('admin1111', '1', 'Quản trị viên')">
                <div class="quick-avatar" style="background: #fee2e2; color: #dc2626;">👑</div>
                <div class="quick-info">
                    <strong class="quick-name">admin1111</strong>
                    <span class="quick-role">Quản trị viên hệ thống</span>
                </div>
                <button type="button" class="btn-quick-go">Vào &rarr;</button>
            </div>

            <!-- Account 2: Chef Lan -->
            <div class="quick-account-card" onclick="quickLoginUser('chef_lan', '1', 'Bếp Trưởng Lan')">
                <div class="quick-avatar" style="background: #ffedd5; color: #ea580c;">👩‍🍳</div>
                <div class="quick-info">
                    <strong class="quick-name">chef_lan</strong>
                    <span class="quick-role">Bếp Trưởng Lan</span>
                </div>
                <button type="button" class="btn-quick-go">Vào &rarr;</button>
            </div>

            <!-- Account 3: Me Bong -->
            <div class="quick-account-card" onclick="quickLoginUser('me_bong', '1', 'Mẹ Bống Nội Trợ')">
                <div class="quick-avatar" style="background: #fef9c3; color: #ca8a04;">🍲</div>
                <div class="quick-info">
                    <strong class="quick-name">me_bong</strong>
                    <span class="quick-role">Mẹ Bống Nội Trợ</span>
                </div>
                <button type="button" class="btn-quick-go">Vào &rarr;</button>
            </div>

            <!-- Account 4: Chu Nam Cook -->
            <div class="quick-account-card" onclick="quickLoginUser('chu_nam_cook', '1', 'Chú Năm Cook')">
                <div class="quick-avatar" style="background: #dbeafe; color: #2563eb;">👨‍🍳</div>
                <div class="quick-info">
                    <strong class="quick-name">chu_nam_cook</strong>
                    <span class="quick-role">Chú Năm Cook</span>
                </div>
                <button type="button" class="btn-quick-go">Vào &rarr;</button>
            </div>

            <!-- Account 5: Lan Anh Kitchen -->
            <div class="quick-account-card" onclick="quickLoginUser('lan_anh_kitchen', '1', 'Lan Anh Kitchen')">
                <div class="quick-avatar" style="background: #dcfce7; color: #16a34a;">🍳</div>
                <div class="quick-info">
                    <strong class="quick-name">lan_anh_kitchen</strong>
                    <span class="quick-role">Lan Anh Kitchen</span>
                </div>
                <button type="button" class="btn-quick-go">Vào &rarr;</button>
            </div>
        </div>

        <!-- Custom Saved Accounts from LocalStorage will be appended here dynamically -->
        <div id="customSavedAccountsList"></div>
    </div>

    <!-- DIVIDER -->
    <div style="position: relative; text-align: center; margin: 1.25rem 0 1rem;">
        <hr style="border: 0; border-top: 1px solid var(--border, #e2e8f0);">
        <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--bg-card, #ffffff); padding: 0 0.65rem; font-size: 0.78rem; color: #94a3b8; font-weight: 600;">hoặc nhập tài khoản</span>
    </div>

    <!-- AUTH TABS -->
    <div class="auth-tabs" style="margin-bottom: 1rem;">
        <button type="button" class="auth-tab active" data-auth-tab="login" onclick="switchAuthTab('login')">Đăng nhập</button>
        <button type="button" class="auth-tab" data-auth-tab="register" onclick="switchAuthTab('register')">Đăng ký mới</button>
    </div>

    <!-- FORM LOGIN -->
    <form id="formLogin" method="post" action="<?= BASE_URL ?>/actions/auth_action.php" onsubmit="handleLoginSubmit(event)">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <label style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.35rem; display: block;">
            Tên tài khoản:
            <input type="text" id="loginUsernameInput" name="username" placeholder="Ví dụ: admin1111 hoặc chef_lan" required style="margin-top: 0.25rem;">
        </label>
        
        <label style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.75rem; display: block;">
            Mật khẩu:
            <input type="password" id="loginPasswordInput" name="password" placeholder="Nhập mật khẩu (mặc định: 1)" required style="margin-top: 0.25rem;">
        </label>

        <!-- REMEMBER ME CHECKBOX -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.84rem; cursor: pointer; color: var(--text-muted, #64748b);">
                <input type="checkbox" id="chkRememberAccount" checked style="width: auto; margin: 0; accent-color: #ea580c;">
                <span>Lưu tài khoản trên thiết bị này</span>
            </label>
        </div>

        <button type="submit" class="button button-create" style="width: 100%; padding: 0.75rem; font-weight: 700; font-size: 0.95rem; border-radius: 0.75rem;">
            Đăng nhập vào Cookio
        </button>
    </form>

    <!-- FORM REGISTER -->
    <form id="formRegister" class="hidden" method="post" action="<?= BASE_URL ?>/actions/auth_action.php">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <label style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.35rem; display: block;">
            Tên tài khoản mong muốn:
            <input type="text" name="username" placeholder="Ví dụ: bep_nha_minh" required style="margin-top: 0.25rem;">
        </label>
        <label style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.35rem; display: block;">
            Số điện thoại (tùy chọn):
            <input type="tel" name="phone" placeholder="0901234567" style="margin-top: 0.25rem;">
        </label>
        <label style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.35rem; display: block;">
            Mật khẩu:
            <input type="password" name="password" placeholder="Mật khẩu của bạn" required style="margin-top: 0.25rem;">
        </label>
        <label style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.75rem; display: block;">
            Xác nhận mật khẩu:
            <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required style="margin-top: 0.25rem;">
        </label>
        
        <button type="submit" class="button button-create" style="width: 100%; padding: 0.75rem; font-weight: 700; font-size: 0.95rem; border-radius: 0.75rem;">
            Tạo tài khoản mới
        </button>
    </form>
</dialog>
