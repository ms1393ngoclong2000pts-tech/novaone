# Cấu trúc dự án chuẩn cho Novaone

Tài liệu này dùng tiếng Việt để mô tả nghiệp vụ, nhưng tên file/folder code vẫn dùng tiếng Anh không dấu để đúng chuẩn PHP và dễ deploy.

## Nhóm thư mục chính

| Thư mục | Vai trò |
| --- | --- |
| `app/Core` | Lớp nền tảng dùng chung: render view, lưu dữ liệu, helper, đọc Excel |
| `app/Controllers` | Controller dùng chung như đăng nhập, dashboard, báo cáo, phân quyền |
| `app/Modules` | Chứa các phân hệ nghiệp vụ |
| `app/Views` | Layout, partial và các trang dùng chung |
| `config` | Cấu hình ứng dụng |
| `database` | Schema MySQL và tài liệu dữ liệu |
| `docs` | Tài liệu dự án |
| `public` | Front controller, CSS, JS, hình ảnh, upload |
| `storage` | Dữ liệu runtime, seed, log |
| `android` | Project Android sinh bởi Capacitor |

## Bản đồ module nghiệp vụ

| Tên tiếng Việt | Module/Thư mục |
| --- | --- |
| Quản lý nhân sự | `app/Modules/HumanResources` |
| Công việc | `app/Modules/Work` |
| Kinh doanh | `app/Modules/Business` |
| Trang thiết bị/Kho nội bộ | `app/Modules/Inventory` |
| Tuyển dụng | `app/Modules/Recruitment` |

## Bản đồ chức năng chính

| Tên trên giao diện | Controller/View |
| --- | --- |
| Danh sách nhân viên | `HumanResources/EmployeeController.php`, `Views/employees` |
| Hợp đồng lao động | `HumanResources/ContractController.php`, `Views/contracts` |
| Chấm công | `HumanResources/AttendanceController.php` |
| Bảng lương | `HumanResources/PayrollController.php`, `Views/payrolls` |
| Bảo hiểm xã hội | `HumanResources/SocialInsuranceController.php`, `Views/social_insurance` |
| Phiếu yêu cầu | `HumanResources/RequestFormController.php`, `Views/requests` |
| Danh sách vi phạm | `HumanResources/ViolationController.php`, `Views/violations` |
| Danh sách khen thưởng | `HumanResources/RewardController.php`, `Views/rewards` |
| Dự án | `Work/ProjectController.php`, `Views/projects` |
| Danh sách công việc | `Work/TaskController.php`, `Views/tasks` |
| Báo cáo hằng ngày | `Work/DailyReportController.php`, `Views/daily_reports` |
| Nhà cung cấp | `Business/SupplierController.php`, `Views/suppliers` |
| Danh sách dịch vụ | `Business/ServiceController.php`, `Views/services` |
| Danh sách sản phẩm | `Business/ProductController.php`, `Views/products` |
| Đơn hàng | `Business/SalesOrderController.php`, `Views/sales_orders` |
| Chỉ tiêu tháng | `Business/SalesTargetController.php`, `Views/sales_targets` |
| Phiếu bán hàng | `Business/SalesReceiptController.php`, `Views/sales_receipts` |
| Kho máy | `Inventory/MachineWarehouseController.php`, `Views/machine_warehouses` |
| Quản lý thiết bị | `Inventory/EquipmentDeviceController.php`, `Views/equipment_devices` |
| Loại thiết bị | `Inventory/EquipmentTypeController.php`, `Views/equipment_types` |
| Mua sắm | `Inventory/PurchasingController.php`, `Views/purchasing` |
| Phiếu yêu cầu tuyển dụng | `Recruitment/RecruitmentRequestController.php`, `Views/requests` |

## Cách gom file khi thêm tính năng

Khi thêm một chức năng mới, đặt toàn bộ file của chức năng trong module tương ứng:

```text
app/Modules/Business/
|-- Controllers/NewFeatureController.php
`-- Views/new_feature/
    |-- index.php
    |-- form.php
    `-- show.php
```

Không đặt controller nghiệp vụ mới ở root nếu chức năng thuộc module. `app/Controllers` chỉ dành cho chức năng dùng chung toàn hệ thống.

## Quy tắc giữ dự án gọn

- Assets trình duyệt đặt trong `public/assets`.
- File upload đặt trong `public/uploads`.
- Dữ liệu runtime và log đặt trong `storage`.
- Tài liệu đặt trong `docs`.
- Không commit `node_modules`, file build Android, log, file tạm.
- Không để logic xử lý lớn trong view; view chỉ hiển thị dữ liệu.
