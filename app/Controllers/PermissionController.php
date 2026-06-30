<?php

declare(strict_types=1);

final class PermissionController
{
    public function index(DataStore $store): void
    {
        require_auth();

        $roles = $this->roles($store);
        $selectedRole = trim((string) ($_GET['role'] ?? current_user_role()));
        if (! in_array($selectedRole, $roles, true)) {
            $selectedRole = $roles[0] ?? 'Admin';
        }

        View::render('permissions/index', [
            'active' => 'permissions',
            'title' => 'Phân quyền',
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'modules' => permission_modules(),
            'matrix' => role_permissions($selectedRole),
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $role = trim((string) ($_POST['role'] ?? ''));
        if ($role === '' || strcasecmp($role, 'Admin') === 0) {
            $_SESSION['flash_error'] = 'Không thể chỉnh sửa quyền Admin.';
            redirect('permissions');
        }

        $posted = is_array($_POST['permissions'] ?? null) ? $_POST['permissions'] : [];
        $data = $store->all();
        $rows = array_values(array_filter($data['permissions'] ?? [], fn (array $item): bool => strcasecmp((string) ($item['role'] ?? ''), $role) !== 0));

        foreach (permission_modules() as $module => $meta) {
            $actions = is_array($posted[$module] ?? null) ? $posted[$module] : [];
            $rows[] = [
                'id' => 'pm_' . uid(),
                'role' => $role,
                'module' => $module,
                'module_label' => $meta['label'],
                'can_view' => isset($actions['view']) ? 'yes' : 'no',
                'can_create' => isset($actions['create']) ? 'yes' : 'no',
                'can_update' => isset($actions['update']) ? 'yes' : 'no',
                'can_delete' => isset($actions['delete']) ? 'yes' : 'no',
            ];
        }

        $data['permissions'] = $rows;
        $store->save($data);
        add_notification($store, 'Phân quyền', 'Đã cập nhật quyền cho vai trò ' . $role . '.', '?route=permissions&role=' . urlencode($role), 'success');

        $_SESSION['flash_success'] = 'Đã lưu phân quyền cho vai trò ' . $role . '.';
        redirect('permissions&role=' . urlencode($role));
    }

    private function roles(DataStore $store): array
    {
        $data = $store->all();
        $roles = ['Admin', 'Manager', 'HR', 'Sales', 'Warehouse'];
        foreach ($data['users'] ?? [] as $user) {
            $role = trim((string) ($user['role'] ?? ''));
            if ($role !== '') {
                $roles[] = $role;
            }
        }
        foreach ($data['permissions'] ?? [] as $permission) {
            $role = trim((string) ($permission['role'] ?? ''));
            if ($role !== '') {
                $roles[] = $role;
            }
        }

        return array_values(array_unique($roles));
    }
}
