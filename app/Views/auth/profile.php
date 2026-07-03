<section class="panel account-panel">
    <div class="panel-head">
        <div>
            <h2>Thông tin cá nhân</h2>
            <p>Cập nhật hồ sơ hiển thị trên góc phải hệ thống.</p>
        </div>
    </div>
    <div class="panel-body">
        <div class="mobile-profile-summary">
            <div class="mobile-profile-avatar">
                <?php if (! empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" alt="">
                <?php else: ?>
                    <?= e(first_character($user['name'] ?? 'A')) ?>
                <?php endif; ?>
            </div>
            <h1><?= e($user['company'] ?? $user['name'] ?? 'Novaone') ?></h1>
            <p>Đăng nhập với <?= e($user['role'] ?? 'admin') ?></p>
        </div>
        <h3 class="mobile-profile-section-title">Giới thiệu</h3>
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
        <form method="post" action="?route=profile" class="form-grid account-form" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="profile-photo span-2">
                <div class="profile-avatar">
                    <?php if (! empty($user['avatar'])): ?>
                        <img src="<?= e($user['avatar']) ?>" alt="">
                    <?php else: ?>
                        <?= e(first_character($user['name'] ?? 'A')) ?>
                    <?php endif; ?>
                </div>
                <label class="field profile-upload">
                    <span>Ảnh đại diện</span>
                    <input name="avatar" type="file" accept="image/png,image/jpeg,image/webp,image/gif">
                    <small>Hỗ trợ JPG, PNG, WEBP, GIF. Tối đa 2MB.</small>
                </label>
            </div>
            <label class="field">
                <span class="profile-field-icon"><?= ui_icon('users') ?></span>
                <span>Họ tên</span>
                <input name="name" value="<?= e($user['name'] ?? '') ?>" required>
            </label>
            <label class="field">
                <span class="profile-field-icon"><?= ui_icon('mail') ?></span>
                <span>Email</span>
                <input value="<?= e($user['email'] ?? '') ?>" disabled>
            </label>
            <label class="field">
                <span class="profile-field-icon"><?= ui_icon('phone') ?></span>
                <span>Điện thoại</span>
                <input name="phone" value="<?= e($user['phone'] ?? '') ?>">
            </label>
            <label class="field">
                <span class="profile-field-icon"><?= ui_icon('briefcase') ?></span>
                <span>Chức danh</span>
                <input name="position" value="<?= e($user['position'] ?? '') ?>">
            </label>
            <label class="field">
                <span class="profile-field-icon"><?= ui_icon('calendar') ?></span>
                <span>Ngày sinh</span>
                <input name="birthday" type="date" value="<?= e($user['birthday'] ?? '') ?>">
            </label>
            <label class="field">
                <span class="profile-field-icon"><?= ui_icon('building') ?></span>
                <span>Công ty</span>
                <input name="company" value="<?= e($user['company'] ?? 'bData co.,ltd') ?>" required>
            </label>
            <label class="field span-2">
                <span class="profile-field-icon"><?= ui_icon('map') ?></span>
                <span>Địa chỉ</span>
                <input name="address" value="<?= e($user['address'] ?? '') ?>">
            </label>
            <h3 class="mobile-profile-section-title span-2">Cài đặt</h3>
            <div class="actions span-2">
                <button class="btn primary" type="submit"><?= ui_icon('save') ?> Lưu thông tin</button>
                <a class="btn" href="?route=dashboard"><?= ui_icon('arrow-left') ?> Quay lại</a>
            </div>
        </form>
    </div>
</section>
