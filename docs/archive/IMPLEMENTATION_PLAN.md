# Haizon ERP — Implementation Plan

## 1. Annual Leave & Air Ticket System (New Feature)

**Current gaps**: No date_of_joining in rp_users, no leave balance tracking, no air ticket module.

**Implementation:**

| Step | File(s) | Action |
|------|---------|--------|
| 1.1 | DB schema | Add date_of_joining DATE column to rp_users |
| 1.2 | src/Core/DB.php | Add ANNUAL_LEAVE_ENTITLEMENTS, AIR_TICKETS, HR_TODO_TASKS constants |
| 1.3 | src/Model/AnnualLeaveEntitlement.php | New DTO |
| 1.4 | src/Model/AirTicket.php | New DTO |
| 1.5 | src/Model/HrTodoTask.php | New DTO |
| 1.6 | src/Repository/*Repository.php | New CRUD for each model |
| 1.7 | src/Service/AnnualLeaveService.php | New: 6mo → HR todo alert, 12mo → 1mo leave + AED 1250 air ticket |
| 1.8 | src/Service/AirTicketService.php | New: Status management (HR view only, Accounts update) |
| 1.9 | src/Service/HrTodoService.php | New: Mark complete/archived |
| 1.10 | src/Http/Controller/*Controller.php | New CRUD controllers |
| 1.11 | dashboard/ files | New listing + form dispatchers |
| 1.12 | esources/views/ | New form views |
| 1.13 | cron/check_annual_leave.php | New: Daily cron for milestone checks |

**Rule**: 12mo from date_of_joining → 1 month paid leave + AED 1250 air ticket. 6mo → HR todo alert.

---

## 2. Gratuity Calculation (New Feature)

**UAE Law Formula**: <1yr = none, 1-5yr = 21 days/year, 5+yr = 30 days/year, cap = 2yr salary.

| Step | File(s) | Action |
|------|---------|--------|
| 2.1 | src/Core/DB.php | Add GRATUITY_CALCULATIONS constant |
| 2.2 | src/Model/GratuityCalculation.php | New DTO |
| 2.3 | src/Repository/GratuityRepository.php | New CRUD |
| 2.4 | src/Service/GratuityService.php | New calculation logic |
| 2.5 | Controllers + Views + Dashboard | New pages |

---

## 3. Users.php Document Section — Date Picker Readonly

**Files affected**: esources/views/users/form.php, dashboard/assets_custom/js/datepicker-config.js

**Changes**:
- Add eadonly to #doc-issued and #doc-expiry inputs
- Add datepicker init for both IDs in datepicker-config.js

---

## 4. HR Navbar — Help/Guide Link

| Step | File(s) | Action |
|------|---------|--------|
| 4.1 | dashboard/admin_elements/hr_navbar.php | Add guide tab after eports |
| 4.2 | dashboard/hr_guide.php | New dispatcher |
| 4.3 | src/Http/Controller/HrGuideController.php | New controller |
| 4.4 | esources/views/hr_guide/index.php | Visual step-by-step guide page |

---

## 5. Alerts vs Notifications — Use rp_alerts

**Decision**: Use existing rp_alerts table (has full MVC stack). Add 	ype column ('manual'/'system'), optionally eference_type + eference_id. Extend AlertService with createSystemNotification(). No new table needed.

---

## 6. Leave Type — 3 Days Paid Then Unpaid

| Step | File(s) | Action |
|------|---------|--------|
| 6.1 | DB schema | Add paid_days INT DEFAULT 3 to rp_leave_types |
| 6.2 | src/Model/LeaveType.php | Add paidDays property |
| 6.3 | src/Repository/LeaveTypeRepository.php | Update SQL |
| 6.4 | src/Service/LeaveRequestService.php | Calculate paid/unpaid portions |
| 6.5 | esources/views/leave_types/form.php | Add Paid Days input |

**Default**: Annual leave gets paid_days = 3.

---

## 7. Salaries — Accounts Department Only

| Step | File(s) | Action |
|------|---------|--------|
| 7.1 | Permissions | Add payroll_manage module or use existing modules |
| 7.2 | Payroll controllers | Add canView()/canEdit() checks |
| 7.3 | hr_navbar.php | Conditionally show payroll tab based on permissions |

---

## 8. Air Tickets — Accounts Update, HR View Only

| Step | File(s) | Action |
|------|---------|--------|
| 8.1 | Permissions | Add ir_tickets module with iew + dit permissions |
| 8.2 | AirTicketService | HR: list/getById only; Accounts: updateStatus |
| 8.3 | Controllers | Permission gating |
| 8.4 | Views | Accounts sees action buttons, HR sees read-only |

---

## 9. Organization Offcanvas — Hide Options

**File**: dashboard/admin_elements/admin_footer.php:536-551

Remove entire "Organization Actions" section (Organizations, Organization Roles, Invite Members buttons). Keep only "My Organizations" list.

---

## 10. DataTables SR Column Fix

**Root cause**: GenericDataTable::formatRow() and UsersDataTable::formatRow() return $id as first column instead of $this->rowNumber.

| File | Change |
|------|--------|
| src/DataTable/GenericDataTable.php:38 | $id → $this->rowNumber |
| src/DataTable/UsersDataTable.php:111 | $id → $this->rowNumber |

Fixes SR column for ~29 GenericDataTable-driven listing pages + Users listing.
