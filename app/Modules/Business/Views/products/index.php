<?php
/** @var string $field */
/** @var string $query */
/** @var string $category */
/** @var string $status */
/** @var int $perPage */
/** @var string $sort */
/** @var string $direction */
/** @var array<int, array<string, mixed>> $allItems */
/** @var array<int, array<string, mixed>> $items */
/** @var array<string, string> $fieldLabels */
/** @var array<string, string> $statusLabels */
/** @var array<int, array{value: string, label: string}> $categories */
/** @var int $total */
/** @var int $page */
/** @var int $pages */
$queryUrl = function (array $changes = []) use ($field, $query, $category, $status, $perPage): string {
    $base = [
        'route' => 'products',
        'field' => $field,
        'q' => $query,
        'category' => $category,
        'status' => $status,
        'per_page' => $perPage,
    ];
    $queryData = array_merge($base, $changes);
    foreach ($queryData as $key => $value) {
        if ($value === '' || $value === null || ($key === 'status' && $value === 'all')) {
            unset($queryData[$key]);
        }
    }
    return '?' . http_build_query($queryData);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
$statusCounts = ['all' => count($allItems)];
foreach ($statusLabels as $key => $label) {
    if ($key === 'all') {
        continue;
    }
    $statusCounts[$key] = count(array_filter($allItems, fn (array $item): bool => ($item['status'] ?? 'in_stock') === $key));
}
?>

<section class="product-panel">
    <header class="product-head">
        <h2>DANH SÁCH SẢN PHẨM</h2>
        <button class="employee-action blue" type="button" data-product-open>Thêm mới sản phẩm</button>
    </header>

    <div class="product-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form class="product-filter" method="get">
            <input type="hidden" name="route" value="products">
            <select name="field">
                <?php foreach ($fieldLabels as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $field === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="product-search-box">
                <input name="q" value="<?= e($query) ?>" placeholder="Nhập mã sản phẩm">
                <?= ui_icon('search') ?>
            </div>
            <button class="employee-action rose" type="submit">Tìm kiếm</button>
            <a class="employee-action violet" href="?route=products">Đặt lại</a>
            <span class="product-filter-divider"></span>
            <label>Lọc danh mục</label>
            <select name="category" onchange="this.form.submit()">
                <option value="">--- Chọn danh mục sản phẩm ---</option>
                <?php foreach ($categories as $categoryOption): ?>
                    <option value="<?= e($categoryOption['value']) ?>" <?= $category === $categoryOption['value'] ? 'selected' : '' ?>><?= e($categoryOption['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <nav class="product-status-tabs">
            <?php foreach ($statusLabels as $key => $label): ?>
                <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= e($queryUrl(['status' => $key, 'page' => 1])) ?>"><?= e($label) ?> (<?= (int) ($statusCounts[$key] ?? 0) ?>)</a>
            <?php endforeach; ?>
        </nav>

        <div class="product-tools">
            <label>Hiển thị
                <select name="per_page" form="product-page-form" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form id="product-page-form" method="get">
                <input type="hidden" name="route" value="products">
                <input type="hidden" name="field" value="<?= e($field) ?>">
                <input type="hidden" name="q" value="<?= e($query) ?>">
                <input type="hidden" name="category" value="<?= e($category) ?>">
                <input type="hidden" name="status" value="<?= e($status) ?>">
            </form>
        </div>

        <div class="product-table-wrap">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th><a href="<?= e($sortUrl('name')) ?>">Tên sản phẩm</a></th>
                        <th><a href="<?= e($sortUrl('variant')) ?>">Phân loại biến thể</a></th>
                        <th><a href="<?= e($sortUrl('price')) ?>">Giá</a></th>
                        <th><a href="<?= e($sortUrl('quantity')) ?>">Số lượng</a></th>
                        <th><a href="<?= e($sortUrl('revenue')) ?>">Doanh số</a></th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $itemForUi = $item;
                        $itemForUi['image_url'] = asset_url((string) ($item['image'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <?php if (! empty($item['image'])): ?>
                                    <img class="product-thumb" src="<?= e($itemForUi['image_url']) ?>" alt="">
                                <?php else: ?>
                                    <span class="product-thumb placeholder"><span>▣</span></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="product-name" type="button" data-product-view='<?= e(json_encode($itemForUi, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['name'] ?? '') ?></button>
                            </td>
                            <td><?= e($item['variant'] ?? '-') ?></td>
                            <td class="product-money">
                                <span>₫ <?= e(number_format((float) ($item['price'] ?? 0), 0, ',', '.')) ?></span>
                                <button class="product-price-edit" type="button" data-product-price-edit='<?= e(json_encode($itemForUi, JSON_UNESCAPED_UNICODE)) ?>' aria-label="Chỉnh sửa giá">
                                    <?= ui_icon('edit') ?>
                                </button>
                            </td>
                            <td class="product-number"><?= e((string) ((int) ($item['quantity'] ?? 0))) ?></td>
                            <td class="product-number"><?= e((string) ((int) ($item['revenue'] ?? 0))) ?></td>
                            <td>
                                <details class="service-actions product-actions">
                                    <summary>Thao tác</summary>
                                    <div class="service-action-menu">
                                        <button class="service-action-detail" type="button" data-product-view='<?= e(json_encode($itemForUi, JSON_UNESCAPED_UNICODE)) ?>'>Xem chi tiết</button>
                                        <button class="service-action-edit" type="button" data-product-edit='<?= e(json_encode($itemForUi, JSON_UNESCAPED_UNICODE)) ?>'>Sửa</button>
                                        <form method="post" action="?route=products.delete" onsubmit="return confirm('Xóa sản phẩm này?')">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                            <button class="service-action-delete" type="submit">Xóa</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="service-empty" colspan="7">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination product-pagination">
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

<dialog id="product-dialog" class="employee-dialog product-dialog">
    <form method="post" action="?route=products.save" enctype="multipart/form-data" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm mới sản phẩm</h3><button type="button" data-product-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Tên sản phẩm</span><input name="name" required></label>
            <label><span>Mã sản phẩm</span><input name="code"></label>
            <label><span>SKU</span><input name="sku"></label>
            <label><span>Phân loại biến thể</span><input name="variant"></label>
            <label><span>Danh mục</span><select name="category"><option value="">Chọn danh mục sản phẩm</option><?php foreach ($categories as $categoryOption): ?><option value="<?= e($categoryOption['value']) ?>"><?= e($categoryOption['label']) ?></option><?php endforeach; ?></select></label>
            <label><span>Trạng thái</span><select name="status"><?php foreach ($statusLabels as $value => $label): ?><?php if ($value !== 'all'): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endif; ?><?php endforeach; ?></select></label>
            <label><span>Giá</span><input name="price" type="number" min="0" step="1000"></label>
            <label><span>Số lượng</span><input name="quantity" type="number" min="0" step="1"></label>
            <label><span>Doanh số</span><input name="revenue" type="number" min="0" step="1"></label>
            <label><span>Ảnh sản phẩm</span><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/*"></label>
            <label class="contract-note"><span>Ghi chú</span><textarea name="note" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-product-close>Hủy</button><button class="employee-action teal" type="submit">Lưu sản phẩm</button></footer>
    </form>
</dialog>

<dialog id="product-price-dialog" class="employee-dialog product-price-dialog">
    <form method="post" action="?route=products.price" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Chỉnh sửa giá</h3><button type="button" data-product-price-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Sản phẩm</span><input name="product_name" readonly></label>
            <label><span>Giá</span><input name="price" type="number" min="0" step="1000" required></label>
        </div>
        <footer><button class="employee-action" type="button" data-product-price-close>Hủy</button><button class="employee-action teal" type="submit">Lưu giá</button></footer>
    </form>
</dialog>

<dialog id="product-view-dialog" class="employee-dialog product-dialog">
    <form class="employee-dialog-form" method="dialog">
        <header><h3>Chi tiết sản phẩm</h3><button type="button" data-product-view-close aria-label="Đóng">×</button></header>
        <div class="product-detail-list"></div>
        <footer><button class="employee-action teal" type="button" data-product-view-close>Đóng</button></footer>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('product-dialog');
    const viewDialog = document.getElementById('product-view-dialog');
    const priceDialog = document.getElementById('product-price-dialog');
    if (!dialog || !viewDialog || !priceDialog) return;
    const form = dialog.querySelector('form');
    const priceForm = priceDialog.querySelector('form');
    const details = viewDialog.querySelector('.product-detail-list');
    const statusMap = {
        in_stock: 'Còn hàng',
        out_of_stock: 'Hết hàng',
        pending: 'Chờ duyệt',
        violation: 'Vi phạm',
        hidden: 'Ẩn'
    };
    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    document.querySelector('[data-product-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.sku.value = '';
        form.elements.variant.value = '';
        form.elements.category.value = '';
        form.elements.status.value = 'in_stock';
        dialog.querySelector('h3').textContent = 'Thêm mới sản phẩm';
        dialog.showModal();
    });

    document.querySelectorAll('[data-product-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-product-view-close]').forEach(button => button.addEventListener('click', () => viewDialog.close()));
    document.querySelectorAll('[data-product-price-close]').forEach(button => button.addEventListener('click', () => priceDialog.close()));

    document.querySelectorAll('[data-product-price-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.productPriceEdit);
        priceForm.reset();
        priceForm.elements.id.value = item.id || '';
        priceForm.elements.product_name.value = item.name || '';
        priceForm.elements.price.value = Number(item.price || 0);
        priceDialog.showModal();
    }));

    document.querySelectorAll('[data-product-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.productEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key] && form.elements[key].type !== 'file') form.elements[key].value = value ?? '';
        });
        dialog.querySelector('h3').textContent = 'Cập nhật sản phẩm';
        dialog.showModal();
    }));

    document.querySelectorAll('[data-product-view]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.productView);
        const money = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' VND';
        const status = statusMap[item.status] || item.status || '---';
        const image = item.image_url || item.image
            ? `<img class="product-detail-image" src="${escapeHtml(item.image_url || item.image)}" alt="${escapeHtml(item.name || '')}">`
            : '<span class="product-detail-image placeholder"><span>▣</span></span>';
        details.innerHTML = `
            <div class="product-detail-hero">
                <div class="product-detail-media">${image}</div>
                <div class="product-detail-main">
                    <span class="product-detail-status">${escapeHtml(status)}</span>
                    <h4>${escapeHtml(item.name || '---')}</h4>
                    <p>SKU: ${escapeHtml(item.sku || '-')}</p>
                    <dl>
                        <div><dt>Mã sản phẩm</dt><dd>${escapeHtml(item.code || '---')}</dd></div>
                        <div><dt>Danh mục</dt><dd>${escapeHtml(item.category || 'Chưa chọn')}</dd></div>
                        <div><dt>Phân loại biến thể</dt><dd>${escapeHtml(item.variant || '-')}</dd></div>
                    </dl>
                </div>
            </div>
            <div class="product-detail-stats">
                <div><span>Giá</span><strong>${escapeHtml(money(item.price))}</strong></div>
                <div><span>Số lượng</span><strong>${escapeHtml(item.quantity ?? 0)}</strong></div>
                <div><span>Doanh số</span><strong>${escapeHtml(item.revenue ?? 0)}</strong></div>
            </div>
            <section class="product-detail-note">
                <h5>Ghi chú</h5>
                <p>${escapeHtml(item.note || 'Không có ghi chú')}</p>
            </section>
        `;
        viewDialog.showModal();
    }));
})();
</script>
