<?php
$queryUrl = function (array $changes = []): string {
    $values = array_merge($_GET, $changes);
    foreach ($values as $key => $value) {
        if ($value === '' || $value === null) {
            unset($values[$key]);
        }
    }
    return '?' . http_build_query($values);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
?>

<section class="violation-panel">
    <header class="violation-head">
        <div><a class="violation-back" href="?route=employees" title="Quay lại">←</a><h2>DANH SÁCH PHIẾU VI PHẠM</h2></div>
        <button class="employee-action teal" type="button" data-violation-open>Lập phiếu</button>
    </header>

    <div class="violation-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="violation-filter" class="violation-filter" method="get">
            <input type="hidden" name="route" value="violations">
            <label><span>Từ ngày</span><input name="from_date" type="date" value="<?= e($fromDate) ?>"></label>
            <label><span>Đến ngày</span><input name="to_date" type="date" value="<?= e($toDate) ?>"></label>
            <button type="submit">Lọc</button>
        </form>

        <div class="violation-tools">
            <label>Hiển thị
                <select name="per_page" form="violation-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="violations">
                <input type="hidden" name="from_date" value="<?= e($fromDate) ?>">
                <input type="hidden" name="to_date" value="<?= e($toDate) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="violation-table-wrap">
            <table class="violation-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th><a href="<?= e($sortUrl('employee_name')) ?>">Người vi phạm <span><?= $sort === 'employee_name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('violation_date')) ?>">Thời gian vi phạm <span><?= $sort === 'violation_date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('violation_type')) ?>">Loại vi phạm <span><?= $sort === 'violation_type' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Chi tiết</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><?= e($item['employee_name'] ?? '') ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['violation_date'] ?? 'now')))) ?></td>
                            <td><?= e($item['violation_type'] ?? '') ?></td>
                            <td><button class="insurance-detail" type="button" title="Xem chi tiết" data-violation-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                            <td>
                                <form method="post" action="?route=violations.delete" onsubmit="return confirm('Xóa phiếu vi phạm này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="violation-delete" type="submit" title="Xóa">×</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="contract-empty" colspan="6">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination violation-pagination">
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

<dialog id="violation-dialog" class="employee-dialog violation-dialog">
    <form method="post" action="?route=violations.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Lập phiếu vi phạm</h3><button type="button" data-violation-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Người vi phạm</span><select name="employee_name" required><option value="">-- Chọn nhân viên --</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['name'] ?? '') ?>"><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Thời gian vi phạm</span><input name="violation_date" type="date" required></label>
            <label><span>Loại vi phạm</span><select name="violation_type" required><option>Vi phạm về thời gian làm việc</option><option>Vi phạm nội quy</option><option>Vi phạm quy trình kho</option><option>Vi phạm bảo mật thông tin</option><option>Vi phạm sử dụng tài sản</option><option>Khác</option></select></label>
            <label><span>Mức phạt</span><input name="penalty" type="number" min="0" step="50000"></label>
            <label class="contract-note"><span>Nội dung vi phạm</span><textarea name="description" rows="3"></textarea></label>
            <label class="contract-note"><span>Hình thức xử lý</span><textarea name="resolution" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-violation-close>Hủy</button><button class="employee-action teal" type="submit">Lưu phiếu</button></footer>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('violation-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    document.querySelector('[data-violation-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.violation_date.value = new Date().toISOString().slice(0, 10);
        dialog.querySelector('h3').textContent = 'Lập phiếu vi phạm';
        dialog.showModal();
    });
    document.querySelectorAll('[data-violation-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-violation-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.violationEdit);
        Object.entries(item).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
        dialog.querySelector('h3').textContent = 'Chi tiết phiếu vi phạm';
        dialog.showModal();
    }));
})();
</script>
