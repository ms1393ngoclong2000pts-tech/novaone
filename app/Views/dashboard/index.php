<?php
$tiles = [
    ['label' => 'NHÂN SỰ', 'href' => '?route=employees', 'icon' => 'users', 'accent' => 'yellow'],
    ['label' => 'CÔNG VIỆC', 'href' => '?route=projects', 'icon' => 'check', 'accent' => 'green'],
    ['label' => 'NHÀ CUNG CẤP', 'href' => '?route=suppliers', 'icon' => 'briefcase', 'accent' => 'purple'],
    ['label' => 'TRANG THIẾT BỊ', 'href' => '?route=machine-warehouses', 'icon' => 'warehouse', 'accent' => 'blue'],
    ['label' => 'TUYỂN DỤNG', 'href' => '?route=recruitment-requests', 'icon' => 'monitor', 'accent' => 'green-dark'],
    ['label' => 'BÁO CÁO TỔNG HỢP', 'href' => '?route=reports', 'icon' => 'file', 'accent' => 'navy'],
];
?>
<section class="app-dashboard">
    <h1>QUẢN LÝ ỨNG DỤNG</h1>
    <div class="app-grid">
        <?php foreach ($tiles as $tile): ?>
            <a class="app-tile accent-<?= e($tile['accent']) ?>" href="<?= e($tile['href']) ?>">
                <span class="tile-corner"></span>
                <?= ui_icon($tile['icon']) ?>
                <strong><?= e($tile['label']) ?></strong>
            </a>
        <?php endforeach; ?>
    </div>
</section>
