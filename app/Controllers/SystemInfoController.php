<?php

declare(strict_types=1);

final class SystemInfoController
{
    public function index(DataStore $store): void
    {
        require_auth();
        require_permission('settings', 'view');

        $data = $store->all();
        $sections = $this->sections();
        $values = $this->settingValues($data['settings'] ?? [], $sections);

        View::render('settings/index', [
            'active' => 'settings',
            'title' => 'Thông tin hệ thống',
            'sections' => $sections,
            'values' => $values,
            'summary' => $this->summary($data, $values),
            'departments' => $this->uniqueValues($data['employees'] ?? [], 'department'),
            'positions' => $this->uniqueValues($data['employees'] ?? [], 'position'),
            'roles' => $this->roles($data),
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();
        require_permission('settings', 'update');

        $data = $store->all();
        $sections = $this->sections();
        $definitions = $this->flatDefinitions($sections);
        $knownKeys = array_keys($definitions);
        $existing = $data['settings'] ?? [];
        $rowsByKey = [];
        $unknownRows = [];

        foreach ($existing as $row) {
            $key = (string) ($row['key'] ?? '');
            if ($key !== '' && isset($definitions[$key])) {
                $rowsByKey[$key] = $row;
                continue;
            }
            $unknownRows[] = $row;
        }

        $savedRows = [];
        foreach ($knownKeys as $key) {
            $field = $definitions[$key];
            $value = $this->cleanValue($_POST[$key] ?? '', $field);
            $row = $rowsByKey[$key] ?? ['id' => 'set_' . uid()];
            $row['key'] = $key;
            $row['value'] = $value;
            $row['group'] = $field['group'];
            $row['status'] = $field['status'] ?? 'active';
            $savedRows[] = $row;
        }

        $data['settings'] = array_values(array_merge($savedRows, $unknownRows));
        $store->save($data);

        add_notification(
            $store,
            'Quản lý thông tin',
            'Đã cập nhật thông tin hệ thống và cấu hình vận hành.',
            '?route=settings',
            'success'
        );
        $_SESSION['flash_success'] = 'Đã lưu thông tin hệ thống.';
        redirect('settings');
    }

    private function sections(): array
    {
        return [
            'company' => [
                'title' => 'Thông tin công ty',
                'description' => 'Thông tin hiển thị trên hồ sơ, báo cáo, phiếu và chứng từ.',
                'icon' => 'building',
                'fields' => [
                    ['key' => 'company_name', 'label' => 'Tên công ty', 'type' => 'text', 'default' => 'NovaOne', 'required' => true],
                    ['key' => 'tax_code', 'label' => 'Mã số thuế', 'type' => 'text', 'default' => ''],
                    ['key' => 'company_email', 'label' => 'Email công ty', 'type' => 'email', 'default' => 'contact@novaone.vn'],
                    ['key' => 'company_phone', 'label' => 'Số điện thoại', 'type' => 'text', 'default' => '0333870736'],
                    ['key' => 'company_address', 'label' => 'Địa chỉ', 'type' => 'text', 'default' => '207 An Dương Vương'],
                    ['key' => 'website', 'label' => 'Website', 'type' => 'url', 'default' => 'https://novaone.vn'],
                    ['key' => 'representative', 'label' => 'Người đại diện', 'type' => 'text', 'default' => 'Trần Ngọc Long'],
                    ['key' => 'business_field', 'label' => 'Lĩnh vực hoạt động', 'type' => 'textarea', 'default' => 'Quản trị doanh nghiệp, nhân sự, kinh doanh, kho và báo cáo.'],
                ],
            ],
            'bank' => [
                'title' => 'Thông tin ngân hàng',
                'description' => 'Dùng cho thanh toán, báo giá, hợp đồng và phiếu bán hàng.',
                'icon' => 'briefcase',
                'fields' => [
                    ['key' => 'bank_name', 'label' => 'Ngân hàng', 'type' => 'text', 'default' => ''],
                    ['key' => 'bank_account', 'label' => 'Số tài khoản', 'type' => 'text', 'default' => ''],
                    ['key' => 'bank_account_name', 'label' => 'Chủ tài khoản', 'type' => 'text', 'default' => 'NovaOne'],
                    ['key' => 'bank_branch', 'label' => 'Chi nhánh', 'type' => 'text', 'default' => ''],
                ],
            ],
            'system' => [
                'title' => 'Cấu hình vận hành',
                'description' => 'Các tham số mặc định giúp hệ thống chạy thống nhất.',
                'icon' => 'settings',
                'fields' => [
                    ['key' => 'timezone', 'label' => 'Múi giờ', 'type' => 'select', 'default' => 'Asia/Saigon', 'options' => ['Asia/Saigon', 'UTC']],
                    ['key' => 'currency', 'label' => 'Tiền tệ', 'type' => 'select', 'default' => 'VND', 'options' => ['VND', 'USD']],
                    ['key' => 'language', 'label' => 'Ngôn ngữ', 'type' => 'select', 'default' => 'vi', 'options' => ['vi', 'en'], 'option_labels' => ['vi' => 'Tiếng Việt', 'en' => 'English']],
                    ['key' => 'low_stock_alert', 'label' => 'Cảnh báo tồn kho', 'type' => 'select', 'default' => 'enabled', 'options' => ['enabled', 'disabled'], 'option_labels' => ['enabled' => 'Bật', 'disabled' => 'Tắt']],
                    ['key' => 'session_timeout', 'label' => 'Thời gian phiên đăng nhập (phút)', 'type' => 'number', 'default' => '120'],
                    ['key' => 'maintenance_mode', 'label' => 'Chế độ bảo trì', 'type' => 'select', 'default' => 'disabled', 'options' => ['disabled', 'enabled'], 'option_labels' => ['disabled' => 'Tắt', 'enabled' => 'Bật'], 'status' => 'policy'],
                ],
            ],
            'policy' => [
                'title' => 'Chính sách hệ thống',
                'description' => 'Thiết lập dùng cho nhân sự, chấm công và quy trình phê duyệt.',
                'icon' => 'check',
                'fields' => [
                    ['key' => 'work_start', 'label' => 'Giờ bắt đầu làm việc', 'type' => 'time', 'default' => '08:00'],
                    ['key' => 'work_end', 'label' => 'Giờ kết thúc làm việc', 'type' => 'time', 'default' => '17:30'],
                    ['key' => 'work_days', 'label' => 'Ngày làm việc', 'type' => 'text', 'default' => 'Thứ 2 - Thứ 7'],
                    ['key' => 'default_contract', 'label' => 'Loại hợp đồng mặc định', 'type' => 'select', 'default' => 'official', 'options' => ['probation', 'official', 'long_term'], 'option_labels' => ['probation' => 'Thử việc', 'official' => 'Chính thức', 'long_term' => 'Dài hạn']],
                    ['key' => 'approval_flow', 'label' => 'Quy trình phê duyệt', 'type' => 'textarea', 'default' => 'Nhân viên tạo phiếu, quản lý duyệt, admin xác nhận và lưu lịch sử thao tác.'],
                ],
            ],
        ];
    }

    private function settingValues(array $rows, array $sections): array
    {
        $values = [];
        foreach ($this->flatDefinitions($sections) as $key => $field) {
            $values[$key] = (string) ($field['default'] ?? '');
        }

        foreach ($rows as $row) {
            $key = (string) ($row['key'] ?? '');
            if ($key !== '' && array_key_exists($key, $values)) {
                $values[$key] = (string) ($row['value'] ?? '');
            }
        }

        return $values;
    }

    private function flatDefinitions(array $sections): array
    {
        $definitions = [];
        foreach ($sections as $group => $section) {
            foreach ($section['fields'] as $field) {
                $field['group'] = $group;
                $definitions[$field['key']] = $field;
            }
        }

        return $definitions;
    }

    private function cleanValue(mixed $value, array $field): string
    {
        $value = trim((string) $value);
        if (($field['type'] ?? '') === 'number') {
            return (string) max(0, (int) $value);
        }

        if (($field['type'] ?? '') === 'select') {
            $options = $field['options'] ?? [];
            return in_array($value, $options, true) ? $value : (string) ($field['default'] ?? '');
        }

        return $value;
    }

    private function summary(array $data, array $values): array
    {
        return [
            ['label' => 'Nhân sự', 'value' => (string) count($data['employees'] ?? []), 'hint' => 'Hồ sơ đang quản lý', 'tone' => 'blue'],
            ['label' => 'Phòng ban', 'value' => (string) count($this->uniqueValues($data['employees'] ?? [], 'department')), 'hint' => 'Từ danh sách nhân sự', 'tone' => 'green'],
            ['label' => 'Vai trò', 'value' => (string) count($this->roles($data)), 'hint' => 'Dùng cho phân quyền', 'tone' => 'orange'],
            ['label' => 'Tiền tệ', 'value' => $values['currency'] ?? 'VND', 'hint' => 'Áp dụng cho bán hàng', 'tone' => 'purple'],
        ];
    }

    private function uniqueValues(array $rows, string $key): array
    {
        $values = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                $values[] = $value;
            }
        }

        sort($values);
        return array_values(array_unique($values));
    }

    private function roles(array $data): array
    {
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

        sort($roles);
        return array_values(array_unique($roles));
    }
}
