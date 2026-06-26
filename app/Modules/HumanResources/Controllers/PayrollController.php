<?php

declare(strict_types=1);

final class PayrollController
{
    private const TYPES = [
        'fixed' => 'Lương cố định',
        'shift' => 'Lương theo ca',
    ];

    private const STATUSES = [
        'completed' => 'Hoàn Thành',
        'draft' => 'Đang xử lý',
    ];

    private const SORTABLE = ['name', 'department', 'employee_scope', 'total_salary', 'created_date', 'status'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $type = array_key_exists($_GET['type'] ?? '', self::TYPES) ? (string) $_GET['type'] : '';
        $department = trim((string) ($_GET['department'] ?? ''));
        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));

        $items = $this->filtered($store, $type, $department, $startDate, $endDate, $query);
        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'created_date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            if ($sort === 'total_salary') {
                $result = ((float) ($a[$sort] ?? 0)) <=> ((float) ($b[$sort] ?? 0));
            } else {
                $result = strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));
            }

            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 50);
        $perPage = in_array($requested, $allowed, true) ? $requested : 50;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@HumanResources/payrolls/index', [
            'active' => 'employees',
            'title' => 'Bảng Lương',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'departments' => $this->departments($store),
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'type' => $type,
            'department' => $department,
            'startDate' => $startDate,
            'endDate' => $endDate,
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
        $name = trim((string) ($_POST['name'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $employeeScope = trim((string) ($_POST['employee_scope'] ?? 'Tất cả'));
        $createdDate = trim((string) ($_POST['created_date'] ?? ''));
        $type = array_key_exists($_POST['salary_type'] ?? '', self::TYPES) ? (string) $_POST['salary_type'] : 'fixed';
        $status = array_key_exists($_POST['status'] ?? '', self::STATUSES) ? (string) $_POST['status'] : 'draft';

        if ($name === '' || $createdDate === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên bảng lương và ngày tạo.';
            redirect('payrolls');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'name' => $name,
            'department' => $department,
            'applied_position' => trim((string) ($_POST['applied_position'] ?? $department)),
            'employee_scope' => $employeeScope === '' ? 'Tất cả' : $employeeScope,
            'total_salary' => max(0, (float) ($_POST['total_salary'] ?? 0)),
            'created_date' => $createdDate,
            'status' => $status,
            'salary_type' => $type,
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('payrolls');
        $isUpdate = $id !== '';

        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put('payrolls', $items);
        add_notification(
            $store,
            'Bảng lương',
            ($isUpdate ? 'Đã cập nhật bảng lương ' : 'Đã tạo bảng lương ') . $name . '.',
            '?route=payrolls',
            $isUpdate ? 'info' : 'success'
        );

        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật bảng lương.' : 'Đã tạo bảng lương mới.';
        redirect('payrolls');
    }

    public function complete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $updated = null;
        $items = array_map(function (array $item) use ($id, &$updated): array {
            if (($item['id'] ?? '') === $id) {
                $item['status'] = 'completed';
                $updated = $item;
            }

            return $item;
        }, $store->get('payrolls'));

        $store->put('payrolls', $items);

        if ($updated !== null) {
            add_notification($store, 'Bảng lương', 'Đã hoàn thành bảng lương ' . ($updated['name'] ?? '') . '.', '?route=payrolls', 'success');
        }

        $_SESSION['flash_success'] = 'Đã cập nhật trạng thái bảng lương.';
        redirect('payrolls');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('payrolls'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }

            return true;
        }));

        $store->put('payrolls', $items);

        if ($deleted !== null) {
            add_notification($store, 'Bảng lương', 'Đã xóa bảng lương ' . ($deleted['name'] ?? '') . '.', '?route=payrolls', 'danger');
        }

        $_SESSION['flash_success'] = 'Đã xóa bảng lương.';
        redirect('payrolls');
    }

    public function export(DataStore $store): never
    {
        require_auth();

        $items = $this->filtered(
            $store,
            array_key_exists($_GET['type'] ?? '', self::TYPES) ? (string) $_GET['type'] : '',
            trim((string) ($_GET['department'] ?? '')),
            trim((string) ($_GET['start_date'] ?? '')),
            trim((string) ($_GET['end_date'] ?? '')),
            trim((string) ($_GET['q'] ?? ''))
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bang-luong-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        fputcsv($output, ['name', 'salary_type', 'department', 'applied_position', 'employee_scope', 'total_salary', 'created_date', 'status', 'note']);

        foreach ($items as $item) {
            fputcsv($output, [
                $item['name'] ?? '',
                self::TYPES[$item['salary_type'] ?? 'fixed'] ?? '',
                $item['department'] ?? '',
                $item['applied_position'] ?? '',
                $item['employee_scope'] ?? '',
                $item['total_salary'] ?? 0,
                $item['created_date'] ?? '',
                self::STATUSES[$item['status'] ?? 'draft'] ?? '',
                $item['note'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    private function filtered(DataStore $store, string $type, string $department, string $startDate, string $endDate, string $query): array
    {
        return array_values(array_filter($store->get('payrolls'), function (array $item) use ($type, $department, $startDate, $endDate, $query): bool {
            if ($type !== '' && ($item['salary_type'] ?? '') !== $type) {
                return false;
            }

            if ($department !== '' && ($item['department'] ?? '') !== $department && ($item['applied_position'] ?? '') !== $department) {
                return false;
            }

            if ($startDate !== '' && ($item['created_date'] ?? '') < $startDate) {
                return false;
            }

            if ($endDate !== '' && ($item['created_date'] ?? '') > $endDate) {
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
        $departments = array_map(
            fn (array $employee): string => trim((string) ($employee['department'] ?? '')),
            $store->get('employees')
        );

        $departments = array_values(array_unique(array_filter(array_merge($departments, [
            'BACK OFFICE',
            'REVILINK',
            'Tuyển Dụng',
            'Kinh Doanh',
            'Kỹ Thuật',
        ]))));

        sort($departments);
        return $departments;
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['payrolls'])) {
            return;
        }

        $data['payrolls'] = [
            ['id' => 'pl01', 'name' => 'Test1', 'department' => 'BACK OFFICE', 'applied_position' => 'BACK OFFICE', 'employee_scope' => 'Tất cả', 'total_salary' => 0, 'created_date' => '2026-06-15', 'status' => 'completed', 'salary_type' => 'fixed', 'note' => 'Bảng lương mẫu khối Back Office'],
            ['id' => 'pl02', 'name' => 'Test', 'department' => 'REVILINK', 'applied_position' => 'REVILINK', 'employee_scope' => 'Tất cả', 'total_salary' => 0, 'created_date' => '2026-06-15', 'status' => 'draft', 'salary_type' => 'shift', 'note' => 'Đang rà soát dữ liệu ca'],
            ['id' => 'pl03', 'name' => 'Long Trần', 'department' => '', 'applied_position' => '', 'employee_scope' => 'Tất cả', 'total_salary' => 37010000, 'created_date' => '2026-06-10', 'status' => 'completed', 'salary_type' => 'fixed', 'note' => 'Bảng lương cá nhân'],
            ['id' => 'pl04', 'name' => 'Test', 'department' => 'Tuyển Dụng', 'applied_position' => 'Tuyển Dụng', 'employee_scope' => 'Tất cả', 'total_salary' => 0, 'created_date' => '2026-06-08', 'status' => 'completed', 'salary_type' => 'fixed', 'note' => 'Bảng lương tuyển dụng'],
            ['id' => 'pl05', 'name' => 'bảng lương test 1', 'department' => 'Tuyển Dụng', 'applied_position' => 'Tuyển Dụng', 'employee_scope' => 'Tất cả', 'total_salary' => 0, 'created_date' => '2026-04-23', 'status' => 'completed', 'salary_type' => 'fixed', 'note' => 'Dữ liệu kiểm thử'],
            ['id' => 'pl06', 'name' => 'ds', 'department' => '', 'applied_position' => '', 'employee_scope' => 'Tất cả', 'total_salary' => 22100045, 'created_date' => '2024-07-22', 'status' => 'completed', 'salary_type' => 'fixed', 'note' => 'Dữ liệu lịch sử'],
            ['id' => 'pl07', 'name' => 'Lương tháng 12', 'department' => '', 'applied_position' => '', 'employee_scope' => 'Tất cả', 'total_salary' => 48700069, 'created_date' => '2024-02-27', 'status' => 'completed', 'salary_type' => 'shift', 'note' => 'Bảng lương tháng 12'],
        ];
        $store->save($data);
    }
}
