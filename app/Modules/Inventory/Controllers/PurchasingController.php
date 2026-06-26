<?php

declare(strict_types=1);

final class PurchasingController
{
    private const STATUSES = [
        'new' => 'Khởi tạo',
        'purchased' => 'Đã mua',
        'received' => 'Đã thực nhận',
        'debt' => 'Công nợ',
        'canceled' => 'Đã hủy',
    ];

    private const SORTABLE = ['voucher_no', 'requester', 'status', 'needed_date', 'receiver', 'approver'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);
        $this->normalizePeopleFields($store);

        $department = trim((string) ($_GET['department'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store, $department, $status, $query);

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'voucher_no';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));
            if ($sort === 'voucher_no') {
                $result = ((int) ($a[$sort] ?? 0)) <=> ((int) ($b[$sort] ?? 0));
            }
            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 10);
        $perPage = in_array($requested, $allowed, true) ? $requested : 10;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Inventory/purchasing/index', [
            'active' => 'internal_assets',
            'title' => 'Mua Sắm',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'departments' => $this->departments($store),
            'statuses' => self::STATUSES,
            'department' => $department,
            'status' => $status,
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
        $requester = trim((string) ($_POST['requester'] ?? ''));
        $receiver = trim((string) ($_POST['receiver'] ?? ''));
        $approver = trim((string) ($_POST['approver'] ?? ''));
        $neededDate = trim((string) ($_POST['needed_date'] ?? ''));
        if ($requester === '' || $neededDate === '') {
            $_SESSION['flash_error'] = 'Vui lòng chọn người yêu cầu và ngày cần.';
            redirect('purchasing');
        }

        $requester = $this->employeeName($store, $requester) ?? '';
        $receiver = $receiver === '' ? '' : ($this->employeeName($store, $receiver) ?? '');
        $approver = $approver === '' ? '' : ($this->employeeName($store, $approver) ?? '');
        if ($requester === '' || (trim((string) ($_POST['receiver'] ?? '')) !== '' && $receiver === '') || (trim((string) ($_POST['approver'] ?? '')) !== '' && $approver === '')) {
            $_SESSION['flash_error'] = 'NgÆ°á»i yĂªu cáº§u, ngÆ°á»i nháº­n vĂ  ngÆ°á»i phĂª duyá»‡t pháº£i chá»n tá»« Danh SĂ¡ch NhĂ¢n ViĂªn.';
            redirect('purchasing');
        }

        $status = array_key_exists($_POST['status'] ?? '', self::STATUSES) ? (string) $_POST['status'] : 'new';
        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'voucher_no' => $id !== '' ? (int) ($_POST['voucher_no'] ?? 0) : $this->nextVoucherNo($store),
            'requester' => $requester,
            'department' => trim((string) ($_POST['department'] ?? '')),
            'status' => $status,
            'needed_date' => $neededDate,
            'receiver' => $receiver,
            'approver' => $approver,
            'detail' => trim((string) ($_POST['detail'] ?? '')),
            'over_budget' => ! empty($_POST['over_budget']),
        ];

        $items = $store->get('purchase_requests');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put('purchase_requests', $items);
        add_notification($store, 'Mua sắm', ($isUpdate ? 'Đã cập nhật phiếu mua sắm #' : 'Đã tạo phiếu mua sắm #') . $payload['voucher_no'] . '.', '?route=purchasing', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật phiếu mua sắm.' : 'Đã tạo phiếu mua sắm.';
        redirect('purchasing');
    }

    public function approval(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $updated = null;
        $items = array_map(function (array $item) use ($id, &$updated): array {
            if (($item['id'] ?? '') === $id) {
                $item['status'] = ($item['status'] ?? '') === 'new' ? 'purchased' : 'new';
                $updated = $item;
            }
            return $item;
        }, $store->get('purchase_requests'));

        $store->put('purchase_requests', $items);
        if ($updated !== null) {
            add_notification($store, 'Mua sắm', 'Đã cập nhật phê duyệt phiếu #' . ($updated['voucher_no'] ?? '') . '.', '?route=purchasing', 'info');
        }
        $_SESSION['flash_success'] = 'Đã cập nhật trạng thái phiếu.';
        redirect('purchasing');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('purchase_requests'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));

        $store->put('purchase_requests', $items);
        if ($deleted !== null) {
            add_notification($store, 'Mua sắm', 'Đã xóa phiếu mua sắm #' . ($deleted['voucher_no'] ?? '') . '.', '?route=purchasing', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa phiếu mua sắm.';
        redirect('purchasing');
    }

    private function filtered(DataStore $store, string $department, string $status, string $query): array
    {
        return array_values(array_filter($store->get('purchase_requests'), function (array $item) use ($department, $status, $query): bool {
            if ($department !== '' && ($item['department'] ?? '') !== $department) {
                return false;
            }
            if ($status !== '' && ($item['status'] ?? '') !== $status) {
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
        $departments = array_values(array_unique(array_filter(array_map(
            fn (array $employee): string => trim((string) ($employee['department'] ?? '')),
            $store->get('employees')
        ))));
        sort($departments);
        return $departments;
    }

    private function nextVoucherNo(DataStore $store): int
    {
        $numbers = array_map(fn (array $item): int => (int) ($item['voucher_no'] ?? 0), $store->get('purchase_requests'));
        return max($numbers ?: [0]) + 1;
    }

    private function employeeName(DataStore $store, string $name): ?string
    {
        $needle = function_exists('mb_strtolower') ? mb_strtolower(trim($name), 'UTF-8') : strtolower(trim($name));
        foreach ($store->get('employees') as $employee) {
            $employeeName = trim((string) ($employee['name'] ?? ''));
            $candidate = function_exists('mb_strtolower') ? mb_strtolower($employeeName, 'UTF-8') : strtolower($employeeName);
            if ($employeeName !== '' && $candidate === $needle) {
                return $employeeName;
            }
        }
        return null;
    }

    private function normalizePeopleFields(DataStore $store): void
    {
        $items = $store->get('purchase_requests');
        $changed = false;
        foreach ($items as &$item) {
            foreach (['requester', 'receiver', 'approver'] as $field) {
                $value = trim((string) ($item[$field] ?? ''));
                $normalized = $value === '' ? '' : $this->employeeName($store, $value);
                if ($normalized !== null && $normalized !== $value) {
                    $item[$field] = $normalized;
                    $changed = true;
                }
            }
        }
        unset($item);

        if ($changed) {
            $store->put('purchase_requests', $items);
        }
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['purchase_requests'])) {
            return;
        }

        $data['purchase_requests'] = [
            ['id' => 'pr19', 'voucher_no' => 19, 'requester' => 'bData co.,ltd', 'department' => 'Admin', 'status' => 'new', 'needed_date' => '2026-05-26', 'receiver' => 'Trần Thị Thu Nguyên', 'approver' => 'Trần Thị Thu Nguyên', 'detail' => 'Yêu cầu bổ sung thiết bị văn phòng.', 'over_budget' => false],
            ['id' => 'pr18', 'voucher_no' => 18, 'requester' => 'bData co.,ltd', 'department' => 'Admin', 'status' => 'new', 'needed_date' => '2026-05-22', 'receiver' => 'Trần Thị Thu Nguyên', 'approver' => 'bData co.,ltd', 'detail' => 'Mua phụ kiện máy tính.', 'over_budget' => false],
            ['id' => 'pr17', 'voucher_no' => 17, 'requester' => 'bData co.,ltd', 'department' => 'Công nghệ', 'status' => 'new', 'needed_date' => '2026-05-21', 'receiver' => 'bData co.,ltd', 'approver' => 'Trần Thị Thu Nguyên', 'detail' => 'Thiết bị mạng nội bộ.', 'over_budget' => false],
            ['id' => 'pr16', 'voucher_no' => 16, 'requester' => 'bData co.,ltd', 'department' => 'Công nghệ', 'status' => 'new', 'needed_date' => '2026-05-20', 'receiver' => 'Nguyễn Xuân Hùng', 'approver' => 'bData co.,ltd', 'detail' => 'Thay thế màn hình hỏng.', 'over_budget' => false],
            ['id' => 'pr15', 'voucher_no' => 15, 'requester' => 'bData co.,ltd', 'department' => 'Admin', 'status' => 'new', 'needed_date' => '2026-04-30', 'receiver' => 'Nguyễn Xuân Hùng', 'approver' => 'Trần Thị Thu Nguyên', 'detail' => 'Mua vật tư phòng họp.', 'over_budget' => false],
            ['id' => 'pr14', 'voucher_no' => 14, 'requester' => 'bData co.,ltd', 'department' => 'Admin', 'status' => 'new', 'needed_date' => '2026-04-30', 'receiver' => 'bData co.,ltd', 'approver' => 'Trần Thị Thu Nguyên', 'detail' => 'Mua sắm theo kế hoạch tháng.', 'over_budget' => false],
            ['id' => 'pr13', 'voucher_no' => 13, 'requester' => 'bData co.,ltd', 'department' => 'Kinh doanh', 'status' => 'purchased', 'needed_date' => '2026-04-30', 'receiver' => 'bData co.,ltd', 'approver' => 'bData co.,ltd', 'detail' => 'Thiết bị hỗ trợ bán hàng.', 'over_budget' => true],
        ];
        $store->save($data);
    }
}
