# Novaone Admin PHP

Novaone là ứng dụng quản trị doanh nghiệp viết bằng PHP thuần, tổ chức theo mô hình MVC nhẹ và chia module theo nghiệp vụ.

## Tính năng chính

- Nhân sự: danh sách nhân viên, hồ sơ nhân sự, hợp đồng lao động, chấm công, bảng lương, bảo hiểm xã hội, phiếu yêu cầu, vi phạm, khen thưởng.
- Công việc: dự án, danh sách công việc, báo cáo hằng ngày.
- Kinh doanh: nhà cung cấp, dịch vụ, sản phẩm, đơn hàng, chỉ tiêu tháng, phiếu bán hàng.
- Kho nội bộ: kho máy, quản lý thiết bị, loại thiết bị, mua sắm.
- Hệ thống: dashboard, tìm kiếm, thông báo, hồ sơ cá nhân, đổi mật khẩu, phân quyền, gọi điện.
- Mobile/App: giao diện responsive, Capacitor Android, hỗ trợ build APK debug để khách hàng test.

## Cấu trúc dự án

```text
Novaone/
|-- app/                    # Mã nguồn PHP chính
|   |-- Core/               # View, DataStore, helper, đọc XLSX
|   |-- Controllers/        # Controller dùng chung toàn hệ thống
|   |-- Modules/            # Module nghiệp vụ
|   |-- Views/              # Layout, partial và view dùng chung
|   |-- bootstrap.php       # Nạp core, controller và module
|   `-- module_schemas.php  # Cấu hình CRUD dùng chung
|-- config/                 # Cấu hình ứng dụng
|-- database/               # Schema MySQL tham khảo
|-- docs/                   # Tài liệu dự án bằng tiếng Việt
|-- public/                 # Front controller và assets trình duyệt
|-- storage/                # Dữ liệu JSON runtime, seed, log
|-- android/                # Project Android do Capacitor sinh ra
|-- capacitor.config.json   # Cấu hình WebView cho APK
|-- package.json            # Script build APK
`-- router.php              # Router cho PHP built-in server
```

Chi tiết cấu trúc xem tại [docs/CAU_TRUC_DU_AN.md](docs/CAU_TRUC_DU_AN.md).

## Quy ước đặt tên

Dự án dùng tên file, folder, class bằng tiếng Anh không dấu theo chuẩn PHP để chạy ổn định trên Windows, Linux hosting, Git và autoload sau này. Tên tiếng Việt được dùng ở giao diện, tài liệu và bản đồ chức năng.

Chi tiết quy ước xem tại [docs/QUY_UOC_DAT_TEN.md](docs/QUY_UOC_DAT_TEN.md).

## Chạy dự án

Laragon:

```text
http://localhost/Novaone
```

PHP built-in server:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -S 127.0.0.1:8000 -t public
```

Mở:

```text
http://127.0.0.1:8000/?route=login
```

## Tài khoản demo

- Email: `admin@novaone.local`
- Mật khẩu: `admin123`

## Build APK debug

1. Chạy web bằng PHP server hoặc domain public.
2. Cập nhật `server.url` trong `capacitor.config.json`.
3. Build APK:

```powershell
npm run apk:debug
```

File APK nằm tại:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

## Dữ liệu

Hiện tại ứng dụng dùng `storage/data.json` để chạy nhanh không cần MySQL. Khi triển khai production, nên chuyển sang repository dùng PDO/MySQL và dùng `database/schema.sql` làm nền tảng thiết kế bảng.
