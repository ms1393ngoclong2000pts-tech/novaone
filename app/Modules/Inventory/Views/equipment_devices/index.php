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
?>

<section class="machine-panel device-panel">
    <header class="machine-head">
        <h2>DANH SÁCH THIẾT BỊ</h2>
        <div class="machine-head-actions">
            <button class="employee-action teal" type="button" data-device-open>Thêm mới thiết bị</button>
            <button class="employee-action blue" type="button" data-device-import-open>Nhập dữ liệu</button>
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

        <form id="device-filter" method="get"></form>
        <input form="device-filter" type="hidden" name="route" value="equipment-devices">
        <input form="device-filter" type="hidden" name="q" value="<?= e($query) ?>">

        <div class="machine-tools">
            <label>Hiển thị
                <select name="per_page" form="device-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="equipment-devices">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="machine-table-wrap">
            <table class="machine-table device-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th><a href="<?= e($sortUrl('name')) ?>">Tên hàng <span><?= $sort === 'name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('code')) ?>">Mã hàng <span><?= $sort === 'code' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('unit_price')) ?>">Đơn giá <span><?= $sort === 'unit_price' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('supplier')) ?>">Nhà cung cấp <span><?= $sort === 'supplier' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('unit')) ?>">Đơn vị tính <span><?= $sort === 'unit' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Xem</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="machine-name device-name" type="button" data-device-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('box') ?><?= e($item['name'] ?? '') ?></button></td>
                            <td><?= e($item['code'] ?? '') ?></td>
                            <td><?= e(number_format((float) ($item['unit_price'] ?? 0), 0, ',', ',')) ?></td>
                            <td><?php if (($item['supplier'] ?? '') !== ''): ?><a class="machine-link" href="?route=suppliers&amp;q=<?= e(urlencode((string) ($item['supplier'] ?? ''))) ?>"><?= e($item['supplier'] ?? '') ?></a><?php endif; ?></td>
                            <td><?= e($item['unit'] ?? '') ?></td>
                            <td><button class="machine-icon view" type="button" title="Xem chi tiết" data-device-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('eye') ?></button></td>
                            <td>
                                <form method="post" action="?route=equipment-devices.delete" onsubmit="return confirm('Xóa thiết bị này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="machine-icon delete" type="submit" title="Xóa"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="machine-empty" colspan="8">Không có dữ liệu</td></tr>
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

<dialog id="device-dialog" class="employee-dialog machine-dialog">
    <form method="post" action="?route=equipment-devices.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm mới thiết bị</h3><button type="button" data-device-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Tên hàng</span><input name="name" required></label>
            <label><span>Mã hàng</span><input name="code" required></label>
            <label><span>Đơn giá</span><input name="unit_price" type="number" min="0" step="1000" required></label>
            <label><span>Nhà cung cấp</span><input name="supplier" list="device-suppliers"></label>
            <label><span>Đơn vị tính</span><input name="unit" value="cái"></label>
            <label class="contract-note"><span>Ghi chú</span><textarea name="note" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-device-close>Hủy</button><button class="employee-action teal" type="submit">Lưu thiết bị</button></footer>
    </form>
</dialog>

<dialog id="device-import-dialog" class="employee-dialog machine-dialog">
    <form method="post" action="?route=equipment-devices.import" enctype="multipart/form-data" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <header><h3>Nhập dữ liệu thiết bị</h3><button type="button" data-device-import-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label class="contract-note"><span>File CSV/XLSX</span><input name="device_file" type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></label>
            <label class="contract-note"><span>Cột bắt buộc</span><input value="name, code, unit_price, supplier, unit" disabled></label>
        </div>
        <footer><button class="employee-action" type="button" data-device-import-close>Hủy</button><button class="employee-action blue" type="submit">Nhập dữ liệu</button></footer>
    </form>
</dialog>

<datalist id="device-suppliers">
    <?php foreach ($suppliers as $supplier): ?><option value="<?= e($supplier) ?>"><?php endforeach; ?>
</datalist>

<script>
(() => {
    const dialog = document.getElementById('device-dialog');
    const importDialog = document.getElementById('device-import-dialog');
    if (!dialog || !importDialog) return;
    const form = dialog.querySelector('form');

    document.querySelector('[data-device-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.unit.value = 'cái';
        dialog.querySelector('h3').textContent = 'Thêm mới thiết bị';
        dialog.showModal();
    });

    document.querySelectorAll('[data-device-close]').forEach(button => button.addEventListener('click', () => dialog.close()));

    document.querySelectorAll('[data-device-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.deviceEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key]) form.elements[key].value = value ?? '';
        });
        dialog.querySelector('h3').textContent = 'Chi tiết thiết bị';
        dialog.showModal();
    }));

    document.querySelector('[data-device-import-open]')?.addEventListener('click', () => importDialog.showModal());
    document.querySelectorAll('[data-device-import-close]').forEach(button => button.addEventListener('click', () => importDialog.close()));
})();
</script>
