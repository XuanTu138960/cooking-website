<dialog id="loginModal" class="login-modal">
    <button type="button" class="close-modal" data-close-modal aria-label="Đóng">&times;</button>
    <div id="authMethodsView">
        <p class="eyebrow">COOKIO</p>
        <h2>Đăng ký hoặc đăng nhập</h2>
        <button type="button" class="button full-width" data-show-form>Tiếp tục với tài khoản</button>
    </div>
    <div id="authFormView" class="hidden">
        <div class="auth-tabs">
            <button type="button" class="auth-tab active" data-auth-tab="login">Đăng nhập</button>
            <button type="button" class="auth-tab" data-auth-tab="register">Đăng ký</button>
        </div>
        <form id="formLogin" method="post" action="<?= BASE_URL ?>/actions/auth_action.php">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <label>Tài khoản<input type="text" name="username" placeholder="admin1111" required></label>
            <label>Mật khẩu<input type="password" name="password" placeholder="1" required></label>
            <button type="submit">Đăng nhập</button>
        </form>
        <form id="formRegister" class="hidden" method="post" action="<?= BASE_URL ?>/actions/auth_action.php">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <label>Tên tài khoản<input type="text" name="username" required></label>
            <label>Số điện thoại<input type="tel" name="phone" placeholder="0901234567"></label>
            <label>Mật khẩu<input type="password" name="password" required></label>
            <label>Xác nhận mật khẩu<input type="password" name="confirm_password" required></label>
            <button type="submit">Tạo tài khoản</button>
        </form>
        <button type="button" class="text-button" data-show-methods>Chọn phương thức khác</button>
    </div>
</dialog>
