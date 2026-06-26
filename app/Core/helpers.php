<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function load_env_file(string $path): void
{
    if (! is_file($path) || ! is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        $value = trim($value, "\"'");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env_value(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => $value,
    };
}

function redirect(string $route): never
{
    $route = str_replace(["\r", "\n"], '', $route);
    if ($route === '' || str_contains($route, '://') || str_starts_with($route, '//')) {
        $route = 'dashboard';
    }

    header('Location: ?route=' . $route, true, 303);
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_auth(): void
{
    if (! is_logged_in()) {
        redirect('login');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_token'];
}

function rotate_csrf_token(): void
{
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';

    if (! hash_equals($_SESSION['_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn.');
    }
}

function safe_internal_href(string $href, string $fallback = '?route=dashboard'): string
{
    $href = str_replace(["\r", "\n"], '', trim($href));
    if ($href === '' || str_contains($href, '://') || str_starts_with($href, '//')) {
        return $fallback;
    }

    return str_starts_with($href, '?route=') ? $href : $fallback;
}

function money(mixed $value): string
{
    return number_format((float) $value, 0, ',', '.') . ' VND';
}

function label_value(string $value): string
{
    $labels = [
        'active' => 'Hoạt động',
        'locked' => 'Đã khóa',
        'on_leave' => 'Tạm nghỉ',
        'inactive' => 'Ngưng',
        'pending' => 'Chờ xử lý',
        'in_progress' => 'Đang làm',
        'completed' => 'Hoàn tất',
        'canceled' => 'Đã hủy',
        'screening' => 'Sàng lọc',
        'interview' => 'Phỏng vấn',
        'offer' => 'Đề nghị',
        'hired' => 'Đã tuyển',
        'rejected' => 'Loại',
        'customer' => 'Khách hàng',
        'supplier' => 'Nhà cung cấp',
        'new' => 'Mới',
        'vip' => 'VIP',
        'unpaid' => 'Chưa trả',
        'partial' => 'Một phần',
        'paid' => 'Đã trả',
        'draft' => 'Nháp',
        'confirmed' => 'Đã xác nhận',
        'delivered' => 'Đã giao',
        'available' => 'Ổn định',
        'low' => 'Tồn thấp',
        'out' => 'Hết hàng',
        'on_track' => 'Đúng tiến độ',
        'risk' => 'Rủi ro',
        'done' => 'Đạt',
        'packing' => 'Đóng gói',
        'shipping' => 'Đang giao',
        'delayed' => 'Trễ',
        'company' => 'Công ty',
        'department' => 'Phòng ban',
        'employee' => 'Nhân viên',
        'cash' => 'Tiền mặt',
        'bank' => 'Chuyển khoản',
        'card' => 'Thẻ',
        'ewallet' => 'Ví điện tử',
        'in_use' => 'Đang sử dụng',
        'maintenance' => 'Bảo trì',
        'meeting' => 'Lịch họp',
        'business_trip' => 'Công tác',
        'internal_event' => 'Sự kiện nội bộ',
        'reminder' => 'Nhắc việc',
        'fixed_asset' => 'Tài sản cố định',
        'meeting_room' => 'Phòng họp',
        'vehicle' => 'Phương tiện',
        'equipment' => 'Thiết bị',
        'yes' => 'Có',
        'no' => 'Không',
        'policy' => 'Chính sách',
        'system' => 'Hệ thống',
    ];

    return $labels[$value] ?? $value;
}

function badge(string $value): string
{
    $good = ['active', 'vip', 'paid', 'delivered', 'completed', 'done', 'available', 'on_track', 'hired', 'yes'];
    $bad = ['locked', 'inactive', 'canceled', 'out', 'rejected', 'delayed', 'no'];
    $class = in_array($value, $good, true) ? 'good' : (in_array($value, $bad, true) ? 'bad' : 'warn');

    return '<span class="badge ' . $class . '">' . e(label_value($value)) . '</span>';
}

function uid(): string
{
    return bin2hex(random_bytes(4));
}

function first_character(string $value): string
{
    if ($value === '') {
        return 'A';
    }

    return function_exists('mb_substr') ? mb_substr($value, 0, 1, 'UTF-8') : substr($value, 0, 1);
}

function ui_icon(string $name): string
{
    $icons = [
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/><circle cx="12" cy="12" r="10"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>',
        'pie' => '<path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9v9h9z"/><path d="M12 3a9 9 0 0 1 9 9"/>',
        'arrow' => '<circle cx="12" cy="12" r="10"/><path d="m12 16 4-4-4-4"/><path d="M8 12h8"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/>',
        'lifebuoy' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m4.93 19.07 4.24-4.24"/>',
        'cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h7.72a2 2 0 0 0 1.95-1.57L20 7H5.12"/>',
        'truck' => '<path d="M10 17h4V5H2v12h3"/><path d="M14 17h1"/><path d="M19 17h3v-5l-3-5h-5"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'warehouse' => '<path d="M3 21V9l9-6 9 6v12"/><path d="M9 21v-8h6v8"/><path d="M3 9h18"/>',
        'command' => '<path d="M18 3a3 3 0 0 0-3 3v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0 0-6"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 22h8"/><path d="M12 18v4"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
        'award' => '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.4 1.06V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 8.6 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-.6-1 1.65 1.65 0 0 0-1.06-.4H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 8.6a1.65 1.65 0 0 0-.33-1.82l-.06-.06A2 2 0 1 1 7.04 3.9l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-.6 1.65 1.65 0 0 0 .4-1.06V3a2 2 0 1 1 4 0v.09A1.65 1.65 0 0 0 15.4 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.35.28.74.6 1.06.6H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
        'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.77.6 2.6a2 2 0 0 1-.45 2.11L8 9.7a16 16 0 0 0 6.3 6.3l1.27-1.27a2 2 0 0 1 2.11-.45c.83.28 1.7.48 2.6.6A2 2 0 0 1 22 16.92z"/>',
        'menu' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m12 11 8-8"/><path d="m17 3 4 4"/><path d="m15 5 4 4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'box' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>',
    ];

    return '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['file']) . '</svg>';
}

function notifications_data(): array
{
    $path = BASE_PATH . '/storage/data.json';
    if (! file_exists($path)) {
        return [];
    }

    $data = json_decode(file_get_contents($path) ?: '{}', true);
    return is_array($data['_notifications'] ?? null) ? $data['_notifications'] : [];
}

function unread_notifications(): array
{
    return array_values(array_filter(notifications_data(), fn ($item) => empty($item['read_at'])));
}

function recent_notifications(int $limit = 6): array
{
    return array_slice(notifications_data(), 0, $limit);
}

function add_notification(DataStore $store, string $title, string $message, string $href = '?route=dashboard', string $type = 'info'): void
{
    $data = $store->all();
    $notifications = $data['_notifications'] ?? [];
    array_unshift($notifications, [
        'id' => uid(),
        'title' => $title,
        'message' => $message,
        'href' => $href,
        'type' => $type,
        'created_at' => date('Y-m-d H:i:s'),
        'read_at' => null,
    ]);

    $data['_notifications'] = array_slice($notifications, 0, 50);
    $store->save($data);
}
