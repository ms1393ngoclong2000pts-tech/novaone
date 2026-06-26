<?php

declare(strict_types=1);

final class WorkItemController
{
    private const STATUSES = [
        'pending' => 'Chưa hoàn thành',
        'in_progress' => 'Đang tiến hành',
        'completed' => 'Hoàn thành',
    ];

    private const PRIORITIES = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    private const SORTABLE = ['title', 'hours', 'assignee', 'start_date', 'completion_date', 'status', 'progress'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->migrateData($store);

        $project = trim((string) ($_GET['project'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $statusFilterApplied = isset($_GET['status_filter']);
        $selectedStatuses = $statusFilterApplied
            ? array_values(array_intersect(array_keys(self::STATUSES), (array) ($_GET['statuses'] ?? [])))
            : ['pending', 'in_progress'];

        $allItems = array_map([$this, 'normalize'], $store->get('tasks'));
        $projects = array_values(array_unique(array_filter(array_column($allItems, 'project'))));
        $categories = array_values(array_unique(array_filter(array_column($allItems, 'category'))));
        natcasesort($projects);
        natcasesort($categories);

        $items = array_values(array_filter($allItems, function (array $item) use ($project, $category, $query, $selectedStatuses): bool {
            if ($project !== '' && $item['project'] !== $project) {
                return false;
            }
            if ($category !== '' && $item['category'] !== $category) {
                return false;
            }
            if (! in_array($item['status'], $selectedStatuses, true)) {
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

        View::render('@Work/work_items/index', [
            'active' => 'tasks',
            'title' => 'Danh Sách Công Việc',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'projectRecords' => $store->get('projects'),
            'projects' => array_values($projects),
            'categories' => array_values($categories),
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'selectedStatuses' => $selectedStatuses,
            'project' => $project,
            'category' => $category,
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
        $title = trim((string) ($_POST['title'] ?? ''));
        $assignee = trim((string) ($_POST['assignee'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $completionDate = trim((string) ($_POST['completion_date'] ?? ''));
        $status = array_key_exists($_POST['status'] ?? '', self::STATUSES) ? (string) $_POST['status'] : 'pending';
        $priority = array_key_exists($_POST['priority'] ?? '', self::PRIORITIES) ? (string) $_POST['priority'] : 'normal';

        if ($title === '' || $startDate === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên công việc và ngày bắt đầu.';
            redirect('work-items');
        }
        $employeeNames = array_filter(array_column($store->get('employees'), 'name'));
        if ($assignee !== '' && ! in_array($assignee, $employeeNames, true)) {
            $_SESSION['flash_error'] = 'Người thực hiện phải được chọn từ Danh Sách Nhân Viên.';
            redirect('work-items');
        }
        if ($completionDate !== '' && $completionDate < $startDate) {
            $_SESSION['flash_error'] = 'Ngày hoàn thành phải sau hoặc bằng ngày bắt đầu.';
            redirect('work-items');
        }
        if ($status === 'completed' && $completionDate === '') {
            $completionDate = date('Y-m-d');
        }

        $progress = min(100, max(0, (int) ($_POST['progress'] ?? 0)));
        if ($status === 'completed') {
            $progress = 100;
        }
        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'title' => $title,
            'project' => trim((string) ($_POST['project'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? '')),
            'hours' => max(0, (float) ($_POST['hours'] ?? 0)),
            'assignee' => $assignee,
            'start_date' => $startDate,
            'completion_date' => $completionDate,
            'deadline' => $completionDate,
            'status' => $status,
            'progress' => $progress,
            'priority' => $priority,
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];

        $items = $store->get('tasks');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }
        $store->put('tasks', $items);

        add_notification($store, 'Công việc', ($isUpdate ? 'Đã cập nhật công việc ' : 'Đã tạo công việc ') . $title . '.', '?route=work-items', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật công việc.' : 'Đã tạo công việc mới.';
        redirect('work-items');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('tasks'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));
        $store->put('tasks', $items);
        if ($deleted !== null) {
            add_notification($store, 'Công việc', 'Đã xóa công việc ' . ($deleted['title'] ?? '') . '.', '?route=work-items', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa công việc.';
        redirect('work-items');
    }

    private function normalize(array $item): array
    {
        $status = array_key_exists($item['status'] ?? '', self::STATUSES) ? (string) $item['status'] : 'pending';
        return [
            ...$item,
            'title' => (string) ($item['title'] ?? ''),
            'project' => (string) ($item['project'] ?? ''),
            'category' => (string) ($item['category'] ?? 'Công việc chung'),
            'hours' => (float) ($item['hours'] ?? 0),
            'assignee' => (string) ($item['assignee'] ?? ''),
            'start_date' => (string) ($item['start_date'] ?? date('Y-m-d')),
            'completion_date' => (string) ($item['completion_date'] ?? $item['deadline'] ?? ''),
            'deadline' => (string) ($item['deadline'] ?? $item['completion_date'] ?? ''),
            'status' => $status,
            'progress' => (int) ($item['progress'] ?? ($status === 'completed' ? 100 : 0)),
            'priority' => (string) ($item['priority'] ?? 'normal'),
            'description' => (string) ($item['description'] ?? ''),
        ];
    }

    private function migrateData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['_migrations']['work_items_v1'])) {
            return;
        }

        $items = array_map([$this, 'normalize'], $data['tasks'] ?? []);
        $items = [...$items, ...self::sampleData()];
        $data['tasks'] = $items;
        $data['_migrations']['work_items_v1'] = true;
        $store->save($data);
    }

    public static function sampleData(): array
    {
        return [
            ['id' => 'wi04', 'title' => 'Hoàn thiện giao diện quản lý dự án', 'project' => 'BDATA-AI', 'category' => 'Phát triển', 'hours' => 24, 'assignee' => 'Quang Le', 'start_date' => '2026-06-17', 'completion_date' => '2026-06-25', 'deadline' => '2026-06-25', 'status' => 'in_progress', 'progress' => 65, 'priority' => 'high', 'description' => 'Hoàn thiện giao diện và kiểm thử responsive.'],
            ['id' => 'wi05', 'title' => 'Chuẩn bị dữ liệu khách hàng', 'project' => 'MPRO', 'category' => 'Dữ liệu', 'hours' => 12, 'assignee' => 'Minh Nguyen', 'start_date' => '2026-06-18', 'completion_date' => '2026-06-24', 'deadline' => '2026-06-24', 'status' => 'pending', 'progress' => 20, 'priority' => 'normal', 'description' => 'Làm sạch dữ liệu trước khi import.'],
            ['id' => 'wi06', 'title' => 'Kiểm thử quy trình nhập kho', 'project' => 'Green Pin', 'category' => 'Kiểm thử', 'hours' => 16, 'assignee' => 'Ha Pham', 'start_date' => '2026-06-16', 'completion_date' => '2026-06-22', 'deadline' => '2026-06-22', 'status' => 'completed', 'progress' => 100, 'priority' => 'high', 'description' => 'Kiểm thử và lập biên bản nghiệm thu.'],
            ['id' => 'wi07', 'title' => 'Thiết kế báo cáo doanh thu', 'project' => 'Happy C', 'category' => 'Báo cáo', 'hours' => 20, 'assignee' => 'Minh Nguyen', 'start_date' => '2026-06-20', 'completion_date' => '2026-06-30', 'deadline' => '2026-06-30', 'status' => 'in_progress', 'progress' => 45, 'priority' => 'normal', 'description' => 'Xây dựng biểu đồ và bộ lọc báo cáo.'],
            ['id' => 'wi08', 'title' => 'Tài liệu hướng dẫn người dùng', 'project' => 'Home 3DS', 'category' => 'Tài liệu', 'hours' => 8, 'assignee' => 'Quang Le', 'start_date' => '2026-06-21', 'completion_date' => '2026-06-28', 'deadline' => '2026-06-28', 'status' => 'pending', 'progress' => 10, 'priority' => 'low', 'description' => 'Biên soạn hướng dẫn sử dụng cho khách hàng.'],
        ];
    }
}
