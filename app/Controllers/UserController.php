<?php

declare(strict_types=1);

final class UserController
{
    private const ROLES = ['Admin', 'Manager', 'HR', 'Sales', 'Warehouse'];
    private const STATUSES = ['active' => 'Hoạt động', 'locked' => 'Đã khóa'];

    public function index(DataStore $store): void
    {
        require_auth();
        require_permission('users', 'view');

        $query = clean_text($_GET['q'] ?? '', 120);
        $role = clean_text($_GET['role'] ?? '', 40);
        $status = clean_text($_GET['status'] ?? '', 40);
        $users = $this->users($store);

        $items = array_values(array_filter($users, function (array $user) use ($query, $role, $status): bool {
            if ($role !== '' && strcasecmp((string) ($user['role'] ?? ''), $role) !== 0) {
                return false;
            }
            if ($status !== '' && (string) ($user['status'] ?? 'active') !== $status) {
                return false;
            }
            if ($query === '') {
                return true;
            }

            $haystack = implode(' ', [
                $user['name'] ?? '',
                $user['email'] ?? '',
                $user['role'] ?? '',
                $user['status'] ?? '',
            ]);
            return str_contains(strtolower($haystack), strtolower($query));
        }));

        usort($items, fn (array $a, array $b): int => strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        View::render('users/index', [
            'active' => 'users',
            'title' => 'Quản lý tài khoản',
            'items' => $items,
            'allUsers' => $users,
            'roles' => $this->roles($store),
            'statuses' => self::STATUSES,
            'query' => $query,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $id = clean_text($_POST['id'] ?? '', 80);
        require_permission('users', $id === '' ? 'create' : 'update');

        $name = clean_text($_POST['name'] ?? '', 160);
        $email = strtolower(clean_text($_POST['email'] ?? '', 180));
        $role = clean_text($_POST['role'] ?? 'Manager', 60);
        $status = array_key_exists($_POST['status'] ?? '', self::STATUSES) ? (string) $_POST['status'] : 'active';
        $password = (string) ($_POST['password'] ?? '');
        $isUpdate = $id !== '';

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Vui lòng nhập họ tên và email hợp lệ.';
            redirect('users');
        }

        if (! in_array($role, $this->roles($store), true)) {
            $_SESSION['flash_error'] = 'Vai trò không hợp lệ.';
            redirect('users');
        }

        if (! $isUpdate && ! $this->isStrongPassword($password)) {
            $_SESSION['flash_error'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
            redirect('users');
        }

        if ($isUpdate && $password !== '' && ! $this->isStrongPassword($password)) {
            $_SESSION['flash_error'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            redirect('users');
        }

        $items = $this->users($store);
        foreach ($items as $item) {
            if (strtolower((string) ($item['email'] ?? '')) === $email && (string) ($item['id'] ?? '') !== $id) {
                $_SESSION['flash_error'] = 'Email này đã tồn tại.';
                redirect('users');
            }
        }

        $current = $isUpdate ? $this->find($items, $id) : null;
        if ($isUpdate && $current === null) {
            $_SESSION['flash_error'] = 'Không tìm thấy tài khoản cần cập nhật.';
            redirect('users');
        }

        $payload = [
            'id' => $isUpdate ? $id : 'u_' . uid(),
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($isUpdate) {
            $payload = [...$current, ...$payload];
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
        }

        if ($password !== '') {
            $payload['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($isUpdate) {
            $items = array_map(fn (array $item): array => ((string) ($item['id'] ?? '') === $id ? $payload : $item), $items);
        } else {
            array_unshift($items, $payload);
        }

        $data = $store->all();
        $data['users'] = array_values($items);
        $store->save($data);

        add_notification($store, 'Tài khoản', ($isUpdate ? 'Đã cập nhật tài khoản ' : 'Đã tạo tài khoản ') . $name . '.', '?route=users', $isUpdate ? 'info' : 'success');
        $_SESSION['flash_success'] = $isUpdate ? 'Đã cập nhật tài khoản.' : 'Đã tạo tài khoản mới.';
        redirect('users');
    }

    public function delete(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        require_permission('users', 'delete');

        $id = clean_text($_POST['id'] ?? '', 80);
        if ($id === (string) ($_SESSION['user']['id'] ?? '')) {
            $_SESSION['flash_error'] = 'Không thể xóa tài khoản đang đăng nhập.';
            redirect('users');
        }

        $deleted = null;
        $items = array_values(array_filter($this->users($store), function (array $item) use ($id, &$deleted): bool {
            if ((string) ($item['id'] ?? '') === $id) {
                $deleted = $item;
                return false;
            }
            return true;
        }));

        $data = $store->all();
        $data['users'] = $items;
        $store->save($data);

        if ($deleted !== null) {
            add_notification($store, 'Tài khoản', 'Đã xóa tài khoản ' . ($deleted['name'] ?? '') . '.', '?route=users', 'danger');
        }
        $_SESSION['flash_success'] = 'Đã xóa tài khoản.';
        redirect('users');
    }

    private function users(DataStore $store): array
    {
        $data = $store->all();
        $users = is_array($data['users'] ?? null) ? $data['users'] : [];
        $demo = require BASE_PATH . '/config/app.php';
        $demoUser = $demo['demo_user'] ?? [];

        $hasDemo = false;
        foreach ($users as &$user) {
            $user['status'] = $user['status'] ?? 'active';
            if (strtolower((string) ($user['email'] ?? '')) === strtolower((string) ($demoUser['email'] ?? ''))) {
                $hasDemo = true;
                if (empty($user['password_hash'])) {
                    $auth = $data['_auth'] ?? [];
                    $user['password_hash'] = $auth['password_hash'] ?? password_hash((string) ($demoUser['password'] ?? 'admin123'), PASSWORD_DEFAULT);
                }
            }
        }
        unset($user);

        if (! $hasDemo && ! empty($demoUser['email'])) {
            array_unshift($users, [
                'id' => 'u_admin',
                'name' => $demoUser['name'] ?? 'Admin Novaone',
                'email' => $demoUser['email'],
                'role' => $demoUser['role'] ?? 'Admin',
                'status' => 'active',
                'password_hash' => password_hash((string) ($demoUser['password'] ?? 'admin123'), PASSWORD_DEFAULT),
            ]);
        }

        return array_values($users);
    }

    private function roles(DataStore $store): array
    {
        $roles = self::ROLES;
        foreach ($store->get('users') as $user) {
            $role = clean_text($user['role'] ?? '', 60);
            if ($role !== '') {
                $roles[] = $role;
            }
        }

        return array_values(array_unique($roles));
    }

    private function find(array $items, string $id): ?array
    {
        foreach ($items as $item) {
            if ((string) ($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 6;
    }
}
