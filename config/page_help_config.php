<?php

declare(strict_types=1);

return [
    'listing_customers.php' => [
        'title' => 'Customers',
        'icon'  => 'ph-user-circle',
        'what'  => 'View and manage all your customer accounts. Each row shows the customer name, contact details, outstanding receivables, status, and approval state.',
        'steps' => [
            'Click <strong>+ New</strong> at the top-right to create a new customer.',
            'Use the <strong>search box</strong> above the table to find a specific customer by name, email, or phone.',
            'Click a <strong>customer name</strong> to view their full profile and transaction history.',
            'Use the <strong>Actions</strong> column on each row to edit or delete a customer.',
            'Filter by <strong>status badges</strong> at the top to see only Active, Inactive, or other customer groups.',
        ],
        'fields' => [
            'Receivables (BCY)' => 'The total outstanding balance owed by this customer, shown in your base currency.',
            'Status'            => 'Whether the customer is currently Active or Inactive in your system.',
            'Approval'          => 'Whether the customer record has been reviewed and approved by a manager.',
        ],
        'tips' => [
            'You can sort any column by clicking its header — click again to reverse the order.',
            'Use the entries dropdown (10, 25, 50, 100) at the bottom to control how many rows appear per page.',
        ],
    ],

    'listing_invoices.php' => [
        'title' => 'Invoices',
        'icon'  => 'ph-receipt',
        'what'  => 'View, create, and manage all invoices. Track invoice status, amounts, balance due, and overdue days at a glance.',
        'steps' => [
            'Click <strong>Create Invoice</strong> to generate a new invoice for a customer.',
            'Use the <strong>search box</strong> to find invoices by number, customer name, or amount.',
            'Click an <strong>invoice number</strong> to view the full invoice details and payment history.',
            'Use the <strong>⋮ menu</strong> (three dots) on each row for quick actions: View, Edit, Download PDF, or Delete.',
            'Click <strong>Export CSV</strong> to download the current list as a spreadsheet file.',
        ],
        'fields' => [
            'Invoice Info'  => 'Shows the invoice number and linked sales order (if any).',
            'Amount'        => 'The total invoice amount including taxes.',
            'Balance Due'   => 'Remaining unpaid amount — shown in red when outstanding, green when fully paid.',
            'Status'        => 'Current state: Draft, Sent, Paid, Unpaid, or Overdue.',
            'Days Overdue'  => 'How many days past the due date. Negative values mean the invoice is not yet due.',
        ],
        'tips' => [
            'Overdue invoices are highlighted in red — prioritize following up on these.',
            'You can download a PDF of any invoice directly from the Actions menu.',
        ],
    ],

    'listing_quotations.php' => [
        'title' => 'Quotations',
        'icon'  => 'ph-file-text',
        'what'  => 'Create and manage price quotations sent to customers. Track which quotes are pending, accepted, or expired.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new quotation.',
            'Fill in the customer, items, quantities, and pricing, then save.',
            'Once ready, you can convert an accepted quotation directly into an invoice or sales order.',
            'Use <strong>Actions</strong> to view, edit, duplicate, or delete a quotation.',
        ],
        'fields' => [
            'Status'    => 'Whether the quote is Draft, Sent, Accepted, Declined, or Expired.',
            'Amount'    => 'Total quoted amount including applicable taxes.',
            'Valid Till' => 'The expiry date after which the quotation is no longer valid.',
        ],
        'tips' => [
            'Duplicate an existing quotation to quickly create a similar one for another customer.',
            'Expired quotes can still be viewed but cannot be converted — create a new one instead.',
        ],
    ],

    'listing_leads.php' => [
        'title' => 'Leads',
        'icon'  => 'ph-target',
        'what'  => 'Track potential customers (leads) through your sales pipeline. Manage lead sources, statuses, and follow-up actions.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new lead manually.',
            'Fill in company name, contact person, email, phone, and lead source.',
            'Assign a <strong>status</strong> (New, Contacted, Qualified, etc.) to track progress.',
            'When a lead becomes a customer, convert them using the <strong>Convert to Customer</strong> action.',
        ],
        'fields' => [
            'Source'  => 'Where the lead came from — e.g., Website, Referral, Cold Call, Trade Show.',
            'Status'  => 'Current pipeline stage of the lead.',
            'Owner'   => 'The team member responsible for following up with this lead.',
        ],
        'tips' => [
            'Regularly update lead statuses to keep your pipeline accurate.',
            'Use filters to focus on leads that need immediate follow-up.',
        ],
    ],

    'listing_expenses.php' => [
        'title' => 'Expenses',
        'icon'  => 'ph-wallet',
        'what'  => 'Record and manage business expenses. Track vendor payments, categorize spending, and monitor expense approvals.',
        'steps' => [
            'Click <strong>+ New</strong> to record a new expense.',
            'Select the vendor (payee), expense account, amount, and date.',
            'Attach receipts or supporting documents if required.',
            'Submit the expense for approval (if your organization requires it).',
        ],
        'fields' => [
            'Amount'   => 'The total expense amount in the transaction currency.',
            'Status'   => 'Whether the expense is Draft, Pending Approval, Approved, or Paid.',
            'Account'  => 'The chart-of-accounts category this expense is recorded under.',
        ],
        'tips' => [
            'Attach receipt images to expenses for easy audit reference.',
            'Use the date filter to review expenses for a specific period.',
        ],
    ],

    'listing_payments_received.php' => [
        'title' => 'Payments Received',
        'icon'  => 'ph-arrow-circle-down',
        'what'  => 'Track all payments received from customers. Link payments to invoices to automatically update balances.',
        'steps' => [
            'Click <strong>+ New</strong> to record a new payment received.',
            'Select the customer and the invoice(s) the payment applies to.',
            'Enter the amount received, payment date, and payment method.',
            'Save to automatically update the invoice balance.',
        ],
        'fields' => [
            'Amount'      => 'The payment amount received.',
            'Payment Mode' => 'How the payment was made — Cash, Bank Transfer, Cheque, Credit Card, etc.',
            'Invoice'      => 'The invoice number this payment is applied against.',
        ],
        'tips' => [
            'A single payment can be split across multiple invoices.',
            'Payments automatically reduce the Balance Due on the linked invoice.',
        ],
    ],

    'listing_payments_made.php' => [
        'title' => 'Payments Made',
        'icon'  => 'ph-arrow-circle-up',
        'what'  => 'Track all outgoing payments made to vendors and suppliers.',
        'steps' => [
            'Click <strong>+ New</strong> to record a payment made.',
            'Select the vendor, bill, amount, and payment method.',
            'Save to update the vendor\'s outstanding balance.',
        ],
        'fields' => [
            'Amount'  => 'The payment amount sent to the vendor.',
            'Vendor'  => 'The supplier or vendor who received the payment.',
            'Bill'    => 'The bill number this payment is linked to.',
        ],
        'tips' => [
            'Review unpaid bills regularly to avoid late payment penalties.',
        ],
    ],

    'listing_items.php' => [
        'title' => 'Items / Products',
        'icon'  => 'ph-cube',
        'what'  => 'Manage your product and service catalog. Set prices, track inventory, and organize items by category.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new item or service.',
            'Enter the item name, SKU, category, unit, selling price, and cost price.',
            'Save the item — it will now be available when creating invoices, quotations, and purchase orders.',
        ],
        'fields' => [
            'SKU'          => 'Stock Keeping Unit — a unique code identifying the product.',
            'Selling Price' => 'The default price charged to customers.',
            'Cost Price'    => 'Your purchase or manufacturing cost for this item.',
        ],
        'tips' => [
            'Keep SKU codes consistent for easier inventory management.',
            'Items can be both products (physical goods) and services (labor, consulting).',
        ],
    ],

    'listing_users.php' => [
        'title' => 'System Users',
        'icon'  => 'ph-users',
        'what'  => 'Manage all user accounts that can log in to the system. Control access levels, roles, and account status.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new user account.',
            'Fill in name, email, phone, and assign a <strong>Role</strong> to control what they can access.',
            'The user will receive login credentials via email (if email is configured).',
            'Use Actions to edit, deactivate, or reset a user\'s password.',
        ],
        'fields' => [
            'Role'   => 'Determines what modules and actions the user can access (e.g., Admin, Manager, Staff).',
            'Status' => 'Active users can log in; Inactive users are locked out.',
        ],
        'tips' => [
            'Deactivate users instead of deleting them to preserve audit history.',
            'Assign the most restrictive role needed — you can always upgrade later.',
        ],
    ],

    'listing_roles.php' => [
        'title' => 'Roles & Permissions',
        'icon'  => 'ph-lock-key',
        'what'  => 'Define user roles and set granular permissions for each module. Control who can view, create, edit, or delete records.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new role.',
            'Give the role a name (e.g., "Sales Manager", "Accountant").',
            'Use the permissions grid to check/uncheck View, Create, Edit, and Delete for each module.',
            'Save the role — then assign it to users from the System Users page.',
        ],
        'fields' => [
            'View'   => 'Allows seeing records in a module.',
            'Create' => 'Allows adding new records.',
            'Edit'   => 'Allows modifying existing records.',
            'Delete' => 'Allows removing records (soft delete).',
        ],
        'tips' => [
            'Test new roles by logging in as a user with that role to verify permissions.',
            'The Super Admin role always has full access and cannot be restricted.',
        ],
    ],

    'listing_vendors.php' => [
        'title' => 'Vendors / Suppliers',
        'icon'  => 'ph-storefront',
        'what'  => 'Manage your supplier and vendor directory. Track contact details, purchase history, and outstanding payables.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new vendor.',
            'Enter company name, contact person, email, phone, and payment terms.',
            'Click a vendor name to view their profile and purchase history.',
        ],
        'fields' => [
            'Payables' => 'Total outstanding amount you owe to this vendor.',
            'Status'   => 'Whether the vendor is Active or Inactive.',
        ],
        'tips' => [
            'Keep vendor payment terms updated for accurate cash-flow forecasting.',
        ],
    ],

    'listing_projects.php' => [
        'title' => 'Projects',
        'icon'  => 'ph-briefcase',
        'what'  => 'Create and track projects linked to customers. Monitor progress, assign team members, and track billable hours.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new project.',
            'Link the project to a customer and set start/end dates.',
            'Add tasks and assign team members as needed.',
            'Track project progress from the overview page.',
        ],
        'fields' => [
            'Status'   => 'Current project phase: Not Started, In Progress, On Hold, or Completed.',
            'Customer' => 'The client this project belongs to.',
        ],
        'tips' => [
            'Use project statuses to quickly identify which projects need attention.',
        ],
    ],

    'listing_jobs.php' => [
        'title' => 'Jobs',
        'icon'  => 'ph-suitcase-simple',
        'what'  => 'Manage individual jobs or work orders. Track assignments, deadlines, and completion status.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new job.',
            'Fill in the job details, assign it to a team member, and set a deadline.',
            'Update the status as work progresses.',
        ],
        'fields' => [
            'Assigned To' => 'The team member responsible for completing this job.',
            'Status'      => 'Whether the job is Pending, In Progress, or Completed.',
            'Deadline'    => 'The target completion date.',
        ],
        'tips' => [
            'Review overdue jobs regularly to keep work on track.',
        ],
    ],

    'listing_attendance.php' => [
        'title' => 'Attendance',
        'icon'  => 'ph-clock',
        'what'  => 'Track employee attendance records. View check-in/check-out times, working hours, and attendance status.',
        'steps' => [
            'Click <strong>+ New</strong> to manually log an attendance entry.',
            'Select the employee, date, check-in time, and check-out time.',
            'The system will calculate working hours automatically.',
        ],
        'fields' => [
            'Check In'     => 'The time the employee started work.',
            'Check Out'    => 'The time the employee ended work.',
            'Working Hours' => 'Total hours worked for that day.',
            'Status'        => 'Present, Absent, Half Day, or Late.',
        ],
        'tips' => [
            'Use date filters to view attendance for a specific week or month.',
        ],
    ],

    'listing_leave_requests.php' => [
        'title' => 'Leave Requests',
        'icon'  => 'ph-calendar-check',
        'what'  => 'Manage employee leave applications. Review, approve, or reject leave requests.',
        'steps' => [
            'Click <strong>+ New</strong> to submit a leave request (for yourself or on behalf of an employee).',
            'Select the leave type (Annual, Sick, Emergency, etc.), start date, and end date.',
            'Add a reason or note, then submit.',
            'Managers can approve or reject pending requests from this page.',
        ],
        'fields' => [
            'Leave Type' => 'The category of leave being requested.',
            'Duration'   => 'Number of days requested.',
            'Status'     => 'Pending, Approved, or Rejected.',
        ],
        'tips' => [
            'Leave balances are automatically updated when requests are approved.',
        ],
    ],

    'listing_sale_orders.php' => [
        'title' => 'Sales Orders',
        'icon'  => 'ph-shopping-cart',
        'what'  => 'Manage confirmed sales orders. Track fulfillment, convert orders to invoices, and monitor delivery.',
        'steps' => [
            'Click <strong>+ New</strong> to create a sales order.',
            'Select the customer, add line items with quantities and prices.',
            'Save the order — you can later convert it to an invoice or delivery note.',
        ],
        'fields' => [
            'Order No'  => 'Unique sales order reference number.',
            'Amount'    => 'Total order value including taxes.',
            'Status'    => 'Draft, Confirmed, Delivered, Invoiced, or Cancelled.',
        ],
        'tips' => [
            'Convert confirmed orders directly to invoices to save time.',
        ],
    ],

    'listing_purchase_orders.php' => [
        'title' => 'Purchase Orders',
        'icon'  => 'ph-clipboard-text',
        'what'  => 'Create and manage purchase orders sent to vendors. Track order status and delivery.',
        'steps' => [
            'Click <strong>+ New</strong> to create a purchase order.',
            'Select the vendor, add items with quantities and rates.',
            'Save and send the PO to your vendor.',
        ],
        'fields' => [
            'PO No'     => 'Unique purchase order reference number.',
            'Amount'    => 'Total purchase value.',
            'Status'    => 'Draft, Sent, Partially Received, Received, or Cancelled.',
        ],
        'tips' => [
            'Track partial deliveries by updating the received quantities.',
        ],
    ],

    'listing_shipping_advices.php' => [
        'title' => 'Shipping Advices',
        'icon'  => 'ph-note-pencil',
        'what'  => 'Create and manage shipping advice documents for cargo and freight operations.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new shipping advice.',
            'Enter cargo details, ports, vessel information, and shipping dates.',
            'Save and track the shipment status.',
        ],
        'tips' => [
            'Link shipping advices to the corresponding invoices for complete documentation.',
        ],
    ],

    'listing_shipping_invoices.php' => [
        'title' => 'Shipping Invoices',
        'icon'  => 'ph-files',
        'what'  => 'Manage invoices related to shipping and freight operations.',
        'steps' => [
            'Click <strong>+ New</strong> to create a shipping invoice.',
            'Link the invoice to the relevant shipping advice.',
            'Enter charges, taxes, and payment terms.',
        ],
        'tips' => [
            'Cross-reference with shipping advices to ensure all charges are captured.',
        ],
    ],

    'listing_shipping_stocks.php' => [
        'title' => 'Shipping Stocks',
        'icon'  => 'ph-stack',
        'what'  => 'Track cargo stock levels, warehouse positions, and inventory across shipping operations.',
        'steps' => [
            'View current stock levels for each item and warehouse.',
            'Click on an item to see detailed movement history.',
        ],
        'tips' => [
            'Regularly reconcile stock records with physical counts.',
        ],
    ],

    'listing_email_providers.php' => [
        'title' => 'Email Providers',
        'icon'  => 'ph-envelope-simple',
        'what'  => 'Configure SMTP email providers used by the system to send emails (invoices, notifications, etc.).',
        'steps' => [
            'Click <strong>+ New</strong> to add a new email provider.',
            'Enter the SMTP host, port, username, and password.',
            'Set the "From" name and email address.',
            'Test the configuration by sending a test email.',
        ],
        'tips' => [
            'Use a dedicated business email address for professional communication.',
            'SMTP passwords are encrypted at rest for security.',
        ],
    ],

    'listing_email_queue.php' => [
        'title' => 'Email Queue',
        'icon'  => 'ph-list-numbers',
        'what'  => 'View emails waiting to be sent. The system processes this queue automatically via cron jobs.',
        'steps' => [
            'Review pending emails in the queue.',
            'Failed emails will show an error reason — you can retry or delete them.',
        ],
        'tips' => [
            'If emails are stuck in the queue, check that the cron job is running and your SMTP provider is configured correctly.',
        ],
    ],

    'listing_email_history.php' => [
        'title' => 'Email History',
        'icon'  => 'ph-archive',
        'what'  => 'View a log of all emails sent from the system, including delivery status and timestamps.',
        'steps' => [
            'Search by recipient, subject, or date to find specific emails.',
            'Click on an email to view its full content and delivery details.',
        ],
        'tips' => [
            'Use this page to verify whether a customer received their invoice or notification.',
        ],
    ],

    'listing_accounts.php' => [
        'title' => 'Chart of Accounts',
        'icon'  => 'ph-tree-structure',
        'what'  => 'Manage your organization\'s chart of accounts — the foundation of your accounting system.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new account.',
            'Select the account type (Asset, Liability, Equity, Income, or Expense).',
            'Enter an account code and name.',
        ],
        'fields' => [
            'Account Code' => 'A numeric code for organizing accounts (e.g., 1000 for Assets, 4000 for Revenue).',
            'Account Type'  => 'The category: Asset, Liability, Equity, Revenue, or Expense.',
        ],
        'tips' => [
            'Follow standard accounting conventions for account codes to make reporting easier.',
        ],
    ],

    'listing_cron_jobs.php' => [
        'title' => 'Cron Jobs',
        'icon'  => 'ph-clock-clockwise',
        'what'  => 'View and manage scheduled background tasks (cron jobs) that run automatically.',
        'steps' => [
            'Review the list of scheduled tasks and their run times.',
            'Check the "Last Run" column to ensure jobs are executing on schedule.',
        ],
        'tips' => [
            'If a cron job shows errors, check the error logs for details.',
        ],
    ],

    'listing_departments.php' => [
        'title' => 'Departments',
        'icon'  => 'ph-buildings',
        'what'  => 'Manage organizational departments. Departments are used to group employees and control access.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new department.',
            'Enter the department name and optionally assign a department head.',
        ],
        'tips' => [
            'Departments are used in HR reports, payroll grouping, and leave management.',
        ],
    ],

    'listing_carriers.php' => [
        'title' => 'Carriers',
        'icon'  => 'ph-truck',
        'what'  => 'Manage shipping carriers and transport companies used for cargo delivery.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new carrier.',
            'Enter carrier name, contact details, and service type.',
        ],
        'tips' => [
            'Keep carrier contact information updated for smooth coordination.',
        ],
    ],

    'listing_ports.php' => [
        'title' => 'Ports',
        'icon'  => 'ph-map-pin-line',
        'what'  => 'Manage port locations used in shipping operations for origin and destination tracking.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new port.',
            'Enter the port name, country, and port code.',
        ],
        'tips' => [
            'Use standard UN/LOCODE port codes for consistency.',
        ],
    ],

    'index.php' => [
        'title' => 'Dashboard',
        'icon'  => 'ph-house',
        'what'  => 'Your home screen showing an overview of key business metrics, recent activities, and quick navigation to common tasks.',
        'steps' => [
            'Review the summary cards at the top for key numbers (revenue, customers, invoices, etc.).',
            'Use the sidebar or top navigation to jump to any module.',
            'Check the activity feed for recent changes made by your team.',
        ],
        'tips' => [
            'Bookmark this page as your daily starting point.',
            'The dashboard updates in real-time — refresh to see the latest data.',
        ],
    ],

    'global_settings.php' => [
        'title' => 'Global Settings',
        'icon'  => 'ph-sliders-horizontal',
        'what'  => 'Configure system-wide settings such as company information, currency, date format, logo, and email defaults.',
        'steps' => [
            'Update your company name, address, and contact details.',
            'Set the default currency and date format for the entire system.',
            'Upload your company logo — it will appear on invoices and quotations.',
            'Click <strong>Save</strong> to apply changes.',
        ],
        'tips' => [
            'Changes to global settings affect all users and modules.',
            'Upload a high-quality logo (PNG, max 2MB) for best results on printed documents.',
        ],
    ],

    'listing_payroll_runs.php' => [
        'title' => 'Payroll Runs',
        'icon'  => 'ph-money',
        'what'  => 'Process and manage monthly payroll. Generate payslips, calculate deductions, and track payment status.',
        'steps' => [
            'Click <strong>+ New</strong> to start a new payroll run for a specific month.',
            'Select the employees to include and review salary calculations.',
            'Approve the payroll to generate payslips.',
            'Mark as paid once bank transfers are complete.',
        ],
        'tips' => [
            'Always review payroll calculations before approving — corrections after approval are complex.',
            'Run payroll at least 3 days before pay day to allow time for review.',
        ],
    ],

    'listing_user_documents.php' => [
        'title' => 'User Documents',
        'icon'  => 'ph-folder-open',
        'what'  => 'Manage employee documents such as ID copies, contracts, certificates, and visa documents.',
        'steps' => [
            'Click <strong>+ New</strong> to upload a new document.',
            'Select the employee, document type, and upload the file.',
            'Set an expiry date for documents that need renewal (e.g., visas, trade licenses).',
        ],
        'tips' => [
            'Set expiry dates to get automatic reminders before documents expire.',
            'Accepted file types: PDF, JPG, PNG (max 10MB per file).',
        ],
    ],

    'listing_authentication_activity.php' => [
        'title' => 'Security Logs',
        'icon'  => 'ph-shield-check',
        'what'  => 'View login attempts, session activity, and security events. Monitor for unauthorized access.',
        'steps' => [
            'Review the log for any unfamiliar IP addresses or failed login attempts.',
            'Use date filters to focus on a specific period.',
            'Click on a row for detailed session information.',
        ],
        'tips' => [
            'Multiple failed login attempts from the same IP may indicate a brute-force attack.',
        ],
    ],

    // ── Accounting Module ──────────────────────────────────────────

    'listing_credit_notes.php' => [
        'title' => 'Credit Notes',
        'icon'  => 'ph-arrow-u-up-left',
        'what'  => 'Issue and manage credit notes to customers. Credit notes reduce the amount a customer owes, typically issued for returns, overpayments, or billing corrections.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new credit note.',
            'Select the customer and optionally link it to an original invoice.',
            'Enter the credit amount, reason, and line items.',
            'Save and apply the credit note against outstanding invoices.',
        ],
        'fields' => [
            'Credit Note No' => 'Unique reference number for the credit note.',
            'Amount'         => 'The total credit amount being issued to the customer.',
            'Status'         => 'Draft, Open, Applied, or Void.',
            'Reference'      => 'The linked invoice or reason for the credit.',
        ],
        'tips' => [
            'Credit notes can be applied to any outstanding invoice for the same customer.',
            'Voided credit notes remain visible for audit purposes but cannot be re-applied.',
        ],
    ],

    'listing_debit_notes.php' => [
        'title' => 'Debit Notes',
        'icon'  => 'ph-arrow-u-up-right',
        'what'  => 'Issue and manage debit notes sent to vendors. Debit notes increase the amount a vendor owes you, typically for billing corrections or returned goods.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new debit note.',
            'Select the vendor and optionally link it to a purchase or bill.',
            'Enter the debit amount, reason, and applicable line items.',
            'Save and apply the debit note against outstanding payables.',
        ],
        'fields' => [
            'Debit Note No' => 'Unique reference number for the debit note.',
            'Amount'        => 'The total debit amount being charged to the vendor.',
            'Status'        => 'Draft, Open, Applied, or Void.',
        ],
        'tips' => [
            'Use debit notes to correct overbilling from vendors without modifying the original bill.',
        ],
    ],

    'listing_journals.php' => [
        'title' => 'Journal Entries',
        'icon'  => 'ph-notebook',
        'what'  => 'Record manual journal entries for accounting adjustments, accruals, reclassifications, and other transactions not captured by standard invoices or payments.',
        'steps' => [
            'Click <strong>+ New</strong> to create a journal entry.',
            'Enter the debit and credit lines with the correct accounts and amounts.',
            'Ensure total debits equal total credits before saving.',
            'Add a reference number and narration for audit trail.',
        ],
        'fields' => [
            'Entry No' => 'Unique journal entry reference number.',
            'Debit'    => 'The debit side of the entry — increases assets and expenses.',
            'Credit'   => 'The credit side of the entry — increases liabilities and income.',
            'Status'   => 'Draft, Posted, or Voided.',
        ],
        'tips' => [
            'Journal entries must be balanced — total debits must equal total credits.',
            'Use journal entries sparingly; prefer invoices and bills for routine transactions.',
        ],
    ],

    'listing_accounts_report_categories.php' => [
        'title' => 'Report Categories',
        'icon'  => 'ph-chart-bar',
        'what'  => 'Manage account report categories used to group accounts for financial reporting (e.g., Balance Sheet, Profit & Loss).',
        'steps' => [
            'Click <strong>+ New</strong> to create a new report category.',
            'Enter the category name and assign it to a report type (BS or P&L).',
            'Set the display order to control where it appears in reports.',
        ],
        'fields' => [
            'Report Type' => 'Balance Sheet or Profit & Loss statement grouping.',
            'Display Order' => 'Controls the sequence of categories in generated reports.',
        ],
        'tips' => [
            'Organize categories to match your financial reporting structure for clearer reports.',
        ],
    ],

    // ── CRM Module ─────────────────────────────────────────────────

    'listing_inquiries.php' => [
        'title' => 'Inquiries',
        'icon'  => 'ph-chats-circle',
        'what'  => 'Manage customer inquiries and service requests. Track incoming questions, quote requests, and support tickets from customers and prospects.',
        'steps' => [
            'Click <strong>+ New</strong> to log a new inquiry.',
            'Enter the customer or prospect details and inquiry subject.',
            'Assign a status (New, In Progress, Resolved) and priority level.',
            'Link the inquiry to a quotation or job if it progresses to a sale.',
        ],
        'fields' => [
            'Source'   => 'How the inquiry was received — Email, Phone, Website, Walk-in.',
            'Status'   => 'Current state: New, In Progress, Resolved, or Closed.',
            'Priority' => 'Urgency level: Low, Medium, High, or Urgent.',
        ],
        'tips' => [
            'Convert resolved inquiries into quotations to track the full sales cycle.',
            'Use priority levels to ensure urgent inquiries are handled first.',
        ],
    ],

    'listing_lead_quotations.php' => [
        'title' => 'Lead Quotations',
        'icon'  => 'ph-file-plus',
        'what'  => 'View and manage quotations linked to leads in the CRM pipeline. Track which quotes have been sent, accepted, or rejected.',
        'steps' => [
            'Quotations are created from within a lead\'s detail page.',
            'Use the search box to find quotations by lead name or reference number.',
            'Click on a quotation to view its details and line items.',
            'Convert accepted quotations into invoices or sales orders.',
        ],
        'fields' => [
            'Lead'       => 'The lead this quotation is associated with.',
            'Status'     => 'Draft, Sent, Accepted, Declined, or Expired.',
            'Amount'     => 'Total quoted amount including taxes.',
            'Valid Till'  => 'The expiry date after which the quotation is no longer valid.',
        ],
        'tips' => [
            'Follow up on sent quotations that have not been responded to within 3-5 days.',
            'Accepted quotations can be directly converted to invoices to avoid re-entering data.',
        ],
    ],

    'listing_customer_contacts.php' => [
        'title' => 'Customer Contacts',
        'icon'  => 'ph-address-book',
        'what'  => 'Manage contact persons associated with each customer. Store multiple contacts per customer with their roles, emails, and phone numbers.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new contact person.',
            'Select the parent customer and enter the contact\'s name, email, and phone.',
            'Set the contact\'s role (e.g., Accounts, Operations, Decision Maker).',
            'Use the Actions column to edit or deactivate a contact.',
        ],
        'fields' => [
            'Customer' => 'The customer account this contact belongs to.',
            'Role'     => 'The contact person\'s role or designation within the customer\'s organization.',
            'Primary'  => 'The default contact used for invoices, quotations, and communications.',
        ],
        'tips' => [
            'Mark the main point of contact as "Primary" for auto-populating documents.',
            'Keep contact information updated to ensure emails reach the right person.',
        ],
    ],

    // ── Shipping Module ────────────────────────────────────────────

    'listing_consignees.php' => [
        'title' => 'Consignees',
        'icon'  => 'ph-user-list',
        'what'  => 'Manage consignees — the parties who receive shipments. Store consignee details for use in shipping advices, bills of lading, and customs documentation.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new consignee.',
            'Enter the consignee name, address, contact person, and tax/registration numbers.',
            'Save the consignee — they will be available when creating shipping advices.',
        ],
        'fields' => [
            'Address'        => 'The consignee\'s delivery or registered address.',
            'Contact Person' => 'The main point of contact at the consignee\'s location.',
            'Tax ID'         => 'Tax or registration number required for customs documentation.',
        ],
        'tips' => [
            'Keep consignee addresses accurate to avoid customs delays.',
        ],
    ],

    'listing_container_types.php' => [
        'title' => 'Container Types',
        'icon'  => 'ph-container',
        'what'  => 'Manage shipping container types used in cargo operations (e.g., 20ft, 40ft, Reefer, Open Top). Standardize container references across shipping documents.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new container type.',
            'Enter the container type name, code, and dimensions.',
            'Save — the container type will be available when creating shipping advices.',
        ],
        'fields' => [
            'Code'       => 'Standard container code (e.g., 20GP, 40HC, 20RF).',
            'Dimensions' => 'Length × Width × Height of the container.',
            'Capacity'   => 'Maximum weight or volume the container can hold.',
        ],
        'tips' => [
            'Use industry-standard codes for consistency across shipping documents.',
        ],
    ],

    'listing_commodity_types.php' => [
        'title' => 'Commodity Types',
        'icon'  => 'ph-parcel',
        'what'  => 'Manage commodity or cargo types for classification in shipping operations. Used in customs declarations and freight documentation.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new commodity type.',
            'Enter the commodity name, description, and HS code if applicable.',
            'Save — it will be available when creating shipping advices and invoices.',
        ],
        'fields' => [
            'HS Code' => 'Harmonized System code for customs classification.',
            'Type'    => 'General category: General Cargo, Dangerous Goods, Perishable, etc.',
        ],
        'tips' => [
            'Accurate commodity classification helps avoid customs delays and penalties.',
        ],
    ],

    'listing_hscodes.php' => [
        'title' => 'HS Codes',
        'icon'  => 'ph-barcode',
        'what'  => 'Manage Harmonized System (HS) codes used for international trade customs classification. HS codes determine import/export duties and regulatory requirements.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new HS code.',
            'Enter the HS code, description, and duty rate if applicable.',
            'Link HS codes to commodities and items for customs documentation.',
        ],
        'fields' => [
            'HS Code'     => 'The internationally standardized numerical code for the product.',
            'Description' => 'The official description of the goods under this code.',
            'Duty Rate'   => 'The applicable import/export duty percentage.',
        ],
        'tips' => [
            'Using the correct HS code is critical — incorrect classification can result in fines or shipment delays.',
            'Consult your customs broker if unsure about the correct HS code for a product.',
        ],
    ],

    'listing_incoterms.php' => [
        'title' => 'Incoterms',
        'icon'  => 'ph-seal-check',
        'what'  => 'Manage International Commercial Terms (Incoterms) that define the responsibilities of buyers and sellers in international trade — who pays for shipping, insurance, and customs.',
        'steps' => [
            'Review the standard Incoterms listed (FOB, CIF, EXW, DDP, etc.).',
            'Click <strong>+ New</strong> to add a custom term if needed.',
            'Use Incoterms when creating quotations, invoices, and shipping documents.',
        ],
        'fields' => [
            'Code'        => 'The standard Incoterm code (e.g., FOB, CIF, EXW).',
            'Description' => 'What this term means — who is responsible for costs and risks.',
        ],
        'tips' => [
            'Always specify the Incoterm version (e.g., Incoterms 2020) on your documents.',
            'Choose Incoterms carefully — they affect pricing, insurance, and liability.',
        ],
    ],

    'listing_exit_points.php' => [
        'title' => 'Exit Points',
        'icon'  => 'ph-sign-out',
        'what'  => 'Manage exit points (ports, airports, border crossings) used for customs declarations and shipping documentation.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new exit point.',
            'Enter the exit point name, type (Sea, Air, Land), and country.',
            'Save — it will be available when creating customs and shipping documents.',
        ],
        'fields' => [
            'Type'    => 'The mode of transport: Sea Port, Airport, or Land Border.',
            'Country' => 'The country where this exit point is located.',
        ],
        'tips' => [
            'Maintain accurate exit point data for smooth customs processing.',
        ],
    ],

    // ── HR Module ──────────────────────────────────────────────────

    'listing_designations.php' => [
        'title' => 'Designations',
        'icon'  => 'ph-badge',
        'what'  => 'Manage job designations or titles within your organization. Designations are used to define employee roles and hierarchy.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new designation.',
            'Enter the designation name (e.g., Senior Accountant, Operations Manager).',
            'Save — the designation will be available when creating or editing employee records.',
        ],
        'tips' => [
            'Keep designations consistent across the organization for accurate HR reporting.',
            'Designations appear on offer letters, employee profiles, and payroll reports.',
        ],
    ],

    'listing_leave_types.php' => [
        'title' => 'Leave Types',
        'icon'  => 'ph-calendar-blank',
        'what'  => 'Configure the types of leave available to employees (e.g., Annual Leave, Sick Leave, Emergency Leave). Set default allowances and carry-forward rules.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new leave type.',
            'Enter the leave type name, default days allowed per year, and whether it carries forward.',
            'Save — employees can then request this leave type from their profiles.',
        ],
        'fields' => [
            'Default Days' => 'The number of days allocated per year for this leave type.',
            'Carry Forward' => 'Whether unused days roll over to the next year.',
            'Paid'         => 'Whether this leave type is paid or unpaid.',
        ],
        'tips' => [
            'Review leave type settings at the start of each financial year.',
            'Different leave types can have different carry-forward and encashment rules.',
        ],
    ],

    'listing_employee_salaries.php' => [
        'title' => 'Employee Salaries',
        'icon'  => 'ph-money',
        'what'  => 'View and manage salary structures for employees. Define basic pay, allowances, deductions, and net salary components.',
        'steps' => [
            'Click on an employee to view their salary breakdown.',
            'Use <strong>+ New</strong> to set up or update an employee\'s salary structure.',
            'Configure earnings (basic, HRA, transport) and deductions (tax, insurance).',
            'Save — the salary structure will be used in monthly payroll runs.',
        ],
        'fields' => [
            'Basic Pay'  => 'The base salary before allowances and deductions.',
            'Allowances' => 'Additional components like HRA, transport, medical allowance.',
            'Deductions' => 'Amounts subtracted like tax, insurance, loan repayments.',
            'Net Pay'    => 'The final take-home amount after all calculations.',
        ],
        'tips' => [
            'Ensure salary structures are finalized before running monthly payroll.',
            'Changes to salary structures take effect from the next payroll run.',
        ],
    ],

    // ── System / Master Data ───────────────────────────────────────

    'listing_alerts.php' => [
        'title' => 'System Alerts',
        'icon'  => 'ph-bell-ringing',
        'what'  => 'View system-generated alerts and notifications. These include error warnings, overdue reminders, and operational notifications.',
        'steps' => [
            'Review the list of active alerts sorted by severity and date.',
            'Click on an alert to view its details and suggested action.',
            'Dismiss or mark alerts as read once the issue is resolved.',
        ],
        'tips' => [
            'Regularly check alerts to catch and resolve issues early.',
            'Critical alerts (red) should be addressed immediately.',
        ],
    ],

    'listing_banks.php' => [
        'title' => 'Bank Accounts',
        'icon'  => 'ph-bank',
        'what'  => 'Manage your organization\'s bank accounts. Track balances, configure account details for reconciliation, and link bank accounts to payment transactions.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new bank account.',
            'Enter the bank name, account number, IBAN, SWIFT code, and opening balance.',
            'Save — the bank account will be available in payment and receipt forms.',
        ],
        'fields' => [
            'Account Number' => 'The bank account number or IBAN.',
            'Opening Balance' => 'The balance at the start of the tracking period.',
            'Currency'       => 'The currency of this bank account.',
        ],
        'tips' => [
            'Keep bank account details accurate for seamless payment processing.',
            'Regularly reconcile bank statements with system records.',
        ],
    ],

    'listing_banned_words.php' => [
        'title' => 'Banned Words',
        'icon'  => 'ph-shield-warning',
        'what'  => 'Manage a list of banned or restricted words that are filtered from user-generated content such as comments, notes, and descriptions.',
        'steps' => [
            'Click <strong>+ New</strong> to add a banned word or phrase.',
            'Enter the word and select the severity (block or warn).',
            'Save — the system will automatically flag or block this word in content.',
        ],
        'tips' => [
            'Review and update the banned words list periodically to maintain content quality.',
        ],
    ],

    'listing_categories.php' => [
        'title' => 'Categories',
        'icon'  => 'ph-tag',
        'what'  => 'Manage item and product categories used to organize your catalog. Categories help classify items for easier searching and reporting.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new category.',
            'Enter the category name and optionally a parent category for sub-categorization.',
            'Save — the category will be available when creating or editing items.',
        ],
        'fields' => [
            'Parent Category' => 'The higher-level category this belongs to (for nested categories).',
            'Item Count'      => 'The number of items currently assigned to this category.',
        ],
        'tips' => [
            'Use a logical hierarchy (e.g., Electronics > Mobile Phones) for better organization.',
            'Categories affect item filtering in invoices, quotations, and stock reports.',
        ],
    ],

    'listing_subcategories.php' => [
        'title' => 'Subcategories',
        'icon'  => 'ph-tag-chevron',
        'what'  => 'Manage subcategories for more granular classification of items within parent categories.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new subcategory.',
            'Select the parent category and enter the subcategory name.',
            'Save — the subcategory will be available when creating items under the parent category.',
        ],
        'tips' => [
            'Subcategories provide a second level of organization for detailed inventory classification.',
        ],
    ],

    'listing_currencies.php' => [
        'title' => 'Currencies',
        'icon'  => 'ph-currency-circle-dollar',
        'what'  => 'Manage currencies used in transactions. Set exchange rates, base currency, and active currencies for multi-currency invoicing.',
        'steps' => [
            'Review the list of configured currencies and their exchange rates.',
            'Click <strong>+ New</strong> to add a new currency.',
            'Enter the currency code (e.g., USD, EUR, AED), symbol, and exchange rate.',
            'Set one currency as the base currency for your organization.',
        ],
        'fields' => [
            'Currency Code'  => 'ISO 4217 code (e.g., USD, EUR, GBP, AED).',
            'Exchange Rate'  => 'The rate relative to your base currency.',
            'Base Currency'  => 'The primary currency for all financial reporting.',
        ],
        'tips' => [
            'Update exchange rates regularly for accurate multi-currency reporting.',
            'Changing the base currency affects all historical reports — do this only if necessary.',
        ],
    ],

    'listing_disposable_email_domains.php' => [
        'title' => 'Disposable Email Domains',
        'icon'  => 'ph-at',
        'what'  => 'Manage a list of disposable/temporary email domains (e.g., guerrillamail.com) that are blocked during user registration to prevent spam accounts.',
        'steps' => [
            'Review the current list of blocked domains.',
            'Click <strong>+ New</strong> to add a new disposable email domain.',
            'Enter the domain name (e.g., tempmail.com) and save.',
        ],
        'tips' => [
            'The system automatically checks new registrations against this list.',
            'Update the list periodically as new disposable email services appear.',
        ],
    ],

    'listing_document_categories.php' => [
        'title' => 'Document Categories',
        'icon'  => 'ph-folder-notch',
        'what'  => 'Manage categories for organizing uploaded documents (e.g., Contracts, IDs, Certificates, Invoices). Categories help users quickly find and classify files.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new document category.',
            'Enter the category name and an optional description.',
            'Save — the category will be available when uploading documents.',
        ],
        'tips' => [
            'Use clear, descriptive category names for easy document retrieval.',
            'Document categories are used across HR, CRM, and Shipping modules.',
        ],
    ],

    'listing_documents.php' => [
        'title' => 'Documents',
        'icon'  => 'ph-folder-open',
        'what'  => 'View and manage all uploaded documents across the system. Browse, search, and download files linked to customers, employees, jobs, and other records.',
        'steps' => [
            'Use the search box to find documents by name, category, or linked record.',
            'Click on a document to view its details and download link.',
            'Use the Actions column to edit document metadata or delete the file.',
        ],
        'fields' => [
            'Category' => 'The document classification (e.g., Contract, ID, Certificate).',
            'Linked To' => 'The record this document is attached to (customer, employee, job, etc.).',
            'Uploaded'  => 'The date and user who uploaded the document.',
        ],
        'tips' => [
            'Organize documents by category for faster retrieval.',
            'Set expiry dates on time-sensitive documents (visas, licenses) for reminders.',
        ],
    ],

    'listing_geo_countries.php' => [
        'title' => 'Countries',
        'icon'  => 'ph-globe',
        'what'  => 'Manage the list of countries used across the system for addresses, shipping origins/destinations, and customer/vendor locations.',
        'steps' => [
            'Review the list of configured countries.',
            'Click <strong>+ New</strong> to add a country not in the list.',
            'Enter the country name, ISO code, and dialing code.',
        ],
        'fields' => [
            'ISO Code' => 'Two-letter country code (e.g., US, AE, GB).',
            'Dial Code' => 'International dialing prefix (e.g., +1, +971, +44).',
        ],
        'tips' => [
            'Countries are used in address fields across customers, vendors, and shipping documents.',
        ],
    ],

    'listing_geo_states.php' => [
        'title' => 'States / Provinces',
        'icon'  => 'ph-map-trifold',
        'what'  => 'Manage states, provinces, or regions within countries. Used for address classification and regional reporting.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new state or province.',
            'Select the parent country and enter the state name and code.',
            'Save — the state will be available in address forms across the system.',
        ],
        'tips' => [
            'States are linked to countries and appear in customer, vendor, and shipping address forms.',
        ],
    ],

    'listing_geo_cities.php' => [
        'title' => 'Cities',
        'icon'  => 'ph-buildings',
        'what'  => 'Manage cities used in address fields across the system. Cities are linked to states/provinces for hierarchical address management.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new city.',
            'Select the parent country and state, then enter the city name.',
            'Save — the city will be available in address dropdowns.',
        ],
        'tips' => [
            'Cities are part of the hierarchical address system: Country → State → City.',
        ],
    ],

    'listing_job_statuses.php' => [
        'title' => 'Job Statuses',
        'icon'  => 'ph-traffic-sign',
        'what'  => 'Manage the status workflow for jobs. Define custom statuses that represent each stage of a job\'s lifecycle (e.g., New, In Progress, Completed, Closed).',
        'steps' => [
            'Click <strong>+ New</strong> to create a new job status.',
            'Enter the status name, select a color for the badge, and set the display order.',
            'Save — the status will be available when updating jobs.',
        ],
        'fields' => [
            'Color'   => 'The badge color used to visually identify this status in listings.',
            'Order'   => 'Controls the sequence of statuses in the job workflow.',
            'Default' => 'Whether this status is automatically assigned to new jobs.',
        ],
        'tips' => [
            'Define a logical workflow progression (e.g., Draft → Open → In Progress → Completed → Closed).',
            'Use distinct colors for each status to quickly identify job stages at a glance.',
        ],
    ],

    'listing_modules.php' => [
        'title' => 'System Modules',
        'icon'  => 'ph-puzzle-piece',
        'what'  => 'View all registered system modules. Modules represent functional areas of the application (e.g., Customers, Invoices, Jobs) that can have permissions configured.',
        'steps' => [
            'Review the list of available modules and their status.',
            'Click on a module to view its details and associated permissions.',
            'Modules are used in the Roles & Permissions configuration.',
        ],
        'tips' => [
            'Module permissions are configured from the Roles & Permissions page.',
            'Inactive modules are hidden from the sidebar and navigation.',
        ],
    ],

    'listing_organizations.php' => [
        'title' => 'Organizations',
        'icon'  => 'ph-buildings',
        'what'  => 'Manage organizations (business entities) within the system. Each organization has its own data, settings, and user access controls.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new organization.',
            'Enter the organization name, address, and contact details.',
            'Assign users and set their roles within the organization.',
            'Switch between organizations using the organization selector in the header.',
        ],
        'fields' => [
            'Status'    => 'Active or Inactive — inactive organizations cannot be accessed.',
            'Members'   => 'Number of users assigned to this organization.',
            'Owner'     => 'The user who has administrative control over this organization.',
        ],
        'tips' => [
            'Each organization\'s data is completely isolated — users only see data for their assigned organizations.',
            'The organization owner can manage members and settings for that organization.',
        ],
    ],

    // ── Customer Context Pages ─────────────────────────────────────

    'listing_customer_invoices.php' => [
        'title' => 'Customer Invoices',
        'icon'  => 'ph-receipt',
        'what'  => 'View invoices for a specific customer. This is a filtered view of invoices belonging to the selected customer account.',
        'steps' => [
            'The list is automatically filtered to show only invoices for the selected customer.',
            'Click <strong>+ New</strong> to create a new invoice for this customer.',
            'Click an <strong>invoice number</strong> to view full invoice details.',
            'Use the <strong>Actions</strong> column to edit, download PDF, or delete an invoice.',
        ],
        'fields' => [
            'Status'      => 'Current invoice state: Draft, Sent, Paid, Unpaid, or Overdue.',
            'Amount'      => 'The total invoice amount including taxes.',
            'Balance Due' => 'Remaining unpaid amount on the invoice.',
        ],
        'tips' => [
            'This view is scoped to a single customer — go to the main Invoices page to see all invoices.',
            'Overdue invoices should be followed up on promptly.',
        ],
    ],

    'listing_customer_payments.php' => [
        'title' => 'Customer Payments',
        'icon'  => 'ph-arrow-circle-down',
        'what'  => 'View payments received from a specific customer. This is a filtered view of payment records belonging to the selected customer account.',
        'steps' => [
            'The list shows only payments received from the selected customer.',
            'Click <strong>+ New</strong> to record a new payment for this customer.',
            'Click a payment to view details and linked invoices.',
            'Use the <strong>Actions</strong> column to edit or delete a payment record.',
        ],
        'fields' => [
            'Amount'       => 'The payment amount received.',
            'Payment Mode' => 'How the payment was made — Cash, Bank Transfer, Cheque, etc.',
            'Reference'    => 'The payment reference or cheque number.',
        ],
        'tips' => [
            'Link payments to specific invoices for accurate balance tracking.',
            'This view is scoped to one customer — go to Payments Received for all payments.',
        ],
    ],

    // ── Accounting — Payment Configuration ─────────────────────────

    'listing_payment_methods.php' => [
        'title' => 'Payment Methods',
        'icon'  => 'ph-credit-card',
        'what'  => 'Manage the payment methods available in your system (e.g., Cash, Bank Transfer, Cheque, Credit Card). Payment methods are used when recording payments received and payments made.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new payment method.',
            'Enter the payment method name and an optional description.',
            'Save — the method will be available in payment forms throughout the system.',
        ],
        'tips' => [
            'Use consistent naming conventions for payment methods to simplify reporting.',
            'Inactive payment methods will not appear in payment form dropdowns.',
        ],
    ],

    'listing_payment_terms.php' => [
        'title' => 'Payment Terms',
        'icon'  => 'ph-timer',
        'what'  => 'Manage payment terms that define when payments are due (e.g., Net 30, Due on Receipt, Net 60). Payment terms are applied to invoices, quotations, and vendor bills.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new payment term.',
            'Enter the term name and the number of days until payment is due.',
            'Save — the term will be available when creating invoices and bills.',
        ],
        'fields' => [
            'Due Days' => 'The number of days from the invoice date until payment is due.',
        ],
        'tips' => [
            'Common terms: "Net 30" (due in 30 days), "Due on Receipt" (due immediately).',
            'Set appropriate payment terms on customer accounts to auto-populate invoices.',
        ],
    ],

    // ── Accounting — Purchases ─────────────────────────────────────

    'listing_purchases.php' => [
        'title' => 'Purchases / Bills',
        'icon'  => 'ph-shopping-bag',
        'what'  => 'Manage purchase bills received from vendors. Track what your organization owes, record payments made, and monitor payables.',
        'steps' => [
            'Click <strong>+ New</strong> to record a new purchase bill.',
            'Select the vendor, add line items with quantities and rates.',
            'Save the bill — it will update your accounts payable.',
            'Record payments against bills from the Payments Made page.',
        ],
        'fields' => [
            'Bill No'     => 'Unique bill or invoice reference number from the vendor.',
            'Amount'      => 'Total bill amount including taxes.',
            'Balance Due' => 'Remaining unpaid amount on the bill.',
            'Status'      => 'Draft, Unpaid, Paid, or Overdue.',
        ],
        'tips' => [
            'Link purchases to expense accounts for accurate financial reporting.',
            'Record payments promptly to keep your payables up to date.',
        ],
    ],

    'listing_purchase_types.php' => [
        'title' => 'Purchase Types',
        'icon'  => 'ph-tag',
        'what'  => 'Manage purchase type categories used to classify purchases and bills (e.g., Raw Materials, Office Supplies, Subcontractor Services).',
        'steps' => [
            'Click <strong>+ New</strong> to add a new purchase type.',
            'Enter the purchase type name and an optional description.',
            'Save — the type will be available when creating purchase records.',
        ],
        'tips' => [
            'Use purchase types to categorize spending for budget analysis and reporting.',
        ],
    ],

    // ── Accounting — Sales ─────────────────────────────────────────

    'listing_services.php' => [
        'title' => 'Services',
        'icon'  => 'ph-wrench',
        'what'  => 'Manage service offerings that can be sold to customers (e.g., Consulting, Freight, Installation). Services are used in invoices and quotations alongside physical products.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new service.',
            'Enter the service name, description, selling rate, and income account.',
            'Save — the service will be available when creating invoices and quotations.',
        ],
        'fields' => [
            'Rate'         => 'The default selling price for this service.',
            'Income Account' => 'The chart-of-accounts entry where revenue is recorded.',
        ],
        'tips' => [
            'Services do not track inventory — use Items for physical products.',
            'Set appropriate income accounts for accurate revenue reporting.',
        ],
    ],

    'listing_sale_types.php' => [
        'title' => 'Sale Types',
        'icon'  => 'ph-tag',
        'what'  => 'Manage sale type categories used to classify sales and invoices (e.g., Domestic, Export, Wholesale, Retail).',
        'steps' => [
            'Click <strong>+ New</strong> to add a new sale type.',
            'Enter the sale type name and an optional description.',
            'Save — the type will be available when creating sales records.',
        ],
        'tips' => [
            'Use sale types to segment revenue for analysis and reporting.',
        ],
    ],

    // ── HR Module — Extended ───────────────────────────────────────

    'listing_payroll_components.php' => [
        'title' => 'Payroll Components',
        'icon'  => 'ph-puzzle-piece',
        'what'  => 'Manage salary components used in payroll calculations — earnings (basic, HRA, transport) and deductions (tax, insurance, loans). Components are assembled into salary structures.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new payroll component.',
            'Enter the component name, type (Earning or Deduction), and calculation method.',
            'Save — the component will be available when building salary structures.',
        ],
        'fields' => [
            'Type'       => 'Earning (adds to salary) or Deduction (subtracts from salary).',
            'Calculation' => 'Fixed amount or percentage of basic pay.',
            'Taxable'    => 'Whether this component is subject to income tax.',
        ],
        'tips' => [
            'Create all recurring earnings and deductions as components before setting up salary structures.',
            'Components can be marked as taxable or non-taxable for payroll compliance.',
        ],
    ],

    'listing_payslips.php' => [
        'title' => 'Payslips',
        'icon'  => 'ph-file-doc',
        'what'  => 'View and manage employee payslips generated from payroll runs. Each payslip shows the full salary breakdown with earnings and deductions.',
        'steps' => [
            'Payslips are automatically generated when a payroll run is approved.',
            'Use the search and filter options to find payslips by employee or month.',
            'Click on a payslip to view the detailed salary breakdown.',
            'Download or email payslips to employees from the Actions menu.',
        ],
        'fields' => [
            'Gross Pay'  => 'Total earnings before deductions.',
            'Deductions' => 'Total deductions including tax, insurance, and loans.',
            'Net Pay'    => 'The final take-home amount paid to the employee.',
        ],
        'tips' => [
            'Payslips cannot be edited directly — modify the salary structure or payroll run instead.',
            'Ensure all deductions are correctly configured before running payroll.',
        ],
    ],

    'listing_salary_structures.php' => [
        'title' => 'Salary Structures',
        'icon'  => 'ph-stack',
        'what'  => 'Define salary structures for employees by combining payroll components (basic pay, allowances, deductions) into a complete compensation package.',
        'steps' => [
            'Click <strong>+ New</strong> to create a salary structure for an employee.',
            'Select the employee and assign each payroll component with its amount.',
            'Review the gross and net pay calculations.',
            'Save — the structure will be used in monthly payroll runs.',
        ],
        'fields' => [
            'Employee'    => 'The employee this salary structure belongs to.',
            'Effective From' => 'The date from which this salary structure takes effect.',
            'Net Salary'  => 'The calculated take-home amount after all components.',
        ],
        'tips' => [
            'Only one active salary structure per employee at a time — new structures supersede previous ones.',
            'Changes take effect from the next payroll run.',
        ],
    ],

    // ── Shipping Module — Extended ─────────────────────────────────

    'listing_shippers.php' => [
        'title' => 'Shippers',
        'icon'  => 'ph-boat',
        'what'  => 'Manage shipping companies and freight forwarders that handle the physical transportation of cargo. Store contact details, service types, and agreements.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new shipper.',
            'Enter the shipper name, contact details, and service type (Sea, Air, Land).',
            'Save — the shipper will be available when creating shipping advices.',
        ],
        'tips' => [
            'Keep shipper contact details updated for smooth cargo coordination.',
            'Distinguish between carriers (transport providers) and shippers (freight forwarders).',
        ],
    ],

    'listing_shipping_advice_items.php' => [
        'title' => 'Shipping Advice Items',
        'icon'  => 'ph-list-checks',
        'what'  => 'View and manage the line items within shipping advices. Each row represents a specific cargo item with its quantity, weight, and container assignment.',
        'steps' => [
            'This page shows items from the selected shipping advice.',
            'Click <strong>+ New</strong> to add a cargo item to the shipping advice.',
            'Enter the item description, quantity, weight, volume, and container number.',
            'Use the Actions column to edit or remove items.',
        ],
        'fields' => [
            'Quantity' => 'The number of units or packages for this item.',
            'Weight'   => 'Gross weight of the cargo item.',
            'Container' => 'The container number this item is loaded into.',
        ],
        'tips' => [
            'Ensure weights and quantities match the commercial invoice and packing list.',
        ],
    ],

    'listing_shipping_customers.php' => [
        'title' => 'Shipping Customers',
        'icon'  => 'ph-user-circle-gear',
        'what'  => 'Manage customer records specific to the shipping module. These are the parties involved in shipping transactions — buyers, notify parties, and consignees.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new shipping customer.',
            'Enter the company name, address, contact person, and tax/registration details.',
            'Save — the customer will be available when creating shipping documents.',
        ],
        'fields' => [
            'Role'    => 'Buyer, Notify Party, Consignee, or Shipper.',
            'Address' => 'The registered or shipping address used on documents.',
        ],
        'tips' => [
            'Accurate customer data ensures correct customs documentation.',
        ],
    ],

    'listing_storage_types.php' => [
        'title' => 'Storage Types',
        'icon'  => 'ph-warehouse',
        'what'  => 'Manage storage type categories for warehouse and cargo operations (e.g., Dry Storage, Cold Storage, Open Yard, Bonded Warehouse).',
        'steps' => [
            'Click <strong>+ New</strong> to add a new storage type.',
            'Enter the storage type name, description, and temperature requirements if applicable.',
            'Save — the type will be available when managing shipping stocks.',
        ],
        'tips' => [
            'Proper storage type classification helps with warehouse planning and cargo handling.',
        ],
    ],

    'listing_storage_subtypes.php' => [
        'title' => 'Storage Subtypes',
        'icon'  => 'ph-stack-simple',
        'what'  => 'Manage storage subtypes for more granular classification within storage types (e.g., Ambient, Chilled, Frozen under Cold Storage).',
        'steps' => [
            'Click <strong>+ New</strong> to add a new storage subtype.',
            'Select the parent storage type and enter the subtype name.',
            'Save — the subtype will be available when managing stocks.',
        ],
        'tips' => [
            'Subtypes provide a second level of organization for warehouse management.',
        ],
    ],

    // ── System / Master Data — Extended ────────────────────────────

    'listing_organization_roles.php' => [
        'title' => 'Organization Roles',
        'icon'  => 'ph-users-three',
        'what'  => 'Manage roles within an organization. Organization roles define the access level and responsibilities of team members (e.g., Owner, Admin, Member, Viewer).',
        'steps' => [
            'Click <strong>+ New</strong> to create a new organization role.',
            'Enter the role name and configure the permissions for this role.',
            'Save — the role will be available when assigning members to organizations.',
        ],
        'tips' => [
            'Organization roles are separate from system roles — they control access within a specific organization.',
            'The Owner role has full access and cannot be deleted.',
        ],
    ],

    // 'listing_pages.php' removed — pages module decommissioned

    'listing_pages_audit.php' => [
        'title' => 'Page Access Audit',
        'icon'  => 'ph-shield-check',
        'what'  => 'Track and audit page access across the system. View which users accessed which pages and when, for security monitoring and compliance.',
        'steps' => [
            'Review the access log sorted by date and time.',
            'Use filters to search by user, page, or date range.',
            'Click on an entry to view detailed session information.',
        ],
        'tips' => [
            'Use this page for security audits and to investigate unauthorized access.',
            'Regular audit reviews help maintain compliance with security policies.',
        ],
    ],

    'listing_setup_groups.php' => [
        'title' => 'Setup Groups',
        'icon'  => 'ph-folders',
        'what'  => 'Manage setup groups that organize configuration options and settings into logical categories for easier administration.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new setup group.',
            'Enter the group name and description.',
            'Save — the group will be available for organizing setup items.',
        ],
        'tips' => [
            'Use groups to logically cluster related settings for faster navigation.',
        ],
    ],

    'listing_setup_sources.php' => [
        'title' => 'Lead Sources',
        'icon'  => 'ph-funnel',
        'what'  => 'Manage the sources from which leads and inquiries originate (e.g., Website, Referral, Trade Show, Cold Call, Social Media).',
        'steps' => [
            'Click <strong>+ New</strong> to add a new lead source.',
            'Enter the source name and an optional description.',
            'Save — the source will be available when creating leads and inquiries.',
        ],
        'tips' => [
            'Track lead sources to measure the effectiveness of marketing campaigns.',
            'Keep sources updated as new channels emerge.',
        ],
    ],

    'listing_setup_statuses.php' => [
        'title' => 'Custom Statuses',
        'icon'  => 'ph-traffic-cone',
        'what'  => 'Manage custom status options used across various modules (e.g., Leads, Jobs, Projects). Define workflow stages specific to your business processes.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new status.',
            'Enter the status name, select the module it applies to, and choose a color.',
            'Save — the status will be available in the corresponding module.',
        ],
        'fields' => [
            'Module' => 'The module this status applies to (e.g., Leads, Jobs, Projects).',
            'Color'  => 'The badge color for visual identification.',
            'Order'  => 'Controls the sequence of statuses in the workflow.',
        ],
        'tips' => [
            'Define a clear workflow progression for each module.',
            'Use distinct colors to quickly identify status stages at a glance.',
        ],
    ],

    'listing_setup_tags.php' => [
        'title' => 'Tags',
        'icon'  => 'ph-hash',
        'what'  => 'Manage tags that can be attached to various records across the system (e.g., customers, leads, jobs). Tags provide flexible, cross-module categorization.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new tag.',
            'Enter the tag name and select a color for the badge.',
            'Save — the tag will be available to attach to records.',
        ],
        'tips' => [
            'Tags are cross-module — the same tag can be used on customers, leads, and jobs.',
            'Use tags to create custom groupings that don\'t fit into standard categories.',
        ],
    ],

    'listing_system_settings.php' => [
        'title' => 'System Settings',
        'icon'  => 'ph-gear-six',
        'what'  => 'Manage advanced system-level configuration options including performance settings, feature toggles, integration endpoints, and technical parameters.',
        'steps' => [
            'Review the list of configurable system settings.',
            'Click on a setting to view its description and current value.',
            'Modify the value and save your changes.',
        ],
        'tips' => [
            'Changes to system settings affect all users and modules — proceed with caution.',
            'Document any changes made to system settings for troubleshooting reference.',
        ],
    ],

    'listing_tax_treatments.php' => [
        'title' => 'Tax Treatments',
        'icon'  => 'ph-percent',
        'what'  => 'Manage tax treatment categories that define how transactions are taxed (e.g., Taxable, Exempt, Zero-Rated, Out of Scope). Used for VAT/GST compliance and reporting.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new tax treatment.',
            'Enter the treatment name, description, and applicable tax rate.',
            'Save — the treatment will be available in invoice and bill forms.',
        ],
        'fields' => [
            'Tax Rate' => 'The percentage of tax applied under this treatment.',
            'Type'     => 'Whether the treatment is Taxable, Exempt, Zero-Rated, or Out of Scope.',
        ],
        'tips' => [
            'Correct tax treatment assignment is critical for accurate VAT/GST returns.',
            'Consult your tax advisor if unsure about the correct treatment for specific items.',
        ],
    ],

    'listing_units.php' => [
        'title' => 'Units of Measure',
        'icon'  => 'ph-ruler',
        'what'  => 'Manage units of measure used for items and products (e.g., Piece, Kg, Meter, Box, Hour). Units are referenced on invoices, quotations, and inventory records.',
        'steps' => [
            'Click <strong>+ New</strong> to add a new unit of measure.',
            'Enter the unit name, abbreviation, and unit type (e.g., Count, Weight, Length, Volume).',
            'Save — the unit will be available when creating items.',
        ],
        'fields' => [
            'Abbreviation' => 'The short form (e.g., pcs, kg, m, ltr).',
            'Type'         => 'The category: Count, Weight, Length, Volume, or Time.',
        ],
        'tips' => [
            'Use standard abbreviations for consistency across documents.',
            'Ensure the correct unit is assigned to items to avoid inventory discrepancies.',
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    //  NON-LISTING PAGES — Edit / Create / Overview / Dashboard / Reports
    // ═══════════════════════════════════════════════════════════════

    // ── Dashboard Hub Pages ───────────────────────────────────────

    'dashboard_accounting.php' => [
        'title' => 'Accounting Dashboard',
        'icon'  => 'ph-chart-pie-slice',
        'what'  => 'An overview of your accounting module showing key financial metrics, recent transactions, outstanding receivables/payables, and quick links to accounting functions.',
        'steps' => [
            'Review the summary cards for total income, expenses, receivables, and payables.',
            'Use the quick-action buttons to jump to invoices, bills, or journal entries.',
            'Check recent transaction feeds for the latest activity.',
        ],
        'tips' => [
            'Use this dashboard as your daily accounting starting point.',
            'Click on any metric card to drill down into the detailed report.',
        ],
    ],

    'dashboard_crm.php' => [
        'title' => 'CRM Dashboard',
        'icon'  => 'ph-chart-line-up',
        'what'  => 'An overview of your sales pipeline showing lead counts, conversion rates, upcoming follow-ups, and recent CRM activity.',
        'steps' => [
            'Review the pipeline summary for leads at each stage.',
            'Check upcoming follow-ups and overdue tasks.',
            'Use quick links to jump to leads, quotations, or inquiries.',
        ],
        'tips' => [
            'Update lead statuses regularly for accurate pipeline metrics.',
            'Check this dashboard daily to stay on top of sales activities.',
        ],
    ],

    'dashboard_hr.php' => [
        'title' => 'HR Dashboard',
        'icon'  => 'ph-users-three',
        'what'  => 'An overview of human resources metrics including headcount, attendance, pending leave requests, and upcoming payroll information.',
        'steps' => [
            'Review employee count, attendance rates, and leave balances.',
            'Check pending leave requests that need approval.',
            'Use quick links to manage attendance, leaves, and payroll.',
        ],
        'tips' => [
            'Process pending leave requests promptly to maintain employee satisfaction.',
            'Review attendance trends for any patterns that need attention.',
        ],
    ],

    'dashboard_shipping.php' => [
        'title' => 'Shipping Dashboard',
        'icon'  => 'ph-boat',
        'what'  => 'An overview of shipping operations including active shipments, pending advices, cargo status, and recent shipping activity.',
        'steps' => [
            'Review active shipments and their current status.',
            'Check pending shipping advices that need processing.',
            'Use quick links to manage shipping advices, invoices, and stocks.',
        ],
        'tips' => [
            'Monitor shipment statuses to ensure timely deliveries.',
            'Keep shipping documentation up to date for customs compliance.',
        ],
    ],

    'dashboard_sitemap.php' => [
        'title' => 'Sitemap Dashboard',
        'icon'  => 'ph-map-trifold',
        'what'  => 'A visual overview of the system navigation structure showing all modules, pages, and their relationships.',
        'steps' => [
            'Browse the sitemap to understand the system structure.',
            'Click on any page to navigate directly to it.',
            'Use this as a quick navigation tool for rarely visited pages.',
        ],
        'tips' => [
            'Use this page to discover features you may not have used before.',
        ],
    ],

    // ── Invoice Module — Edit / Overview ──────────────────────────

    'invoices.php' => [
        'title' => 'Invoice Details',
        'icon'  => 'ph-receipt',
        'what'  => 'Create a new invoice or edit an existing one. Fill in customer details, line items, taxes, and payment terms to generate a professional invoice.',
        'steps' => [
            'Select or search for the customer from the dropdown.',
            'Add line items with item name, quantity, rate, and tax.',
            'Set the invoice date, due date, and payment terms.',
            'Add notes or terms in the footer section.',
            'Save as Draft or click Save and Send to email the invoice to the customer.',
        ],
        'fields' => [
            'Invoice Number' => 'Auto-generated or manually entered unique reference.',
            'Due Date'       => 'The date by which payment is expected.',
            'Terms'          => 'Payment terms (e.g., Net 30, Due on Receipt).',
        ],
        'tips' => [
            'Save as Draft to continue editing later without sending.',
            'Use the "Add Discount" option to apply line-level or invoice-level discounts.',
        ],
    ],

    'invoice_overview.php' => [
        'title' => 'Invoice Overview',
        'icon'  => 'ph-receipt',
        'what'  => 'View the complete details of an invoice including line items, taxes, payment history, and outstanding balance.',
        'steps' => [
            'Review the invoice details, line items, and totals.',
            'Check the payment history to see what has been received.',
            'Use the action buttons to edit, send, download PDF, or record a payment.',
            'View the activity timeline for all changes made to this invoice.',
        ],
        'tips' => [
            'Record payments against this invoice from the "Record Payment" button.',
            'Download the PDF to share with customers or for your records.',
        ],
    ],

    // ── Quotation Module — Edit / Overview ────────────────────────

    'quotations.php' => [
        'title' => 'Quotation Details',
        'icon'  => 'ph-file-text',
        'what'  => 'Create a new quotation or edit an existing one. Build professional price quotes for customers with line items, pricing, and terms.',
        'steps' => [
            'Select the customer and set the quotation date and validity period.',
            'Add line items with descriptions, quantities, and rates.',
            'Set terms and conditions in the footer.',
            'Save and send the quotation via email to the customer.',
        ],
        'tips' => [
            'Set a reasonable validity period — typically 7 to 30 days.',
            'Convert accepted quotations directly to invoices or sales orders.',
        ],
    ],

    'quotation_overview.php' => [
        'title' => 'Quotation Overview',
        'icon'  => 'ph-file-text',
        'what'  => 'View the full details of a quotation including line items, customer information, status, and conversion options.',
        'steps' => [
            'Review the quotation details, pricing, and terms.',
            'Check the quotation status (Draft, Sent, Accepted, Declined, Expired).',
            'Use actions to edit, send, duplicate, or convert to invoice/sales order.',
        ],
        'tips' => [
            'Mark quotations as Accepted or Declined to keep your pipeline accurate.',
            'Duplicate a quotation to quickly create a similar one for another customer.',
        ],
    ],

    // ── Customer Module — Edit / Overview ─────────────────────────

    'customers.php' => [
        'title' => 'Customer Details',
        'icon'  => 'ph-user-circle',
        'what'  => 'Create a new customer or edit an existing one. Enter company details, contact information, billing/shipping addresses, and payment terms.',
        'steps' => [
            'Enter the customer name, email, phone, and website.',
            'Add billing and shipping addresses.',
            'Set the currency, payment terms, and tax treatment.',
            'Save the customer — they will be available in invoices, quotations, and sales orders.',
        ],
        'tips' => [
            'Use the Notes section to store internal information about the customer.',
            'Set a credit limit to track customer spending.',
        ],
    ],

    'customer_overview.php' => [
        'title' => 'Customer Overview',
        'icon'  => 'ph-user-circle',
        'what'  => 'A comprehensive view of a customer including their profile, outstanding balance, transaction history, contacts, and recent activity.',
        'steps' => [
            'Review the customer summary cards for receivables, credits, and recent activity.',
            'Use the tabs to view invoices, payments, contacts, and notes.',
            'Navigate to sub-pages for billing addresses, shipping addresses, and transaction history.',
        ],
        'tips' => [
            'Use the Statement view to generate a customer account statement for a date range.',
            'Check the Logs tab for a complete audit trail of changes.',
        ],
    ],

    'customer_statement.php' => [
        'title' => 'Customer Statement',
        'icon'  => 'ph-file-text',
        'what'  => 'Generate and view a detailed account statement for a customer showing all transactions, invoices, payments, and running balance over a period.',
        'steps' => [
            'Select the date range for the statement.',
            'Review the transaction list showing invoices, payments, and credits.',
            'Download or print the statement to share with the customer.',
        ],
        'tips' => [
            'Use this statement for account reconciliation with customers.',
            'The opening and closing balances help verify account accuracy.',
        ],
    ],

    'customer_transactions.php' => [
        'title' => 'Customer Transactions',
        'icon'  => 'ph-swap',
        'what'  => 'View all financial transactions for a customer including invoices, payments received, credit notes, and adjustments.',
        'steps' => [
            'Review the chronological list of all transactions.',
            'Use filters to narrow down by transaction type or date range.',
            'Click on any transaction to view its full details.',
        ],
        'tips' => [
            'Use this view for detailed customer account reconciliation.',
        ],
    ],

    'customer_contacts.php' => [
        'title' => 'Customer Contacts',
        'icon'  => 'ph-address-book',
        'what'  => 'Manage contact persons for a specific customer. Add multiple contacts with their roles, emails, and phone numbers.',
        'steps' => [
            'Click <strong>+ New</strong> to add a contact for this customer.',
            'Enter the contact name, email, phone, and designation.',
            'Mark one contact as the primary contact for communications.',
        ],
        'tips' => [
            'Primary contacts are auto-selected when creating invoices and quotations.',
        ],
    ],

    'customer_billing_addresses.php' => [
        'title' => 'Billing Addresses',
        'icon'  => 'ph-map-pin',
        'what'  => 'Manage billing addresses for a customer. Store multiple billing addresses for different offices or entities.',
        'steps' => [
            'Click <strong>+ New</strong> to add a billing address.',
            'Enter the full address including city, state, country, and postal code.',
            'Mark one address as the default billing address.',
        ],
        'tips' => [
            'Default billing address is auto-populated on new invoices.',
        ],
    ],

    'customer_shipping_addresses.php' => [
        'title' => 'Shipping Addresses',
        'icon'  => 'ph-truck',
        'what'  => 'Manage shipping/delivery addresses for a customer. Store multiple shipping locations for different delivery points.',
        'steps' => [
            'Click <strong>+ New</strong> to add a shipping address.',
            'Enter the full delivery address.',
            'Mark one address as the default shipping address.',
        ],
        'tips' => [
            'Default shipping address is auto-populated on sales orders and shipping documents.',
        ],
    ],

    'customer_comments.php' => [
        'title' => 'Customer Comments',
        'icon'  => 'ph-chat-circle-text',
        'what'  => 'Internal notes and comments about a customer. Team members can add comments for collaboration and record-keeping.',
        'steps' => [
            'Type your comment in the text box and click Add.',
            'All comments are timestamped and show the author.',
            'Use comments for internal communication about the customer.',
        ],
        'tips' => [
            'Comments are internal and not visible to the customer.',
        ],
    ],

    'customer_mails.php' => [
        'title' => 'Customer Emails',
        'icon'  => 'ph-envelope',
        'what'  => 'View all emails sent to this customer including invoices, quotations, reminders, and notifications.',
        'steps' => [
            'Review the email history for this customer.',
            'Click on an email to view its content and delivery status.',
            'Use this to verify whether the customer received important documents.',
        ],
        'tips' => [
            'Check delivery status to follow up on emails that bounced or failed.',
        ],
    ],

    'customer_logs.php' => [
        'title' => 'Customer Activity Log',
        'icon'  => 'ph-clock-counter-clockwise',
        'what'  => 'A chronological audit trail of all changes made to this customer record including edits, status changes, and linked transactions.',
        'steps' => [
            'Review the activity timeline for this customer.',
            'Each entry shows what changed, who changed it, and when.',
        ],
        'tips' => [
            'Use the log to investigate discrepancies or track changes over time.',
        ],
    ],

    // ── Expense Module — Edit / Overview ──────────────────────────

    'expenses.php' => [
        'title' => 'Expense Details',
        'icon'  => 'ph-wallet',
        'what'  => 'Create a new expense or edit an existing one. Record business expenses with vendor, amount, category, and supporting documents.',
        'steps' => [
            'Select the vendor (payee) and expense account.',
            'Enter the amount, date, and payment method.',
            'Attach receipts or supporting documents.',
            'Save the expense for approval or mark as paid.',
        ],
        'tips' => [
            'Attach receipt images for audit compliance.',
            'Use the correct expense account for accurate financial reporting.',
        ],
    ],

    'expense_overview.php' => [
        'title' => 'Expense Overview',
        'icon'  => 'ph-wallet',
        'what'  => 'View the complete details of an expense including amount, category, vendor, attached documents, and approval status.',
        'steps' => [
            'Review the expense details and attached documents.',
            'Check the approval status and any reviewer comments.',
            'Use actions to edit, approve, or delete the expense.',
        ],
        'tips' => [
            'Approve or reject expenses promptly to maintain accurate books.',
        ],
    ],

    // ── Lead / CRM Module — Edit / Overview ──────────────────────

    'leads.php' => [
        'title' => 'Lead Details',
        'icon'  => 'ph-target',
        'what'  => 'Create a new lead or edit an existing one. Enter prospect details, assign a status and owner, and track through the sales pipeline.',
        'steps' => [
            'Enter the lead company name, contact person, email, and phone.',
            'Select the lead source and assign an owner.',
            'Set the initial status (e.g., New, Contacted).',
            'Save the lead — it will appear in the leads pipeline.',
        ],
        'tips' => [
            'Add detailed notes to help with follow-up conversations.',
            'Attach relevant documents like RFPs or meeting notes.',
        ],
    ],

    'lead.php' => [
        'title' => 'Lead Details',
        'icon'  => 'ph-target',
        'what'  => 'View and manage a lead\'s complete profile including contact info, notes, attachments, quotations, activity log, and pipeline status.',
        'steps' => [
            'Review the lead summary and current pipeline status.',
            'Use tabs to view notes, attachments, quotations, and activity log.',
            'Update the status as the lead progresses through the pipeline.',
            'Convert to a customer when the lead is ready.',
        ],
        'tips' => [
            'Keep notes and attachments updated for team collaboration.',
            'Track all interactions in the activity log for a complete history.',
        ],
    ],

    'lead_notes.php' => [
        'title' => 'Lead Notes',
        'icon'  => 'ph-notepad',
        'what'  => 'Add and manage notes for a lead. Track conversation summaries, follow-up reminders, and important details about the prospect.',
        'steps' => [
            'Click <strong>+ New</strong> to add a note.',
            'Enter the note content and save.',
            'All notes are timestamped and show the author.',
        ],
        'tips' => [
            'Record key discussion points from calls and meetings.',
        ],
    ],

    'lead_attachments.php' => [
        'title' => 'Lead Attachments',
        'icon'  => 'ph-paperclip',
        'what'  => 'Upload and manage files attached to a lead such as proposals, RFPs, contracts, and meeting notes.',
        'steps' => [
            'Click <strong>Upload</strong> to attach a file.',
            'Select the file from your computer and add an optional description.',
            'Download or delete attachments as needed.',
        ],
        'tips' => [
            'Keep all lead-related documents in one place for easy team access.',
        ],
    ],

    'lead_logs.php' => [
        'title' => 'Lead Activity Log',
        'icon'  => 'ph-clock-counter-clockwise',
        'what'  => 'A chronological history of all changes and interactions for a lead including status changes, notes, emails, and quotations.',
        'steps' => [
            'Review the timeline for a complete history of this lead.',
            'Each entry shows the action, user, and timestamp.',
        ],
        'tips' => [
            'Use the log to prepare for follow-up conversations.',
        ],
    ],

    'lead_quotations.php' => [
        'title' => 'Lead Quotations',
        'icon'  => 'ph-file-plus',
        'what'  => 'View and create quotations linked to this lead. Track which quotes have been sent, accepted, or rejected.',
        'steps' => [
            'Click <strong>+ New</strong> to create a quotation for this lead.',
            'Add line items with pricing and terms.',
            'Send the quotation and track its status.',
        ],
        'tips' => [
            'Accepted quotations can be converted to invoices or sales orders.',
        ],
    ],

    'lead_quotation.php' => [
        'title' => 'Lead Quotation Details',
        'icon'  => 'ph-file-plus',
        'what'  => 'Create or edit a quotation linked to a lead. Build price quotes with line items, pricing, and terms for the prospect.',
        'steps' => [
            'Review or enter the customer and line item details.',
            'Set pricing, quantities, and applicable taxes.',
            'Save and send the quotation to the lead.',
        ],
        'tips' => [
            'Use the linked lead\'s contact information to auto-populate fields.',
        ],
    ],

    // ── Sales / Purchase Order — Edit / Overview ──────────────────

    'sale_orders.php' => [
        'title' => 'Sales Order Details',
        'icon'  => 'ph-shopping-cart',
        'what'  => 'Create a new sales order or edit an existing one. Document confirmed orders with customer details, line items, and delivery terms.',
        'steps' => [
            'Select the customer and add line items with quantities and rates.',
            'Set delivery dates and shipping terms.',
            'Save the order — you can later convert it to an invoice.',
        ],
        'tips' => [
            'Convert confirmed sales orders directly to invoices to save time.',
        ],
    ],

    'sale_order_overview.php' => [
        'title' => 'Sales Order Overview',
        'icon'  => 'ph-shopping-cart',
        'what'  => 'View the complete details of a sales order including line items, customer info, delivery status, and linked invoices.',
        'steps' => [
            'Review the sales order details and line items.',
            'Check the fulfillment status and linked invoices.',
            'Use actions to edit, convert to invoice, or print.',
        ],
        'tips' => [
            'Track which sales orders have been invoiced and which are still pending.',
        ],
    ],

    'purchase_orders.php' => [
        'title' => 'Purchase Order Details',
        'icon'  => 'ph-clipboard-text',
        'what'  => 'Create a new purchase order or edit an existing one. Generate POs for vendors with item details, quantities, and delivery expectations.',
        'steps' => [
            'Select the vendor and add items with quantities and rates.',
            'Set the expected delivery date and terms.',
            'Save and send the PO to the vendor.',
        ],
        'tips' => [
            'Track received quantities against the PO to monitor partial deliveries.',
        ],
    ],

    'purchase_order_overview.php' => [
        'title' => 'Purchase Order Overview',
        'icon'  => 'ph-clipboard-text',
        'what'  => 'View the complete details of a purchase order including line items, vendor info, delivery status, and linked bills.',
        'steps' => [
            'Review the PO details, items, and amounts.',
            'Check the received quantities and status.',
            'Use actions to edit, convert to bill, or print.',
        ],
        'tips' => [
            'Convert received purchase orders to bills for accurate payables tracking.',
        ],
    ],

    // ── Payments — Edit / Overview ────────────────────────────────

    'payments_received.php' => [
        'title' => 'Payment Received Details',
        'icon'  => 'ph-arrow-circle-down',
        'what'  => 'Record a new payment received from a customer or edit an existing one. Link the payment to outstanding invoices.',
        'steps' => [
            'Select the customer and the invoice(s) to apply the payment to.',
            'Enter the payment amount, date, method, and reference number.',
            'Save to automatically update the invoice balances.',
        ],
        'tips' => [
            'A single payment can be split across multiple invoices.',
        ],
    ],

    'payment_received_overview.php' => [
        'title' => 'Payment Received Overview',
        'icon'  => 'ph-arrow-circle-down',
        'what'  => 'View the details of a payment received including the amount, method, reference, and linked invoices.',
        'steps' => [
            'Review the payment details and linked invoices.',
            'Check which invoices were partially or fully paid.',
            'Use actions to edit or delete the payment.',
        ],
        'tips' => [
            'Deleting a payment will reverse the balance updates on linked invoices.',
        ],
    ],

    'payments_made.php' => [
        'title' => 'Payment Made Details',
        'icon'  => 'ph-arrow-circle-up',
        'what'  => 'Record a new payment made to a vendor or edit an existing one. Link the payment to outstanding bills.',
        'steps' => [
            'Select the vendor and the bill(s) to apply the payment to.',
            'Enter the payment amount, date, method, and reference.',
            'Save to automatically update the bill balances.',
        ],
        'tips' => [
            'Record payments promptly to keep your payables up to date.',
        ],
    ],

    'payments_made_overview.php' => [
        'title' => 'Payment Made Overview',
        'icon'  => 'ph-arrow-circle-up',
        'what'  => 'View the details of a payment made to a vendor including amount, method, reference, and linked bills.',
        'steps' => [
            'Review the payment details and linked bills.',
            'Check which bills were partially or fully paid.',
            'Use actions to edit or delete the payment.',
        ],
        'tips' => [
            'Verify payment details against bank statements regularly.',
        ],
    ],

    // ── Purchases / Bills — Edit / Overview ───────────────────────

    'purchases.php' => [
        'title' => 'Purchase / Bill Details',
        'icon'  => 'ph-shopping-bag',
        'what'  => 'Create a new purchase bill or edit an existing one. Record vendor bills with line items, taxes, and payment terms.',
        'steps' => [
            'Select the vendor and add line items with quantities and rates.',
            'Set the bill date, due date, and payment terms.',
            'Save the bill — it will update your accounts payable.',
        ],
        'tips' => [
            'Link purchases to expense accounts for accurate reporting.',
        ],
    ],

    'purchase_overview.php' => [
        'title' => 'Purchase / Bill Overview',
        'icon'  => 'ph-shopping-bag',
        'what'  => 'View the complete details of a purchase bill including line items, vendor info, payment history, and balance due.',
        'steps' => [
            'Review the bill details, line items, and totals.',
            'Check the payment history and outstanding balance.',
            'Use actions to edit, record payment, or download PDF.',
        ],
        'tips' => [
            'Record payments against bills from this page to keep payables accurate.',
        ],
    ],

    // ── Credit / Debit Notes — Edit / Overview ────────────────────

    'credit_notes.php' => [
        'title' => 'Credit Note Details',
        'icon'  => 'ph-arrow-u-up-left',
        'what'  => 'Create a new credit note or edit an existing one. Issue credits to customers for returns, overpayments, or billing corrections.',
        'steps' => [
            'Select the customer and optionally link to an original invoice.',
            'Add line items with the credit amounts and reasons.',
            'Save the credit note and apply it against outstanding invoices.',
        ],
        'tips' => [
            'Credit notes can be applied to any outstanding invoice for the same customer.',
        ],
    ],

    'credit_note_overview.php' => [
        'title' => 'Credit Note Overview',
        'icon'  => 'ph-arrow-u-up-left',
        'what'  => 'View the details of a credit note including line items, applied amounts, remaining balance, and linked invoices.',
        'steps' => [
            'Review the credit note details and applied amounts.',
            'Check which invoices the credit has been applied to.',
            'Use actions to edit, apply to invoice, or void.',
        ],
        'tips' => [
            'Voided credit notes remain visible for audit but cannot be re-applied.',
        ],
    ],

    'debit_notes.php' => [
        'title' => 'Debit Note Details',
        'icon'  => 'ph-arrow-u-up-right',
        'what'  => 'Create a new debit note or edit an existing one. Issue debit notes to vendors for billing corrections or returned goods.',
        'steps' => [
            'Select the vendor and optionally link to a purchase or bill.',
            'Enter the debit amount, reason, and applicable items.',
            'Save the debit note and apply it against outstanding payables.',
        ],
        'tips' => [
            'Use debit notes to correct vendor overbilling without modifying the original bill.',
        ],
    ],

    'debit_note_overview.php' => [
        'title' => 'Debit Note Overview',
        'icon'  => 'ph-arrow-u-up-right',
        'what'  => 'View the details of a debit note including amounts, status, and linked vendor bills.',
        'steps' => [
            'Review the debit note details and applied amounts.',
            'Check the status and linked payables.',
            'Use actions to edit, apply, or void.',
        ],
        'tips' => [
            'Track which vendor bills have been adjusted by this debit note.',
        ],
    ],

    // ── Journal Entries — Edit / Overview ─────────────────────────

    'journals.php' => [
        'title' => 'Journal Entry Details',
        'icon'  => 'ph-notebook',
        'what'  => 'Create a new journal entry or edit an existing one. Record manual accounting adjustments with debit and credit lines.',
        'steps' => [
            'Enter the journal date and reference number.',
            'Add debit and credit lines with the correct accounts and amounts.',
            'Ensure total debits equal total credits.',
            'Save and post the journal entry.',
        ],
        'tips' => [
            'Always verify that debits and credits balance before saving.',
            'Add a clear narration for audit trail purposes.',
        ],
    ],

    // ── Projects — Edit / Overview ────────────────────────────────

    'projects.php' => [
        'title' => 'Project Details',
        'icon'  => 'ph-briefcase',
        'what'  => 'Create a new project or edit an existing one. Set up project details, link to a customer, and define timelines.',
        'steps' => [
            'Enter the project name and link it to a customer.',
            'Set the start date, end date, and project budget.',
            'Assign team members and set the project status.',
            'Save the project.',
        ],
        'tips' => [
            'Use meaningful project names for easy identification.',
        ],
    ],

    'view_project.php' => [
        'title' => 'Project Overview',
        'icon'  => 'ph-briefcase',
        'what'  => 'View the complete details of a project including tasks, team members, timeline, budget, and progress.',
        'steps' => [
            'Review the project summary and progress indicators.',
            'Use tabs to view tasks, time entries, expenses, and invoices.',
            'Update project status and add tasks as needed.',
        ],
        'tips' => [
            'Track time and expenses against the project for accurate profitability analysis.',
        ],
    ],

    // ── Jobs — Edit / Overview ────────────────────────────────────

    'jobs.php' => [
        'title' => 'Job Details',
        'icon'  => 'ph-suitcase-simple',
        'what'  => 'Create a new job or edit an existing one. Set up job details, assign team members, and define deadlines.',
        'steps' => [
            'Enter the job title and description.',
            'Assign the job to a team member and set a deadline.',
            'Set the initial status and priority.',
            'Save the job.',
        ],
        'tips' => [
            'Use clear, descriptive job titles for easy identification.',
        ],
    ],

    'view_job.php' => [
        'title' => 'Job Overview',
        'icon'  => 'ph-suitcase-simple',
        'what'  => 'View the complete details of a job including status, assignee, deadline, linked documents, and activity history.',
        'steps' => [
            'Review the job details and current status.',
            'Use tabs to view comments, attachments, and activity log.',
            'Update the status as work progresses.',
        ],
        'tips' => [
            'Keep the job status updated so managers can track progress.',
        ],
    ],

    // ── Recurring Invoices ────────────────────────────────────────

    'recurring_invoices.php' => [
        'title' => 'Recurring Invoice Details',
        'icon'  => 'ph-repeat',
        'what'  => 'Create or edit a recurring invoice profile. Set up automatic invoice generation on a schedule (weekly, monthly, yearly).',
        'steps' => [
            'Select the customer and add line items.',
            'Set the repeat frequency (weekly, monthly, quarterly, yearly).',
            'Define the start date and optional end date.',
            'Save — invoices will be generated automatically on the schedule.',
        ],
        'tips' => [
            'Recurring invoices save time for regular billing cycles.',
        ],
    ],

    'recurring_invoice_overview.php' => [
        'title' => 'Recurring Invoice Overview',
        'icon'  => 'ph-repeat',
        'what'  => 'View the recurring invoice profile details including schedule, generated invoices, and next run date.',
        'steps' => [
            'Review the recurring schedule and past generated invoices.',
            'Check the next invoice generation date.',
            'Use actions to edit, pause, or delete the recurring profile.',
        ],
        'tips' => [
            'Pause a recurring invoice to temporarily stop generation without deleting.',
        ],
    ],

    // ── Vendor Pages ──────────────────────────────────────────────

    'vendor_overview.php' => [
        'title' => 'Vendor Overview',
        'icon'  => 'ph-storefront',
        'what'  => 'A comprehensive view of a vendor including their profile, outstanding payables, purchase history, and recent activity.',
        'steps' => [
            'Review the vendor summary cards for payables and recent transactions.',
            'Use the tabs to view bills, payments, and purchase orders.',
            'Navigate to edit the vendor profile or add a new bill.',
        ],
        'tips' => [
            'Keep vendor contact information updated for smooth procurement.',
        ],
    ],

    'vendor_credit_overview.php' => [
        'title' => 'Vendor Credit Overview',
        'icon'  => 'ph-arrow-u-up-left',
        'what'  => 'View the details of a vendor credit note including amounts, status, and linked bills.',
        'steps' => [
            'Review the credit details and applied amounts.',
            'Check which bills have been adjusted.',
            'Use actions to apply to a bill or void.',
        ],
        'tips' => [
            'Apply vendor credits to outstanding bills to reduce payables.',
        ],
    ],

    // ── Shipping Module — Edit / View ─────────────────────────────

    'shipping_advices.php' => [
        'title' => 'Shipping Advice Details',
        'icon'  => 'ph-note-pencil',
        'what'  => 'Create a new shipping advice or edit an existing one. Document cargo details, ports, vessel information, and shipping dates.',
        'steps' => [
            'Enter the shipping advice details: origin, destination, vessel, and dates.',
            'Add cargo items with quantities, weights, and container assignments.',
            'Link to the relevant customer and invoice.',
            'Save the shipping advice.',
        ],
        'tips' => [
            'Ensure all cargo details match the commercial invoice and packing list.',
        ],
    ],

    'view_shipping_advice.php' => [
        'title' => 'Shipping Advice View',
        'icon'  => 'ph-note-pencil',
        'what'  => 'View the complete details of a shipping advice including cargo items, ports, vessel info, documents, and status.',
        'steps' => [
            'Review the shipping advice details and cargo items.',
            'Check linked documents, invoices, and customs information.',
            'Use actions to edit, print, or download the shipping advice.',
        ],
        'tips' => [
            'Cross-reference with the commercial invoice to ensure accuracy.',
        ],
    ],

    'shipping_invoices.php' => [
        'title' => 'Shipping Invoice Details',
        'icon'  => 'ph-files',
        'what'  => 'Create a new shipping invoice or edit an existing one. Bill for freight, handling, and other shipping-related charges.',
        'steps' => [
            'Select the customer and link to a shipping advice.',
            'Add line items for freight charges, handling fees, and taxes.',
            'Save the shipping invoice.',
        ],
        'tips' => [
            'Link shipping invoices to shipping advices for complete documentation.',
        ],
    ],

    'shipping_stocks.php' => [
        'title' => 'Shipping Stocks View',
        'icon'  => 'ph-stack',
        'what'  => 'View and manage cargo stock levels across warehouses. Track inventory positions and movement history.',
        'steps' => [
            'Review current stock levels by item and warehouse.',
            'Click on an item to view detailed movement history.',
            'Use filters to narrow down by warehouse or item type.',
        ],
        'tips' => [
            'Regularly reconcile stock records with physical counts.',
        ],
    ],

    'view_shipping_stocks.php' => [
        'title' => 'Shipping Stock Details',
        'icon'  => 'ph-stack',
        'what'  => 'View the detailed stock movement history for a specific item including receipts, dispatches, and current balance.',
        'steps' => [
            'Review the item details and current stock level.',
            'Check the movement history showing all ins and outs.',
            'Use filters to narrow down by date range or transaction type.',
        ],
        'tips' => [
            'Use this view for stock reconciliation and audit purposes.',
        ],
    ],

    'import_shipping_advices.php' => [
        'title' => 'Import Shipping Advices',
        'icon'  => 'ph-upload',
        'what'  => 'Bulk import shipping advices from a spreadsheet file. Upload a CSV or Excel file with shipping advice data.',
        'steps' => [
            'Download the import template to see the required format.',
            'Fill in the shipping advice data in the template.',
            'Upload the file and review the import preview.',
            'Confirm the import to create the shipping advices.',
        ],
        'tips' => [
            'Always use the provided template to ensure correct column mapping.',
            'Review the preview carefully before confirming the import.',
        ],
    ],

    // ── Email Module Pages ────────────────────────────────────────

    'email_history.php' => [
        'title' => 'Email History',
        'icon'  => 'ph-archive',
        'what'  => 'View a log of all emails sent from the system, including delivery status, timestamps, and full email content.',
        'steps' => [
            'Search by recipient, subject, or date to find specific emails.',
            'Click on an email to view its full content and delivery details.',
            'Use filters to narrow down by status (sent, failed, queued).',
        ],
        'tips' => [
            'Use this page to verify whether a customer received their invoice or notification.',
        ],
    ],

    'email_test.php' => [
        'title' => 'Email Test',
        'icon'  => 'ph-paper-plane-tilt',
        'what'  => 'Send a test email to verify your SMTP email provider configuration is working correctly.',
        'steps' => [
            'Enter the recipient email address for the test.',
            'Click Send Test Email.',
            'Check your inbox (and spam folder) for the test email.',
        ],
        'tips' => [
            'Always test email configuration after setting up a new SMTP provider.',
            'If the test fails, verify your SMTP host, port, username, and password.',
        ],
    ],

    // ── Settings / Configuration Pages ────────────────────────────

    'system_settings.php' => [
        'title' => 'System Settings',
        'icon'  => 'ph-gear-six',
        'what'  => 'Manage advanced system settings including feature toggles, performance options, and technical parameters.',
        'steps' => [
            'Review the available system settings.',
            'Modify values as needed and save.',
            'Test the system after making changes to ensure everything works.',
        ],
        'tips' => [
            'Changes to system settings affect all users — proceed with caution.',
        ],
    ],

    'ui_design_settings.php' => [
        'title' => 'UI Design Settings',
        'icon'  => 'ph-palette',
        'what'  => 'Customize the look and feel of the dashboard including colors, fonts, sidebar style, and layout preferences.',
        'steps' => [
            'Choose your preferred color scheme and sidebar style.',
            'Adjust font size and layout preferences.',
            'Save your changes — the new look will apply immediately.',
        ],
        'tips' => [
            'These settings are personal — each user can have their own preferences.',
        ],
    ],

    'change_password.php' => [
        'title' => 'Change Password',
        'icon'  => 'ph-key',
        'what'  => 'Update your account password. Enter your current password and a new password to secure your account.',
        'steps' => [
            'Enter your current password for verification.',
            'Enter and confirm your new password.',
            'Click Save to update your password.',
        ],
        'tips' => [
            'Use a strong password with at least 8 characters, including uppercase, lowercase, numbers, and symbols.',
        ],
    ],

    'profile.php' => [
        'title' => 'My Profile',
        'icon'  => 'ph-user',
        'what'  => 'View and edit your personal profile including name, email, phone, profile picture, and preferences.',
        'steps' => [
            'Update your name, email, and phone number.',
            'Upload a profile picture.',
            'Save your changes.',
        ],
        'tips' => [
            'Keep your profile information up to date for team communication.',
        ],
    ],

    'features.php' => [
        'title' => 'Features',
        'icon'  => 'ph-star',
        'what'  => 'View and manage enabled features for your organization. Toggle modules and features on or off.',
        'steps' => [
            'Review the list of available features.',
            'Toggle features on or off based on your organization\'s needs.',
            'Save your changes.',
        ],
        'tips' => [
            'Disabling a feature hides it from the navigation but does not delete data.',
        ],
    ],

    // ── Organization Pages ────────────────────────────────────────

    'organizations.php' => [
        'title' => 'Organization Details',
        'icon'  => 'ph-buildings',
        'what'  => 'Create a new organization or edit an existing one. Configure the organization name, address, settings, and membership.',
        'steps' => [
            'Enter the organization name, address, and contact details.',
            'Configure organization-specific settings.',
            'Save the organization.',
        ],
        'tips' => [
            'Each organization has its own isolated data and settings.',
        ],
    ],

    'organization_invites.php' => [
        'title' => 'Organization Invitations',
        'icon'  => 'ph-envelope-simple',
        'what'  => 'Manage pending invitations to join an organization. Send, resend, or revoke invitations to team members.',
        'steps' => [
            'Review pending invitations and their status.',
            'Send new invitations by entering the email address and role.',
            'Revoke invitations that are no longer needed.',
        ],
        'tips' => [
            'Invitations expire after a set period — resend if needed.',
        ],
    ],

    'organization_roles.php' => [
        'title' => 'Organization Roles',
        'icon'  => 'ph-users-three',
        'what'  => 'Manage roles within an organization. Define what each role can access and modify within the organization.',
        'steps' => [
            'Click <strong>+ New</strong> to create a new organization role.',
            'Configure the permissions for this role.',
            'Assign the role to organization members.',
        ],
        'tips' => [
            'The Owner role has full access and cannot be modified.',
        ],
    ],

    'organization_accept_invite.php' => [
        'title' => 'Accept Invitation',
        'icon'  => 'ph-check-circle',
        'what'  => 'Accept an invitation to join an organization. Review the invitation details and confirm your membership.',
        'steps' => [
            'Review the organization name and your assigned role.',
            'Click Accept to join the organization.',
            'You will be redirected to the organization dashboard.',
        ],
        'tips' => [
            'If you don\'t recognize the invitation, contact the organization administrator.',
        ],
    ],

    'select_organization.php' => [
        'title' => 'Select Organization',
        'icon'  => 'ph-buildings',
        'what'  => 'Choose which organization to work in. Switch between organizations you have access to.',
        'steps' => [
            'Click on the organization you want to switch to.',
            'You will be taken to that organization\'s dashboard.',
        ],
        'tips' => [
            'Each organization has its own separate data and settings.',
        ],
    ],

    // ── Reports Pages ─────────────────────────────────────────────

    'reports.php' => [
        'title' => 'Reports',
        'icon'  => 'ph-chart-bar',
        'what'  => 'Access all financial and business reports organized by category — accounting, sales, purchases, receivables, payables, and HR.',
        'steps' => [
            'Browse report categories to find the report you need.',
            'Click on a report to generate and view it.',
            'Use date filters and parameters to customize the report.',
            'Export reports to PDF or CSV for sharing.',
        ],
        'tips' => [
            'Bookmark frequently used reports for quick access.',
        ],
    ],

    'report_balance_sheet.php' => [
        'title' => 'Balance Sheet',
        'icon'  => 'ph-scales',
        'what'  => 'View the Balance Sheet report showing your organization\'s assets, liabilities, and equity as of a specific date.',
        'steps' => [
            'Select the report date (as-of date).',
            'Review the assets, liabilities, and equity sections.',
            'Export to PDF or CSV for external use.',
        ],
        'tips' => [
            'The balance sheet must balance: Assets = Liabilities + Equity.',
        ],
    ],

    'report_profit_and_loss.php' => [
        'title' => 'Profit & Loss Statement',
        'icon'  => 'ph-chart-line-up',
        'what'  => 'View the Profit & Loss (Income Statement) report showing revenue, expenses, and net profit/loss for a period.',
        'steps' => [
            'Select the date range for the report.',
            'Review income, expenses, and the resulting profit or loss.',
            'Export to PDF or CSV.',
        ],
        'tips' => [
            'Compare month-over-month or year-over-year for trend analysis.',
        ],
    ],

    'report_trial_balance.php' => [
        'title' => 'Trial Balance',
        'icon'  => 'ph-list-checks',
        'what'  => 'View the Trial Balance report showing the debit and credit balances of all accounts as of a specific date.',
        'steps' => [
            'Select the report date.',
            'Review all account balances with debit and credit columns.',
            'Verify that total debits equal total credits.',
        ],
        'tips' => [
            'An unbalanced trial balance indicates a journal entry error that needs correction.',
        ],
    ],

    'report_general_ledger.php' => [
        'title' => 'General Ledger',
        'icon'  => 'ph-book-open',
        'what'  => 'View the General Ledger report showing all transactions for all accounts within a date range.',
        'steps' => [
            'Select the date range and optionally filter by account.',
            'Review all transactions with their debit and credit entries.',
            'Export to PDF or CSV.',
        ],
        'tips' => [
            'Use the detailed general ledger for transaction-level analysis.',
        ],
    ],

    'report_detailed_general_ledger.php' => [
        'title' => 'Detailed General Ledger',
        'icon'  => 'ph-book-open',
        'what'  => 'View a detailed version of the General Ledger with full transaction descriptions, references, and running balances.',
        'steps' => [
            'Select the date range and filter criteria.',
            'Review detailed transaction entries with running balances.',
            'Export for audit or reconciliation purposes.',
        ],
        'tips' => [
            'Use this report for detailed account reconciliation.',
        ],
    ],

    'report_cash_flow_statement.php' => [
        'title' => 'Cash Flow Statement',
        'icon'  => 'ph-currency-circle-dollar',
        'what'  => 'View the Cash Flow Statement showing cash inflows and outflows from operating, investing, and financing activities.',
        'steps' => [
            'Select the date range for the report.',
            'Review cash flows from operating, investing, and financing activities.',
            'Analyze the net change in cash position.',
        ],
        'tips' => [
            'A positive cash flow from operations indicates healthy business performance.',
        ],
    ],

    'report_movement_of_equity.php' => [
        'title' => 'Movement of Equity',
        'icon'  => 'ph-chart-bar',
        'what'  => 'View the Movement of Equity report showing changes in owner\'s equity over a period.',
        'steps' => [
            'Select the date range.',
            'Review changes in equity components.',
            'Export for financial reporting.',
        ],
        'tips' => [
            'This report is typically included in annual financial statements.',
        ],
    ],

    'report_account_transactions.php' => [
        'title' => 'Account Transactions',
        'icon'  => 'ph-swap',
        'what'  => 'View all transactions for a specific account within a date range. Filter by account code or name.',
        'steps' => [
            'Select the account and date range.',
            'Review all transactions for the selected account.',
            'Export for detailed account analysis.',
        ],
        'tips' => [
            'Use this report to investigate specific account activity.',
        ],
    ],

    'report_account_type_summary.php' => [
        'title' => 'Account Type Summary',
        'icon'  => 'ph-chart-pie-slice',
        'what'  => 'View a summary of balances grouped by account type (Assets, Liabilities, Equity, Income, Expenses).',
        'steps' => [
            'Select the report date.',
            'Review the summary by account type.',
            'Use this for a quick financial overview.',
        ],
        'tips' => [
            'Use this report as a quick snapshot of your financial position.',
        ],
    ],

    'report_invoices.php' => [
        'title' => 'Invoice Report',
        'icon'  => 'ph-receipt',
        'what'  => 'View a report of all invoices within a date range with filtering by status, customer, and amount.',
        'steps' => [
            'Select the date range and filters.',
            'Review the invoice list with totals.',
            'Export to PDF or CSV.',
        ],
        'tips' => [
            'Use status filters to focus on unpaid or overdue invoices.',
        ],
    ],

    'report_invoice_details.php' => [
        'title' => 'Invoice Details Report',
        'icon'  => 'ph-receipt',
        'what'  => 'View detailed invoice information including line items, taxes, payments received, and outstanding balances.',
        'steps' => [
            'Select filters for date range, customer, and status.',
            'Review detailed invoice information.',
            'Export for analysis or sharing.',
        ],
        'tips' => [
            'Use this report for detailed revenue analysis by customer or item.',
        ],
    ],

    'report_sales_summary.php' => [
        'title' => 'Sales Summary',
        'icon'  => 'ph-chart-line-up',
        'what'  => 'View a summary of sales by period with totals, trends, and comparisons.',
        'steps' => [
            'Select the date range and grouping (monthly, quarterly, yearly).',
            'Review sales trends and totals.',
            'Export for management review.',
        ],
        'tips' => [
            'Compare periods to identify growth trends or seasonal patterns.',
        ],
    ],

    'report_sales_by_customer.php' => [
        'title' => 'Sales by Customer',
        'icon'  => 'ph-user-list',
        'what'  => 'View a breakdown of sales by customer showing who your top revenue contributors are.',
        'steps' => [
            'Select the date range.',
            'Review the customer-wise sales breakdown.',
            'Identify top customers by revenue.',
        ],
        'tips' => [
            'Use this report to identify your most valuable customers for relationship management.',
        ],
    ],

    'report_sales_by_item.php' => [
        'title' => 'Sales by Item',
        'icon'  => 'ph-cube',
        'what'  => 'View a breakdown of sales by item/product showing which products generate the most revenue.',
        'steps' => [
            'Select the date range.',
            'Review the item-wise sales breakdown.',
            'Identify top-selling and underperforming items.',
        ],
        'tips' => [
            'Use this report to optimize your product mix and pricing strategy.',
        ],
    ],

    'report_sales_by_sales_person.php' => [
        'title' => 'Sales by Sales Person',
        'icon'  => 'ph-user-circle',
        'what'  => 'View a breakdown of sales by sales person showing individual performance and revenue contributions.',
        'steps' => [
            'Select the date range.',
            'Review each sales person\'s contribution.',
            'Use for performance evaluation and commission calculations.',
        ],
        'tips' => [
            'Use this report for sales team performance reviews.',
        ],
    ],

    'report_expense_details.php' => [
        'title' => 'Expense Details Report',
        'icon'  => 'ph-wallet',
        'what'  => 'View detailed expense information including vendor, category, amount, and approval status.',
        'steps' => [
            'Select filters for date range, vendor, and category.',
            'Review detailed expense records.',
            'Export for expense analysis.',
        ],
        'tips' => [
            'Use category filters to analyze spending patterns.',
        ],
    ],

    'report_expenses_by_category.php' => [
        'title' => 'Expenses by Category',
        'icon'  => 'ph-tag',
        'what'  => 'View a breakdown of expenses by category showing where your money is being spent.',
        'steps' => [
            'Select the date range.',
            'Review the category-wise expense breakdown.',
            'Identify categories with the highest spending.',
        ],
        'tips' => [
            'Use this report to identify areas for cost reduction.',
        ],
    ],

    'report_expenses_by_customer.php' => [
        'title' => 'Expenses by Customer',
        'icon'  => 'ph-user-circle',
        'what'  => 'View a breakdown of billable expenses by customer for cost recovery analysis.',
        'steps' => [
            'Select the date range.',
            'Review expenses allocated to each customer.',
            'Use for billing and cost recovery.',
        ],
        'tips' => [
            'Billable expenses should be invoiced to customers for cost recovery.',
        ],
    ],

    'report_billable_expense_details.php' => [
        'title' => 'Billable Expense Details',
        'icon'  => 'ph-wallet',
        'what'  => 'View details of billable expenses that can be charged to customers.',
        'steps' => [
            'Review all billable expenses that have not yet been invoiced.',
            'Select expenses to include in customer invoices.',
        ],
        'tips' => [
            'Regularly invoice billable expenses to maintain cash flow.',
        ],
    ],

    'report_payments_received.php' => [
        'title' => 'Payments Received Report',
        'icon'  => 'ph-arrow-circle-down',
        'what'  => 'View a report of all payments received within a date range with filtering by customer and payment method.',
        'steps' => [
            'Select the date range and filters.',
            'Review the payment list with totals.',
            'Export for reconciliation.',
        ],
        'tips' => [
            'Use this report for bank reconciliation purposes.',
        ],
    ],

    'report_ar_summary.php' => [
        'title' => 'Receivables Summary',
        'icon'  => 'ph-chart-bar',
        'what'  => 'View a summary of accounts receivable showing total outstanding amounts by customer.',
        'steps' => [
            'Review the outstanding receivables by customer.',
            'Identify customers with overdue balances.',
            'Use for collections follow-up.',
        ],
        'tips' => [
            'Focus collection efforts on the largest overdue amounts first.',
        ],
    ],

    'report_ar_aging_summary.php' => [
        'title' => 'Receivables Aging Summary',
        'icon'  => 'ph-clock',
        'what'  => 'View an aging summary of receivables showing how long invoices have been outstanding (current, 30, 60, 90+ days).',
        'steps' => [
            'Review the aging buckets for overdue receivables.',
            'Identify invoices in the 90+ days bucket for immediate action.',
        ],
        'tips' => [
            'Invoices over 90 days old have a lower probability of collection — act quickly.',
        ],
    ],

    'report_ar_aging_details.php' => [
        'title' => 'Receivables Aging Details',
        'icon'  => 'ph-clock',
        'what'  => 'View detailed aging of each outstanding invoice showing customer, amount, due date, and days overdue.',
        'steps' => [
            'Review each outstanding invoice with its aging details.',
            'Use for detailed collections follow-up.',
        ],
        'tips' => [
            'Export this report to share with your collections team.',
        ],
    ],

    'report_receivable_summary.php' => [
        'title' => 'Receivable Summary',
        'icon'  => 'ph-chart-bar',
        'what'  => 'View a consolidated summary of all receivables with totals and customer breakdowns.',
        'steps' => [
            'Review the total receivables and customer-wise breakdown.',
            'Use for management reporting.',
        ],
        'tips' => [
            'Compare with previous periods to track receivables trends.',
        ],
    ],

    'report_receivable_details.php' => [
        'title' => 'Receivable Details',
        'icon'  => 'ph-chart-bar',
        'what'  => 'View detailed receivable information for each customer including invoice numbers, amounts, and due dates.',
        'steps' => [
            'Review detailed receivable records by customer.',
            'Export for collections follow-up.',
        ],
        'tips' => [
            'Use this report for detailed customer account reconciliation.',
        ],
    ],

    'report_payable_summary.php' => [
        'title' => 'Payables Summary',
        'icon'  => 'ph-chart-bar',
        'what'  => 'View a summary of accounts payable showing total outstanding amounts by vendor.',
        'steps' => [
            'Review the outstanding payables by vendor.',
            'Identify vendors with overdue payments.',
            'Use for payment planning.',
        ],
        'tips' => [
            'Prioritize payments based on due dates and vendor relationships.',
        ],
    ],

    'report_payable_details.php' => [
        'title' => 'Payable Details',
        'icon'  => 'ph-chart-bar',
        'what'  => 'View detailed payable information for each vendor including bill numbers, amounts, and due dates.',
        'steps' => [
            'Review detailed payable records by vendor.',
            'Export for payment planning.',
        ],
        'tips' => [
            'Use this report for cash flow planning and payment scheduling.',
        ],
    ],

    'report_credit_note_details.php' => [
        'title' => 'Credit Note Details Report',
        'icon'  => 'ph-arrow-u-up-left',
        'what'  => 'View detailed credit note information including customer, amounts, reasons, and linked invoices.',
        'steps' => [
            'Select filters for date range and customer.',
            'Review credit note details and applied amounts.',
        ],
        'tips' => [
            'Monitor credit note trends to identify recurring billing issues.',
        ],
    ],

    'report_journal_report.php' => [
        'title' => 'Journal Report',
        'icon'  => 'ph-notebook',
        'what'  => 'View all journal entries within a date range with debit and credit details.',
        'steps' => [
            'Select the date range.',
            'Review journal entries with their debit and credit lines.',
            'Export for audit purposes.',
        ],
        'tips' => [
            'Use this report for audit preparation and account reconciliation.',
        ],
    ],

    'report_quote_details.php' => [
        'title' => 'Quotation Details Report',
        'icon'  => 'ph-file-text',
        'what'  => 'View detailed quotation information including customer, line items, status, and conversion rates.',
        'steps' => [
            'Select filters for date range, status, and customer.',
            'Review quotation details and conversion metrics.',
        ],
        'tips' => [
            'Monitor quotation-to-invoice conversion rates to measure sales effectiveness.',
        ],
    ],

    'report_clients.php' => [
        'title' => 'Client Report',
        'icon'  => 'ph-user-list',
        'what'  => 'View a report of all customers/clients with their account balances, contact details, and transaction summaries.',
        'steps' => [
            'Review the client list with balances and activity.',
            'Export for customer analysis.',
        ],
        'tips' => [
            'Use this report for customer segmentation and relationship management.',
        ],
    ],

    'report_leads.php' => [
        'title' => 'Leads Report',
        'icon'  => 'ph-target',
        'what'  => 'View a report of leads by status, source, owner, and date with conversion metrics.',
        'steps' => [
            'Select filters for status, source, and date range.',
            'Review lead counts and conversion rates.',
            'Export for sales team analysis.',
        ],
        'tips' => [
            'Track conversion rates by source to optimize marketing spend.',
        ],
    ],

    'report_hr.php' => [
        'title' => 'HR Reports',
        'icon'  => 'ph-users-three',
        'what'  => 'Access HR-related reports including attendance, leave, payroll, and employee statistics.',
        'steps' => [
            'Browse available HR report categories.',
            'Select a report and configure parameters.',
            'View and export the report.',
        ],
        'tips' => [
            'Use HR reports for workforce planning and compliance.',
        ],
    ],

    'report_recurring_invoice_details.php' => [
        'title' => 'Recurring Invoice Details Report',
        'icon'  => 'ph-repeat',
        'what'  => 'View details of recurring invoice profiles including schedules, generated invoices, and revenue projections.',
        'steps' => [
            'Review recurring invoice profiles and their status.',
            'Check generated invoices and upcoming schedules.',
        ],
        'tips' => [
            'Monitor recurring revenue for cash flow forecasting.',
        ],
    ],

    'report_refund_history.php' => [
        'title' => 'Refund History',
        'icon'  => 'ph-arrow-counter-clockwise',
        'what'  => 'View a history of all refunds processed including amounts, reasons, and linked transactions.',
        'steps' => [
            'Review the refund list with details.',
            'Filter by date range and customer.',
            'Export for accounting reconciliation.',
        ],
        'tips' => [
            'Monitor refund trends to identify potential issues.',
        ],
    ],

    'report_reconciliation_status.php' => [
        'title' => 'Reconciliation Status',
        'icon'  => 'ph-check-circle',
        'what'  => 'View the bank reconciliation status showing matched and unmatched transactions.',
        'steps' => [
            'Review matched and unmatched transactions.',
            'Identify discrepancies between bank statements and system records.',
        ],
        'tips' => [
            'Regular reconciliation ensures accurate financial records.',
        ],
    ],

    'report_vendor_balance_summary.php' => [
        'title' => 'Vendor Balance Summary',
        'icon'  => 'ph-storefront',
        'what'  => 'View a summary of outstanding balances by vendor for payables management.',
        'steps' => [
            'Review vendor balances sorted by amount.',
            'Identify vendors with the highest outstanding amounts.',
            'Use for payment prioritization.',
        ],
        'tips' => [
            'Regular review helps maintain good vendor relationships.',
        ],
    ],

    'report_time_to_get_paid.php' => [
        'title' => 'Time to Get Paid',
        'icon'  => 'ph-timer',
        'what'  => 'View a report showing the average number of days it takes to collect payment from customers.',
        'steps' => [
            'Select the date range.',
            'Review the average days to payment by customer.',
            'Identify customers with slow payment patterns.',
        ],
        'tips' => [
            'A shorter collection period improves cash flow.',
        ],
    ],

    'report_shipping_stocks.php' => [
        'title' => 'Shipping Stocks Report',
        'icon'  => 'ph-stack',
        'what'  => 'View a report of cargo stock levels across warehouses and shipping operations.',
        'steps' => [
            'Review stock levels by item and warehouse.',
            'Identify low-stock or overstocked items.',
            'Export for inventory management.',
        ],
        'tips' => [
            'Regular stock reports help prevent stockouts and overstocking.',
        ],
    ],

    'report_business_performance_ratios.php' => [
        'title' => 'Business Performance Ratios',
        'icon'  => 'ph-chart-pie-slice',
        'what'  => 'View key financial ratios including profitability, liquidity, and efficiency metrics for business performance analysis.',
        'steps' => [
            'Select the date range for the analysis.',
            'Review the calculated financial ratios.',
            'Compare with industry benchmarks or previous periods.',
        ],
        'tips' => [
            'Use these ratios for strategic planning and investor reporting.',
        ],
    ],

    'report_sales_by_customer.php' => [
        'title' => 'Sales by Customer',
        'icon'  => 'ph-user-list',
        'what'  => 'View a breakdown of sales by customer showing revenue contribution from each client.',
        'steps' => [
            'Select the date range.',
            'Review customer-wise sales breakdown.',
            'Identify top revenue-generating customers.',
        ],
        'tips' => [
            'Diversify your customer base to reduce dependency on top clients.',
        ],
    ],

    // ── Other Utility Pages ───────────────────────────────────────

    // 'pages.php' removed — pages module decommissioned

    'modules.php' => [
        'title' => 'Modules',
        'icon'  => 'ph-puzzle-piece',
        'what'  => 'Manage system modules that represent functional areas of the application. Configure module settings and permissions.',
        'steps' => [
            'Review the list of available modules.',
            'Configure module settings and access controls.',
        ],
        'tips' => [
            'Module permissions are configured from the Roles & Permissions page.',
        ],
    ],

    'user.php' => [
        'title' => 'User Details',
        'icon'  => 'ph-user',
        'what'  => 'Create a new user account or edit an existing one. Configure user details, role assignment, and access settings.',
        'steps' => [
            'Enter the user\'s name, email, and phone number.',
            'Assign a role to control access permissions.',
            'Save the user account.',
        ],
        'tips' => [
            'Assign the most restrictive role needed for each user.',
        ],
    ],

    'user_documents.php' => [
        'title' => 'User Documents',
        'icon'  => 'ph-folder-open',
        'what'  => 'Upload and manage documents for a specific user such as ID copies, contracts, and certificates.',
        'steps' => [
            'Click <strong>Upload</strong> to add a document.',
            'Select the document type and upload the file.',
            'Set an expiry date if the document needs renewal.',
        ],
        'tips' => [
            'Set expiry dates for documents that need periodic renewal (visas, licenses).',
        ],
    ],

    'send_email.php' => [
        'title' => 'Send Email',
        'icon'  => 'ph-envelope',
        'what'  => 'Compose and send an email directly from the system using a configured email provider.',
        'steps' => [
            'Enter the recipient email address.',
            'Enter the subject and email body.',
            'Click Send.',
        ],
        'tips' => [
            'Emails sent from here are logged in the Email History.',
        ],
    ],

    'sitemap.php' => [
        'title' => 'Sitemap',
        'icon'  => 'ph-map-trifold',
        'what'  => 'A hierarchical view of all system pages and modules for navigation reference.',
        'steps' => [
            'Browse the sitemap to find the page you need.',
            'Click on any page to navigate directly to it.',
        ],
        'tips' => [
            'Use this as a quick reference for system navigation.',
        ],
    ],

    'sitemaps.php' => [
        'title' => 'Sitemaps',
        'icon'  => 'ph-map-trifold',
        'what'  => 'Manage and view the system sitemap configuration for navigation and SEO purposes.',
        'steps' => [
            'Review the sitemap structure.',
            'Configure sitemap settings as needed.',
        ],
        'tips' => [
            'Keep the sitemap updated as new pages are added.',
        ],
    ],

    'setup.php' => [
        'title' => 'Setup',
        'icon'  => 'ph-gear-six',
        'what'  => 'The initial setup wizard for configuring your organization. Set up company details, currency, and basic configuration.',
        'steps' => [
            'Follow the setup steps to configure your organization.',
            'Enter company name, address, and contact details.',
            'Set the base currency and date format.',
            'Complete the setup to start using the system.',
        ],
        'tips' => [
            'Complete all setup steps for the best experience.',
        ],
    ],

    'view_backend_error_logs.php' => [
        'title' => 'Backend Error Logs',
        'icon'  => 'ph-warning',
        'what'  => 'View server-side error logs for debugging and troubleshooting system issues.',
        'steps' => [
            'Review the error log entries sorted by date.',
            'Click on an entry for detailed error information.',
            'Use this information to report or fix issues.',
        ],
        'tips' => [
            'Only system administrators should access this page.',
        ],
    ],

    'view_frontend_error_logs.php' => [
        'title' => 'Frontend Error Logs',
        'icon'  => 'ph-warning',
        'what'  => 'View client-side JavaScript error logs for debugging frontend issues.',
        'steps' => [
            'Review the error log entries.',
            'Identify patterns in frontend errors.',
        ],
        'tips' => [
            'Frontend errors can indicate browser compatibility issues.',
        ],
    ],

    'sidebar_hidden_items.php' => [
        'title' => 'Hidden Sidebar Items',
        'icon'  => 'ph-sidebar-simple',
        'what'  => 'Manage which sidebar menu items are hidden from your view. Restore hidden items or hide additional ones.',
        'steps' => [
            'Review the list of hidden sidebar items.',
            'Toggle items to show or hide them from the sidebar.',
        ],
        'tips' => [
            'Hiding items does not delete any data — it only affects your sidebar view.',
        ],
    ],

    'subscription_management.php' => [
        'title' => 'Subscription Management',
        'icon'  => 'ph-credit-card',
        'what'  => 'Manage your organization\'s subscription plan, billing, and usage limits.',
        'steps' => [
            'Review your current plan and usage.',
            'Upgrade or change your subscription plan.',
            'Update payment methods and billing information.',
        ],
        'tips' => [
            'Monitor your usage to avoid hitting plan limits.',
        ],
    ],
];
