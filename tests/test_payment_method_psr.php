<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Model\PaymentMethod;
use App\Repository\PaymentMethodRepository;
use App\Service\PaymentMethodService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

global $db;

echo "==================================================\n";
echo "PSR-4 PaymentMethod Architecture Integration Tests\n";
echo "==================================================\n\n";

try {
    $repo = new PaymentMethodRepository($db);
    $service = new PaymentMethodService($repo);

    $testOrgId = 999;
    $testUserId = 101;
    $testName = 'Test Payment Method ' . uniqid();

    // Clean any prior test data
    $db->execute("DELETE FROM erp_payment_methods WHERE organization_id = :org", ['org' => $testOrgId]);

    // Test 1: Create
    echo "[TEST 1] Creating payment method via Service... ";
    $method = $service->create([
        'payment_method' => $testName,
    ], $testOrgId, $testUserId);
    assert($method->id !== null, 'PaymentMethod ID should not be null');
    assert($method->paymentMethod === $testName, 'PaymentMethod name should match');
    echo "✓ PASS (id={$method->id})\n";

    // Test 2: Fetch by ID
    echo "[TEST 2] Fetching payment method via Repository... ";
    $fetched = $repo->find((int)$method->id, $testOrgId);
    assert($fetched !== null, 'PaymentMethod should be fetchable');
    assert($fetched->paymentMethod === $testName, 'PaymentMethod name should match');
    echo "✓ PASS\n";

    // Test 3: Update
    echo "[TEST 3] Updating payment method... ";
    $updated = $service->update((int)$method->id, ['payment_method' => $testName . ' Updated'], $testOrgId);
    assert($updated->paymentMethod === $testName . ' Updated', 'PaymentMethod name should be updated');
    $refetched = $repo->find((int)$method->id, $testOrgId);
    assert($refetched !== null && $refetched->paymentMethod === $testName . ' Updated', 'Update should persist');
    echo "✓ PASS\n";

    // Test 4: Validation - empty name
    echo "[TEST 4] Validation on empty payment method name... ";
    try {
        $service->create(['payment_method' => ''], $testOrgId, $testUserId);
        echo "✗ FAIL (Allowed invalid data)\n";
        exit(1);
    } catch (ValidationException $e) {
        echo "✓ PASS (Caught ValidationException)\n";
    }

    // Test 5: List
    echo "[TEST 5] Listing payment methods... ";
    $list = $service->list($testOrgId);
    assert(count($list) >= 1, 'List should contain at least the test payment method');
    echo "✓ PASS (count=" . count($list) . ")\n";

    // Test 6: Delete
    echo "[TEST 6] Deleting payment method... ";
    $service->delete((int)$method->id, $testOrgId);
    try {
        $service->getById((int)$method->id, $testOrgId);
        echo "✗ FAIL (Should have thrown NotFoundException)\n";
        exit(1);
    } catch (NotFoundException $e) {
        echo "✓ PASS (Confirmed deleted)\n";
    }

    // Clean up
    $db->execute("DELETE FROM erp_payment_methods WHERE organization_id = :org", ['org' => $testOrgId]);
    echo "\nAll payment method tests passed successfully!\n";

} catch (Throwable $e) {
    echo "ERROR during tests: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
