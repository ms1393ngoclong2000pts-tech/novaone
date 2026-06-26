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

<section class="project-panel">
    <header class="project-head">
        <div><a class="violation-back" href="?route=tasks" title="Quay lại">←</a><h2>DANH SÁCH DỰ ÁN</h2></div>
        <div class="project-head-actions">
            <a class="employee-action blue" href="?route=projects.template">Tải mẫu</a>
            <form method="post" action="?route=projects.import" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <label class="employee-action violet">Import Excel<input name="project_file" type="file" accept=".xlsx,.csv" onchange="this.form.submit()" hidden required></label>
            </form>
            <button class="employee-action teal" type="button" data-project-open>Tạo dự án</button>
        </div>
    </header>

    <div class="project-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form id="project-filter" class="project-filter" method="get">
            <input type="hidden" name="route" value="projects">
            <label><span>Trạng thái</span><select name="status"><option value="">Tất cả</option><?php foreach ($statuses as $value => $label): ?><option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Ngày kết thúc từ</span><input name="end_from" type="date" value="<?= e($endFrom) ?>"></label>
            <label><span>Ngày kết thúc đến</span><input name="end_to" type="date" value="<?= e($endTo) ?>"></label>
            <button type="submit">Lọc</button>
        </form>

        <div class="project-tools">
            <label>Hiển thị <select name="per_page" form="project-filter" onchange="this.form.submit()"><?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select> trên 1 trang</label>
            <form method="get"><input type="hidden" name="route" value="projects"><input type="hidden" name="status" value="<?= e($status) ?>"><input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm"></form>
        </div>

        <div class="project-table-wrap">
            <table class="project-table">
                <thead><tr>
                    <th>STT</th>
                    <?php foreach (['name' => 'Tên dự án', 'category' => 'Tên danh mục', 'company' => 'Tên công ty', 'start_date' => 'Ngày bắt đầu', 'end_date' => 'Ngày kết thúc', 'status' => 'Trạng thái'] as $column => $label): ?>
                        <th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                    <?php endforeach; ?>
                    <th>Thao tác</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><button class="project-link" type="button" data-project-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['name'] ?? '') ?></button></td>
                            <td><?= e($item['category'] ?: '—') ?></td>
                            <td><span class="project-company"><?= e($item['company'] ?? '') ?></span></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['start_date'] ?? 'now')))) ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['end_date'] ?? 'now')))) ?></td>
                            <td><span class="project-status <?= e($item['status'] ?? 'open') ?>"><?= e($statuses[$item['status'] ?? ''] ?? '') ?></span></td>
                            <td><button class="insurance-detail" type="button" title="Chi tiết" data-project-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?><tr><td class="contract-empty" colspan="8">Không có dự án phù hợp</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination project-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> dự án</span>
            <nav><a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a><?php for ($number = 1; $number <= $pages; $number++): ?><a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a><?php endfor; ?><a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a></nav>
        </footer>
    </div>
</section>

<dialog id="project-dialog" class="employee-dialog project-dialog">
    <form method="post" action="?route=projects.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="">
        <header><h3>Tạo dự án</h3><button type="button" data-project-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Tên dự án</span><input name="name" required></label>
            <label><span>Danh mục</span><input name="category"></label>
            <label class="contract-note"><span>Tên công ty</span><input name="company"></label>
            <label><span>Ngày bắt đầu</span><input name="start_date" type="date" required></label>
            <label><span>Ngày kết thúc</span><input name="end_date" type="date" required></label>
            <label><span>Trạng thái</span><select name="status"><?php foreach ($statuses as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Quản lý dự án</span><select name="manager"><option value="">-- Chọn nhân viên --</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['name'] ?? '') ?>"><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Ngân sách</span><input name="budget" type="number" min="0" step="1000000"></label>
            <label class="contract-note"><span>Mô tả dự án</span><textarea name="description" rows="4"></textarea></label>
        </div>
        <footer class="project-dialog-actions"><button class="employee-action danger project-delete" type="button" hidden>Xóa dự án</button><span></span><button class="employee-action" type="button" data-project-close>Hủy</button><button class="employee-action teal" type="submit">Lưu dự án</button></footer>
    </form>
</dialog>

<form id="project-delete-form" method="post" action="?route=projects.delete" hidden><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id"></form>

<script>
(() => {
    const dialog = document.getElementById('project-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const deleteButton = dialog.querySelector('.project-delete');
    const deleteForm = document.getElementById('project-delete-form');
    document.querySelector('[data-project-open]')?.addEventListener('click', () => {
        form.reset(); form.elements.id.value = ''; deleteButton.hidden = true;
        dialog.querySelector('h3').textContent = 'Tạo dự án'; dialog.showModal();
    });
    document.querySelectorAll('[data-project-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-project-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.projectEdit);
        Object.entries(item).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
        deleteButton.hidden = false; dialog.querySelector('h3').textContent = 'Chi tiết dự án'; dialog.showModal();
    }));
    deleteButton.addEventListener('click', () => {
        if (!confirm('Xóa dự án này?')) return;
        deleteForm.elements.id.value = form.elements.id.value; deleteForm.submit();
    });
})();
</script>
