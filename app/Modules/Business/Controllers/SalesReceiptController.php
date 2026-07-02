<?php

declare(strict_types=1);

final class SalesReceiptController
{
    private const SORTABLE = ['code', 'customer', 'created_date', 'address', 'phone'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store, $query);
        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'created_date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = $sort === 'code'
                ? ((int) ($a[$sort] ?? 0) <=> (int) ($b[$sort] ?? 0))
                : strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));

            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 25);
        $perPage = in_array($requested, $allowed, true) ? $requested : 25;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Business/sales_receipts/index', [
            'active' => 'sales',
            'title' => 'Phiếu bán hàng',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'customers' => $this->customers($store),
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));
        $customer = trim((string) ($_POST['customer'] ?? ''));
        $createdDate = trim((string) ($_POST['created_date'] ?? date('Y-m-d')));
        $phone = preg_replace('/[^\d+]/', '', (string) ($_POST['phone'] ?? '')) ?? '';

        if ($code === '' || $createdDate === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập mã đơn hàng và ngày tạo.';
            redirect('sales-receipts');
        }

        if ($phone !== '' && ! preg_match('/^\+?\d{8,15}$/', $phone)) {
            $_SESSION['flash_error'] = 'Số điện thoại không hợp lệ.';
            redirect('sales-receipts');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'code' => $code,
            'customer' => $customer,
            'created_date' => $createdDate,
            'address' => trim((string) ($_POST['address'] ?? '')),
            'phone' => $phone,
            'total' => max(0, (float) ($_POST['total'] ?? 0)),
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('sales_receipts');
        $isUpdate = $id !== '';
        $items = $isUpdate
            ? array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items)
            : array_merge([$payload], $items);

        $store->put('sales_receipts', $items);
        add_notification($store, 'Phiếu bán hàng', ($isUpdate ? 'Đã cập nhật phiếu ' : 'Đã tạo phiếu ') . $code . '.', '?route=sales-receipts', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật phiếu bán hàng.' : 'Đã thêm phiếu bán hàng mới.';
        redirect('sales-receipts');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('sales_receipts'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }

            return true;
        }));

        $store->put('sales_receipts', $items);
        if ($deleted !== null) {
            add_notification($store, 'Phiếu bán hàng', 'Đã xóa phiếu ' . ($deleted['code'] ?? '') . '.', '?route=sales-receipts', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa phiếu bán hàng.';
        redirect('sales-receipts');
    }

    private function filtered(DataStore $store, string $query): array
    {
        return array_values(array_filter($store->get('sales_receipts'), function (array $item) use ($query): bool {
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
        $values = array_merge(
            array_map(fn (array $item): string => trim((string) ($item['customer'] ?? '')), $store->get('sales_receipts')),
            array_map(fn (array $item): string => trim((string) ($item['customer'] ?? '')), $store->get('sales_orders'))
        );
        $values = array_values(array_unique(array_filter($values)));
        sort($values);

        return $values;
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['sales_receipts'])) {
            return;
        }

        $data['sales_receipts'] = [
            ['id' => 'receipt445', 'code' => '445', 'customer' => 'Tú UyênKhách hàng', 'created_date' => '2026-06-17', 'address' => '', 'phone' => '0369852111', 'total' => 12500000, 'note' => 'Phiếu bán hàng mẫu'],
            ['id' => 'receipt442', 'code' => '442', 'customer' => 'Trần NgọcLong', 'created_date' => '2026-06-15', 'address' => '', 'phone' => '0768104432', 'total' => 8600000, 'note' => 'Khách hàng mua thiết bị'],
            ['id' => 'receipt441', 'code' => '441', 'customer' => 'Trần NgọcLong', 'created_date' => '2026-06-10', 'address' => '', 'phone' => '0768104432', 'total' => 7200000, 'note' => 'Thanh toán sau'],
            ['id' => 'receipt440', 'code' => '440', 'customer' => 'thunguyễn', 'created_date' => '2026-06-09', 'address' => 'gggg', 'phone' => '0233222555', 'total' => 3100000, 'note' => 'Giao trong ngày'],
            ['id' => 'receipt439', 'code' => '439', 'customer' => 'thunguyễn', 'created_date' => '2026-06-10', 'address' => 'gggg', 'phone' => '0233222555', 'total' => 2800000, 'note' => 'Phiếu lặp lại'],
            ['id' => 'receipt438', 'code' => '438', 'customer' => 'hoamai', 'created_date' => '2026-05-27', 'address' => 'huế', 'phone' => '0369258852', 'total' => 4500000, 'note' => 'Khách hàng tỉnh'],
            ['id' => 'receipt437', 'code' => '437', 'customer' => 'thunguyễn', 'created_date' => '2026-06-15', 'address' => 'gggg', 'phone' => '0233222555', 'total' => 1900000, 'note' => 'Đã giao'],
            ['id' => 'receipt436', 'code' => '436', 'customer' => 'thiTami', 'created_date' => '2026-06-15', 'address' => 'ggggggggg', 'phone' => '0125444444', 'total' => 3600000, 'note' => 'Cần xuất hóa đơn'],
            ['id' => 'receipt435', 'code' => '435', 'customer' => '', 'created_date' => '2026-06-08', 'address' => '', 'phone' => '0987456321', 'total' => 2200000, 'note' => 'Khách chưa cập nhật tên'],
            ['id' => 'receipt434', 'code' => '434', 'customer' => 'thunguyễn', 'created_date' => '2026-06-09', 'address' => 'gggg', 'phone' => '0233222555', 'total' => 1750000, 'note' => 'Phiếu mẫu thứ 10'],
        ];

        $store->save($data);
    }
}
