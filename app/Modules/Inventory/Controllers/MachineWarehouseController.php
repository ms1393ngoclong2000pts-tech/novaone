<?php

declare(strict_types=1);

final class MachineWarehouseController
{
    private const SORTABLE = ['name', 'project', 'manager', 'keeper', 'address', 'phone'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $warehouse = trim((string) ($_GET['warehouse'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store, $warehouse, $query);

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'name';
        $direction = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
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

        View::render('@Inventory/machine_warehouses/index', [
            'active' => 'internal_assets',
            'title' => 'Kho Máy',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'warehouses' => $this->warehouseNames($store),
            'projects' => $this->projectNames($store),
            'warehouse' => $warehouse,
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
        if ($name === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên kho.';
            redirect('machine-warehouses');
        }

        $project = trim((string) ($_POST['project'] ?? ''));
        $validProjects = array_values(array_filter(array_map(
            fn (array $item): string => trim((string) ($item['name'] ?? '')),
            $store->get('projects')
        )));
        if ($project !== '' && ! in_array($project, $validProjects, true)) {
            $_SESSION['flash_error'] = 'Dự án phải được chọn từ danh sách dự án.';
            redirect('machine-warehouses');
        }

        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'name' => $name,
            'project' => $project,
            'manager' => trim((string) ($_POST['manager'] ?? '')),
            'keeper' => trim((string) ($_POST['keeper'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('machine_warehouses');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put('machine_warehouses', $items);
        add_notification(
            $store,
            'Kho máy',
            ($isUpdate ? 'Đã cập nhật kho ' : 'Đã thêm kho ') . $name . '.',
            '?route=machine-warehouses',
            $isUpdate ? 'info' : 'success'
        );

        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật kho.' : 'Đã thêm kho mới.';
        redirect('machine-warehouses');
    }

    public function transfer(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $target = trim((string) ($_POST['target'] ?? ''));
        $updated = null;
        $items = array_map(function (array $item) use ($id, $target, &$updated): array {
            if (($item['id'] ?? '') === $id) {
                $item['note'] = trim((string) ($item['note'] ?? '') . ($target !== '' ? ' | Chuyển kho: ' . $target : ' | Đã tạo phiếu chuyển kho'));
                $updated = $item;
            }
            return $item;
        }, $store->get('machine_warehouses'));

        $store->put('machine_warehouses', $items);
        if ($updated !== null) {
            add_notification($store, 'Kho máy', 'Đã tạo phiếu chuyển kho cho ' . ($updated['name'] ?? '') . '.', '?route=machine-warehouses', 'info');
        }

        $_SESSION['flash_success'] = 'Đã tạo phiếu chuyển kho.';
        redirect('machine-warehouses');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('machine_warehouses'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));

        $store->put('machine_warehouses', $items);
        if ($deleted !== null) {
            add_notification($store, 'Kho máy', 'Đã xóa kho ' . ($deleted['name'] ?? '') . '.', '?route=machine-warehouses', 'danger');
        }

        $_SESSION['flash_success'] = 'Đã xóa kho.';
        redirect('machine-warehouses');
    }

    private function filtered(DataStore $store, string $warehouse, string $query): array
    {
        return array_values(array_filter($store->get('machine_warehouses'), function (array $item) use ($warehouse, $query): bool {
            if ($warehouse !== '' && ($item['name'] ?? '') !== $warehouse) {
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

    private function warehouseNames(DataStore $store): array
    {
        $names = array_values(array_unique(array_filter(array_map(
            fn (array $item): string => trim((string) ($item['name'] ?? '')),
            $store->get('machine_warehouses')
        ))));
        sort($names);
        return $names;
    }

    private function projectNames(DataStore $store): array
    {
        $names = array_values(array_unique(array_filter(array_map(
            fn (array $item): string => trim((string) ($item['name'] ?? '')),
            $store->get('projects')
        ))));
        natcasesort($names);
        return array_values($names);
    }

    private function ensureSampleData(DataStore $store): void
    {
        $data = $store->all();
        if (! empty($data['machine_warehouses'])) {
            return;
        }

        $data['machine_warehouses'] = [
            ['id' => 'mw01', 'name' => 'Kho cty', 'project' => '', 'manager' => 'bData co.,ltd', 'keeper' => 'bData co.,ltd', 'address' => '207 An Dương Vương', 'phone' => '1234567897', 'note' => 'Kho chính công ty'],
            ['id' => 'mw02', 'name' => 'Phú an', 'project' => '', 'manager' => 'bData co.,ltd', 'keeper' => '', 'address' => 'abc', 'phone' => '123456', 'note' => 'Kho khu vực'],
            ['id' => 'mw03', 'name' => 'Phú Nam', 'project' => '', 'manager' => 'bData co.,ltd', 'keeper' => '', 'address' => 'abcd', 'phone' => '1234567', 'note' => 'Kho khu vực'],
            ['id' => 'mw04', 'name' => 'Cơ Khí', 'project' => '', 'manager' => 'bData co.,ltd', 'keeper' => '', 'address' => '12345678', 'phone' => '912345789', 'note' => 'Kho cơ khí'],
            ['id' => 'mw05', 'name' => 'Hương Vân', 'project' => '', 'manager' => 'bData co.,ltd', 'keeper' => '', 'address' => '123456789', 'phone' => '123456789', 'note' => 'Kho chi nhánh'],
            ['id' => 'mw06', 'name' => 'Luật', 'project' => '', 'manager' => 'bData co.,ltd', 'keeper' => '', 'address' => '123456', 'phone' => '459', 'note' => 'Kho hồ sơ'],
            ['id' => 'mw07', 'name' => 'Phan Đình Phùng', 'project' => '', 'manager' => 'bData co.,ltd', 'keeper' => '', 'address' => '12', 'phone' => '23', 'note' => 'Kho điểm bán'],
            ['id' => 'mw08', 'name' => 'Phú Bài', 'project' => '', 'manager' => 'bData co.,ltd', 'keeper' => '', 'address' => 'abc', 'phone' => '123456', 'note' => 'Kho khu vực'],
        ];
        $store->save($data);
    }
}
