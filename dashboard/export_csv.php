<?php

use App\Core\DB;
/**
 * CSV Export Handler
 * 
 * Centralized endpoint for CSV exports from dashboard listing pages
 * Handles permission checks and triggers appropriate export
 * 
 * Usage: ?export={module}&filters={json}
 */

require_once __DIR__ . '/admin_elements/admin_header_minimal.php';
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/CSVExporter.php';
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/DB.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

$userId = $_SESSION['user_id'];
$module = $_GET['export'] ?? '';
$filters = json_decode($_GET['filters'] ?? '{}', true) ?? [];

// Validate module
if (empty($module)) {
    die('Invalid export request');
}

// Get module ID and check permissions
$moduleId = getModuleIdBySlug($module, $mysqli);
if (!granted('view', $moduleId)) {
    die('Permission denied');
}

// Initialize CSV exporter
$exporter = new CSVExporter($mysqli);
$exporter->setMaxRows(50000);

// Route to appropriate export function
switch ($module) {
    case 'customers':
        exportCustomers($exporter, $mysqli, $filters);
        break;
    
    case 'customer_invoices':
        exportInvoices($exporter, $mysqli, $filters);
        break;
    
    case 'customer_payments':
        exportPayments($exporter, $mysqli, $filters);
        break;
    
    case 'email_history':
        exportEmailHistory($exporter, $mysqli, $filters);
        break;
    
    
    case 'inquiries':
        exportInquiries($exporter, $mysqli, $filters);
        break;
    
    case 'customer_contacts':
        exportCustomerContacts($exporter, $mysqli, $filters);
        break;
    

    
    
    case 'categories':
        exportCategories($exporter, $mysqli, $filters);
        break;
    
    
    case 'authentication_activity':
        exportAuthenticationActivity($exporter, $mysqli, $filters);
        break;
    
    
    case 'email_queue':
        exportEmailQueue($exporter, $mysqli, $filters);
        break;
    
    
    case 'geo_cities':
        exportGeoCities($exporter, $mysqli, $filters);
        break;
    
    case 'geo_states':
        exportGeoStates($exporter, $mysqli, $filters);
        break;
    
    case 'geo_countries':
        exportGeoCountries($exporter, $mysqli, $filters);
        break;
    
    default:
        die('Export not implemented for this module');
}



/**
 * Export Categories (Business Categories)
 */
function exportCategories($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            c.id,
            c.category_name,
            c.slug,
            c.description,
            c.icon,
            c.display_order,
            c.is_active,
            COUNT(DISTINCT sc.id) as subcategories_count,
            0 as companies_count,
            c.created_at
        FROM `" . DB::CATEGORIES . "` c
        LEFT JOIN `" . DB::SUBCATEGORIES . "` sc ON c.id = sc.category_id
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['search'])) {
        $search = $mysqli->real_escape_string($filters['search']);
        $query .= " AND (c.category_name LIKE '%{$search}%' OR c.description LIKE '%{$search}%')";
    }
    
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $query .= " AND c.is_active = " . (int)$filters['is_active'];
    }
    
    $query .= " GROUP BY c.id ORDER BY c.display_order ASC, c.id DESC LIMIT 50000";
    
    $columns = ['id', 'category_name', 'slug', 'description', 'icon', 'display_order', 'is_active', 'subcategories_count', 'companies_count', 'created_at'];
    $columnHeaders = [
        'id' => 'ID',
        'category_name' => 'Category Name',
        'slug' => 'Slug',
        'description' => 'Description',
        'icon' => 'Icon',
        'display_order' => 'Display Order',
        'is_active' => 'Active',
        'subcategories_count' => 'Subcategories',
        'companies_count' => 'Companies',
        'created_at' => 'Created Date'
    ];
    
    $exporter->exportFromQuery($query, 'categories', $columns, $columnHeaders);
}


/**
 * Export Authentication Activity
 */
function exportAuthenticationActivity($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            aa.id,
            aa.user_id,
            u.full_name AS user_name,
            u.email AS user_email,
            aa.activity_type,
            aa.ip_address,
            aa.user_agent,
            aa.success,
            aa.created_at
        FROM `" . DB::AUTHENTICATION_ACTIVITY . "` aa
        LEFT JOIN `" . DB::USERS . "` u ON aa.user_id = u.id
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['search'])) {
        $search = $mysqli->real_escape_string($filters['search']);
        $query .= " AND (u.full_name LIKE '%{$search}%' OR aa.ip_address LIKE '%{$search}%' OR aa.activity_type LIKE '%{$search}%')";
    }
    
    if (!empty($filters['activity_type'])) {
        $type = $mysqli->real_escape_string($filters['activity_type']);
        $query .= " AND aa.activity_type = '{$type}'";
    }
    
    $query .= " ORDER BY aa.id DESC LIMIT 50000";
    
    $columns = ['id', 'user_id', 'user_name', 'user_email', 'activity_type', 'ip_address', 'user_agent', 'success', 'created_at'];
    $columnHeaders = [
        'id' => 'ID',
        'user_id' => 'User ID',
        'user_name' => 'User Name',
        'user_email' => 'User Email',
        'activity_type' => 'Activity Type',
        'ip_address' => 'IP Address',
        'user_agent' => 'User Agent',
        'success' => 'Success',
        'created_at' => 'Date'
    ];
    
    $exporter->exportFromQuery($query, 'authentication_activity', $columns, $columnHeaders);
}


/**
 * Export Email Queue
 */
function exportEmailQueue($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            eq.id,
            '' AS campaign_name,
            eq.recipient_email,
            eq.recipient_name,
            eq.status,
            eq.attempts,
            eq.next_retry_at,
            ep.provider_name,
            eq.created_at,
            eq.updated_at
        FROM `" . DB::EMAIL_QUEUE . "` eq
        LEFT JOIN `" . DB::EMAIL_PROVIDERS . "` ep ON eq.provider_id = ep.id
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['search'])) {
        $search = $mysqli->real_escape_string($filters['search']);
        $query .= " AND (eq.recipient_email LIKE '%{$search}%' OR eq.recipient_name LIKE '%{$search}%')";
    }
    
    if (!empty($filters['status'])) {
        $status = $mysqli->real_escape_string($filters['status']);
        $query .= " AND eq.status = '{$status}'";
    }
    
    $query .= " ORDER BY eq.id DESC LIMIT 50000";
    
    $columns = ['id', 'campaign_name', 'recipient_email', 'recipient_name', 'status', 'attempts', 'next_retry_at', 'provider_name', 'created_at', 'updated_at'];
    $columnHeaders = [
        'id' => 'ID',
        'campaign_name' => 'Campaign',
        'recipient_email' => 'Recipient Email',
        'recipient_name' => 'Recipient Name',
        'status' => 'Status',
        'attempts' => 'Attempts',
        'next_retry_at' => 'Next Retry',
        'provider_name' => 'Provider',
        'created_at' => 'Created',
        'updated_at' => 'Updated'
    ];
    
    $exporter->exportFromQuery($query, 'email_queue', $columns, $columnHeaders);
}

/**
 * Export Customers
 */
function exportCustomers($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            id,
            full_name,
            email,
            phone,
            company,
            city,
            country,
            is_active,
            email_verified,
            created_at,
            last_login_at
        FROM `" . DB::CUSTOMERS . "`
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['search'])) {
        $search = $mysqli->real_escape_string($filters['search']);
        $query .= " AND (full_name LIKE '%{$search}%' OR email LIKE '%{$search}%')";
    }
    
    $query .= " ORDER BY id DESC LIMIT 50000";
    
    $columns = ['id', 'full_name', 'email', 'phone', 'company', 'city', 'country', 'is_active', 'email_verified', 'created_at', 'last_login_at'];
    $columnHeaders = [
        'id' => 'ID',
        'full_name' => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'company' => 'Company',
        'city' => 'City',
        'country' => 'Country',
        'is_active' => 'Active',
        'email_verified' => 'Email Verified',
        'created_at' => 'Created Date',
        'last_login_at' => 'Last Login'
    ];
    
    $exporter->exportFromQuery($query, 'customers', $columns, $columnHeaders);
}

/**
 * Export Invoices
 */
function exportInvoices($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            i.id,
            i.invoice_number,
            c.full_name AS customer_name,
            c.email AS customer_email,
            i.invoice_date,
            i.due_date,
            i.subtotal,
            i.tax_amount,
            i.total_amount,
            i.invoice_status,
            i.payment_status,
            i.created_at
        FROM `" . DB::CUSTOMER_INVOICES . "` i
        LEFT JOIN `" . DB::CUSTOMERS . "` c ON i.customer_id = c.id
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['invoice_status'])) {
        $status = $mysqli->real_escape_string($filters['invoice_status']);
        $query .= " AND i.invoice_status = '{$status}'";
    }
    
    $query .= " ORDER BY i.id DESC LIMIT 50000";
    
    $columns = ['id', 'invoice_number', 'customer_name', 'customer_email', 'invoice_date', 'due_date', 'subtotal', 'tax_amount', 'total_amount', 'invoice_status', 'payment_status', 'created_at'];
    
    $exporter->exportFromQuery($query, 'invoices', $columns);
}

/**
 * Export Payments
 */
function exportPayments($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            p.id,
            p.payment_number,
            c.full_name AS customer_name,
            p.payment_date,
            p.amount,
            p.payment_method,
            p.payment_status,
            p.transaction_id,
            p.created_at
        FROM `" . DB::CUSTOMER_PAYMENTS . "` p
        LEFT JOIN `" . DB::CUSTOMERS . "` c ON p.customer_id = c.id
        WHERE 1=1
        ORDER BY p.id DESC
        LIMIT 50000
    ";
    
    $columns = ['id', 'payment_number', 'customer_name', 'payment_date', 'amount', 'payment_method', 'payment_status', 'transaction_id', 'created_at'];
    
    $exporter->exportFromQuery($query, 'payments', $columns);
}

/**
 * Export Email History
 */
function exportEmailHistory($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            id,
            recipient_email,
            recipient_name,
            subject,
            email_type,
            sent_status,
            sent_at,
            opened_at,
            clicked_at,
            created_at
        FROM `" . DB::EMAIL_HISTORY . "`
        WHERE 1=1
        ORDER BY id DESC
        LIMIT 50000
    ";
    
    $columns = ['id', 'recipient_email', 'recipient_name', 'subject', 'email_type', 'sent_status', 'sent_at', 'opened_at', 'clicked_at', 'created_at'];
    
    $exporter->exportFromQuery($query, 'email_history', $columns);
}

/**
 * Export Inquiries (Contact Form Submissions)
 */
function exportInquiries($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            id,
            full_name,
            email,
            mobile,
            subject,
            message,
            status,
            ip_address,
            user_agent,
            is_active,
            created_at
        FROM `" . DB::INQUIRIES . "`
        WHERE is_active = 1
    ";
    
    // Apply filters
    if (!empty($filters['search'])) {
        $search = $mysqli->real_escape_string($filters['search']);
        $query .= " AND (full_name LIKE '%{$search}%' OR email LIKE '%{$search}%' OR subject LIKE '%{$search}%' OR message LIKE '%{$search}%')";
    }
    
    $query .= " ORDER BY id DESC LIMIT 50000";
    
    $columns = ['id', 'full_name', 'email', 'mobile', 'subject', 'message', 'status', 'ip_address', 'created_at'];
    $columnHeaders = [
        'id' => 'ID',
        'full_name' => 'Full Name',
        'email' => 'Email',
        'mobile' => 'Phone',
        'subject' => 'Subject',
        'message' => 'Message',
        'status' => 'Status',
        'ip_address' => 'IP Address',
        'created_at' => 'Date Submitted'
    ];
    
    $exporter->exportFromQuery($query, 'inquiries', $columns, $columnHeaders);
}

/**
 * Export Customer Contacts
 */
function exportCustomerContacts($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            cc.id,
            c.full_name AS customer_name,
            c.email AS customer_email,
            cc.first_name,
            cc.last_name,
            cc.position,
            cc.email,
            cc.phone,
            cc.notes,
            cc.is_active,
            cc.created_at
        FROM `" . DB::CUSTOMER_CONTACTS . "` cc
        LEFT JOIN `" . DB::CUSTOMERS . "` c ON cc.customer_id = c.id
        WHERE 1=1
    ";
    
    // Apply customer_id filter if provided
    if (!empty($filters['customer_id'])) {
        $customerId = (int)$filters['customer_id'];
        $query .= " AND cc.customer_id = {$customerId}";
    }
    
    $query .= " ORDER BY cc.id DESC LIMIT 50000";
    
    $columns = ['id', 'customer_name', 'first_name', 'last_name', 'position', 'email', 'phone', 'notes', 'is_active', 'created_at'];
    $columnHeaders = [
        'id' => 'ID',
        'customer_name' => 'Customer',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'position' => 'Position',
        'email' => 'Email',
        'phone' => 'Phone',
        'notes' => 'Notes',
        'is_active' => 'Active',
        'created_at' => 'Created Date'
    ];
    
    $exporter->exportFromQuery($query, 'customer_contacts', $columns, $columnHeaders);
}


/**
 * Export Geo Cities
 */
function exportGeoCities($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            gc.id,
            gc.slug,
            gc.city,
            gc.city_ar,
            gs.state AS state_name,
            gco.country AS country_name,
            gc.is_active,
            gc.created_at,
            gc.updated_at
        FROM `" . DB::GEO_CITIES . "` gc
        LEFT JOIN `" . DB::GEO_STATES . "` gs ON gc.state_id = gs.id
        LEFT JOIN `" . DB::GEO_COUNTRIES . "` gco ON gc.country_id = gco.id
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['search'])) {
        $search = $mysqli->real_escape_string($filters['search']);
        $query .= " AND (gc.city LIKE '%{$search}%' OR gc.slug LIKE '%{$search}%')";
    }
    
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $isActive = (int)$filters['is_active'];
        $query .= " AND gc.is_active = {$isActive}";
    }
    
    $query .= " ORDER BY gc.id DESC LIMIT 50000";
    
    $columns = ['id', 'slug', 'city', 'city_ar', 'state_name', 'country_name', 'is_active', 'created_at', 'updated_at'];
    $columnHeaders = [
        'id' => 'ID',
        'slug' => 'Slug',
        'city' => 'City',
        'city_ar' => 'City (Arabic)',
        'state_name' => 'State',
        'country_name' => 'Country',
        'is_active' => 'Active',
        'created_at' => 'Created',
        'updated_at' => 'Updated'
    ];
    
    $exporter->exportFromQuery($query, 'geo_cities', $columns, $columnHeaders);
}

/**
 * Export Geo States
 */
function exportGeoStates($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            gs.id,
            gs.slug,
            gs.state,
            gs.state_ar,
            gco.country AS country_name,
            gs.is_active,
            gs.created_at,
            gs.updated_at
        FROM `" . DB::GEO_STATES . "` gs
        LEFT JOIN `" . DB::GEO_COUNTRIES . "` gco ON gs.country_id = gco.id
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['search'])) {
        $search = $mysqli->real_escape_string($filters['search']);
        $query .= " AND (gs.state LIKE '%{$search}%' OR gs.slug LIKE '%{$search}%')";
    }
    
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $isActive = (int)$filters['is_active'];
        $query .= " AND gs.is_active = {$isActive}";
    }
    
    $query .= " ORDER BY gs.id DESC LIMIT 50000";
    
    $columns = ['id', 'slug', 'state', 'state_ar', 'country_name', 'is_active', 'created_at', 'updated_at'];
    $columnHeaders = [
        'id' => 'ID',
        'slug' => 'Slug',
        'state' => 'State',
        'state_ar' => 'State (Arabic)',
        'country_name' => 'Country',
        'is_active' => 'Active',
        'created_at' => 'Created',
        'updated_at' => 'Updated'
    ];
    
    $exporter->exportFromQuery($query, 'geo_states', $columns, $columnHeaders);
}

/**
 * Export Geo Countries
 */
function exportGeoCountries($exporter, $mysqli, $filters) {
    $query = "
        SELECT 
            id,
            slug,
            country,
            country_ar,
            dialing_code,
            abbr,
            is_active,
            created_at,
            updated_at
        FROM `" . DB::GEO_COUNTRIES . "`
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['search'])) {
        $search = $mysqli->real_escape_string($filters['search']);
        $query .= " AND (country LIKE '%{$search}%' OR slug LIKE '%{$search}%' OR abbr LIKE '%{$search}%')";
    }
    
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $isActive = (int)$filters['is_active'];
        $query .= " AND is_active = {$isActive}";
    }
    
    $query .= " ORDER BY id DESC LIMIT 50000";
    
    $columns = ['id', 'slug', 'country', 'country_ar', 'dialing_code', 'abbr', 'is_active', 'created_at', 'updated_at'];
    $columnHeaders = [
        'id' => 'ID',
        'slug' => 'Slug',
        'country' => 'Country',
        'country_ar' => 'Country (Arabic)',
        'dialing_code' => 'Dialing Code',
        'abbr' => 'Abbreviation',
        'is_active' => 'Active',
        'created_at' => 'Created',
        'updated_at' => 'Updated'
    ];
    
    $exporter->exportFromQuery($query, 'geo_countries', $columns, $columnHeaders);
}