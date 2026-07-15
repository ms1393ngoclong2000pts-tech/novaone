<?php
/** @var array<string, mixed> $hub */
/** @var array<string, mixed> $feature */
$sourceHref = (string) ($feature['href'] ?? '?route=home');
$sourceParts = parse_url(str_replace('&amp;', '&', $sourceHref));
parse_str((string) ($sourceParts['query'] ?? ''), $sourceQuery);
$key = (string) ($sourceQuery['key'] ?? 'hr');
?>
<section class="feature-hub-panel feature-detail-panel">
    <header class="feature-hub-hero">
        <div>
            <a class="back-button" href="?route=features&amp;key=<?= e($key) ?>" aria-label="Quay lại"><?= ui_icon('arrow') ?></a>
            <span class="feature-hub-icon"><?= ui_icon((string) ($feature['icon'] ?? 'file')) ?></span>
            <h2><?= e($feature['label'] ?? '') ?></h2>
            <p><?= e($feature['description'] ?? '') ?></p>
        </div>
        <a class="employee-action teal" href="?route=home">Trang chủ</a>
    </header>

    <div class="feature-detail-grid">
        <article>
            <h3>Mục tiêu nghiệp vụ</h3>
            <p>Tính năng này được đặt trong nhóm <?= e($hub['title'] ?? '') ?> để mở rộng quy trình hiện tại mà không tách rời dữ liệu chính của Novaone.</p>
        </article>
        <article>
            <h3>Dữ liệu nên liên kết</h3>
            <ul>
                <li>Nhân viên, phòng ban hoặc người phụ trách nếu có.</li>
                <li>Ngày tạo, trạng thái xử lý và người cập nhật cuối.</li>
                <li>Thông báo hoạt động và lịch sử thao tác.</li>
            </ul>
        </article>
        <article>
            <h3>Luồng đề xuất</h3>
            <ul>
                <li>Tạo bản ghi mới từ form chuẩn.</li>
                <li>Lọc, tìm kiếm, phân trang và xuất dữ liệu.</li>
                <li>Phân quyền xem, thêm, sửa, xóa theo vai trò.</li>
            </ul>
        </article>
        <article>
            <h3>Trạng thái triển khai</h3>
            <p>Đã có màn hình định hướng. Có thể nâng cấp tiếp thành bảng dữ liệu riêng khi chốt chi tiết trường thông tin.</p>
        </article>
    </div>
</section>
