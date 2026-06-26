<?php

declare(strict_types=1);

final class RewardController
{
    private const SORTABLE = ['employee_name', 'reward_date', 'reward_type'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $fromDate = trim((string) ($_GET['from_date'] ?? ''));
        $toDate = trim((string) ($_GET['to_date'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $items = array_values(array_filter($store->get('rewards'), function (array $item) use ($fromDate, $toDate, $query): bool {
            $date = (string) ($item['reward_date'] ?? '');
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

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'reward_date';
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

        View::render('@HumanResources/rewards/index', [
            'active' => 'employees',
            'title' => 'Danh Sách Phiếu Khen Thưởng',
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
        $rewardDate = trim((string) ($_POST['reward_date'] ?? ''));
        $rewardType = trim((string) ($_POST['reward_type'] ?? ''));
        $employeeNames = array_filter(array_column($store->get('employees'), 'name'));

        if (! in_array($employeeName, $employeeNames, true)) {
            $_SESSION['flash_error'] = 'Người được khen thưởng phải được chọn từ Danh Sách Nhân Viên.';
            redirect('rewards');
        }
        if ($rewardDate === '' || $rewardType === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập thời gian và loại khen thưởng.';
            redirect('rewards');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'employee_name' => $employeeName,
            'reward_date' => $rewardDate,
            'reward_type' => $rewardType,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'amount' => max(0, (float) ($_POST['amount'] ?? 0)),
            'decision_number' => trim((string) ($_POST['decision_number'] ?? '')),
        ];

        $items = $store->get('rewards');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }
        $store->put('rewards', $items);

        add_notification(
            $store,
            'Phiếu khen thưởng',
            ($isUpdate ? 'Đã cập nhật phiếu khen thưởng của ' : 'Đã lập phiếu khen thưởng cho ') . $employeeName . '.',
            '?route=rewards',
            'success'
        );
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật phiếu khen thưởng.' : 'Đã lập phiếu khen thưởng mới.';
        redirect('rewards');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('rewards'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));
        $store->put('rewards', $items);
        if ($deleted !== null) {
            add_notification($store, 'Phiếu khen thưởng', 'Đã xóa phiếu khen thưởng của ' . ($deleted['employee_name'] ?? '') . '.', '?route=rewards', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa phiếu khen thưởng.';
        redirect('rewards');
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['rewards'])) {
            return;
        }
        $data['rewards'] = self::sampleData();
        $store->save($data);
    }

    public static function sampleData(): array
    {
        return [
            ['id' => 'kt01', 'employee_name' => 'Minh Nguyen', 'reward_date' => '2026-06-20', 'reward_type' => 'Khen thưởng nhân viên xuất sắc', 'description' => 'Vượt 125% chỉ tiêu doanh số tháng.', 'amount' => 3000000, 'decision_number' => 'QDKT-2026-001'],
            ['id' => 'kt02', 'employee_name' => 'Ha Pham', 'reward_date' => '2026-06-15', 'reward_type' => 'Đóng góp ý tưởng sáng tạo', 'description' => 'Đề xuất quy trình kiểm kê giúp giảm thời gian xử lý.', 'amount' => 1500000, 'decision_number' => 'QDKT-2026-002'],
            ['id' => 'kt03', 'employee_name' => 'Quang Le', 'reward_date' => '2026-06-10', 'reward_type' => 'Hoàn thành xuất sắc dự án', 'description' => 'Hoàn thành module báo cáo trước thời hạn.', 'amount' => 2500000, 'decision_number' => 'QDKT-2026-003'],
            ['id' => 'kt04', 'employee_name' => 'Minh Nguyen', 'reward_date' => '2026-05-25', 'reward_type' => 'Khen thưởng nhân viên xuất sắc', 'description' => 'Duy trì chất lượng chăm sóc khách hàng tốt.', 'amount' => 2000000, 'decision_number' => 'QDKT-2026-004'],
            ['id' => 'kt05', 'employee_name' => 'Ha Pham', 'reward_date' => '2026-05-12', 'reward_type' => 'Đóng góp ý tưởng sáng tạo', 'description' => 'Cải tiến cách bố trí hàng hóa trong kho.', 'amount' => 1000000, 'decision_number' => 'QDKT-2026-005'],
            ['id' => 'kt06', 'employee_name' => 'Quang Le', 'reward_date' => '2026-04-30', 'reward_type' => 'Hỗ trợ đồng đội', 'description' => 'Hỗ trợ đào tạo người dùng hệ thống mới.', 'amount' => 1000000, 'decision_number' => 'QDKT-2026-006'],
        ];
    }
}
