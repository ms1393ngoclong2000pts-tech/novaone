<?php
$queryUrl = function (array $changes = []): string {
    $values = array_merge($_GET, $changes);
    foreach ($values as $key => $value) if ($value === '' || $value === null) unset($values[$key]);
    return '?' . http_build_query($values);
};
$sortUrl = fn (string $column): string => $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
?>

<section class="daily-panel">
    <header class="daily-head"><h2>BÁO CÁO HÀNG NGÀY</h2></header>
    <div class="daily-body">
        <?php if (! empty($_SESSION['flash_success'])): ?><div class="alert success"><?= e($_SESSION['flash_success']) ?></div><?php unset($_SESSION['flash_success']); endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?><div class="alert"><?= e($_SESSION['flash_error']) ?></div><?php unset($_SESSION['flash_error']); endif; ?>

        <form id="daily-filter" class="daily-filter" method="get">
            <input type="hidden" name="route" value="daily-reports">
            <label><span>Từ ngày</span><input name="from_date" type="date" value="<?= e($fromDate) ?>"></label>
            <label><span>Đến ngày</span><input name="to_date" type="date" value="<?= e($toDate) ?>"></label>
            <label><span>Nhân viên</span><select name="employee"><option value="">--- Nhân viên ---</option><?php foreach ($employees as $employeeItem): ?><option value="<?= e($employeeItem['name'] ?? '') ?>" <?= $employee === ($employeeItem['name'] ?? '') ? 'selected' : '' ?>><?= e($employeeItem['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Trạng thái dự án</span><select name="project_status"><option value="">Tất cả</option><?php foreach ($projectStatuses as $value => $label): ?><option value="<?= e($value) ?>" <?= $projectStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Dự án</span><select name="project"><option value="">--- Dự án ---</option><?php foreach ($projects as $projectItem): ?><option value="<?= e($projectItem['name'] ?? '') ?>" <?= $project === ($projectItem['name'] ?? '') ? 'selected' : '' ?>><?= e($projectItem['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <button type="submit">Lọc</button>
        </form>

        <section class="daily-create">
            <h3>Thêm mới</h3>
            <form id="daily-create-form" method="post" action="?route=daily-reports.save">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <div id="daily-entry-list">
                    <div class="daily-entry">
                        <label><span>Dự án</span><select name="project[]" required><option value="">--- Dự án ---</option><?php foreach ($projects as $projectItem): ?><option value="<?= e($projectItem['name'] ?? '') ?>"><?= e($projectItem['name'] ?? '') ?></option><?php endforeach; ?></select></label>
                        <label><span>Người thực hiện</span><select name="employee[]" required><option value="">--- Nhân viên ---</option><?php foreach ($employees as $employeeItem): ?><option value="<?= e($employeeItem['name'] ?? '') ?>"><?= e($employeeItem['name'] ?? '') ?></option><?php endforeach; ?></select></label>
                        <label><span>Thời gian</span><select name="hours[]"><?php for ($hour = .5; $hour <= 12; $hour += .5): ?><option value="<?= $hour ?>" <?= $hour === .5 ? 'selected' : '' ?>><?= $hour ?></option><?php endfor; ?></select></label>
                        <label><span>Hạng mục</span><select name="category[]"><option value="">--- Hạng mục ---</option><?php foreach ($categories as $categoryItem): ?><option value="<?= e($categoryItem) ?>"><?= e($categoryItem) ?></option><?php endforeach; ?></select></label>
                        <label><span>Ngày</span><input name="report_date[]" type="date" value="<?= date('Y-m-d') ?>" required></label>
                        <label class="daily-detail-field"><span>Chi tiết</span><textarea name="details[]" rows="4" required></textarea></label>
                        <button class="daily-remove" type="button" title="Xóa dòng">−</button>
                    </div>
                </div>
                <div class="daily-create-actions"><button class="daily-add" type="button" title="Thêm dòng">+</button><button class="employee-action teal" type="submit">Lưu báo cáo</button></div>
            </form>
        </section>

        <div class="daily-tools"><label>Hiển thị <select name="per_page" form="daily-filter" onchange="this.form.submit()"><?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select> trên 1 trang</label><form method="get"><input type="hidden" name="route" value="daily-reports"><input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm"></form></div>

        <div class="daily-table-wrap"><table class="daily-table">
            <thead><tr><?php foreach (['project' => 'Tên dự án', 'category' => 'Hạng mục', 'employee' => 'Người thực hiện', 'details' => 'Chi tiết', 'hours' => 'Giờ', 'report_date' => 'Ngày'] as $column => $label): ?><th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th><?php endforeach; ?><th>Thao tác</th></tr></thead>
            <tbody><?php foreach ($items as $item): ?><tr>
                <td><?= e($item['project'] ?? '') ?></td><td><?= e($item['category'] ?? '') ?></td><td><?= e($item['employee'] ?? '') ?></td><td><?= e($item['details'] ?? '') ?></td><td><?= e((string) ($item['hours'] ?? 0)) ?></td><td><?= e(date('d/m/Y', strtotime((string) ($item['report_date'] ?? 'now')))) ?></td><td><button class="insurance-detail" type="button" data-daily-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>' title="Chi tiết"><?= ui_icon('info') ?></button></td>
            </tr><?php endforeach; ?><?php if ($total === 0): ?><tr><td class="contract-empty" colspan="7">Không có dữ liệu</td></tr><?php endif; ?></tbody>
        </table></div>
        <footer class="employee-pagination daily-pagination"><span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> mục</span><nav><a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a><?php for ($number = 1; $number <= $pages; $number++): ?><a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a><?php endfor; ?><a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a></nav></footer>
    </div>
</section>

<dialog id="daily-dialog" class="employee-dialog daily-dialog"><form method="post" action="?route=daily-reports.update" class="employee-dialog-form"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id"><header><h3>Chi tiết báo cáo</h3><button type="button" data-daily-close>×</button></header><div class="employee-form-grid"><label><span>Dự án</span><select name="project" required><?php foreach ($projects as $projectItem): ?><option value="<?= e($projectItem['name'] ?? '') ?>"><?= e($projectItem['name'] ?? '') ?></option><?php endforeach; ?></select></label><label><span>Người thực hiện</span><select name="employee" required><?php foreach ($employees as $employeeItem): ?><option value="<?= e($employeeItem['name'] ?? '') ?>"><?= e($employeeItem['name'] ?? '') ?></option><?php endforeach; ?></select></label><label><span>Hạng mục</span><input name="category"></label><label><span>Thời gian</span><input name="hours" type="number" min="0.5" step="0.5"></label><label><span>Ngày</span><input name="report_date" type="date" required></label><label class="contract-note"><span>Chi tiết</span><textarea name="details" rows="5" required></textarea></label></div><footer class="daily-dialog-actions"><button class="employee-action danger daily-delete" type="button">Xóa</button><span></span><button class="employee-action" type="button" data-daily-close>Hủy</button><button class="employee-action teal" type="submit">Cập nhật</button></footer></form></dialog>
<form id="daily-delete-form" method="post" action="?route=daily-reports.delete" hidden><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id"></form>

<script>
(() => {
  const list = document.getElementById('daily-entry-list'); const first = list?.querySelector('.daily-entry');
  document.querySelector('.daily-add')?.addEventListener('click', () => { const row = first.cloneNode(true); row.querySelectorAll('input, textarea, select').forEach(field => { if (field.name === 'report_date[]') field.value = new Date().toISOString().slice(0, 10); else if (field.name === 'hours[]') field.value = '0.5'; else field.value = ''; }); list.appendChild(row); });
  list?.addEventListener('click', event => { const button = event.target.closest('.daily-remove'); if (!button) return; const rows = list.querySelectorAll('.daily-entry'); if (rows.length === 1) { rows[0].querySelectorAll('textarea, select').forEach(field => field.value = field.name === 'hours[]' ? '0.5' : ''); return; } button.closest('.daily-entry').remove(); });
  const dialog = document.getElementById('daily-dialog'); const form = dialog?.querySelector('form'); const deleteForm = document.getElementById('daily-delete-form');
  document.querySelectorAll('[data-daily-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  document.querySelectorAll('[data-daily-edit]').forEach(button => button.addEventListener('click', () => { const item = JSON.parse(button.dataset.dailyEdit); Object.entries(item).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; }); dialog.showModal(); }));
  dialog?.querySelector('.daily-delete')?.addEventListener('click', () => { if (!confirm('Xóa báo cáo này?')) return; deleteForm.elements.id.value = form.elements.id.value; deleteForm.submit(); });
})();
</script>
