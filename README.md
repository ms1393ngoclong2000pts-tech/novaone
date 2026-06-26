# Novaone Admin PHP

Ứng dụng quản trị doanh nghiệp viết bằng PHP thuần theo kiến trúc MVC nhẹ và module nghiệp vụ.

## Phân hệ hiện có

- Nhân sự: nhân viên, hợp đồng, bảo hiểm xã hội, vi phạm, khen thưởng.
- Công việc: dự án, danh sách công việc, bảng Kanban.
- Kinh doanh: nhà cung cấp, dịch vụ, bán hàng, POS, khách hàng và vận chuyển.
- Kho và hệ thống: tồn kho, tài sản nội bộ, báo cáo, lịch và phân quyền.

## Cấu trúc chính

- `app/Core`: hạ tầng dùng chung.
- `app/Controllers`: controller toàn hệ thống.
- `app/Views`: layout và view dùng chung.
- `app/Modules/HumanResources`: module quản lý nhân sự.
- `app/Modules/Work`: module quản lý dự án và công việc.
- `public/index.php`: khai báo route và front controller.
- `storage/data.json`: dữ liệu runtime.
- `storage/seed.php`: dữ liệu khởi tạo.

Xem [ARCHITECTURE.md](ARCHITECTURE.md) để biết chi tiết luồng request và cách thêm module.

## Chạy dự án

Laragon:

```text
http://localhost/Novaone
```

PHP built-in server:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -S localhost:8000 -t .
```

Mở `http://localhost:8000`.

## Tài khoản demo

- Email: `admin@novaone.local`
- Mật khẩu: `admin123`

Ứng dụng hiện dùng JSON để chạy ngay không cần MySQL. Khi triển khai production, thay `DataStore` bằng repository PDO và sử dụng `database/schema.sql`.
