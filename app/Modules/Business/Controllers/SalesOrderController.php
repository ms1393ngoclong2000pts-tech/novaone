<?php

declare(strict_types=1);

final class SalesOrderController
{
    private const STAGES = [
        'init' => 'Khởi Tạo',
        'quote' => 'Báo Giá',
        'contract' => 'Hợp Đồng',
        'paid' => 'Đã thanh toán',
    ];

    private const SORTABLE = ['code', 'name', 'stage', 'contact', 'amount', 'created_date'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $stage = array_key_exists($_GET['stage'] ?? '', self::STAGES) ? (string) $_GET['stage'] : '';
        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));
        $customer = trim((string) ($_GET['customer'] ?? ''));
        $customerGroup = trim((string) ($_GET['customer_group'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));

        $items = $this->filtered($store, $stage, $startDate, $endDate, $customer, $customerGroup, $query);
        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'created_date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = $sort === 'amount'
                ? ((float) ($a[$sort] ?? 0) <=> (float) ($b[$sort] ?? 0))
                : strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));
            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 25);
        $perPage = in_array($requested, $allowed, true) ? $requested : 25;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Business/sales_orders/index', [
            'active' => 'sales',
            'title' => 'Danh sách bán hàng',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'stages' => self::STAGES,
            'stage' => $stage,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'customer' => $customer,
            'customerGroup' => $customerGroup,
            'employees' => $store->get('employees'),
            'customers' => $this->customers($store),
            'customerGroups' => $this->customerGroups($store),
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
        $code = trim((string) ($_POST['code'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $stage = array_key_exists($_POST['stage'] ?? '', self::STAGES) ? (string) $_POST['stage'] : 'init';
        $createdDate = trim((string) ($_POST['created_date'] ?? date('Y-m-d')));
        $contactEmployeeId = trim((string) ($_POST['contact_employee_id'] ?? ''));
        $contactName = $this->employeeNameById($store, $contactEmployeeId);

        if ($code === '' || $name === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập mã đơn hàng và tên đơn hàng.';
            redirect('sales-orders');
        }
        if ($contactEmployeeId === '' || $contactName === '') {
            $_SESSION['flash_error'] = 'Vui lòng chọn người liên hệ trong danh sách nhân viên.';
            redirect('sales-orders');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'code' => $code,
            'name' => $name,
            'stage' => $stage,
            'contact' => $contactName,
            'contact_employee_id' => $contactEmployeeId,
            'amount' => max(0, (float) ($_POST['amount'] ?? 0)),
            'unit' => trim((string) ($_POST['unit'] ?? 'VNĐ')) ?: 'VNĐ',
            'created_date' => $createdDate,
            'customer' => trim((string) ($_POST['customer'] ?? '')),
            'customer_group' => trim((string) ($_POST['customer_group'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('sales_orders');
        $isUpdate = $id !== '';
        $items = $isUpdate
            ? array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items)
            : array_merge([$payload], $items);

        $store->put('sales_orders', $items);
        add_notification($store, 'Đơn hàng', ($isUpdate ? 'Đã cập nhật đơn hàng ' : 'Đã tạo đơn hàng ') . $code . '.', '?route=sales-orders', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật đơn hàng.' : 'Đã tạo đơn hàng mới.';
        redirect('sales-orders');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('sales_orders'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));

        $store->put('sales_orders', $items);
        if ($deleted !== null) {
            add_notification($store, 'Đơn hàng', 'Đã xóa đơn hàng ' . ($deleted['code'] ?? '') . '.', '?route=sales-orders', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa đơn hàng.';
        redirect('sales-orders');
    }

    private function filtered(DataStore $store, string $stage, string $startDate, string $endDate, string $customer, string $customerGroup, string $query): array
    {
        return array_values(array_filter($store->get('sales_orders'), function (array $item) use ($stage, $startDate, $endDate, $customer, $customerGroup, $query): bool {
            if ($stage !== '' && ($item['stage'] ?? '') !== $stage) {
                return false;
            }
            if ($startDate !== '' && ($item['created_date'] ?? '') < $startDate) {
                return false;
            }
            if ($endDate !== '' && ($item['created_date'] ?? '') > $endDate) {
                return false;
            }
            if ($customer !== '' && ($item['customer'] ?? '') !== $customer) {
                return false;
            }
            if ($customerGroup !== '' && ($item['customer_group'] ?? '') !== $customerGroup) {
                return false;
            }
            if ($query === '') {
                return true;
            }
            $haystack = implode(' ', array_map('strval', $item));
            $lower = fn (string $value): string => function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
            return str_contains($lower($haystack), $lower($query));
        }));
    }

    private function customers(DataStore $store): array
    {
        $values = array_values(array_unique(array_filter(array_map(fn (array $item): string => trim((string) ($item['customer'] ?? '')), $store->get('sales_orders')))));
        sort($values);
        return $values;
    }

    private function employeeNameById(DataStore $store, string $id): string
    {
        foreach ($store->get('employees') as $employee) {
            if (($employee['id'] ?? '') === $id) {
                return (string) ($employee['name'] ?? '');
            }
        }

        return '';
    }

    private function customerGroups(DataStore $store): array
    {
        $values = array_values(array_unique(array_filter(array_map(fn (array $item): string => trim((string) ($item['customer_group'] ?? '')), $store->get('sales_orders')))));
        sort($values);
        return $values;
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['sales_orders'])) {
            return;
        }

        $data['sales_orders'] = [
            ['id' => 'so01', 'code' => '20250115001', 'name' => 'HVN', 'stage' => 'init', 'contact' => '', 'amount' => 100000, 'unit' => 'VNĐ', 'created_date' => '2025-01-15', 'customer' => 'Khách hàng cá nhân', 'customer_group' => 'Khách lẻ', 'note' => 'Đơn mẫu khởi tạo'],
            ['id' => 'so02', 'code' => '20250629001', 'name' => 'NovaOne CRM', 'stage' => 'quote', 'contact' => 'Trần Ngọc Long', 'amount' => 35000000, 'unit' => 'VNĐ', 'created_date' => '2026-06-29', 'customer' => 'bData co.,ltd', 'customer_group' => 'Doanh nghiệp', 'note' => 'Báo giá phần mềm'],
            ['id' => 'so03', 'code' => '20250620002', 'name' => 'Triển khai thiết bị', 'stage' => 'contract', 'contact' => 'Phòng mua hàng', 'amount' => 82000000, 'unit' => 'VNĐ', 'created_date' => '2026-06-20', 'customer' => 'CÔNG TY TNHH DIC (VIỆT NAM)', 'customer_group' => 'Nhà máy', 'note' => 'Đang chờ ký hợp đồng'],
            ['id' => 'so04', 'code' => '20250512003', 'name' => 'Gói bảo trì', 'stage' => 'paid', 'contact' => 'Minh Anh', 'amount' => 12000000, 'unit' => 'VNĐ', 'created_date' => '2026-05-12', 'customer' => 'Happy Creative', 'customer_group' => 'Doanh nghiệp', 'note' => 'Đã thanh toán đủ'],
        ];
        $store->save($data);
    }
}
