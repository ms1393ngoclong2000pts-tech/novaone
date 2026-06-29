<?php
$queryUrl = function (array $changes = []): string {
    $query = array_merge($_GET, $changes);
    foreach ($query as $key => $value) {
        if ($value === '') {
            unset($query[$key]);
        }
    }
    return '?' . http_build_query($query);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl([
        'sort' => $column,
        'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        'page' => 1,
    ]);
};
$statusLabels = ['active' => 'Đang làm việc', 'on_leave' => 'Đang nghỉ phép', 'inactive' => 'Đã nghỉ việc'];
?>

<section class="employee-panel">
    <header class="employee-head">
        <h2>QUẢN LÝ NHÂN SỰ</h2>
        <div class="employee-head-actions">
            <a class="employee-icon-btn" href="?route=home" title="Quản lý ứng dụng"><?= ui_icon('command') ?></a>
            <a class="employee-action blue" href="?route=employees.export">Xuất</a>
            <a class="employee-action violet" href="?route=employees.template">Tải file mẫu danh sách nhân sự</a>
            <button class="employee-action teal" type="button" data-dialog-open="employee-dialog">Thêm nhân sự</button>
        </div>
    </header>

    <div class="employee-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="employee-filter-band">
            <form id="employee-filters" class="employee-filters" method="get">
                <input type="hidden" name="route" value="employees">
                <label>
                    <span>Chức danh</span>
                    <select name="position" onchange="this.form.submit()">
                        <option value="">-- Chức danh --</option>
                        <?php foreach ($positions as $position): ?>
                            <option value="<?= e($position) ?>" <?= $filters['position'] === $position ? 'selected' : '' ?>><?= e($position) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Bộ phận</span>
                    <select name="department" onchange="this.form.submit()">
                        <option value="">-- Bộ phận --</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= e($department) ?>" <?= $filters['department'] === $department ? 'selected' : '' ?>><?= e($department) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Tình trạng</span>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">Tất cả tình trạng</option>
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>

            <form class="employee-upload" method="post" action="?route=employees.import" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <label>
                    <span>Chọn file <strong>Excel: .xlsx hoặc .csv</strong></span>
                    <input name="employee_file" type="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
                </label>
                <button type="submit">UPLOAD FILE</button>
            </form>
        </div>

        <div class="employee-table-tools">
            <label>Hiển thị
                <strong>10</strong>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="employees">
                <input name="q" value="<?= e($filters['q']) ?>" placeholder="Tìm kiếm:">
            </form>
        </div>

        <form id="employee-column-search" method="get">
            <input type="hidden" name="route" value="employees">
            <input type="hidden" name="position" value="<?= e($filters['position']) ?>">
            <input type="hidden" name="department" value="<?= e($filters['department']) ?>">
            <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
        </form>

        <div class="employee-table-wrap">
            <table class="employee-table">
                <thead>
                    <tr>
                        <?php foreach ([
                            'attendance_code' => 'Mã chấm công', 'employee_code' => 'Mã nhân viên',
                            'name' => 'Họ và tên', 'gender' => 'Giới tính', 'email' => 'Địa chỉ email',
                            'position' => 'Chức danh', 'department' => 'Bộ phận', 'status' => 'Tình trạng',
                        ] as $column => $label): ?>
                            <th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span class="sort-mark"><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <?php endforeach; ?>
                        <th>Thao tác</th>
                    </tr>
                    <tr class="column-filters">
                        <?php foreach ([
                            'attendance_code' => 'Mã chấm công', 'employee_code' => 'Mã nhân viên',
                            'name' => 'Họ và tên', 'gender' => 'Giới tính', 'email' => 'Địa chỉ email',
                            'position' => 'Chức danh', 'department' => 'Bộ phận', 'status' => 'Tình trạng',
                        ] as $column => $label): ?>
                            <th><input form="employee-column-search" name="<?= e($column) ?>" value="<?= e($filters[$column]) ?>" placeholder="Tìm theo <?= e($label) ?>"></th>
                        <?php endforeach; ?>
                        <th><button form="employee-column-search" type="submit" title="Lọc"><?= ui_icon('search') ?></button></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><span class="employee-chip"><?= ui_icon('users') ?><?= e($item['attendance_code']) ?></span></td>
                            <td><?= e($item['employee_code']) ?></td>
                            <td><a class="employee-chip employee-name-link" href="?route=employees.show&amp;id=<?= e($item['id']) ?>"><?= ui_icon('users') ?><?= e($item['name']) ?></a></td>
                            <td><?= e($item['gender']) ?></td>
                            <td><?php if ($item['email'] !== ''): ?><a class="employee-chip" href="mailto:<?= e($item['email']) ?>"><?= ui_icon('mail') ?><?= e($item['email']) ?></a><?php endif; ?></td>
                            <td><?= e($item['position']) ?></td>
                            <td><?= e($item['department']) ?></td>
                            <td><span class="employee-status <?= e($item['status']) ?>"><?= e($statusLabels[$item['status']] ?? $item['status']) ?></span></td>
                            <td class="employee-row-actions">
                                <button type="button" title="Sửa" data-employee-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'>Sửa</button>
                                <form method="post" action="?route=resource.delete" onsubmit="return confirm('Xóa nhân viên này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="_resource" value="employees">
                                    <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                    <button class="danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="empty" colspan="9">Không có nhân viên phù hợp.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?>-<?= min($page * $perPage, $total) ?> trong <?= $total ?> nhân viên</span>
            <nav>
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a>
                <?php for ($number = 1; $number <= $pages; $number++): ?>
                    <a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a>
                <?php endfor; ?>
                <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a>
            </nav>
        </footer>
    </div>
</section>

<dialog id="employee-dialog" class="employee-dialog">
    <form method="post" action="?route=resource.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="_resource" value="employees">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm nhân sự</h3><button type="button" data-dialog-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Mã chấm công</span><input name="attendance_code" required></label>
            <label><span>Mã nhân viên</span><input name="employee_code" required></label>
            <label><span>Họ và tên</span><input name="name" required></label>
            <label><span>Giới tính</span><select name="gender"><option>Nam</option><option>Nữ</option><option>Khác</option></select></label>
            <label><span>Địa chỉ email</span><input name="email" type="email" required></label>
            <label><span>Chức danh</span><input name="position" required></label>
            <label><span>Bộ phận</span><select name="department"><option>Kinh doanh</option><option>Nhân sự</option><option>Kho vận</option><option>Công nghệ</option><option>Tài chính</option></select></label>
            <label><span>Hợp đồng</span><select name="contract"><option>Chính thức</option><option>Thử việc</option><option>Thời vụ</option></select></label>
            <label><span>Tình trạng</span><select name="status"><option value="active">Đang làm việc</option><option value="on_leave">Đang nghỉ phép</option><option value="inactive">Đã nghỉ việc</option></select></label>
        </div>
        <footer><button type="button" class="employee-action" data-dialog-close>Hủy</button><button class="employee-action teal" type="submit">Lưu nhân sự</button></footer>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('employee-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    document.querySelectorAll('[data-dialog-open]').forEach(button => button.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        dialog.querySelector('h3').textContent = 'Thêm nhân sự';
        dialog.showModal();
    }));
    document.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-employee-edit]').forEach(button => button.addEventListener('click', () => {
        const employee = JSON.parse(button.dataset.employeeEdit);
        Object.entries(employee).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
        dialog.querySelector('h3').textContent = 'Cập nhật nhân sự';
        dialog.showModal();
    }));
})();
</script>
