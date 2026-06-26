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

<section class="supplier-panel">
    <header class="supplier-head">
        <h2>DANH SÁCH NHÀ CUNG CẤP</h2>
        <div class="supplier-head-actions">
            <button class="employee-action blue" type="button" data-supplier-open>Thêm mới</button>
            <button class="employee-action amber" type="button" data-supplier-import-open>Nhập excel</button>
        </div>
    </header>

    <div class="supplier-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="supplier-tools">
            <label>Hiển thị
                <select name="per_page" form="supplier-search" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form id="supplier-search" method="get">
                <input type="hidden" name="route" value="suppliers">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm:">
            </form>
        </div>

        <div class="supplier-table-wrap">
            <table class="supplier-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th><a href="<?= e($sortUrl('code')) ?>">Mã nhà cung cấp <span><?= $sort === 'code' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('name')) ?>">Tên nhà cung cấp <span><?= $sort === 'name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Thông tin</th>
                        <th>Sửa</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><span class="supplier-code"><?= ui_icon('box') ?><?= e($item['code'] ?? '') ?></span></td>
                            <td><?= e($item['name'] ?? '') ?></td>
                            <td><button class="supplier-icon info" type="button" title="Thông tin" data-supplier-view='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                            <td><button class="supplier-icon edit" type="button" title="Sửa" data-supplier-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('edit') ?></button></td>
                            <td>
                                <form method="post" action="?route=suppliers.delete" onsubmit="return confirm('Xóa nhà cung cấp này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="supplier-icon delete" type="submit" title="Xóa"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="supplier-empty" colspan="6">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination supplier-pagination">
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

<dialog id="supplier-dialog" class="employee-dialog supplier-dialog">
    <form method="post" action="?route=suppliers.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm nhà cung cấp</h3><button type="button" data-supplier-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Mã nhà cung cấp</span><input name="code" required></label>
            <label><span>Tên nhà cung cấp</span><input name="name" required></label>
            <label><span>Danh mục</span><input name="category"></label>
            <label><span>Điện thoại</span><input name="phone"></label>
            <label><span>Email</span><input name="email" type="email"></label>
            <label><span>Người liên hệ</span><input name="contact_person"></label>
            <label class="contract-note"><span>Địa chỉ</span><input name="address"></label>
            <label><span>Công nợ</span><input name="debt" type="number" min="0" step="1000"></label>
            <label><span>Trạng thái</span><select name="status"><option value="active">Hoạt động</option><option value="inactive">Ngưng</option></select></label>
            <label class="contract-note"><span>Ghi chú</span><textarea name="note" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-supplier-close>Hủy</button><button class="employee-action teal" type="submit">Lưu nhà cung cấp</button></footer>
    </form>
</dialog>

<dialog id="supplier-view-dialog" class="employee-dialog supplier-dialog">
    <form class="employee-dialog-form" method="dialog">
        <header><h3>Thông tin nhà cung cấp</h3><button type="button" data-supplier-view-close aria-label="Đóng">×</button></header>
        <div class="supplier-detail-list"></div>
        <footer><button class="employee-action teal" type="button" data-supplier-view-close>Đóng</button></footer>
    </form>
</dialog>

<dialog id="supplier-import-dialog" class="employee-dialog supplier-dialog">
    <form method="post" action="?route=suppliers.import" enctype="multipart/form-data" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <header><h3>Nhập excel nhà cung cấp</h3><button type="button" data-supplier-import-close aria-label="Đóng">×</button></header>
        <div class="supplier-import-note">
            <p>File hỗ trợ `.xlsx` hoặc `.csv` với cột: mã, tên, danh mục, điện thoại, email, địa chỉ, người liên hệ, công nợ, ghi chú.</p>
            <input name="supplier_file" type="file" accept=".xlsx,.csv" required>
        </div>
        <footer><button class="employee-action" type="button" data-supplier-import-close>Hủy</button><button class="employee-action amber" type="submit">Nhập excel</button></footer>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('supplier-dialog');
    const viewDialog = document.getElementById('supplier-view-dialog');
    const importDialog = document.getElementById('supplier-import-dialog');
    if (!dialog || !viewDialog || !importDialog) return;
    const form = dialog.querySelector('form');
    const details = viewDialog.querySelector('.supplier-detail-list');
    const fields = {
        code: 'Mã nhà cung cấp',
        name: 'Tên nhà cung cấp',
        category: 'Danh mục',
        phone: 'Điện thoại',
        email: 'Email',
        contact_person: 'Người liên hệ',
        address: 'Địa chỉ',
        debt: 'Công nợ',
        status: 'Trạng thái',
        note: 'Ghi chú'
    };
    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    document.querySelector('[data-supplier-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.status.value = 'active';
        dialog.querySelector('h3').textContent = 'Thêm nhà cung cấp';
        dialog.showModal();
    });

    document.querySelectorAll('[data-supplier-close]').forEach(button => button.addEventListener('click', () => dialog.close()));

    document.querySelectorAll('[data-supplier-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.supplierEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key]) form.elements[key].value = value ?? '';
        });
        dialog.querySelector('h3').textContent = 'Cập nhật nhà cung cấp';
        dialog.showModal();
    }));

    document.querySelectorAll('[data-supplier-view]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.supplierView);
        details.innerHTML = Object.entries(fields).map(([key, label]) => {
            const value = key === 'debt'
                ? new Intl.NumberFormat('vi-VN').format(Number(item[key] || 0)) + ' VND'
                : (item[key] || '---');
            return `<div><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`;
        }).join('');
        viewDialog.showModal();
    }));

    document.querySelectorAll('[data-supplier-view-close]').forEach(button => button.addEventListener('click', () => viewDialog.close()));
    document.querySelector('[data-supplier-import-open]')?.addEventListener('click', () => importDialog.showModal());
    document.querySelectorAll('[data-supplier-import-close]').forEach(button => button.addEventListener('click', () => importDialog.close()));
})();
</script>
