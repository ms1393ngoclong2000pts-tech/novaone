<?php
$queryUrl = function (array $changes = []): string {
    $values = array_merge($_GET, $changes);
    foreach ($values as $key => $value) {
        if ($value === '' || $value === null) unset($values[$key]);
    }
    return '?' . http_build_query($values);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
?>

<section class="work-panel">
    <header class="work-head"><h2>DANH MỤC CÔNG VIỆC</h2><button class="employee-action blue" type="button" data-work-open>+ Tạo công việc</button></header>
    <div class="work-body">
        <?php if (! empty($_SESSION['flash_success'])): ?><div class="alert success"><?= e($_SESSION['flash_success']) ?></div><?php unset($_SESSION['flash_success']); endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?><div class="alert"><?= e($_SESSION['flash_error']) ?></div><?php unset($_SESSION['flash_error']); endif; ?>

        <section class="work-filter-section">
            <h3>LỌC & TÌM KIẾM CÔNG VIỆC</h3>
            <form id="work-filter" method="get">
                <input type="hidden" name="route" value="work-items"><input type="hidden" name="status_filter" value="1">
                <strong>Tìm kiếm và lọc theo tùy chọn</strong>
                <div class="work-filter-grid">
                    <label><span>Từ khóa</span><input type="text" name="q" value="<?= e($query) ?>" placeholder="Nhập từ khóa tìm kiếm..."></label>
                    <label><span>Dự án</span><select name="project" onchange="this.form.submit()"><option value="">-- Tất cả dự án --</option><?php foreach ($projects as $projectName): ?><option value="<?= e($projectName) ?>" <?= $project === $projectName ? 'selected' : '' ?>><?= e($projectName) ?></option><?php endforeach; ?></select></label>
                    <label><span>Hạng mục</span><select name="category" onchange="this.form.submit()"><option value="">-- Tất cả hạng mục --</option><?php foreach ($categories as $categoryName): ?><option value="<?= e($categoryName) ?>" <?= $category === $categoryName ? 'selected' : '' ?>><?= e($categoryName) ?></option><?php endforeach; ?></select></label>
                </div>
                <div class="work-status-filters">
                    <div class="status-checkboxes">
                        <?php foreach ($statuses as $value => $label): ?><label><input name="statuses[]" type="checkbox" value="<?= e($value) ?>" <?= in_array($value, $selectedStatuses, true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?>
                    </div>
                    <div class="filter-actions">
                        <button type="submit">Áp dụng</button>
                        <?php if ($project !== '' || $category !== '' || $query !== '' || $selectedStatuses !== ['pending', 'in_progress']): ?>
                            <a href="?route=work-items" class="btn-clear-filter">Xóa bộ lọc</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </section>

        <div class="work-tools">
            <label>Hiển thị <select name="per_page" form="work-filter" onchange="this.form.submit()"><?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select> trên 1 trang</label>
        </div>

        <div class="work-table-wrap"><table class="work-table">
            <thead><tr>
                <?php foreach (['title' => 'Công việc được xem', 'hours' => 'Giờ', 'assignee' => 'Người thực hiện', 'start_date' => 'Ngày bắt đầu', 'completion_date' => 'Ngày hoàn thành', 'status' => 'Trạng thái', 'progress' => 'Tiến độ'] as $column => $label): ?><th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th><?php endforeach; ?>
                <th>Thao tác</th>
            </tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?><tr>
                    <td><button class="project-link" type="button" data-work-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= e($item['title']) ?></button><small><?= e($item['project']) ?></small></td>
                    <td><?= e((string) $item['hours']) ?></td>
                    <td><?= e($item['assignee']) ?></td>
                    <td><?= e(date('d/m/Y', strtotime($item['start_date']))) ?></td>
                    <td><?= $item['completion_date'] !== '' ? e(date('d/m/Y', strtotime($item['completion_date']))) : '—' ?></td>
                    <td><span class="work-status <?= e($item['status']) ?>"><?= e($statuses[$item['status']] ?? '') ?></span></td>
                    <td><div class="work-progress"><i style="width: <?= (int) $item['progress'] ?>%"></i></div><b><?= (int) $item['progress'] ?>%</b></td>
                    <td><button class="insurance-detail" type="button" title="Chi tiết" data-work-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                </tr><?php endforeach; ?>
                <?php if ($total === 0): ?><tr><td class="contract-empty" colspan="8">Không có dữ liệu</td></tr><?php endif; ?>
            </tbody>
        </table></div>

        <footer class="employee-pagination work-pagination"><span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> mục</span><nav><a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a><?php for ($number = 1; $number <= $pages; $number++): ?><a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a><?php endfor; ?><a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a></nav></footer>
    </div>
</section>

<dialog id="work-dialog" class="employee-dialog work-dialog">
    <form method="post" action="?route=work-items.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id">
        <header><h3>Tạo công việc</h3><button type="button" data-work-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label class="contract-note"><span>Tên công việc</span><input name="title" required></label>
            <label><span>Dự án</span><select name="project"><option value="">-- Chọn dự án --</option><?php foreach ($projectRecords as $projectItem): ?><option value="<?= e($projectItem['name'] ?? '') ?>"><?= e($projectItem['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Hạng mục</span><input name="category"></label>
            <label><span>Người thực hiện</span><select name="assignee"><option value="">-- Chưa phân công --</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['name'] ?? '') ?>"><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Số giờ</span><input name="hours" type="number" min="0" step="0.5"></label>
            <label><span>Ngày bắt đầu</span><input name="start_date" type="date" required></label>
            <label><span>Ngày hoàn thành</span><input name="completion_date" type="date"></label>
            <label><span>Trạng thái</span><select name="status"><?php foreach ($statuses as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Tiến độ (%)</span><input name="progress" type="number" min="0" max="100" value="0"></label>
            <label><span>Ưu tiên</span><select name="priority"><?php foreach ($priorities as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label class="contract-note"><span>Mô tả</span><textarea name="description" rows="4"></textarea></label>
        </div>
        <footer class="work-dialog-actions"><button class="employee-action danger work-delete" type="button" hidden>Xóa công việc</button><span></span><button class="employee-action" type="button" data-work-close>Hủy</button><button class="employee-action teal" type="submit">Lưu công việc</button></footer>
    </form>
</dialog>
<form id="work-delete-form" method="post" action="?route=work-items.delete" hidden><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id"></form>

<script>
(() => {
    const dialog = document.getElementById('work-dialog'); if (!dialog) return;
    const form = dialog.querySelector('form'); const deleteButton = dialog.querySelector('.work-delete'); const deleteForm = document.getElementById('work-delete-form');
    document.querySelector('[data-work-open]')?.addEventListener('click', () => { form.reset(); form.elements.id.value = ''; form.elements.start_date.value = new Date().toISOString().slice(0, 10); deleteButton.hidden = true; dialog.querySelector('h3').textContent = 'Tạo công việc'; dialog.showModal(); });
    document.querySelectorAll('[data-work-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    document.querySelectorAll('[data-work-edit]').forEach(button => button.addEventListener('click', () => { const item = JSON.parse(button.dataset.workEdit); Object.entries(item).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; }); deleteButton.hidden = false; dialog.querySelector('h3').textContent = 'Chi tiết công việc'; dialog.showModal(); }));
    deleteButton.addEventListener('click', () => { if (!confirm('Xóa công việc này?')) return; deleteForm.elements.id.value = form.elements.id.value; deleteForm.submit(); });
})();
</script>
