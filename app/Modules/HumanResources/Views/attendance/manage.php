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
?>

<section class="attendance-panel">
    <header class="attendance-head">
        <div class="attendance-title-row">
            <?= back_link('attendance') ?>
            <h2>QUẢN LÝ MÁY</h2>
        </div>
        <button class="employee-action blue" type="button" data-attendance-machine-open>Thêm mới</button>
    </header>

    <div class="attendance-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="attendance-machine-filter" class="attendance-filter manage-filter" method="get">
            <input type="hidden" name="route" value="attendance.manage">
            <label><span>Dự án</span><select name="project" onchange="this.form.submit()"><option value="">--- ---</option><?php foreach ($projects as $item): ?><option value="<?= e($item) ?>" <?= $project === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
        </form>

        <div class="attendance-tools">
            <label>Hiển thị
                <select name="per_page" form="attendance-machine-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="attendance.manage">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm Kiếm">
            </form>
        </div>

        <div class="attendance-table-wrap">
            <table class="attendance-table attendance-machine-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>TÊN MÁY</th>
                        <th>SERIALNUMBER</th>
                        <th>TÊN CÔNG TRÌNH</th>
                        <th>THÔNG TIN</th>
                        <th>SỬA</th>
                        <th>XÓA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="attendance-machine-name" type="button" data-attendance-machine-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['name'] ?? '') ?></button></td>
                            <td><?= e($item['serial'] ?? '') ?></td>
                            <td><?= e($item['project'] ?? '') ?></td>
                            <td><button class="machine-icon view" type="button" title="<?= e($item['note'] ?? 'Thông tin máy') ?>" data-attendance-machine-info="<?= e($item['note'] ?? '') ?>"><?= ui_icon('info') ?></button></td>
                            <td><button class="machine-icon edit" type="button" data-attendance-machine-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('edit') ?></button></td>
                            <td>
                                <form method="post" action="?route=attendance.manage.delete" onsubmit="return confirm('Xóa máy chấm công này?')">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <button class="machine-icon delete" type="submit"><?= ui_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?><tr><td class="attendance-empty" colspan="7">Không có dữ liệu</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination attendance-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trong số <?= $total ?> mục</span>
            <nav>
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a>
                <?php for ($number = 1; $number <= $pages; $number++): ?><a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a><?php endfor; ?>
                <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a>
            </nav>
        </footer>
    </div>
</section>

<dialog id="attendance-machine-dialog" class="employee-dialog machine-dialog">
    <form method="post" action="?route=attendance.manage.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Thêm máy chấm công</h3><button type="button" data-attendance-machine-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Tên máy</span><input name="name" required></label>
            <label><span>Serialnumber</span><input name="serial" required></label>
            <label><span>Dự án</span><input name="project" list="attendance-project-options"></label>
            <label class="wide"><span>Thông tin</span><textarea name="note" rows="3"></textarea></label>
        </div>
        <footer><button class="employee-action" type="button" data-attendance-machine-close>Hủy</button><button class="employee-action teal" type="submit">Lưu</button></footer>
    </form>
</dialog>

<dialog id="attendance-machine-info-dialog" class="employee-dialog machine-dialog">
    <form method="dialog" class="employee-dialog-form">
        <header><h3>Thông tin máy</h3><button type="button" data-attendance-info-close aria-label="Đóng">×</button></header>
        <p class="machine-info-message"></p>
        <footer><button class="employee-action teal" type="button" data-attendance-info-close>Đóng</button></footer>
    </form>
</dialog>

<datalist id="attendance-project-options">
    <?php foreach ($projects as $item): ?><option value="<?= e($item) ?>"><?php endforeach; ?>
</datalist>

<script>
(() => {
    const dialog = document.getElementById('attendance-machine-dialog');
    const infoDialog = document.getElementById('attendance-machine-info-dialog');
    if (!dialog || !infoDialog) return;
    const form = dialog.querySelector('form');
    document.querySelector('[data-attendance-machine-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        dialog.querySelector('h3').textContent = 'Thêm máy chấm công';
        dialog.showModal();
    });
    document.querySelectorAll('[data-attendance-machine-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-attendance-machine-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.attendanceMachineEdit);
        Object.entries(item).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
        dialog.querySelector('h3').textContent = 'Cập nhật máy chấm công';
        dialog.showModal();
    }));
    document.querySelectorAll('[data-attendance-machine-info]').forEach(button => button.addEventListener('click', () => {
        infoDialog.querySelector('.machine-info-message').textContent = button.dataset.attendanceMachineInfo || 'Chưa có thông tin chi tiết.';
        infoDialog.showModal();
    }));
    document.querySelectorAll('[data-attendance-info-close]').forEach(button => button.addEventListener('click', () => infoDialog.close()));
})();
</script>
