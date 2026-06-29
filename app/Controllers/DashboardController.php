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

        View::render('dashboard/index', [
            'active' => 'dashboard',
            'title' => 'Dashboard',
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
}
