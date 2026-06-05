<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Core\Database;
use App\Core\Container;
use App\DataTable\Registry as DataTableRegistry;

echo "==================================================\n";
echo "PSR DataTable PDO Upgrade Integration Tests\n";
echo "==================================================\n\n";

try {
    $db = new Database();
    
    // Set up a mock/test department to run Datatable on
    $testOrgId = 999;
    $db->execute("DELETE FROM erp_department WHERE organization_id = :org", ['org' => $testOrgId]);
    
    $db->execute(
        "INSERT INTO erp_department (organization_id, department, publish, created_by) VALUES (:org, 'Test Dept 1', 1, 1)",
        ['org' => $testOrgId]
    );
    $db->execute(
        "INSERT INTO erp_department (organization_id, department, publish, created_by) VALUES (:org, 'Test Dept 2', 1, 1)",
        ['org' => $testOrgId]
    );
    $db->execute(
        "INSERT INTO erp_department (organization_id, department, publish, created_by) VALUES (:org, 'Another Dept', 0, 1)",
        ['org' => $testOrgId]
    );

    // Initialize Registry with App\Core\Database and test contexts
    // Pass null for user and role, and $testOrgId for organizationId scoping
    $registry = new DataTableRegistry($db, null, null, $testOrgId);

    // Test 1: Verify registry has listing_departments registered
    echo "[TEST 1] Verifying handler registration... ";
    if ($registry->isRegistered('listing_departments')) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("listing_departments is not registered.");
    }

    // Test 2: Process request for departments
    echo "[TEST 2] Processing DataTable request via Registry & PDO... ";
    $requestData = [
        'draw' => 1,
        'start' => 0,
        'length' => 10,
        'search' => ['value' => 'Test'],
        'order' => [[
            'column' => 1,
            'dir' => 'asc'
        ]]
    ];

    $response = $registry->process('listing_departments', $requestData);

    if (isset($response['recordsTotal']) && isset($response['data'])) {
        echo "✓ PASS\n";
        echo "        Total Records: " . $response['recordsTotal'] . "\n";
        echo "        Filtered Records: " . $response['recordsFiltered'] . "\n";
        echo "        Data Count: " . count($response['data']) . "\n";
        
        // Assertions
        // Active org has 3 departments total, but only 2 match search value 'Test'
        if ($response['recordsTotal'] !== 3) {
            throw new Exception("Expected 3 records total, found: " . $response['recordsTotal']);
        }
        if ($response['recordsFiltered'] !== 2) {
            throw new Exception("Expected 2 filtered records, found: " . $response['recordsFiltered']);
        }
    } else {
        throw new Exception("Invalid response structure: " . json_encode($response));
    }

    // Clean up departments
    $db->execute("DELETE FROM erp_department WHERE organization_id = :org", ['org' => $testOrgId]);


    // Test 5: Verify GeoCountriesDataTable integration
    echo "[TEST 5] Processing GeoCountriesDataTable request... ";
    $db->execute("DELETE FROM erp_geo_countries WHERE country = 'Test Country'");
    $db->execute(
        "INSERT INTO erp_geo_countries (slug, country, country_ar, dialing_code, abbr, is_active) VALUES ('test-country', 'Test Country', 'Test Country AR', '999', 'TC', 1)"
    );
    
    $responseGeo = $registry->process('listing_geo_countries', [
        'draw' => 1,
        'start' => 0,
        'length' => 10,
        'search' => ['value' => 'Test Country'],
        'order' => [[
            'column' => 0,
            'dir' => 'desc'
        ]]
    ]);
    
    if (isset($responseGeo['recordsTotal']) && count($responseGeo['data']) >= 1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Expected at least 1 geo country record, found: " . json_encode($responseGeo));
    }
    $db->execute("DELETE FROM erp_geo_countries WHERE country = 'Test Country'");

    // Test 6: Verify InquiriesDataTable integration
    echo "[TEST 6] Processing InquiriesDataTable request... ";
    $db->execute("DELETE FROM erp_inquiries WHERE email = 'test_inquiry@test.com'");
    $db->execute(
        "INSERT INTO erp_inquiries (full_name, email, mobile, subject, message, status, is_spam, is_active) VALUES ('Test Name', 'test_inquiry@test.com', '123456789', 'Test Subject', 'Test Message', 0, 0, 1)"
    );
    
    $responseInq = $registry->process('listing_inquiries', [
        'draw' => 1,
        'start' => 0,
        'length' => 10,
        'search' => ['value' => 'test_inquiry'],
        'order' => [[
            'column' => 1,
            'dir' => 'desc'
        ]]
    ]);
    
    if (isset($responseInq['recordsTotal']) && count($responseInq['data']) >= 1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Expected at least 1 inquiry record, found: " . json_encode($responseInq));
    }
    $db->execute("DELETE FROM erp_inquiries WHERE email = 'test_inquiry@test.com'");

    echo "\nAll DataTable PDO upgrade tests passed successfully!\n";

} catch (Throwable $e) {
    echo "ERROR during tests: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
