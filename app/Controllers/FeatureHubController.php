<?php

declare(strict_types=1);

final class FeatureHubController
{
    public function show(DataStore $store): void
    {
        require_auth();

        $key = clean_text($_GET['key'] ?? 'hr', 40);
        $feature = clean_text($_GET['feature'] ?? '', 80);
        $hubs = $this->hubs($store);

        if (! isset($hubs[$key])) {
            $key = 'hr';
        }

        if ($feature !== '') {
            $item = $this->findFeature($hubs[$key]['groups'], $feature);
            if ($item !== null) {
                View::render('features/detail', [
                    'active' => $hubs[$key]['active'],
                    'title' => $item['label'],
                    'hub' => $hubs[$key],
                    'feature' => $item,
                ]);
                return;
            }
        }

        View::render('features/show', [
            'active' => $hubs[$key]['active'],
            'title' => $hubs[$key]['title'],
            'key' => $key,
            'hub' => $hubs[$key],
        ]);
    }

    private function hubs(DataStore $store): array
    {
        $data = $store->all();

        return [
            'hr' => [
                'title' => 'Nhân sự',
                'subtitle' => 'Hồ sơ nhân viên, hợp đồng, chấm công, bảng lương và các cảnh báo nhân sự.',
                'active' => 'employees',
                'icon' => 'users',
                'stats' => [
                    ['label' => 'Nhân viên', 'value' => count((array) ($data['employees'] ?? []))],
                    ['label' => 'Hợp đồng', 'value' => count((array) ($data['contracts'] ?? []))],
                    ['label' => 'Phiếu yêu cầu', 'value' => count((array) ($data['requests'] ?? []))],
                ],
                'groups' => [
                    'Nghiệp vụ chính' => [
                        $this->feature('Danh sách nhân viên', 'Hồ sơ, phòng ban, chức danh và trạng thái nhân viên.', '?route=employees', 'users'),
                        $this->feature('Hợp đồng lao động', 'Theo dõi loại hợp đồng, lương, ngày bắt đầu và ngày kết thúc.', '?route=contracts', 'file'),
                        $this->feature('Chấm công', 'Lọc công thực tế, xử lý công và quản lý máy chấm công.', '?route=attendance', 'calendar'),
                        $this->feature('Bảng lương', 'Tổng hợp lương, trạng thái hoàn thành và xuất dữ liệu.', '?route=payrolls', 'file'),
                        $this->feature('Bảo hiểm xã hội', 'Theo dõi nhân viên tham gia BHXH và mức đóng.', '?route=social-insurance', 'award'),
                    ],
                    'Mở rộng' => [
                        $this->feature('Hồ sơ nhân viên dạng timeline', 'Tập hợp hợp đồng, công, lương, bảo hiểm, thưởng phạt và thiết bị theo từng nhân viên.', '?route=features&key=hr&feature=employee-timeline', 'users', 'employee-timeline'),
                        $this->feature('Sinh nhật trong tháng', 'Danh sách nhân viên có sinh nhật trong tháng để HR chăm sóc nội bộ.', '?route=features&key=hr&feature=employee-birthdays', 'calendar', 'employee-birthdays'),
                        $this->feature('Cảnh báo thiếu hồ sơ', 'Theo dõi hồ sơ còn thiếu email, điện thoại, ngày sinh, chức danh hoặc hợp đồng.', '?route=features&key=hr&feature=profile-alerts', 'info', 'profile-alerts'),
                        $this->feature('Lịch sử thưởng phạt', 'Tra cứu nhanh vi phạm và khen thưởng theo từng nhân viên.', '?route=rewards', 'award'),
                    ],
                ],
            ],
            'system' => [
                'title' => 'Quản lý thông tin',
                'subtitle' => 'Thông tin công ty, tài khoản, phân quyền, nhật ký và cấu hình vận hành.',
                'active' => 'settings',
                'icon' => 'info',
                'stats' => [
                    ['label' => 'Tài khoản', 'value' => count((array) ($data['users'] ?? []))],
                    ['label' => 'Thông báo', 'value' => count((array) ($data['_notifications'] ?? []))],
                    ['label' => 'Nhật ký', 'value' => count((array) ($data['_activity_log'] ?? []))],
                ],
                'groups' => [
                    'Hệ thống' => [
                        $this->feature('Thông tin công ty', 'Cấu hình tên công ty, ngân hàng, liên hệ và nhận diện.', '?route=settings', 'building'),
                        $this->feature('Tài khoản người dùng', 'Tạo tài khoản, khóa/mở và gán vai trò.', '?route=users', 'users'),
                        $this->feature('Phân quyền', 'Phân quyền xem, thêm, sửa, xóa theo vai trò.', '?route=permissions', 'settings'),
                        $this->feature('Lịch sử thao tác', 'Audit log cho thao tác tạo, sửa, xóa, import và export.', '?route=activity-log', 'file'),
                    ],
                    'Cấu hình mở rộng' => [
                        $this->feature('Template hệ thống', 'Quản lý mẫu hợp đồng, phiếu yêu cầu và nội dung thông báo.', '?route=features&key=system&feature=templates', 'file', 'templates'),
                        $this->feature('Backup dữ liệu', 'Kế hoạch sao lưu/khôi phục dữ liệu storage hoặc MySQL.', '?route=features&key=system&feature=backup', 'save', 'backup'),
                        $this->feature('Cấu hình giao diện', 'Logo, màu thương hiệu, tên app và cấu hình mobile.', '?route=features&key=system&feature=theme', 'settings', 'theme'),
                    ],
                ],
            ],
            'services' => [
                'title' => 'Dịch vụ',
                'subtitle' => 'Ngành hàng, dịch vụ, sản phẩm, bảng giá và trạng thái kích hoạt.',
                'active' => 'services',
                'icon' => 'lifebuoy',
                'stats' => [
                    ['label' => 'Ngành hàng', 'value' => count((array) ($data['services'] ?? []))],
                    ['label' => 'Sản phẩm', 'value' => count((array) ($data['products'] ?? []))],
                    ['label' => 'Nhà cung cấp', 'value' => count((array) ($data['suppliers'] ?? []))],
                ],
                'groups' => [
                    'Dịch vụ - sản phẩm' => [
                        $this->feature('Danh sách dịch vụ', 'Quản lý ngành hàng nhiều cấp và trạng thái hiển thị.', '?route=services', 'lifebuoy'),
                        $this->feature('Danh sách sản phẩm', 'Sản phẩm, hình ảnh, giá bán, số lượng và doanh số.', '?route=products', 'box'),
                        $this->feature('Bảng giá dịch vụ', 'Theo dõi giá theo gói, loại khách hàng và thời gian áp dụng.', '?route=features&key=services&feature=price-list', 'file', 'price-list'),
                        $this->feature('Kích hoạt dịch vụ', 'Gợi ý luồng kích hoạt dịch vụ cho khách hàng sau bán.', '?route=features&key=services&feature=activation', 'check', 'activation'),
                    ],
                ],
            ],
            'suppliers' => [
                'title' => 'Nhà cung cấp',
                'subtitle' => 'Hồ sơ đối tác, lịch sử mua hàng, hợp đồng và công nợ nhà cung cấp.',
                'active' => 'suppliers',
                'icon' => 'briefcase',
                'stats' => [
                    ['label' => 'Nhà cung cấp', 'value' => count((array) ($data['suppliers'] ?? []))],
                    ['label' => 'Mua sắm', 'value' => count((array) ($data['purchasing'] ?? []))],
                    ['label' => 'Thiết bị', 'value' => count((array) ($data['equipment_devices'] ?? []))],
                ],
                'groups' => [
                    'Quản lý đối tác' => [
                        $this->feature('Danh sách nhà cung cấp', 'Mã, tên, thông tin chi tiết, sửa và xóa.', '?route=suppliers', 'briefcase'),
                        $this->feature('Lịch sử mua hàng', 'Liên kết các phiếu mua sắm theo từng nhà cung cấp.', '?route=purchasing', 'cart'),
                        $this->feature('Đánh giá nhà cung cấp', 'Theo dõi chất lượng, thời gian giao hàng và rủi ro.', '?route=features&key=suppliers&feature=ratings', 'award', 'ratings'),
                        $this->feature('Công nợ nhà cung cấp', 'Theo dõi phát sinh phải trả và trạng thái thanh toán.', '?route=features&key=suppliers&feature=payables', 'file', 'payables'),
                    ],
                ],
            ],
            'hr-reports' => [
                'title' => 'Báo cáo nhân sự',
                'subtitle' => 'Tổng hợp nhân sự, chấm công, lương, nghỉ phép, khen thưởng và vi phạm.',
                'active' => 'reports',
                'icon' => 'file',
                'stats' => [
                    ['label' => 'Công', 'value' => count((array) ($data['attendance_records'] ?? []))],
                    ['label' => 'Lương', 'value' => count((array) ($data['payrolls'] ?? []))],
                    ['label' => 'Vi phạm', 'value' => count((array) ($data['violations'] ?? []))],
                ],
                'groups' => [
                    'Báo cáo' => [
                        $this->feature('Báo cáo tổng hợp', 'Dashboard và báo cáo xuất Excel/PDF.', '?route=reports&module=human', 'file'),
                        $this->feature('Báo cáo chấm công', 'Tổng công, tổng giờ, nhân viên thiếu giờ và theo phòng ban.', '?route=attendance', 'calendar'),
                        $this->feature('Báo cáo lương', 'Tổng lương, trạng thái hoàn thành và lọc theo bộ phận.', '?route=payrolls', 'file'),
                        $this->feature('Báo cáo thưởng phạt', 'Tổng hợp khen thưởng và vi phạm theo thời gian.', '?route=violations', 'award'),
                    ],
                ],
            ],
            'sales-stock' => [
                'title' => 'Quản lý kho bán hàng',
                'subtitle' => 'Sản phẩm, tồn kho, nhập xuất, kiểm kê và cảnh báo hàng hóa.',
                'active' => 'products',
                'icon' => 'settings',
                'stats' => [
                    ['label' => 'Sản phẩm', 'value' => count((array) ($data['products'] ?? []))],
                    ['label' => 'Tồn kho', 'value' => array_sum(array_map(fn (array $item): int => (int) ($item['quantity'] ?? 0), (array) ($data['products'] ?? [])))],
                    ['label' => 'Kho', 'value' => count((array) ($data['inventory'] ?? []))],
                ],
                'groups' => [
                    'Kho bán hàng' => [
                        $this->feature('Danh sách sản phẩm', 'Hình ảnh, giá, số lượng, doanh số và thao tác.', '?route=products', 'box'),
                        $this->feature('Tồn kho bán hàng', 'Theo dõi SKU, kho, số lượng và tồn tối thiểu.', '?route=inventory', 'warehouse'),
                        $this->feature('Kiểm kê', 'Gợi ý luồng kiểm kê và chênh lệch tồn kho.', '?route=features&key=sales-stock&feature=stocktake', 'check', 'stocktake'),
                        $this->feature('In mã vạch', 'Chuẩn bị luồng in tem/mã vạch cho sản phẩm.', '?route=features&key=sales-stock&feature=barcode', 'file', 'barcode'),
                    ],
                ],
            ],
            'retail' => [
                'title' => 'Bán hàng lẻ',
                'subtitle' => 'POS, hóa đơn bán lẻ, đổi trả, khuyến mãi và báo cáo theo ca.',
                'active' => 'sales',
                'icon' => 'cart',
                'stats' => [
                    ['label' => 'Phiếu bán', 'value' => count((array) ($data['sales_receipts'] ?? []))],
                    ['label' => 'POS', 'value' => count((array) ($data['pos'] ?? []))],
                    ['label' => 'Đơn hàng', 'value' => count((array) ($data['sales_orders'] ?? []))],
                ],
                'groups' => [
                    'Bán lẻ' => [
                        $this->feature('Phiếu bán hàng', 'Danh sách phiếu, khách hàng, ngày tạo và xem chi tiết.', '?route=sales-receipts', 'cart'),
                        $this->feature('POS bán lẻ', 'Bán hàng nhanh, thu ngân và phương thức thanh toán.', '?route=pos', 'cart'),
                        $this->feature('Đổi trả hàng', 'Gợi ý luồng xử lý đổi trả theo phiếu bán hàng.', '?route=features&key=retail&feature=returns', 'backspace', 'returns'),
                        $this->feature('Khuyến mãi', 'Gợi ý quản lý chương trình khuyến mãi và voucher.', '?route=features&key=retail&feature=promotions', 'award', 'promotions'),
                    ],
                ],
            ],
            'work-reports' => [
                'title' => 'Báo cáo công việc',
                'subtitle' => 'Tiến độ dự án, công việc quá hạn, hiệu suất nhân viên và ticket.',
                'active' => 'reports',
                'icon' => 'file',
                'stats' => [
                    ['label' => 'Dự án', 'value' => count((array) ($data['projects'] ?? []))],
                    ['label' => 'Công việc', 'value' => count((array) ($data['work_items'] ?? []))],
                    ['label' => 'Báo cáo ngày', 'value' => count((array) ($data['daily_reports'] ?? []))],
                ],
                'groups' => [
                    'Báo cáo công việc' => [
                        $this->feature('Dự án', 'Danh sách dự án, trạng thái, công ty và ngày kết thúc.', '?route=projects', 'briefcase'),
                        $this->feature('Danh sách công việc', 'Lọc dự án, trạng thái, người thực hiện và tiến độ.', '?route=work-items', 'check'),
                        $this->feature('Báo cáo hằng ngày', 'Nhập giờ làm, hạng mục, chi tiết và ngày báo cáo.', '?route=daily-reports', 'file'),
                        $this->feature('Ticket công việc', 'Theo dõi ticket hỗ trợ và trạng thái xử lý.', '?route=tickets', 'headset'),
                    ],
                ],
            ],
            'equipment' => [
                'title' => 'Trang thiết bị',
                'subtitle' => 'Kho máy, thiết bị, loại thiết bị, mua sắm, cấp phát và bảo trì.',
                'active' => 'internal_assets',
                'icon' => 'briefcase',
                'stats' => [
                    ['label' => 'Kho máy', 'value' => count((array) ($data['machine_warehouses'] ?? []))],
                    ['label' => 'Thiết bị', 'value' => count((array) ($data['equipment_devices'] ?? []))],
                    ['label' => 'Mua sắm', 'value' => count((array) ($data['purchasing'] ?? []))],
                ],
                'groups' => [
                    'Trang thiết bị' => [
                        $this->feature('Kho máy', 'Danh sách kho, dự án, người quản lý và chuyển kho.', '?route=machine-warehouses', 'warehouse'),
                        $this->feature('Quản lý thiết bị', 'Tên hàng, mã hàng, đơn giá, nhà cung cấp và xem chi tiết.', '?route=equipment-devices', 'box'),
                        $this->feature('Loại thiết bị', 'Tên loại thiết bị, tên viết tắt và thời gian tạo.', '?route=equipment-types', 'box'),
                        $this->feature('Mua sắm', 'Phiếu yêu cầu thiết bị, phê duyệt, thực nhận và công nợ.', '?route=purchasing', 'cart'),
                        $this->feature('Cấp phát thiết bị', 'Theo dõi thiết bị cấp cho nhân viên và trạng thái sử dụng.', '?route=internal-assets', 'users'),
                        $this->feature('Bảo trì/bảo hành', 'Gợi ý lịch bảo trì, bảo hành, hỏng/mất và thanh lý.', '?route=features&key=equipment&feature=maintenance', 'settings', 'maintenance'),
                    ],
                ],
            ],
            'sales' => [
                'title' => 'Bán hàng',
                'subtitle' => 'Đơn hàng, báo giá, hợp đồng, nghiệm thu, chỉ tiêu và pipeline.',
                'active' => 'sales',
                'icon' => 'cart',
                'stats' => [
                    ['label' => 'Đơn hàng', 'value' => count((array) ($data['sales_orders'] ?? []))],
                    ['label' => 'Chỉ tiêu', 'value' => count((array) ($data['sales_targets'] ?? []))],
                    ['label' => 'Phiếu bán', 'value' => count((array) ($data['sales_receipts'] ?? []))],
                ],
                'groups' => [
                    'Bán hàng' => [
                        $this->feature('Đơn hàng', 'Pipeline khởi tạo, báo giá, hợp đồng và thanh toán.', '?route=sales-orders', 'cart'),
                        $this->feature('Chỉ tiêu tháng', 'Doanh số, sản lượng, khách hàng và nhân viên quản lý.', '?route=sales-targets', 'pie'),
                        $this->feature('Phiếu bán hàng', 'Danh sách phiếu bán và xem chi tiết.', '?route=sales-receipts', 'file'),
                        $this->feature('Công nợ khách hàng', 'Gợi ý theo dõi đã thanh toán, còn nợ và nhắc công nợ.', '?route=features&key=sales&feature=receivables', 'file', 'receivables'),
                    ],
                ],
            ],
            'work' => [
                'title' => 'Công việc',
                'subtitle' => 'Dự án, công việc, báo cáo hằng ngày, ticket, deadline và Kanban.',
                'active' => 'tasks',
                'icon' => 'check',
                'stats' => [
                    ['label' => 'Dự án', 'value' => count((array) ($data['projects'] ?? []))],
                    ['label' => 'Công việc', 'value' => count((array) ($data['work_items'] ?? []))],
                    ['label' => 'Ticket', 'value' => count((array) ($data['tickets'] ?? []))],
                ],
                'groups' => [
                    'Quản lý công việc' => [
                        $this->feature('Dự án', 'Dự án, công ty, thời gian, trạng thái và import Excel.', '?route=projects', 'briefcase'),
                        $this->feature('Danh sách công việc', 'Công việc, người thực hiện, tiến độ, trạng thái và deadline.', '?route=work-items', 'check'),
                        $this->feature('Báo cáo hằng ngày', 'Báo cáo giờ làm theo dự án, nhân viên và hạng mục.', '?route=daily-reports', 'file'),
                        $this->feature('Ticket', 'Ticket hỗ trợ và xử lý công việc phát sinh.', '?route=tickets', 'headset'),
                        $this->feature('Kanban board', 'Gợi ý bảng kéo thả theo trạng thái công việc.', '?route=features&key=work&feature=kanban', 'settings', 'kanban'),
                    ],
                ],
            ],
            'recruitment' => [
                'title' => 'Tuyển dụng',
                'subtitle' => 'Phiếu tuyển dụng, ứng viên, lịch phỏng vấn, đánh giá và chi phí.',
                'active' => 'recruitments',
                'icon' => 'monitor',
                'stats' => [
                    ['label' => 'Phiếu tuyển', 'value' => count((array) ($data['recruitment_requests'] ?? []))],
                    ['label' => 'Ứng viên', 'value' => array_sum(array_map(fn (array $item): int => (int) ($item['candidates_total'] ?? 0), (array) ($data['recruitment_requests'] ?? [])))],
                    ['label' => 'Đạt', 'value' => array_sum(array_map(fn (array $item): int => (int) ($item['candidates_passed'] ?? 0), (array) ($data['recruitment_requests'] ?? [])))],
                ],
                'groups' => [
                    'Tuyển dụng' => [
                        $this->feature('Phiếu yêu cầu tuyển dụng', 'Danh sách phiếu, trạng thái, người duyệt và ứng viên.', '?route=recruitment-requests', 'monitor'),
                        $this->feature('Pipeline ứng viên', 'Gợi ý luồng sàng lọc, phỏng vấn, offer và nhận việc.', '?route=features&key=recruitment&feature=candidate-pipeline', 'users', 'candidate-pipeline'),
                        $this->feature('Lịch phỏng vấn', 'Gợi ý lịch phỏng vấn gắn với ứng viên và người phỏng vấn.', '?route=calendar', 'calendar'),
                        $this->feature('Chi phí tuyển dụng', 'Theo dõi chi phí theo phiếu và hiệu quả nguồn tuyển.', '?route=features&key=recruitment&feature=recruitment-cost', 'file', 'recruitment-cost'),
                    ],
                ],
            ],
            'training' => [
                'title' => 'Đào tạo',
                'subtitle' => 'Khóa học, tài liệu, bài thi, kết quả, chứng chỉ và lộ trình đào tạo.',
                'active' => 'training',
                'icon' => 'book',
                'stats' => [
                    ['label' => 'Khóa học', 'value' => count((array) ($data['training'] ?? []))],
                    ['label' => 'Hoàn thành', 'value' => count(array_filter((array) ($data['training'] ?? []), fn (array $item): bool => ($item['status'] ?? '') === 'completed'))],
                    ['label' => 'Báo cáo', 'value' => 1],
                ],
                'groups' => [
                    'Đào tạo' => [
                        $this->feature('Khóa học', 'Khóa đào tạo, nhân viên, trainer, tiến độ và trạng thái.', '?route=training', 'book'),
                        $this->feature('Báo cáo đào tạo', 'Tỷ lệ hoàn thành, nhân viên chưa hoàn thành và hiệu quả khóa học.', '?route=training-reports', 'file'),
                        $this->feature('Tài liệu học', 'Gợi ý kho tài liệu đào tạo theo khóa học.', '?route=features&key=training&feature=training-documents', 'file', 'training-documents'),
                        $this->feature('Bài thi và chứng chỉ', 'Gợi ý bài thi, điểm số và chứng chỉ theo nhân viên.', '?route=features&key=training&feature=certificates', 'award', 'certificates'),
                    ],
                ],
            ],
            'cskh' => [
                'title' => 'CSKH',
                'subtitle' => 'Khách hàng, tương tác, cuộc gọi, ticket, chat hỗ trợ và kế hoạch chăm sóc.',
                'active' => 'calls',
                'icon' => 'phone',
                'stats' => [
                    ['label' => 'Cuộc gọi', 'value' => count((array) ($data['call_logs'] ?? []))],
                    ['label' => 'Ticket', 'value' => count((array) ($data['tickets'] ?? []))],
                    ['label' => 'Khách hàng', 'value' => count((array) ($data['customers'] ?? []))],
                ],
                'groups' => [
                    'Chăm sóc khách hàng' => [
                        $this->feature('Gọi điện', 'Bàn phím gọi điện, nội dung cuộc gọi và lưu lịch sử.', '?route=calls', 'phone'),
                        $this->feature('Danh sách khách hàng', 'Quản lý khách hàng và trạng thái chăm sóc.', '?route=customers', 'users'),
                        $this->feature('Ticket hỗ trợ', 'Ticket, người phụ trách và trạng thái xử lý.', '?route=tickets', 'headset'),
                        $this->feature('Kế hoạch CSKH', 'Gợi ý lịch chăm sóc và nhắc khách hàng lâu chưa tương tác.', '?route=features&key=cskh&feature=care-plan', 'calendar', 'care-plan'),
                    ],
                ],
            ],
            'training-reports' => [
                'title' => 'Báo cáo đào tạo',
                'subtitle' => 'Tỷ lệ hoàn thành, điểm số, chứng chỉ và hiệu quả đào tạo theo phòng ban.',
                'active' => 'training_reports',
                'icon' => 'file',
                'stats' => [
                    ['label' => 'Khóa học', 'value' => count((array) ($data['training'] ?? []))],
                    ['label' => 'Hoàn thành', 'value' => count(array_filter((array) ($data['training'] ?? []), fn (array $item): bool => ($item['status'] ?? '') === 'completed'))],
                    ['label' => 'Đang học', 'value' => count(array_filter((array) ($data['training'] ?? []), fn (array $item): bool => ($item['status'] ?? '') === 'in_progress'))],
                ],
                'groups' => [
                    'Báo cáo đào tạo' => [
                        $this->feature('Báo cáo đào tạo tổng hợp', 'Trang báo cáo hiện có theo khóa học và nhân viên.', '?route=training-reports', 'file'),
                        $this->feature('Nhân viên chưa hoàn thành', 'Gợi ý danh sách nhân viên cần nhắc hoàn thành khóa học.', '?route=features&key=training-reports&feature=incomplete-training', 'users', 'incomplete-training'),
                        $this->feature('Hiệu quả khóa học', 'Gợi ý thống kê điểm, hoàn thành và đánh giá khóa.', '?route=features&key=training-reports&feature=training-effectiveness', 'pie', 'training-effectiveness'),
                    ],
                ],
            ],
            'operations' => [
                'title' => 'Vận hành',
                'subtitle' => 'Lịch, vận chuyển, cơ sở vật chất và các quy trình hỗ trợ vận hành.',
                'active' => 'calendar',
                'icon' => 'calendar',
                'stats' => [
                    ['label' => 'Lịch', 'value' => count((array) ($data['calendar'] ?? []))],
                    ['label' => 'Vận chuyển', 'value' => count((array) ($data['shipments'] ?? []))],
                    ['label' => 'Cơ sở vật chất', 'value' => count((array) ($data['facilities'] ?? []))],
                ],
                'groups' => [
                    'Vận hành' => [
                        $this->feature('Lịch làm việc', 'Lịch họp, công tác, sự kiện nội bộ và nhắc việc.', '?route=calendar', 'calendar'),
                        $this->feature('Vận chuyển', 'Đơn giao hàng, đơn vị vận chuyển, trạng thái và ETA.', '?route=shipments', 'truck'),
                        $this->feature('Cơ sở vật chất', 'Tài sản cố định, phòng họp, phương tiện và vị trí.', '?route=facilities', 'building'),
                    ],
                ],
            ],
        ];
    }

    private function feature(string $label, string $description, string $href, string $icon, ?string $slug = null): array
    {
        return [
            'label' => $label,
            'description' => $description,
            'href' => $href,
            'icon' => $icon,
            'slug' => $slug ?? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $label) ?? ''),
            'implemented' => href_route($href) !== 'features',
        ];
    }

    private function findFeature(array $groups, string $slug): ?array
    {
        foreach ($groups as $items) {
            foreach ($items as $item) {
                if (($item['slug'] ?? '') === $slug) {
                    return $item;
                }
            }
        }

        return null;
    }
}
