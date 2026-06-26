<?php
$queryUrl = function (array $changes = []): string {
    $queryValues = array_merge($_GET, $changes);
    foreach ($queryValues as $key => $value) {
        if ($value === '' || $value === null) {
            unset($queryValues[$key]);
        }
    }
    return '?' . http_build_query($queryValues);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
$employeeIdsByName = [];
foreach ($employees as $employeeItem) {
    $employeeName = trim((string) ($employeeItem['name'] ?? ''));
    if ($employeeName !== '' && ! isset($employeeIdsByName[$employeeName])) {
        $employeeIdsByName[$employeeName] = (string) ($employeeItem['id'] ?? '');
    }
}
$employeeProfileLink = function (string $name) use ($employeeIdsByName): ?string {
    $id = $employeeIdsByName[trim($name)] ?? '';
    return $id !== '' ? '?route=employees.show&amp;id=' . e($id) : null;
};
?>

<section class="insurance-panel">
    <header class="insurance-head">
        <h2>DANH SÁCH THAM GIA BẢO HIỂM XÃ HỘI</h2>
        <button class="employee-action teal" type="button" data-insurance-open>Thêm tham gia</button>
    </header>

    <div class="insurance-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="insurance-filter" class="insurance-filter" method="get">
            <input type="hidden" name="route" value="social-insurance">
            <label>
                <span>Nhân viên</span>
                <select name="employee" onchange="this.form.submit()">
                    <option value="">-- Tất cả nhân viên --</option>
                    <?php foreach ($employeeNames as $name): ?>
                        <option value="<?= e($name) ?>" <?= $employee === $name ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <div class="insurance-tools">
            <label>Hiển thị
                <select name="per_page" form="insurance-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="social-insurance">
                <input type="hidden" name="employee" value="<?= e($employee) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="insurance-table-wrap">
            <table class="insurance-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <?php foreach ([
                            'employee_name' => 'Họ và tên', 'employee_code' => 'Mã nhân viên',
                            'contract_start' => 'Bắt đầu HĐ', 'contract_end' => 'Kết thúc HĐ',
                            'insurance_number' => 'Sổ bảo hiểm', 'salary' => 'Lương',
                            'contribution' => 'Tiền đóng BHXH',
                        ] as $column => $label): ?>
                            <th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <?php endforeach; ?>
                        <th>Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td>
                                <?php $profileLink = $employeeProfileLink((string) ($item['employee_name'] ?? '')); ?>
                                <?php if ($profileLink !== null): ?>
                                    <a class="employee-chip employee-name-link" href="<?= $profileLink ?>"><?= ui_icon('users') ?><?= e($item['employee_name'] ?? '') ?></a>
                                <?php else: ?>
                                    <span class="employee-chip"><?= ui_icon('users') ?><?= e($item['employee_name'] ?? '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($item['employee_code'] ?? '') ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['contract_start'] ?? 'now')))) ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['contract_end'] ?? 'now')))) ?></td>
                            <td><?= e($item['insurance_number'] ?? '') ?></td>
                            <td><?= e(money($item['salary'] ?? 0)) ?></td>
                            <td><?= e(money($item['contribution'] ?? 0)) ?></td>
                            <td><button class="insurance-detail" type="button" title="Xem chi tiết" data-insurance-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="contract-empty" colspan="9">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination insurance-pagination">
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

<dialog id="insurance-dialog" class="employee-dialog insurance-dialog">
    <form method="post" action="?route=social-insurance.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm tham gia BHXH</h3><button type="button" data-insurance-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Họ và tên</span><input name="employee_name" list="insurance-employees" required></label>
            <label><span>Mã nhân viên</span><input name="employee_code" required></label>
            <label><span>Bắt đầu hợp đồng</span><input name="contract_start" type="date" required></label>
            <label><span>Kết thúc hợp đồng</span><input name="contract_end" type="date" required></label>
            <label><span>Số bảo hiểm</span><input name="insurance_number"></label>
            <label><span>Mức lương đóng BHXH</span><input name="salary" type="number" min="0" step="100000" required></label>
            <label><span>Tiền đóng BHXH</span><input name="contribution" type="number" min="0" step="1000" placeholder="Để trống để tự tính 10,5%"></label>
            <label><span>Nơi đăng ký khám chữa bệnh</span><input name="hospital"></label>
            <label class="contract-note"><span>Ghi chú</span><input name="note"></label>
        </div>
        <footer class="insurance-dialog-actions">
            <button class="employee-action danger insurance-delete" type="button" hidden>Xóa</button>
            <span></span>
            <button class="employee-action" type="button" data-insurance-close>Hủy</button>
            <button class="employee-action teal" type="submit">Lưu thông tin</button>
        </footer>
    </form>
</dialog>

<form id="insurance-delete-form" method="post" action="?route=social-insurance.delete" hidden>
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="">
</form>

<datalist id="insurance-employees">
    <?php foreach ($employees as $employeeItem): ?><option value="<?= e($employeeItem['name'] ?? '') ?>"><?php endforeach; ?>
</datalist>

<script>
(() => {
    const dialog = document.getElementById('insurance-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const deleteButton = dialog.querySelector('.insurance-delete');
    const deleteForm = document.getElementById('insurance-delete-form');
    document.querySelector('[data-insurance-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        deleteButton.hidden = true;
        dialog.querySelector('h3').textContent = 'Thêm tham gia BHXH';
        dialog.showModal();
    });
    document.querySelectorAll('[data-insurance-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-insurance-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.insuranceEdit);
        Object.entries(item).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
        deleteButton.hidden = false;
        dialog.querySelector('h3').textContent = 'Chi tiết bảo hiểm xã hội';
        dialog.showModal();
    }));
    deleteButton.addEventListener('click', () => {
        if (!confirm('Xóa thông tin BHXH này?')) return;
        deleteForm.elements.id.value = form.elements.id.value;
        deleteForm.submit();
    });
})();
</script>
