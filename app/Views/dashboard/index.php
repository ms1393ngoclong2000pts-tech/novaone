<?php
$donePercent = min(100, max(0, ($taskDone / max(1, $taskTotal)) * 100));
$progressPercent = 100 - $donePercent;
$serviceMax = max(10, $productCount, $serviceCount);
$barWidth = min(100, max(6, $customerGroupCount * 24));
$formatMoney = fn (float $value): string => number_format($value, 0, ',', '.') . ' VNĐ';
?>

<section class="analytics-dashboard">
    <?php if (! empty($roleSummary)): ?>
        <section class="role-dashboard">
            <div>
                <span><?= e($role ?? current_user_role()) ?></span>
                <h2><?= e($roleSummary['title'] ?? 'Bảng điều hành') ?></h2>
                <p><?= e($roleSummary['description'] ?? '') ?></p>
            </div>
            <div class="role-dashboard-items">
                <?php foreach (($roleSummary['items'] ?? []) as $item): ?>
                    <a href="<?= e($item['href'] ?? '?route=dashboard') ?>">
                        <small><?= e($item['label'] ?? '') ?></small>
                        <strong><?= e((string) ($item['value'] ?? 0)) ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    <div class="analytics-grid">
        <article class="analytics-card task-card">
            <header>Công Việc Trên Hệ Thống</header>
            <div class="pie-wrap">
                <div class="task-pie" style="--done: <?= e((string) $donePercent) ?>;"></div>
                <div class="pie-label done">Hoàn thành<br><?= e(number_format($donePercent, 2)) ?>%</div>
                <div class="pie-label progress">Đang tiến hành<br><?= e(number_format($progressPercent, 2)) ?>%</div>
            </div>
            <footer><span>Trial Version</span><i class="dot red"></i> Hoàn thành <i class="dot green"></i> Đang tiến hành</footer>
        </article>

        <article class="analytics-card service-card">
            <header>Số Lượng SP Và Dịch Vụ Sử Dụng</header>
            <div class="line-chart" style="--product: <?= e((string) min(100, ($productCount / $serviceMax) * 100)) ?>%; --service: <?= e((string) min(100, ($serviceCount / $serviceMax) * 100)) ?>%;">
                <div class="grid-lines"></div>
                <span class="axis-value top">10</span>
                <span class="axis-value mid">6</span>
                <span class="axis-value low">2</span>
                <span class="month m1">Jan</span><span class="month m2">Mar</span><span class="month m3">May</span><span class="month m4">Jul</span><span class="month m5">Sep</span><span class="month m6">Nov</span>
                <i class="point product p1"></i><i class="point service p2"></i>
            </div>
            <footer><span>Trial Version</span><b class="legend blue">▲ Sản Phẩm</b><b class="legend red">▲ Dịch Vụ</b></footer>
        </article>

        <article class="analytics-card customer-card">
            <header>Loại Khách Hàng</header>
            <div class="bar-chart">
                <span class="bar-label">Khách hàng</span>
                <i class="bar" style="width: <?= e((string) $barWidth) ?>%;"></i>
                <span class="bar-axis">Số Lượng</span>
            </div>
            <footer>Trial Version</footer>
        </article>

        <aside class="module-wheel" aria-label="bData modules">
            <div class="wheel-center"><span class="mini-logo"></span><strong>b Data</strong></div>
            <div class="wheel-segment crm">CRM <?= ui_icon('briefcase') ?></div>
            <div class="wheel-segment hr">Nhân Sự <?= ui_icon('users') ?></div>
            <div class="wheel-segment work">Công Việc <?= ui_icon('pie') ?></div>
            <div class="wheel-segment finance">Tài Chính <?= ui_icon('check') ?></div>
            <div class="wheel-segment accounting">Kế Toán <?= ui_icon('file') ?></div>
        </aside>

        <article class="analytics-card attendance-card">
            <header>Dữ Liệu Chấm Công</header>
            <div class="empty-chart">
                <span class="y-label">Số Lượng</span>
                <strong><?= e(rtrim(rtrim(number_format((float) $attendanceHours, 2, '.', ''), '0'), '.')) ?> giờ</strong>
                <small><?= (int) $attendancePeople ?> nhân viên</small>
            </div>
            <footer>Trial Version</footer>
        </article>

        <article class="sales-summary-card">
            <h3>Dữ Liệu Bán Hàng</h3>
            <div class="summary-block total">
                <span>TỔNG TIỀN ĐƠN HÀNG</span>
                <strong><?= e($formatMoney((float) $orderTotal)) ?></strong>
            </div>
            <div class="summary-row">
                <div class="summary-block quote">
                    <span>TỔNG TIỀN BÁO GIÁ</span>
                    <strong><?= e($formatMoney((float) $quoteTotal)) ?></strong>
                </div>
                <div class="summary-block contract">
                    <span>TỔNG TIỀN HỢP ĐỒNG</span>
                    <strong><?= e($formatMoney((float) $contractTotal)) ?></strong>
                </div>
            </div>
        </article>
    </div>

    <a class="dashboard-float" href="?route=reports" aria-label="Báo cáo"><?= ui_icon('file') ?></a>
</section>
