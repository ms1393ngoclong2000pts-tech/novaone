<?php
$queryUrl = function (array $changes = []): string {
    $query = array_merge($_GET, $changes);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }
    return '?' . http_build_query($query);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
$typeClass = ['general' => 'blue', 'advance' => 'teal', 'overtime' => 'amber', 'violation' => 'violet', 'reward' => 'soft', 'extra_work' => 'pink'];
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

<section class="request-panel">
    <header class="request-head">
        <h2>DANH SÁCH PHIẾU YÊU CẦU</h2>
        <div class="request-head-actions">
            <?php foreach (['general' => 'Tạo mới', 'advance' => 'Ứng tiền', 'overtime' => 'Yêu cầu tăng ca', 'violation' => 'Vi phạm', 'reward' => 'Khen thưởng', 'extra_work' => 'Phiếu làm thêm'] as $typeValue => $label): ?>
                <button class="request-action <?= e($typeClass[$typeValue] ?? 'blue') ?>" type="button" data-request-open data-request-type="<?= e($typeValue) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
    </header>

    <div class="request-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="request-filter" class="request-filter" method="get">
            <input type="hidden" name="route" value="requests">
            <label>
                <span>Nhân viên</span>
                <select name="employee" onchange="this.form.submit()">
                    <option value="">Tất cả</option>
                    <?php foreach ($employees as $employeeItem): ?>
                        <option value="<?= e($employeeItem['name'] ?? '') ?>" <?= $employee === ($employeeItem['name'] ?? '') ? 'selected' : '' ?>><?= e($employeeItem['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Bộ phận</span>
                <select name="department" onchange="this.form.submit()">
                    <option value="">--- ---</option>
                    <?php foreach ($departments as $departmentItem): ?>
                        <option value="<?= e($departmentItem) ?>" <?= $department === $departmentItem ? 'selected' : '' ?>><?= e($departmentItem) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Từ Ngày</span><input name="from_date" type="date" value="<?= e($fromDate) ?>"></label>
            <label><span>Đến Ngày</span><input name="to_date" type="date" value="<?= e($toDate) ?>"></label>
        </form>

        <div class="request-tools">
            <label>Hiển thị
                <select name="per_page" form="request-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="requests">
                <input type="hidden" name="employee" value="<?= e($employee) ?>">
                <input type="hidden" name="department" value="<?= e($department) ?>">
                <input type="hidden" name="from_date" value="<?= e($fromDate) ?>">
                <input type="hidden" name="to_date" value="<?= e($toDate) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm Kiếm">
            </form>
        </div>

        <div class="request-table-wrap">
            <table class="request-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th><a href="<?= e($sortUrl('request_type')) ?>">Loại yêu cầu <span><?= $sort === 'request_type' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('employee_name')) ?>">Người yêu cầu <span><?= $sort === 'employee_name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('department')) ?>">Bộ phận <span><?= $sort === 'department' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('created_date')) ?>">Ngày tạo <span><?= $sort === 'created_date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('start_date')) ?>">Ngày bắt đầu <span><?= $sort === 'start_date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('end_date')) ?>">Ngày kết thúc <span><?= $sort === 'end_date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Chi tiết</th>
                        <th><a href="<?= e($sortUrl('approval')) ?>">Phê duyệt <span><?= $sort === 'approval' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><?= e($types[$item['request_type'] ?? 'general'] ?? ($item['request_type'] ?? '')) ?></td>
                            <td>
                                <?php $profileLink = $employeeProfileLink((string) ($item['employee_name'] ?? '')); ?>
                                <?php if ($profileLink !== null): ?>
                                    <a class="employee-chip employee-name-link" href="<?= $profileLink ?>"><?= ui_icon('users') ?><?= e($item['employee_name'] ?? '') ?></a>
                                <?php else: ?>
                                    <span class="employee-chip"><?= ui_icon('users') ?><?= e($item['employee_name'] ?? '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($item['department'] ?? '') ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['created_date'] ?? 'now')))) ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['start_date'] ?? 'now')))) ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['end_date'] ?? 'now')))) ?></td>
                            <td><button class="insurance-detail" type="button" title="Xem chi tiết" data-request-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                            <td><strong class="request-approval <?= e($item['approval'] ?? 'pending') ?>"><?= e($approvals[$item['approval'] ?? 'pending'] ?? 'Chờ duyệt') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="request-empty" colspan="9">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination request-pagination">
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

<dialog id="request-dialog" class="employee-dialog request-dialog">
    <form method="post" action="?route=requests.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Tạo phiếu yêu cầu</h3><button type="button" data-request-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Loại yêu cầu</span><select name="request_type"><?php foreach ($types as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Người yêu cầu</span><select name="employee_name" required><option value="">-- Chọn nhân viên --</option><?php foreach ($employees as $employeeItem): ?><option value="<?= e($employeeItem['name'] ?? '') ?>" data-department="<?= e($employeeItem['department'] ?? '') ?>"><?= e($employeeItem['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Bộ phận</span><select name="department"><option value="">--- ---</option><?php foreach ($departments as $departmentItem): ?><option value="<?= e($departmentItem) ?>"><?= e($departmentItem) ?></option><?php endforeach; ?></select></label>
            <label><span>Ngày tạo</span><input name="created_date" type="date" required></label>
            <label><span>Ngày bắt đầu</span><input name="start_date" type="date" required></label>
            <label><span>Ngày kết thúc</span><input name="end_date" type="date" required></label>
            <label><span>Số tiền</span><input name="amount" type="number" min="0" step="1000"></label>
            <label><span>Phê duyệt</span><select name="approval"><?php foreach ($approvals as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label class="contract-note"><span>Nội dung yêu cầu</span><textarea name="detail" rows="4"></textarea></label>
        </div>
        <footer class="request-dialog-actions">
            <button class="employee-action danger request-delete" type="button">Xóa</button>
            <span></span>
            <button class="employee-action" type="button" data-request-close>Hủy</button>
            <button class="employee-action teal" type="submit">Lưu phiếu</button>
        </footer>
    </form>
</dialog>

<form id="request-delete-form" method="post" action="?route=requests.delete" hidden>
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id">
</form>

<script>
(() => {
    const dialog = document.getElementById('request-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const deleteForm = document.getElementById('request-delete-form');
    const deleteButton = dialog.querySelector('.request-delete');
    const today = new Date().toISOString().slice(0, 10);

    form.elements.employee_name?.addEventListener('change', () => {
        const option = form.elements.employee_name.selectedOptions[0];
        if (option?.dataset.department && form.elements.department) {
            form.elements.department.value = option.dataset.department;
        }
    });

    document.querySelectorAll('[data-request-open]').forEach(button => button.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.request_type.value = button.dataset.requestType || 'general';
        form.elements.created_date.value = today;
        form.elements.start_date.value = today;
        form.elements.end_date.value = today;
        form.elements.approval.value = 'pending';
        deleteButton.hidden = true;
        dialog.querySelector('h3').textContent = 'Tạo phiếu yêu cầu';
        dialog.showModal();
    }));

    document.querySelectorAll('[data-request-close]').forEach(button => button.addEventListener('click', () => dialog.close()));

    document.querySelectorAll('[data-request-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.requestEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key]) form.elements[key].value = value ?? '';
        });
        deleteButton.hidden = false;
        dialog.querySelector('h3').textContent = 'Chi tiết phiếu yêu cầu';
        dialog.showModal();
    }));

    deleteButton?.addEventListener('click', () => {
        if (!form.elements.id.value || !confirm('Xóa phiếu yêu cầu này?')) return;
        deleteForm.elements.id.value = form.elements.id.value;
        deleteForm.submit();
    });
})();
</script>
