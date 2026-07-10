# Kiến trúc dự án Novaone

Novaone đang được tổ chức theo mô hình PHP MVC nhẹ, trong đó các nghiệp vụ lớn được tách thành module riêng để dễ tìm kiếm, bảo trì và mở rộng.

## Sơ đồ thư mục

```text
Novaone/
|-- app/
|   |-- Core/                    # Lớp nền tảng: View, DataStore, helper, XlsxReader
|   |-- Controllers/             # Controller dùng chung toàn hệ thống
|   |-- Views/                   # Layout, partial, auth, dashboard, report, permission
|   |-- Modules/
|   |   |-- Business/            # Kinh doanh: nhà cung cấp, dịch vụ, sản phẩm, bán hàng
|   |   |-- HumanResources/      # Nhân sự: nhân viên, hợp đồng, chấm công, lương
|   |   |-- Inventory/           # Kho nội bộ/trang thiết bị
|   |   |-- Recruitment/         # Tuyển dụng
|   |   `-- Work/                # Công việc: dự án, công việc, báo cáo hằng ngày
|   |-- bootstrap.php            # Nạp core, controller và bootstrap module
|   `-- module_schemas.php       # Schema CRUD dùng chung
|-- config/                      # Cấu hình ứng dụng
|-- database/                    # Schema MySQL tham khảo
|-- docs/                        # Tài liệu dự án
|-- public/                      # Entry point web và assets
|-- storage/                     # Dữ liệu JSON, seed, log, file runtime
|-- android/                     # Project Android Capacitor
|-- index.php                    # Entry point khi chạy qua Laragon document root
`-- router.php                   # Router khi chạy PHP built-in server từ root
```

## Luồng request

1. Trình duyệt hoặc WebView gọi vào `public/index.php`.
2. `public/index.php` đọc `route` và điều hướng tới controller phù hợp.
3. Controller xử lý dữ liệu qua `DataStore` hoặc helper nghiệp vụ.
4. Controller gọi `View::render(...)` để render giao diện.
5. `app/Views/layouts/app.php` bọc view bằng layout chính.

## Quy ước module

Mỗi module nên có cấu trúc:

```text
app/Modules/<ModuleName>/
|-- Controllers/
|-- Views/
`-- bootstrap.php
```

Ví dụ:

```php
View::render('@HumanResources/employees/index', $data);
View::render('@Business/products/index', $data);
View::render('@Inventory/equipment_devices/index', $data);
```

Ký hiệu `@ModuleName/...` được `View` ánh xạ tới `app/Modules/<ModuleName>/Views/...`.

## Thêm chức năng mới

1. Chọn module nghiệp vụ phù hợp.
2. Tạo controller trong `app/Modules/<Module>/Controllers`.
3. Tạo view trong `app/Modules/<Module>/Views/<feature>`.
4. Nạp controller trong `app/Modules/<Module>/bootstrap.php`.
5. Khai báo route trong `public/index.php`.
6. Thêm menu/sidebar trong `app/Views/layouts/app.php`.
7. Bổ sung dữ liệu mẫu trong `storage/data.json` hoặc `storage/seed.php`.

## Hướng nâng cấp chuẩn hơn

- Thêm `composer.json` và PSR-4 autoload để bỏ dần các lệnh `require_once` thủ công.
- Tách route ra `routes/web.php` thay vì để nhiều trong `public/index.php`.
- Tách lớp repository/service khi chuyển từ JSON sang MySQL.
- Tách file ngôn ngữ `resources/lang/vi.php` nếu cần đa ngôn ngữ.
- Bổ sung test tự động cho controller/service quan trọng.
