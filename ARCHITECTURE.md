# Cấu trúc dự án Novaone

```text
Novaone/
|-- app/
|   |-- Core/                    # View, DataStore, helper, đọc XLSX
|   |-- Controllers/             # Controller dùng chung/toàn hệ thống
|   |-- Views/                   # Layout và view dùng chung
|   |-- Modules/
|   |   |-- HumanResources/      # Nhân viên, hợp đồng, BHXH, vi phạm, khen thưởng
|   |   `-- Work/                # Dự án, công việc, Kanban
|   |-- bootstrap.php            # Khởi tạo ứng dụng và nạp module
|   `-- module_schemas.php       # Schema CRUD dùng chung
|-- config/                      # Cấu hình ứng dụng
|-- database/                    # Schema MySQL tham khảo
|-- public/                      # Front controller và assets trình duyệt
|-- storage/                     # Dữ liệu JSON runtime và seed
`-- index.php                    # Entry point cho Laragon/PHP server
```

## Luồng request

1. `index.php` gọi `public/index.php`.
2. `public/index.php` chọn route và controller.
3. Controller đọc/ghi qua `DataStore`.
4. Controller render view dùng chung hoặc view module.
5. `app/Views/layouts/app.php` bọc nội dung trong layout chính.

## Quy ước view module

View trong module được gọi bằng tiền tố `@`:

```php
View::render('@HumanResources/employees/index', $data);
View::render('@Work/projects/index', $data);
```

`View` sẽ ánh xạ tới `app/Modules/<Module>/Views/...`.

## Thêm chức năng mới

1. Chọn module nghiệp vụ phù hợp hoặc tạo module mới.
2. Thêm controller vào `Modules/<Module>/Controllers`.
3. Thêm view vào `Modules/<Module>/Views/<feature>`.
4. Require controller trong `Modules/<Module>/bootstrap.php`.
5. Khai báo route trong `public/index.php`.
6. Thêm liên kết sidebar trong `app/Views/layouts/app.php`.
