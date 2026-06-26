<?php

declare(strict_types=1);

final class EquipmentTypeController
{
    private const SORTABLE = ['name', 'short_name', 'created_at'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $query = trim((string) ($_GET['q'] ?? ''));
        $items = $this->filtered($store, $query);

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'created_at';
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

        View::render('@Inventory/equipment_types/index', [
            'active' => 'internal_assets',
            'title' => 'Loại Thiết Bị',
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
        $name = trim((string) ($_POST['name'] ?? ''));
        $shortName = trim((string) ($_POST['short_name'] ?? ''));
        if ($name === '' || $shortName === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên loại thiết bị và tên viết tắt.';
            redirect('equipment-types');
        }

        $items = $store->get('equipment_types');
        $isUpdate = $id !== '';
        $payload = [
            'id' => $isUpdate ? $id : uid(),
            'name' => $name,
            'short_name' => $shortName,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($isUpdate) {
            $items = array_map(function (array $item) use ($id, $payload): array {
                if (($item['id'] ?? '') !== $id) {
                    return $item;
                }
                return [...$payload, 'created_at' => (string) ($item['created_at'] ?? $payload['created_at'])];
            }, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put('equipment_types', $items);
        add_notification($store, 'Loại thiết bị', ($isUpdate ? 'Đã cập nhật loại ' : 'Đã thêm loại ') . $name . '.', '?route=equipment-types', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật loại thiết bị.' : 'Đã thêm loại thiết bị.';
        redirect('equipment-types');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('equipment_types'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));

        $store->put('equipment_types', $items);
        if ($deleted !== null) {
            add_notification($store, 'Loại thiết bị', 'Đã xóa loại ' . ($deleted['name'] ?? '') . '.', '?route=equipment-types', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa loại thiết bị.';
        redirect('equipment-types');
    }

    private function filtered(DataStore $store, string $query): array
    {
        return array_values(array_filter($store->get('equipment_types'), function (array $item) use ($query): bool {
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
        if (! empty($data['equipment_types'])) {
            return;
        }

        $data['equipment_types'] = [
            ['id' => 'et01', 'name' => 'Server BDATA.LINK', 'short_name' => 'DBA', 'created_at' => '2026-06-12 04:22:00'],
            ['id' => 'et02', 'name' => 'Máy tính', 'short_name' => 'MT', 'created_at' => '2026-05-26 08:26:00'],
            ['id' => 'et03', 'name' => 'Chuột', 'short_name' => 'CH', 'created_at' => '2026-05-18 10:03:00'],
        ];
        $store->save($data);
    }
}
