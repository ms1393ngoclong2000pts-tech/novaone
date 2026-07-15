<?php

declare(strict_types=1);

final class ApiController
{
    public function login(array $config, DataStore $store): never
    {
        $payload = $this->payload();
        $email = strtolower(clean_text($payload['email'] ?? '', 180));
        $password = (string) ($payload['password'] ?? '');
        $data = $store->all();
        $user = $this->findUser($data, $config, $email);

        if ($user === null || ($user['status'] ?? 'active') !== 'active' || ! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            $this->json(['ok' => false, 'message' => 'Email hoặc mật khẩu không đúng.'], 401);
        }

        $token = bin2hex(random_bytes(32));
        $data['_api_tokens'][$token] = [
            'user_id' => $user['id'] ?? '',
            'email' => $user['email'] ?? $email,
            'role' => $user['role'] ?? 'Manager',
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 14),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        $data['_api_tokens'] = $this->cleanTokens($data['_api_tokens']);
        $store->save($data);

        unset($user['password_hash']);
        $this->json(['ok' => true, 'token' => $token, 'user' => $user]);
    }

    public function me(DataStore $store): never
    {
        [$user] = $this->requireToken($store);
        unset($user['password_hash']);
        $this->json(['ok' => true, 'user' => $user]);
    }

    public function dashboard(DataStore $store): never
    {
        [, $token] = $this->requireToken($store);
        $role = (string) ($token['role'] ?? 'Manager');
        $data = $store->all();
        $this->json([
            'ok' => true,
            'role' => $role,
            'summary' => [
                'employees' => count((array) ($data['employees'] ?? [])),
                'projects' => count((array) ($data['projects'] ?? [])),
                'orders' => count((array) ($data['sales_orders'] ?? [])),
                'products' => count((array) ($data['products'] ?? [])),
                'devices' => count((array) ($data['equipment_devices'] ?? [])),
                'unread_notifications' => count(array_filter((array) ($data['_notifications'] ?? []), fn (array $item): bool => empty($item['read_at']))),
            ],
        ]);
    }

    public function resource(DataStore $store): never
    {
        [, $token] = $this->requireToken($store);
        $name = clean_text($_GET['name'] ?? '', 80);
        $allowed = [
            'employees' => 'employees',
            'projects' => 'projects',
            'work_items' => 'work_items',
            'products' => 'products',
            'sales_orders' => 'sales_orders',
            'sales_targets' => 'sales_targets',
            'sales_receipts' => 'sales_receipts',
            'suppliers' => 'suppliers',
            'services' => 'services',
            'equipment_devices' => 'equipment_devices',
        ];
        if (! isset($allowed[$name])) {
            $this->json(['ok' => false, 'message' => 'Resource không hợp lệ.'], 404);
        }

        $role = (string) ($token['role'] ?? 'Manager');
        if (strcasecmp($role, 'Admin') !== 0 && ! (role_permissions($role)[permission_module_key($name)]['view'] ?? false)) {
            $this->json(['ok' => false, 'message' => 'Không có quyền truy cập dữ liệu này.'], 403);
        }

        $items = array_slice($store->get($name), 0, 100);
        $this->json(['ok' => true, 'name' => $name, 'items' => $items]);
    }

    public function notifications(DataStore $store): never
    {
        $this->requireToken($store);
        $data = $store->all();
        $items = array_slice((array) ($data['_notifications'] ?? []), 0, 50);
        $this->json([
            'ok' => true,
            'unread' => count(array_filter($items, fn (array $item): bool => empty($item['read_at']))),
            'items' => $items,
        ]);
    }

    private function requireToken(DataStore $store): array
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (! preg_match('/Bearer\s+([a-f0-9]{64})/i', $header, $matches)) {
            $this->json(['ok' => false, 'message' => 'Thiếu API token.'], 401);
        }

        $data = $store->all();
        $tokens = $this->cleanTokens((array) ($data['_api_tokens'] ?? []));
        $token = $tokens[$matches[1]] ?? null;
        if (! is_array($token)) {
            $this->json(['ok' => false, 'message' => 'API token không hợp lệ hoặc đã hết hạn.'], 401);
        }

        $user = $this->findUserByEmail($data, (string) ($token['email'] ?? ''));
        if ($user === null || ($user['status'] ?? 'active') !== 'active') {
            $this->json(['ok' => false, 'message' => 'Tài khoản không còn hoạt động.'], 403);
        }

        if ($tokens !== ($data['_api_tokens'] ?? [])) {
            $data['_api_tokens'] = $tokens;
            $store->save($data);
        }

        return [$user, $token];
    }

    private function findUser(array $data, array $config, string $email): ?array
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

    private function findUserByEmail(array $data, string $email): ?array
    {
        foreach ((array) ($data['users'] ?? []) as $user) {
            if (strtolower((string) ($user['email'] ?? '')) === strtolower($email)) {
                return is_array($user) ? $user : null;
            }
        }

        return null;
    }

    private function cleanTokens(array $tokens): array
    {
        $now = time();
        return array_filter($tokens, fn (array $token): bool => strtotime((string) ($token['expires_at'] ?? '')) > $now);
    }

    private function payload(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $_POST;
    }

    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
