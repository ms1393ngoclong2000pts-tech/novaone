<?php
$tiles = [
    ['label' => 'NHÂN SỰ', 'href' => '?route=employees', 'icon' => 'users', 'accent' => 'yellow'],
    ['label' => 'QUẢN LÝ THÔNG TIN', 'href' => '?route=dashboard', 'icon' => 'info', 'accent' => 'green'],
    ['label' => 'DỊCH VỤ', 'href' => '?route=suppliers', 'icon' => 'lifebuoy', 'accent' => 'orange'],
    ['label' => 'NHÀ CUNG CẤP', 'href' => '?route=suppliers', 'icon' => 'briefcase', 'accent' => 'purple'],
    ['label' => 'BÁO CÁO NHÂN SỰ', 'href' => '?route=reports', 'icon' => 'users', 'accent' => 'pink'],
    ['label' => 'QUẢN LÝ KHO BÁN HÀNG', 'href' => '?route=machine-warehouses', 'icon' => 'command', 'accent' => 'blue'],
    ['label' => 'BÁN HÀNG LẺ', 'href' => '?route=sales-orders', 'icon' => 'cart', 'accent' => 'blue'],
    ['label' => 'BÁO CÁO CÔNG VIỆC', 'href' => '?route=reports', 'icon' => 'file', 'accent' => 'navy'],
    ['label' => 'TRANG THIẾT BỊ', 'href' => '?route=machine-warehouses', 'icon' => 'briefcase', 'accent' => 'navy'],
    ['label' => 'LỊCH', 'href' => '?route=attendance', 'icon' => 'calendar', 'accent' => 'green-dark'],
    ['label' => 'BÁN HÀNG', 'href' => '?route=sales-orders', 'icon' => 'cart', 'accent' => 'red'],
    ['label' => 'CÔNG VIỆC', 'href' => '?route=projects', 'icon' => 'check', 'accent' => 'green'],
    ['label' => 'TUYỂN DỤNG', 'href' => '?route=recruitment-requests', 'icon' => 'monitor', 'accent' => 'yellow'],
    ['label' => 'ĐÀO TẠO', 'href' => '?route=reports', 'icon' => 'book', 'accent' => 'blue'],
    ['label' => 'CSKH', 'href' => '?route=suppliers', 'icon' => 'award', 'accent' => 'pink'],
    ['label' => 'BÁO CÁO ĐÀO TẠO', 'href' => '?route=reports', 'icon' => 'book', 'accent' => 'navy'],
    ['label' => 'KHO NỘI BỘ', 'href' => '?route=machine-warehouses', 'icon' => 'warehouse', 'accent' => 'yellow'],
    ['label' => 'KPI', 'href' => '?route=dashboard', 'icon' => 'pie', 'accent' => 'navy'],
    ['label' => 'VẬN CHUYỂN', 'href' => '?route=purchasing', 'icon' => 'truck', 'accent' => 'yellow'],
    ['label' => 'OKR', 'href' => '?route=projects', 'icon' => 'command', 'accent' => 'green-dark'],
];
$tiles = array_values(array_filter($tiles, fn (array $tile): bool => can_access_route(href_route((string) ($tile['href'] ?? '')))));
?>

<section class="app-dashboard">
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
