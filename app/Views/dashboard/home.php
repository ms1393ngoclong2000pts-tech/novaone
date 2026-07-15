<?php
$tiles = [
    ['label' => 'NHÂN SỰ', 'href' => '?route=features&key=hr', 'icon' => 'users', 'accent' => 'yellow'],
    ['label' => 'QUẢN LÝ THÔNG TIN', 'href' => '?route=features&key=system', 'icon' => 'info', 'accent' => 'green'],
    ['label' => 'DỊCH VỤ', 'href' => '?route=features&key=services', 'icon' => 'lifebuoy', 'accent' => 'red'],
    ['label' => 'NHÀ CUNG CẤP', 'href' => '?route=features&key=suppliers', 'icon' => 'briefcase', 'accent' => 'purple'],
    ['label' => 'BÁO CÁO NHÂN SỰ', 'href' => '?route=features&key=hr-reports', 'icon' => 'users', 'accent' => 'pink'],
    ['label' => 'QUẢN LÝ KHO BÁN HÀNG', 'href' => '?route=features&key=sales-stock', 'icon' => 'settings', 'accent' => 'navy'],
    ['label' => 'BÁN HÀNG LẺ', 'href' => '?route=features&key=retail', 'icon' => 'cart', 'accent' => 'red'],
    ['label' => 'BÁO CÁO CÔNG VIỆC', 'href' => '?route=features&key=work-reports', 'icon' => 'file', 'accent' => 'navy'],
    ['label' => 'TRANG THIẾT BỊ', 'href' => '?route=features&key=equipment', 'icon' => 'briefcase', 'accent' => 'yellow'],
    ['label' => 'BÁN HÀNG', 'href' => '?route=features&key=sales', 'icon' => 'cart', 'accent' => 'red'],
    ['label' => 'CÔNG VIỆC', 'href' => '?route=features&key=work', 'icon' => 'check', 'accent' => 'green'],
    ['label' => 'TUYỂN DỤNG', 'href' => '?route=features&key=recruitment', 'icon' => 'monitor', 'accent' => 'yellow'],
    ['label' => 'ĐÀO TẠO', 'href' => '?route=features&key=training', 'icon' => 'book', 'accent' => 'purple'],
    ['label' => 'CSKH', 'href' => '?route=features&key=cskh', 'icon' => 'phone', 'accent' => 'pink'],
    ['label' => 'BÁO CÁO ĐÀO TẠO', 'href' => '?route=features&key=training-reports', 'icon' => 'file', 'accent' => 'navy'],
    ['label' => 'VẬN HÀNH', 'href' => '?route=features&key=operations', 'icon' => 'calendar', 'accent' => 'green'],
    ['label' => 'PHÂN QUYỀN', 'href' => '?route=permissions', 'icon' => 'settings', 'accent' => 'purple'],
    ['label' => 'TÀI KHOẢN', 'href' => '?route=users', 'icon' => 'users', 'accent' => 'yellow'],
    ['label' => 'BÁO CÁO TỔNG HỢP', 'href' => '?route=reports', 'icon' => 'pie', 'accent' => 'red'],
];
$tiles = array_values(array_filter($tiles, fn (array $tile): bool => can_access_route(href_route((string) ($tile['href'] ?? '')))));
?>

<section class="app-dashboard">
    <a class="mobile-home-back" href="?route=dashboard" aria-label="Quay lại"><?= ui_icon('arrow') ?></a>
    <h1>QUẢN LÝ ỨNG DỤNG</h1>
    <div class="app-grid">
        <?php foreach ($tiles as $tile): ?>
            <a class="app-tile accent-<?= e($tile['accent']) ?>" href="<?= e($tile['href']) ?>">
                <i class="tile-corner" aria-hidden="true"></i>
                <?= ui_icon($tile['icon']) ?>
                <strong><?= e($tile['label']) ?></strong>
            </a>
        <?php endforeach; ?>
    </div>
</section>
