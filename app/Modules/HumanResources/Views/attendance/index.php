<?php
$queryUrl = function (array $changes = []) use ($department, $position, $employeeId, $startDate, $endDate, $selectedShifts, $query, $perPage, $sort, $direction, $page): string {
    $params = [
        'route' => 'attendance',
        'department' => $department,
        'position' => $position,
        'employee_id' => $employeeId,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'shift' => $selectedShifts,
        'q' => $query,
        'per_page' => $perPage,
        'sort' => $sort,
        'dir' => $direction,
        'page' => $page,
    ];
    $params = array_merge($params, $changes);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null || $value === []) {
            unset($params[$key]);
        }
    }
    return '?' . http_build_query($params);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
?>

<section class="attendance-panel">
    <header class="attendance-head">
        <h2>QUẢN LÝ CHẤM CÔNG THỰC TẾ</h2>
        <div class="attendance-head-actions">
            <a class="employee-action amber" href="?route=attendance.process">Xử lý công</a>
            <a class="employee-action violet" href="?route=attendance.manage">Quản lý chấm công</a>
        </div>
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

        <form id="attendance-filter" class="attendance-filter" method="get">
            <input type="hidden" name="route" value="attendance">
            <input type="hidden" name="q" value="<?= e($query) ?>">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($direction) ?>">
            <label><span>Bộ phận</span><select name="department" onchange="this.form.submit()"><option value="">--- ---</option><?php foreach ($departments as $item): ?><option value="<?= e($item) ?>" <?= $department === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
            <label><span>Chức danh</span><select name="position" onchange="this.form.submit()"><option value="">--- ---</option><?php foreach ($positions as $item): ?><option value="<?= e($item) ?>" <?= $position === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
            <label><span>Nhân viên</span><select name="employee_id" onchange="this.form.submit()"><option value="">--- ---</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['id'] ?? '') ?>" <?= $employeeId === ($employee['id'] ?? '') ? 'selected' : '' ?>><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Từ Ngày</span><input name="start_date" type="date" value="<?= e($startDate) ?>"></label>
            <label><span>Đến Ngày</span><input name="end_date" type="date" value="<?= e($endDate) ?>"></label>
            <fieldset class="attendance-shifts">
                <?php foreach ($shifts as $value => $label): ?>
                    <label><input type="checkbox" name="shift[]" value="<?= e($value) ?>" <?= in_array($value, $selectedShifts, true) ? 'checked' : '' ?>> <?= e($label) ?></label>
                <?php endforeach; ?>
            </fieldset>
            <button class="employee-action blue" type="submit">Lọc</button>
        </form>

        <div class="attendance-tools">
            <label>Hiển thị
                <select name="per_page" form="attendance-filter" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form method="get">
                <input type="hidden" name="route" value="attendance">
                <input type="hidden" name="department" value="<?= e($department) ?>">
                <input type="hidden" name="position" value="<?= e($position) ?>">
                <input type="hidden" name="employee_id" value="<?= e($employeeId) ?>">
                <input type="hidden" name="start_date" value="<?= e($startDate) ?>">
                <input type="hidden" name="end_date" value="<?= e($endDate) ?>">
                <input type="hidden" name="per_page" value="<?= e((string) $perPage) ?>">
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
                <input type="hidden" name="dir" value="<?= e($direction) ?>">
                <?php foreach ($selectedShifts as $selectedShift): ?>
                    <input type="hidden" name="shift[]" value="<?= e($selectedShift) ?>">
                <?php endforeach; ?>
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm Kiếm">
            </form>
        </div>

        <div class="attendance-table-wrap">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <?php foreach (['date' => 'NGÀY', 'employee_name' => 'NHÂN VIÊN', 'department' => 'BỘ PHẬN', 'check_time' => 'CHẤM CÔNG', 'total_hours' => 'TỔNG THỜI GIAN', 'shift' => 'CA'] as $column => $label): ?>
                            <th><a href="<?= e($sortUrl($column)) ?>"><?= e($label) ?><span><?= $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= (($page - 1) * $perPage) + $index + 1 ?></td>
                            <td><?= e(date('d/m/Y', strtotime((string) ($item['date'] ?? 'now')))) ?></td>
                            <td><a class="employee-chip employee-name-link" href="?route=employees.show&amp;id=<?= e($item['employee_id'] ?? '') ?>"><?= ui_icon('users') ?><?= e($item['employee_name'] ?? '') ?></a></td>
                            <td><?= e($item['department'] ?? '') ?></td>
                            <td><?= e($item['check_time'] ?? '') ?></td>
                            <td><?= e(rtrim(rtrim(number_format((float) ($item['total_hours'] ?? 0), 2, '.', ''), '0'), '.')) ?> giờ</td>
                            <td><?= e($shifts[$item['shift'] ?? ''] ?? ($item['shift'] ?? '')) ?></td>
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
