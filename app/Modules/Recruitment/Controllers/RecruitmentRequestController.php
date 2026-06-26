<?php

declare(strict_types=1);

final class RecruitmentRequestController
{
    private const APPROVALS = [
        'approved' => 'Chấp nhận',
        'rejected' => 'Không chấp nhận',
        'pending' => 'Chờ duyệt',
    ];

    private const SORTABLE = ['request_no', 'request_date', 'cost', 'status', 'approver', 'candidate_total', 'candidate_passed'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store, $query);

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'request_date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $left = $sort === 'cost' || str_starts_with($sort, 'candidate_') ? (float) ($a[$sort] ?? 0) : (string) ($a[$sort] ?? '');
            $right = $sort === 'cost' || str_starts_with($sort, 'candidate_') ? (float) ($b[$sort] ?? 0) : (string) ($b[$sort] ?? '');
            $result = is_float($left) || is_float($right) ? ($left <=> $right) : strnatcasecmp((string) $left, (string) $right);
            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 10);
        $perPage = in_array($requested, $allowed, true) ? $requested : 10;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Recruitment/requests/index', [
            'active' => 'recruitments',
            'title' => 'Phiếu Yêu Cầu Tuyển Dụng',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'approvals' => self::APPROVALS,
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
        $requestDate = trim((string) ($_POST['request_date'] ?? ''));
        $status = array_key_exists($_POST['status'] ?? '', self::APPROVALS) ? (string) $_POST['status'] : 'pending';
        if ($requestDate === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập thời gian tuyển dụng.';
            redirect('recruitment-requests');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'request_no' => trim((string) ($_POST['request_no'] ?? '')),
            'request_date' => $requestDate,
            'cost' => max(0, (float) ($_POST['cost'] ?? 0)),
            'status' => $status,
            'approver' => trim((string) ($_POST['approver'] ?? '')),
            'candidate_total' => max(0, (int) ($_POST['candidate_total'] ?? 0)),
            'candidate_passed' => max(0, (int) ($_POST['candidate_passed'] ?? 0)),
            'news_title' => trim((string) ($_POST['news_title'] ?? '')),
            'position' => trim((string) ($_POST['position'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];
        if ($payload['request_no'] === '') {
            $payload['request_no'] = (string) (count($store->get('recruitment_requests')) + 1);
        }
        if ($payload['candidate_passed'] > $payload['candidate_total']) {
            $payload['candidate_passed'] = $payload['candidate_total'];
        }

        $items = $store->get('recruitment_requests');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }
        $store->put('recruitment_requests', $items);

        add_notification(
            $store,
            'Phiếu tuyển dụng',
            ($isUpdate ? 'Đã cập nhật phiếu tuyển dụng #' : 'Đã tạo phiếu tuyển dụng #') . $payload['request_no'] . '.',
            '?route=recruitment-requests',
            $isUpdate ? 'info' : 'success'
        );
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật phiếu tuyển dụng.' : 'Đã tạo phiếu tuyển dụng mới.';
        redirect('recruitment-requests');
    }

    public function approval(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $status = array_key_exists($_POST['status'] ?? '', self::APPROVALS) ? (string) $_POST['status'] : 'pending';
        $updated = null;
        $items = array_map(function (array $item) use ($id, $status, &$updated): array {
            if (($item['id'] ?? '') === $id) {
                $item['status'] = $status;
                $updated = $item;
            }
            return $item;
        }, $store->get('recruitment_requests'));

        $store->put('recruitment_requests', $items);
        if ($updated !== null) {
            add_notification($store, 'Phiếu tuyển dụng', 'Đã cập nhật phê duyệt phiếu #' . ($updated['request_no'] ?? '') . '.', '?route=recruitment-requests', 'info');
        }
        $_SESSION['flash_success'] = 'Đã cập nhật trạng thái phiếu tuyển dụng.';
        redirect('recruitment-requests');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('recruitment_requests'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));
        $store->put('recruitment_requests', $items);

        if ($deleted !== null) {
            add_notification($store, 'Phiếu tuyển dụng', 'Đã xóa phiếu tuyển dụng #' . ($deleted['request_no'] ?? '') . '.', '?route=recruitment-requests', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa phiếu tuyển dụng.';
        redirect('recruitment-requests');
    }

    private function filtered(DataStore $store, string $query): array
    {
        return array_values(array_filter($store->get('recruitment_requests'), function (array $item) use ($query): bool {
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
        if (array_key_exists('recruitment_requests', $data)) {
            return;
        }

        $data['recruitment_requests'] = [
            ['id' => 'rr01', 'request_no' => '1', 'request_date' => '2020-07-20', 'cost' => 200000, 'status' => 'approved', 'approver' => '', 'candidate_total' => 4, 'candidate_passed' => 0, 'news_title' => 'Tuyển nhân sự kinh doanh', 'position' => 'Nhân viên kinh doanh', 'description' => 'Cần bổ sung nhân sự kinh doanh cho khu vực mới.'],
            ['id' => 'rr02', 'request_no' => '2', 'request_date' => '2020-10-11', 'cost' => 1000000, 'status' => 'rejected', 'approver' => '', 'candidate_total' => 0, 'candidate_passed' => 0, 'news_title' => 'Tuyển kế toán nội bộ', 'position' => 'Kế toán', 'description' => 'Phiếu chưa đủ ngân sách nên không được duyệt.'],
            ['id' => 'rr03', 'request_no' => '3', 'request_date' => '2020-09-03', 'cost' => 1000000, 'status' => 'approved', 'approver' => 'bData co.,ltd', 'candidate_total' => 0, 'candidate_passed' => 0, 'news_title' => 'Tuyển lập trình viên', 'position' => 'Developer', 'description' => 'Tuyển bổ sung lập trình viên cho dự án ERP.'],
            ['id' => 'rr04', 'request_no' => '4', 'request_date' => '2021-11-11', 'cost' => 1000000, 'status' => 'rejected', 'approver' => '', 'candidate_total' => 0, 'candidate_passed' => 0, 'news_title' => 'Tuyển hỗ trợ vận hành', 'position' => 'Operations', 'description' => 'Nhu cầu tạm hoãn.'],
            ['id' => 'rr05', 'request_no' => '5', 'request_date' => '2021-01-12', 'cost' => 500000, 'status' => 'approved', 'approver' => '', 'candidate_total' => 3, 'candidate_passed' => 0, 'news_title' => 'Tuyển chăm sóc khách hàng', 'position' => 'CSKH', 'description' => 'Tuyển đội chăm sóc khách hàng ca ngày.'],
            ['id' => 'rr06', 'request_no' => '6', 'request_date' => '2023-09-23', 'cost' => 100000, 'status' => 'approved', 'approver' => 'bData co.,ltd', 'candidate_total' => 1, 'candidate_passed' => 0, 'news_title' => 'Tuyển thực tập sinh', 'position' => 'Thực tập sinh', 'description' => 'Thực tập sinh hỗ trợ nhập liệu.'],
            ['id' => 'rr07', 'request_no' => '7', 'request_date' => '2024-08-01', 'cost' => 20000, 'status' => 'approved', 'approver' => 'bData co.,ltd', 'candidate_total' => 0, 'candidate_passed' => 0, 'news_title' => 'Tuyển admin', 'position' => 'Admin', 'description' => 'Bổ sung admin văn phòng.'],
            ['id' => 'rr08', 'request_no' => '8', 'request_date' => '2024-08-01', 'cost' => 20000, 'status' => 'approved', 'approver' => 'bData co.,ltd', 'candidate_total' => 1, 'candidate_passed' => 0, 'news_title' => 'Tuyển kho vận', 'position' => 'Kho vận', 'description' => 'Nhân viên kho bán hàng.'],
            ['id' => 'rr09', 'request_no' => '9', 'request_date' => '2024-08-03', 'cost' => 20000, 'status' => 'pending', 'approver' => '', 'candidate_total' => 0, 'candidate_passed' => 0, 'news_title' => 'Tuyển thiết kế', 'position' => 'Designer', 'description' => 'Đang chờ duyệt ngân sách.'],
        ];
        $store->save($data);
    }
}
