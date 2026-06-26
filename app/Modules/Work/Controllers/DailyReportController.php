<?php

declare(strict_types=1);

final class DailyReportController
{
    private const PROJECT_STATUSES = [
        'open' => 'Mở', 'in_progress' => 'Đang thực hiện', 'completed' => 'Hoàn thành',
        'on_hold' => 'Tạm dừng', 'canceled' => 'Đã hủy',
    ];
    private const SORTABLE = ['project', 'category', 'employee', 'details', 'hours', 'report_date'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $fromDate = trim((string) ($_GET['from_date'] ?? ''));
        $toDate = trim((string) ($_GET['to_date'] ?? ''));
        $employee = trim((string) ($_GET['employee'] ?? ''));
        $projectStatus = array_key_exists($_GET['project_status'] ?? '', self::PROJECT_STATUSES) ? (string) $_GET['project_status'] : '';
        $project = trim((string) ($_GET['project'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));

        $projects = $store->get('projects');
        $allowedProjects = $projectStatus === ''
            ? null
            : array_column(array_filter($projects, fn (array $item): bool => ($item['status'] ?? '') === $projectStatus), 'name');
        $items = array_values(array_filter($store->get('daily_reports'), function (array $item) use ($fromDate, $toDate, $employee, $project, $query, $allowedProjects): bool {
            $date = (string) ($item['report_date'] ?? '');
            if ($fromDate !== '' && $date < $fromDate) return false;
            if ($toDate !== '' && $date > $toDate) return false;
            if ($employee !== '' && ($item['employee'] ?? '') !== $employee) return false;
            if ($project !== '' && ($item['project'] ?? '') !== $project) return false;
            if ($allowedProjects !== null && ! in_array($item['project'] ?? '', $allowedProjects, true)) return false;
            if ($query === '') return true;
            $haystack = implode(' ', array_map('strval', $item));
            return function_exists('mb_strtolower')
                ? str_contains(mb_strtolower($haystack, 'UTF-8'), mb_strtolower($query, 'UTF-8'))
                : str_contains(strtolower($haystack), strtolower($query));
        }));

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'report_date';
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
        $categories = array_values(array_unique(array_filter(array_column($store->get('tasks'), 'category'))));
        natcasesort($categories);

        View::render('@Work/daily_reports/index', [
            'active' => 'tasks', 'title' => 'Báo Cáo Hàng Ngày',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'), 'projects' => $projects,
            'categories' => array_values($categories), 'projectStatuses' => self::PROJECT_STATUSES,
            'fromDate' => $fromDate, 'toDate' => $toDate, 'employee' => $employee,
            'projectStatus' => $projectStatus, 'project' => $project, 'query' => $query,
            'sort' => $sort, 'direction' => $direction, 'perPage' => $perPage,
            'total' => $total, 'page' => $page, 'pages' => $pages,
        ]);
    }

    public function saveBatch(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $projects = (array) ($_POST['project'] ?? []);
        $employees = (array) ($_POST['employee'] ?? []);
        $categories = (array) ($_POST['category'] ?? []);
        $hours = (array) ($_POST['hours'] ?? []);
        $dates = (array) ($_POST['report_date'] ?? []);
        $details = (array) ($_POST['details'] ?? []);
        $validEmployees = array_filter(array_column($store->get('employees'), 'name'));
        $validProjects = array_filter(array_column($store->get('projects'), 'name'));
        $items = $store->get('daily_reports');
        $count = 0;

        foreach ($projects as $index => $project) {
            $project = trim((string) $project);
            $employee = trim((string) ($employees[$index] ?? ''));
            $date = trim((string) ($dates[$index] ?? ''));
            $detail = trim((string) ($details[$index] ?? ''));
            if (! in_array($project, $validProjects, true) || ! in_array($employee, $validEmployees, true) || $date === '' || $detail === '') {
                continue;
            }
            $items[] = [
                'id' => uid(), 'project' => $project,
                'category' => trim((string) ($categories[$index] ?? '')),
                'employee' => $employee, 'details' => $detail,
                'hours' => max(0.5, (float) ($hours[$index] ?? 0.5)), 'report_date' => $date,
            ];
            $count++;
        }

        if ($count === 0) {
            $_SESSION['flash_error'] = 'Chưa có dòng báo cáo hợp lệ để lưu.';
            redirect('daily-reports');
        }
        $store->put('daily_reports', $items);
        add_notification($store, 'Báo cáo hàng ngày', "Đã thêm $count dòng báo cáo công việc.", '?route=daily-reports', 'success');
        $_SESSION['flash_success'] = "Đã lưu $count dòng báo cáo.";
        redirect('daily-reports');
    }

    public function update(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        $id = trim((string) ($_POST['id'] ?? ''));
        $project = trim((string) ($_POST['project'] ?? ''));
        $employee = trim((string) ($_POST['employee'] ?? ''));
        $date = trim((string) ($_POST['report_date'] ?? ''));
        $details = trim((string) ($_POST['details'] ?? ''));
        if ($id === '' || $date === '' || $details === '' || ! in_array($project, array_column($store->get('projects'), 'name'), true) || ! in_array($employee, array_column($store->get('employees'), 'name'), true)) {
            $_SESSION['flash_error'] = 'Thông tin báo cáo chưa hợp lệ.';
            redirect('daily-reports');
        }
        $payload = ['id' => $id, 'project' => $project, 'category' => trim((string) ($_POST['category'] ?? '')), 'employee' => $employee, 'details' => $details, 'hours' => max(0.5, (float) ($_POST['hours'] ?? 0.5)), 'report_date' => $date];
        $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $store->get('daily_reports'));
        $store->put('daily_reports', $items);
        add_notification($store, 'Báo cáo hàng ngày', 'Đã cập nhật báo cáo của ' . $employee . '.', '?route=daily-reports', 'info');
        $_SESSION['flash_success'] = 'Đã cập nhật báo cáo.';
        redirect('daily-reports');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        $id = trim((string) ($_POST['id'] ?? ''));
        $items = array_values(array_filter($store->get('daily_reports'), fn (array $item): bool => ($item['id'] ?? '') !== $id));
        $store->put('daily_reports', $items);
        add_notification($store, 'Báo cáo hàng ngày', 'Đã xóa một dòng báo cáo.', '?route=daily-reports', 'danger');
        $_SESSION['flash_success'] = 'Đã xóa báo cáo.';
        redirect('daily-reports');
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (array_key_exists('daily_reports', $data)) return;
        $data['daily_reports'] = [
            ['id' => 'dr01', 'project' => 'BDATA-AI', 'category' => 'Phát triển', 'employee' => 'Quang Le', 'details' => 'Hoàn thiện API phân tích dữ liệu và viết unit test.', 'hours' => 7.5, 'report_date' => '2026-06-21'],
            ['id' => 'dr02', 'project' => 'MPRO', 'category' => 'Dữ liệu', 'employee' => 'Minh Nguyen', 'details' => 'Chuẩn hóa danh sách khách hàng trước khi import.', 'hours' => 6, 'report_date' => '2026-06-21'],
            ['id' => 'dr03', 'project' => 'Green Pin', 'category' => 'Kiểm thử', 'employee' => 'Ha Pham', 'details' => 'Kiểm thử quy trình nhập kho và lập biên bản lỗi.', 'hours' => 8, 'report_date' => '2026-06-20'],
            ['id' => 'dr04', 'project' => 'Happy C', 'category' => 'Báo cáo', 'employee' => 'Minh Nguyen', 'details' => 'Thiết kế biểu đồ doanh thu theo chiến dịch.', 'hours' => 5.5, 'report_date' => '2026-06-20'],
            ['id' => 'dr05', 'project' => 'Home 3DS', 'category' => 'Tài liệu', 'employee' => 'Quang Le', 'details' => 'Soạn hướng dẫn sử dụng chức năng thiết kế.', 'hours' => 4, 'report_date' => '2026-06-19'],
        ];
        $store->save($data);
    }
}
