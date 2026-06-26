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
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
$exportUrl = $queryUrl(['route' => 'contracts.export', 'page' => null, 'per_page' => null, 'sort' => null, 'dir' => null]);
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

<section class="contract-panel">
    <header class="contract-head">
        <h2>HỢP ĐỒNG</h2>
        <button class="employee-action teal" type="button" data-contract-open>Thêm mới</button>
    </header>

    <div class="contract-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <nav class="contract-tabs" aria-label="Loại hợp đồng">
            <?php foreach ($types as $value => $label): ?>
                <a class="<?= $type === $value ? 'active' : '' ?>" href="?route=contracts&type=<?= e($value) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <form id="contract-filter" class="contract-filter" method="get">
            <input type="hidden" name="route" value="contracts">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <label><span>Ngày bắt đầu</span><input name="start_date" type="date" value="<?= e($startDate) ?>"></label>
            <label><span>Ngày kết thúc</span><input name="end_date" type="date" value="<?= e($endDate) ?>"></label>
            <button class="contract-filter-button" type="submit">Lọc</button>
            <a class="contract-export" href="<?= e($exportUrl) ?>"><?= ui_icon('file') ?> Xuất file</a>
        </form>

        <div class="contract-tools">
            <label>Hiển thị
                <select name="per_page" form="contract-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="contracts">
                <input type="hidden" name="type" value="<?= e($type) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm:">
            </form>
        </div>

        <div class="contract-table-wrap">
            <table class="contract-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <?php foreach (['contract_code' => 'Mã hợp đồng', 'employee_name' => 'Họ và tên', 'salary' => 'Mức lương', 'start_date' => 'Ngày bắt đầu', 'end_date' => 'Ngày kết thúc'] as $column => $label): ?>
                            <th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <?php endforeach; ?>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><strong><?= e($item['contract_code'] ?? '') ?></strong></td>
                            <td>
                                <?php $profileLink = $employeeProfileLink((string) ($item['employee_name'] ?? '')); ?>
                                <?php if ($profileLink !== null): ?>
                                    <a class="employee-chip employee-name-link" href="<?= $profileLink ?>"><?= ui_icon('users') ?><?= e($item['employee_name'] ?? '') ?></a>
                                <?php else: ?>
                                    <span class="employee-chip"><?= ui_icon('users') ?><?= e($item['employee_name'] ?? '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(money($item['salary'] ?? 0)) ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['start_date'] ?? 'now')))) ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['end_date'] ?? 'now')))) ?></td>
                            <td class="contract-actions">
                                <button type="button" data-contract-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'>Sửa</button>
                                <form method="post" action="?route=contracts.delete" onsubmit="return confirm('Xóa hợp đồng này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <input type="hidden" name="contract_type" value="<?= e($type) ?>">
                                    <button class="danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="contract-empty" colspan="7">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination contract-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trong số <?= $total ?> mục</span>
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

<dialog id="contract-dialog" class="employee-dialog contract-dialog">
    <form method="post" action="?route=contracts.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm hợp đồng</h3><button type="button" data-contract-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Mã hợp đồng</span><input name="contract_code" required></label>
            <label><span>Loại hợp đồng</span><select name="contract_type"><?php foreach ($types as $value => $label): ?><option value="<?= e($value) ?>" <?= $type === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Nhân viên</span><input name="employee_name" list="contract-employees" required></label>
            <label><span>Mức lương</span><input name="salary" type="number" min="0" step="100000" required></label>
            <label><span>Ngày bắt đầu</span><input name="start_date" type="date" required></label>
            <label><span>Ngày kết thúc</span><input name="end_date" type="date" required></label>
            <label class="contract-note"><span>Ghi chú</span><input name="note"></label>
        </div>
        <footer><button class="employee-action" type="button" data-contract-close>Hủy</button><button class="employee-action teal" type="submit">Lưu hợp đồng</button></footer>
    </form>
</dialog>

<datalist id="contract-employees">
    <?php foreach ($employees as $employee): ?><option value="<?= e($employee['name'] ?? '') ?>"><?php endforeach; ?>
</datalist>

<script>
(() => {
    const dialog = document.getElementById('contract-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    document.querySelector('[data-contract-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.contract_type.value = <?= json_encode($type) ?>;
        dialog.querySelector('h3').textContent = 'Thêm hợp đồng';
        dialog.showModal();
    });
    document.querySelectorAll('[data-contract-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-contract-edit]').forEach(button => button.addEventListener('click', () => {
        const contract = JSON.parse(button.dataset.contractEdit);
        Object.entries(contract).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
        dialog.querySelector('h3').textContent = 'Cập nhật hợp đồng';
        dialog.showModal();
    }));
})();
</script>
