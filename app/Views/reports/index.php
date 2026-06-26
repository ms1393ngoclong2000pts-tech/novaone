<?php
$sales = $data['sales'] ?? [];
$pos = $data['pos'] ?? [];
$employees = $data['employees'] ?? [];
$inventory = $data['inventory'] ?? [];
$recruitments = $data['recruitments'] ?? [];
$shipments = $data['shipments'] ?? [];
$tickets = $data['tickets'] ?? [];
$training = $data['training'] ?? [];
$kpis = $data['kpi'] ?? [];
$okrs = $data['okrs'] ?? [];
$ratio = fn ($value, $total) => $total > 0 ? ($value / $total) * 100 : 0;
?>
<section class="grid stats">
    <?php $label = 'Đơn hàng'; $value = count($sales) + count($pos); require BASE_PATH . '/app/Views/partials/stat.php'; ?>
    <?php $label = 'Khách hàng/NCC'; $value = count($data['customers'] ?? []); require BASE_PATH . '/app/Views/partials/stat.php'; ?>
    <?php $label = 'Ticket CSKH'; $value = count($tickets); require BASE_PATH . '/app/Views/partials/stat.php'; ?>
    <?php $label = 'Vận đơn'; $value = count($shipments); require BASE_PATH . '/app/Views/partials/stat.php'; ?>
</section>
<section class="panel">
    <div class="panel-head"><h2>Báo cáo tổng hợp</h2><p>doanh thu, tồn kho, nhân sự, tiến độ</p></div>
    <div class="panel-body bars">
        <?php $label = 'Doanh thu đã thu'; $value = $ratio(count(array_filter($sales, fn ($item) => $item['payment'] === 'paid')), count($sales)); require BASE_PATH . '/app/Views/partials/bar.php'; ?>
        <?php $label = 'Nhân sự hoạt động'; $value = $ratio(count(array_filter($employees, fn ($item) => $item['status'] === 'active')), count($employees)); require BASE_PATH . '/app/Views/partials/bar.php'; ?>
        <?php $label = 'Đào tạo hoàn tất'; $value = $ratio(count(array_filter($training, fn ($item) => $item['status'] === 'completed')), count($training)); require BASE_PATH . '/app/Views/partials/bar.php'; ?>
        <?php $label = 'KPI đúng tiến độ'; $value = $ratio(count(array_filter($kpis, fn ($item) => $item['status'] === 'on_track' || $item['status'] === 'done')), count($kpis)); require BASE_PATH . '/app/Views/partials/bar.php'; ?>
        <?php $label = 'OKR đúng tiến độ'; $value = $ratio(count(array_filter($okrs, fn ($item) => $item['status'] === 'on_track' || $item['status'] === 'done')), count($okrs)); require BASE_PATH . '/app/Views/partials/bar.php'; ?>
        <?php $label = 'Tồn kho ổn định'; $value = $ratio(count(array_filter($inventory, fn ($item) => (float) $item['quantity'] > (float) $item['min'])), count($inventory)); require BASE_PATH . '/app/Views/partials/bar.php'; ?>
        <?php $label = 'Tuyển dụng thành công'; $value = $ratio(count(array_filter($recruitments, fn ($item) => $item['stage'] === 'hired')), count($recruitments)); require BASE_PATH . '/app/Views/partials/bar.php'; ?>
        <?php $label = 'CSKH hoàn tất'; $value = $ratio(count(array_filter($tickets, fn ($item) => $item['status'] === 'completed')), count($tickets)); require BASE_PATH . '/app/Views/partials/bar.php'; ?>
    </div>
</section>
