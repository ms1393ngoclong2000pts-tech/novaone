# Work Management

Nhóm chức năng quản lý dự án và công việc.

## Chức năng

- `projects`: danh sách dự án, import Excel/CSV.
- `work_items`: danh sách công việc và tiến độ.
- `tasks`: bảng Kanban dùng chung dữ liệu công việc.
- `daily_reports`: báo cáo công việc hàng ngày, hỗ trợ nhập nhiều dòng.

## Cấu trúc

- `Controllers/`: xử lý request, validation và dữ liệu.
- `Views/`: giao diện dự án, danh sách công việc và Kanban.
- `bootstrap.php`: nạp controller của module.

`work_items` và `tasks` cùng sử dụng key `tasks` trong `storage/data.json` để luôn đồng bộ.
