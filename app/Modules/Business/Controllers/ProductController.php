<?php

declare(strict_types=1);

final class ProductController
{
    private const SORTABLE = ['name', 'sku', 'variant', 'price', 'quantity', 'revenue', 'status', 'category'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $field = in_array($_GET['field'] ?? '', ['code', 'name', 'sku'], true) ? (string) $_GET['field'] : 'code';
        $query = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? 'all'));
        $status = in_array($status, ['all', 'in_stock', 'out_stock', 'pending', 'violation', 'hidden'], true) ? $status : 'all';

        $allItems = $store->get('products');
        $items = $this->filtered($allItems, $field, $query, $category, $status);
        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'name';
        $direction = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $numeric = in_array($sort, ['price', 'quantity', 'revenue'], true);
            $result = $numeric
                ? ((float) ($a[$sort] ?? 0)) <=> ((float) ($b[$sort] ?? 0))
                : strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));

            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 10);
        $perPage = in_array($requested, $allowed, true) ? $requested : 10;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Business/products/index', [
            'active' => 'services',
            'title' => 'Danh sách sản phẩm',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'allItems' => $allItems,
            'categories' => $this->categories($store),
            'field' => $field,
            'query' => $query,
            'category' => $category,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'statusLabels' => $this->statusLabels(),
            'fieldLabels' => ['code' => 'Mã sản phẩm', 'name' => 'Tên sản phẩm', 'sku' => 'SKU'],
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên sản phẩm.';
            redirect('products');
        }

        $existing = $id !== '' ? $this->findById($store, $id) : null;
        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'name' => $name,
            'sku' => trim((string) ($_POST['sku'] ?? '')),
            'code' => trim((string) ($_POST['code'] ?? '')),
            'variant' => trim((string) ($_POST['variant'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? '')),
            'price' => max(0, (float) ($_POST['price'] ?? 0)),
            'quantity' => max(0, (int) ($_POST['quantity'] ?? 0)),
            'revenue' => max(0, (float) ($_POST['revenue'] ?? 0)),
            'status' => array_key_exists($_POST['status'] ?? '', $this->statusLabels()) ? (string) $_POST['status'] : 'in_stock',
            'image' => $this->uploadImage((string) ($existing['image'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        $items = $store->get('products');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put('products', $items);
        add_notification($store, 'Sản phẩm', ($isUpdate ? 'Đã cập nhật sản phẩm ' : 'Đã thêm sản phẩm ') . $name . '.', '?route=products', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật sản phẩm.' : 'Đã thêm sản phẩm mới.';
        redirect('products');
    }

    public function updatePrice(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $price = max(0, (float) ($_POST['price'] ?? 0));
        $updated = null;
        $items = array_map(function (array $item) use ($id, $price, &$updated): array {
            if (($item['id'] ?? '') !== $id) {
                return $item;
            }

            $item['price'] = $price;
            $updated = $item;
            return $item;
        }, $store->get('products'));

        if ($updated === null) {
            $_SESSION['flash_error'] = 'Không tìm thấy sản phẩm cần cập nhật giá.';
            redirect('products');
        }

        $store->put('products', $items);
        add_notification(
            $store,
            'Sản phẩm',
            'Đã cập nhật giá sản phẩm ' . ($updated['name'] ?? '') . '.',
            '?route=products',
            'info'
        );
        $_SESSION['flash_success'] = 'Đã cập nhật giá sản phẩm.';
        redirect('products');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('products'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }

            return true;
        }));

        $store->put('products', $items);
        if ($deleted !== null) {
            add_notification($store, 'Sản phẩm', 'Đã xóa sản phẩm ' . ($deleted['name'] ?? '') . '.', '?route=products', 'danger');
        }

        $_SESSION['flash_success'] = 'Đã xóa sản phẩm.';
        redirect('products');
    }

    private function filtered(array $items, string $field, string $query, string $category, string $status): array
    {
        return array_values(array_filter($items, function (array $item) use ($field, $query, $category, $status): bool {
            if ($category !== '' && ($item['category'] ?? '') !== $category) {
                return false;
            }
            if ($status !== 'all' && ($item['status'] ?? 'in_stock') !== $status) {
                return false;
            }
            if ($query === '') {
                return true;
            }

            $haystack = (string) ($item[$field] ?? '');
            if (function_exists('mb_strtolower')) {
                return str_contains(mb_strtolower($haystack, 'UTF-8'), mb_strtolower($query, 'UTF-8'));
            }

            return str_contains(strtolower($haystack), strtolower($query));
        }));
    }

    private function categories(DataStore $store): array
    {
        $services = array_values(array_filter($store->get('services'), fn (array $item): bool => trim((string) ($item['name'] ?? '')) !== ''));
        usort($services, fn (array $a, array $b): int => strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        $children = [];
        foreach ($services as $item) {
            $parent = trim((string) ($item['parent'] ?? ''));
            $children[$parent][] = $item;
        }

        $result = [];
        $walk = function (string $parent, int $depth) use (&$walk, &$children, &$result): void {
            foreach ($children[$parent] ?? [] as $item) {
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $result[] = [
                    'value' => $name,
                    'label' => str_repeat('— ', max(0, $depth - 1)) . $name,
                ];
                $walk($name, $depth + 1);
            }
        };

        $walk('', 1);
        $known = array_column($result, 'value');
        foreach ($services as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name !== '' && ! in_array($name, $known, true)) {
                $result[] = ['value' => $name, 'label' => $name];
            }
        }

        return $result;
    }

    private function statusLabels(): array
    {
        return [
            'all' => 'Tất cả',
            'in_stock' => 'Còn hàng',
            'out_stock' => 'Hết hàng',
            'pending' => 'Chờ duyệt',
            'violation' => 'Vi phạm',
            'hidden' => 'Ẩn',
        ];
    }

    private function findById(DataStore $store, string $id): ?array
    {
        foreach ($store->get('products') as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    private function uploadImage(string $current): string
    {
        $file = $_FILES['image'] ?? null;
        if (! is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $current;
        }

        try {
            return store_uploaded_image('image', 'public/uploads/products', 'product') ?? $current;
        } catch (RuntimeException) {
            $_SESSION['flash_error'] = 'Ảnh sản phẩm không hợp lệ. Chỉ hỗ trợ JPG, PNG, WEBP và tối đa 2MB.';
            redirect('products');
        }
    }

    private function ensureSampleData(DataStore $store): void
    {
        $items = $store->get('products');
        if ($items !== []) {
            return;
        }

        $store->put('products', [
            ['id' => 'prd01', 'name' => 'sản phẩm AB', 'sku' => '-', 'code' => 'SP-AB', 'variant' => '-', 'category' => 'bERP', 'price' => 4000000, 'quantity' => 0, 'revenue' => 0, 'status' => 'in_stock', 'image' => 'public/assets/sample-product-lotus.svg', 'note' => 'Sản phẩm mẫu'],
            ['id' => 'prd02', 'name' => 'Sản phẩm DBTG', 'sku' => '-', 'code' => 'SP-DBTG', 'variant' => '-', 'category' => 'Dịch vụ sửa chữa', 'price' => 10000000, 'quantity' => 0, 'revenue' => 0, 'status' => 'in_stock', 'image' => '', 'note' => 'Sản phẩm mẫu'],
        ]);
    }
}
