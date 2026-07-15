<?php

declare(strict_types=1);

final class DashboardController
{
    public function home(DataStore $store): void
    {
        require_auth();

        View::render('dashboard/home', [
            'active' => 'home',
            'title' => 'Quản lý ứng dụng',
        ]);
    }

    public function index(DataStore $store): void
    {
        require_auth();

        $data = $store->all();
        $tasks = array_merge($data['tasks'] ?? [], $data['work_items'] ?? []);
        $attendance = $data['attendance_records'] ?? [];
        $orders = $data['sales_orders'] ?? [];
        $suppliers = $data['suppliers'] ?? [];
        $devices = $data['equipment_devices'] ?? [];

        $taskTotal = max(1, count($tasks));
        $taskDone = count(array_filter($tasks, fn ($item) => in_array($item['status'] ?? '', ['completed', 'done'], true)));
        $taskProgress = max(0, $taskTotal - $taskDone);
        $attendanceHours = array_sum(array_map(fn ($item) => (float) ($item['total_hours'] ?? 0), $attendance));
        $attendancePeople = count(array_unique(array_filter(array_map(fn ($item) => (string) ($item['employee_id'] ?? ''), $attendance))));

        $orderTotal = array_sum(array_map(fn ($item) => (float) ($item['amount'] ?? 0), $orders));
        $quoteTotal = array_sum(array_map(fn ($item) => ($item['stage'] ?? '') === 'quote' ? (float) ($item['amount'] ?? 0) : 0, $orders));
        $contractTotal = array_sum(array_map(fn ($item) => ($item['stage'] ?? '') === 'contract' ? (float) ($item['amount'] ?? 0) : 0, $orders));
        $customerGroups = array_values(array_filter(array_unique(array_map(fn ($item) => (string) ($item['customer_group'] ?? ''), $orders))));
        $role = current_user_role();
        $roleSummary = $this->roleSummary($role, $data);

        View::render('dashboard/index', [
            'active' => 'dashboard',
            'title' => 'Dashboard',
            'role' => $role,
            'roleSummary' => $roleSummary,
            'data' => $data,
            'taskDone' => $taskDone,
            'taskProgress' => $taskProgress,
            'taskTotal' => $taskTotal,
            'attendanceHours' => $attendanceHours,
            'attendancePeople' => $attendancePeople,
            'orderTotal' => $orderTotal,
            'quoteTotal' => $quoteTotal,
            'contractTotal' => $contractTotal,
            'customerGroupCount' => max(1, count($customerGroups)),
            'productCount' => count($devices),
            'serviceCount' => count($suppliers),
        ]);
    }

    private function roleSummary(string $role, array $data): array
    {
        $role = strtolower($role);
        return match ($role) {
            'hr' => [
                'title' => 'Bảng điều hành Nhân sự',
                'description' => 'Theo dõi nhân viên, chấm công, hợp đồng và các phiếu cần xử lý.',
                'items' => [
                    ['label' => 'Nhân viên', 'value' => count((array) ($data['employees'] ?? [])), 'href' => '?route=employees'],
                    ['label' => 'Chấm công', 'value' => count((array) ($data['attendance_records'] ?? [])), 'href' => '?route=attendance'],
                    ['label' => 'Phiếu yêu cầu', 'value' => count((array) ($data['requests'] ?? [])), 'href' => '?route=requests'],
                ],
            ],
            'sales' => [
                'title' => 'Bảng điều hành Kinh doanh',
                'description' => 'Tập trung vào đơn hàng, chỉ tiêu tháng, sản phẩm và nhà cung cấp.',
                'items' => [
                    ['label' => 'Đơn hàng', 'value' => count((array) ($data['sales_orders'] ?? [])), 'href' => '?route=sales-orders'],
                    ['label' => 'Chỉ tiêu', 'value' => count((array) ($data['sales_targets'] ?? [])), 'href' => '?route=sales-targets'],
                    ['label' => 'Sản phẩm', 'value' => count((array) ($data['products'] ?? [])), 'href' => '?route=products'],
                ],
            ],
            'warehouse' => [
                'title' => 'Bảng điều hành Kho thiết bị',
                'description' => 'Theo dõi kho máy, thiết bị, loại thiết bị và yêu cầu mua sắm.',
                'items' => [
                    ['label' => 'Kho máy', 'value' => count((array) ($data['machine_warehouses'] ?? [])), 'href' => '?route=machine-warehouses'],
                    ['label' => 'Thiết bị', 'value' => count((array) ($data['equipment_devices'] ?? [])), 'href' => '?route=equipment-devices'],
                    ['label' => 'Mua sắm', 'value' => count((array) ($data['purchasing'] ?? [])), 'href' => '?route=purchasing'],
                ],
            ],
            default => [
                'title' => 'Bảng điều hành tổng hợp',
                'description' => 'Tổng quan nhanh các phân hệ chính và hoạt động mới nhất của Novaone.',
                'items' => [
                    ['label' => 'Nhân sự', 'value' => count((array) ($data['employees'] ?? [])), 'href' => '?route=employees'],
                    ['label' => 'Dự án', 'value' => count((array) ($data['projects'] ?? [])), 'href' => '?route=projects'],
                    ['label' => 'Doanh thu', 'value' => money(array_sum(array_map(fn ($item) => (float) ($item['amount'] ?? 0), (array) ($data['sales_orders'] ?? [])))), 'href' => '?route=sales-orders'],
                ],
            ],
        };
    }
}
