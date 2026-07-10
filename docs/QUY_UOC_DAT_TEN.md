# Quy ước đặt tên file và thư mục

## Nguyên tắc chính

Không đặt tên file code bằng tiếng Việt có dấu hoặc có khoảng trắng.

Lý do:

- Dễ lỗi khi deploy lên Linux hosting/VPS do khác biệt filesystem.
- Không phù hợp với chuẩn class/controller PHP và PSR-4 autoload.
- Dễ lỗi khi dùng Git, ZIP, CI/CD, Android build hoặc công cụ dòng lệnh.
- Khó require/import ổn định trong dự án PHP thuần.

## Cách đặt tên chuẩn

| Loại file | Quy ước | Ví dụ |
| --- | --- | --- |
| Controller | PascalCase + `Controller.php` | `EmployeeController.php` |
| Class core | PascalCase | `DataStore.php` |
| View folder | snake_case tiếng Anh | `sales_targets` |
| View file | snake_case hoặc tên hành động | `index.php`, `form.php`, `show.php` |
| CSS/JS | kebab-case hoặc snake_case | `mobile.css`, `app.css` |
| Tài liệu | UPPER_SNAKE_CASE không dấu | `CAU_TRUC_DU_AN.md` |

## Tên tiếng Việt dùng ở đâu?

Tên tiếng Việt nên dùng trong:

- Menu/sidebar.
- Tiêu đề trang.
- Label form.
- Thông báo hệ thống.
- Tài liệu trong `docs`.
- Bản đồ chức năng.

Ví dụ:

```php
// File code
app/Modules/HumanResources/Controllers/EmployeeController.php

// Tên hiển thị
Danh Sách Nhân Viên
```

## Khi cần đổi tên chức năng

Nếu chỉ đổi tên hiển thị, sửa label trong view/layout. Không đổi tên controller/view folder nếu không bắt buộc.

Nếu bắt buộc đổi tên file/folder:

1. Đổi file/folder bằng `git mv`.
2. Cập nhật `require_once` trong `bootstrap.php`.
3. Cập nhật route trong `public/index.php`.
4. Cập nhật `View::render(...)`.
5. Chạy `php -l` toàn bộ file PHP.
6. Chạy web test desktop/mobile.
