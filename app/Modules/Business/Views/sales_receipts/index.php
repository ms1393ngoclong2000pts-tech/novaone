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
$formatDate = fn (string $date): string => $date !== '' ? date('d/m/Y', strtotime($date)) : '';
?>

<section class="sales-receipt-panel">
    <header class="sales-receipt-head">
        <h2>PHIẾU BÁN HÀNG</h2>
        <button class="employee-action blue" type="button" data-receipt-open>Thêm mới phiếu</button>
    </header>

    <div class="sales-receipt-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="sales-receipt-tools">
            <label>Hiển thị
                <select name="per_page" form="receipt-search" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form id="receipt-search" method="get">
                <input type="hidden" name="route" value="sales-receipts">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="sales-receipt-table-wrap">
            <table class="sales-receipt-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <?php foreach ([
                            'code' => 'MÃ ĐƠN HÀNG',
                            'customer' => 'KHÁCH HÀNG',
                            'created_date' => 'NGÀY TẠO',
                            'address' => 'ĐỊA CHỈ',
                            'phone' => 'SỐ ĐIỆN THOẠI',
                        ] as $column => $label): ?>
                            <th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <?php endforeach; ?>
                        <th>XEM CHI TIẾT</th>
                        <th>XÓA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="receipt-code" type="button" data-receipt-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['code'] ?? '') ?></button></td>
                            <td><?= e($item['customer'] ?? '') ?></td>
                            <td><?= e($formatDate((string) ($item['created_date'] ?? ''))) ?></td>
                            <td><?= e($item['address'] ?? '') ?></td>
                            <td><?= e($item['phone'] ?? '') ?></td>
                            <td><button class="receipt-info" type="button" data-receipt-detail='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                            <td>
                                <form method="post" action="?route=sales-receipts.delete" onsubmit="return confirm('Xóa phiếu bán hàng này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="receipt-delete" type="submit"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?><tr><td class="receipt-empty" colspan="8">Không có dữ liệu</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination sales-receipt-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> mục</span>
            <nav>
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a>
                <?php for ($number = 1; $number <= $pages; $number++): ?><a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a><?php endfor; ?>
                <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a>
            </nav>
        </footer>
    </div>
</section>

<dialog id="receipt-dialog" class="employee-dialog receipt-dialog">
    <form method="post" action="?route=sales-receipts.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm mới phiếu bán hàng</h3><button type="button" data-receipt-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Mã đơn hàng</span><input name="code" required></label>
            <label><span>Khách hàng</span><input name="customer" list="receipt-customers"></label>
            <label><span>Ngày tạo</span><input name="created_date" type="date" required></label>
            <label><span>Số điện thoại</span><input name="phone" inputmode="tel"></label>
            <label><span>Tổng tiền</span><input name="total" type="number" min="0" step="1000"></label>
            <label><span>Địa chỉ</span><input name="address"></label>
            <label class="wide"><span>Ghi chú</span><textarea name="note" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-receipt-close>Hủy</button><button class="employee-action teal" type="submit">Lưu phiếu</button></footer>
    </form>
</dialog>

<dialog id="receipt-detail-dialog" class="employee-dialog receipt-detail-dialog">
    <form method="dialog" class="employee-dialog-form">
        <header><h3>Chi tiết phiếu bán hàng</h3><button type="submit" aria-label="Đóng">×</button></header>
        <dl class="receipt-detail-list">
            <div><dt>Mã đơn hàng</dt><dd data-detail="code"></dd></div>
            <div><dt>Khách hàng</dt><dd data-detail="customer"></dd></div>
            <div><dt>Ngày tạo</dt><dd data-detail="created_date"></dd></div>
            <div><dt>Địa chỉ</dt><dd data-detail="address"></dd></div>
            <div><dt>Số điện thoại</dt><dd data-detail="phone"></dd></div>
            <div><dt>Tổng tiền</dt><dd data-detail="total"></dd></div>
            <div class="wide"><dt>Ghi chú</dt><dd data-detail="note"></dd></div>
        </dl>
        <footer><button class="employee-action blue" type="submit">Đóng</button></footer>
    </form>
</dialog>

<datalist id="receipt-customers"><?php foreach ($customers as $item): ?><option value="<?= e($item) ?>"><?php endforeach; ?></datalist>

<script>
(() => {
    const dialog = document.getElementById('receipt-dialog');
    const detailDialog = document.getElementById('receipt-detail-dialog');
    if (!dialog || !detailDialog) return;
    const form = dialog.querySelector('form');
    const formatDate = value => value ? value.split('-').reverse().join('/') : '';
    const formatMoney = value => `${Number(value || 0).toLocaleString('vi-VN')} VNĐ`;

    document.querySelector('[data-receipt-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.created_date.value = new Date().toISOString().slice(0, 10);
        dialog.querySelector('h3').textContent = 'Thêm mới phiếu bán hàng';
        dialog.showModal();
    });

    document.querySelectorAll('[data-receipt-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-receipt-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.receiptEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key]) form.elements[key].value = value ?? '';
        });
        dialog.querySelector('h3').textContent = 'Cập nhật phiếu bán hàng';
        dialog.showModal();
    }));

    document.querySelectorAll('[data-receipt-detail]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.receiptDetail);
        detailDialog.querySelector('[data-detail="code"]').textContent = item.code || '';
        detailDialog.querySelector('[data-detail="customer"]').textContent = item.customer || '';
        detailDialog.querySelector('[data-detail="created_date"]').textContent = formatDate(item.created_date || '');
        detailDialog.querySelector('[data-detail="address"]').textContent = item.address || '';
        detailDialog.querySelector('[data-detail="phone"]').textContent = item.phone || '';
        detailDialog.querySelector('[data-detail="total"]').textContent = formatMoney(item.total);
        detailDialog.querySelector('[data-detail="note"]').textContent = item.note || '';
        detailDialog.showModal();
    }));
})();
</script>
