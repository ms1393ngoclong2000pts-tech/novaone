<?php

declare(strict_types=1);

final class RequestFormController
{
    private const TYPES = [
        'general' => 'Hội nghị, học tập',
        'advance' => 'Ứng tiền',
        'overtime' => 'Yêu cầu tăng ca',
        'violation' => 'Vi phạm',
        'reward' => 'Khen thưởng',
        'extra_work' => 'Phiếu làm thêm',
        'leave' => 'Nghỉ phép',
        'sick' => 'Ốm, điều dưỡng',
    ];

    private const APPROVALS = [
        'approved' => 'Chấp nhận',
        'rejected' => 'Không chấp thuận',
        'pending' => 'Chờ duyệt',
    ];

    private const SORTABLE = ['request_type', 'employee_name', 'department', 'created_date', 'start_date', 'end_date', 'approval'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $employee = trim((string) ($_GET['employee'] ?? ''));
        $department = trim((string) ($_GET['department'] ?? ''));
        $fromDate = trim((string) ($_GET['from_date'] ?? ''));
        $toDate = trim((string) ($_GET['to_date'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store, $employee, $department, $fromDate, $toDate, $query);

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'created_date';
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

        View::render('@HumanResources/requests/index', [
            'active' => 'employees',
            'title' => 'Phiếu Yêu Cầu',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'departments' => $this->departments($store),
            'types' => self::TYPES,
            'approvals' => self::APPROVALS,
            'employee' => $employee,
            'department' => $department,
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
        $requestType = array_key_exists($_POST['request_type'] ?? '', self::TYPES) ? (string) $_POST['request_type'] : 'general';
        $createdDate = trim((string) ($_POST['created_date'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $approval = array_key_exists($_POST['approval'] ?? '', self::APPROVALS) ? (string) $_POST['approval'] : 'pending';

        $employeeData = $this->employeeByName($store, $employeeName);
        if ($employeeName === '' || $employeeData === null) {
            $_SESSION['flash_error'] = 'Người yêu cầu phải được chọn từ Danh Sách Nhân Viên.';
            redirect('requests');
        }

        if ($createdDate === '' || $startDate === '' || $endDate === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập đầy đủ ngày tạo, ngày bắt đầu và ngày kết thúc.';
            redirect('requests');
        }

        if ($endDate < $startDate) {
            $_SESSION['flash_error'] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
            redirect('requests');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'request_type' => $requestType,
            'employee_name' => $employeeName,
            'department' => trim((string) ($_POST['department'] ?? ($employeeData['department'] ?? ''))),
            'created_date' => $createdDate,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'approval' => $approval,
            'amount' => max(0, (float) ($_POST['amount'] ?? 0)),
            'detail' => trim((string) ($_POST['detail'] ?? '')),
        ];

        $items = $store->get('request_forms');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }
        $store->put('request_forms', $items);

        add_notification(
            $store,
            'Phiếu yêu cầu',
            ($isUpdate ? 'Đã cập nhật phiếu yêu cầu của ' : 'Đã tạo phiếu yêu cầu cho ') . $employeeName . '.',
            '?route=requests',
            $isUpdate ? 'info' : 'success'
        );
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật phiếu yêu cầu.' : 'Đã tạo phiếu yêu cầu mới.';
        redirect('requests');
    }

    public function approval(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $approval = array_key_exists($_POST['approval'] ?? '', self::APPROVALS) ? (string) $_POST['approval'] : 'pending';
        $updated = null;
        $items = array_map(function (array $item) use ($id, $approval, &$updated): array {
            if (($item['id'] ?? '') === $id) {
                $item['approval'] = $approval;
                $updated = $item;
            }
            return $item;
        }, $store->get('request_forms'));

        $store->put('request_forms', $items);
        if ($updated !== null) {
            add_notification($store, 'Phiếu yêu cầu', 'Đã cập nhật phê duyệt phiếu của ' . ($updated['employee_name'] ?? '') . '.', '?route=requests', 'info');
        }
        $_SESSION['flash_success'] = 'Đã cập nhật trạng thái phê duyệt.';
        redirect('requests');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('request_forms'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));
        $store->put('request_forms', $items);

        if ($deleted !== null) {
            add_notification($store, 'Phiếu yêu cầu', 'Đã xóa phiếu yêu cầu của ' . ($deleted['employee_name'] ?? '') . '.', '?route=requests', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa phiếu yêu cầu.';
        redirect('requests');
    }

    private function filtered(DataStore $store, string $employee, string $department, string $fromDate, string $toDate, string $query): array
    {
        return array_values(array_filter($store->get('request_forms'), function (array $item) use ($employee, $department, $fromDate, $toDate, $query): bool {
            if ($employee !== '' && ($item['employee_name'] ?? '') !== $employee) {
                return false;
            }
            if ($department !== '' && ($item['department'] ?? '') !== $department) {
                return false;
            }
            $createdDate = (string) ($item['created_date'] ?? '');
            if ($fromDate !== '' && $createdDate < $fromDate) {
                return false;
            }
            if ($toDate !== '' && $createdDate > $toDate) {
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
    }

    private function departments(DataStore $store): array
    {
        $departments = array_map(fn (array $employee): string => trim((string) ($employee['department'] ?? '')), $store->get('employees'));
        $departments = array_values(array_unique(array_filter(array_merge($departments, ['Admin', 'Lập Trình Viên', 'Kinh doanh', 'Công nghệ']))));
        sort($departments);
        return $departments;
    }

    private function employeeByName(DataStore $store, string $name): ?array
    {
        foreach ($store->get('employees') as $employee) {
            if (($employee['name'] ?? '') === $name) {
                return $employee;
            }
        }
        return null;
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['request_forms'])) {
            return;
        }

        $employeeNames = array_values(array_filter(array_column($data['employees'] ?? [], 'name')));
        $first = $employeeNames[0] ?? 'Minh Nguyen';
        $second = $employeeNames[1] ?? $first;
        $third = $employeeNames[2] ?? $second;

        $data['request_forms'] = [
            ['id' => 'rq01', 'request_type' => 'general', 'employee_name' => $first, 'department' => 'Lập Trình Viên', 'created_date' => '2026-04-23', 'start_date' => '2026-04-22', 'end_date' => '2026-04-23', 'approval' => 'approved', 'amount' => 0, 'detail' => 'Đề nghị tham gia hội nghị chuyên môn.'],
            ['id' => 'rq02', 'request_type' => 'leave', 'employee_name' => $second, 'department' => 'Admin', 'created_date' => '2024-08-02', 'start_date' => '2024-08-06', 'end_date' => '2024-08-24', 'approval' => 'rejected', 'amount' => 0, 'detail' => 'Xin nghỉ phép dài ngày.'],
            ['id' => 'rq03', 'request_type' => 'sick', 'employee_name' => $second, 'department' => 'Admin', 'created_date' => '2024-08-01', 'start_date' => '2024-08-05', 'end_date' => '2024-08-05', 'approval' => 'approved', 'amount' => 0, 'detail' => 'Nghỉ ốm có giấy xác nhận.'],
            ['id' => 'rq04', 'request_type' => 'sick', 'employee_name' => $second, 'department' => 'Admin', 'created_date' => '2024-08-01', 'start_date' => '2024-08-05', 'end_date' => '2024-08-23', 'approval' => 'rejected', 'amount' => 0, 'detail' => 'Đề nghị điều dưỡng.'],
            ['id' => 'rq05', 'request_type' => 'sick', 'employee_name' => $second, 'department' => 'Admin', 'created_date' => '2024-07-19', 'start_date' => '2024-07-01', 'end_date' => '2024-07-11', 'approval' => 'approved', 'amount' => 0, 'detail' => 'Nghỉ điều dưỡng sau ốm.'],
            ['id' => 'rq06', 'request_type' => 'sick', 'employee_name' => $third, 'department' => 'Admin', 'created_date' => '2024-07-19', 'start_date' => '2024-07-01', 'end_date' => '2024-07-26', 'approval' => 'approved', 'amount' => 0, 'detail' => 'Nghỉ điều trị ngắn hạn.'],
            ['id' => 'rq07', 'request_type' => 'sick', 'employee_name' => $second, 'department' => 'Admin', 'created_date' => '2024-07-19', 'start_date' => '2024-07-08', 'end_date' => '2024-08-02', 'approval' => 'rejected', 'amount' => 0, 'detail' => 'Thời gian nghỉ vượt định mức.'],
        ];
        $store->save($data);
    }
}
