<?php

declare(strict_types=1);

final class ViolationController
{
    private const SORTABLE = ['employee_name', 'violation_date', 'violation_type'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);
        $this->alignSampleEmployees($store);

        $fromDate = trim((string) ($_GET['from_date'] ?? ''));
        $toDate = trim((string) ($_GET['to_date'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $items = array_values(array_filter($store->get('violations'), function (array $item) use ($fromDate, $toDate, $query): bool {
            $date = (string) ($item['violation_date'] ?? '');
            if ($fromDate !== '' && $date < $fromDate) {
                return false;
            }
            if ($toDate !== '' && $date > $toDate) {
                return false;
            }
            if ($query === '') {
                return true;
            }
            $haystack = implode(' ', array_map('strval', $item));
            if (function_exists('mb_strtolower')) {
                return str_contains(mb_strtolower($haystack, 'UTF-8'), mb_strtolower($query, 'UTF-8'));
            }
            return str_contains(strtolower($haystack), strtolower($query));
        }));

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'violation_date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));
            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 50);
        $perPage = in_array($requested, $allowed, true) ? $requested : 50;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@HumanResources/violations/index', [
            'active' => 'employees',
            'title' => 'Danh Sách Phiếu Vi Phạm',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $employeeName = trim((string) ($_POST['employee_name'] ?? ''));
        $violationDate = trim((string) ($_POST['violation_date'] ?? ''));
        $violationType = trim((string) ($_POST['violation_type'] ?? ''));

        $employeeNames = array_filter(array_column($store->get('employees'), 'name'));
        if (! in_array($employeeName, $employeeNames, true)) {
            $_SESSION['flash_error'] = 'Người vi phạm phải được chọn từ Danh Sách Nhân Viên.';
            redirect('violations');
        }

        if ($violationDate === '' || $violationType === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập nhân viên, thời gian và loại vi phạm.';
            redirect('violations');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'employee_name' => $employeeName,
            'violation_date' => $violationDate,
            'violation_type' => $violationType,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'penalty' => max(0, (float) ($_POST['penalty'] ?? 0)),
            'resolution' => trim((string) ($_POST['resolution'] ?? '')),
        ];

        $items = $store->get('violations');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }
        $store->put('violations', $items);

        add_notification(
            $store,
            'Phiếu vi phạm',
            ($isUpdate ? 'Đã cập nhật phiếu vi phạm của ' : 'Đã lập phiếu vi phạm cho ') . $employeeName . '.',
            '?route=violations',
            $isUpdate ? 'info' : 'warning'
        );
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật phiếu vi phạm.' : 'Đã lập phiếu vi phạm mới.';
        redirect('violations');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('violations'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));
        $store->put('violations', $items);
        if ($deleted !== null) {
            add_notification($store, 'Phiếu vi phạm', 'Đã xóa phiếu vi phạm của ' . ($deleted['employee_name'] ?? '') . '.', '?route=violations', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa phiếu vi phạm.';
        redirect('violations');
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['violations'])) {
            return;
        }
        $data['violations'] = self::sampleData();
        $store->save($data);
    }

    public static function sampleData(): array
    {
        return [
            ['id' => 'vp01', 'employee_name' => 'Minh Nguyen', 'violation_date' => '2026-06-19', 'violation_type' => 'Vi phạm về thời gian làm việc', 'description' => 'Đi làm muộn 35 phút không báo trước.', 'penalty' => 200000, 'resolution' => 'Nhắc nhở và trừ KPI tháng'],
            ['id' => 'vp02', 'employee_name' => 'Ha Pham', 'violation_date' => '2026-06-19', 'violation_type' => 'Vi phạm quy trình kho', 'description' => 'Chưa hoàn tất biên bản bàn giao cuối ca.', 'penalty' => 150000, 'resolution' => 'Bổ sung biên bản trong ngày'],
            ['id' => 'vp03', 'employee_name' => 'Quang Le', 'violation_date' => '2026-06-18', 'violation_type' => 'Vi phạm về thời gian làm việc', 'description' => 'Nộp báo cáo công việc trễ hạn.', 'penalty' => 100000, 'resolution' => 'Nhắc nhở lần đầu'],
            ['id' => 'vp04', 'employee_name' => 'Ha Pham', 'violation_date' => '2026-06-16', 'violation_type' => 'Vi phạm nội quy', 'description' => 'Không đeo thẻ nhân viên trong giờ làm việc.', 'penalty' => 50000, 'resolution' => 'Nhắc nhở'],
            ['id' => 'vp05', 'employee_name' => 'Minh Nguyen', 'violation_date' => '2026-06-15', 'violation_type' => 'Vi phạm bảo mật thông tin', 'description' => 'Để tài liệu nội bộ tại khu vực công cộng.', 'penalty' => 500000, 'resolution' => 'Đào tạo lại quy định bảo mật'],
            ['id' => 'vp06', 'employee_name' => 'Minh Nguyen', 'violation_date' => '2026-05-28', 'violation_type' => 'Vi phạm về thời gian làm việc', 'description' => 'Vắng họp giao ban không có lý do.', 'penalty' => 200000, 'resolution' => 'Trừ KPI tháng'],
            ['id' => 'vp07', 'employee_name' => 'Ha Pham', 'violation_date' => '2026-05-20', 'violation_type' => 'Vi phạm sử dụng tài sản', 'description' => 'Sử dụng thiết bị không đúng quy trình.', 'penalty' => 300000, 'resolution' => 'Đào tạo lại quy trình thiết bị'],
            ['id' => 'vp08', 'employee_name' => 'Quang Le', 'violation_date' => '2026-05-10', 'violation_type' => 'Vi phạm nội quy', 'description' => 'Không cập nhật trạng thái công việc cuối ngày.', 'penalty' => 100000, 'resolution' => 'Nhắc nhở lần đầu'],
        ];
    }

    private function alignSampleEmployees(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['_migrations']['violation_employee_links_v1'])) {
            return;
        }

        $validNames = array_values(array_filter(array_column($data['employees'] ?? [], 'name')));
        $fallbacks = ['vp04' => 'Ha Pham', 'vp05' => 'Minh Nguyen'];
        foreach ($data['violations'] ?? [] as &$violation) {
            if (in_array($violation['employee_name'] ?? '', $validNames, true)) {
                continue;
            }
            $fallback = $fallbacks[$violation['id'] ?? ''] ?? null;
            if ($fallback !== null && in_array($fallback, $validNames, true)) {
                $violation['employee_name'] = $fallback;
            }
        }
        unset($violation);

        $data['_migrations']['violation_employee_links_v1'] = true;
        $store->save($data);
    }
}
