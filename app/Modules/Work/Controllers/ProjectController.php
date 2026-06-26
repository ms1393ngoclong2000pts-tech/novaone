<?php

declare(strict_types=1);

final class ProjectController
{
    private const STATUSES = [
        'open' => 'Mở',
        'in_progress' => 'Đang thực hiện',
        'completed' => 'Hoàn thành',
        'on_hold' => 'Tạm dừng',
        'canceled' => 'Đã hủy',
    ];

    private const COLUMNS = [
        'name', 'category', 'company', 'start_date', 'end_date',
        'status', 'manager', 'budget', 'description',
    ];

    private const SORTABLE = ['name', 'category', 'company', 'start_date', 'end_date', 'status'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $status = array_key_exists($_GET['status'] ?? '', self::STATUSES) ? (string) $_GET['status'] : '';
        $endFrom = trim((string) ($_GET['end_from'] ?? ''));
        $endTo = trim((string) ($_GET['end_to'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store->get('projects'), $status, $endFrom, $endTo, $query);

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'start_date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
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

        View::render('@Work/projects/index', [
            'active' => 'tasks',
            'title' => 'Danh Sách Dự Án',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'statuses' => self::STATUSES,
            'status' => $status,
            'endFrom' => $endFrom,
            'endTo' => $endTo,
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
        $name = trim((string) ($_POST['name'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $status = array_key_exists($_POST['status'] ?? '', self::STATUSES) ? (string) $_POST['status'] : 'open';

        if ($name === '' || $startDate === '' || $endDate === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên dự án, ngày bắt đầu và ngày kết thúc.';
            redirect('projects');
        }
        if ($endDate < $startDate) {
            $_SESSION['flash_error'] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
            redirect('projects');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'name' => $name,
            'category' => trim((string) ($_POST['category'] ?? '')),
            'company' => trim((string) ($_POST['company'] ?? '')),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'manager' => trim((string) ($_POST['manager'] ?? '')),
            'budget' => max(0, (float) ($_POST['budget'] ?? 0)),
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];

        $items = $store->get('projects');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }
        $store->put('projects', $items);

        add_notification(
            $store,
            'Dự án',
            ($isUpdate ? 'Đã cập nhật dự án ' : 'Đã tạo dự án ') . $name . '.',
            '?route=projects',
            $isUpdate ? 'info' : 'success'
        );
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật dự án.' : 'Đã tạo dự án mới.';
        redirect('projects');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('projects'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));
        $store->put('projects', $items);
        if ($deleted !== null) {
            add_notification($store, 'Dự án', 'Đã xóa dự án ' . ($deleted['name'] ?? '') . '.', '?route=projects', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa dự án.';
        redirect('projects');
    }

    public function template(): never
    {
        require_auth();
        $this->downloadTemplate();
    }

    public function import(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $file = $_FILES['project_file'] ?? null;
        if (! is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Vui lòng chọn file Excel hoặc CSV.';
            redirect('projects');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024 || ! is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            $_SESSION['flash_error'] = 'File tải lên không hợp lệ hoặc vượt quá 5MB.';
            redirect('projects');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            $_SESSION['flash_error'] = 'Chỉ hỗ trợ file Excel XLSX hoặc CSV.';
            redirect('projects');
        }

        try {
            $rows = $extension === 'xlsx'
                ? XlsxReader::rows((string) $file['tmp_name'])
                : $this->csvRows((string) $file['tmp_name']);
        } catch (RuntimeException $exception) {
            $_SESSION['flash_error'] = $exception->getMessage();
            redirect('projects');
        }

        $header = array_shift($rows);
        $header = is_array($header)
            ? array_map(fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"), $header)
            : [];
        if (array_slice($header, 0, count(self::COLUMNS)) !== self::COLUMNS) {
            $_SESSION['flash_error'] = 'Cấu trúc cột không đúng file mẫu dự án.';
            redirect('projects');
        }

        $items = $store->get('projects');
        $count = 0;
        foreach ($rows as $row) {
            $row = array_pad($row, count(self::COLUMNS), '');
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $values = array_combine(self::COLUMNS, array_slice($row, 0, count(self::COLUMNS)));
            $name = trim((string) ($values['name'] ?? ''));
            $startDate = trim((string) ($values['start_date'] ?? ''));
            $endDate = trim((string) ($values['end_date'] ?? ''));
            if ($name === '' || $startDate === '' || $endDate === '' || $endDate < $startDate) {
                continue;
            }
            $projectStatus = array_key_exists($values['status'] ?? '', self::STATUSES) ? (string) $values['status'] : 'open';
            $items[] = [
                'id' => uid(), 'name' => $name, 'category' => trim((string) $values['category']),
                'company' => trim((string) $values['company']), 'start_date' => $startDate, 'end_date' => $endDate,
                'status' => $projectStatus, 'manager' => trim((string) $values['manager']),
                'budget' => max(0, (float) $values['budget']), 'description' => trim((string) $values['description']),
            ];
            $count++;
        }
        $store->put('projects', $items);
        add_notification($store, 'Dự án', "Đã nhập $count dự án từ file " . strtoupper($extension) . '.', '?route=projects', 'success');
        $_SESSION['flash_success'] = "Đã nhập thành công $count dự án.";
        redirect('projects');
    }

    private function filtered(array $items, string $status, string $endFrom, string $endTo, string $query): array
    {
        return array_values(array_filter($items, function (array $item) use ($status, $endFrom, $endTo, $query): bool {
            if ($status !== '' && ($item['status'] ?? '') !== $status) {
                return false;
            }
            $endDate = (string) ($item['end_date'] ?? '');
            if ($endFrom !== '' && $endDate < $endFrom) {
                return false;
            }
            if ($endTo !== '' && $endDate > $endTo) {
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

    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Không thể đọc file CSV.');
        }
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function downloadTemplate(): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="file-mau-du-an.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        fputcsv($output, self::COLUMNS);
        fputcsv($output, ['Dự án mẫu', 'Công nghệ', 'Công ty ABC', '2026-07-01', '2026-12-31', 'open', 'Minh Nguyen', '500000000', 'Mô tả dự án']);
        fclose($output);
        exit;
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (array_key_exists('projects', $data)) {
            return;
        }
        $data['projects'] = self::sampleData();
        $store->save($data);
    }

    public static function sampleData(): array
    {
        return [
            ['id' => 'pr01', 'name' => 'Takashimaya', 'category' => 'Triển khai hệ thống', 'company' => 'Công ty TNHH Công nghệ Metatek', 'start_date' => '2026-06-22', 'end_date' => '2026-08-22', 'status' => 'open', 'manager' => 'Minh Nguyen', 'budget' => 850000000, 'description' => 'Triển khai nền tảng quản trị vận hành.'],
            ['id' => 'pr02', 'name' => 'MPRO', 'category' => 'Phần mềm doanh nghiệp', 'company' => 'Công ty TNHH Thương mại & Kỹ thuật V.M.S', 'start_date' => '2026-06-18', 'end_date' => '2026-07-31', 'status' => 'in_progress', 'manager' => 'Quang Le', 'budget' => 620000000, 'description' => 'Phát triển hệ thống quản lý bán hàng.'],
            ['id' => 'pr03', 'name' => 'Sàn Nông Sản Quốc Tế', 'category' => 'Thương mại điện tử', 'company' => 'Công ty Cổ phần Health Care Center APP', 'start_date' => '2026-06-08', 'end_date' => '2026-08-29', 'status' => 'open', 'manager' => 'Ha Pham', 'budget' => 1200000000, 'description' => 'Xây dựng sàn kết nối nông sản.'],
            ['id' => 'pr04', 'name' => 'CMD ROYAL', 'category' => 'Dữ liệu', 'company' => 'Công ty TNHH MTV khai thác dữ liệu số bData', 'start_date' => '2026-06-16', 'end_date' => '2026-06-30', 'status' => 'completed', 'manager' => 'Quang Le', 'budget' => 320000000, 'description' => 'Chuẩn hóa dữ liệu khách hàng.'],
            ['id' => 'pr05', 'name' => 'Green Pin', 'category' => 'Sản xuất', 'company' => 'Công ty TNHH Thương mại dịch vụ sản xuất P2D', 'start_date' => '2026-04-01', 'end_date' => '2026-06-30', 'status' => 'on_hold', 'manager' => 'Ha Pham', 'budget' => 740000000, 'description' => 'Quản lý chuỗi cung ứng sản xuất.'],
            ['id' => 'pr06', 'name' => 'Home 3DS', 'category' => 'Thiết kế', 'company' => 'Công ty TNHH Thiết kế và xây dựng Home Design', 'start_date' => '2026-06-16', 'end_date' => '2026-09-15', 'status' => 'open', 'manager' => 'Minh Nguyen', 'budget' => 480000000, 'description' => 'Nền tảng quản lý thiết kế 3D.'],
            ['id' => 'pr07', 'name' => 'Happy C', 'category' => 'Marketing', 'company' => 'Công ty TNHH Happy Creative', 'start_date' => '2026-06-01', 'end_date' => '2026-12-31', 'status' => 'in_progress', 'manager' => 'Minh Nguyen', 'budget' => 900000000, 'description' => 'Hệ thống quản lý chiến dịch marketing.'],
            ['id' => 'pr08', 'name' => 'BDATA-AI', 'category' => 'Trí tuệ nhân tạo', 'company' => 'Công ty TNHH MTV khai thác dữ liệu số bData', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'in_progress', 'manager' => 'Quang Le', 'budget' => 2500000000, 'description' => 'Nền tảng AI phân tích dữ liệu doanh nghiệp.'],
        ];
    }
}
