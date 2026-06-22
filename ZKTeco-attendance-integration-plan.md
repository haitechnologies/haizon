# ZKTeco BioPro SA30 — Attendance Machine Integration Plan

## Device Overview

**ZKTeco BioPro SA30** — Fingerprint + RFID time attendance terminal
- TCP/IP communication on standard ZK protocol (port **4370**)
- 3,000 fingerprint capacity, 100,000 log capacity
- Supports PULL mode (server connects to device)

## Integration Approach: PULL Mode (Daily Cron)

The PHP server connects to the device via TCP on port 4370, authenticates,
downloads attendance logs since last sync, stores raw punches, then derives
daily attendance summaries.

```
ZKTeco Device (static IP)
  → cron/sync_attendance.php (scheduled 23:30 daily)
    → ZKTecoClient.php (ZK protocol over TCP sockets)
    → AttendanceSyncService
      → erp_attendance_punches (raw logs, deduped)
      → erp_attendance (derived daily: check_in=1st punch, check_out=last punch)
```

## User Mapping

`zk_user_id` column added to `erp_users`. During employee onboarding, set this
to match the numeric UserID on the ZKTeco device.

## Auto-Creation

When raw punches exist for a date without an `erp_attendance` record:
- `check_in` = first punch timestamp
- `check_out` = last punch timestamp
- `total_hours` = hours between first and last punch
- `status` = `present`

## Device Buffer

Buffer is preserved on the device. Dedup via:
- UNIQUE KEY on `(device_id, zk_user_id, punch_time)`
- `last_sync_at` timestamp tracks last successful pull

## New Database Tables

### erp_attendance_devices
| Column | Type | Purpose |
|--------|------|---------|
| id | INT PK | |
| organization_id | INT | Tenant scope |
| device_name | VARCHAR(100) | "Office Main Door" |
| ip_address | VARCHAR(45) | Static IP |
| port | INT (default 4370) | ZK TCP port |
| serial_number | VARCHAR(50) | Device serial |
| device_password | VARCHAR(50) | Auth password (default `0`) |
| device_model | VARCHAR(50) | "BioPro SA30" |
| location | VARCHAR(255) | Physical location |
| last_sync_at | DATETIME | Last successful pull |
| last_punch_at | DATETIME | Most recent punch |
| is_active | TINYINT(1) | Enable/disable sync |
| created_by | INT | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### erp_attendance_punches
| Column | Type | Purpose |
|--------|------|---------|
| id | BIGINT PK | Auto-increment |
| organization_id | INT | Tenant scope |
| device_id | INT | FK → devices |
| employee_id | INT | FK → erp_users |
| zk_user_id | VARCHAR(20) | Raw ZK user ID from device |
| punch_time | DATETIME | Exact timestamp |
| punch_type | TINYINT | 0=check-in, 1=check-out, 2=overtime_in, 3=overtime_out |
| verification_mode | TINYINT | 0=password, 1=fingerprint, 2=card |
| status | TINYINT | Device status byte |
| created_at | TIMESTAMP | |
| UNIQUE KEY | (device_id, zk_user_id, punch_time) | Dedup |

## File Change Summary (21 files)

### New Files (16)
1. `src/Service/ZKTecoClient.php` — ZK protocol TCP client
2. `src/Model/AttendanceDevice.php` — DTO
3. `src/Model/AttendancePunch.php` — DTO
4. `src/Repository/AttendanceDeviceRepository.php`
5. `src/Repository/AttendancePunchRepository.php`
6. `src/Service/AttendanceSyncService.php` — Sync engine
7. `src/Http/Controller/AttendanceDeviceController.php`
8. `src/DataTable/AttendanceDeviceDataTable.php`
9. `dashboard/attendance_devices.php` — Device form dispatcher
10. `dashboard/listing_attendance_devices.php` — Device list
11. `resources/views/attendance_devices/form.php`
12. `cron/sync_attendance.php` — Daily cron script
13. `database/migrations/006_create_attendance_devices.php`
14. `database/migrations/007_create_attendance_punches.php`
15. `database/migrations/008_add_zk_user_id_to_users.php`
16. `docs/attendance-device-integration.md` — Setup guide

### Modified Files (5)
17. `src/Core/DB.php` — Add 2 table constants
18. `src/Service/AttendanceService.php` — Add `deriveDailyAttendance()`
19. `dashboard/bootstrap.php` — Autowire + register
20. `dashboard/admin_elements/hr_navbar.php` — Menu entry
21. `dashboard/admin_elements/permissions.php` — Module group mapping
