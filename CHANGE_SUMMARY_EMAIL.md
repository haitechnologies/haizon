# Email Draft — HR & Attendance System Update

**To:** All Stakeholders
**From:** Development Team
**Subject:** Haizon ERP — Employee Lifecycle & Attendance Modernization

---

Dear Team,

We have completed a major update to the HR and attendance functions in Haizon ERP. This memo walks through what changed, how it works, and what it means for your day-to-day operations.

---

## 1. Employee Benefits — Now Fully Automated

### Annual Leave & Air Tickets

Every employee is assigned a **date of joining** in their profile. The system watches this date and takes action automatically:

- **At the 6-month mark**: An HR to-do task is created as a reminder to prepare the employee's leave and ticket paperwork. No more calendar reminders or sticky notes.

- **At the 12-month mark**: The system automatically creates:
  - **30 days** of annual leave entitlement (as per UAE Labour Law)
  - **AED 1,250** air ticket allowance

The system keeps a running balance: as leave is used, the balance goes down. Once all leave is exhausted, the record automatically marks itself as fully availed.

**What you can do**: View any employee's leave history across all years. See at a glance how many days they have used, how many remain, and whether their air ticket is still pending. You can also manually create or adjust entitlements if needed.

### HR To-Do Tasks

A central task list for everything HR needs to follow up on. Tasks are created both automatically (like the 6-month reminder above) and manually by HR staff. Each task tracks:
- Which employee it relates to
- What needs to be done (e.g., "Complete 12-month leave review for John Smith")
- A due date
- Status: Pending → Completed → Archived

### What Changed Day-to-Day

| Before | After |
|--------|-------|
| Leave tracked on spreadsheets | System auto-creates entitlements at 12 months |
| HR manually watched for milestones | System sends a to-do at 6 months, creates the record at 12 months |
| No visibility into air ticket eligibility | Tickets are auto-created with AED 1,250, status tracked from start to payment |
| No leave balance history | Full per-year, per-employee history with remaining balance shown |

---

## 2. Attendance — Biometric Device Integration

### How It Works

The system now connects directly to ZKTeco biometric attendance devices (fingerprint or face recognition). Here is the flow:

1. **Device setup**: HR adds each device to the system — just the device name, IP address, and password. One device per office location.

2. **Employee mapping**: Each employee's profile has a "Device User ID" field that links them to their biometric identity on the machine. This is set once, typically during onboarding.

3. **Nightly sync**: Every night, the system automatically:
   - Connects to each device over the office network
   - Downloads all new fingerprint/facial recognition logs
   - Links each punch to the correct employee
   - Records every single scan in an audit trail
   - Calculates check-in (first scan), check-out (last scan), and total hours worked
   - Updates the daily attendance record — either creates a new entry or updates an existing one

4. **Morning view**: When HR opens the dashboard next day, today's attendance is already populated — who checked in, what time, total hours, and status (present/absent/late).

### What You Can Do

- **Manage devices**: Add new devices, edit settings, activate/deactivate from HR → Device Sync
- **Check sync status**: See when each device last synced and when the last punch was recorded
- **Override if needed**: Manual check-in/check-out edits still work alongside auto-synced data

### Setup

The IT team can configure devices in about 15 minutes. A full setup guide is available covering network configuration, device setup, and employee mapping.

### What Changed Day-to-Day

| Before | After |
|--------|-------|
| HR manually entered attendance for everyone | Biometric data flows in automatically overnight |
| No record of raw punch events | Every scan is stored — full audit trail |
| Check-in/check-out entered by hand | Derived automatically from first and last punch |
| No device management | Devices are managed through the HR menu, sync status visible at a glance |

---

## 3. HR Dashboard — Daily Operations at a Glance

The HR Dashboard was rebuilt to show what matters most, all on one screen:

### Top Row — 4 Key Numbers

| Card | What It Shows |
|------|---------------|
| **Present Today** | How many employees checked in, plus breakdown of absent/on leave |
| **Pending Leaves** | Leave requests waiting for your review |
| **Pending Air Tickets** | Air tickets not yet paid or issued |
| **Expiring Documents** | Employee passports, visas, IDs expiring within 30 days |

### Middle Section

- **Today's Attendance**: A table showing every employee — who checked in, at what time, how many hours, and their status. A button to sync devices and a link to view full attendance.
- **UAE Holidays**: A calendar of all official UAE public holidays for the current year, marked as upcoming, today, or passed.

### Bottom Section

- **Leave Requests Pending**: Employee name, leave type, dates, number of days, and a "Review" button to approve or decline.
- **Air Tickets Pending**: Employee name, amount, eligibility date, and status.
- **Expiring Documents**: Full list of all employee documents expiring within 30 days or already expired, with color-coded urgency.

---

## 4. HR Guide — Now Visual

The HR Guide page was redesigned from a plain text manual into a visual reference:

- Each section (Employees, Organization, Attendance, Leave, Payroll, Documents, Air Tickets, Gratuity, Reports) has its own heading with an icon
- Instructions are presented as step-by-step cards with icons and color accents
- Key tips are highlighted in info boxes

---

## 5. Organization Form — Cleaner Layout

When editing an organization:

- **Logo** now appears right in the main form, after the TRN field — no more switching between sections
- **Documents** have their own clearly labeled card with a table of all uploaded files and an upload form
- The whole form is more compact and easier to navigate

---

## 6. System Improvements

- **Alerts**: Fixed a labeling issue where alert validation messages showed the wrong name
- **Navigation**: New HR menu items added for Annual Leave Entitlements, To-Do Tasks, and Device Sync — all in the HR sidebar
- **Organization form cleanup**: Removed a redundant active/inactive toggle that was no longer needed

---

We hope these changes make HR operations smoother and more efficient. The system is fully live. If you would like a walkthrough of any feature, please let us know.

Best regards,
The Development Team
