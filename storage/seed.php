<?php

return [
    'users' => [
        ['id' => 'u1', 'name' => 'Admin Novaone', 'email' => 'admin@novaone.local', 'role' => 'Admin', 'status' => 'active'],
        ['id' => 'u2', 'name' => 'Linh Tran', 'email' => 'linh.hr@novaone.local', 'role' => 'HR', 'status' => 'active'],
    ],
    'employees' => [
        ['id' => 'e1', 'name' => 'Minh Nguyen', 'department' => 'Kinh doanh', 'position' => 'Sales Lead', 'contract' => 'Chính thức', 'status' => 'active'],
        ['id' => 'e2', 'name' => 'Ha Pham', 'department' => 'Kho vận', 'position' => 'Warehouse Admin', 'contract' => 'Chính thức', 'status' => 'active'],
        ['id' => 'e3', 'name' => 'Quang Le', 'department' => 'Công nghệ', 'position' => 'Developer', 'contract' => 'Thử việc', 'status' => 'on_leave'],
    ],
    'contracts' => [
        ['id' => 'ct01', 'contract_code' => 'HDHV-2026-001', 'employee_name' => 'Minh Nguyen', 'salary' => 6500000, 'start_date' => '2026-06-01', 'end_date' => '2026-07-31', 'contract_type' => 'hoc_viec', 'note' => 'Học việc khối kinh doanh'],
        ['id' => 'ct02', 'contract_code' => 'HDHV-2026-002', 'employee_name' => 'Ha Pham', 'salary' => 6000000, 'start_date' => '2026-06-15', 'end_date' => '2026-08-14', 'contract_type' => 'hoc_viec', 'note' => 'Học việc kho vận'],
        ['id' => 'ct03', 'contract_code' => 'HDTV-2026-001', 'employee_name' => 'Quang Le', 'salary' => 12000000, 'start_date' => '2026-05-01', 'end_date' => '2026-06-30', 'contract_type' => 'thu_viec', 'note' => 'Thử việc lập trình viên'],
        ['id' => 'ct04', 'contract_code' => 'HDTV-2026-002', 'employee_name' => 'Linh Tran', 'salary' => 11000000, 'start_date' => '2026-05-15', 'end_date' => '2026-07-14', 'contract_type' => 'thu_viec', 'note' => 'Thử việc phòng nhân sự'],
        ['id' => 'ct05', 'contract_code' => 'HDCT-2026-001', 'employee_name' => 'Minh Nguyen', 'salary' => 18000000, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'contract_type' => 'chinh_thuc', 'note' => 'Hợp đồng chính thức 12 tháng'],
        ['id' => 'ct06', 'contract_code' => 'HDCT-2026-002', 'employee_name' => 'Ha Pham', 'salary' => 15000000, 'start_date' => '2026-02-01', 'end_date' => '2027-01-31', 'contract_type' => 'chinh_thuc', 'note' => 'Hợp đồng chính thức 12 tháng'],
        ['id' => 'ct07', 'contract_code' => 'HDDH-2025-001', 'employee_name' => 'Admin Novaone', 'salary' => 32000000, 'start_date' => '2025-01-01', 'end_date' => '2028-12-31', 'contract_type' => 'dai_han', 'note' => 'Hợp đồng dài hạn'],
        ['id' => 'ct08', 'contract_code' => 'HDDH-2025-002', 'employee_name' => 'Linh Tran', 'salary' => 22000000, 'start_date' => '2025-06-01', 'end_date' => '2028-05-31', 'contract_type' => 'dai_han', 'note' => 'Hợp đồng dài hạn'],
    ],
    'social_insurance' => [
        ['id' => 'bh01', 'employee_name' => 'Minh Nguyen', 'employee_code' => 'NV001', 'contract_start' => '2026-01-01', 'contract_end' => '2026-12-31', 'insurance_number' => 'BHXH-010126001', 'salary' => 18000000, 'contribution' => 1890000, 'hospital' => 'Bệnh viện Đa khoa Hà Nội', 'note' => 'Tham gia đầy đủ'],
        ['id' => 'bh02', 'employee_name' => 'Ha Pham', 'employee_code' => 'NV002', 'contract_start' => '2026-02-01', 'contract_end' => '2027-01-31', 'insurance_number' => 'BHXH-020226002', 'salary' => 15000000, 'contribution' => 1575000, 'hospital' => 'Bệnh viện Nhân Dân 115', 'note' => 'Tham gia đầy đủ'],
        ['id' => 'bh03', 'employee_name' => 'Quang Le', 'employee_code' => 'NV003', 'contract_start' => '2026-05-01', 'contract_end' => '2026-06-30', 'insurance_number' => 'BHXH-010526003', 'salary' => 12000000, 'contribution' => 1260000, 'hospital' => 'Bệnh viện Thống Nhất', 'note' => 'Đang thử việc'],
        ['id' => 'bh04', 'employee_name' => 'Linh Tran', 'employee_code' => 'NV004', 'contract_start' => '2026-01-01', 'contract_end' => '2026-12-31', 'insurance_number' => 'BHXH-010126004', 'salary' => 22000000, 'contribution' => 2310000, 'hospital' => 'Bệnh viện Đại học Y Dược', 'note' => 'Tham gia đầy đủ'],
        ['id' => 'bh05', 'employee_name' => 'Long Trần', 'employee_code' => 'NV005', 'contract_start' => '2026-06-01', 'contract_end' => '2027-05-31', 'insurance_number' => 'BHXH-010626005', 'salary' => 16000000, 'contribution' => 1680000, 'hospital' => 'Bệnh viện Đa khoa Tâm Anh', 'note' => 'Mới đăng ký'],
    ],
    'violations' => [
        ['id' => 'vp01', 'employee_name' => 'Minh Nguyen', 'violation_date' => '2026-06-19', 'violation_type' => 'Vi phạm về thời gian làm việc', 'description' => 'Đi làm muộn 35 phút không báo trước.', 'penalty' => 200000, 'resolution' => 'Nhắc nhở và trừ KPI tháng'],
        ['id' => 'vp02', 'employee_name' => 'Ha Pham', 'violation_date' => '2026-06-19', 'violation_type' => 'Vi phạm quy trình kho', 'description' => 'Chưa hoàn tất biên bản bàn giao cuối ca.', 'penalty' => 150000, 'resolution' => 'Bổ sung biên bản trong ngày'],
        ['id' => 'vp03', 'employee_name' => 'Quang Le', 'violation_date' => '2026-06-18', 'violation_type' => 'Vi phạm về thời gian làm việc', 'description' => 'Nộp báo cáo công việc trễ hạn.', 'penalty' => 100000, 'resolution' => 'Nhắc nhở lần đầu'],
        ['id' => 'vp04', 'employee_name' => 'Ha Pham', 'violation_date' => '2026-06-16', 'violation_type' => 'Vi phạm nội quy', 'description' => 'Không đeo thẻ nhân viên trong giờ làm việc.', 'penalty' => 50000, 'resolution' => 'Nhắc nhở'],
        ['id' => 'vp05', 'employee_name' => 'Minh Nguyen', 'violation_date' => '2026-06-15', 'violation_type' => 'Vi phạm bảo mật thông tin', 'description' => 'Để tài liệu nội bộ tại khu vực công cộng.', 'penalty' => 500000, 'resolution' => 'Đào tạo lại quy định bảo mật'],
        ['id' => 'vp06', 'employee_name' => 'Minh Nguyen', 'violation_date' => '2026-05-28', 'violation_type' => 'Vi phạm về thời gian làm việc', 'description' => 'Vắng họp giao ban không có lý do.', 'penalty' => 200000, 'resolution' => 'Trừ KPI tháng'],
        ['id' => 'vp07', 'employee_name' => 'Ha Pham', 'violation_date' => '2026-05-20', 'violation_type' => 'Vi phạm sử dụng tài sản', 'description' => 'Sử dụng thiết bị không đúng quy trình.', 'penalty' => 300000, 'resolution' => 'Đào tạo lại quy trình thiết bị'],
        ['id' => 'vp08', 'employee_name' => 'Quang Le', 'violation_date' => '2026-05-10', 'violation_type' => 'Vi phạm nội quy', 'description' => 'Không cập nhật trạng thái công việc cuối ngày.', 'penalty' => 100000, 'resolution' => 'Nhắc nhở lần đầu'],
    ],
    'rewards' => [
        ['id' => 'kt01', 'employee_name' => 'Minh Nguyen', 'reward_date' => '2026-06-20', 'reward_type' => 'Khen thưởng nhân viên xuất sắc', 'description' => 'Vượt 125% chỉ tiêu doanh số tháng.', 'amount' => 3000000, 'decision_number' => 'QDKT-2026-001'],
        ['id' => 'kt02', 'employee_name' => 'Ha Pham', 'reward_date' => '2026-06-15', 'reward_type' => 'Đóng góp ý tưởng sáng tạo', 'description' => 'Đề xuất quy trình kiểm kê giúp giảm thời gian xử lý.', 'amount' => 1500000, 'decision_number' => 'QDKT-2026-002'],
        ['id' => 'kt03', 'employee_name' => 'Quang Le', 'reward_date' => '2026-06-10', 'reward_type' => 'Hoàn thành xuất sắc dự án', 'description' => 'Hoàn thành module báo cáo trước thời hạn.', 'amount' => 2500000, 'decision_number' => 'QDKT-2026-003'],
        ['id' => 'kt04', 'employee_name' => 'Minh Nguyen', 'reward_date' => '2026-05-25', 'reward_type' => 'Khen thưởng nhân viên xuất sắc', 'description' => 'Duy trì chất lượng chăm sóc khách hàng tốt.', 'amount' => 2000000, 'decision_number' => 'QDKT-2026-004'],
        ['id' => 'kt05', 'employee_name' => 'Ha Pham', 'reward_date' => '2026-05-12', 'reward_type' => 'Đóng góp ý tưởng sáng tạo', 'description' => 'Cải tiến cách bố trí hàng hóa trong kho.', 'amount' => 1000000, 'decision_number' => 'QDKT-2026-005'],
        ['id' => 'kt06', 'employee_name' => 'Quang Le', 'reward_date' => '2026-04-30', 'reward_type' => 'Hỗ trợ đồng đội', 'description' => 'Hỗ trợ đào tạo người dùng hệ thống mới.', 'amount' => 1000000, 'decision_number' => 'QDKT-2026-006'],
    ],
    'projects' => [
        ['id' => 'pr01', 'name' => 'Takashimaya', 'category' => 'Triển khai hệ thống', 'company' => 'Công ty TNHH Công nghệ Metatek', 'start_date' => '2026-06-22', 'end_date' => '2026-08-22', 'status' => 'open', 'manager' => 'Minh Nguyen', 'budget' => 850000000, 'description' => 'Triển khai nền tảng quản trị vận hành.'],
        ['id' => 'pr02', 'name' => 'MPRO', 'category' => 'Phần mềm doanh nghiệp', 'company' => 'Công ty TNHH Thương mại & Kỹ thuật V.M.S', 'start_date' => '2026-06-18', 'end_date' => '2026-07-31', 'status' => 'in_progress', 'manager' => 'Quang Le', 'budget' => 620000000, 'description' => 'Phát triển hệ thống quản lý bán hàng.'],
        ['id' => 'pr03', 'name' => 'Sàn Nông Sản Quốc Tế', 'category' => 'Thương mại điện tử', 'company' => 'Công ty Cổ phần Health Care Center APP', 'start_date' => '2026-06-08', 'end_date' => '2026-08-29', 'status' => 'open', 'manager' => 'Ha Pham', 'budget' => 1200000000, 'description' => 'Xây dựng sàn kết nối nông sản.'],
        ['id' => 'pr04', 'name' => 'CMD ROYAL', 'category' => 'Dữ liệu', 'company' => 'Công ty TNHH MTV khai thác dữ liệu số bData', 'start_date' => '2026-06-16', 'end_date' => '2026-06-30', 'status' => 'completed', 'manager' => 'Quang Le', 'budget' => 320000000, 'description' => 'Chuẩn hóa dữ liệu khách hàng.'],
        ['id' => 'pr05', 'name' => 'Green Pin', 'category' => 'Sản xuất', 'company' => 'Công ty TNHH Thương mại dịch vụ sản xuất P2D', 'start_date' => '2026-04-01', 'end_date' => '2026-06-30', 'status' => 'on_hold', 'manager' => 'Ha Pham', 'budget' => 740000000, 'description' => 'Quản lý chuỗi cung ứng sản xuất.'],
        ['id' => 'pr06', 'name' => 'Home 3DS', 'category' => 'Thiết kế', 'company' => 'Công ty TNHH Thiết kế và xây dựng Home Design', 'start_date' => '2026-06-16', 'end_date' => '2026-09-15', 'status' => 'open', 'manager' => 'Minh Nguyen', 'budget' => 480000000, 'description' => 'Nền tảng quản lý thiết kế 3D.'],
        ['id' => 'pr07', 'name' => 'Happy C', 'category' => 'Marketing', 'company' => 'Công ty TNHH Happy Creative', 'start_date' => '2026-06-01', 'end_date' => '2026-12-31', 'status' => 'in_progress', 'manager' => 'Minh Nguyen', 'budget' => 900000000, 'description' => 'Hệ thống quản lý chiến dịch marketing.'],
        ['id' => 'pr08', 'name' => 'BDATA-AI', 'category' => 'Trí tuệ nhân tạo', 'company' => 'Công ty TNHH MTV khai thác dữ liệu số bData', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'in_progress', 'manager' => 'Quang Le', 'budget' => 2500000000, 'description' => 'Nền tảng AI phân tích dữ liệu doanh nghiệp.'],
    ],
    'daily_reports' => [
        ['id' => 'dr01', 'project' => 'BDATA-AI', 'category' => 'Phát triển', 'employee' => 'Quang Le', 'details' => 'Hoàn thiện API phân tích dữ liệu và viết unit test.', 'hours' => 7.5, 'report_date' => '2026-06-21'],
        ['id' => 'dr02', 'project' => 'MPRO', 'category' => 'Dữ liệu', 'employee' => 'Minh Nguyen', 'details' => 'Chuẩn hóa danh sách khách hàng trước khi import.', 'hours' => 6, 'report_date' => '2026-06-21'],
        ['id' => 'dr03', 'project' => 'Green Pin', 'category' => 'Kiểm thử', 'employee' => 'Ha Pham', 'details' => 'Kiểm thử quy trình nhập kho và lập biên bản lỗi.', 'hours' => 8, 'report_date' => '2026-06-20'],
        ['id' => 'dr04', 'project' => 'Happy C', 'category' => 'Báo cáo', 'employee' => 'Minh Nguyen', 'details' => 'Thiết kế biểu đồ doanh thu theo chiến dịch.', 'hours' => 5.5, 'report_date' => '2026-06-20'],
        ['id' => 'dr05', 'project' => 'Home 3DS', 'category' => 'Tài liệu', 'employee' => 'Quang Le', 'details' => 'Soạn hướng dẫn sử dụng chức năng thiết kế.', 'hours' => 4, 'report_date' => '2026-06-19'],
    ],
    'tasks' => [
        ['id' => 't1', 'title' => 'Thiết kế database phase 1', 'project' => 'Core ERP', 'assignee' => 'Quang Le', 'status' => 'in_progress', 'deadline' => '2026-06-25'],
        ['id' => 't2', 'title' => 'Chuẩn hóa danh sách khách hàng', 'project' => 'CRM', 'assignee' => 'Minh Nguyen', 'status' => 'pending', 'deadline' => '2026-06-21'],
        ['id' => 't3', 'title' => 'Kiểm kê tồn kho đầu kỳ', 'project' => 'Inventory', 'assignee' => 'Ha Pham', 'status' => 'completed', 'deadline' => '2026-06-18'],
    ],
    'training' => [
        ['id' => 'tr1', 'course' => 'Onboarding nhân sự mới', 'employee' => 'Quang Le', 'trainer' => 'Linh Tran', 'progress' => 75, 'status' => 'in_progress'],
        ['id' => 'tr2', 'course' => 'Quy trình bán hàng B2B', 'employee' => 'Minh Nguyen', 'trainer' => 'Admin Novaone', 'progress' => 100, 'status' => 'completed'],
    ],
    'recruitments' => [
        ['id' => 'r1', 'position' => 'Backend Laravel Developer', 'candidate' => 'Nam Ho', 'stage' => 'interview', 'owner' => 'Linh Tran'],
        ['id' => 'r2', 'position' => 'Sales Executive', 'candidate' => 'Mai Do', 'stage' => 'offer', 'owner' => 'Linh Tran'],
    ],
    'customers' => [
        ['id' => 'c1', 'name' => 'Công ty An Phát', 'type' => 'customer', 'phone' => '0901 111 222', 'owner' => 'Minh Nguyen', 'status' => 'vip'],
        ['id' => 'c2', 'name' => 'Nhà cung cấp Sao Bắc', 'type' => 'supplier', 'phone' => '0902 333 444', 'owner' => 'Ha Pham', 'status' => 'active'],
    ],
    'suppliers' => [
        ['id' => 'sp1', 'name' => 'Sao Bắc Logistics', 'category' => 'Vận chuyển', 'phone' => '0902 333 444', 'debt' => 12000000, 'status' => 'active'],
        ['id' => 'sp2', 'name' => 'Thiết Bị Việt', 'category' => 'Thiết bị kho', 'phone' => '0908 222 111', 'debt' => 0, 'status' => 'active'],
    ],
    'services' => [
        ['id' => 'sv1', 'name' => 'CRM Pro', 'package' => 'Doanh nghiệp', 'price' => 58000000, 'status' => 'active'],
        ['id' => 'sv2', 'name' => 'Tư vấn triển khai ERP', 'package' => 'Project', 'price' => 120000000, 'status' => 'active'],
    ],
    'sales' => [
        ['id' => 's1', 'code' => 'SO-1001', 'customer' => 'Công ty An Phát', 'product' => 'Gói CRM Pro', 'amount' => 58000000, 'payment' => 'partial', 'status' => 'confirmed'],
        ['id' => 's2', 'code' => 'SO-1002', 'customer' => 'Minh Long', 'product' => 'Thiết bị kho', 'amount' => 32000000, 'payment' => 'paid', 'status' => 'delivered'],
    ],
    'pos' => [
        ['id' => 'p1', 'invoice' => 'POS-0001', 'cashier' => 'Ha Pham', 'items' => 'Tem nhãn vận chuyển', 'amount' => 1250000, 'payment_method' => 'cash', 'status' => 'paid'],
        ['id' => 'p2', 'invoice' => 'POS-0002', 'cashier' => 'Minh Nguyen', 'items' => 'Máy quét barcode', 'amount' => 4500000, 'payment_method' => 'card', 'status' => 'paid'],
    ],
    'tickets' => [
        ['id' => 'tk1', 'code' => 'TK-1001', 'customer' => 'Công ty An Phát', 'issue' => 'Cần hỗ trợ cấu hình phân quyền', 'owner' => 'Quang Le', 'status' => 'in_progress'],
        ['id' => 'tk2', 'code' => 'TK-1002', 'customer' => 'Minh Long', 'issue' => 'Yêu cầu xuất báo cáo bán hàng', 'owner' => 'Minh Nguyen', 'status' => 'new'],
    ],
    'call_logs' => [],
    'inventory' => [
        ['id' => 'i1', 'sku' => 'SKU-001', 'name' => 'Máy quét barcode', 'warehouse' => 'Kho Hà Nội', 'quantity' => 24, 'min' => 10, 'status' => 'available'],
        ['id' => 'i2', 'sku' => 'SKU-014', 'name' => 'Tem nhãn vận chuyển', 'warehouse' => 'Kho TP.HCM', 'quantity' => 6, 'min' => 20, 'status' => 'low'],
    ],
    'internal_assets' => [
        ['id' => 'ia1', 'code' => 'AS-001', 'name' => 'Laptop Dell', 'assigned_to' => 'Quang Le', 'quantity' => 1, 'status' => 'in_use'],
        ['id' => 'ia2', 'code' => 'AS-002', 'name' => 'Máy chiếu phòng họp', 'assigned_to' => 'Phòng Hành chính', 'quantity' => 1, 'status' => 'available'],
    ],
    'kpi' => [
        ['id' => 'k1', 'objective' => 'Tăng doanh thu quý', 'owner' => 'Minh Nguyen', 'metric' => '1.8 tỷ VND', 'progress' => 68, 'status' => 'on_track'],
        ['id' => 'k2', 'objective' => 'Tuyển đủ team dev', 'owner' => 'Linh Tran', 'metric' => '4 nhân sự', 'progress' => 45, 'status' => 'risk'],
    ],
    'okrs' => [
        ['id' => 'o1', 'objective' => 'Chuẩn hóa vận hành NovaOne', 'key_result' => '100% phân hệ core có quy trình', 'level' => 'company', 'owner' => 'Admin Novaone', 'progress' => 62, 'status' => 'on_track'],
        ['id' => 'o2', 'objective' => 'Nâng chất lượng CSKH', 'key_result' => '90% ticket xử lý đúng hạn', 'level' => 'department', 'owner' => 'Minh Nguyen', 'progress' => 48, 'status' => 'risk'],
    ],
    'shipments' => [
        ['id' => 'sh1', 'code' => 'SHP-7781', 'order' => 'SO-1001', 'carrier' => 'GHN', 'status' => 'shipping', 'eta' => '2026-06-20'],
        ['id' => 'sh2', 'code' => 'SHP-7782', 'order' => 'SO-1002', 'carrier' => 'Viettel Post', 'status' => 'delivered', 'eta' => '2026-06-15'],
    ],
    'calendar' => [
        ['id' => 'cal1', 'title' => 'Họp triển khai giai đoạn 2', 'type' => 'meeting', 'owner' => 'Admin Novaone', 'date' => '2026-06-22', 'status' => 'pending'],
        ['id' => 'cal2', 'title' => 'Đào tạo người dùng kho', 'type' => 'internal_event', 'owner' => 'Ha Pham', 'date' => '2026-06-24', 'status' => 'pending'],
    ],
    'facilities' => [
        ['id' => 'f1', 'code' => 'RM-01', 'name' => 'Phòng họp lớn', 'type' => 'meeting_room', 'location' => 'Tầng 3', 'status' => 'available'],
        ['id' => 'f2', 'code' => 'VH-01', 'name' => 'Xe giao hàng', 'type' => 'vehicle', 'location' => 'Kho Hà Nội', 'status' => 'in_use'],
    ],
    'permissions' => [
        ['id' => 'pm1', 'role' => 'Admin', 'module' => 'Tất cả', 'can_view' => 'yes', 'can_create' => 'yes', 'can_update' => 'yes', 'can_delete' => 'yes'],
        ['id' => 'pm2', 'role' => 'Sales', 'module' => 'CRM & Sales', 'can_view' => 'yes', 'can_create' => 'yes', 'can_update' => 'yes', 'can_delete' => 'no'],
    ],
    'settings' => [
        ['id' => 'set1', 'key' => 'company_name', 'value' => 'NovaOne', 'group' => 'company', 'status' => 'active'],
        ['id' => 'set2', 'key' => 'low_stock_alert', 'value' => 'enabled', 'group' => 'system', 'status' => 'active'],
    ],
];
