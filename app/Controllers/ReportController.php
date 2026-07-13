<?php

declare(strict_types=1);

final class ReportController
{
    public function index(DataStore $store): void
    {
        require_auth();

        View::render('reports/index', [
            'active' => 'reports',
            'title' => 'Báo cáo',
            ...$this->buildReportData($store),
        ]);
    }

    public function training(DataStore $store): void
    {
        require_auth();

        $query = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $items = $store->get('training');

        if ($query !== '') {
            $lower = fn (string $value): string => function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value);
            $items = array_values(array_filter($items, fn (array $item): bool => str_contains(
                $lower(implode(' ', array_map('strval', $item))),
                $lower($query)
            )));
        }

        if ($status !== '') {
            $items = array_values(array_filter($items, fn (array $item): bool => (string) ($item['status'] ?? '') === $status));
        }

        $allItems = $store->get('training');
        $total = count($allItems);
        $completed = count(array_filter($allItems, fn (array $item): bool => ($item['status'] ?? '') === 'completed'));
        $inProgress = count(array_filter($allItems, fn (array $item): bool => ($item['status'] ?? '') === 'in_progress'));
        $pending = count(array_filter($allItems, fn (array $item): bool => ($item['status'] ?? '') === 'pending'));
        $averageProgress = $total > 0
            ? array_sum(array_map(fn (array $item): float => (float) ($item['progress'] ?? 0), $allItems)) / $total
            : 0;

        View::render('reports/training', [
            'active' => 'training_reports',
            'title' => 'Báo cáo đào tạo',
            'items' => $items,
            'query' => $query,
            'status' => $status,
            'summary' => [
                ['label' => 'Tổng bản ghi', 'value' => (string) $total, 'tone' => 'blue'],
                ['label' => 'Hoàn thành', 'value' => (string) $completed, 'tone' => 'green'],
                ['label' => 'Đang học', 'value' => (string) $inProgress, 'tone' => 'violet'],
                ['label' => 'Chờ học', 'value' => (string) $pending, 'tone' => 'orange'],
                ['label' => 'Tiến độ trung bình', 'value' => number_format($averageProgress, 1, ',', '.') . '%', 'tone' => 'teal'],
            ],
            'statusRows' => [
                ['label' => 'Chờ học', 'status' => 'pending', 'count' => $pending],
                ['label' => 'Đang học', 'status' => 'in_progress', 'count' => $inProgress],
                ['label' => 'Hoàn thành', 'status' => 'completed', 'count' => $completed],
            ],
        ]);
    }

    public function export(DataStore $store): void
    {
        require_auth();

        $report = $this->buildReportData($store);
        $filename = 'novaone-report-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Báo cáo Novaone', $report['filters']['from'] ?: 'Tất cả', $report['filters']['to'] ?: 'Tất cả']);
        fputcsv($output, []);
        fputcsv($output, ['Chỉ số', 'Giá trị', 'Ghi chú']);
        foreach ($report['summary'] as $item) {
            fputcsv($output, [$item['label'], $item['value'], $item['note']]);
        }

        fputcsv($output, []);
        fputcsv($output, ['Báo cáo nhỏ', 'Chỉ số', 'Giá trị']);
        foreach ($report['miniReports'] as $section) {
            foreach ($section['items'] as $item) {
                fputcsv($output, [$section['title'], $item['label'], $item['value']]);
            }
        }

        fputcsv($output, []);
        fputcsv($output, ['Nhân viên', 'Giờ báo cáo hằng ngày']);
        foreach ($report['topWorkHours'] as $row) {
            fputcsv($output, [$row['name'], $row['hours']]);
        }

        fputcsv($output, []);
        fputcsv($output, ['Giai đoạn đơn hàng', 'Số lượng', 'Tổng tiền']);
        foreach ($report['salesByStage'] as $row) {
            fputcsv($output, [$row['label'], $row['count'], $row['amount']]);
        }

        fclose($output);
        exit;
    }

    private function buildReportData(DataStore $store): array
    {
        $data = $store->all();
        $filters = [
            'from' => $this->validDate($_GET['from'] ?? '') ?: '',
            'to' => $this->validDate($_GET['to'] ?? '') ?: '',
            'module' => preg_replace('/[^a-z_]/', '', (string) ($_GET['module'] ?? 'all')) ?: 'all',
        ];

        $employees = $data['employees'] ?? [];
        $contracts = $this->filterByDate($data['contracts'] ?? [], $filters, ['start_date', 'created_date']);
        $insurance = $this->filterByDate($data['social_insurance'] ?? [], $filters, ['start_date', 'contract_start']);
        $requestForms = $this->filterByDate($data['request_forms'] ?? [], $filters, ['created_date', 'start_date']);
        $violations = $this->filterByDate($data['violations'] ?? [], $filters, ['violation_date', 'date']);
        $rewards = $this->filterByDate($data['rewards'] ?? [], $filters, ['reward_date', 'date']);
        $projects = $this->filterByDate($data['projects'] ?? [], $filters, ['start_date', 'end_date']);
        $workItems = $this->filterByDate($data['work_items'] ?? [], $filters, ['start_date', 'completion_date', 'deadline']);
        $dailyReports = $this->filterByDate($data['daily_reports'] ?? [], $filters, ['report_date']);
        $attendance = $this->filterByDate($data['attendance_records'] ?? [], $filters, ['date', 'work_date']);
        $salesOrders = $this->filterByDate($data['sales_orders'] ?? [], $filters, ['created_date']);
        $sales = $this->filterByDate($data['sales'] ?? [], $filters, ['date', 'created_date']);
        $pos = $this->filterByDate($data['pos'] ?? [], $filters, ['date', 'created_date']);
        $recruitmentRequests = $this->filterByDate($data['recruitment_requests'] ?? [], $filters, ['request_date']);
        $purchaseRequests = $this->filterByDate($data['purchase_requests'] ?? [], $filters, ['needed_date', 'created_date']);

        $activeEmployees = count(array_filter($employees, fn (array $item): bool => ($item['status'] ?? '') === 'active' || ($item['status'] ?? '') === 'Đang làm việc'));
        $taskTotal = count($workItems);
        $taskDone = count(array_filter($workItems, fn (array $item): bool => in_array($item['status'] ?? '', ['completed', 'done'], true)));
        $attendanceHours = array_sum(array_map(fn (array $item): float => (float) ($item['total_hours'] ?? $item['hours'] ?? 0), $attendance));
        $dailyHours = array_sum(array_map(fn (array $item): float => (float) ($item['hours'] ?? 0), $dailyReports));
        $salesAmount = $this->sumAmount($salesOrders, 'amount') + $this->sumAmount($sales, 'amount') + $this->sumAmount($pos, 'amount');
        $inventoryValue = array_sum(array_map(fn (array $item): float => (float) ($item['unit_price'] ?? 0), $data['equipment_devices'] ?? []));
        $recruitmentCost = $this->sumAmount($recruitmentRequests, 'cost');

        $summary = [
            ['label' => 'Nhân sự đang hoạt động', 'value' => $activeEmployees . '/' . count($employees), 'note' => 'Tổng nhân sự trong hệ thống', 'tone' => 'blue'],
            ['label' => 'Tổng doanh thu', 'value' => $this->money($salesAmount), 'note' => 'Đơn hàng, bán hàng, POS', 'tone' => 'green'],
            ['label' => 'Tiến độ công việc', 'value' => $this->percent($taskDone, max(1, $taskTotal)), 'note' => $taskDone . '/' . $taskTotal . ' công việc hoàn thành', 'tone' => 'violet'],
            ['label' => 'Giờ chấm công', 'value' => $this->number($attendanceHours) . ' giờ', 'note' => count($attendance) . ' bản ghi chấm công', 'tone' => 'orange'],
            ['label' => 'Giờ báo cáo hằng ngày', 'value' => $this->number($dailyHours) . ' giờ', 'note' => count($dailyReports) . ' dòng báo cáo', 'tone' => 'teal'],
            ['label' => 'Chi phí tuyển dụng', 'value' => $this->money($recruitmentCost), 'note' => count($recruitmentRequests) . ' phiếu tuyển dụng', 'tone' => 'pink'],
        ];

        $miniReports = [
            [
                'id' => 'human',
                'title' => 'Nhân sự',
                'subtitle' => 'Hồ sơ, hợp đồng, bảo hiểm, kỷ luật',
                'icon' => 'users',
                'href' => '?route=employees',
                'items' => [
                    ['label' => 'Nhân viên', 'value' => count($employees)],
                    ['label' => 'Hợp đồng', 'value' => count($contracts)],
                    ['label' => 'BHXH', 'value' => count($insurance)],
                    ['label' => 'Phiếu yêu cầu', 'value' => count($requestForms)],
                    ['label' => 'Vi phạm', 'value' => count($violations)],
                    ['label' => 'Khen thưởng', 'value' => count($rewards)],
                ],
            ],
            [
                'id' => 'work',
                'title' => 'Công việc',
                'subtitle' => 'Dự án, công việc và báo cáo ngày',
                'icon' => 'check',
                'href' => '?route=projects',
                'items' => [
                    ['label' => 'Dự án', 'value' => count($projects)],
                    ['label' => 'Đang mở', 'value' => count(array_filter($projects, fn (array $item): bool => ($item['status'] ?? '') === 'open'))],
                    ['label' => 'Công việc', 'value' => count($workItems)],
                    ['label' => 'Hoàn thành', 'value' => $taskDone],
                    ['label' => 'Báo cáo ngày', 'value' => count($dailyReports)],
                    ['label' => 'Tổng giờ', 'value' => $this->number($dailyHours)],
                ],
            ],
            [
                'id' => 'sales',
                'title' => 'Bán hàng',
                'subtitle' => 'Đơn hàng, báo giá, hợp đồng, nghiệm thu',
                'icon' => 'cart',
                'href' => '?route=sales-orders',
                'items' => [
                    ['label' => 'Đơn hàng', 'value' => count($salesOrders)],
                    ['label' => 'Báo giá', 'value' => count(array_filter($salesOrders, fn (array $item): bool => ($item['stage'] ?? '') === 'quote'))],
                    ['label' => 'Hợp đồng', 'value' => count(array_filter($salesOrders, fn (array $item): bool => ($item['stage'] ?? '') === 'contract'))],
                    ['label' => 'Đã thanh toán', 'value' => count(array_filter($salesOrders, fn (array $item): bool => ($item['stage'] ?? '') === 'paid'))],
                    ['label' => 'Doanh thu', 'value' => $this->money($salesAmount)],
                ],
            ],
            [
                'id' => 'inventory',
                'title' => 'Trang thiết bị',
                'subtitle' => 'Kho máy, thiết bị, loại thiết bị, mua sắm',
                'icon' => 'briefcase',
                'href' => '?route=machine-warehouses',
                'items' => [
                    ['label' => 'Kho máy', 'value' => count($data['machine_warehouses'] ?? [])],
                    ['label' => 'Thiết bị', 'value' => count($data['equipment_devices'] ?? [])],
                    ['label' => 'Loại thiết bị', 'value' => count($data['equipment_types'] ?? [])],
                    ['label' => 'Phiếu mua sắm', 'value' => count($purchaseRequests)],
                    ['label' => 'Giá trị thiết bị', 'value' => $this->money($inventoryValue)],
                ],
            ],
            [
                'id' => 'recruitment',
                'title' => 'Tuyển dụng',
                'subtitle' => 'Phiếu, ứng viên, trạng thái phê duyệt',
                'icon' => 'monitor',
                'href' => '?route=recruitment-requests',
                'items' => [
                    ['label' => 'Phiếu', 'value' => count($recruitmentRequests)],
                    ['label' => 'Chấp nhận', 'value' => count(array_filter($recruitmentRequests, fn (array $item): bool => ($item['status'] ?? '') === 'approved'))],
                    ['label' => 'Chờ duyệt', 'value' => count(array_filter($recruitmentRequests, fn (array $item): bool => ($item['status'] ?? '') === 'pending'))],
                    ['label' => 'Không chấp nhận', 'value' => count(array_filter($recruitmentRequests, fn (array $item): bool => ($item['status'] ?? '') === 'rejected'))],
                    ['label' => 'Tổng ứng viên', 'value' => array_sum(array_map(fn (array $item): int => (int) ($item['candidate_total'] ?? 0), $recruitmentRequests))],
                ],
            ],
            [
                'id' => 'attendance',
                'title' => 'Chấm công',
                'subtitle' => 'Giờ công, nhân sự tham gia, máy chấm công',
                'icon' => 'calendar',
                'href' => '?route=attendance',
                'items' => [
                    ['label' => 'Bản ghi', 'value' => count($attendance)],
                    ['label' => 'Nhân sự', 'value' => count(array_unique(array_filter(array_map(fn (array $item): string => (string) ($item['employee_id'] ?? $item['employee'] ?? ''), $attendance))))],
                    ['label' => 'Tổng giờ', 'value' => $this->number($attendanceHours)],
                    ['label' => 'Máy chấm công', 'value' => count($data['attendance_machines'] ?? [])],
                    ['label' => 'Vi phạm', 'value' => count($violations)],
                ],
            ],
        ];

        return [
            'filters' => $filters,
            'summary' => $summary,
            'miniReports' => $miniReports,
            'topWorkHours' => $this->topHours($dailyReports),
            'salesByStage' => $this->salesByStage($salesOrders),
            'attendanceByEmployee' => $this->attendanceByEmployee($attendance, $employees),
            'recentActivities' => $this->recentActivities($data),
            'exportUrl' => '?route=reports.export&from=' . urlencode($filters['from']) . '&to=' . urlencode($filters['to']) . '&module=' . urlencode($filters['module']),
        ];
    }

    private function filterByDate(array $items, array $filters, array $fields): array
    {
        $from = $filters['from'];
        $to = $filters['to'];
        if ($from === '' && $to === '') {
            return array_values($items);
        }

        return array_values(array_filter($items, function (array $item) use ($fields, $from, $to): bool {
            foreach ($fields as $field) {
                $date = $this->validDate((string) ($item[$field] ?? ''));
                if ($date === null) {
                    continue;
                }

                if (($from === '' || $date >= $from) && ($to === '' || $date <= $to)) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function validDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function sumAmount(array $items, string $field): float
    {
        return array_sum(array_map(fn (array $item): float => (float) ($item[$field] ?? 0), $items));
    }

    private function topHours(array $dailyReports): array
    {
        $map = [];
        foreach ($dailyReports as $item) {
            $name = trim((string) ($item['employee'] ?? 'Chưa xác định'));
            $map[$name] = ($map[$name] ?? 0) + (float) ($item['hours'] ?? 0);
        }

        arsort($map);
        return array_map(fn (string $name, float $hours): array => ['name' => $name, 'hours' => $this->number($hours)], array_keys(array_slice($map, 0, 6, true)), array_values(array_slice($map, 0, 6, true)));
    }

    private function salesByStage(array $orders): array
    {
        $labels = ['init' => 'Khởi tạo', 'quote' => 'Báo giá', 'contract' => 'Hợp đồng', 'paid' => 'Đã thanh toán'];
        $rows = [];
        foreach ($labels as $stage => $label) {
            $items = array_values(array_filter($orders, fn (array $item): bool => ($item['stage'] ?? 'init') === $stage));
            $rows[] = ['label' => $label, 'count' => count($items), 'amount' => $this->sumAmount($items, 'amount')];
        }

        return $rows;
    }

    private function attendanceByEmployee(array $attendance, array $employees): array
    {
        $names = [];
        foreach ($employees as $employee) {
            $names[(string) ($employee['id'] ?? '')] = (string) ($employee['name'] ?? '');
        }

        $map = [];
        foreach ($attendance as $item) {
            $id = (string) ($item['employee_id'] ?? '');
            $name = $names[$id] ?? (string) ($item['employee'] ?? 'Chưa xác định');
            $map[$name] = ($map[$name] ?? 0) + (float) ($item['total_hours'] ?? $item['hours'] ?? 0);
        }

        arsort($map);
        return array_map(fn (string $name, float $hours): array => ['name' => $name, 'hours' => $this->number($hours)], array_keys(array_slice($map, 0, 6, true)), array_values(array_slice($map, 0, 6, true)));
    }

    private function recentActivities(array $data): array
    {
        $rows = [];
        $source = $data['_activity_log'] ?? $data['_notifications'] ?? [];
        foreach (array_slice($source, 0, 8) as $item) {
            $rows[] = [
                'title' => (string) ($item['title'] ?? 'Thông báo'),
                'message' => (string) ($item['message'] ?? ''),
                'time' => (string) ($item['created_at'] ?? ''),
                'type' => (string) ($item['action'] ?? $item['type'] ?? 'info'),
            ];
        }

        return $rows;
    }

    private function money(float $value): string
    {
        return number_format($value, 0, ',', '.') . ' VNĐ';
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private function percent(int $value, int $total): string
    {
        return number_format(($value / max(1, $total)) * 100, 1, ',', '.') . '%';
    }
}
