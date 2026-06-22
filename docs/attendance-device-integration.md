# ZKTeco Attendance Device Integration — Setup Guide

## Overview

This module integrates ZKTeco biometric attendance devices (e.g. BioPro SA30)
with the Haizon ERP system. It uses PULL mode: a daily cron job connects to
the device via TCP, downloads attendance logs, and derives daily check-in/
check-out records.

## Architecture

```
ZKTeco Device (static IP, TCP port 4370)
  → cron/sync_attendance.php (scheduled 23:30 daily)
    → ZKTecoClient.php (ZK protocol via PHP sockets)
    → AttendanceSyncService
      → erp_attendance_punches (raw punch logs)
      → erp_attendance (derived daily summary)
```

## Prerequisites

1. **ZKTeco device** with a static IP on the same network as the server
2. TCP port `4370` open between the server and device
3. Device password (default is `0`)
4. Unique employee numbering: each employee's ZK UserID must be set in
   `erp_users.zk_user_id` (under User/Employee profile)

## Device Setup

1. Connect the BioPro SA30 to your network via Ethernet
2. On the device, go to **Menu → Communication → Network Settings**
3. Set a **static IP address** (e.g., `192.168.1.100`)
4. Note the IP, port (default `4370`), and device password
5. Enroll employees on the device — assign each a numeric UserID
6. Record each employee's ZK UserID

## ERP Setup

### 1. Database Migrations

Run migrations to create the required tables:

```bash
php database/migrate.php
```

This creates:
- `erp_attendance_devices` — device configurations
- `erp_attendance_punches` — raw punch logs from devices
- Adds `zk_user_id` column to `erp_users`

### 2. Register Modules

Add these records to `erp_modules`:

```sql
INSERT INTO erp_modules (module_name, slug, parent_id, module_order, icon, is_active)
VALUES 
  ('Attendance Devices', 'attendance_devices', NULL, 16, 'ph-devices', 1);

INSERT INTO erp_module_permissions (module_id, slug)
SELECT id, 'view' FROM erp_modules WHERE slug = 'attendance_devices'
UNION ALL
SELECT id, 'create' FROM erp_modules WHERE slug = 'attendance_devices'
UNION ALL
SELECT id, 'edit' FROM erp_modules WHERE slug = 'attendance_devices'
UNION ALL
SELECT id, 'delete' FROM erp_modules WHERE slug = 'attendance_devices';
```

Then assign permissions to appropriate roles via the Permissions UI.

### 3. Add a Device (Admin UI)

1. Go to **HR → Attendance & Leave → Device Sync**
2. Click **New Attendance Device**
3. Fill in:
   - **Device Name** — "Main Entrance", "Back Gate", etc.
   - **IP Address** — static IP of the device
   - **Port** — `4370` (default)
   - **Serial Number** — from device label (optional)
   - **Device Password** — `0` unless changed on device
   - **Device Model** — "BioPro SA30"
   - **Location** — e.g., "Main Gate"
   - **Is Active** — checked
4. Click **Save**

### 4. Map Employees

For each employee, set their `zk_user_id` in the **Users/Employee** profile:

1. Go to **HR → Users / Employees → Edit Employee**
2. Find the **ZK User ID** field
3. Enter the numeric UserID from the ZKTeco device
4. Save

> **Important:** The ZK UserID must match exactly what is stored on the
> device. If the device has UserID `12`, enter `12` in the employee profile.

### 5. Test Connection

You can test connectivity by running the sync script manually:

```bash
php cron/sync_attendance.php
```

Expected output:
```
[2026-06-19 23:30:00] Starting attendance sync...
[2026-06-19 23:30:02] [OK] Main Entrance: 15 logs pulled, 12 new punches, 5 attendance records derived
[2026-06-19 23:30:02] Sync complete: 1 devices, 0 errors, 15 total logs, 12 new punches, 5 attendance records
```

If connection fails, check:
- Device is powered on and on the network
- IP address and port are correct
- Firewall allows port 4370
- Device password is correct

## Cron Setup

### Linux (crontab)

```bash
# Run daily at 11:30 PM
30 23 * * * php /path/to/haizon/cron/sync_attendance.php >> /path/to/haizon/storage/logs/attendance_sync.log 2>&1
```

### Windows (Task Scheduler)

1. Open **Task Scheduler**
2. Create Basic Task → Trigger: Daily at 23:30
3. Action: Start a program
4. Program: `C:\xampp\php\php.exe`
5. Arguments: `G:\xampp\htdocs\haizon\cron\sync_attendance.php`

### cPanel

1. Go to **Cron Jobs**
2. Command: `php /home/user/public_html/haizon/cron/sync_attendance.php`
3. Time: `30 23 * * *`

## How It Works

### Sync Flow

1. **Connect** — PHP opens TCP socket to device (port 4370)
2. **Authenticate** — sends device password
3. **Disable device** — prevents real-time events during data transfer
4. **Pull logs** — requests all attendance records
5. **Parse records** — decodes binary ZK protocol data into structured records
6. **Map users** — matches ZK UserID → ERP employee ID
7. **Store punches** — inserts into `erp_attendance_punches` (dedup by device + user + time)
8. **Derive daily attendance** — for each employee+date:
   - `check_in` = earliest punch time
   - `check_out` = latest punch time
   - `total_hours` = hours between first and last punch
   - `status` = `'present'`
9. **Update device** — records `last_sync_at` timestamp
10. **Enable device** — restores normal operation
11. **Disconnect** — closes TCP socket

### Deduplication

- `erp_attendance_punches` has a UNIQUE KEY on `(device_id, zk_user_id, punch_time)`
- `INSERT IGNORE` ensures duplicate punches are silently skipped
- Device buffer is preserved (not cleared after sync)

## Data Model

### `erp_attendance_devices`

| Column | Description |
|--------|-------------|
| `id` | Auto-increment PK |
| `organization_id` | Tenant scope |
| `device_name` | "Main Entrance" |
| `ip_address` | Static IP address |
| `port` | TCP port (default 4370) |
| `serial_number` | Device serial |
| `device_password` | Auth password |
| `device_model` | e.g., "BioPro SA30" |
| `location` | Physical location description |
| `last_sync_at` | Last successful sync timestamp |
| `last_punch_at` | Most recent punch detected |
| `is_active` | Enable/disable sync |

### `erp_attendance_punches`

| Column | Description |
|--------|-------------|
| `id` | Auto-increment PK |
| `organization_id` | Tenant scope |
| `device_id` | FK → `erp_attendance_devices.id` |
| `employee_id` | FK → `erp_users.id` (0 if unmapped) |
| `zk_user_id` | Raw numeric UserID from device |
| `punch_time` | Exact datetime from device |
| `punch_type` | 0=check-in, 1=check-out, 2=overtime_in, 3=overtime_out |
| `verification_mode` | 0=password, 1=fingerprint, 2=card |
| UNIQUE | `(device_id, zk_user_id, punch_time)` |

## Troubleshooting

### Connection Refused

- Is the device powered on?
- Is the IP address correct? (ping from the server)
- Is port 4370 open? (use `telnet <ip> 4370` to test)

### Authentication Failed

- Default password is `0` (numeric zero)
- If changed, update in the ERP device config

### No Attendance Logs

- Are employees enrolled on the device?
- Have they clocked in/out?
- Check `last_sync_at` — if first run, it pulls ALL logs

### Employee Not Mapped

- `zk_user_id` field is empty for the employee
- Set it in the employee profile to match the device UserID

### Duplicate Attendance Records

Not possible — the sync service uses SELECT + INSERT/UPDATE pattern
keyed by `(employee_id, work_date)`. Only one record per employee per day.

## File Reference

| File | Purpose |
|------|---------|
| `cron/sync_attendance.php` | Daily cron entry point |
| `src/Service/ZKTecoClient.php` | ZK protocol TCP client |
| `src/Service/AttendanceSyncService.php` | Sync engine |
| `src/Model/AttendanceDevice.php` | Device configuration DTO |
| `src/Model/AttendancePunch.php` | Raw punch log DTO |
| `src/Repository/AttendanceDeviceRepository.php` | Device CRUD |
| `src/Repository/AttendancePunchRepository.php` | Punch CRUD + batch insert |
| `src/Service/AttendanceDeviceService.php` | Device business logic |
| `src/Http/Controller/AttendanceDeviceController.php` | Device management controller |
| `src/DataTable/AttendanceDevicesDataTable.php` | Device list DataTable handler |
| `dashboard/attendance_devices.php` | Device form dispatcher |
| `dashboard/listing_attendance_devices.php` | Device list page |
| `resources/views/attendance_devices/form.php` | Device form template |
