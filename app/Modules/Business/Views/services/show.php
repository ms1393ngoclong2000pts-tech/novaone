<?php
$statusLabel = ($item['status'] ?? 'active') === 'active' ? 'Hiển thị' : 'Ẩn';
$code = trim((string) ($item['code'] ?? ''));
if ($code === '') {
    $code = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', (string) ($item['name'] ?? 'service'))) . '001';
}
?>

<section class="service-detail-panel">
    <header class="service-detail-head">
        <?= back_link('services') ?>
        <h2>XEM CHI TIẾT NGÀNH HÀNG</h2>
        <a class="employee-action" href="?route=services.edit&id=<?= e($item['id'] ?? '') ?>">Sửa</a>
    </header>

    <div class="service-detail-body">
        <div class="service-detail-media">
            <section>
                <h3>Ảnh đại diện sản phẩm</h3>
                <div class="service-image-frame">
                    <?php if (! empty($item['image'])): ?>
                        <img src="<?= e($item['image']) ?>" alt="">
                    <?php endif; ?>
                </div>
            </section>
            <section>
                <h3>Ảnh icon ngành hàng</h3>
                <div class="service-image-frame service-icon-frame">
                    <?php if (! empty($item['icon'])): ?>
                        <img src="<?= e($item['icon']) ?>" alt="">
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="service-detail-grid">
            <div>
                <h3>Mã ngành hàng</h3>
                <p><?= e($code) ?></p>
            </div>
            <div>
                <h3>Tên ngành hàng</h3>
                <p><?= e($item['name'] ?? '') ?></p>
            </div>
            <div>
                <h3>Trạng thái</h3>
                <p><?= e($statusLabel) ?></p>
            </div>
            <div>
                <h3>Mô tả</h3>
                <p><?= e($item['note'] ?? '') ?></p>
            </div>
        </div>

        <section class="service-products">
            <h3>Danh sách sản phẩm</h3>
            <div class="service-products-tools">
                <label>Hiển thị
                    <select>
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                    trên 1 trang
                </label>
            </div>
            <div class="service-product-table-wrap">
                <table class="service-product-table">
                    <thead>
                        <tr>
                            <th>Hình ảnh <span>↕</span></th>
                            <th>Tên sản phẩm</th>
                            <th>Phân loại biến thể</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Doanh số</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= e($product['image'] ?? '') ?></td>
                                <td><?= e($product['name'] ?? '') ?></td>
                                <td><?= e($product['variant'] ?? '') ?></td>
                                <td><?= e($product['price'] ?? '') ?></td>
                                <td><?= e($product['quantity'] ?? '') ?></td>
                                <td><?= e($product['revenue'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($products) === 0): ?>
                            <tr><td class="service-empty" colspan="6">Không có dữ liệu</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <footer class="employee-pagination service-pagination">
                <span>Hiển thị 0 tới 0 trong số 0 mục</span>
                <nav>
                    <a class="disabled" href="?route=services.show&id=<?= e($item['id'] ?? '') ?>">Trước</a>
                    <a class="disabled" href="?route=services.show&id=<?= e($item['id'] ?? '') ?>">Sau</a>
                </nav>
            </footer>
        </section>
    </div>
</section>
