<?php
/** @var array<int, array<string, mixed>> $items */
/** @var array<string, string> $filters */
/** @var array<int, string> $modules */
/** @var array<int, string> $actions */
/** @var string $exportUrl */
?>

<section class="report-page activity-page">
    <header class="report-hero">
        <div>
            <span>Kiểm soát hệ thống</span>
            <h1>Lịch sử thao tác</h1>
            <p>Theo dõi thao tác thêm, sửa, xóa, nhập dữ liệu và các cập nhật quan trọng trong NovaOne.</p>
        </div>
        <div class="report-hero-actions">
            <a class="report-btn soft" href="?route=reports"><?= ui_icon('file') ?> Báo cáo</a>
            <a class="report-btn primary" href="<?= e($exportUrl) ?>"><?= ui_icon('file') ?> Xuất CSV</a>
        </div>
    </header>

    <form class="report-filter activity-filter" method="get">
        <input type="hidden" name="route" value="activity-log">
        <label>
            <span>Tìm kiếm</span>
            <input name="q" value="<?= e($filters['q']) ?>" placeholder="Người dùng, nội dung, module...">
        </label>
        <label>
            <span>Module</span>
            <select name="module">
                <option value="">Tất cả module</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= e($module) ?>" <?= $filters['module'] === $module ? 'selected' : '' ?>><?= e(label_value($module)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Hành động</span>
            <select name="action">
                <option value="">Tất cả hành động</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e(label_value($action)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="report-btn primary" type="submit"><?= ui_icon('search') ?> Lọc</button>
        <a class="report-btn ghost" href="?route=activity-log">Đặt lại</a>
    </form>

    <section class="report-section">
        <div class="report-section-head">
            <div>
                <h2>Nhật ký gần đây</h2>
                <p>Hiển thị tối đa 120 dòng gần nhất theo bộ lọc hiện tại.</p>
            </div>
        </div>
        <div class="table-wrap activity-table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người dùng</th>
                    <th>Module</th>
                    <th>Hành động</th>
                    <th>Nội dung</th>
                    <th>Liên kết</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['created_at'] ?? '') ?></td>
                        <td>
                            <strong><?= e($item['user_name'] ?? 'Hệ thống') ?></strong>
                            <small><?= e($item['user_role'] ?? '') ?></small>
                        </td>
                        <td><?= e(label_value((string) ($item['module'] ?? ''))) ?></td>
                        <td><?= badge((string) ($item['action'] ?? 'info')) ?></td>
                        <td>
                            <strong><?= e($item['title'] ?? '') ?></strong>
                            <p><?= e($item['message'] ?? '') ?></p>
                        </td>
                        <td><a class="report-btn ghost" href="<?= e(safe_internal_href((string) ($item['href'] ?? ''), '?route=dashboard')) ?>">Mở</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($items === []): ?>
                    <tr><td colspan="6" class="empty">Chưa có lịch sử thao tác phù hợp.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
