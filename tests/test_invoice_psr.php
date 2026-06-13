<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'localhost';

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

$project_pre = $GLOBALS['project_pre'] ?? 'haizon';
$_SESSION[$project_pre] = [
    'DASHBOARD' => [
        'user_id' => 12345,
        'role_id' => 1
    ]
];
$_SESSION['h_role_id'] = 1;

use App\Core\Database;
use App\Repository\InvoiceRepository;
use App\Repository\CustomerRepository;
use App\Service\InvoiceService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

echo "==================================================\n";
echo "PSR Invoice Module Integration Tests\n";
echo "==================================================\n\n";

try {
    $db = new Database();
    $invoiceRepo = new InvoiceRepository($db);
    $customerRepo = new CustomerRepository($db);
    $invoiceService = new InvoiceService($invoiceRepo, $customerRepo, $db);

    $testOrgId = 9999;
    $testUserId = 12345;

    // Clean up any old test data
    $db->execute("DELETE FROM `erp_invoice_items` WHERE organization_id = :org", ['org' => $testOrgId]);
    $db->execute("DELETE FROM `erp_invoices` WHERE organization_id = :org", ['org' => $testOrgId]);
    $db->execute("DELETE FROM `erp_customers` WHERE organization_id = :org", ['org' => $testOrgId]);

    // Setup a dummy customer for testing invoices
    $sqlCust = "INSERT INTO `erp_customers` (id, organization_id, display_name, address, email, publish, is_active, created_by, customer_type)
                VALUES (7777, :org, 'Test Customer for Invoices', '123 Test Rd', 'invoice.cust@test.com', 1, 1, :created_by, 'business')";
    $db->execute($sqlCust, ['org' => $testOrgId, 'created_by' => $testUserId]);

    // Test 1: Create Invoice
    echo "[TEST 1] Creating invoice via InvoiceService... ";
    $invoiceData = [
        'customer_id' => 7777,
        'invoice_date' => date('Y-m-d'),
        'expiry_date' => date('Y-m-d', strtotime('+30 days')),
        'grand_subtotal' => 100.00,
        'grand_total' => 105.00,
        'grand_tax' => 5.00,
        'warehouse_id' => 1
    ];
    $itemsData = [
        [
            'service' => 1,
            'description' => 'Test Service Line Item 1',
            'qty' => 2,
            'rate' => 50.00,
            'sub_total' => 100.00,
            'tax' => 5.00,
            'tax_amount' => 5.00,
            'total' => 105.00
        ]
    ];

    $invoice = $invoiceService->createInvoice($invoiceData, $itemsData, $testOrgId, $testUserId);
    if ($invoice->id !== null && $invoice->customerId === 7777 && strpos($invoice->invoiceNo, 'FL-IN') === 0) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to create invoice header.");
    }

    // Test 2: Verify Invoice Items created
    echo "[TEST 2] Verifying cascade creation of line items... ";
    $items = $invoiceService->getInvoiceItems((int)$invoice->id, $testOrgId);
    if (count($items) === 1 && $items[0]->description === 'Test Service Line Item 1') {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to cascade create line items.");
    }

    // Test 3: Validation checks
    echo "[TEST 3] Verifying mandatory fields validation... ";
    try {
        $invoiceService->createInvoice(['customer_id' => 'Please select'], [], $testOrgId, $testUserId);
        echo "✗ FAIL (Allowed empty customer)\n";
        exit(1);
    } catch (ValidationException $e) {
        echo "✓ PASS\n";
    }

    // Test 4: Get Public Fetch Methods
    echo "[TEST 4] Verifying public token access fetchers... ";
    $publicInvoice = $invoiceService->getInvoicePublic((int)$invoice->id);
    $publicItems = $invoiceService->getInvoiceItemsPublic((int)$invoice->id);
    if ($publicInvoice->id === $invoice->id && count($publicItems) === 1) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Public token-based loaders failed.");
    }

    // Test 5: Update Invoice and Cascade Items Diff
    echo "[TEST 5] Updating invoice & testing cascade items diffing... ";
    $updatedInvoiceData = [
        'customer_id' => 7777,
        'invoice_date' => date('Y-m-d'),
        'grand_subtotal' => 150.00,
        'grand_total' => 150.00,
        'grand_tax' => 0.00,
        'warehouse_id' => 1
    ];
    $updatedItemsData = [
        // Keep the old one but update it
        [
            'id' => $items[0]->id,
            'service' => 1,
            'description' => 'Test Service Line Item 1 (Updated)',
            'qty' => 1,
            'rate' => 100.00,
            'sub_total' => 100.00,
            'tax' => 0.00,
            'tax_amount' => 0.00,
            'total' => 100.00
        ],
        // Add a new one
        [
            'service' => 2,
            'description' => 'New Service Line Item',
            'qty' => 1,
            'rate' => 50.00,
            'sub_total' => 50.00,
            'tax' => 0.00,
            'tax_amount' => 0.00,
            'total' => 50.00
        ]
    ];
    $invoiceService->updateInvoice((int)$invoice->id, $updatedInvoiceData, $updatedItemsData, $testOrgId, $testUserId);
    
    $itemsAfterUpdate = $invoiceService->getInvoiceItems((int)$invoice->id, $testOrgId);
    if (count($itemsAfterUpdate) === 2 && $itemsAfterUpdate[0]->description === 'Test Service Line Item 1 (Updated)') {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Cascade updating items failed.");
    }

    // Test 6: Clone Invoice
    echo "[TEST 6] Cloning invoice... ";
    $cloned = $invoiceService->cloneInvoice((int)$invoice->id, $testOrgId, $testUserId);
    if ($cloned->id !== $invoice->id && $cloned->invoiceStatus === 'draft') {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Cloning invoice failed.");
    }

    // Test 7: Confirmed status guard
    echo "[TEST 7] Verifying confirmed deletion guard... ";
    $invoiceService->updateStatus((int)$invoice->id, 'confirmed', $testOrgId);
    try {
        $invoiceService->deleteInvoice((int)$invoice->id, $testOrgId);
        echo "✗ FAIL (Allowed deleting confirmed invoice)\n";
        exit(1);
    } catch (ValidationException $e) {
        echo "✓ PASS\n";
    }

    // Test 8: Cascade Deletion
    echo "[TEST 8] Deleting draft invoice & verifying cascade... ";
    $invoiceService->deleteInvoice((int)$cloned->id, $testOrgId);
    
    // Verify cloned items are deleted
    $clonedItems = $invoiceService->getInvoiceItems((int)$cloned->id, $testOrgId);
    if (empty($clonedItems)) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Cascade deletion of line items failed.");
    }

    // Clean up confirmed test invoice
    $invoiceService->updateStatus((int)$invoice->id, 'draft', $testOrgId);
    $invoiceService->deleteInvoice((int)$invoice->id, $testOrgId);
    $db->execute("DELETE FROM `erp_customers` WHERE id = 7777 AND organization_id = :org", ['org' => $testOrgId]);

    echo "\nAll Invoice integration tests passed successfully!\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
