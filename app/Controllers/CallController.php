<?php

declare(strict_types=1);

final class CallController
{
    public function index(DataStore $store): void
    {
        require_auth();

        View::render('calls/index', [
            'active' => 'calls',
            'title' => 'Gọi điện',
            'contacts' => $this->contacts($store),
            'templates' => $this->templates(),
            'logs' => array_slice($store->get('call_logs'), 0, 8),
        ]);
    }

    public function save(DataStore $store): void
    {
        require_auth();
        verify_csrf();

        $phone = $this->normalizePhone((string) ($_POST['phone'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $customer = trim((string) ($_POST['customer'] ?? ''));
        $contact = trim((string) ($_POST['contact'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? ''));

        if ($phone === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập số điện thoại trước khi lưu nội dung cuộc gọi.';
            redirect('calls');
        }

        if ($title === '' && $content === '') {
            $_SESSION['flash_error'] = 'Vui lòng nhập tiêu đề hoặc nội dung cuộc gọi.';
            redirect('calls');
        }

        $logs = $store->get('call_logs');
        array_unshift($logs, [
            'id' => uid(),
            'phone' => $phone,
            'title' => $title !== '' ? $title : 'Cuộc gọi',
            'content' => $content,
            'customer' => $customer,
            'contact' => $contact,
            'type' => $type,
            'created_by' => $_SESSION['user']['name'] ?? 'Admin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $store->put('call_logs', array_slice($logs, 0, 100));

        add_notification($store, 'Cuộc gọi', 'Đã lưu nội dung cuộc gọi tới ' . $phone . '.', '?route=calls', 'success');
        $_SESSION['flash_success'] = 'Đã lưu nội dung cuộc gọi.';
        redirect('calls');
    }

    private function contacts(DataStore $store): array
    {
        $data = $store->all();
        $contacts = [];

        foreach ($data['customers'] ?? [] as $item) {
            $this->pushContact($contacts, [
                'source' => 'Khách hàng',
                'customer' => (string) ($item['name'] ?? ''),
                'name' => (string) ($item['owner'] ?? $item['name'] ?? ''),
                'phone' => (string) ($item['phone'] ?? ''),
                'type' => (string) ($item['type'] ?? 'customer'),
            ]);
        }

        foreach ($data['suppliers'] ?? [] as $item) {
            $this->pushContact($contacts, [
                'source' => 'Nhà cung cấp',
                'customer' => (string) ($item['name'] ?? ''),
                'name' => (string) ($item['contact_person'] ?? $item['name'] ?? ''),
                'phone' => (string) ($item['phone'] ?? ''),
                'type' => 'supplier',
            ]);
        }

        foreach ($data['machine_warehouses'] ?? [] as $item) {
            $this->pushContact($contacts, [
                'source' => 'Kho máy',
                'customer' => (string) ($item['name'] ?? ''),
                'name' => (string) ($item['manager'] ?? $item['name'] ?? ''),
                'phone' => (string) ($item['phone'] ?? ''),
                'type' => 'warehouse',
            ]);
        }

        $authPhone = (string) ($data['_auth']['phone'] ?? $_SESSION['user']['phone'] ?? '');
        $this->pushContact($contacts, [
            'source' => 'Nội bộ',
            'customer' => $_SESSION['user']['company'] ?? 'Novaone',
            'name' => $_SESSION['user']['name'] ?? 'Admin',
            'phone' => $authPhone,
            'type' => 'internal',
        ]);

        usort($contacts, fn (array $a, array $b): int => strcmp($a['customer'] . $a['name'], $b['customer'] . $b['name']));
        return $contacts;
    }

    private function pushContact(array &$contacts, array $contact): void
    {
        $phone = $this->normalizePhone((string) ($contact['phone'] ?? ''));
        if ($phone === '' || strlen(preg_replace('/\D+/', '', $phone) ?? '') < 6) {
            return;
        }

        $key = $phone . '|' . ($contact['name'] ?? '');
        $contacts[$key] = [
            ...$contact,
            'phone' => $phone,
        ];
    }

    private function normalizePhone(string $value): string
    {
        return trim(preg_replace('/[^0-9+*#]/', '', $value) ?? '');
    }

    private function templates(): array
    {
        return [
            ['id' => 'intro', 'label' => 'Tư vấn lần đầu', 'title' => 'Tư vấn dịch vụ Novaone', 'content' => 'Chào khách hàng, giới thiệu nhanh giải pháp và ghi nhận nhu cầu triển khai.'],
            ['id' => 'follow_up', 'label' => 'Chăm sóc sau báo giá', 'title' => 'Theo dõi báo giá', 'content' => 'Xác nhận khách hàng đã nhận báo giá, hỏi phản hồi và bước tiếp theo.'],
            ['id' => 'support', 'label' => 'Hỗ trợ kỹ thuật', 'title' => 'Hỗ trợ xử lý vấn đề', 'content' => 'Ghi nhận vấn đề, mức độ ảnh hưởng, thời hạn cần xử lý và người phụ trách.'],
            ['id' => 'payment', 'label' => 'Nhắc thanh toán', 'title' => 'Nhắc lịch thanh toán', 'content' => 'Xác nhận công nợ, thời hạn thanh toán và phương thức chuyển khoản.'],
        ];
    }
}
