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

<section class="violation-panel reward-panel">
    <header class="violation-head">
        <div><?= back_link('employees') ?><h2>DANH SÁCH PHIẾU KHEN THƯỞNG</h2></div>
        <button class="employee-action teal" type="button" data-reward-open>Lập phiếu</button>
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

        <form id="reward-filter" class="violation-filter" method="get">
            <input type="hidden" name="route" value="rewards">
            <label><span>Từ ngày</span><input name="from_date" type="date" value="<?= e($fromDate) ?>"></label>
            <label><span>Đến ngày</span><input name="to_date" type="date" value="<?= e($toDate) ?>"></label>
            <button type="submit">Lọc</button>
        </form>

        <div class="violation-tools">
            <label>Hiển thị
                <select name="per_page" form="reward-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="rewards">
                <input type="hidden" name="from_date" value="<?= e($fromDate) ?>">
                <input type="hidden" name="to_date" value="<?= e($toDate) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="violation-table-wrap">
            <table class="violation-table reward-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th><a href="<?= e($sortUrl('employee_name')) ?>">Người khen thưởng <span><?= $sort === 'employee_name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('reward_date')) ?>">Thời gian khen thưởng <span><?= $sort === 'reward_date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('reward_type')) ?>">Loại khen thưởng <span><?= $sort === 'reward_type' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Chi tiết</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><?= e($item['employee_name'] ?? '') ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['reward_date'] ?? 'now')))) ?></td>
                            <td><?= e($item['reward_type'] ?? '') ?></td>
                            <td><button class="insurance-detail" type="button" title="Xem chi tiết" data-reward-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                            <td>
                                <form method="post" action="?route=rewards.delete" onsubmit="return confirm('Xóa phiếu khen thưởng này?')">
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

<dialog id="reward-dialog" class="employee-dialog reward-dialog">
    <form method="post" action="?route=rewards.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Lập phiếu khen thưởng</h3><button type="button" data-reward-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Người được khen thưởng</span><select name="employee_name" required><option value="">-- Chọn nhân viên --</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['name'] ?? '') ?>"><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Thời gian khen thưởng</span><input name="reward_date" type="date" required></label>
            <label><span>Loại khen thưởng</span><select name="reward_type" required><option>Khen thưởng nhân viên xuất sắc</option><option>Đóng góp ý tưởng sáng tạo</option><option>Hoàn thành xuất sắc dự án</option><option>Hỗ trợ đồng đội</option><option>Thành tích bán hàng</option><option>Khác</option></select></label>
            <label><span>Mức thưởng</span><input name="amount" type="number" min="0" step="100000"></label>
            <label class="contract-note"><span>Số quyết định</span><input name="decision_number"></label>
            <label class="contract-note"><span>Nội dung khen thưởng</span><textarea name="description" rows="4"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-reward-close>Hủy</button><button class="employee-action teal" type="submit">Lưu phiếu</button></footer>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('reward-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    document.querySelector('[data-reward-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.reward_date.value = new Date().toISOString().slice(0, 10);
        dialog.querySelector('h3').textContent = 'Lập phiếu khen thưởng';
        dialog.showModal();
    });
    document.querySelectorAll('[data-reward-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-reward-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.rewardEdit);
        Object.entries(item).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
        dialog.querySelector('h3').textContent = 'Chi tiết phiếu khen thưởng';
        dialog.showModal();
    }));
})();
</script>
