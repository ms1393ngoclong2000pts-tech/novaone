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
?>

<section class="machine-panel">
    <header class="machine-head">
        <h2>DANH SÁCH KHO</h2>
        <div class="machine-head-actions">
            <button class="employee-action teal" type="button" data-machine-open>Thêm mới kho</button>
            <button class="employee-action danger" type="button" data-machine-info="Phiếu phê duyệt kho máy đã sẵn sàng để xử lý.">Phiếu phê duyệt</button>
            <button class="employee-action amber" type="button" data-machine-info="Khuyến mãi hệ thống áp dụng cho các kho đủ điều kiện.">Khuyến mãi hệ thống</button>
        </div>
    </header>

    <div class="machine-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="machine-filter" class="machine-filter" method="get">
            <input type="hidden" name="route" value="machine-warehouses">
            <label>
                <span>Kho máy</span>
                <select name="warehouse" onchange="this.form.submit()">
                    <option value="">--- ---</option>
                    <?php foreach ($warehouses as $option): ?>
                        <option value="<?= e($option) ?>" <?= $warehouse === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <div class="machine-tools">
            <label>Hiển thị
                <select name="per_page" form="machine-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="machine-warehouses">
                <input type="hidden" name="warehouse" value="<?= e($warehouse) ?>">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="machine-table-wrap">
            <table class="machine-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th><a href="<?= e($sortUrl('name')) ?>">Tên kho <span><?= $sort === 'name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('project')) ?>">Dự án <span><?= $sort === 'project' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('manager')) ?>">Người quản lý <span><?= $sort === 'manager' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('keeper')) ?>">Nhân viên kho <span><?= $sort === 'keeper' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('address')) ?>">Địa chỉ <span><?= $sort === 'address' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('phone')) ?>">Số điện thoại <span><?= $sort === 'phone' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Chuyển kho</th>
                        <th>Sửa</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="machine-name" type="button" data-machine-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['name'] ?? '') ?></button></td>
                            <td><?= e($item['project'] ?? '') ?></td>
                            <td><a class="machine-link" href="?route=suppliers&q=<?= e(urlencode($item['manager'] ?? '')) ?>"><?= e($item['manager'] ?? '') ?></a></td>
                            <td><?= e($item['keeper'] ?? '') ?></td>
                            <td><?= e($item['address'] ?? '') ?></td>
                            <td><?= e($item['phone'] ?? '') ?></td>
                            <td><button class="machine-icon transfer" type="button" title="Chuyển kho" data-machine-transfer='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('arrow') ?></button></td>
                            <td><button class="machine-icon edit" type="button" title="Sửa" data-machine-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('edit') ?></button></td>
                            <td>
                                <form method="post" action="?route=machine-warehouses.delete" onsubmit="return confirm('Xóa kho này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="machine-icon delete" type="submit" title="Xóa"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="machine-empty" colspan="10">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination machine-pagination">
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

<dialog id="machine-dialog" class="employee-dialog machine-dialog">
    <form method="post" action="?route=machine-warehouses.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm mới kho</h3><button type="button" data-machine-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Tên kho</span><input name="name" required></label>
            <label>
                <span>Dự án</span>
                <select name="project">
                    <option value="">--- Dự án ---</option>
                    <?php foreach ($projects as $projectName): ?>
                        <option value="<?= e($projectName) ?>"><?= e($projectName) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Người quản lý</span><input name="manager"></label>
            <label><span>Nhân viên kho</span><input name="keeper"></label>
            <label><span>Địa chỉ</span><input name="address"></label>
            <label><span>Số điện thoại</span><input name="phone"></label>
            <label class="contract-note"><span>Ghi chú</span><textarea name="note" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-machine-close>Hủy</button><button class="employee-action teal" type="submit">Lưu kho</button></footer>
    </form>
</dialog>

<dialog id="machine-transfer-dialog" class="employee-dialog machine-dialog">
    <form method="post" action="?route=machine-warehouses.transfer" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Chuyển kho</h3><button type="button" data-machine-transfer-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Kho hiện tại</span><input name="current" disabled></label>
            <label><span>Chuyển đến</span><input name="target" list="machine-warehouse-options" required></label>
            <label class="contract-note"><span>Ghi chú</span><input name="note" disabled></label>
        </div>
        <footer><button class="employee-action" type="button" data-machine-transfer-close>Hủy</button><button class="employee-action violet" type="submit">Tạo phiếu chuyển</button></footer>
    </form>
</dialog>

<dialog id="machine-info-dialog" class="employee-dialog machine-dialog">
    <form method="dialog" class="employee-dialog-form">
        <header><h3>Thông báo</h3><button type="button" data-machine-info-close aria-label="Đóng">×</button></header>
        <div class="machine-info-message"></div>
        <footer><button class="employee-action teal" type="button" data-machine-info-close>Đóng</button></footer>
    </form>
</dialog>

<datalist id="machine-warehouse-options">
    <?php foreach ($warehouses as $option): ?><option value="<?= e($option) ?>"><?php endforeach; ?>
</datalist>

<script>
(() => {
    const dialog = document.getElementById('machine-dialog');
    const transferDialog = document.getElementById('machine-transfer-dialog');
    const infoDialog = document.getElementById('machine-info-dialog');
    if (!dialog || !transferDialog || !infoDialog) return;
    const form = dialog.querySelector('form');
    const transferForm = transferDialog.querySelector('form');
    const infoMessage = infoDialog.querySelector('.machine-info-message');

    document.querySelector('[data-machine-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        dialog.querySelector('h3').textContent = 'Thêm mới kho';
        dialog.showModal();
    });

    document.querySelectorAll('[data-machine-close]').forEach(button => button.addEventListener('click', () => dialog.close()));

    document.querySelectorAll('[data-machine-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.machineEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key]) form.elements[key].value = value ?? '';
        });
        dialog.querySelector('h3').textContent = 'Cập nhật kho';
        dialog.showModal();
    }));

    document.querySelectorAll('[data-machine-transfer]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.machineTransfer);
        transferForm.reset();
        transferForm.elements.id.value = item.id || '';
        transferForm.elements.current.value = item.name || '';
        transferForm.elements.note.value = item.note || '';
        transferDialog.showModal();
    }));

    document.querySelectorAll('[data-machine-transfer-close]').forEach(button => button.addEventListener('click', () => transferDialog.close()));

    document.querySelectorAll('[data-machine-info]').forEach(button => button.addEventListener('click', () => {
        infoMessage.textContent = button.dataset.machineInfo || '';
        infoDialog.showModal();
    }));
    document.querySelectorAll('[data-machine-info-close]').forEach(button => button.addEventListener('click', () => infoDialog.close()));
})();
</script>
