<?php

declare(strict_types=1);

final class ResourceController
{
    public function index(DataStore $store, array $schemas): void
    {
        require_auth();

        $name = $_GET['name'] ?? 'employees';
        $this->renderResource($store, $schemas, (string) $name, 'resource');
    }

    public function page(DataStore $store, array $schemas, string $name, string $route, ?string $active = null): void
    {
        require_auth();
        $this->renderResource($store, $schemas, $name, $route, $active);
    }

    private function renderResource(DataStore $store, array $schemas, string $name, string $route, ?string $active = null): void
    {
        abort_unless(isset($schemas[$name]));
        require_permission(permission_module_key((string) $name), 'view');
        $query = trim($_GET['q'] ?? '');
        $items = $store->get($name);

        if ($query !== '') {
            $lower = fn (string $value): string => function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value);
            $items = array_values(array_filter($items, fn ($item) => str_contains(
                $lower(implode(' ', array_map('strval', $item))),
                $lower($query)
            )));
        }

        View::render('resources/index', [
            'active' => $active ?? $name,
            'title' => $schemas[$name]['title'],
            'name' => $name,
            'routeName' => $route,
            'schema' => $schemas[$name],
            'items' => $items,
            'query' => $query,
        ]);
    }

    public function save(DataStore $store, array $schemas): void
    {
        require_auth();
        verify_csrf();

        $name = $_POST['_resource'] ?? '';
        abort_unless(isset($schemas[$name]));
        $module = permission_module_key((string) $name);

        $items = $store->get($name);
        $id = $_POST['id'] ?? '';
        require_permission($module, $id !== '' ? 'update' : 'create');
        $payload = ['id' => $id !== '' ? $id : uid()];

        foreach ($schemas[$name]['fields'] as $field) {
            $value = $_POST[$field['name']] ?? '';
            $payload[$field['name']] = $field['type'] === 'number' ? (float) $value : trim((string) $value);
        }

        $isUpdate = $id !== '';
        if ($isUpdate) {
            $items = array_map(fn ($item) => $item['id'] === $id ? $payload : $item, $items);
        } else {
            array_unshift($items, $payload);
        }

        $store->put($name, $items);
        $returnRoute = $this->safeReturnRoute((string) ($_POST['_return'] ?? ''));
        $fallbackRoute = $name === 'employees' ? 'employees' : 'dashboard';
        add_notification(
            $store,
            $schemas[$name]['title'] ?? 'Dữ liệu',
            ($isUpdate ? 'Đã cập nhật' : 'Đã thêm mới') . ' một bản ghi trong ' . ($schemas[$name]['title'] ?? $name) . '.',
            '?route=' . ($returnRoute !== '' ? $returnRoute : $fallbackRoute),
            $isUpdate ? 'info' : 'success'
        );
        redirect($returnRoute !== '' ? $returnRoute : $fallbackRoute);
    }

    public function delete(DataStore $store, array $schemas): void
    {
        require_auth();
        verify_csrf();

        $name = $_POST['_resource'] ?? '';
        $id = $_POST['id'] ?? '';
        abort_unless(isset($schemas[$name]));
        require_permission(permission_module_key((string) $name), 'delete');

        $items = array_values(array_filter($store->get($name), fn ($item) => $item['id'] !== $id));
        $store->put($name, $items);
        $returnRoute = $this->safeReturnRoute((string) ($_POST['_return'] ?? ''));
        $fallbackRoute = $name === 'employees' ? 'employees' : 'dashboard';
        add_notification(
            $store,
            $schemas[$name]['title'] ?? 'Dữ liệu',
            'Đã xóa một bản ghi trong ' . ($schemas[$name]['title'] ?? $name) . '.',
            '?route=' . ($returnRoute !== '' ? $returnRoute : $fallbackRoute),
            'danger'
        );
        redirect($returnRoute !== '' ? $returnRoute : $fallbackRoute);
    }

    public function reset(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        require_permission('permissions', 'delete');

        $store->reset();
        redirect('dashboard');
    }

    private function safeReturnRoute(string $route): string
    {
        return preg_match('/^[a-zA-Z0-9_.-]+$/', $route) === 1 ? $route : '';
    }
}

function abort_unless(bool $condition): void
{
    if (! $condition) {
        http_response_code(404);
        exit('Not found.');
    }
}
