<section class="panel account-panel">
    <div class="panel-head">
        <div>
            <h2>Đổi mật khẩu</h2>
            <p>Mật khẩu mới sẽ được dùng cho lần đăng nhập tiếp theo.</p>
        </div>
    </div>
    <div class="panel-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
        <form method="post" action="?route=password" class="form-grid account-form">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <label class="field span-2">
                <span>Mật khẩu hiện tại</span>
                <input name="current_password" type="password" required>
            </label>
            <label class="field">
                <span>Mật khẩu mới</span>
                <input name="password" type="password" minlength="6" required>
            </label>
            <label class="field">
                <span>Xác nhận mật khẩu mới</span>
                <input name="password_confirmation" type="password" minlength="6" required>
            </label>
            <div class="actions span-2">
                <button class="btn primary" type="submit">Đổi mật khẩu</button>
                <a class="btn" href="?route=dashboard">Quay lại</a>
            </div>
        </form>
    </div>
</section>
