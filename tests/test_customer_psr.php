<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Core\Database;
use App\Repository\CustomerRepository;
use App\Service\CustomerService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

echo "==================================================\n";
echo "PSR Customer Module Integration Tests\n";
echo "==================================================\n\n";

try {
    $db = new Database();
    $customerRepo = new CustomerRepository($db);
    $customerService = new CustomerService($customerRepo);

    $testOrgId = 9999;
    $testUserId = 12345;

    // Clean up any old test data
    $db->execute("DELETE FROM `erp_customer_addresses` WHERE organization_id = :org", ['org' => $testOrgId]);
    $db->execute("DELETE FROM `erp_customer_contacts` WHERE organization_id = :org", ['org' => $testOrgId]);
    $db->execute("DELETE FROM `erp_customers` WHERE organization_id = :org", ['org' => $testOrgId]);

    // Test 1: Create Customer
    echo "[TEST 1] Creating customer via CustomerService... ";
    $customerData = [
        'display_name' => 'Integration Test Customer',
        'address' => '123 Test Street, Dubai',
        'email' => 'integration.test@haipulse.com',
        'phone' => '+971 4 000 0000',
        'customer_type' => 'business',
        'opening_balance' => 100.50
    ];
    $customer = $customerService->createCustomer($customerData, $testOrgId, $testUserId);
    if ($customer->id !== null && $customer->displayName === 'Integration Test Customer' && $customer->organizationId === $testOrgId) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to create customer correctly.");
    }

    // Test 2: Validation of Mandatory Fields
    echo "[TEST 2] Verifying mandatory field validations... ";
    try {
        $customerService->createCustomer(['display_name' => ''], $testOrgId, $testUserId);
        echo "✗ FAIL (Allowed empty display name)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['display_name']) && isset($errors['address'])) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL (Unexpected validation errors: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }

    // Test 3: Validation of Duplicate Email
    echo "[TEST 3] Verifying duplicate email validation... ";
    try {
        $customerService->createCustomer($customerData, $testOrgId, $testUserId);
        echo "✗ FAIL (Allowed duplicate email)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['email']) && strpos($errors['email'], 'Duplicate Email') !== false) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL (Unexpected validation message: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }

    // Test 4: Get Customer
    echo "[TEST 4] Fetching customer via CustomerService... ";
    $fetched = $customerService->getCustomer((int)$customer->id, $testOrgId);
    if ($fetched->id === $customer->id && $fetched->displayName === $customer->displayName) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to fetch customer.");
    }

    // Test 5: Update Customer
    echo "[TEST 5] Updating customer via CustomerService... ";
    $updated = $customerService->updateCustomer((int)$customer->id, [
        'display_name' => 'Integration Test Customer (Updated)'
    ], $testOrgId, $testUserId);
    if ($updated->displayName === 'Integration Test Customer (Updated)') {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to update customer.");
    }

    // Test 6: Approve Customer
    echo "[TEST 6] Approving customer... ";
    $approved = $customerService->approveCustomer((int)$customer->id, $testOrgId, $testUserId);
    if ($approved->approved === true && $approved->approvedBy === $testUserId) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to approve customer.");
    }

    // Test 7: Disapprove Customer
    echo "[TEST 7] Disapproving customer... ";
    $disapproved = $customerService->disapproveCustomer((int)$customer->id, $testOrgId, $testUserId);
    if ($disapproved->approved === false) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to disapprove customer.");
    }

    // Test 8: Clone Customer
    echo "[TEST 8] Cloning customer... ";
    $cloned = $customerService->cloneCustomer((int)$customer->id, $testOrgId, $testUserId);
    if ($cloned->id !== $customer->id && $cloned->displayName === $updated->displayName . ' (Copy)' && $cloned->approved === false) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to clone customer.");
    }

    // Test 9: Active / Inactive
    echo "[TEST 9] Marking customer as Active/Inactive... ";
    $activeCust = $customerService->markAsActive((int)$customer->id, $testOrgId, $testUserId);
    $inactiveCust = $customerService->markAsInactive((int)$customer->id, $testOrgId, $testUserId);
    if ($activeCust->isActive === true && $inactiveCust->isActive === false) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to update active/inactive status.");
    }

    // Test 10: Create Contact
    echo "[TEST 10] Creating customer contact... ";
    $contactData = [
        'customer_id' => $customer->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@haipulse.com',
        'phone' => '+971 50 111 2222',
        'position' => 'Manager',
        'is_primary' => true
    ];
    $contact = $customerService->createContact($contactData, $testOrgId, $testUserId);
    if ($contact->id !== null && $contact->firstName === 'John' && $contact->isPrimary === true) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to create customer contact.");
    }

    // Test 11: Update Contact & Primary Flag Switch
    echo "[TEST 11] Updating contact and testing primary toggle... ";
    $contact2 = $customerService->createContact([
        'customer_id' => $customer->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@haipulse.com',
        'is_primary' => true
    ], $testOrgId, $testUserId);

    $oldContact = $customerRepo->findContact((int)$contact->id, $testOrgId);
    if ($contact2->isPrimary === true && $oldContact->isPrimary === false) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to toggle primary contact flag correctly.");
    }

    // Test 12: Create Addresses
    echo "[TEST 12] Creating billing & shipping addresses... ";
    $billingAddr = $customerService->createAddress([
        'customer_id' => $customer->id,
        'type' => 'billing',
        'attention' => 'Accounts Department',
        'country' => 229, // UAE
        'address_line1' => 'Standard Address Line 1',
        'city' => 'Dubai'
    ], $testOrgId, $testUserId);

    $shippingAddr = $customerService->createAddress([
        'customer_id' => $customer->id,
        'type' => 'shipping',
        'attention' => 'Warehouse A',
        'country' => 229,
        'address_line1' => 'Shipping Address Line 1',
        'city' => 'Abu Dhabi'
    ], $testOrgId, $testUserId);

    if ($billingAddr->id !== null && $shippingAddr->id !== null) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to create addresses.");
    }

    // Test 13: Cascade Deletion
    echo "[TEST 13] Verifying cascade deletion of customer, contacts, and addresses... ";
    $customerService->deleteCustomer((int)$customer->id, $testOrgId);

    // Verify deleted from DB
    $checkCust = $customerRepo->find((int)$customer->id, $testOrgId);
    $checkContact = $customerRepo->findContact((int)$contact->id, $testOrgId);
    $checkAddress = $customerRepo->findAddress((int)$billingAddr->id, $testOrgId);

    if ($checkCust === null && $checkContact === null && $checkAddress === null) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Cascade deletion failed to clean up sub-entities.");
    }

    // Clean up cloned customer
    $customerService->deleteCustomer((int)$cloned->id, $testOrgId);

    echo "\nAll integration tests passed successfully!\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
