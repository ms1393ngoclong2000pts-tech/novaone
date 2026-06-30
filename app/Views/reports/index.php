<?php
$moduleLabels = [
    'all' => 'Tất cả báo cáo',
    'human' => 'Nhân sự',
    'work' => 'Công việc',
    'sales' => 'Bán hàng',
    'inventory' => 'Trang thiết bị',
    'recruitment' => 'Tuyển dụng',
    'attendance' => 'Chấm công',
];
$visibleReports = $filters['module'] === 'all'
    ? $miniReports
    : array_values(array_filter($miniReports, fn (array $item): bool => ($item['id'] ?? '') === $filters['module']));
$maxSales = max(1, ...array_map(fn (array $item): float => (float) ($item['amount'] ?? 0), $salesByStage));
$maxWorkHours = max(1, ...array_map(fn (array $item): float => (float) str_replace(',', '.', (string) ($item['hours'] ?? 0)), $topWorkHours ?: [['hours' => 0]]));
$maxAttendanceHours = max(1, ...array_map(fn (array $item): float => (float) str_replace(',', '.', (string) ($item['hours'] ?? 0)), $attendanceByEmployee ?: [['hours' => 0]]));
$formatMoney = fn (float $value): string => number_format($value, 0, ',', '.') . ' VNĐ';
?>

<section class="report-page">
    <header class="report-hero">
        <div>
            <span>Báo cáo điều hành</span>
            <h1>Tổng quan hoạt động Novaone</h1>
            <p>Theo dõi nhanh nhân sự, công việc, bán hàng, kho, tuyển dụng và chấm công trên cùng một màn hình.</p>
        </div>
        <div class="report-hero-actions">
            <a class="report-btn soft" href="?route=dashboard"><?= ui_icon('pie') ?> Dashboard</a>
            <a class="report-btn primary" href="<?= e($exportUrl) ?>"><?= ui_icon('file') ?> Xuất CSV</a>
        </div>
    </header>

    <form class="report-filter" method="get">
        <input type="hidden" name="route" value="reports">
        <label>
            <span>Từ ngày</span>
            <input type="date" name="from" value="<?= e($filters['from']) ?>">
        </label>
        <label>
            <span>Đến ngày</span>
            <input type="date" name="to" value="<?= e($filters['to']) ?>">
        </label>
        <label>
            <span>Nhóm báo cáo</span>
            <select name="module">
                <?php foreach ($moduleLabels as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filters['module'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="report-btn primary" type="submit"><?= ui_icon('search') ?> Lọc báo cáo</button>
        <a class="report-btn ghost" href="?route=reports">Đặt lại</a>
    </form>

    <div class="report-summary-grid">
        <?php foreach ($summary as $item): ?>
            <article class="report-kpi tone-<?= e($item['tone']) ?>">
                <span><?= e($item['label']) ?></span>
                <strong><?= e($item['value']) ?></strong>
                <small><?= e($item['note']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="report-section">
        <div class="report-section-head">
            <div>
                <h2>Báo cáo nhỏ</h2>
                <p>Các báo cáo nghiệp vụ có thể mở nhanh sang màn hình chi tiết.</p>
            </div>
        </div>
        <div class="mini-report-grid">
            <?php foreach ($visibleReports as $section): ?>
                <article class="mini-report-card">
                    <header>
                        <span class="mini-report-icon"><?= ui_icon($section['icon']) ?></span>
                        <div>
                            <h3><?= e($section['title']) ?></h3>
                            <p><?= e($section['subtitle']) ?></p>
                        </div>
                    </header>
                    <div class="mini-report-items">
                        <?php foreach ($section['items'] as $item): ?>
                            <div>
                                <span><?= e($item['label']) ?></span>
                                <strong><?= e((string) $item['value']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= e($section['href']) ?>">Xem chi tiết <?= ui_icon('arrow') ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="report-columns">
        <section class="report-section">
            <div class="report-section-head">
                <div>
                    <h2>Doanh thu theo giai đoạn</h2>
                    <p>Theo dõi giá trị đơn hàng từ khởi tạo đến thanh toán.</p>
                </div>
            </div>
            <div class="report-bars">
                <?php foreach ($salesByStage as $row): ?>
                    <?php $width = max(6, min(100, ((float) $row['amount'] / $maxSales) * 100)); ?>
                    <div class="report-bar-row">
                        <div><strong><?= e($row['label']) ?></strong><span><?= (int) $row['count'] ?> đơn</span></div>
                        <i><b style="width: <?= e((string) $width) ?>%"></b></i>
                        <em><?= e($formatMoney((float) $row['amount'])) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="report-section">
            <div class="report-section-head">
                <div>
                    <h2>Giờ báo cáo theo nhân sự</h2>
                    <p>Dựa trên dữ liệu báo cáo hằng ngày.</p>
                </div>
            </div>
            <div class="report-rank-list">
                <?php if (count($topWorkHours) === 0): ?>
                    <p class="report-empty">Chưa có dữ liệu báo cáo hằng ngày trong khoảng lọc.</p>
                <?php endif; ?>
                <?php foreach ($topWorkHours as $index => $row): ?>
                    <?php $hours = (float) str_replace(',', '.', (string) $row['hours']); $width = max(8, min(100, ($hours / $maxWorkHours) * 100)); ?>
                    <div class="report-rank-row">
                        <span><?= $index + 1 ?></span>
                        <div>
                            <strong><?= e($row['name']) ?></strong>
                            <i><b style="width: <?= e((string) $width) ?>%"></b></i>
                        </div>
                        <em><?= e((string) $row['hours']) ?>h</em>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="report-columns">
        <section class="report-section">
            <div class="report-section-head">
                <div>
                    <h2>Giờ chấm công theo nhân sự</h2>
                    <p>Tổng hợp từ bản ghi chấm công thực tế.</p>
                </div>
                <a href="?route=attendance">Mở chấm công</a>
            </div>
            <div class="report-rank-list">
                <?php if (count($attendanceByEmployee) === 0): ?>
                    <p class="report-empty">Chưa có dữ liệu chấm công trong khoảng lọc.</p>
                <?php endif; ?>
                <?php foreach ($attendanceByEmployee as $index => $row): ?>
                    <?php $hours = (float) str_replace(',', '.', (string) $row['hours']); $width = max(8, min(100, ($hours / $maxAttendanceHours) * 100)); ?>
                    <div class="report-rank-row attendance">
                        <span><?= $index + 1 ?></span>
                        <div>
                            <strong><?= e($row['name']) ?></strong>
                            <i><b style="width: <?= e((string) $width) ?>%"></b></i>
                        </div>
                        <em><?= e((string) $row['hours']) ?>h</em>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="report-section">
            <div class="report-section-head">
                <div>
                    <h2>Hoạt động gần đây</h2>
                    <p>Các thông báo hệ thống mới nhất.</p>
                </div>
                <a href="?route=notification.readAll">Đánh dấu đã đọc</a>
            </div>
            <div class="report-activity-list">
                <?php foreach ($recentActivities as $item): ?>
                    <article>
                        <span class="activity-dot <?= e($item['type']) ?>"></span>
                        <div>
                            <strong><?= e($item['title']) ?></strong>
                            <p><?= e($item['message']) ?></p>
                            <small><?= e($item['time']) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
