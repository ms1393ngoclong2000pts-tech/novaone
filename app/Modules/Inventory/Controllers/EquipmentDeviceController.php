<?php

declare(strict_types=1);

final class EquipmentDeviceController
{
    private const COLUMNS = ['name', 'code', 'unit_price', 'supplier', 'unit'];
    private const SORTABLE = ['name', 'code', 'unit_price', 'supplier', 'unit'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store, $query);

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'name';
        $direction = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $left = $sort === 'unit_price' ? (float) ($a[$sort] ?? 0) : (string) ($a[$sort] ?? '');
            $right = $sort === 'unit_price' ? (float) ($b[$sort] ?? 0) : (string) ($b[$sort] ?? '');
            $result = is_float($left) ? ($left <=> (float) $right) : strnatcasecmp((string) $left, (string) $right);
            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 25);
        $perPage = in_array($requested, $allowed, true) ? $requested : 25;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Inventory/equipment_devices/index', [
            'active' => 'internal_assets',
            'title' => 'Quản Lý Thiết Bị',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'suppliers' => $this->supplierNames($store),
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));

        if ($name === '' || $code === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên hàng và mã hàng.';
            redirect('equipment-devices');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'name' => $name,
            'code' => $code,
            'unit_price' => max(0, (float) ($_POST['unit_price'] ?? 0)),
            'supplier' => trim((string) ($_POST['supplier'] ?? '')),
            'unit' => trim((string) ($_POST['unit'] ?? 'cái')),
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('equipment_devices');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put('equipment_devices', $items);
        add_notification(
            $store,
            'Quản lý thiết bị',
            ($isUpdate ? 'Đã cập nhật thiết bị ' : 'Đã thêm thiết bị ') . $name . '.',
            '?route=equipment-devices',
            $isUpdate ? 'info' : 'success'
        );

        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật thiết bị.' : 'Đã thêm thiết bị mới.';
        redirect('equipment-devices');
    }

    public function import(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $file = $_FILES['device_file'] ?? null;
        if (! is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Vui lòng chọn file CSV hoặc XLSX.';
            redirect('equipment-devices');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024 || ! is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            $_SESSION['flash_error'] = 'File tải lên không hợp lệ hoặc vượt quá 5MB.';
            redirect('equipment-devices');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            $_SESSION['flash_error'] = 'Chỉ hỗ trợ file CSV hoặc XLSX.';
            redirect('equipment-devices');
        }

        try {
            $rows = $extension === 'xlsx'
                ? XlsxReader::rows((string) $file['tmp_name'])
                : $this->csvRows((string) $file['tmp_name']);
        } catch (RuntimeException $exception) {
            $_SESSION['flash_error'] = $exception->getMessage();
            redirect('equipment-devices');
        }

        $header = array_shift($rows);
        if (! is_array($header)) {
            $_SESSION['flash_error'] = 'File không có dữ liệu.';
            redirect('equipment-devices');
        }

        $header = array_map(fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"), $header);
        if (array_slice($header, 0, count(self::COLUMNS)) !== self::COLUMNS) {
            $_SESSION['flash_error'] = 'Cột dữ liệu phải là: ' . implode(', ', self::COLUMNS) . '.';
            redirect('equipment-devices');
        }

        $items = $store->get('equipment_devices');
        $count = 0;
        foreach ($rows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $row = array_pad($row, count(self::COLUMNS), '');
            $device = ['id' => uid()];
            foreach (self::COLUMNS as $index => $column) {
                $device[$column] = $column === 'unit_price' ? (float) $row[$index] : trim((string) $row[$index]);
            }
            if ($device['name'] === '' || $device['code'] === '') {
                continue;
            }
            $items[] = $device;
            $count++;
        }

        $store->put('equipment_devices', $items);
        add_notification($store, 'Quản lý thiết bị', "Đã nhập $count thiết bị từ file " . strtoupper($extension) . '.', '?route=equipment-devices', 'success');
        $_SESSION['flash_success'] = "Đã nhập thành công $count thiết bị.";
        redirect('equipment-devices');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('equipment_devices'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));

        $store->put('equipment_devices', $items);
        if ($deleted !== null) {
            add_notification($store, 'Quản lý thiết bị', 'Đã xóa thiết bị ' . ($deleted['name'] ?? '') . '.', '?route=equipment-devices', 'danger');
        }

        $_SESSION['flash_success'] = 'Đã xóa thiết bị.';
        redirect('equipment-devices');
    }

    private function filtered(DataStore $store, string $query): array
    {
        return array_values(array_filter($store->get('equipment_devices'), function (array $item) use ($query): bool {
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

    private function supplierNames(DataStore $store): array
    {
        $names = array_values(array_unique(array_filter(array_map(
            fn (array $item): string => trim((string) ($item['name'] ?? '')),
            $store->get('suppliers')
        ))));
        natcasesort($names);
        return array_values($names);
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

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['equipment_devices'])) {
            return;
        }

        $data['equipment_devices'] = [
            ['id' => 'dv01', 'name' => 'Quạt hơi nước', 'code' => 'PS1', 'unit_price' => 1000000, 'supplier' => 'bData co.,ltd', 'unit' => 'cái', 'note' => 'Thiết bị làm mát'],
            ['id' => 'dv02', 'name' => 'Sạc', 'code' => 'SP128000', 'unit_price' => 55000, 'supplier' => 'bData co.,ltd', 'unit' => 'cái', 'note' => 'Phụ kiện nguồn'],
            ['id' => 'dv03', 'name' => 'Ram 8g', 'code' => 'SP126', 'unit_price' => 100000, 'supplier' => 'bData co.,ltd', 'unit' => 'cái', 'note' => 'Linh kiện máy tính'],
            ['id' => 'dv04', 'name' => 'Màn hình dell', 'code' => 'SP12789', 'unit_price' => 155000, 'supplier' => 'bData co.,ltd', 'unit' => 'cái', 'note' => 'Màn hình văn phòng'],
            ['id' => 'dv05', 'name' => 'Thiết bị chiếu sáng', 'code' => 'TB023111', 'unit_price' => 500000, 'supplier' => 'bData co.,ltd', 'unit' => 'VND', 'note' => 'Thiết bị văn phòng'],
            ['id' => 'dv06', 'name' => 'Tivi', 'code' => 'TV01', 'unit_price' => 2000000, 'supplier' => 'bData co.,ltd', 'unit' => 'cái', 'note' => 'Thiết bị trình chiếu'],
        ];
        $store->save($data);
    }
}
