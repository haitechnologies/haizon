<?php declare(strict_types=1);

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
            'Enable Two-Factor Authentication (2FA) for extra security.',
        ],
    ],
];
