<?php
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
    ],
    'KINH DOANH' => [
        ['id' => 'suppliers', 'label' => 'Nhà cung cấp', 'href' => '?route=suppliers', 'icon' => 'briefcase'],
        ['id' => 'sales', 'label' => 'Bán hàng', 'href' => '?route=sales-orders', 'icon' => 'cart', 'children' => [
            ['label' => 'Đơn Hàng', 'href' => '?route=sales-orders', 'route' => 'sales-orders'],
            ['label' => 'Báo Giá', 'href' => '?route=sales-orders&stage=quote', 'route' => 'sales-orders.quote'],
            ['label' => 'Hợp Đồng', 'href' => '?route=sales-orders&stage=contract', 'route' => 'sales-orders.contract'],
            ['label' => 'Nghiệm Thu', 'href' => '?route=sales-orders&stage=paid', 'route' => 'sales-orders.paid'],
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
    ],
    'QUẢN LÝ HỆ THỐNG' => [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '?route=dashboard', 'icon' => 'monitor'],
        ['id' => 'recruitments', 'label' => 'Tuyển dụng', 'href' => '?route=recruitment-requests', 'icon' => 'monitor', 'children' => [
            ['label' => 'Phiếu Yêu Cầu Tuyển Dụng', 'href' => '?route=recruitment-requests', 'route' => 'recruitment-requests'],
        ]],
    ],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novaone</title>
    <link rel="icon" type="image/png" href="public/assets/novaone-logo.png">
    <link rel="icon" type="image/svg+xml" href="public/assets/novaone-mark.svg">
    <link rel="shortcut icon" href="public/assets/novaone-logo.png">
    <link rel="apple-touch-icon" href="public/assets/novaone-logo.png">
    <link rel="stylesheet" href="public/assets/app.css?v=<?= filemtime(BASE_PATH . '/public/assets/app.css') ?>">
    <link rel="stylesheet" href="public/assets/mobile.css?v=<?= filemtime(BASE_PATH . '/public/assets/mobile.css') ?>">
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
                <a class="top-icon" href="?route=home" aria-label="Màn hình"><?= ui_icon('monitor') ?></a>
            </div>
            <div class="topbar-right">
                <form class="global-search" method="get" action="">
                    <input type="hidden" name="route" value="search">
                    <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm..." aria-label="Tìm kiếm">
                    <button class="top-icon" type="submit" aria-label="Tìm kiếm"><?= ui_icon('search') ?></button>
                </form>
                <details class="notification-menu">
                    <summary class="top-icon has-badge" aria-label="Thông báo">
                        <?= ui_icon('bell') ?>
                        <?php if (count($unreadNotifications) > 0): ?><span><?= count($unreadNotifications) ?></span><?php endif; ?>
                    </summary>
                    <div class="notification-dropdown">
                        <div class="notification-head">
                            <strong>Thông báo hoạt động</strong>
                            <small><?= count($unreadNotifications) ?> mới / <?= count($allNotifications) ?> tất cả</small>
                        </div>
                        <?php if (count($allNotifications) === 0): ?>
                            <div class="notification-empty">Chưa có thông báo hoạt động.</div>
                        <?php endif; ?>
                        <?php foreach ($visibleNotifications as $notification): ?>
                            <a class="<?= empty($notification['read_at']) ? 'unread' : '' ?>" href="?route=notification.read&id=<?= e($notification['id']) ?>">
                                <span class="activity-dot <?= e($notification['type'] ?? 'info') ?>"></span>
                                <span>
                                    <strong><?= e($notification['title'] ?? 'Thông báo') ?></strong>
                                    <small><?= e($notification['message'] ?? '') ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <?php foreach ($extraNotifications as $notification): ?>
                            <a class="notification-extra <?= empty($notification['read_at']) ? 'unread' : '' ?>" href="?route=notification.read&id=<?= e($notification['id']) ?>">
                                <span class="activity-dot <?= e($notification['type'] ?? 'info') ?>"></span>
                                <span>
                                    <strong><?= e($notification['title'] ?? 'Thông báo') ?></strong>
                                    <small><?= e($notification['message'] ?? '') ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($hasMoreNotifications): ?>
                            <button class="notification-show-all" type="button">Hiển thị tất cả thông báo</button>
                        <?php endif; ?>
                        <?php if (count($unreadNotifications) > 0): ?>
                            <a class="notification-read-all" href="?route=notification.readAll">Đánh dấu đã đọc tất cả</a>
                        <?php endif; ?>
                    </div>
                </details>
                <button class="top-icon" type="button" aria-label="Email"><?= ui_icon('mail') ?></button>
                <button class="top-icon" type="button" aria-label="Điện thoại"><?= ui_icon('phone') ?></button>
                <details class="user-menu">
                    <summary class="user-chip">
                        <span class="avatar">
                            <?php if (! empty($_SESSION['user']['avatar'])): ?>
                                <img src="<?= e($_SESSION['user']['avatar']) ?>" alt="">
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
      document.body.classList.remove('notification-open');
    } else if (notificationMenu?.open) {
      document.body.classList.add('notification-open');
    }
  });

  if (notificationMenu) {
    notificationMenu.addEventListener('toggle', () => {
      document.body.classList.toggle('notification-open', notificationMenu.open && isMobileNav());
      if (!notificationMenu.open) {
        notificationMenu.classList.remove('show-all');
        if (notificationShowAll) notificationShowAll.hidden = false;
      }
    });
  }

  if (notificationShowAll && notificationMenu) {
    notificationShowAll.addEventListener('click', () => {
      notificationMenu.classList.add('show-all');
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
})();
</script>
</body>
</html>
