# Human Resources

Nhóm chức năng quản lý nhân sự.

## Chức năng

- `employees`: danh sách nhân viên, import/export.
- `contracts`: hợp đồng lao động.
- `social_insurance`: bảo hiểm xã hội.
- `violations`: phiếu vi phạm.
- `rewards`: phiếu khen thưởng.

## Cấu trúc

- `Controllers/`: xử lý request, validation và dữ liệu.
- `Views/`: giao diện theo từng chức năng.
- `bootstrap.php`: nạp controller của module.

Các route được khai báo tập trung tại `public/index.php`. Dữ liệu runtime nằm trong `storage/data.json`.
