<?php

declare(strict_types=1);

final class ServiceController
{
    private const SORTABLE = ['name', 'level', 'parent', 'status'];
    private const IMAGE_FIELDS = ['image' => 'Ảnh đại diện', 'icon' => 'Ảnh icon danh mục'];

    public function index(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $query = trim((string) ($_GET['q'] ?? ''));
        $level = (int) ($_GET['level'] ?? 1);
        $level = in_array($level, [1, 2, 3, 4], true) ? $level : 1;
        $items = $this->filtered($store, $query);
        $allItems = $store->get('services');
        $selectedServiceId = trim((string) ($_GET['selected_service'] ?? ''));

        $sort = in_array($_GET['sort'] ?? '', self::SORTABLE, true) ? (string) $_GET['sort'] : 'name';
        $direction = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        usort($items, function (array $a, array $b) use ($sort, $direction): int {
            $result = $sort === 'level'
                ? ((int) ($a[$sort] ?? 0)) <=> ((int) ($b[$sort] ?? 0))
                : strnatcasecmp((string) ($a[$sort] ?? ''), (string) ($b[$sort] ?? ''));

            return $direction === 'desc' ? -$result : $result;
        });

        $allowed = [10, 25, 50, 100];
        $requested = (int) ($_GET['per_page'] ?? 10);
        $perPage = in_array($requested, $allowed, true) ? $requested : 10;
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);

        View::render('@Business/services/index', [
            'active' => 'services',
            'title' => 'Danh sách dịch vụ',
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'levels' => $this->levels($allItems),
            'allItems' => $allItems,
            'serviceTree' => $this->serviceTree($allItems, $selectedServiceId),
            'query' => $query,
            'level' => $level,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'levelLabels' => $this->levelLabels(),
            'levelButtonLabels' => $this->levelButtonLabels(),
        ]);
    }

    public function create(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);
        $level = (int) ($_GET['level'] ?? 1);
        $level = in_array($level, [1, 2, 3, 4], true) ? $level : 1;

        View::render('@Business/services/form', [
            'active' => 'services',
            'title' => 'Thêm mới ngành hàng',
            'item' => ['level' => $level, 'status' => 'active'],
            'items' => $store->get('services'),
            'levelLabels' => $this->levelLabels(),
            'isEdit' => false,
        ]);
    }

    public function edit(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $id = trim((string) ($_GET['id'] ?? ''));
        $item = $this->findById($store, $id);
        if ($item === null) {
            $_SESSION['flash_error'] = 'Không tìm thấy ngành hàng.';
            redirect('services');
        }

        View::render('@Business/services/form', [
            'active' => 'services',
            'title' => 'Cập nhật ngành hàng',
            'item' => $item,
            'items' => $store->get('services'),
            'levelLabels' => $this->levelLabels(),
            'isEdit' => true,
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $level = (int) ($_POST['level'] ?? 1);
        if ($name === '' || ! in_array($level, [1, 2, 3, 4], true)) {
            $_SESSION['flash_error'] = 'Vui lòng nhập tên ngành hàng và cấp hợp lệ.';
            $this->redirectToForm($id);
        }

        $existing = $id !== '' ? $this->findById($store, $id) : null;
        $payload = [
            'id' => $id !== '' ? $id : uid(),
            'name' => $name,
            'level' => $level,
            'parent' => trim((string) ($_POST['parent'] ?? '')),
            'code' => trim((string) ($_POST['code'] ?? '')),
            'price' => max(0, (float) ($_POST['price'] ?? 0)),
            'status' => in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? (string) $_POST['status'] : 'active',
            'note' => trim((string) ($_POST['note'] ?? '')),
            'image' => $this->uploadImage('image', (string) ($existing['image'] ?? ''), $id),
            'icon' => $this->uploadImage('icon', (string) ($existing['icon'] ?? ''), $id),
        ];

        $items = $store->get('services');
        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ($item['id'] ?? '') === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put('services', $items);
        add_notification(
            $store,
            'Dịch vụ',
            ($isUpdate ? 'Đã cập nhật ngành hàng ' : 'Đã thêm ngành hàng ') . $name . '.',
            '?route=services',
            $isUpdate ? 'info' : 'success'
        );

        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật ngành hàng.' : 'Đã thêm ngành hàng mới.';
        redirect('services');
    }

    public function show(DataStore $store): void
    {
        require_auth();
        $this->ensureSampleData($store);

        $id = trim((string) ($_GET['id'] ?? ''));
        $item = $this->findById($store, $id);
        if ($item === null) {
            $_SESSION['flash_error'] = 'Không tìm thấy ngành hàng.';
            redirect('services');
        }

        View::render('@Business/services/show', [
            'active' => 'services',
            'title' => 'Xem chi tiết ngành hàng',
            'item' => $item,
            'products' => [],
        ]);
    }

    public function toggle(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $updated = null;
        $items = array_map(function (array $item) use ($id, &$updated): array {
            if (($item['id'] ?? '') !== $id) {
                return $item;
            }

            $item['status'] = ($item['status'] ?? 'active') === 'active' ? 'inactive' : 'active';
            $updated = $item;
            return $item;
        }, $store->get('services'));

        $store->put('services', $items);
        if ($updated !== null) {
            add_notification(
                $store,
                'Dịch vụ',
                (($updated['status'] ?? 'active') === 'active' ? 'Đã bật hiển thị ' : 'Đã tắt hiển thị ') . ($updated['name'] ?? '') . '.',
                '?route=services',
                'info'
            );
            $_SESSION['flash_success'] = (($updated['status'] ?? 'active') === 'active' ? 'Đã bật hiển thị ngành hàng.' : 'Đã tắt hiển thị ngành hàng.');
        }

        redirect('services');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = trim((string) ($_POST['id'] ?? ''));
        $deleted = null;
        $items = array_values(array_filter($store->get('services'), function (array $item) use ($id, &$deleted): bool {
            if (($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }

            return true;
        }));

        $store->put('services', $items);
        if ($deleted !== null) {
            add_notification($store, 'Dịch vụ', 'Đã xóa ngành hàng ' . ($deleted['name'] ?? '') . '.', '?route=services', 'danger');
        }

        $_SESSION['flash_success'] = 'Đã xóa ngành hàng.';
        redirect('services');
    }

    private function filtered(DataStore $store, string $query): array
    {
        return array_values(array_filter($store->get('services'), function (array $item) use ($query): bool {
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

    private function findById(DataStore $store, string $id): ?array
    {
        foreach ($store->get('services') as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    private function levels(array $items): array
    {
        $levels = [1 => [], 2 => [], 3 => [], 4 => []];
        foreach ($items as $item) {
            $level = (int) ($item['level'] ?? 1);
            if (! isset($levels[$level])) {
                $level = 1;
            }

            $levels[$level][] = $item;
        }

        foreach ($levels as &$levelItems) {
            usort($levelItems, fn (array $a, array $b): int => strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        }
        unset($levelItems);

        return $levels;
    }

    private function serviceTree(array $items, string $selectedId): array
    {
        $byId = [];
        $byName = [];
        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            $name = (string) ($item['name'] ?? '');
            if ($id !== '') {
                $byId[$id] = $item;
            }
            if ($name !== '') {
                $byName[$name] = $item;
            }
        }

        $selected = isset($byId[$selectedId]) ? $byId[$selectedId] : null;
        $chain = [];
        $guard = 0;
        while ($selected !== null && $guard < 8) {
            array_unshift($chain, $selected);
            $parent = (string) ($selected['parent'] ?? '');
            $selected = $parent !== '' && isset($byName[$parent]) ? $byName[$parent] : null;
            $guard++;
        }

        $parents = [''];
        foreach (array_slice($chain, 0, 3) as $item) {
            $parents[] = (string) ($item['name'] ?? '');
        }

        $columns = [];
        for ($depth = 1; $depth <= 4; $depth++) {
            $parentName = $parents[$depth - 1] ?? null;
            if ($parentName === null) {
                $columns[$depth] = [];
                continue;
            }

            $columns[$depth] = array_values(array_filter($items, fn (array $item): bool => (string) ($item['parent'] ?? '') === $parentName));
            usort($columns[$depth], fn (array $a, array $b): int => strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        }

        return [
            'columns' => $columns,
            'selectedIds' => array_column($chain, 'id'),
        ];
    }

    private function levelLabels(): array
    {
        return [
            1 => 'Ngành hàng',
            2 => 'Ngành hàng cấp 1',
            3 => 'Ngành hàng cấp 2',
            4 => 'Ngành hàng cấp 3',
        ];
    }

    private function levelButtonLabels(): array
    {
        return [
            1 => 'ngành hàng',
            2 => 'ngành hàng cấp 1',
            3 => 'ngành hàng cấp 2',
            4 => 'ngành hàng cấp 3',
        ];
    }

    private function uploadImage(string $field, string $current, string $id): string
    {
        $file = $_FILES[$field] ?? null;
        if (! is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $current;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ! is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            $_SESSION['flash_error'] = 'Không thể tải ' . (self::IMAGE_FIELDS[$field] ?? 'hình ảnh') . '.';
            $this->redirectToForm($id);
        }

        if (($file['size'] ?? 0) > 200 * 1024) {
            $_SESSION['flash_error'] = (self::IMAGE_FIELDS[$field] ?? 'Hình ảnh') . ' không được vượt quá 200KB.';
            $this->redirectToForm($id);
        }

        $mime = mime_content_type((string) $file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (! isset($extensions[$mime])) {
            $_SESSION['flash_error'] = (self::IMAGE_FIELDS[$field] ?? 'Hình ảnh') . ' chỉ hỗ trợ jpg, jpeg, png.';
            $this->redirectToForm($id);
        }

        $dir = BASE_PATH . '/public/uploads/services';
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = $field . '-' . date('YmdHis') . '-' . uid() . '.' . $extensions[$mime];
        if (! move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $filename)) {
            $_SESSION['flash_error'] = 'Không thể lưu ' . (self::IMAGE_FIELDS[$field] ?? 'hình ảnh') . '.';
            $this->redirectToForm($id);
        }

        return 'public/uploads/services/' . $filename;
    }

    private function redirectToForm(string $id): never
    {
        if ($id !== '') {
            header('Location: ?route=services.edit&id=' . rawurlencode($id), true, 303);
            exit;
        }

        redirect('services.create');
    }

    private function ensureSampleData(DataStore $store): void
    {
        $items = $store->get('services');
        $hasHierarchy = count(array_filter($items, fn (array $item): bool => isset($item['level']))) >= 6;
        if ($hasHierarchy) {
            return;
        }

        $store->put('services', [
            ['id' => 'svc01', 'name' => 'Phần mềm', 'level' => 1, 'parent' => '', 'code' => 'PM', 'price' => 0, 'status' => 'inactive', 'note' => 'Nhóm phần mềm doanh nghiệp', 'image' => '', 'icon' => ''],
            ['id' => 'svc02', 'name' => 'bERP', 'level' => 1, 'parent' => '', 'code' => 'BERP', 'price' => 0, 'status' => 'active', 'note' => 'Quản trị doanh nghiệp', 'image' => '', 'icon' => ''],
            ['id' => 'svc03', 'name' => 'bFIN', 'level' => 1, 'parent' => '', 'code' => 'BFIN', 'price' => 0, 'status' => 'active', 'note' => 'Tài chính kế toán', 'image' => '', 'icon' => ''],
            ['id' => 'svc04', 'name' => 'Mua bán Car', 'level' => 1, 'parent' => '', 'code' => 'CAR', 'price' => 0, 'status' => 'active', 'note' => 'Dịch vụ xe', 'image' => '', 'icon' => ''],
            ['id' => 'svc05', 'name' => 'Dịch vụ sửa chữa', 'level' => 1, 'parent' => '', 'code' => 'SC', 'price' => 0, 'status' => 'active', 'note' => 'Nhóm sửa chữa', 'image' => '', 'icon' => ''],
            ['id' => 'svc06', 'name' => 'Dịch vụ sửa chữa 1', 'level' => 1, 'parent' => '', 'code' => 'SC1', 'price' => 0, 'status' => 'active', 'note' => 'Nhóm sửa chữa cấp 1', 'image' => '', 'icon' => ''],
            ['id' => 'svc07', 'name' => 'Dịch vụ A', 'level' => 2, 'parent' => 'bERP', 'code' => 'DVA', 'price' => 15000000, 'status' => 'active', 'note' => 'Triển khai cơ bản', 'image' => '', 'icon' => ''],
            ['id' => 'svc08', 'name' => 'Dịch vụ B', 'level' => 2, 'parent' => 'bERP', 'code' => 'DVB', 'price' => 25000000, 'status' => 'active', 'note' => 'Triển khai nâng cao', 'image' => '', 'icon' => ''],
            ['id' => 'svc09', 'name' => 'Dịch vụ C', 'level' => 2, 'parent' => 'bFIN', 'code' => 'DVC', 'price' => 18000000, 'status' => 'active', 'note' => 'Tư vấn vận hành', 'image' => '', 'icon' => ''],
            ['id' => 'svc10', 'name' => 'Dịch vụ in ấn', 'level' => 3, 'parent' => 'Dịch vụ sửa chữa', 'code' => 'IN', 'price' => 3000000, 'status' => 'active', 'note' => 'In ấn tài liệu', 'image' => '', 'icon' => ''],
        ]);
    }
}
