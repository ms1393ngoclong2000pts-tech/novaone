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
$formatVietnameseDate = function (string $value): string {
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '---';
    }
    $days = ['Sunday' => 'Chủ nhật', 'Monday' => 'Thứ hai', 'Tuesday' => 'Thứ ba', 'Wednesday' => 'Thứ tư', 'Thursday' => 'Thứ năm', 'Friday' => 'Thứ sáu', 'Saturday' => 'Thứ bảy'];
    $day = $days[date('l', $timestamp)] ?? date('l', $timestamp);
    return $day . ', ' . date('d/m/Y, h:i A', $timestamp);
};
?>

<section class="machine-panel equipment-type-panel">
    <header class="machine-head">
        <h2>THÔNG TIN LOẠI THIẾT BỊ</h2>
    </header>

    <div class="machine-body equipment-type-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="equipment-type-form" class="equipment-type-form" method="post" action="?route=equipment-types.save">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="">
            <label><span>Tên loại thiết bị <strong>*</strong></span><input name="name" required></label>
            <label><span>Tên viết tắt loại thiết bị <strong>*</strong></span><input name="short_name" required></label>
        </form>

        <div class="machine-tools">
            <label>Hiển thị
                <select name="per_page" form="equipment-type-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form id="equipment-type-filter" method="get">
                <input type="hidden" name="route" value="equipment-types">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="machine-table-wrap">
            <table class="machine-table equipment-type-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th><a href="<?= e($sortUrl('name')) ?>">Tên loại thiết bị <span><?= $sort === 'name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('short_name')) ?>">Tên viết tắt <span><?= $sort === 'short_name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('created_at')) ?>">Thời gian <span><?= $sort === 'created_at' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="machine-name" type="button" data-equipment-type-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['name'] ?? '') ?></button></td>
                            <td><?= e($item['short_name'] ?? '') ?></td>
                            <td><?= e($formatVietnameseDate((string) ($item['created_at'] ?? ''))) ?></td>
                            <td>
                                <form method="post" action="?route=equipment-types.delete" onsubmit="return confirm('Xóa loại thiết bị này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="machine-icon delete" type="submit" title="Xóa"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="machine-empty" colspan="5">Không có dữ liệu</td></tr>
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

        <div class="equipment-type-savebar">
            <button class="employee-action teal" form="equipment-type-form" type="submit"><?= ui_icon('file') ?> Lưu</button>
        </div>
    </div>
</section>

<script>
(() => {
    const form = document.getElementById('equipment-type-form');
    if (!form) return;

    document.querySelectorAll('[data-equipment-type-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const item = JSON.parse(button.dataset.equipmentTypeEdit);
            form.elements.id.value = item.id || '';
            form.elements.name.value = item.name || '';
            form.elements.short_name.value = item.short_name || '';
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            form.elements.name.focus();
        });
    });
})();
</script>
