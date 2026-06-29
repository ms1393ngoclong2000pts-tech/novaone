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
$stageColors = ['init' => 'red', 'quote' => 'blue', 'contract' => 'gold', 'paid' => 'gray'];
?>

<section class="sales-panel">
    <header class="sales-head">
        <h2>DANH SÁCH BÁN HÀNG</h2>
        <button class="employee-action blue" type="button" data-sales-open>Tạo mới</button>
    </header>

    <div class="sales-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <nav class="sales-stage-nav" aria-label="Giai đoạn đơn hàng">
            <?php $step = 1; foreach ($stages as $value => $label): ?>
                <a class="stage-card <?= e($stageColors[$value] ?? 'blue') ?> <?= $stage === $value || ($stage === '' && $value === 'init') ? 'active' : '' ?>" href="?route=sales-orders&amp;stage=<?= e($value) ?>">
                    <strong><?= $step++ ?></strong>
                    <span><?= e($label) ?><?= $value === 'init' ? ' ✓' : '' ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <form id="sales-filter" class="sales-filter" method="get">
            <input type="hidden" name="route" value="sales-orders">
            <input type="hidden" name="stage" value="<?= e($stage) ?>">
            <label><span>Từ Ngày</span><input name="start_date" type="date" value="<?= e($startDate) ?>"></label>
            <label><span>Đến ngày</span><input name="end_date" type="date" value="<?= e($endDate) ?>"></label>
            <label><span>Khách hàng</span><select name="customer" onchange="this.form.submit()"><option value="">--- Chọn khách hàng cá nhân---</option><?php foreach ($customers as $item): ?><option value="<?= e($item) ?>" <?= $customer === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
            <label><span>Nhóm khách hàng</span><select name="customer_group" onchange="this.form.submit()"><option value="">--- Chọn nhóm khách hàng---</option><?php foreach ($customerGroups as $item): ?><option value="<?= e($item) ?>" <?= $customerGroup === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
            <button class="employee-action blue" type="submit">Lọc</button>
        </form>

        <div class="sales-tools">
            <label>Hiển thị
                <select name="per_page" form="sales-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="sales-orders">
                <input type="hidden" name="stage" value="<?= e($stage) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="sales-table-wrap">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <?php foreach (['code' => 'MÃ ĐƠN HÀNG', 'name' => 'TÊN', 'stage' => 'GIAI ĐOẠN', 'contact' => 'NGƯỜI LIÊN HỆ', 'amount' => 'SỐ TIỀN / ĐƠN VỊ', 'created_date' => 'NGÀY TẠO'] as $column => $label): ?>
                            <th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <?php endforeach; ?>
                        <th>XÓA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="sales-link" type="button" data-sales-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['code'] ?? '') ?></button></td>
                            <td><?= e($item['name'] ?? '') ?></td>
                            <td><span class="sales-stage-badge <?= e($stageColors[$item['stage'] ?? 'init'] ?? 'blue') ?>"><?= e($stages[$item['stage'] ?? 'init'] ?? '') ?></span></td>
                            <td><?= e($item['contact'] ?? '') ?></td>
                            <td><?= e(number_format((float) ($item['amount'] ?? 0), 0, ',', '.')) ?> <?= e($item['unit'] ?? 'VNĐ') ?></td>
                            <td><?= e(date('d-m-Y', strtotime((string) ($item['created_date'] ?? 'now')))) ?></td>
                            <td>
                                <form method="post" action="?route=sales-orders.delete" onsubmit="return confirm('Xóa đơn hàng này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="sales-delete" type="submit"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?><tr><td class="sales-empty" colspan="8">Không có dữ liệu</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination sales-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> mục</span>
            <nav>
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a>
                <?php for ($number = 1; $number <= $pages; $number++): ?><a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a><?php endfor; ?>
                <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a>
            </nav>
        </footer>
    </div>
</section>

<dialog id="sales-dialog" class="employee-dialog sales-dialog">
    <form method="post" action="?route=sales-orders.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Tạo đơn hàng</h3><button type="button" data-sales-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Mã đơn hàng</span><input name="code" required></label>
            <label><span>Tên</span><input name="name" required></label>
            <label><span>Giai đoạn</span><select name="stage"><?php foreach ($stages as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Người liên hệ</span><select name="contact_employee_id" required><option value="">--- Chọn nhân viên ---</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['id'] ?? '') ?>"><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Số tiền</span><input name="amount" type="number" min="0" step="1000"></label>
            <label><span>Đơn vị</span><input name="unit" value="VNĐ"></label>
            <label><span>Ngày tạo</span><input name="created_date" type="date" required></label>
            <label><span>Khách hàng</span><input name="customer" list="sales-customers"></label>
            <label><span>Nhóm khách hàng</span><input name="customer_group" list="sales-customer-groups"></label>
            <label class="wide"><span>Ghi chú</span><textarea name="note" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-sales-close>Hủy</button><button class="employee-action teal" type="submit">Lưu đơn hàng</button></footer>
    </form>
</dialog>

<datalist id="sales-customers"><?php foreach ($customers as $item): ?><option value="<?= e($item) ?>"><?php endforeach; ?></datalist>
<datalist id="sales-customer-groups"><?php foreach ($customerGroups as $item): ?><option value="<?= e($item) ?>"><?php endforeach; ?></datalist>

<script>
(() => {
    const dialog = document.getElementById('sales-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    document.querySelector('[data-sales-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.stage.value = 'init';
        form.elements.unit.value = 'VNĐ';
        form.elements.created_date.value = new Date().toISOString().slice(0, 10);
        dialog.querySelector('h3').textContent = 'Tạo đơn hàng';
        dialog.showModal();
    });
    document.querySelectorAll('[data-sales-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-sales-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.salesEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value ?? ''; });
        if (form.elements.contact_employee_id && !item.contact_employee_id && item.contact) {
            const match = Array.from(form.elements.contact_employee_id.options).find(option => option.textContent.trim() === String(item.contact).trim());
            if (match) form.elements.contact_employee_id.value = match.value;
        }
        dialog.querySelector('h3').textContent = 'Cập nhật đơn hàng';
        dialog.showModal();
    }));
})();
</script>
