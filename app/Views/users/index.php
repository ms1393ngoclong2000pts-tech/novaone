<?php
/** @var array<int, array<string, mixed>> $items */
/** @var array<int, string> $roles */
/** @var array<string, string> $statuses */
/** @var string $query */
/** @var string $role */
/** @var string $status */

$safeUser = static function (array $user): array {
    unset($user['password_hash']);
    return $user;
};
?>
<section class="report-page users-page">
    <header class="report-hero">
        <div>
            <span>Account Security</span>
            <h1>Quản lý tài khoản</h1>
            <p>Tạo tài khoản riêng cho từng người dùng, gán vai trò, khóa truy cập và đặt lại mật khẩu khi cần.</p>
        </div>
        <div class="report-hero-actions">
            <a class="report-btn soft" href="?route=permissions"><?= ui_icon('settings') ?> Phân quyền</a>
            <button class="report-btn primary" type="button" data-user-open><?= ui_icon('users') ?> Thêm tài khoản</button>
        </div>
    </header>

    <?php if (! empty($_SESSION['flash_success'])): ?><div class="flash success"><?= e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>
    <?php if (! empty($_SESSION['flash_error'])): ?><div class="flash error"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>

    <form class="report-filter" method="get">
        <input type="hidden" name="route" value="users">
        <label><span>Tìm kiếm</span><input name="q" value="<?= e($query) ?>" placeholder="Tên, email, vai trò"></label>
        <label><span>Vai trò</span><select name="role"><option value="">Tất cả</option><?php foreach ($roles as $itemRole): ?><option value="<?= e($itemRole) ?>" <?= $role === $itemRole ? 'selected' : '' ?>><?= e($itemRole) ?></option><?php endforeach; ?></select></label>
        <label><span>Trạng thái</span><select name="status"><option value="">Tất cả</option><?php foreach ($statuses as $key => $label): ?><option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <button class="report-btn primary" type="submit"><?= ui_icon('search') ?> Lọc</button>
        <a class="report-btn ghost" href="?route=users">Đặt lại</a>
    </form>

    <section class="report-section">
        <div class="report-section-head">
            <div>
                <h2>Danh sách tài khoản</h2>
                <p><?= count($items) ?> tài khoản phù hợp bộ lọc.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Cập nhật</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= e($item['name'] ?? '') ?></strong></td>
                            <td><?= e($item['email'] ?? '') ?></td>
                            <td><?= badge((string) ($item['role'] ?? 'Manager')) ?></td>
                            <td><?= badge((string) ($item['status'] ?? 'active')) ?></td>
                            <td><?= e($item['updated_at'] ?? $item['created_at'] ?? '-') ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="employee-action blue" type="button" data-user-edit='<?= e(json_encode($safeUser($item), JSON_UNESCAPED_UNICODE)) ?>'>Sửa</button>
                                    <form method="post" action="?route=users.delete" onsubmit="return confirm('Xóa tài khoản này?')">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                        <button class="employee-action danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($items === []): ?>
                        <tr><td colspan="6" class="empty">Không có tài khoản phù hợp.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<dialog id="user-dialog" class="employee-dialog user-dialog">
    <form method="post" action="?route=users.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id">
        <header>
            <h3 data-user-title>Thêm tài khoản</h3>
            <button type="button" data-user-close aria-label="Đóng">×</button>
        </header>
        <div class="employee-form-grid">
            <label><span>Họ tên *</span><input name="name" required maxlength="160"></label>
            <label><span>Email *</span><input name="email" type="email" required maxlength="180"></label>
            <label><span>Vai trò *</span><select name="role" required><?php foreach ($roles as $itemRole): ?><option value="<?= e($itemRole) ?>"><?= e($itemRole) ?></option><?php endforeach; ?></select></label>
            <label><span>Trạng thái</span><select name="status"><?php foreach ($statuses as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label class="span-2"><span>Mật khẩu <small data-password-note></small></span><input name="password" type="password" autocomplete="new-password" minlength="6" placeholder="Ít nhất 6 ký tự"></label>
        </div>
        <footer>
            <button class="employee-action" type="button" data-user-close>Hủy</button>
            <button class="employee-action teal" type="submit">Lưu tài khoản</button>
        </footer>
    </form>
</dialog>

<script>
(() => {
  const dialog = document.getElementById('user-dialog');
  if (!dialog) return;
  const form = dialog.querySelector('form');
  const title = dialog.querySelector('[data-user-title]');
  const note = dialog.querySelector('[data-password-note]');
  const openCreate = () => {
    form.reset();
    form.elements.id.value = '';
    form.elements.password.required = true;
    title.textContent = 'Thêm tài khoản';
    note.textContent = '(bắt buộc khi tạo mới)';
    dialog.showModal();
  };
  const openEdit = (data) => {
    form.reset();
    form.elements.id.value = data.id || '';
    form.elements.name.value = data.name || '';
    form.elements.email.value = data.email || '';
    form.elements.role.value = data.role || 'Manager';
    form.elements.status.value = data.status || 'active';
    form.elements.password.required = false;
    title.textContent = 'Cập nhật tài khoản';
    note.textContent = '(để trống nếu không đổi)';
    dialog.showModal();
  };

  document.querySelector('[data-user-open]')?.addEventListener('click', openCreate);
  document.querySelectorAll('[data-user-edit]').forEach((button) => {
    button.addEventListener('click', () => openEdit(JSON.parse(button.dataset.userEdit || '{}')));
  });
  document.querySelectorAll('[data-user-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
})();
</script>
