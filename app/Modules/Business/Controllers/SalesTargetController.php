<?php

declare(strict_types=1);

final class SalesTargetController
{
    private const SORTABLE = ['month', 'year', 'revenue', 'quantity', 'created_date', 'customer', 'contract', 'manager'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $month = trim((string) ($_GET['month'] ?? ''));
        $year = trim((string) ($_GET['year'] ?? ''));
        $managerId = trim((string) ($_GET['manager_id'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));

        $items = $this->filtered($store, $month, $year, $managerId, $query);
        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'created_date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = in_array($sort, ['month', 'year', 'revenue', 'quantity'], true)
                ? ((float) ($a[$sort] ?? 0) <=> (float) ($b[$sort] ?? 0))
                : strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));

            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 10);
        $perPage = in_array($requested, $allowed, true) ? $requested : 10;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Business/sales_targets/index', [
            'active' => 'sales',
            'title' => 'Chỉ tiêu tháng',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'customers' => $this->customers($store),
            'contracts' => $this->contracts($store),
            'month' => $month,
            'year' => $year,
            'managerId' => $managerId,
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'years' => $this->years($store),
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $month = (int) ($_POST['month'] ?? 0);
        $year = (int) ($_POST['year'] ?? 0);
        $managerId = trim((string) ($_POST['manager_id'] ?? ''));
        $manager = $this->employeeNameById($store, $managerId);

        if ($month < 1 || $month > 12 || $year < 2000 || $managerId === '' || $manager === '') {
            $_SESSION['flash_error'] = 'Vui lòng chọn tháng, năm và nhân viên quản lý hợp lệ.';
            redirect('sales-targets');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'month' => $month,
            'year' => $year,
            'revenue' => max(0, (float) ($_POST['revenue'] ?? 0)),
            'quantity' => max(0, (float) ($_POST['quantity'] ?? 0)),
            'created_date' => trim((string) ($_POST['created_date'] ?? date('Y-m-d'))) ?: date('Y-m-d'),
            'customer' => trim((string) ($_POST['customer'] ?? '')),
            'contract' => trim((string) ($_POST['contract'] ?? '')),
            'manager_id' => $managerId,
            'manager' => $manager,
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('sales_targets');
        $isUpdate = $id !== '';
        $items = $isUpdate
            ? array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items)
            : array_merge([$payload], $items);

        $store->put('sales_targets', $items);
        add_notification($store, 'Chỉ tiêu tháng', ($isUpdate ? 'Đã cập nhật' : 'Đã thêm') . ' chỉ tiêu tháng ' . $month . '/' . $year . '.', '?route=sales-targets', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật chỉ tiêu.' : 'Đã thêm chỉ tiêu mới.';
        redirect('sales-targets');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('sales_targets'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }

            return true;
        }));

        $store->put('sales_targets', $items);
        if ($deleted !== null) {
            add_notification($store, 'Chỉ tiêu tháng', 'Đã xóa chỉ tiêu tháng ' . ($deleted['month'] ?? '') . '/' . ($deleted['year'] ?? '') . '.', '?route=sales-targets', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa chỉ tiêu.';
        redirect('sales-targets');
    }

    private function filtered(DataStore $store, string $month, string $year, string $managerId, string $query): array
    {
        return array_values(array_filter($store->get('sales_targets'), function (array $item) use ($month, $year, $managerId, $query): bool {
            if ($month !== '' && (string) ($item['month'] ?? '') !== $month) {
                return false;
            }
            if ($year !== '' && (string) ($item['year'] ?? '') !== $year) {
                return false;
            }
            if ($managerId !== '' && ($item['manager_id'] ?? '') !== $managerId) {
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

    private function employeeNameById(DataStore $store, string $id): string
    {
        foreach ($store->get('employees') as $employee) {
            if (($employee['id'] ?? '') === $id) {
                return (string) ($employee['name'] ?? '');
            }
        }

        return '';
    }

    private function customers(DataStore $store): array
    {
        $values = array_merge(
            array_map(fn (array $item): string => trim((string) ($item['customer'] ?? '')), $store->get('sales_targets')),
            array_map(fn (array $item): string => trim((string) ($item['customer'] ?? '')), $store->get('sales_orders'))
        );
        $values = array_values(array_unique(array_filter($values)));
        sort($values);

        return $values;
    }

    private function contracts(DataStore $store): array
    {
        $values = array_values(array_unique(array_filter(array_merge(
            array_map(fn (array $item): string => trim((string) ($item['contract'] ?? '')), $store->get('sales_targets')),
            array_map(fn (array $item): string => trim((string) ($item['name'] ?? '')), $store->get('sales_orders'))
        ))));
        sort($values);

        return $values;
    }

    private function years(DataStore $store): array
    {
        $values = array_values(array_unique(array_filter(array_map(fn (array $item): string => (string) ($item['year'] ?? ''), $store->get('sales_targets')))));
        $current = (string) date('Y');
        if (! in_array($current, $values, true)) {
            $values[] = $current;
        }
        rsort($values);

        return $values;
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['sales_targets'])) {
            return;
        }

        $employees = $store->get('employees');
        $employee = $employees[0] ?? ['id' => 'admin', 'name' => 'Admin Novaone'];
        $employee2 = $employees[1] ?? $employee;

        $data['sales_targets'] = [
            [
                'id' => 'target01',
                'month' => 1,
                'year' => 2026,
                'revenue' => 200,
                'quantity' => 150,
                'created_date' => '2026-05-19',
                'customer' => 'BDATA',
                'contract' => '',
                'manager_id' => (string) ($employee['id'] ?? ''),
                'manager' => (string) ($employee['name'] ?? ''),
                'note' => 'Chỉ tiêu mẫu tháng 1',
            ],
            [
                'id' => 'target02',
                'month' => 1,
                'year' => 2026,
                'revenue' => 5000,
                'quantity' => 10000,
                'created_date' => '2026-04-28',
                'customer' => 'BDATA',
                'contract' => '',
                'manager_id' => (string) ($employee2['id'] ?? ''),
                'manager' => (string) ($employee2['name'] ?? ''),
                'note' => 'Chỉ tiêu mẫu doanh số',
            ],
        ];
        $store->save($data);
    }
}
