<?php
$id = (string) ($item['id'] ?? '');
$image = (string) ($item['image'] ?? '');
$icon = (string) ($item['icon'] ?? '');
$heading = $isEdit ? 'CẬP NHẬT NGÀNH HÀNG' : 'THÊM MỚI NGÀNH HÀNG';
?>

<section class="service-form-panel">
    <header class="service-detail-head">
        <a class="service-back" href="?route=services" aria-label="Quay lại"><?= ui_icon('arrow') ?></a>
        <h2><?= e($heading) ?></h2>
    </header>

    <form class="service-form-body" method="post" action="?route=services.save" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= e($id) ?>">

        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="service-upload-grid">
            <section class="service-upload-block">
                <h3>Ảnh đại diện</h3>
                <div class="service-upload-row">
                    <label class="service-upload-box">
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-service-preview="image-preview">
                        <span class="service-camera-placeholder">
                            <?php if ($image !== ''): ?>
                                <img id="image-preview" src="<?= e($image) ?>" alt="">
                            <?php else: ?>
                                <img id="image-preview" src="" alt="" hidden>
                                <span class="service-camera-icon">▣</span>
                            <?php endif; ?>
                        </span>
                    </label>
                    <ul class="service-upload-hints">
                        <li>Kích thước chuẩn: chiều rộng 500 pixel x chiều cao 500 pixel</li>
                        <li>Kích thước: tối đa 200Kb</li>
                        <li>Định dạng: jpg, jpeg, png</li>
                    </ul>
                </div>
                <p>Nhấn vào ảnh để cập nhật hoặc chỉnh sửa</p>
            </section>

            <section class="service-upload-block">
                <h3>Ảnh icon danh mục</h3>
                <div class="service-upload-row">
                    <label class="service-upload-box">
                        <input type="file" name="icon" accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-service-preview="icon-preview">
                        <span class="service-camera-placeholder">
                            <?php if ($icon !== ''): ?>
                                <img id="icon-preview" src="<?= e($icon) ?>" alt="">
                            <?php else: ?>
                                <img id="icon-preview" src="" alt="" hidden>
                                <span class="service-camera-icon">▣</span>
                            <?php endif; ?>
                        </span>
                    </label>
                    <ul class="service-upload-hints">
                        <li>Kích thước chuẩn: chiều rộng 500 pixel x chiều cao 500 pixel</li>
                        <li>Kích thước: tối đa 200Kb</li>
                        <li>Định dạng: jpg, jpeg, png</li>
                    </ul>
                </div>
                <p>Nhấn vào ảnh để cập nhật icon danh mục</p>
            </section>
        </div>

        <div class="service-form-grid">
            <label>
                <span>Tên ngành hàng<strong>*</strong></span>
                <input name="name" value="<?= e($item['name'] ?? '') ?>" required>
            </label>
            <label>
                <span>Mã ngành hàng <strong>*</strong></span>
                <input name="code" value="<?= e($item['code'] ?? '') ?>" required>
            </label>
            <label>
                <span>Cấp ngành hàng</span>
                <select name="level">
                    <?php foreach ($levelLabels as $value => $label): ?>
                        <option value="<?= $value ?>" <?= (int) ($item['level'] ?? 1) === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Thuộc ngành hàng</span>
                <select name="parent">
                    <option value="">---</option>
                    <?php foreach ($items as $parentItem): ?>
                        <?php if (($parentItem['id'] ?? '') === $id) { continue; } ?>
                        <option value="<?= e($parentItem['name'] ?? '') ?>" <?= (string) ($item['parent'] ?? '') === (string) ($parentItem['name'] ?? '') ? 'selected' : '' ?>><?= e($parentItem['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Trạng thái</span>
                <select name="status">
                    <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="inactive" <?= ($item['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Ẩn</option>
                </select>
            </label>
            <label>
                <span>Giá tham khảo</span>
                <input name="price" type="number" min="0" step="1000" value="<?= e($item['price'] ?? 0) ?>">
            </label>
            <label class="service-form-note">
                <span>Mô tả</span>
                <textarea name="note" rows="6"><?= e($item['note'] ?? '') ?></textarea>
            </label>
        </div>

        <footer class="service-save-bar">
            <button class="employee-action teal" type="submit"><?= ui_icon('file') ?> Lưu</button>
        </footer>
    </form>
</section>

<script>
(() => {
    document.querySelectorAll('[data-service-preview]').forEach(input => {
        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            const preview = document.getElementById(input.dataset.servicePreview);
            const icon = input.closest('.service-upload-box')?.querySelector('.service-camera-icon');
            if (!file || !preview) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            if (icon) icon.hidden = true;
        });
    });
})();
</script>
