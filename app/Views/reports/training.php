<?php
/** @var array<int, array<string, string>> $items */
/** @var array<int, array<string, string>> $summary */
/** @var array<int, array<string, mixed>> $statusRows */
/** @var string $query */
/** @var string $status */
$maxStatus = max(1, ...array_map(fn (array $row): int => (int) ($row['count'] ?? 0), $statusRows));
?>

<section class="report-page">
    <header class="report-hero">
        <div>
            <span>Đào tạo nội bộ</span>
            <h1>Báo cáo đào tạo</h1>
            <p>Theo dõi tiến độ khóa học, người đào tạo, nhân viên tham gia và trạng thái hoàn thành.</p>
        </div>
        <div class="report-hero-actions">
            <a class="report-btn soft" href="?route=training"><?= ui_icon('book') ?> Danh sách đào tạo</a>
            <a class="report-btn primary" href="?route=reports"><?= ui_icon('file') ?> Báo cáo tổng hợp</a>
        </div>
    </header>

    <form class="report-filter" method="get">
        <input type="hidden" name="route" value="training-reports">
        <label>
            <span>Tìm kiếm</span>
            <input name="q" value="<?= e($query) ?>" placeholder="Khóa học, nhân viên, giảng viên...">
        </label>
        <label>
            <span>Trạng thái</span>
            <select name="status">
                <option value="">Tất cả</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ học</option>
                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>Đang học</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
            </select>
        </label>
        <button class="report-btn primary" type="submit"><?= ui_icon('search') ?> Lọc</button>
        <a class="report-btn ghost" href="?route=training-reports">Đặt lại</a>
    </form>

    <div class="report-summary-grid">
        <?php foreach ($summary as $item): ?>
            <article class="report-kpi tone-<?= e($item['tone']) ?>">
                <span><?= e($item['label']) ?></span>
                <strong><?= e($item['value']) ?></strong>
                <small>Dữ liệu từ phân hệ đào tạo</small>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="report-columns">
        <section class="report-section">
            <div class="report-section-head">
                <div>
                    <h2>Phân bổ trạng thái</h2>
                    <p>Tổng hợp nhanh theo trạng thái khóa học.</p>
                </div>
            </div>
            <div class="report-bars">
                <?php foreach ($statusRows as $row): ?>
                    <?php $width = max(8, min(100, ((int) $row['count'] / $maxStatus) * 100)); ?>
                    <div class="report-bar-row">
                        <div><strong><?= e($row['label']) ?></strong><span><?= (int) $row['count'] ?> bản ghi</span></div>
                        <i><b style="width: <?= e((string) $width) ?>%"></b></i>
                        <em><?= badge((string) $row['status']) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="report-section">
            <div class="report-section-head">
                <div>
                    <h2>Danh sách đào tạo</h2>
                    <p>Các khóa học đang được quản lý trong hệ thống.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Khóa đào tạo</th>
                        <th>Nhân viên</th>
                        <th>Người đào tạo</th>
                        <th>Tiến độ</th>
                        <th>Trạng thái</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['course'] ?? '') ?></td>
                            <td><?= e($item['employee'] ?? '') ?></td>
                            <td><?= e($item['trainer'] ?? '') ?></td>
                            <td><?= e((string) ($item['progress'] ?? 0)) ?>%</td>
                            <td><?= badge((string) ($item['status'] ?? 'pending')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($items) === 0): ?>
                        <tr><td colspan="5" class="empty">Không có dữ liệu phù hợp.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
