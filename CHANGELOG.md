# Changelog

## [Unreleased]

### HR Enhancements
- **Annual Leave Entitlements**: Full CRUD (Model, Repository, Service, Controller, Views, Dashboard). Tracks leave days, balance, air ticket eligibility per employee per year.
- **HR To-Do Tasks**: Task management system (Model, Repository, Service, Controller, Views, Dashboard). Supports types: milestone reminders, document expiry, general tasks.
- **Daily Cron (`cron/check_annual_leave.php`)**: Automatically creates 6-month air ticket reminders and 12-month annual leave entitlements (30 days leave + AED 1,250 air ticket).
- **Air Ticket Auto-generation**: Cron creates `erp_air_tickets` records for eligible employees.

### Attendance Modernization
- **ZKTeco Device Integration**: Full protocol implementation (`src/Service/ZKTecoClient.php`) — TCP socket connect, authenticate, pull attendance logs.
- **Attendance Sync Service** (`src/Service/AttendanceSyncService.php`): Pulls raw punches from devices, deduplicates, derives daily check-in/check-out records.
- **Device Management UI**: Attendance Devices CRUD (Model, Repository, Service, Controller, DataTable, Views, Dashboard).
- **Daily Sync Cron** (`cron/sync_attendance.php`): Processes all active devices per organization.
- **Database Migrations**: `attendance_devices`, `attendance_punches` tables, `zk_user_id` column on users.
- **Setup Guide**: `docs/attendance-device-integration.md` — configuration, cron setup, troubleshooting.

### UI Improvements
- **HR Guide**: All 8 accordion sections converted from plain text to visual step cards with Phosphor icons, color-coded borders, and info alerts.
- **Organization Documents**: Moved from separate floating row into the logo card (before photo upload) for a cleaner, more compact layout.

### Codebase Cleanup
- **Deleted**: 10 stale test/scratch scripts from project root and `scratch/` directory.
- **Deleted**: 9 Thumbs.db Windows cache files from `assets/images/`.
- **Deleted**: Stale log files (`cron/email_alerts.log`, `logs/FRONTEND_ERROR_LOG.txt`).
- **Archived**: `IMPLEMENTATION_PLAN.md`, `FLASH_MESSAGE_REFACTOR_PLAN.md` moved to `docs/archive/`.
- **Removed `is_active`**: Toggle removed from Organization form and DataTable. Organizations use `status` field instead.

### Bug Fixes
- **AlertService**: Fixed validation messages that incorrectly referenced "Commodity type" instead of "Alert name". Added `createSystemNotification()` method for system-generated alerts.

### New Files
- `src/Model/AnnualLeaveEntitlement.php`, `src/Model/HrTodoTask.php`
- `src/Repository/AnnualLeaveEntitlementRepository.php`, `src/Repository/HrTodoTaskRepository.php`
- `src/Service/AnnualLeaveEntitlementService.php`, `src/Service/HrTodoTaskService.php`
- `src/Http/Controller/AnnualLeaveEntitlementController.php`, `src/Http/Controller/HrTodoTaskController.php`
- `src/Service/ZKTecoClient.php`, `src/Service/AttendanceSyncService.php`, `src/Service/AttendanceDeviceService.php`
- `src/Model/AttendanceDevice.php`, `src/Model/AttendancePunch.php`
- `src/Repository/AttendanceDeviceRepository.php`, `src/Repository/AttendancePunchRepository.php`
- `src/Http/Controller/AttendanceDeviceController.php`, `src/DataTable/AttendanceDevicesDataTable.php`
- `cron/check_annual_leave.php`, `cron/sync_attendance.php`
- `database/migrations/006_create_attendance_devices.php`, `007_create_attendance_punches.php`, `008_add_zk_user_id_to_users.php`
- `docs/attendance-device-integration.md`

### Modified Files
- `src/Core/DB.php` — Added 4 constants (ANNUAL_LEAVE_ENTITLEMENTS, HR_TODO_TASKS, ATTENDANCE_DEVICES, ATTENDANCE_PUNCHES)
- `dashboard/bootstrap.php` — Registered new repositories, services, controllers
- `dashboard/admin_elements/hr_navbar.php` — Added Annual Leave, To-Do Tasks, Device Sync links
- `dashboard/admin_elements/permissions.php` — Added attendance_devices module mapping
- `src/Service/AlertService.php` — Fixed validation, added createSystemNotification()
- `dashboard/organizations.php` — Documents section moved inside logo card
- `resources/views/hr_guide/index.php` — Visual step card redesign
