<?php

declare(strict_types=1);

final class ContractController
{
    private const TYPES = [
        'hoc_viec' => 'Học việc',
        'thu_viec' => 'Thử việc',
        'chinh_thuc' => 'Chính thức',
        'dai_han' => 'Dài hạn',
    ];

    private const SORTABLE = ['contract_code', 'employee_name', 'salary', 'start_date', 'end_date'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $type = array_key_exists($_GET['type'] ?? '', self::TYPES) ? (string) $_GET['type'] : 'hoc_viec';
        $query = trim((string) ($_GET['q'] ?? ''));
        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));
        $items = $this->filtered($store, $type, $query, $startDate, $endDate);

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'start_date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));
            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 10);
        $perPage = in_array($requested, $allowed, true) ? $requested : 10;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@HumanResources/contracts/index', [
            'active' => 'employees',
            'title' => 'Hợp Đồng Lao Động',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'types' => self::TYPES,
            'type' => $type,
            'query' => $query,
            'startDate' => $startDate,
            'endDate' => $endDate,
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
        $type = array_key_exists($_POST['contract_type'] ?? '', self::TYPES)
            ? (string) $_POST['contract_type']
            : 'hoc_viec';
        $employeeName = trim((string) ($_POST['employee_name'] ?? ''));
        $contractCode = trim((string) ($_POST['contract_code'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));

        if ($employeeName === '' || $contractCode === '' || $startDate === '' || $endDate === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập đầy đủ thông tin hợp đồng.';
            redirect('contracts&type=' . $type);
        }

        if ($endDate < $startDate) {
            $_SESSION['flash_error'] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
            redirect('contracts&type=' . $type);
        }

        $items = $store->get('contracts');
        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'contract_code' => $contractCode,
            'employee_name' => $employeeName,
            'salary' => max(0, (float) ($_POST['salary'] ?? 0)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_type' => $type,
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }
        $store->put('contracts', $items);

        add_notification(
            $store,
            'Hợp đồng lao động',
            ($isUpdate ? 'Đã cập nhật hợp đồng ' : 'Đã thêm hợp đồng ') . $contractCode . ' cho ' . $employeeName . '.',
            '?route=contracts&type=' . urlencode($type),
            $isUpdate ? 'info' : 'success'
        );
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật hợp đồng.' : 'Đã thêm hợp đồng mới.';
        redirect('contracts&type=' . $type);
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $type = array_key_exists($_POST['contract_type'] ?? '', self::TYPES)
            ? (string) $_POST['contract_type']
            : 'hoc_viec';
        $deleted = null;
        $items = array_values(array_filter($store->get('contracts'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));
        $store->put('contracts', $items);

        if ($deleted !== null) {
            add_notification($store, 'Hợp đồng lao động', 'Đã xóa hợp đồng ' . ($deleted['contract_code'] ?? '') . '.', '?route=contracts&type=' . urlencode($type), 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa hợp đồng.';
        redirect('contracts&type=' . $type);
    }

    public function export(DataStore $store): never
    {
        require_auth();

        $type = array_key_exists($_GET['type'] ?? '', self::TYPES) ? (string) $_GET['type'] : 'hoc_viec';
        $items = $this->filtered(
            $store,
            $type,
            trim((string) ($_GET['q'] ?? '')),
            trim((string) ($_GET['start_date'] ?? '')),
            trim((string) ($_GET['end_date'] ?? ''))
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="hop-dong-lao-dong-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        fputcsv($output, ['contract_code', 'employee_name', 'salary', 'start_date', 'end_date', 'contract_type', 'note']);
        foreach ($items as $item) {
            fputcsv($output, [
                $item['contract_code'] ?? '', $item['employee_name'] ?? '', $item['salary'] ?? 0,
                $item['start_date'] ?? '', $item['end_date'] ?? '', self::TYPES[$item['contract_type'] ?? ''] ?? '', $item['note'] ?? '',
            ]);
        }
        fclose($output);
        exit;
    }

    private function filtered(DataStore $store, string $type, string $query, string $startDate, string $endDate): array
    {
        return array_values(array_filter($store->get('contracts'), function (array $item) use ($type, $query, $startDate, $endDate): bool {
            if (($item['contract_type'] ?? '') !== $type) {
                return false;
            }
            if ($startDate !== '' && ($item['start_date'] ?? '') < $startDate) {
                return false;
            }
            if ($endDate !== '' && ($item['end_date'] ?? '') > $endDate) {
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

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['contracts'])) {
            return;
        }

        $data['contracts'] = [
            ['id' => 'ct01', 'contract_code' => 'HDHV-2026-001', 'employee_name' => 'Minh Nguyen', 'salary' => 6500000, 'start_date' => '2026-06-01', 'end_date' => '2026-07-31', 'contract_type' => 'hoc_viec', 'note' => 'Học việc khối kinh doanh'],
            ['id' => 'ct02', 'contract_code' => 'HDHV-2026-002', 'employee_name' => 'Ha Pham', 'salary' => 6000000, 'start_date' => '2026-06-15', 'end_date' => '2026-08-14', 'contract_type' => 'hoc_viec', 'note' => 'Học việc kho vận'],
            ['id' => 'ct03', 'contract_code' => 'HDTV-2026-001', 'employee_name' => 'Quang Le', 'salary' => 12000000, 'start_date' => '2026-05-01', 'end_date' => '2026-06-30', 'contract_type' => 'thu_viec', 'note' => 'Thử việc lập trình viên'],
            ['id' => 'ct04', 'contract_code' => 'HDTV-2026-002', 'employee_name' => 'Linh Tran', 'salary' => 11000000, 'start_date' => '2026-05-15', 'end_date' => '2026-07-14', 'contract_type' => 'thu_viec', 'note' => 'Thử việc phòng nhân sự'],
            ['id' => 'ct05', 'contract_code' => 'HDCT-2026-001', 'employee_name' => 'Minh Nguyen', 'salary' => 18000000, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'contract_type' => 'chinh_thuc', 'note' => 'Hợp đồng chính thức 12 tháng'],
            ['id' => 'ct06', 'contract_code' => 'HDCT-2026-002', 'employee_name' => 'Ha Pham', 'salary' => 15000000, 'start_date' => '2026-02-01', 'end_date' => '2027-01-31', 'contract_type' => 'chinh_thuc', 'note' => 'Hợp đồng chính thức 12 tháng'],
            ['id' => 'ct07', 'contract_code' => 'HDDH-2025-001', 'employee_name' => 'Admin Novaone', 'salary' => 32000000, 'start_date' => '2025-01-01', 'end_date' => '2028-12-31', 'contract_type' => 'dai_han', 'note' => 'Hợp đồng dài hạn'],
            ['id' => 'ct08', 'contract_code' => 'HDDH-2025-002', 'employee_name' => 'Linh Tran', 'salary' => 22000000, 'start_date' => '2025-06-01', 'end_date' => '2028-05-31', 'contract_type' => 'dai_han', 'note' => 'Hợp đồng dài hạn'],
        ];
        $store->save($data);
    }
}
