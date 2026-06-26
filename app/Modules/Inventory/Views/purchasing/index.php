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
$statusClass = ['new' => 'approved', 'purchased' => 'pending', 'received' => 'approved', 'debt' => 'rejected', 'canceled' => 'rejected'];
$employeeNames = array_values(array_filter(array_map(
    fn (array $employee): string => trim((string) ($employee['name'] ?? '')),
    $employees
)));
?>

<section class="request-panel purchase-panel">
    <header class="request-head">
        <h2>DANH SÁCH YÊU CẦU</h2>
        <div class="request-head-actions">
            <a class="request-action blue" href="?route=equipment-devices">Danh mục thiết bị</a>
            <button class="request-action teal" type="button" data-purchase-open>Tạo phiếu yêu cầu thiết bị</button>
            <button class="request-action violet" type="button" data-purchase-open data-purchase-status="purchased">Tạo phiếu mua sắm</button>
            <button class="request-action amber" type="button" data-purchase-open data-purchase-status="received">Phiếu thực nhận</button>
            <button class="request-action pink" type="button" data-purchase-open data-purchase-status="debt">Công nợ</button>
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

        <form id="purchase-filter" class="request-filter purchase-filter" method="get">
            <input type="hidden" name="route" value="purchasing">
            <label>
                <span>Bộ phận</span>
                <select name="department" onchange="this.form.submit()">
                    <option value="">--- Chọn bộ phận ---</option>
                    <?php foreach ($departments as $departmentItem): ?>
                        <option value="<?= e($departmentItem) ?>" <?= $department === $departmentItem ? 'selected' : '' ?>><?= e($departmentItem) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Trạng thái</span>
                <select name="status" onchange="this.form.submit()">
                    <option value="">--- Chọn trạng thái ---</option>
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <div class="request-tools">
            <label>Hiển thị
                <select name="per_page" form="purchase-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="purchasing">
                <input type="hidden" name="department" value="<?= e($department) ?>">
                <input type="hidden" name="status" value="<?= e($status) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="request-table-wrap purchase-table-wrap">
            <table class="request-table purchase-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th><a href="<?= e($sortUrl('voucher_no')) ?>">Mã phiếu <span><?= $sort === 'voucher_no' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('requester')) ?>">Người yêu cầu <span><?= $sort === 'requester' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('status')) ?>">Trạng thái <span><?= $sort === 'status' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('needed_date')) ?>">Ngày cần <span><?= $sort === 'needed_date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('receiver')) ?>">Người nhận yêu cầu <span><?= $sort === 'receiver' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('approver')) ?>">Người phê duyệt <span><?= $sort === 'approver' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Thông tin</th>
                        <th>Phê duyệt</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="purchase-code" type="button" data-purchase-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['voucher_no'] ?? '') ?></button></td>
                            <td><button class="machine-name" type="button" data-purchase-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['requester'] ?? '') ?></button></td>
                            <td><strong class="request-approval <?= e($statusClass[$item['status'] ?? 'new'] ?? 'pending') ?>"><?= e($statuses[$item['status'] ?? 'new'] ?? '') ?></strong></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['needed_date'] ?? 'now')))) ?></td>
                            <td><button class="machine-name" type="button" data-purchase-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['receiver'] ?? '') ?></button></td>
                            <td><button class="machine-name" type="button" data-purchase-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['approver'] ?? '') ?></button></td>
                            <td><button class="insurance-detail" type="button" title="Thông tin" data-purchase-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                            <td>
                                <form method="post" action="?route=purchasing.approval">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="purchase-approve" type="submit" title="Đổi trạng thái"><?= ui_icon('arrow') ?></button>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="?route=purchasing.delete" onsubmit="return confirm('Xóa phiếu mua sắm này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="machine-icon delete" type="submit" title="Xóa"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="request-empty" colspan="10">Không có dữ liệu</td></tr>
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

<dialog id="purchase-dialog" class="employee-dialog request-dialog">
    <form method="post" action="?route=purchasing.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="voucher_no" value="">
        <header><h3>Tạo phiếu yêu cầu thiết bị</h3><button type="button" data-purchase-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Người yêu cầu</span><input name="requester" list="purchase-people" required></label>
            <label><span>Bộ phận</span><select name="department"><option value="">--- Bộ phận ---</option><?php foreach ($departments as $departmentItem): ?><option value="<?= e($departmentItem) ?>"><?= e($departmentItem) ?></option><?php endforeach; ?></select></label>
            <label><span>Trạng thái</span><select name="status"><?php foreach ($statuses as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Ngày cần</span><input name="needed_date" type="date" required></label>
            <label><span>Người nhận yêu cầu</span><input name="receiver" list="purchase-people"></label>
            <label><span>Người phê duyệt</span><input name="approver" list="purchase-people"></label>
            <label><span>Phiếu vượt</span><select name="over_budget"><option value="">Không</option><option value="1">Có</option></select></label>
            <label class="contract-note"><span>Thông tin</span><textarea name="detail" rows="4"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-purchase-close>Hủy</button><button class="employee-action teal" type="submit">Lưu phiếu</button></footer>
    </form>
</dialog>

<datalist id="purchase-people">
    <option value="bData co.,ltd">
    <option value="Trần Thị Thu Nguyên">
    <?php foreach ($employees as $employee): ?><option value="<?= e($employee['name'] ?? '') ?>"><?php endforeach; ?>
</datalist>

<script>
(() => {
    const dialog = document.getElementById('purchase-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const employeeNames = <?= json_encode($employeeNames, JSON_UNESCAPED_UNICODE) ?>;
    const today = new Date().toISOString().slice(0, 10);

    const buildEmployeeSelect = (fieldName, required = false) => {
        const current = form.elements[fieldName];
        if (!current || current.tagName === 'SELECT') return;

        const select = document.createElement('select');
        select.name = fieldName;
        select.required = required;

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '--- Chọn nhân viên ---';
        select.appendChild(placeholder);

        employeeNames.forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            select.appendChild(option);
        });

        current.replaceWith(select);
    };

    buildEmployeeSelect('requester', true);
    buildEmployeeSelect('receiver');
    buildEmployeeSelect('approver');

    document.querySelectorAll('[data-purchase-open]').forEach(button => button.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.voucher_no.value = '';
        form.elements.status.value = button.dataset.purchaseStatus || 'new';
        form.elements.needed_date.value = today;
        dialog.querySelector('h3').textContent = 'Tạo phiếu yêu cầu thiết bị';
        dialog.showModal();
    }));

    document.querySelectorAll('[data-purchase-close]').forEach(button => button.addEventListener('click', () => dialog.close()));

    document.querySelectorAll('[data-purchase-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.purchaseEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key]) {
                form.elements[key].value = key === 'over_budget' ? (value ? '1' : '') : (value ?? '');
            }
        });
        dialog.querySelector('h3').textContent = 'Chi tiết phiếu mua sắm';
        dialog.showModal();
    }));
})();
</script>
