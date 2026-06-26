<?php

declare(strict_types=1);

final class SupplierController
{
    private const SORTABLE = ['code', 'name', 'category', 'phone', 'contact_person', 'debt', 'status'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store, $query);
        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'name';
        $direction = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            if ($sort === 'debt') {
                $result = ((float) ($a[$sort] ?? 0)) <=> ((float) ($b[$sort] ?? 0));
            } else {
                $result = strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));
            }

            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 10);
        $perPage = in_array($requested, $allowed, true) ? $requested : 10;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Business/suppliers/index', [
            'active' => 'suppliers',
            'title' => 'Nhà cung cấp',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
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
        $code = trim((string) ($_POST['code'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($code === '' || $name === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập mã và tên nhà cung cấp.';
            redirect('suppliers');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'code' => $code,
            'name' => $name,
            'category' => trim((string) ($_POST['category'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'contact_person' => trim((string) ($_POST['contact_person'] ?? '')),
            'debt' => max(0, (float) ($_POST['debt'] ?? 0)),
            'status' => in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? (string) $_POST['status'] : 'active',
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('suppliers');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put('suppliers', $items);
        add_notification(
            $store,
            'Nhà cung cấp',
            ($isUpdate ? 'Đã cập nhật nhà cung cấp ' : 'Đã thêm nhà cung cấp ') . $name . '.',
            '?route=suppliers',
            $isUpdate ? 'info' : 'success'
        );

        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật nhà cung cấp.' : 'Đã thêm nhà cung cấp mới.';
        redirect('suppliers');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('suppliers'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }

            return true;
        }));

        $store->put('suppliers', $items);

        if ($deleted !== null) {
            add_notification($store, 'Nhà cung cấp', 'Đã xóa nhà cung cấp ' . ($deleted['name'] ?? '') . '.', '?route=suppliers', 'danger');
        }

        $_SESSION['flash_success'] = 'Đã xóa nhà cung cấp.';
        redirect('suppliers');
    }

    public function import(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $file = $_FILES['supplier_file'] ?? null;
        if (! is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Vui lòng chọn file Excel hoặc CSV.';
            redirect('suppliers');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024 || ! is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            $_SESSION['flash_error'] = 'File tải lên không hợp lệ hoặc vượt quá 5MB.';
            redirect('suppliers');
        }
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            $_SESSION['flash_error'] = 'Chỉ hỗ trợ file Excel XLSX hoặc CSV.';
            redirect('suppliers');
        }

        try {
            $rows = $extension === 'csv'
                ? $this->csvRows((string) $file['tmp_name'])
                : XlsxReader::rows((string) $file['tmp_name']);
        } catch (Throwable $exception) {
            $_SESSION['flash_error'] = $exception->getMessage();
            redirect('suppliers');
        }

        $items = $store->get('suppliers');
        $imported = 0;
        foreach ($rows as $index => $row) {
            if ($index === 0 && $this->looksLikeHeader($row)) {
                continue;
            }

            $code = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            if ($code === '' || $name === '') {
                continue;
            }

            array_unshift($items, [
                'id' => uid(),
                'code' => $code,
                'name' => $name,
                'category' => trim((string) ($row[2] ?? '')),
                'phone' => trim((string) ($row[3] ?? '')),
                'email' => trim((string) ($row[4] ?? '')),
                'address' => trim((string) ($row[5] ?? '')),
                'contact_person' => trim((string) ($row[6] ?? '')),
                'debt' => max(0, (float) str_replace([',', '.'], '', (string) ($row[7] ?? 0))),
                'status' => 'active',
                'note' => trim((string) ($row[8] ?? '')),
            ]);
            $imported++;
        }

        $store->put('suppliers', $items);
        if ($imported > 0) {
            add_notification($store, 'Nhà cung cấp', 'Đã nhập ' . $imported . ' nhà cung cấp từ file.', '?route=suppliers', 'success');
            $_SESSION['flash_success'] = 'Đã nhập ' . $imported . ' nhà cung cấp.';
        } else {
            $_SESSION['flash_error'] = 'Không tìm thấy dòng dữ liệu hợp lệ trong file.';
        }

        redirect('suppliers');
    }

    private function filtered(DataStore $store, string $query): array
    {
        return array_values(array_filter($store->get('suppliers'), function (array $item) use ($query): bool {
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

    private function looksLikeHeader(array $row): bool
    {
        $first = strtolower(trim((string) ($row[0] ?? '')));
        $second = strtolower(trim((string) ($row[1] ?? '')));

        return str_contains($first, 'code') || str_contains($first, 'mã') || str_contains($second, 'name') || str_contains($second, 'tên');
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        $items = $data['suppliers'] ?? [];

        if ($items === []) {
            $items = [
                ['id' => 'sp1', 'code' => 'BDATA', 'name' => 'bData co.,ltd', 'category' => 'Phần mềm', 'phone' => '0901 111 222', 'email' => 'contact@bdata.vn', 'address' => 'TP. Hồ Chí Minh', 'contact_person' => 'Trần Ngọc Long', 'debt' => 0, 'status' => 'active', 'note' => 'Đối tác triển khai hệ thống'],
                ['id' => 'sp2', 'code' => 'NC 009', 'name' => 'CÔNG TY TNHH DIC (VIỆT NAM)', 'category' => 'Thiết bị', 'phone' => '0902 333 444', 'email' => 'sales@dic.vn', 'address' => 'Hà Nội', 'contact_person' => 'Phòng kinh doanh', 'debt' => 0, 'status' => 'active', 'note' => 'Nhà cung cấp thiết bị'],
                ['id' => 'sp3', 'code' => '112321312312', 'name' => 'Trần Ngọc Long', 'category' => 'Dịch vụ', 'phone' => '0903 555 666', 'email' => 'long@example.com', 'address' => 'Đà Nẵng', 'contact_person' => 'Trần Ngọc Long', 'debt' => 0, 'status' => 'active', 'note' => 'Nhà cung cấp cá nhân'],
                ['id' => 'sp4', 'code' => '123123123', 'name' => 'Trần Ngọc Long 1', 'category' => 'Dịch vụ', 'phone' => '0904 555 666', 'email' => 'long1@example.com', 'address' => 'TP. Hồ Chí Minh', 'contact_person' => 'Trần Ngọc Long', 'debt' => 0, 'status' => 'active', 'note' => 'Dữ liệu mẫu'],
            ];
        }

        $normalized = [];
        foreach ($items as $index => $item) {
            $name = (string) ($item['name'] ?? '');
            $normalized[] = [
                'id' => (string) ($item['id'] ?? uid()),
                'code' => (string) ($item['code'] ?? ('NC ' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT))),
                'name' => $name,
                'category' => (string) ($item['category'] ?? ''),
                'phone' => (string) ($item['phone'] ?? ''),
                'email' => (string) ($item['email'] ?? ''),
                'address' => (string) ($item['address'] ?? ''),
                'contact_person' => (string) ($item['contact_person'] ?? ''),
                'debt' => (float) ($item['debt'] ?? 0),
                'status' => (string) ($item['status'] ?? 'active'),
                'note' => (string) ($item['note'] ?? ''),
            ];
        }

        if (empty($data['_migrations']['supplier_sample_rows_v1'])) {
            $existingCodes = array_map(fn (array $item): string => (string) ($item['code'] ?? ''), $normalized);
            foreach (array_reverse($this->sampleRows()) as $sample) {
                if (! in_array($sample['code'], $existingCodes, true)) {
                    array_unshift($normalized, $sample);
                }
            }
            $data['_migrations']['supplier_sample_rows_v1'] = true;
        }

        $data['suppliers'] = $normalized;
        $store->save($data);
    }

    private function sampleRows(): array
    {
        return [
            ['id' => 'sp-bdata', 'code' => 'BDATA', 'name' => 'bData co.,ltd', 'category' => 'Phần mềm', 'phone' => '0901 111 222', 'email' => 'contact@bdata.vn', 'address' => 'TP. Hồ Chí Minh', 'contact_person' => 'Trần Ngọc Long', 'debt' => 0, 'status' => 'active', 'note' => 'Đối tác triển khai hệ thống'],
            ['id' => 'sp-nc009', 'code' => 'NC 009', 'name' => 'CÔNG TY TNHH DIC (VIỆT NAM)', 'category' => 'Thiết bị', 'phone' => '0902 333 444', 'email' => 'sales@dic.vn', 'address' => 'Hà Nội', 'contact_person' => 'Phòng kinh doanh', 'debt' => 0, 'status' => 'active', 'note' => 'Nhà cung cấp thiết bị'],
            ['id' => 'sp-long', 'code' => '112321312312', 'name' => 'Trần Ngọc Long', 'category' => 'Dịch vụ', 'phone' => '0903 555 666', 'email' => 'long@example.com', 'address' => 'Đà Nẵng', 'contact_person' => 'Trần Ngọc Long', 'debt' => 0, 'status' => 'active', 'note' => 'Nhà cung cấp cá nhân'],
            ['id' => 'sp-long-1', 'code' => '123123123', 'name' => 'Trần Ngọc Long 1', 'category' => 'Dịch vụ', 'phone' => '0904 555 666', 'email' => 'long1@example.com', 'address' => 'TP. Hồ Chí Minh', 'contact_person' => 'Trần Ngọc Long', 'debt' => 0, 'status' => 'active', 'note' => 'Dữ liệu mẫu'],
        ];
    }
}
