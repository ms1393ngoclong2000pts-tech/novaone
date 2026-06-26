<?php

declare(strict_types=1);

final class SocialInsuranceController
{
    private const SORTABLE = [
        'employee_name', 'employee_code', 'contract_start', 'contract_end',
        'insurance_number', 'salary', 'contribution',
    ];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $employee = trim((string) ($_GET['employee'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $allItems = $store->get('social_insurance');
        $employees = $store->get('employees');
        $employeeNames = array_values(array_unique(array_filter(array_column($employees, 'name'))));
        natcasesort($employeeNames);
        $employeeNames = array_values($employeeNames);

        $items = array_values(array_filter($allItems, function (array $item) use ($employee, $query): bool {
            if ($employee !== '' && ($item['employee_name'] ?? '') !== $employee) {
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

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'employee_name';
        $direction = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));
            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 25);
        $perPage = in_array($requested, $allowed, true) ? $requested : 25;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@HumanResources/social_insurance/index', [
            'active' => 'employees',
            'title' => 'Bảo Hiểm Xã Hội',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $employees,
            'employeeNames' => $employeeNames,
            'employee' => $employee,
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
        $employeeCode = trim((string) ($_POST['employee_code'] ?? ''));
        $contractStart = trim((string) ($_POST['contract_start'] ?? ''));
        $contractEnd = trim((string) ($_POST['contract_end'] ?? ''));

        if ($employeeName === '' || $employeeCode === '' || $contractStart === '' || $contractEnd === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập đầy đủ nhân viên, mã nhân viên và thời hạn hợp đồng.';
            redirect('social-insurance');
        }
        if ($contractEnd < $contractStart) {
            $_SESSION['flash_error'] = 'Ngày kết thúc hợp đồng phải sau hoặc bằng ngày bắt đầu.';
            redirect('social-insurance');
        }

        $salary = max(0, (float) ($_POST['salary'] ?? 0));
        $contribution = trim((string) ($_POST['contribution'] ?? ''));
        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'employee_name' => $employeeName,
            'employee_code' => $employeeCode,
            'contract_start' => $contractStart,
            'contract_end' => $contractEnd,
            'insurance_number' => trim((string) ($_POST['insurance_number'] ?? '')),
            'salary' => $salary,
            'contribution' => $contribution === '' ? round($salary * 0.105) : max(0, (float) $contribution),
            'hospital' => trim((string) ($_POST['hospital'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('social_insurance');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }
        $store->put('social_insurance', $items);

        add_notification(
            $store,
            'Bảo hiểm xã hội',
            ($isUpdate ? 'Đã cập nhật BHXH của ' : 'Đã thêm tham gia BHXH cho ') . $employeeName . '.',
            '?route=social-insurance',
            $isUpdate ? 'info' : 'success'
        );
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật thông tin BHXH.' : 'Đã thêm người tham gia BHXH.';
        redirect('social-insurance');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('social_insurance'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));
        $store->put('social_insurance', $items);
        if ($deleted !== null) {
            add_notification($store, 'Bảo hiểm xã hội', 'Đã xóa thông tin BHXH của ' . ($deleted['employee_name'] ?? '') . '.', '?route=social-insurance', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa thông tin tham gia BHXH.';
        redirect('social-insurance');
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['social_insurance'])) {
            return;
        }

        $data['social_insurance'] = self::sampleData();
        $store->save($data);
    }

    public static function sampleData(): array
    {
        return [
            ['id' => 'bh01', 'employee_name' => 'Minh Nguyen', 'employee_code' => 'NV001', 'contract_start' => '2026-01-01', 'contract_end' => '2026-12-31', 'insurance_number' => 'BHXH-010126001', 'salary' => 18000000, 'contribution' => 1890000, 'hospital' => 'Bệnh viện Đa khoa Hà Nội', 'note' => 'Tham gia đầy đủ'],
            ['id' => 'bh02', 'employee_name' => 'Ha Pham', 'employee_code' => 'NV002', 'contract_start' => '2026-02-01', 'contract_end' => '2027-01-31', 'insurance_number' => 'BHXH-020226002', 'salary' => 15000000, 'contribution' => 1575000, 'hospital' => 'Bệnh viện Nhân Dân 115', 'note' => 'Tham gia đầy đủ'],
            ['id' => 'bh03', 'employee_name' => 'Quang Le', 'employee_code' => 'NV003', 'contract_start' => '2026-05-01', 'contract_end' => '2026-06-30', 'insurance_number' => 'BHXH-010526003', 'salary' => 12000000, 'contribution' => 1260000, 'hospital' => 'Bệnh viện Thống Nhất', 'note' => 'Đang thử việc'],
            ['id' => 'bh04', 'employee_name' => 'Linh Tran', 'employee_code' => 'NV004', 'contract_start' => '2026-01-01', 'contract_end' => '2026-12-31', 'insurance_number' => 'BHXH-010126004', 'salary' => 22000000, 'contribution' => 2310000, 'hospital' => 'Bệnh viện Đại học Y Dược', 'note' => 'Tham gia đầy đủ'],
            ['id' => 'bh05', 'employee_name' => 'Long Trần', 'employee_code' => 'NV005', 'contract_start' => '2026-06-01', 'contract_end' => '2027-05-31', 'insurance_number' => 'BHXH-010626005', 'salary' => 16000000, 'contribution' => 1680000, 'hospital' => 'Bệnh viện Đa khoa Tâm Anh', 'note' => 'Mới đăng ký'],
        ];
    }
}
