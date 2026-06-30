<?php
$actions = [
    'view' => 'Xem',
    'create' => 'Thêm',
    'update' => 'Sửa',
    'delete' => 'Xóa',
];
$groupedModules = [];
foreach ($modules as $key => $meta) {
    $groupedModules[$meta['group']][] = ['key' => $key, ...$meta];
}
$isAdminRole = strcasecmp($selectedRole, 'Admin') === 0;
?>

<section class="permission-page">
    <header class="permission-hero">
        <div>
            <span>Role based access control</span>
            <h1>Phân quyền hệ thống</h1>
            <p>Quản lý quyền xem, thêm, sửa và xóa cho từng vai trò. Quyền được áp dụng ở cả menu giao diện và kiểm tra route phía backend.</p>
        </div>
        <div class="permission-current">
            <small>Tài khoản hiện tại</small>
            <strong><?= e($_SESSION['user']['name'] ?? 'Admin') ?></strong>
            <span><?= e(current_user_role()) ?></span>
        </div>
    </header>

    <?php if (! empty($_SESSION['flash_success'])): ?><div class="flash success"><?= e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>
    <?php if (! empty($_SESSION['flash_error'])): ?><div class="flash error"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>

    <form class="permission-toolbar" method="get">
        <input type="hidden" name="route" value="permissions">
        <label>
            <span>Vai trò</span>
            <select name="role" onchange="this.form.submit()">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role) ?>" <?= $selectedRole === $role ? 'selected' : '' ?>><?= e($role) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div>
            <strong><?= e($selectedRole) ?></strong>
            <small><?= $isAdminRole ? 'Admin luôn có toàn quyền.' : 'Có thể cấu hình quyền chi tiết bên dưới.' ?></small>
        </div>
    </form>

    <form class="permission-panel" method="post" action="?route=permissions.save">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="role" value="<?= e($selectedRole) ?>">

        <?php foreach ($groupedModules as $group => $items): ?>
            <section class="permission-group">
                <header>
                    <h2><?= e($group) ?></h2>
                    <span><?= count($items) ?> phân hệ</span>
                </header>
                <div class="permission-table-wrap">
                    <table class="permission-table">
                        <thead>
                            <tr>
                                <th>Phân hệ</th>
                                <?php foreach ($actions as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($item['label']) ?></strong>
                                        <small><?= e($item['key']) ?></small>
                                    </td>
                                    <?php foreach ($actions as $action => $label): ?>
                                        <?php $checked = (bool) ($matrix[$item['key']][$action] ?? false); ?>
                                        <td>
                                            <label class="permission-check">
                                                <input type="checkbox" name="permissions[<?= e($item['key']) ?>][<?= e($action) ?>]" value="1" <?= $checked ? 'checked' : '' ?> <?= $isAdminRole ? 'disabled' : '' ?>>
                                                <span><?= e($label) ?></span>
                                            </label>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>

        <footer class="permission-actions">
            <a class="report-btn ghost" href="?route=home">Quay lại</a>
            <button class="report-btn primary" type="submit" <?= $isAdminRole ? 'disabled' : '' ?>><?= ui_icon('settings') ?> Lưu phân quyền</button>
        </footer>
    </form>
</section>
