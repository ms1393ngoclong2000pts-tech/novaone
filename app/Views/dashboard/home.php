<?php
$tiles = [
    ['label' => 'NHÂN SỰ', 'href' => '?route=employees', 'icon' => 'users', 'accent' => 'yellow'],
    ['label' => 'CÔNG VIỆC', 'href' => '?route=projects', 'icon' => 'check', 'accent' => 'green'],
    ['label' => 'NHÀ CUNG CẤP', 'href' => '?route=suppliers', 'icon' => 'briefcase', 'accent' => 'purple'],
    ['label' => 'BÁN HÀNG', 'href' => '?route=sales-orders', 'icon' => 'cart', 'accent' => 'red'],
    ['label' => 'TRANG THIẾT BỊ', 'href' => '?route=machine-warehouses', 'icon' => 'briefcase', 'accent' => 'navy'],
    ['label' => 'TUYỂN DỤNG', 'href' => '?route=recruitment-requests', 'icon' => 'monitor', 'accent' => 'yellow'],
    ['label' => 'BÁO CÁO', 'href' => '?route=reports', 'icon' => 'file', 'accent' => 'pink'],
    ['label' => 'DASHBOARD', 'href' => '?route=dashboard', 'icon' => 'pie', 'accent' => 'green-dark'],
    ['label' => 'GỌI ĐIỆN', 'href' => '?route=calls', 'icon' => 'phone', 'accent' => 'blue'],
    ['label' => 'PHÂN QUYỀN', 'href' => '?route=permissions', 'icon' => 'settings', 'accent' => 'navy'],
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
