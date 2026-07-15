<?php

declare(strict_types=1);

final class AuthController
{
    public function showLogin(array $config): void
    {
        if (is_logged_in()) {
            redirect('home');
        }

        View::render('auth/login', ['config' => $config], 'layouts/guest');
    }

    public function login(array $config, DataStore $store): void
    {
        verify_csrf();

        $email = strtolower(clean_text($_POST['email'] ?? '', 180));
        $password = (string) ($_POST['password'] ?? '');
        $this->assertLoginAllowed($store, $email);

        $demo = $config['demo_user'];
        $data = $store->all();
        $auth = $data['_auth'] ?? [];
        $user = $this->findLoginUser($data, $config, $email);
        $expectedHash = $user['password_hash'] ?? $auth['password_hash'] ?? password_hash($demo['password'], PASSWORD_DEFAULT);

        if ($user !== null && ($user['status'] ?? 'active') === 'active' && password_verify($password, $expectedHash)) {
            session_regenerate_id(true);
            rotate_csrf_token();
            unset($_SESSION['login_attempts'], $_SESSION['login_blocked_until']);
            $this->clearFailedLogin($store, $email);
            $_SESSION['user'] = [
                'id' => $user['id'] ?? '',
                'name' => $user['name'] ?? $auth['name'] ?? $demo['name'],
                'email' => $user['email'] ?? $demo['email'],
                'role' => $user['role'] ?? $demo['role'],
                'phone' => $auth['phone'] ?? '',
                'address' => $auth['address'] ?? '',
                'birthday' => $auth['birthday'] ?? '',
                'position' => $auth['position'] ?? '',
                'company' => $auth['company'] ?? 'bData co.,ltd',
                'avatar' => $auth['avatar'] ?? '',
            ];
            redirect('home');
        }

        $this->recordFailedLogin($store, $email);
        $_SESSION['error'] = 'Email hoặc mật khẩu không đúng.';
        redirect('login');
    }

    public function logout(): void
    {
        verify_csrf();
        unset($_SESSION['user']);
        session_regenerate_id(true);
        rotate_csrf_token();
        redirect('login');
    }

    public function profile(): void
    {
        require_auth();

        View::render('auth/profile', [
            'active' => 'profile',
            'title' => 'Cá Nhân',
            'user' => $_SESSION['user'],
        ]);
    }

    public function updateProfile(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $birthday = trim($_POST['birthday'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $company = trim($_POST['company'] ?? 'bData co.,ltd');

        if ($name === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập họ tên.';
            redirect('profile');
        }

        $avatar = $_SESSION['user']['avatar'] ?? '';
        if (! empty($_FILES['avatar']['name'])) {
            try {
                $avatar = store_uploaded_image(
                    'avatar',
                    'public/uploads/profile',
                    'avatar',
                    ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
                    2 * 1024 * 1024
                ) ?? $avatar;
            } catch (RuntimeException) {
                $_SESSION['flash_error'] = 'Ảnh đại diện không hợp lệ. Chỉ hỗ trợ JPG, PNG, WEBP và tối đa 2MB.';
                redirect('profile');
            }
        }

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['address'] = $address;
        $_SESSION['user']['birthday'] = $birthday;
        $_SESSION['user']['position'] = $position;
        $_SESSION['user']['company'] = $company;
        $_SESSION['user']['avatar'] = $avatar;

        $data = $store->all();
        $data['_auth'] = [
            ...($data['_auth'] ?? []),
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'birthday' => $birthday,
            'position' => $position,
            'company' => $company,
            'avatar' => $avatar,
        ];
        $store->save($data);
        add_notification($store, 'Cá nhân', 'Thông tin cá nhân vừa được cập nhật.', '?route=profile', 'success');

        $_SESSION['flash_success'] = 'Đã cập nhật thông tin cá nhân.';
        redirect('profile');
    }

    public function password(): void
    {
        require_auth();

        View::render('auth/password', [
            'active' => 'password',
            'title' => 'Đổi Mật Khẩu',
        ]);
    }

    public function updatePassword(array $config, DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');
        $data = $store->all();
        $expectedHash = $data['_auth']['password_hash'] ?? password_hash($config['demo_user']['password'], PASSWORD_DEFAULT);

        if (! password_verify($current, $expectedHash)) {
            $_SESSION['flash_error'] = 'Mật khẩu hiện tại không đúng.';
            redirect('password');
        }

        if (! $this->isStrongPassword($new)) {
            $_SESSION['flash_error'] = 'Mật khẩu mới phải có ít nhất 8 ký tự, gồm chữ hoa, chữ thường và số.';
            redirect('password');
        }

        if ($new !== $confirm) {
            $_SESSION['flash_error'] = 'Xác nhận mật khẩu không khớp.';
            redirect('password');
        }

        $data['_auth'] = [
            ...($data['_auth'] ?? []),
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
        ];
        $store->save($data);
        session_regenerate_id(true);
        rotate_csrf_token();
        add_notification($store, 'Bảo mật', 'Mật khẩu tài khoản vừa được thay đổi.', '?route=password', 'warning');

        $_SESSION['flash_success'] = 'Đã đổi mật khẩu thành công.';
        redirect('password');
    }

    private function assertLoginAllowed(DataStore $store, string $email): void
    {
        $blockedUntil = (int) ($_SESSION['login_blocked_until'] ?? 0);
        $persistent = $this->loginThrottleRecord($store, $email);
        if ($blockedUntil > time() || (int) ($persistent['blocked_until'] ?? 0) > time()) {
            $_SESSION['error'] = 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau vài phút.';
            redirect('login');
        }
    }

    private function recordFailedLogin(DataStore $store, string $email): void
    {
        $attempts = (array) ($_SESSION['login_attempts'] ?? []);
        $now = time();
        $attempts = array_values(array_filter($attempts, fn (int $timestamp): bool => $timestamp > $now - 300));
        $attempts[] = $now;
        $_SESSION['login_attempts'] = $attempts;

        if (count($attempts) >= 5) {
            $_SESSION['login_blocked_until'] = $now + 300;
        }

        $data = $store->all();
        $records = is_array($data['_login_attempts'] ?? null) ? $data['_login_attempts'] : [];
        $key = $this->loginThrottleKey($email);
        $record = is_array($records[$key] ?? null) ? $records[$key] : ['attempts' => []];
        $persistentAttempts = array_values(array_filter(
            array_map('intval', (array) ($record['attempts'] ?? [])),
            fn (int $timestamp): bool => $timestamp > $now - 600
        ));
        $persistentAttempts[] = $now;
        $records[$key] = [
            'attempts' => $persistentAttempts,
            'blocked_until' => count($persistentAttempts) >= 5 ? $now + 600 : (int) ($record['blocked_until'] ?? 0),
            'last_email' => substr($email, 0, 160),
            'last_ip' => $this->clientIp(),
        ];

        foreach ($records as $recordKey => $recordValue) {
            $attemptTimes = array_map('intval', (array) ($recordValue['attempts'] ?? []));
            $lastAttempt = $attemptTimes === [] ? 0 : max($attemptTimes);
            $expiresAt = max((int) ($recordValue['blocked_until'] ?? 0), $lastAttempt);
            if ($expiresAt < $now - 86400) {
                unset($records[$recordKey]);
            }
        }

        $data['_login_attempts'] = $records;
        $store->save($data);
    }

    private function clearFailedLogin(DataStore $store, string $email): void
    {
        $data = $store->all();
        $key = $this->loginThrottleKey($email);
        if (isset($data['_login_attempts'][$key])) {
            unset($data['_login_attempts'][$key]);
            $store->save($data);
        }
    }

    private function loginThrottleRecord(DataStore $store, string $email): array
    {
        $data = $store->all();
        $records = is_array($data['_login_attempts'] ?? null) ? $data['_login_attempts'] : [];
        $record = $records[$this->loginThrottleKey($email)] ?? [];

        return is_array($record) ? $record : [];
    }

    private function loginThrottleKey(string $email): string
    {
        return hash('sha256', strtolower(trim($email)) . '|' . $this->clientIp());
    }

    private function clientIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private function findLoginUser(array $data, array $config, string $email): ?array
    {
        $demo = $config['demo_user'];
        $auth = $data['_auth'] ?? [];
        foreach ((array) ($data['users'] ?? []) as $user) {
            if (strtolower((string) ($user['email'] ?? '')) !== $email) {
                continue;
            }

            if (empty($user['password_hash'])) {
                $isDemoUser = $email === strtolower((string) ($demo['email'] ?? ''));
                $fallbackPassword = (string) ($demo['password'] ?? 'admin123');
                $user['password_hash'] = $isDemoUser && ! empty($auth['password_hash'])
                    ? $auth['password_hash']
                    : password_hash($fallbackPassword, PASSWORD_DEFAULT);
            }

            return is_array($user) ? $user : null;
        }

        if ($email === strtolower((string) ($demo['email'] ?? ''))) {
            return [
                'id' => 'u_admin',
                'name' => $auth['name'] ?? $demo['name'],
                'email' => $demo['email'],
                'role' => $demo['role'],
                'status' => 'active',
                'password_hash' => $auth['password_hash'] ?? password_hash((string) ($demo['password'] ?? 'admin123'), PASSWORD_DEFAULT),
            ];
        }

        return null;
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/\d/', $password) === 1;
    }
}
