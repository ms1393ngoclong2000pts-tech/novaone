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
$formatNumber = fn (float|int|string $value): string => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
?>

<section class="monthly-target-panel">
    <header class="monthly-target-head">
        <h2>CHỈ TIÊU THÁNG</h2>
        <button class="employee-action blue" type="button" data-target-open>Thêm mới chỉ tiêu</button>
    </header>

    <div class="monthly-target-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="target-filter" class="monthly-target-filter" method="get">
            <input type="hidden" name="route" value="sales-targets">
            <label>
                <span>Tháng</span>
                <select name="month" onchange="this.form.submit()">
                    <option value="">--- Chọn tháng ---</option>
                    <?php for ($itemMonth = 1; $itemMonth <= 12; $itemMonth++): ?>
                        <option value="<?= $itemMonth ?>" <?= $month === (string) $itemMonth ? 'selected' : '' ?>>Tháng <?= $itemMonth ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label>
                <span>Năm</span>
                <select name="year" onchange="this.form.submit()">
                    <option value="">--- Chọn năm ---</option>
                    <?php foreach ($years as $itemYear): ?>
                        <option value="<?= e($itemYear) ?>" <?= $year === (string) $itemYear ? 'selected' : '' ?>><?= e($itemYear) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Nhân viên quản lý</span>
                <select name="manager_id" onchange="this.form.submit()">
                    <option value="">--- Nhân viên ---</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= e($employee['id'] ?? '') ?>" <?= $managerId === ($employee['id'] ?? '') ? 'selected' : '' ?>><?= e($employee['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <div class="monthly-target-tools">
            <label>Hiển thị
                <select name="per_page" form="target-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="sales-targets">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="monthly-target-table-wrap">
            <table class="monthly-target-table">
                <thead>
                    <tr>
                        <?php foreach ([
                            'month' => 'THÁNG',
                            'year' => 'NĂM',
                            'revenue' => 'DOANH SỐ',
                            'quantity' => 'SẢN LƯỢNG',
                            'created_date' => 'NGÀY TẠO',
                            'customer' => 'TÊN KHÁCH HÀNG',
                            'contract' => 'HỢP ĐỒNG',
                            'manager' => 'NHÂN VIÊN QUẢN LÝ',
                        ] as $column => $label): ?>
                            <th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <?php endforeach; ?>
                        <th>XÓA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><button class="target-link" type="button" data-target-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['month'] ?? '') ?></button></td>
                            <td><?= e($item['year'] ?? '') ?></td>
                            <td><?= e($formatNumber($item['revenue'] ?? 0)) ?></td>
                            <td><?= e($formatNumber($item['quantity'] ?? 0)) ?></td>
                            <td><?= e(date('d-m-Y', strtotime((string) ($item['created_date'] ?? 'now')))) ?></td>
                            <td><?= e($item['customer'] ?? '') ?></td>
                            <td><?= e($item['contract'] ?? '') ?></td>
                            <td><?= e($item['manager'] ?? '') ?></td>
                            <td>
                                <form method="post" action="?route=sales-targets.delete" onsubmit="return confirm('Xóa chỉ tiêu này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="target-delete" type="submit"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?><tr><td class="target-empty" colspan="9">Không có dữ liệu</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination monthly-target-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> mục</span>
            <nav>
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a>
                <?php for ($number = 1; $number <= $pages; $number++): ?><a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a><?php endfor; ?>
                <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a>
            </nav>
        </footer>
    </div>
</section>

<dialog id="target-dialog" class="employee-dialog target-dialog">
    <form method="post" action="?route=sales-targets.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm mới chỉ tiêu</h3><button type="button" data-target-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Tháng</span><select name="month" required><?php for ($itemMonth = 1; $itemMonth <= 12; $itemMonth++): ?><option value="<?= $itemMonth ?>">Tháng <?= $itemMonth ?></option><?php endfor; ?></select></label>
            <label><span>Năm</span><input name="year" type="number" min="2000" max="2100" required></label>
            <label><span>Doanh số</span><input name="revenue" type="number" min="0" step="0.01" required></label>
            <label><span>Sản lượng</span><input name="quantity" type="number" min="0" step="0.01" required></label>
            <label><span>Ngày tạo</span><input name="created_date" type="date" required></label>
            <label><span>Tên khách hàng</span><input name="customer" list="target-customers"></label>
            <label><span>Hợp đồng</span><input name="contract" list="target-contracts"></label>
            <label><span>Nhân viên quản lý</span><select name="manager_id" required><option value="">--- Nhân viên ---</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['id'] ?? '') ?>"><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label class="wide"><span>Ghi chú</span><textarea name="note" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-target-close>Hủy</button><button class="employee-action teal" type="submit">Lưu chỉ tiêu</button></footer>
    </form>
</dialog>

<datalist id="target-customers"><?php foreach ($customers as $item): ?><option value="<?= e($item) ?>"><?php endforeach; ?></datalist>
<datalist id="target-contracts"><?php foreach ($contracts as $item): ?><option value="<?= e($item) ?>"><?php endforeach; ?></datalist>

<script>
(() => {
    const dialog = document.getElementById('target-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const today = new Date().toISOString().slice(0, 10);
    document.querySelector('[data-target-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.month.value = String(new Date().getMonth() + 1);
        form.elements.year.value = String(new Date().getFullYear());
        form.elements.created_date.value = today;
        dialog.querySelector('h3').textContent = 'Thêm mới chỉ tiêu';
        dialog.showModal();
    });
    document.querySelectorAll('[data-target-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-target-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.targetEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key]) form.elements[key].value = value ?? '';
        });
        dialog.querySelector('h3').textContent = 'Cập nhật chỉ tiêu';
        dialog.showModal();
    }));
})();
</script>
