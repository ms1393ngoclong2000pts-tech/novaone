<?php
/** @var array<string, mixed> $employee */
$employee = is_array($employee ?? null) ? $employee : [];
$contracts = is_array($contracts ?? null) ? $contracts : [];
$socialInsurance = is_array($socialInsurance ?? null) ? $socialInsurance : [];
$requests = is_array($requests ?? null) ? $requests : [];
$violations = is_array($violations ?? null) ? $violations : [];
$rewards = is_array($rewards ?? null) ? $rewards : [];
$workItems = is_array($workItems ?? null) ? $workItems : [];
$dailyReports = is_array($dailyReports ?? null) ? $dailyReports : [];

$statusLabels = ['active' => 'Đang làm việc', 'on_leave' => 'Đang nghỉ phép', 'inactive' => 'Đã nghỉ việc'];
$formatDate = fn ($value): string => $value ? date('d/m/Y', strtotime((string) $value)) : '---';
$rowText = fn ($value): string => trim((string) $value) !== '' ? (string) $value : '---';
$initial = function (string $name): string {
    $name = trim($name);
    if ($name === '') {
        return 'NS';
    }
    $parts = preg_split('/\s+/u', $name) ?: [];
    $last = end($parts);
    return function_exists('mb_substr') ? mb_strtoupper(mb_substr((string) $last, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr((string) $last, 0, 1));
};
?>

<section class="employee-profile-panel">
    <header class="employee-profile-head">
        <div>
            <a class="profile-back" href="?route=employees" title="Quay lại">←</a>
            <h2>HỒ SƠ NHÂN SỰ</h2>
        </div>
        <div class="employee-head-actions">
            <?php if ($employee['email'] !== ''): ?>
                <a class="employee-action blue" href="mailto:<?= e($employee['email']) ?>"><?= ui_icon('mail') ?> Gửi email</a>
            <?php endif; ?>
            <a class="employee-action teal" href="?route=employees">Danh sách nhân sự</a>
        </div>
    </header>

    <div class="employee-profile-body">
        <div class="employee-profile-hero">
            <div class="employee-profile-avatar"><?= e($initial($employee['name'])) ?></div>
            <div class="employee-profile-title">
                <h3><?= e($employee['name']) ?></h3>
                <p><?= e($rowText($employee['position'])) ?> · <?= e($rowText($employee['department'])) ?></p>
                <span class="employee-status <?= e($employee['status']) ?>"><?= e($statusLabels[$employee['status']] ?? $employee['status']) ?></span>
            </div>
        </div>

        <div class="employee-profile-grid">
            <?php foreach ([
                'Mã chấm công' => $employee['attendance_code'],
                'Mã nhân viên' => $employee['employee_code'],
                'Giới tính' => $employee['gender'],
                'Email' => $employee['email'],
                'Chức danh' => $employee['position'],
                'Bộ phận' => $employee['department'],
                'Loại hợp đồng' => $employee['contract'],
                'Tình trạng' => $statusLabels[$employee['status']] ?? $employee['status'],
            ] as $label => $value): ?>
                <article class="profile-info-card">
                    <span><?= e($label) ?></span>
                    <strong><?= e($rowText($value)) ?></strong>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="profile-related-grid">
            <section class="profile-related">
                <h3>Hợp đồng lao động</h3>
                <div class="profile-mini-table-wrap">
                    <table class="profile-mini-table">
                        <thead><tr><th>Mã HĐ</th><th>Loại</th><th>Lương</th><th>Hiệu lực</th></tr></thead>
                        <tbody>
                            <?php foreach ($contracts as $item): ?>
                                <tr>
                                    <td><?= e($rowText($item['contract_code'] ?? '')) ?></td>
                                    <td><?= e($rowText($item['contract_type'] ?? '')) ?></td>
                                    <td><?= e(money($item['salary'] ?? 0)) ?></td>
                                    <td><?= e($formatDate($item['start_date'] ?? '')) ?> - <?= e($formatDate($item['end_date'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($contracts)): ?><tr><td colspan="4">Chưa có dữ liệu</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="profile-related">
                <h3>Bảo hiểm xã hội</h3>
                <div class="profile-mini-table-wrap">
                    <table class="profile-mini-table">
                        <thead><tr><th>Số BHXH</th><th>Lương</th><th>Đóng BHXH</th><th>Hợp đồng</th></tr></thead>
                        <tbody>
                            <?php foreach ($socialInsurance as $item): ?>
                                <tr>
                                    <td><?= e($rowText($item['insurance_number'] ?? '')) ?></td>
                                    <td><?= e(money($item['salary'] ?? 0)) ?></td>
                                    <td><?= e(money($item['contribution'] ?? 0)) ?></td>
                                    <td><?= e($formatDate($item['contract_start'] ?? '')) ?> - <?= e($formatDate($item['contract_end'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($socialInsurance)): ?><tr><td colspan="4">Chưa có dữ liệu</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="profile-related">
                <h3>Phiếu yêu cầu</h3>
                <div class="profile-mini-table-wrap">
                    <table class="profile-mini-table">
                        <thead><tr><th>Loại</th><th>Ngày tạo</th><th>Thời gian</th><th>Phê duyệt</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($requests, 0, 5) as $item): ?>
                                <tr>
                                    <td><?= e($rowText($item['request_type'] ?? '')) ?></td>
                                    <td><?= e($formatDate($item['created_date'] ?? '')) ?></td>
                                    <td><?= e($formatDate($item['start_date'] ?? '')) ?> - <?= e($formatDate($item['end_date'] ?? '')) ?></td>
                                    <td><?= e($rowText($item['approval'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($requests)): ?><tr><td colspan="4">Chưa có dữ liệu</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="profile-related profile-summary-list">
                <h3>Kỷ luật và khen thưởng</h3>
                <div>
                    <strong><?= count($violations) ?></strong>
                    <span>Phiếu vi phạm</span>
                </div>
                <div>
                    <strong><?= count($rewards) ?></strong>
                    <span>Phiếu khen thưởng</span>
                </div>
                <div>
                    <strong><?= count($workItems) ?></strong>
                    <span>Công việc được giao</span>
                </div>
                <div>
                    <strong><?= count($dailyReports) ?></strong>
                    <span>Báo cáo hằng ngày</span>
                </div>
            </section>
        </div>
    </div>
</section>
