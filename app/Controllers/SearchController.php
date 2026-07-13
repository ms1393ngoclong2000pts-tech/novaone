<?php

declare(strict_types=1);

final class SearchController
{
    public function index(DataStore $store, array $schemas): void
    {
        require_auth();

        $query = trim((string) ($_GET['q'] ?? ''));
        $group = trim((string) ($_GET['group'] ?? ''));
        $features = array_values(array_filter($this->features(), fn (array $feature): bool => can_access_route(href_route((string) ($feature['href'] ?? '')))));
        $results = $features;

        if ($group !== '') {
            $results = array_values(array_filter($results, fn (array $feature): bool => (string) ($feature['group'] ?? '') === $group));
        }

        if ($query !== '') {
            $needle = $this->normalize($query);
            $results = array_values(array_filter($results, fn ($feature) => str_contains(
                $this->normalize($feature['label'] . ' ' . $feature['group'] . ' ' . $feature['keywords']),
                $needle
            )));
        }

        View::render('search/index', [
            'active' => 'search',
            'title' => 'Tìm kiếm',
            'query' => $query,
            'group' => $group,
            'groups' => $this->groups($features),
            'results' => $results,
        ]);
    }

    private function features(): array
    {
        return [
            ['label' => 'Nhân sự', 'group' => 'Quản lý nhân sự', 'icon' => 'users', 'href' => '?route=employees', 'keywords' => 'nhan vien ho so phong ban'],
            ['label' => 'Hợp đồng lao động', 'group' => 'Quản lý nhân sự', 'icon' => 'file', 'href' => '?route=contracts', 'keywords' => 'hop dong lao dong luong ngay bat dau ket thuc'],
            ['label' => 'Chấm công', 'group' => 'Quản lý nhân sự', 'icon' => 'calendar', 'href' => '?route=attendance', 'keywords' => 'cham cong xu ly cong quan ly may tong cong vi pham'],
            ['label' => 'Bảng lương', 'group' => 'Quản lý nhân sự', 'icon' => 'file', 'href' => '?route=payrolls', 'keywords' => 'bang luong luong co dinh luong theo ca'],
            ['label' => 'Bảo hiểm xã hội', 'group' => 'Quản lý nhân sự', 'icon' => 'award', 'href' => '?route=social-insurance', 'keywords' => 'bao hiem xa hoi bhxh'],
            ['label' => 'Phiếu yêu cầu', 'group' => 'Quản lý nhân sự', 'icon' => 'file', 'href' => '?route=requests', 'keywords' => 'phieu yeu cau nghi phep ung tien tang ca'],
            ['label' => 'Danh sách vi phạm', 'group' => 'Quản lý nhân sự', 'icon' => 'info', 'href' => '?route=violations', 'keywords' => 'vi pham ky luat'],
            ['label' => 'Danh sách khen thưởng', 'group' => 'Quản lý nhân sự', 'icon' => 'award', 'href' => '?route=rewards', 'keywords' => 'khen thuong nhan vien'],
            ['label' => 'Dự án', 'group' => 'Công việc', 'icon' => 'briefcase', 'href' => '?route=projects', 'keywords' => 'du an cong ty tien do'],
            ['label' => 'Danh sách công việc', 'group' => 'Công việc', 'icon' => 'check', 'href' => '?route=work-items', 'keywords' => 'task cong viec giao viec tien do'],
            ['label' => 'Báo cáo hằng ngày', 'group' => 'Công việc', 'icon' => 'file', 'href' => '?route=daily-reports', 'keywords' => 'bao cao hang ngay gio lam chi tiet'],
            ['label' => 'Đào tạo', 'group' => 'Quản lý nhân sự', 'icon' => 'book', 'href' => '?route=training', 'keywords' => 'dao tao khoa hoc tai lieu hoc tap giang vien tien do'],
            ['label' => 'Báo cáo đào tạo', 'group' => 'Báo cáo', 'icon' => 'file', 'href' => '?route=training-reports', 'keywords' => 'bao cao dao tao tien do khoa hoc hoan thanh'],
            ['label' => 'Phiếu yêu cầu tuyển dụng', 'group' => 'Quản lý hệ thống', 'icon' => 'monitor', 'href' => '?route=recruitment-requests', 'keywords' => 'phieu yeu cau tuyen dung ung vien phe duyet chi phi'],
            ['label' => 'Nhà cung cấp', 'group' => 'Kinh doanh', 'icon' => 'briefcase', 'href' => '?route=suppliers', 'keywords' => 'nha cung cap doi tac cong no'],
            ['label' => 'Đơn hàng', 'group' => 'Kinh doanh', 'icon' => 'cart', 'href' => '?route=sales-orders', 'keywords' => 'ban hang don hang bao gia hop dong nghiem thu co hoi'],
            ['label' => 'Chỉ tiêu tháng', 'group' => 'Kinh doanh', 'icon' => 'cart', 'href' => '?route=sales-targets', 'keywords' => 'chi tieu thang doanh so san luong nhan vien quan ly'],
            ['label' => 'Phiếu bán hàng', 'group' => 'Kinh doanh', 'icon' => 'cart', 'href' => '?route=sales-receipts', 'keywords' => 'phieu ban hang khach hang so dien thoai dia chi'],
            ['label' => 'Kho máy', 'group' => 'Quản lý kho', 'icon' => 'briefcase', 'href' => '?route=machine-warehouses', 'keywords' => 'kho may kho noi bo trang thiet bi'],
            ['label' => 'Quản lý thiết bị', 'group' => 'Quản lý kho', 'icon' => 'box', 'href' => '?route=equipment-devices', 'keywords' => 'thiet bi ten hang ma hang don gia nha cung cap'],
            ['label' => 'Loại thiết bị', 'group' => 'Quản lý kho', 'icon' => 'box', 'href' => '?route=equipment-types', 'keywords' => 'loai thiet bi ten viet tat'],
            ['label' => 'Mua sắm', 'group' => 'Quản lý kho', 'icon' => 'cart', 'href' => '?route=purchasing', 'keywords' => 'mua sam yeu cau thiet bi phieu mua sam cong no thuc nhan'],
            ['label' => 'Gọi điện', 'group' => 'CSKH', 'icon' => 'phone', 'href' => '?route=calls', 'keywords' => 'goi dien cuoc goi dien thoai lien he cham soc khach hang'],
            ['label' => 'Quản lý thông tin', 'group' => 'Hệ thống', 'icon' => 'info', 'href' => '?route=settings', 'keywords' => 'quan ly thong tin cong ty cau hinh tham so he thong'],
            ['label' => 'Lịch sử thao tác', 'group' => 'Hệ thống', 'icon' => 'file', 'href' => '?route=activity-log', 'keywords' => 'lich su thao tac audit log nhat ky he thong'],
            ['label' => 'Báo cáo', 'group' => 'Báo cáo', 'icon' => 'file', 'href' => '?route=reports', 'keywords' => 'report dashboard thong ke'],
        ];
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function groups(array $features): array
    {
        $groups = array_values(array_unique(array_map(fn (array $feature): string => (string) ($feature['group'] ?? ''), $features)));
        sort($groups);
        return $groups;
    }

}
