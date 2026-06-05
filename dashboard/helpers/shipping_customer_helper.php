<?php
/**
 * Shipping Customer Helper Functions
 * Created: 2026-02-09
 *
 * Functions to manage shipping customer data extracted from Excel invoices
 */

/**
 * Parse customer information from Excel address field
 * Extracts phone number, city, country from address string
 *
 * Example formats:
 * - "B/O VEN PRO LLP,ZHETYSU REGION PANFILOVSKY DISTRICT PENZHIM,041300 KZ,TL:+77077443030"
 * - "AGENCY,EMIN BALEV, BAKU, AZERBAIJAN AZ,TL:+9945800421"
 *
 * @param string $address_string Full address from Excel
 * @return array Parsed components: address, phone, city, country
 */
function parseCustomerAddress($address_string) {
    $result = [
        'address' => '',
        'phone' => '',
        'city' => '',
        'country' => ''
    ];

    if (empty($address_string)) {
        return $result;
    }

    // Extract phone number (pattern: TL:+xxxxx or Tel:+xxxx)
    if (preg_match('/TL?:?\s*(\+[\d\s\-]+)/i', $address_string, $matches)) {
        $result['phone'] = trim($matches[1]);
        // Remove phone from address string
        $address_string = preg_replace('/,?\s*TL?:?\s*\+[\d\s\-]+/i', '', $address_string);
    }

    // Split by comma
    $parts = array_map('trim', explode(',', $address_string));

    // Last part often contains country code (e.g., "KZ", "AZ")
    if (count($parts) > 0) {
        $last_part = end($parts);
        // Check if last part is just country code (2 letters)
        if (preg_match('/^[A-Z]{2}$/i', $last_part)) {
            $result['country'] = $last_part;
            array_pop($parts);
        }
    }

    // Try to extract city (usually second-to-last part or contains "CITY" keyword)
    foreach ($parts as $part) {
        if (preg_match('/\b(CITY|BAKU|ALMATY|DISTRICT)\b/i', $part)) {
            $result['city'] = $part;
            break;
        }
    }

    // Remaining parts form the address
    $result['address'] = implode(', ', $parts);

    return $result;
}

/**
 * Find or create shipping customer in database
 * Checks if customer exists by name, creates if not found
 *
 * @param mysqli $mysqli Database connection
 * @param string $customer_name Customer name from Excel
 * @param string $customer_address Full address string from Excel
 * @return int Customer ID
 */
function findOrCreateShippingCustomer($mysqli, $customer_name, $customer_address = '') {
    global $tbl_prefix;
    $tbl_name = $tbl_prefix . 'shipping_customers';

    // Check if table exists (for backward compatibility)
    $table_check = $mysqli->query("SHOW TABLES LIKE '$tbl_name'");
    if ($table_check->num_rows == 0) {
        return 0;
    }

    // Sanitize customer name
    $customer_name = trim($customer_name);
    if (empty($customer_name)) {
        return 0;
    }

    // Check if customer exists
    $check_sql = "SELECT id FROM `$tbl_name` WHERE customer_name = '" . $mysqli->real_escape_string($customer_name) . "' LIMIT 1";
    $result = $mysqli->query($check_sql);

    if ($result && $result->num_rows > 0) {
        // Customer exists, return ID
        $row = $result->fetch_assoc();
        return (int)$row['id'];
    }

    // Customer doesn't exist, create new record
    $parsed = parseCustomerAddress($customer_address);

    $insert_sql = "INSERT INTO `$tbl_name` (
        customer_name,
        customer_address,
        customer_phone,
        customer_city,
        customer_country,
        customer_type,
        is_active,
        created_at
    ) VALUES (
        '" . $mysqli->real_escape_string($customer_name) . "',
        '" . $mysqli->real_escape_string($parsed['address']) . "',
        '" . $mysqli->real_escape_string($parsed['phone']) . "',
        '" . $mysqli->real_escape_string($parsed['city']) . "',
        '" . $mysqli->real_escape_string($parsed['country']) . "',
        'importer',
        1,
        NOW()
    )";

    if ($mysqli->query($insert_sql)) {
        return $mysqli->insert_id;
    }

    return 0;
}

/**
 * Get customer details by ID
 *
 * @param mysqli $mysqli Database connection
 * @param int $customer_id Customer ID
 * @return array|null Customer data or null if not found
 */
function getShippingCustomer($mysqli, $customer_id) {
    global $tbl_prefix;
    $tbl_name = $tbl_prefix . 'shipping_customers';

    $sql = "SELECT * FROM `$tbl_name` WHERE id = " . (int)$customer_id . " LIMIT 1";
    $result = $mysqli->query($sql);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Get customer name by ID
 *
 * @param mysqli $mysqli Database connection
 * @param int $customer_id Customer ID
 * @return string Customer name or empty string
 */
function getShippingCustomerName($mysqli, $customer_id) {
    $customer = getShippingCustomer($mysqli, $customer_id);
    return $customer ? $customer['customer_name'] : '';
}

/**
 * Get all shipping customers for dropdown
 *
 * @param mysqli $mysqli Database connection
 * @param bool $active_only Return only active customers
 * @return array Array of customers with id and customer_name
 */
function getAllShippingCustomers($mysqli, $active_only = true) {
    global $tbl_prefix;
    $tbl_name = $tbl_prefix . 'shipping_customers';

    $sql = "SELECT id, customer_name, customer_city, customer_country
            FROM `$tbl_name`";

    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }

    $sql .= " ORDER BY customer_name ASC";

    $result = $mysqli->query($sql);
    $customers = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
    }

    return $customers;
}
