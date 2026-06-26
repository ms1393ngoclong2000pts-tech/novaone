<?php

declare(strict_types=1);

final class AuthController
{
    public function showLogin(array $config): void
    {
        if (is_logged_in()) {
            redirect('dashboard');
        }

        View::render('auth/login', ['config' => $config], 'layouts/guest');
    }

    public function login(array $config, DataStore $store): void
    {
        verify_csrf();

        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $this->assertLoginAllowed();

        $demo = $config['demo_user'];
        $auth = $store->all()['_auth'] ?? [];
        $expectedHash = $auth['password_hash'] ?? password_hash($demo['password'], PASSWORD_DEFAULT);

        if ($email === $demo['email'] && password_verify($password, $expectedHash)) {
            session_regenerate_id(true);
            rotate_csrf_token();
            unset($_SESSION['login_attempts'], $_SESSION['login_blocked_until']);
            $_SESSION['user'] = [
                'name' => $auth['name'] ?? $demo['name'],
                'email' => $demo['email'],
                'role' => $demo['role'],
                'phone' => $auth['phone'] ?? '',
                'address' => $auth['address'] ?? '',
                'birthday' => $auth['birthday'] ?? '',
                'position' => $auth['position'] ?? '',
                'company' => $auth['company'] ?? 'bData co.,ltd',
                'avatar' => $auth['avatar'] ?? '',
            ];
            redirect('dashboard');
        }

        $this->recordFailedLogin();
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
            $upload = $_FILES['avatar'];

            if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $_SESSION['flash_error'] = 'Không thể upload ảnh đại diện.';
                redirect('profile');
            }

            if (($upload['size'] ?? 0) > 2 * 1024 * 1024) {
                $_SESSION['flash_error'] = 'Ảnh đại diện không được vượt quá 2MB.';
                redirect('profile');
            }

            $mime = mime_content_type($upload['tmp_name']);
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];

            if (! isset($extensions[$mime])) {
                $_SESSION['flash_error'] = 'Chỉ hỗ trợ ảnh JPG, PNG, WEBP hoặc GIF.';
                redirect('profile');
            }

            $dir = BASE_PATH . '/public/uploads/profile';
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $filename = 'avatar-' . uid() . '.' . $extensions[$mime];
            $target = $dir . '/' . $filename;

            if (! move_uploaded_file($upload['tmp_name'], $target)) {
                $_SESSION['flash_error'] = 'Không thể lưu ảnh đại diện.';
                redirect('profile');
            }

            $avatar = 'public/uploads/profile/' . $filename;
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

    private function assertLoginAllowed(): void
    {
        $blockedUntil = (int) ($_SESSION['login_blocked_until'] ?? 0);
        if ($blockedUntil > time()) {
            $_SESSION['error'] = 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau vài phút.';
            redirect('login');
        }
    }

    private function recordFailedLogin(): void
    {
        $attempts = (array) ($_SESSION['login_attempts'] ?? []);
        $now = time();
        $attempts = array_values(array_filter($attempts, fn (int $timestamp): bool => $timestamp > $now - 300));
        $attempts[] = $now;
        $_SESSION['login_attempts'] = $attempts;

        if (count($attempts) >= 5) {
            $_SESSION['login_blocked_until'] = $now + 300;
        }
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/\d/', $password) === 1;
    }
}
