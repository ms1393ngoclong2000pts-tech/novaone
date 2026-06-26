<?php
$queryUrl = function (array $changes = []): string {
    $query = array_merge($_GET, $changes);
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        }
    }

    return '?' . http_build_query($query);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
$exportUrl = $queryUrl(['route' => 'payrolls.export', 'page' => null, 'per_page' => null, 'sort' => null, 'dir' => null]);
?>

<section class="payroll-panel">
    <header class="payroll-head">
        <h2>DANH SÁCH BẢNG LƯƠNG</h2>
        <div class="payroll-head-actions">
            <button class="employee-action teal" type="button" data-payroll-open data-payroll-type="fixed">Lương cố định</button>
            <button class="employee-action blue" type="button" data-payroll-open data-payroll-type="shift">Lương theo ca</button>
            <a class="employee-action violet" href="<?= e($exportUrl) ?>">Xuất thông tin</a>
        </div>
    </header>

    <div class="payroll-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="payroll-filter" class="payroll-filter" method="get">
            <input type="hidden" name="route" value="payrolls">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <label>
                <span>Bộ phận</span>
                <select name="department" onchange="this.form.submit()">
                    <option value="">--- ---</option>
                    <?php foreach ($departments as $option): ?>
                        <option value="<?= e($option) ?>" <?= $department === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Từ Ngày</span><input name="start_date" type="date" value="<?= e($startDate) ?>"></label>
            <label><span>Đến Ngày</span><input name="end_date" type="date" value="<?= e($endDate) ?>"></label>
        </form>

        <div class="payroll-tools">
            <label>Hiển thị
                <select name="per_page" form="payroll-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="payrolls">
                <input type="hidden" name="type" value="<?= e($type) ?>">
                <input type="hidden" name="department" value="<?= e($department) ?>">
                <input type="hidden" name="start_date" value="<?= e($startDate) ?>">
                <input type="hidden" name="end_date" value="<?= e($endDate) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm Kiếm">
            </form>
        </div>

        <div class="payroll-table-wrap">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <?php foreach (['name' => 'Tên bảng lương', 'applied_position' => 'Vị trí áp dụng', 'employee_scope' => 'Nhân viên', 'total_salary' => 'Tổng lương', 'created_date' => 'Ngày tạo', 'status' => 'Trạng thái'] as $column => $label): ?>
                            <?php $sortColumn = $column === 'applied_position' ? 'department' : $column; ?>
                            <th><a href="<?= e($sortUrl($sortColumn)) ?>"><?= e($label) ?><span><?= $sort === $sortColumn ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <?php endforeach; ?>
                        <th>Chọn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="payroll-name" type="button" data-payroll-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['name'] ?? '') ?></button></td>
                            <td><?= e($item['applied_position'] ?? $item['department'] ?? '') ?></td>
                            <td><?= e($item['employee_scope'] ?? 'Tất cả') ?></td>
                            <td><?= e(money($item['total_salary'] ?? 0)) ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['created_date'] ?? 'now')))) ?></td>
                            <td class="payroll-status-cell">
                                <?php if (($item['status'] ?? '') === 'completed'): ?>
                                    <strong class="payroll-status completed">Hoàn Thành</strong>
                                <?php else: ?>
                                    <div class="payroll-row-actions">
                                        <form method="post" action="?route=payrolls.complete">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                            <button class="payroll-icon success" type="submit" aria-label="Hoàn thành"><?= ui_icon('check') ?></button>
                                        </form>
                                        <form method="post" action="?route=payrolls.delete" onsubmit="return confirm('Xóa bảng lương này?')">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                            <button class="payroll-icon danger" type="submit" aria-label="Xóa">×</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><input type="checkbox" value="<?= e($item['id'] ?? '') ?>" aria-label="Chọn bảng lương"></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="payroll-empty" colspan="8">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination payroll-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> mục</span>
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

<dialog id="payroll-dialog" class="employee-dialog payroll-dialog">
    <form method="post" action="?route=payrolls.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm bảng lương</h3><button type="button" data-payroll-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Tên bảng lương</span><input name="name" required></label>
            <label><span>Loại lương</span><select name="salary_type"><?php foreach ($types as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Bộ phận</span><select name="department"><option value="">--- ---</option><?php foreach ($departments as $option): ?><option value="<?= e($option) ?>"><?= e($option) ?></option><?php endforeach; ?></select></label>
            <label><span>Vị trí áp dụng</span><input name="applied_position"></label>
            <label><span>Nhân viên</span><select name="employee_scope"><option value="Tất cả">Tất cả</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['name'] ?? '') ?>"><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Tổng lương</span><input name="total_salary" type="number" min="0" step="1000"></label>
            <label><span>Ngày tạo</span><input name="created_date" type="date" required></label>
            <label><span>Trạng thái</span><select name="status"><?php foreach ($statuses as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label class="contract-note"><span>Ghi chú</span><input name="note"></label>
        </div>
        <footer><button class="employee-action" type="button" data-payroll-close>Hủy</button><button class="employee-action teal" type="submit">Lưu bảng lương</button></footer>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('payroll-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const today = new Date().toISOString().slice(0, 10);

    document.querySelectorAll('[data-payroll-open]').forEach(button => {
        button.addEventListener('click', () => {
            form.reset();
            form.elements.id.value = '';
            form.elements.salary_type.value = button.dataset.payrollType || 'fixed';
            form.elements.created_date.value = today;
            form.elements.status.value = 'draft';
            dialog.querySelector('h3').textContent = 'Thêm bảng lương';
            dialog.showModal();
        });
    });

    document.querySelectorAll('[data-payroll-close]').forEach(button => button.addEventListener('click', () => dialog.close()));

    document.querySelectorAll('[data-payroll-edit]').forEach(button => button.addEventListener('click', () => {
        const payroll = JSON.parse(button.dataset.payrollEdit);
        Object.entries(payroll).forEach(([key, value]) => {
            if (form.elements[key]) form.elements[key].value = value ?? '';
        });
        dialog.querySelector('h3').textContent = 'Cập nhật bảng lương';
        dialog.showModal();
    }));
})();
</script>
