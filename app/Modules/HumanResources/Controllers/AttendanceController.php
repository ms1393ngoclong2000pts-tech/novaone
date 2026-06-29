<?php

declare(strict_types=1);

final class AttendanceController
{
    private const SHIFTS = [
        'morning' => 'Sáng',
        'afternoon' => 'Chiều',
        'evening' => 'Tối',
    ];

    private const SORTABLE = ['date', 'employee_name', 'department', 'check_time', 'total_hours', 'shift'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $department = trim((string) ($_GET['department'] ?? ''));
        $position = trim((string) ($_GET['position'] ?? ''));
        $employeeId = trim((string) ($_GET['employee_id'] ?? ''));
        $startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'))));
        $endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-d')));
        $query = trim((string) ($_GET['q'] ?? ''));
        $shifts = array_values(array_filter((array) ($_GET['shift'] ?? array_keys(self::SHIFTS)), fn ($value): bool => array_key_exists((string) $value, self::SHIFTS)));
        if ($shifts === []) {
            $shifts = array_keys(self::SHIFTS);
        }

        $items = $this->filtered($store, $department, $position, $employeeId, $startDate, $endDate, $shifts, $query);
        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'date';
        $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = $sort === 'total_hours'
                ? ((float) ($a[$sort] ?? 0) <=> (float) ($b[$sort] ?? 0))
                : strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));

            return $direction === 'desc' ? -$result : $result;
        });

        [$perPage, $page, $pages, $total] = $this->pagination($items, 25);

        View::render('@HumanResources/attendance/index', [
            'active' => 'employees',
            'title' => 'Quản lý chấm công thực tế',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'employees' => $store->get('employees'),
            'departments' => $this->departments($store),
            'positions' => $this->positions($store),
            'shifts' => self::SHIFTS,
            'selectedShifts' => $shifts,
            'department' => $department,
            'position' => $position,
            'employeeId' => $employeeId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public function process(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $department = trim((string) ($_GET['department'] ?? ''));
        $employeeId = trim((string) ($_GET['employee_id'] ?? ''));
        $startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'))));
        $endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-d', strtotime('sunday this week'))));

        View::render('@HumanResources/attendance/process', [
            'active' => 'employees',
            'title' => 'Xử lý chấm công',
            'employees' => $store->get('employees'),
            'departments' => $this->departments($store),
            'department' => $department,
            'employeeId' => $employeeId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'weekRows' => $this->weekRows($startDate, $endDate),
            'summary' => $this->summary($store, $department, $employeeId, $startDate, $endDate),
        ]);
    }

    public function generate(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        $this->ensureSampleData($store);

        $department = trim((string) ($_POST['department'] ?? ''));
        $employeeId = trim((string) ($_POST['employee_id'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? date('Y-m-d')));
        $endDate = trim((string) ($_POST['end_date'] ?? $startDate));

        if ($startDate === '' || $endDate === '' || $endDate < $startDate) {
            $_SESSION['flash_error'] = 'Khoảng ngày xử lý công không hợp lệ.';
            redirect('attendance.process');
        }

        $employees = $this->targetEmployees($store, $department, $employeeId);
        $items = $store->get('attendance_records');
        $existing = [];
        foreach ($items as $item) {
            $existing[($item['employee_id'] ?? '') . '|' . ($item['date'] ?? '') . '|' . ($item['shift'] ?? '')] = true;
        }

        $created = 0;
        foreach ($this->dateRange($startDate, $endDate) as $date) {
            $weekday = (int) date('N', strtotime($date));
            if ($weekday === 7) {
                continue;
            }
            foreach ($employees as $employee) {
                foreach (['morning', 'afternoon'] as $shift) {
                    $key = ($employee['id'] ?? '') . '|' . $date . '|' . $shift;
                    if (isset($existing[$key])) {
                        continue;
                    }
                    $items[] = $this->recordPayload($employee, $date, $shift, $weekday === 6 ? 3.5 : 4);
                    $existing[$key] = true;
                    $created++;
                }
            }
        }

        $store->put('attendance_records', $items);
        add_notification($store, 'Chấm công', "Đã tổng hợp $created dòng công.", '?route=attendance', 'success');
        $_SESSION['flash_success'] = "Đã tổng hợp $created dòng công.";
        redirect('attendance.process');
    }

    public function markViolations(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        $this->ensureSampleData($store);

        $department = trim((string) ($_POST['department'] ?? ''));
        $employeeId = trim((string) ($_POST['employee_id'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? date('Y-m-d')));
        $endDate = trim((string) ($_POST['end_date'] ?? $startDate));
        $records = $this->filtered($store, $department, '', $employeeId, $startDate, $endDate, array_keys(self::SHIFTS), '');
        $violations = $store->get('violations');
        $created = 0;

        foreach ($records as $record) {
            if ((float) ($record['total_hours'] ?? 0) >= 4) {
                continue;
            }
            $violations[] = [
                'id' => uid(),
                'employee_id' => $record['employee_id'] ?? '',
                'employee_name' => $record['employee_name'] ?? '',
                'date' => $record['date'] ?? date('Y-m-d'),
                'type' => 'Vi phạm về thời gian làm việc',
                'note' => 'Tự động ghi nhận từ dữ liệu chấm công thiếu giờ.',
            ];
            $created++;
        }

        $store->put('violations', $violations);
        add_notification($store, 'Chấm công', "Đã tạo $created phiếu vi phạm từ dữ liệu chấm công.", '?route=violations', $created > 0 ? 'warning' : 'info');
        $_SESSION['flash_success'] = "Đã tạo $created phiếu vi phạm.";
        redirect('attendance.process');
    }

    public function manage(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $project = trim((string) ($_GET['project'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $items = array_values(array_filter($store->get('attendance_machines'), function (array $item) use ($project, $query): bool {
            if ($project !== '' && ($item['project'] ?? '') !== $project) {
                return false;
            }
            if ($query === '') {
                return true;
            }
            $haystack = implode(' ', array_map('strval', $item));
            return str_contains($this->lower($haystack), $this->lower($query));
        }));

        [$perPage, $page, $pages, $total] = $this->pagination($items, 10);

        View::render('@HumanResources/attendance/manage', [
            'active' => 'employees',
            'title' => 'Quản lý máy chấm công',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'projects' => $this->projectNames($store),
            'project' => $project,
            'query' => $query,
            'perPage' => $perPage,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public function saveMachine(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $serial = trim((string) ($_POST['serial'] ?? ''));
        $project = trim((string) ($_POST['project'] ?? ''));

        if ($name === '' || $serial === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên máy và serial number.';
            redirect('attendance.manage');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'name' => $name,
            'serial' => $serial,
            'project' => $project,
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];
        $items = $store->get('attendance_machines');
        $isUpdate = $id !== '';
        $items = $isUpdate
            ? array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items)
            : array_merge([$payload], $items);

        $store->put('attendance_machines', $items);
        add_notification($store, 'Máy chấm công', ($isUpdate ? 'Đã cập nhật máy ' : 'Đã thêm máy ') . $name . '.', '?route=attendance.manage', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật máy chấm công.' : 'Đã thêm máy chấm công.';
        redirect('attendance.manage');
    }

    public function deleteMachine(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('attendance_machines'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));

        $store->put('attendance_machines', $items);
        if ($deleted !== null) {
            add_notification($store, 'Máy chấm công', 'Đã xóa máy ' . ($deleted['name'] ?? '') . '.', '?route=attendance.manage', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa máy chấm công.';
        redirect('attendance.manage');
    }

    private function filtered(DataStore $store, string $department, string $position, string $employeeId, string $startDate, string $endDate, array $shifts, string $query): array
    {
        return array_values(array_filter($store->get('attendance_records'), function (array $item) use ($department, $position, $employeeId, $startDate, $endDate, $shifts, $query): bool {
            if ($department !== '' && ($item['department'] ?? '') !== $department) {
                return false;
            }
            if ($position !== '' && ($item['position'] ?? '') !== $position) {
                return false;
            }
            if ($employeeId !== '' && ($item['employee_id'] ?? '') !== $employeeId) {
                return false;
            }
            if ($startDate !== '' && ($item['date'] ?? '') < $startDate) {
                return false;
            }
            if ($endDate !== '' && ($item['date'] ?? '') > $endDate) {
                return false;
            }
            if (! in_array((string) ($item['shift'] ?? ''), $shifts, true)) {
                return false;
            }
            if ($query === '') {
                return true;
            }
            $haystack = implode(' ', array_map('strval', $item));
            return str_contains($this->lower($haystack), $this->lower($query));
        }));
    }

    private function pagination(array $items, int $default): array
    {
        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? $default);
        $perPage = in_array($requested, $allowed, true) ? $requested : $default;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        return [$perPage, $page, $pages, $total];
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function departments(DataStore $store): array
    {
        return $this->uniqueEmployeeColumn($store, 'department');
    }

    private function positions(DataStore $store): array
    {
        return $this->uniqueEmployeeColumn($store, 'position');
    }

    private function uniqueEmployeeColumn(DataStore $store, string $column): array
    {
        $values = array_values(array_unique(array_filter(array_map(fn (array $employee): string => trim((string) ($employee[$column] ?? '')), $store->get('employees')))));
        sort($values);
        return $values;
    }

    private function projectNames(DataStore $store): array
    {
        $projects = array_map(fn (array $project): string => trim((string) ($project['name'] ?? '')), $store->get('projects'));
        $machines = array_map(fn (array $machine): string => trim((string) ($machine['project'] ?? '')), $store->get('attendance_machines'));
        $values = array_values(array_unique(array_filter(array_merge($projects, $machines))));
        sort($values);
        return $values;
    }

    private function targetEmployees(DataStore $store, string $department, string $employeeId): array
    {
        return array_values(array_filter($store->get('employees'), function (array $employee) use ($department, $employeeId): bool {
            if ($department !== '' && ($employee['department'] ?? '') !== $department) {
                return false;
            }
            if ($employeeId !== '' && ($employee['id'] ?? '') !== $employeeId) {
                return false;
            }
            return true;
        }));
    }

    private function recordPayload(array $employee, string $date, string $shift, float $hours): array
    {
        return [
            'id' => uid(),
            'date' => $date,
            'employee_id' => (string) ($employee['id'] ?? ''),
            'employee_name' => (string) ($employee['name'] ?? ''),
            'department' => (string) ($employee['department'] ?? ''),
            'position' => (string) ($employee['position'] ?? ''),
            'check_time' => self::SHIFTS[$shift] ?? $shift,
            'total_hours' => $hours,
            'shift' => $shift,
        ];
    }

    private function summary(DataStore $store, string $department, string $employeeId, string $startDate, string $endDate): array
    {
        $records = $this->filtered($store, $department, '', $employeeId, $startDate, $endDate, array_keys(self::SHIFTS), '');
        $hours = array_sum(array_map(fn (array $item): float => (float) ($item['total_hours'] ?? 0), $records));
        return [
            'records' => count($records),
            'hours' => $hours,
            'employees' => count(array_unique(array_map(fn (array $item): string => (string) ($item['employee_id'] ?? ''), $records))),
            'violations' => count(array_filter($records, fn (array $item): bool => (float) ($item['total_hours'] ?? 0) < 4)),
        ];
    }

    private function weekRows(string $startDate, string $endDate): array
    {
        $dates = $this->dateRange($startDate, $endDate);
        $cells = array_pad($dates, (int) ceil(max(1, count($dates)) / 7) * 7, '');
        return array_chunk($cells, 7);
    }

    private function dateRange(string $startDate, string $endDate): array
    {
        if ($startDate === '' || $endDate === '' || $endDate < $startDate) {
            return [];
        }
        $dates = [];
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            $dates[] = $date->format('Y-m-d');
        }
        return $dates;
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        $employees = $data['employees'] ?? [];
        if (empty($data['attendance_records']) && ! empty($employees)) {
            $sampleEmployees = array_slice($employees, 0, 6);
            $records = [];
            $dates = ['2026-06-22', '2026-06-23', '2026-06-24', '2026-06-25', '2026-06-26'];
            foreach ($dates as $dateIndex => $date) {
                foreach ($sampleEmployees as $employeeIndex => $employee) {
                    $records[] = $this->recordPayload($employee, $date, 'morning', ($dateIndex + $employeeIndex) % 5 === 0 ? 3.5 : 4);
                    $records[] = $this->recordPayload($employee, $date, 'afternoon', 4);
                }
            }
            $data['attendance_records'] = $records;
        }

        if (empty($data['attendance_machines'])) {
            $data['attendance_machines'] = [
                ['id' => 'am01', 'name' => 'Máy face 3', 'serial' => 'OVN7020067021400222', 'project' => 'Phú an', 'note' => 'Máy khu văn phòng'],
                ['id' => 'am02', 'name' => 'Ngoại ngữ', 'serial' => 'CDQ9182760042', 'project' => 'Phú Nam', 'note' => 'Máy cổng chính'],
                ['id' => 'am03', 'name' => 'Thủy Vân', 'serial' => '0335141000128', 'project' => 'Dự án hiệp thành', 'note' => 'Máy công trình'],
                ['id' => 'am04', 'name' => 'Máy face 5', 'serial' => 'OVN7020067021400359', 'project' => 'Thủy châu', 'note' => 'Máy kho'],
                ['id' => 'am05', 'name' => 'Máy face 4', 'serial' => 'OVN7020067021400400', 'project' => 'kho B', 'note' => 'Máy kho B'],
                ['id' => 'am06', 'name' => 'Thủy Thanh', 'serial' => '0335134800080', 'project' => 'Dự án Phú Đa', 'note' => 'Máy công trình'],
                ['id' => 'am07', 'name' => 'máy face 7', 'serial' => 'OVN7020067021400346', 'project' => 'Kho D', 'note' => 'Máy kho D'],
                ['id' => 'am08', 'name' => 'Nhà vườn', 'serial' => 'CDQ9182760081', 'project' => 'Dự án Phú Đa', 'note' => 'Máy nhà vườn'],
            ];
        }

        $store->save($data);
    }
}
