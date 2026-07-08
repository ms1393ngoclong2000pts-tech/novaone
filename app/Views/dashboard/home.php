<?php
$tiles = [
    ['label' => 'NHÂN SỰ', 'href' => '?route=employees', 'icon' => 'users', 'accent' => 'yellow'],
    ['label' => 'QUẢN LÝ THÔNG TIN', 'href' => '?route=permissions', 'icon' => 'info', 'accent' => 'green'],
    ['label' => 'DỊCH VỤ', 'href' => '?route=services', 'icon' => 'lifebuoy', 'accent' => 'red'],
    ['label' => 'NHÀ CUNG CẤP', 'href' => '?route=suppliers', 'icon' => 'briefcase', 'accent' => 'purple'],
    ['label' => 'BÁO CÁO NHÂN SỰ', 'href' => '?route=reports', 'icon' => 'users', 'accent' => 'pink'],
    ['label' => 'QUẢN LÝ KHO BÁN HÀNG', 'href' => '?route=products', 'icon' => 'settings', 'accent' => 'navy'],
    ['label' => 'TRANG THIẾT BỊ', 'href' => '?route=machine-warehouses', 'icon' => 'briefcase', 'accent' => 'yellow'],
    ['label' => 'BÁN HÀNG', 'href' => '?route=sales-orders', 'icon' => 'cart', 'accent' => 'red'],
    ['label' => 'CÔNG VIỆC', 'href' => '?route=projects', 'icon' => 'check', 'accent' => 'green'],
    ['label' => 'TUYỂN DỤNG', 'href' => '?route=recruitment-requests', 'icon' => 'monitor', 'accent' => 'yellow'],
    ['label' => 'ĐÀO TẠO', 'href' => '?route=reports', 'icon' => 'file', 'accent' => 'purple'],
    ['label' => 'CSKH', 'href' => '?route=calls', 'icon' => 'phone', 'accent' => 'pink'],
    ['label' => 'BÁO CÁO ĐÀO TẠO', 'href' => '?route=reports', 'icon' => 'file', 'accent' => 'navy'],
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
