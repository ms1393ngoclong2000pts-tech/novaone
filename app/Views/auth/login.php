<main class="login">
    <section class="login-visual">
        <div>
            <h1>Novaone</h1>
            <p>Không gian quản trị tập trung cho nhân sự, công việc, bán hàng, kho vận và báo cáo điều hành.</p>
        </div>
    </section>
    <section class="login-panel">
        <form class="login-box" method="post" action="?route=login">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="logo"><span class="logo-mark">N</span><span>Novaone Admin</span></div>
            <?php if (! empty($_SESSION['error'])): ?>
                <div class="alert"><?= e($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <label class="field">
                <span>Email</span>
                <input name="email" type="email" value="<?= e($config['demo_user']['email']) ?>" required>
            </label>
            <label class="field">
                <span>Mật khẩu</span>
                <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                <button class="login-password-hint" type="button" data-login-password="<?= e($config['demo_user']['password']) ?>">
                    G&#7907;i &#253; m&#7853;t kh&#7849;u: <strong><?= e($config['demo_user']['password']) ?></strong>
                </button>
            </label>
            <button class="btn primary" type="submit">Đăng nhập</button>
        </form>
    </section>
</main>

<script>
document.querySelector('[data-login-password]')?.addEventListener('click', (event) => {
    const password = document.getElementById('login-password');
    if (!password) return;
    password.value = event.currentTarget.dataset.loginPassword || '';
    password.focus();
});
</script>
