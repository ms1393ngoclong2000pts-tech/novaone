<?php
$weekLabels = ['CHỦ NHẬT', 'THỨ 2', 'THỨ 3', 'THỨ 4', 'THỨ 5', 'THỨ 6', 'THỨ 7'];
?>

<section class="attendance-panel">
    <header class="attendance-head">
        <div class="attendance-title-row">
            <?= back_link('attendance') ?>
            <h2>XỬ LÝ CHẤM CÔNG</h2>
        </div>
        <div class="attendance-head-actions">
            <form method="post" action="?route=attendance.process.generate">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="department" value="<?= e($department) ?>">
                <input type="hidden" name="employee_id" value="<?= e($employeeId) ?>">
                <input type="hidden" name="start_date" value="<?= e($startDate) ?>">
                <input type="hidden" name="end_date" value="<?= e($endDate) ?>">
                <button class="employee-action teal" type="submit">Tổng công</button>
            </form>
            <form method="post" action="?route=attendance.process.violation">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="department" value="<?= e($department) ?>">
                <input type="hidden" name="employee_id" value="<?= e($employeeId) ?>">
                <input type="hidden" name="start_date" value="<?= e($startDate) ?>">
                <input type="hidden" name="end_date" value="<?= e($endDate) ?>">
                <button class="employee-action amber" type="submit">Vi phạm</button>
            </form>
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

        <form class="attendance-filter process-filter" method="get">
            <input type="hidden" name="route" value="attendance.process">
            <label><span>Bộ phận</span><select name="department" onchange="this.form.submit()"><option value="">--- ---</option><?php foreach ($departments as $item): ?><option value="<?= e($item) ?>" <?= $department === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
            <label><span>Nhân viên</span><select name="employee_id" onchange="this.form.submit()"><option value="">--- ---</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['id'] ?? '') ?>" <?= $employeeId === ($employee['id'] ?? '') ? 'selected' : '' ?>><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Từ Ngày</span><input name="start_date" type="date" value="<?= e($startDate) ?>"></label>
            <label><span>Đến Ngày</span><input name="end_date" type="date" value="<?= e($endDate) ?>"></label>
            <button class="employee-action blue" type="submit">Lọc</button>
        </form>

        <div class="attendance-summary">
            <span><strong><?= (int) ($summary['records'] ?? 0) ?></strong> dòng công</span>
            <span><strong><?= e(rtrim(rtrim(number_format((float) ($summary['hours'] ?? 0), 2, '.', ''), '0'), '.')) ?></strong> giờ</span>
            <span><strong><?= (int) ($summary['employees'] ?? 0) ?></strong> nhân viên</span>
            <span><strong><?= (int) ($summary['violations'] ?? 0) ?></strong> thiếu giờ</span>
        </div>

        <div class="attendance-calendar-wrap">
            <table class="attendance-calendar">
                <thead>
                    <tr><?php foreach ($weekLabels as $label): ?><th><?= e($label) ?></th><?php endforeach; ?></tr>
                </thead>
                <tbody>
                    <?php foreach ($weekRows as $row): ?>
                        <tr>
                            <?php foreach ($row as $date): ?>
                                <td><?= $date !== '' ? e(date('d/m/Y', strtotime($date))) : '' ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
