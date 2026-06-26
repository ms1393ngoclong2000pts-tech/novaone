<?php

declare(strict_types=1);

final class EmployeeController
{
    private const COLUMNS = [
        'attendance_code', 'employee_code', 'name', 'gender', 'email',
        'position', 'department', 'contract', 'status',
    ];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $items = array_map([$this, 'normalize'], $store->get('employees'));
        $positions = $this->uniqueValues($items, 'position');
        $departments = $this->uniqueValues($items, 'department');
        $filters = array_map(
            fn (string $key): string => trim((string) ($_GET[$key] ?? '')),
            array_combine(self::COLUMNS, self::COLUMNS)
        );
        $filters['q'] = trim((string) ($_GET['q'] ?? ''));

        $items = array_values(array_filter($items, function (array $item) use ($filters): bool {
            foreach (self::COLUMNS as $column) {
                if ($column === 'status' && $filters[$column] !== '' && ($item[$column] ?? '') !== $filters[$column]) {
                    return false;
                }
                if ($column !== 'status' && $filters[$column] !== '' && ! $this->contains((string) ($item[$column] ?? ''), $filters[$column])) {
                    return false;
                }
            }

            return $filters['q'] === '' || $this->contains(implode(' ', array_map('strval', $item)), $filters['q']);
        }));

        $sort = in_array($_GET['sort'] ?? '', self::COLUMNS, true) ? (string) $_GET['sort'] : 'name';
        $direction = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));
            return $direction === 'desc' ? -$result : $result;
        });

        $perPage = 10;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        $items = array_slice($items, ($page - 1) * $perPage, $perPage);

        View::render('@HumanResources/employees/index', [
            'active' => 'employees',
            'title' => 'Danh Sách Nhân Viên',
            'items' => $items,
            'positions' => $positions,
            'departments' => $departments,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
            'total' => $total,
        ]);
    }

    public function show(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $id = trim((string) ($_GET['id'] ?? ''));
        $employee = null;

        foreach ($store->get('employees') as $item) {
            $item = $this->normalize($item);
            if ((string) ($item['id'] ?? '') === $id) {
                $employee = $item;
                break;
            }
        }

        if ($employee === null) {
            $_SESSION['flash_error'] = 'Không tìm thấy hồ sơ nhân sự.';
            redirect('employees');
        }

        $name = $employee['name'];
        $relatedByEmployee = fn (string $key): array => array_values(array_filter(
            $store->get($key),
            fn (array $item): bool => trim((string) ($item['employee_name'] ?? '')) === $name
        ));

        View::render('@HumanResources/employees/show', [
            'active' => 'employees',
            'title' => 'Hồ Sơ Nhân Sự',
            'employee' => $employee,
            'contracts' => $relatedByEmployee('contracts'),
            'socialInsurance' => $relatedByEmployee('social_insurance'),
            'requests' => $relatedByEmployee('request_forms'),
            'violations' => $relatedByEmployee('violations'),
            'rewards' => $relatedByEmployee('rewards'),
            'workItems' => array_values(array_filter(
                $store->get('work_items'),
                fn (array $item): bool => trim((string) ($item['assignee'] ?? '')) === $name
            )),
            'dailyReports' => array_values(array_filter(
                $store->get('daily_reports'),
                fn (array $item): bool => trim((string) ($item['employee'] ?? $item['employee_name'] ?? '')) === $name
            )),
        ]);
    }

    public function export(DataStore $store): never
    {
        require_auth();
        $this->download('danh-sach-nhan-su-' . date('Y-m-d') . '.csv', $store->get('employees'));
    }

    public function template(): never
    {
        require_auth();
        $this->download('file-mau-danh-sach-nhan-su.csv', []);
    }

    public function import(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $file = $_FILES['employee_file'] ?? null;
        if (! is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Vui lòng chọn file CSV cần tải lên.';
            redirect('employees');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024 || ! is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            $_SESSION['flash_error'] = 'File tải lên không hợp lệ hoặc vượt quá 5MB.';
            redirect('employees');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            $_SESSION['flash_error'] = 'File chưa đúng định dạng. Chỉ hỗ trợ CSV hoặc Excel XLSX.';
            redirect('employees');
        }

        try {
            $rows = $extension === 'xlsx'
                ? XlsxReader::rows((string) $file['tmp_name'])
                : $this->csvRows((string) $file['tmp_name']);
        } catch (RuntimeException $exception) {
            $_SESSION['flash_error'] = $exception->getMessage();
            redirect('employees');
        }

        $header = array_shift($rows);
        if (! is_array($header)) {
            $_SESSION['flash_error'] = 'File không có dữ liệu.';
            redirect('employees');
        }

        $header = array_map(fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"), $header);
        if (array_slice($header, 0, count(self::COLUMNS)) !== self::COLUMNS) {
            $_SESSION['flash_error'] = 'Cột dữ liệu không đúng file mẫu.';
            redirect('employees');
        }

        $items = $store->get('employees');
        $count = 0;
        foreach ($rows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $row = array_pad($row, count(self::COLUMNS), '');
            $employee = ['id' => uid()];
            foreach (self::COLUMNS as $index => $column) {
                $employee[$column] = trim((string) $row[$index]);
            }
            if ($employee['name'] === '') {
                continue;
            }
            $items[] = $employee;
            $count++;
        }

        $store->put('employees', $items);
        add_notification($store, 'Nhân sự', "Đã nhập $count nhân viên từ file " . strtoupper($extension) . '.', '?route=employees', 'success');
        $_SESSION['flash_success'] = "Đã nhập thành công $count nhân viên.";
        redirect('employees');
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

    private function normalize(array $item): array
    {
        $id = (string) ($item['id'] ?? uid());
        return [
            ...$item,
            'attendance_code' => (string) ($item['attendance_code'] ?? preg_replace('/\D+/', '', $id) ?: $id),
            'employee_code' => (string) ($item['employee_code'] ?? strtoupper($id)),
            'name' => (string) ($item['name'] ?? ''),
            'gender' => (string) ($item['gender'] ?? 'Nam'),
            'email' => (string) ($item['email'] ?? ''),
            'position' => (string) ($item['position'] ?? ''),
            'department' => (string) ($item['department'] ?? ''),
            'contract' => (string) ($item['contract'] ?? ''),
            'status' => (string) ($item['status'] ?? 'active'),
        ];
    }

    private function contains(string $haystack, string $needle): bool
    {
        $lower = fn (string $value): string => function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
        return str_contains($lower($haystack), $lower($needle));
    }

    private function uniqueValues(array $items, string $key): array
    {
        $values = array_values(array_unique(array_filter(array_column($items, $key))));
        natcasesort($values);
        return array_values($values);
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['employees'])) {
            return;
        }

        $data['employees'] = [
            ['id' => 'e1', 'attendance_code' => '32', 'employee_code' => '397375353', 'name' => 'Hồ Viết Nhân', 'gender' => 'Nam', 'email' => 'nhanhv@bdata.vn', 'position' => 'Nhân viên', 'department' => 'Lập Trình Viên', 'contract' => 'Chính thức', 'status' => 'active'],
            ['id' => 'e2', 'attendance_code' => '38', 'employee_code' => '782090953', 'name' => 'Lâm Quốc Tuấn', 'gender' => 'Nam', 'email' => 'tuanlq@bdata.vn', 'position' => 'Nhân viên', 'department' => 'Kỹ Thuật', 'contract' => 'Chính thức', 'status' => 'active'],
            ['id' => 'e3', 'attendance_code' => '56', 'employee_code' => '48658215', 'name' => 'Nguyễn Hữu Phương', 'gender' => 'Nam', 'email' => 'phuongnh@bdata.vn', 'position' => 'Nhân viên', 'department' => 'Kinh Doanh', 'contract' => 'Chính thức', 'status' => 'active'],
            ['id' => 'e4', 'attendance_code' => '67', 'employee_code' => '397983981', 'name' => 'Hoàng Trọng Tín', 'gender' => 'Nam', 'email' => 'tinht@bdata.vn', 'position' => 'Nhân viên', 'department' => 'BACK OFFICE', 'contract' => 'Chính thức', 'status' => 'active'],
            ['id' => 'e5', 'attendance_code' => '92', 'employee_code' => 'L3DMQ3', 'name' => 'Nguyễn Khoa Anh Kính', 'gender' => 'Nam', 'email' => 'kinhnka@bdata.vn', 'position' => 'Nhân viên', 'department' => 'Admin', 'contract' => 'Chính thức', 'status' => 'active'],
            ['id' => 'e6', 'attendance_code' => '100', 'employee_code' => 'EM2V6D', 'name' => 'Võ Thuận', 'gender' => 'Nam', 'email' => 'thuanv@bdata.vn', 'position' => 'Nhân viên', 'department' => 'Tuyển Dụng', 'contract' => 'Thử việc', 'status' => 'active'],
        ];
        $store->save($data);
    }

    private function download(string $filename, array $items): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        fputcsv($output, self::COLUMNS);
        foreach ($items as $item) {
            $item = $this->normalize($item);
            fputcsv($output, array_map(fn ($column) => $item[$column] ?? '', self::COLUMNS));
        }
        fclose($output);
        exit;
    }
}
