<?php
/** @var string $content */
/** @var string|null $active */
$unreadNotifications = unread_notifications();
$allNotifications = notifications_data();
$visibleNotifications = array_slice($allNotifications, 0, 10);
$extraNotifications = array_slice($allNotifications, 10);
$hasMoreNotifications = count($extraNotifications) > 0;

$groups = [
    'QUẢN LÝ NHÂN SỰ' => [
        ['id' => 'employees', 'label' => 'Nhân sự', 'href' => '?route=employees', 'icon' => 'users', 'children' => [
            ['label' => 'Danh Sách Nhân Viên', 'href' => '?route=employees', 'route' => 'employees'],
            ['label' => 'Hợp Đồng Lao Động', 'href' => '?route=contracts', 'route' => 'contracts'],
            ['label' => 'Chấm Công', 'href' => '?route=attendance', 'route' => 'attendance'],
            ['label' => 'Bảng Lương', 'href' => '?route=payrolls', 'route' => 'payrolls'],
            ['label' => 'Bảo Hiểm Xã Hội', 'href' => '?route=social-insurance', 'route' => 'social-insurance'],
            ['label' => 'Phiếu Yêu Cầu', 'href' => '?route=requests', 'route' => 'requests'],
            ['label' => 'Danh Sách Vi Phạm', 'href' => '?route=violations', 'route' => 'violations'],
            ['label' => 'Danh Sách Khen Thưởng', 'href' => '?route=rewards', 'route' => 'rewards'],
        ]],
        ['id' => 'tasks', 'label' => 'Công việc', 'href' => '?route=tasks', 'icon' => 'check', 'children' => [
            ['label' => 'Dự Án', 'href' => '?route=projects', 'route' => 'projects'],
            ['label' => 'Danh Sách Công Việc', 'href' => '?route=work-items', 'route' => 'work-items'],
            ['label' => 'Báo Cáo Hàng Ngày', 'href' => '?route=daily-reports', 'route' => 'daily-reports'],
        ]],
        ['id' => 'training', 'label' => 'Đào tạo', 'href' => '?route=training', 'icon' => 'book'],
    ],
    'KINH DOANH' => [
        ['id' => 'suppliers', 'label' => 'Nhà cung cấp', 'href' => '?route=suppliers', 'icon' => 'briefcase'],
        ['id' => 'services', 'label' => 'Dịch vụ', 'href' => '?route=services', 'icon' => 'lifebuoy', 'children' => [
            ['label' => 'Danh Sách Dịch Vụ', 'href' => '?route=services', 'route' => 'services'],
            ['label' => 'Danh Sách Sản Phẩm', 'href' => '?route=products', 'route' => 'products'],
        ]],
        ['id' => 'sales', 'label' => 'Bán hàng', 'href' => '?route=sales-orders', 'icon' => 'cart', 'children' => [
            ['label' => 'Đơn Hàng', 'href' => '?route=sales-orders', 'route' => 'sales-orders'],
            ['label' => 'Báo Giá', 'href' => '?route=sales-orders&stage=quote', 'route' => 'sales-orders.quote'],
            ['label' => 'Hợp Đồng', 'href' => '?route=sales-orders&stage=contract', 'route' => 'sales-orders.contract'],
            ['label' => 'Nghiệm Thu', 'href' => '?route=sales-orders&stage=paid', 'route' => 'sales-orders.paid'],
            ['label' => 'Chỉ Tiêu Tháng', 'href' => '?route=sales-targets', 'route' => 'sales-targets'],
            ['label' => 'Phiếu Bán Hàng', 'href' => '?route=sales-receipts', 'route' => 'sales-receipts'],
        ]],
    ],
    'QUẢN LÝ KHO' => [
        ['id' => 'internal_assets', 'label' => 'Trang thiết bị', 'href' => '?route=machine-warehouses', 'icon' => 'briefcase', 'children' => [
            ['label' => 'Kho Máy', 'href' => '?route=machine-warehouses', 'route' => 'machine-warehouses'],
            ['label' => 'Quản Lý Thiết Bị', 'href' => '?route=equipment-devices', 'route' => 'equipment-devices'],
            ['label' => 'Loại Thiết Bị', 'href' => '?route=equipment-types', 'route' => 'equipment-types'],
            ['label' => 'Mua Sắm', 'href' => '?route=purchasing', 'route' => 'purchasing'],
        ]],
    ],
    'BÁO CÁO' => [
        ['id' => 'reports', 'label' => 'Báo cáo tổng hợp', 'href' => '?route=reports', 'icon' => 'file'],
        ['id' => 'training_reports', 'label' => 'Báo cáo đào tạo', 'href' => '?route=training-reports', 'icon' => 'book'],
    ],
    'QUẢN LÝ HỆ THỐNG' => [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '?route=dashboard', 'icon' => 'monitor'],
        ['id' => 'settings', 'label' => 'Quản lý thông tin', 'href' => '?route=settings', 'icon' => 'info'],
        ['id' => 'users', 'label' => 'Tài khoản người dùng', 'href' => '?route=users', 'icon' => 'users'],
        ['id' => 'activity_log', 'label' => 'Lịch sử thao tác', 'href' => '?route=activity-log', 'icon' => 'file'],
        ['id' => 'permissions', 'label' => 'Phân quyền', 'href' => '?route=permissions', 'icon' => 'settings'],
        ['id' => 'recruitments', 'label' => 'Tuyển dụng', 'href' => '?route=recruitment-requests', 'icon' => 'monitor', 'children' => [
            ['label' => 'Phiếu Yêu Cầu Tuyển Dụng', 'href' => '?route=recruitment-requests', 'route' => 'recruitment-requests'],
        ]],
    ],
];
$groups = [
    'TRANG CHỦ ỨNG DỤNG' => [
        ['id' => 'home', 'label' => 'Quản lý ứng dụng', 'href' => '?route=home', 'icon' => 'command'],
        ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '?route=dashboard', 'icon' => 'monitor'],
    ],
    'QUẢN LÝ NHÂN SỰ' => [
        ['id' => 'employees', 'label' => 'Nhân sự', 'href' => '?route=features&key=hr', 'icon' => 'users', 'children' => [
            ['label' => 'Danh sách nhân viên', 'href' => '?route=employees', 'route' => 'employees'],
            ['label' => 'Hợp đồng lao động', 'href' => '?route=contracts', 'route' => 'contracts'],
            ['label' => 'Chấm công', 'href' => '?route=attendance', 'route' => 'attendance'],
            ['label' => 'Bảng lương', 'href' => '?route=payrolls', 'route' => 'payrolls'],
            ['label' => 'Bảo hiểm xã hội', 'href' => '?route=social-insurance', 'route' => 'social-insurance'],
            ['label' => 'Phiếu yêu cầu', 'href' => '?route=requests', 'route' => 'requests'],
            ['label' => 'Danh sách vi phạm', 'href' => '?route=violations', 'route' => 'violations'],
            ['label' => 'Danh sách khen thưởng', 'href' => '?route=rewards', 'route' => 'rewards'],
            ['label' => 'Hồ sơ timeline', 'href' => '?route=features&key=hr&feature=employee-timeline', 'route' => 'features'],
        ]],
        ['id' => 'tasks', 'label' => 'Công việc', 'href' => '?route=features&key=work', 'icon' => 'check', 'children' => [
            ['label' => 'Dự án', 'href' => '?route=projects', 'route' => 'projects'],
            ['label' => 'Danh sách công việc', 'href' => '?route=work-items', 'route' => 'work-items'],
            ['label' => 'Báo cáo hằng ngày', 'href' => '?route=daily-reports', 'route' => 'daily-reports'],
            ['label' => 'Ticket', 'href' => '?route=tickets', 'route' => 'tickets'],
            ['label' => 'Kanban board', 'href' => '?route=features&key=work&feature=kanban', 'route' => 'features'],
        ]],
        ['id' => 'training', 'label' => 'Đào tạo', 'href' => '?route=features&key=training', 'icon' => 'book', 'children' => [
            ['label' => 'Khóa học', 'href' => '?route=training', 'route' => 'training'],
            ['label' => 'Báo cáo đào tạo', 'href' => '?route=training-reports', 'route' => 'training-reports'],
            ['label' => 'Tài liệu học', 'href' => '?route=features&key=training&feature=training-documents', 'route' => 'features'],
            ['label' => 'Bài thi và chứng chỉ', 'href' => '?route=features&key=training&feature=certificates', 'route' => 'features'],
        ]],
        ['id' => 'kpi', 'label' => 'KPI', 'href' => '?route=kpi', 'icon' => 'pie'],
        ['id' => 'okrs', 'label' => 'OKR', 'href' => '?route=okrs', 'icon' => 'arrow'],
    ],
    'KINH DOANH' => [
        ['id' => 'suppliers', 'label' => 'Nhà cung cấp', 'href' => '?route=features&key=suppliers', 'icon' => 'briefcase', 'children' => [
            ['label' => 'Danh sách nhà cung cấp', 'href' => '?route=suppliers', 'route' => 'suppliers'],
            ['label' => 'Lịch sử mua hàng', 'href' => '?route=purchasing', 'route' => 'purchasing'],
            ['label' => 'Đánh giá nhà cung cấp', 'href' => '?route=features&key=suppliers&feature=ratings', 'route' => 'features'],
            ['label' => 'Công nợ nhà cung cấp', 'href' => '?route=features&key=suppliers&feature=payables', 'route' => 'features'],
        ]],
        ['id' => 'services', 'label' => 'Dịch vụ', 'href' => '?route=features&key=services', 'icon' => 'lifebuoy', 'children' => [
            ['label' => 'Danh sách dịch vụ', 'href' => '?route=services', 'route' => 'services'],
            ['label' => 'Danh sách sản phẩm', 'href' => '?route=products', 'route' => 'products'],
            ['label' => 'Bảng giá dịch vụ', 'href' => '?route=features&key=services&feature=price-list', 'route' => 'features'],
            ['label' => 'Kích hoạt dịch vụ', 'href' => '?route=features&key=services&feature=activation', 'route' => 'features'],
        ]],
        ['id' => 'sales', 'label' => 'Bán hàng', 'href' => '?route=features&key=sales', 'icon' => 'cart', 'children' => [
            ['label' => 'Đơn hàng', 'href' => '?route=sales-orders', 'route' => 'sales-orders'],
            ['label' => 'Báo giá', 'href' => '?route=sales-orders&stage=quote', 'route' => 'sales-orders.quote'],
            ['label' => 'Hợp đồng', 'href' => '?route=sales-orders&stage=contract', 'route' => 'sales-orders.contract'],
            ['label' => 'Nghiệm thu', 'href' => '?route=sales-orders&stage=paid', 'route' => 'sales-orders.paid'],
            ['label' => 'Chỉ tiêu tháng', 'href' => '?route=sales-targets', 'route' => 'sales-targets'],
            ['label' => 'Phiếu bán hàng', 'href' => '?route=sales-receipts', 'route' => 'sales-receipts'],
            ['label' => 'Công nợ khách hàng', 'href' => '?route=features&key=sales&feature=receivables', 'route' => 'features'],
        ]],
        ['id' => 'retail', 'label' => 'Bán hàng lẻ', 'href' => '?route=features&key=retail', 'icon' => 'cart', 'children' => [
            ['label' => 'Phiếu bán hàng', 'href' => '?route=sales-receipts', 'route' => 'sales-receipts'],
            ['label' => 'POS bán lẻ', 'href' => '?route=pos', 'route' => 'pos'],
            ['label' => 'Đổi trả hàng', 'href' => '?route=features&key=retail&feature=returns', 'route' => 'features'],
            ['label' => 'Khuyến mãi', 'href' => '?route=features&key=retail&feature=promotions', 'route' => 'features'],
        ]],
        ['id' => 'calls', 'label' => 'CSKH', 'href' => '?route=features&key=cskh', 'icon' => 'phone', 'children' => [
            ['label' => 'Gọi điện', 'href' => '?route=calls', 'route' => 'calls'],
            ['label' => 'Danh sách khách hàng', 'href' => '?route=customers', 'route' => 'customers'],
            ['label' => 'Ticket hỗ trợ', 'href' => '?route=tickets', 'route' => 'tickets'],
            ['label' => 'Kế hoạch CSKH', 'href' => '?route=features&key=cskh&feature=care-plan', 'route' => 'features'],
        ]],
    ],
    'QUẢN LÝ KHO' => [
        ['id' => 'products', 'label' => 'Quản lý kho bán hàng', 'href' => '?route=features&key=sales-stock', 'icon' => 'settings', 'children' => [
            ['label' => 'Danh sách sản phẩm', 'href' => '?route=products', 'route' => 'products'],
            ['label' => 'Tồn kho bán hàng', 'href' => '?route=inventory', 'route' => 'inventory'],
            ['label' => 'Kiểm kê', 'href' => '?route=features&key=sales-stock&feature=stocktake', 'route' => 'features'],
            ['label' => 'In mã vạch', 'href' => '?route=features&key=sales-stock&feature=barcode', 'route' => 'features'],
        ]],
        ['id' => 'internal_assets', 'label' => 'Trang thiết bị', 'href' => '?route=features&key=equipment', 'icon' => 'briefcase', 'children' => [
            ['label' => 'Kho máy', 'href' => '?route=machine-warehouses', 'route' => 'machine-warehouses'],
            ['label' => 'Quản lý thiết bị', 'href' => '?route=equipment-devices', 'route' => 'equipment-devices'],
            ['label' => 'Loại thiết bị', 'href' => '?route=equipment-types', 'route' => 'equipment-types'],
            ['label' => 'Mua sắm', 'href' => '?route=purchasing', 'route' => 'purchasing'],
            ['label' => 'Cấp phát thiết bị', 'href' => '?route=internal-assets', 'route' => 'internal-assets'],
            ['label' => 'Bảo trì/bảo hành', 'href' => '?route=features&key=equipment&feature=maintenance', 'route' => 'features'],
        ]],
    ],
    'BÁO CÁO' => [
        ['id' => 'reports', 'label' => 'Báo cáo tổng hợp', 'href' => '?route=reports', 'icon' => 'file'],
        ['id' => 'hr_reports', 'label' => 'Báo cáo nhân sự', 'href' => '?route=features&key=hr-reports', 'icon' => 'users'],
        ['id' => 'work_reports', 'label' => 'Báo cáo công việc', 'href' => '?route=features&key=work-reports', 'icon' => 'file'],
        ['id' => 'training_reports', 'label' => 'Báo cáo đào tạo', 'href' => '?route=features&key=training-reports', 'icon' => 'book'],
    ],
    'VẬN HÀNH' => [
        ['id' => 'calendar', 'label' => 'Lịch làm việc', 'href' => '?route=calendar', 'icon' => 'calendar'],
        ['id' => 'shipments', 'label' => 'Vận chuyển', 'href' => '?route=shipments', 'icon' => 'truck'],
        ['id' => 'facilities', 'label' => 'Cơ sở vật chất', 'href' => '?route=facilities', 'icon' => 'building'],
    ],
    'QUẢN LÝ HỆ THỐNG' => [
        ['id' => 'settings', 'label' => 'Quản lý thông tin', 'href' => '?route=features&key=system', 'icon' => 'info', 'children' => [
            ['label' => 'Thông tin công ty', 'href' => '?route=settings', 'route' => 'settings'],
            ['label' => 'Template hệ thống', 'href' => '?route=features&key=system&feature=templates', 'route' => 'features'],
            ['label' => 'Backup dữ liệu', 'href' => '?route=features&key=system&feature=backup', 'route' => 'features'],
            ['label' => 'Cấu hình giao diện', 'href' => '?route=features&key=system&feature=theme', 'route' => 'features'],
        ]],
        ['id' => 'users', 'label' => 'Tài khoản', 'href' => '?route=users', 'icon' => 'users'],
        ['id' => 'permissions', 'label' => 'Phân quyền', 'href' => '?route=permissions', 'icon' => 'settings'],
        ['id' => 'activity_log', 'label' => 'Lịch sử thao tác', 'href' => '?route=activity-log', 'icon' => 'file'],
        ['id' => 'recruitments', 'label' => 'Tuyển dụng', 'href' => '?route=features&key=recruitment', 'icon' => 'monitor', 'children' => [
            ['label' => 'Phiếu yêu cầu tuyển dụng', 'href' => '?route=recruitment-requests', 'route' => 'recruitment-requests'],
            ['label' => 'Pipeline ứng viên', 'href' => '?route=features&key=recruitment&feature=candidate-pipeline', 'route' => 'features'],
            ['label' => 'Lịch phỏng vấn', 'href' => '?route=calendar', 'route' => 'calendar'],
            ['label' => 'Chi phí tuyển dụng', 'href' => '?route=features&key=recruitment&feature=recruitment-cost', 'route' => 'features'],
        ]],
    ],
];
$groups = filter_nav_groups_by_permission($groups);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Novaone</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_url('public/assets/novaone-logo.png')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/novaone-logo.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= e(asset_url('public/assets/novaone-logo.png')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/novaone-logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= e(asset_url('public/assets/novaone-logo.png')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/novaone-logo.png') ?>">
    <link rel="stylesheet" href="<?= e(asset_url('public/assets/app.css')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/app.css') ?>">
    <link rel="stylesheet" href="<?= e(asset_url('public/assets/mobile.css')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/mobile.css') ?>">
</head>
<body>
<div class="app-shell">
    <a class="brand-mark" href="?route=home" aria-label="Novaone home"></a>
    <aside class="sidebar">
        <nav class="nav">
            <?php foreach ($groups as $group => $items): ?>
                <div class="nav-heading"><?= e($group) ?></div>
                <?php foreach ($items as $item): ?>
                    <?php
                    $isActive = ($active ?? '') === $item['id'];
                    $hasChildren = ! empty($item['children']);
                    ?>
                    <div class="nav-item <?= $isActive ? 'active open' : '' ?> <?= $hasChildren ? 'has-children' : '' ?>">
                        <a class="nav-link" href="<?= e($item['href']) ?>">
                            <?= ui_icon($item['icon']) ?>
                            <span><?= e($item['label']) ?></span>
                            <?php if ($hasChildren): ?><span class="nav-chevron" aria-hidden="true"></span><?php endif; ?>
                        </a>
                        <?php if ($hasChildren): ?>
                            <div class="nav-submenu">
                                <?php foreach ($item['children'] as $child): ?>
                                    <?php
                                    $currentRoute = $_GET['route'] ?? '';
                                    $childRoute = $child['route'] ?? '';
                                    $childActive = $currentRoute === $childRoute || ($childRoute !== '' && str_starts_with((string) $currentRoute, (string) $childRoute . '.'));
                                    $childHref = $child['href'] ?? '#';
                                    ?>
                                    <a class="<?= $childActive ? 'active' : '' ?>" href="<?= e($childHref) ?>">- <span><?= e($child['label'] ?? '') ?></span></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="top-icon sidebar-toggle" type="button" aria-label="Thu gọn menu"><?= ui_icon('menu') ?></button>
                <a class="top-icon profile-shortcut" href="?route=profile" aria-label="Profile"><?= ui_icon('users') ?></a>
            </div>
            <a class="mobile-home-logo" href="?route=home" aria-label="Trang chủ Novaone"></a>
            <div class="topbar-right">
                <form class="global-search" method="get" action="">
                    <input type="hidden" name="route" value="search">
                    <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm..." aria-label="Tìm kiếm">
                    <button class="top-icon" type="submit" aria-label="Tìm kiếm"><?= ui_icon('search') ?></button>
                </form>
                                                <details class="notification-menu">
                    <summary class="top-icon has-badge" aria-label="Th&ocirc;ng b&aacute;o" data-notification-summary>
                        <?= ui_icon('bell') ?>
                        <?php if (count($unreadNotifications) > 0): ?><span data-notification-badge><?= count($unreadNotifications) ?></span><?php endif; ?>
                    </summary>
                    <div class="notification-dropdown">
                        <div class="notification-head">
                            <strong>Th&ocirc;ng b&aacute;o ho&#7841;t &#273;&#7897;ng</strong>
                            <small data-notification-counts><?= count($unreadNotifications) ?> m&#7899;i / <?= count($allNotifications) ?> t&#7845;t c&#7843;</small>
                        </div>
                        <?php if (count($allNotifications) === 0): ?>
                            <div class="notification-empty">Ch&#432;a c&oacute; th&ocirc;ng b&aacute;o ho&#7841;t &#273;&#7897;ng.</div>
                        <?php endif; ?>
                        <?php foreach ($visibleNotifications as $notification): ?>
                            <a class="<?= empty($notification['read_at']) ? 'unread' : '' ?>" href="?route=notification.read&id=<?= e($notification['id']) ?>">
                                <span class="activity-dot <?= e($notification['type'] ?? 'info') ?>"></span>
                                <span>
                                    <strong><?= e($notification['title'] ?? html_entity_decode('Th&ocirc;ng b&aacute;o', ENT_QUOTES, 'UTF-8')) ?></strong>
                                    <small><?= e($notification['message'] ?? '') ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <?php foreach ($extraNotifications as $notification): ?>
                            <a class="notification-extra <?= empty($notification['read_at']) ? 'unread' : '' ?>" href="?route=notification.read&id=<?= e($notification['id']) ?>">
                                <span class="activity-dot <?= e($notification['type'] ?? 'info') ?>"></span>
                                <span>
                                    <strong><?= e($notification['title'] ?? html_entity_decode('Th&ocirc;ng b&aacute;o', ENT_QUOTES, 'UTF-8')) ?></strong>
                                    <small><?= e($notification['message'] ?? '') ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($hasMoreNotifications): ?>
                            <button class="notification-show-all" type="button">Hi&#7875;n th&#7883; t&#7845;t c&#7843; th&ocirc;ng b&aacute;o</button>
                        <?php endif; ?>
                        <?php if (count($unreadNotifications) > 0): ?>
                            <a class="notification-read-all" href="?route=notification.readAll">&#272;&aacute;nh d&#7845;u &#273;&atilde; &#273;&#7885;c t&#7845;t c&#7843;</a>
                        <?php endif; ?>
                    </div>
                </details>
<button class="top-icon" type="button" aria-label="Email"><?= ui_icon('mail') ?></button>
                <a class="top-icon" href="?route=calls" aria-label="Điện thoại"><?= ui_icon('phone') ?></a>
                <details class="user-menu">
                    <summary class="user-chip">
                        <span class="avatar">
                            <?php if (! empty($_SESSION['user']['avatar'])): ?>
                                <img src="<?= e(asset_url($_SESSION['user']['avatar'])) ?>" alt="">
                            <?php else: ?>
                                <?= e(first_character($_SESSION['user']['name'] ?? 'A')) ?>
                            <?php endif; ?>
                        </span>
                        <span><?= e($_SESSION['user']['name'] ?? 'Admin Novaone') ?></span>
                    </summary>
                    <div class="user-dropdown">
                        <a href="?route=profile"><?= ui_icon('users') ?><span>Cá Nhân</span></a>
                        <a href="?route=password"><?= ui_icon('key') ?><span>Đổi Mật Khẩu</span></a>
                        <form method="post" action="?route=logout">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                            <button type="submit"><?= ui_icon('logout') ?><span>Thoát</span></button>
                        </form>
                    </div>
                </details>
            </div>
        </header>
        <main class="content">
            <?= $content ?>
        </main>
    </div>
</div>
<script>
(() => {
  const shell = document.querySelector('.app-shell');
  const toggle = document.querySelector('.sidebar-toggle');
  if (!shell || !toggle) return;
  const notificationMenu = document.querySelector('.notification-menu');
  const notificationDropdown = notificationMenu?.querySelector('.notification-dropdown');
  const notificationShowAll = document.querySelector('.notification-show-all');

  if (localStorage.getItem('novaone-sidebar-collapsed') === '1') {
    shell.classList.add('sidebar-collapsed');
  }

  const isMobileNav = () => window.matchMedia('(max-width: 980px)').matches;

  toggle.addEventListener('click', () => {
    if (isMobileNav()) {
      shell.classList.toggle('sidebar-mobile-open');
      return;
    }

    shell.classList.toggle('sidebar-collapsed');
    localStorage.setItem('novaone-sidebar-collapsed', shell.classList.contains('sidebar-collapsed') ? '1' : '0');
  });

  document.addEventListener('click', (event) => {
    if (!isMobileNav() || !shell.classList.contains('sidebar-mobile-open')) return;
    if (event.target.closest('.sidebar') || event.target.closest('.sidebar-toggle')) return;
    shell.classList.remove('sidebar-mobile-open');
  });

  window.addEventListener('resize', () => {
    if (!isMobileNav()) {
      shell.classList.remove('sidebar-mobile-open');
      notificationMenu?.classList.remove('mobile-open', 'show-all');
      notificationDropdown?.classList.remove('mobile-floating', 'show-all');
      if (notificationMenu && notificationDropdown && notificationDropdown.parentElement !== notificationMenu) {
        notificationMenu.appendChild(notificationDropdown);
      }
      document.body.classList.remove('notification-open');
    } else if (notificationMenu?.classList.contains('mobile-open') || notificationMenu?.open) {
      document.body.classList.add('notification-open');
    }
  });

  if (notificationMenu) {
    const notificationSummary = notificationMenu.querySelector('summary');
    const closeMobileNotifications = () => {
      notificationMenu.open = false;
      notificationMenu.classList.remove('mobile-open', 'show-all');
      notificationDropdown?.classList.remove('mobile-floating', 'show-all');
      if (notificationDropdown && notificationDropdown.parentElement !== notificationMenu) {
        notificationMenu.appendChild(notificationDropdown);
      }
      if (notificationShowAll) notificationShowAll.hidden = false;
      document.body.classList.remove('notification-open');
    };

    const openMobileNotifications = () => {
      notificationMenu.open = false;
      if (notificationDropdown && notificationDropdown.parentElement !== document.body) {
        document.body.appendChild(notificationDropdown);
      }
      notificationMenu.classList.add('mobile-open');
      notificationDropdown?.classList.add('mobile-floating');
      document.body.classList.add('notification-open');
    };

    notificationSummary?.addEventListener('click', (event) => {
      if (!isMobileNav()) return;
      event.preventDefault();
      notificationMenu.classList.contains('mobile-open') ? closeMobileNotifications() : openMobileNotifications();
    });

    document.addEventListener('click', (event) => {
      if (!isMobileNav() || !notificationMenu.classList.contains('mobile-open')) return;
      if (event.target.closest('.notification-dropdown') || event.target.closest('.notification-menu > summary')) return;
      closeMobileNotifications();
    });

    notificationMenu.addEventListener('toggle', () => {
      if (isMobileNav() && notificationMenu.classList.contains('mobile-open')) return;
      document.body.classList.toggle('notification-open', notificationMenu.open && isMobileNav());
      if (!notificationMenu.open) {
        notificationMenu.classList.remove('show-all');
        notificationDropdown?.classList.remove('show-all');
        if (notificationShowAll) notificationShowAll.hidden = false;
      }
    });
  }

  if (notificationShowAll && notificationMenu) {
    notificationShowAll.addEventListener('click', () => {
      notificationMenu.classList.add('show-all');
      notificationDropdown?.classList.add('show-all');
      notificationShowAll.hidden = true;
    });
  }

  document.querySelectorAll('.nav-item.has-children > .nav-link').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      const item = link.closest('.nav-item');
      if (!item) return;
      item.classList.toggle('open');
    });
  });

  document.querySelectorAll('.nav-submenu a').forEach((link) => {
    link.addEventListener('click', () => {
      if (isMobileNav()) shell.classList.remove('sidebar-mobile-open');
    });
  });

  const refreshNotificationBadge = async () => {
    const summary = document.querySelector('[data-notification-summary]');
    const counts = document.querySelector('[data-notification-counts]');
    if (!summary || !counts) return;
    try {
      const response = await fetch('?route=notifications.feed', {headers: {'Accept': 'application/json'}});
      if (!response.ok) return;
      const data = await response.json();
      let badge = summary.querySelector('[data-notification-badge]');
      if ((data.unread || 0) > 0) {
        if (!badge) {
          badge = document.createElement('span');
          badge.dataset.notificationBadge = '';
          summary.appendChild(badge);
        }
        badge.textContent = data.unread;
      } else if (badge) {
        badge.remove();
      }
      counts.textContent = `${data.unread || 0} mới / ${data.total || 0} tất cả`;
    } catch (error) {
      // Network hiccups should not disturb the app shell.
    }
  };
  window.setInterval(refreshNotificationBadge, 30000);
})();
</script>
</body>
</html>
