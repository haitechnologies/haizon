<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Model\Currency;
use App\Repository\CurrencyRepository;
use App\Service\CurrencyService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

global $db;

echo "==================================================\n";
echo "PSR-4 Currency Architecture Integration Tests\n";
echo "==================================================\n\n";

try {
    $repo = new CurrencyRepository($db);
    $service = new CurrencyService($repo);

    $testOrgId = 999;
    $testUserId = 101;
    $testName = 'Test Currency ' . uniqid();

    // Clean any prior test data
    $db->execute("DELETE FROM erp_currencies WHERE organization_id = :org", ['org' => $testOrgId]);

    // Test 1: Create
    echo "[TEST 1] Creating currency via Service... ";
    $currency = $service->create([
        'currency' => $testName,
    ], $testOrgId, $testUserId);
    assert($currency->id !== null, 'Currency ID should not be null');
    assert($currency->currency === $testName, 'Currency name should match');
    echo "✓ PASS (id={$currency->id})\n";

    // Test 2: Fetch by ID
    echo "[TEST 2] Fetching currency via Repository... ";
    $fetched = $repo->find((int)$currency->id, $testOrgId);
    assert($fetched !== null, 'Currency should be fetchable');
    assert($fetched->currency === $testName, 'Currency name should match');
    echo "✓ PASS\n";

    // Test 3: Update
    echo "[TEST 3] Updating currency... ";
    $updated = $service->update((int)$currency->id, ['currency' => $testName . ' Updated'], $testOrgId);
    assert($updated->currency === $testName . ' Updated', 'Currency name should be updated');
    $refetched = $repo->find((int)$currency->id, $testOrgId);
    assert($refetched !== null && $refetched->currency === $testName . ' Updated', 'Update should persist');
    echo "✓ PASS\n";

    // Test 4: Validation - empty name
    echo "[TEST 4] Validation on empty currency name... ";
    try {
        $service->create(['currency' => ''], $testOrgId, $testUserId);
        echo "✗ FAIL (Allowed invalid data)\n";
        exit(1);
    } catch (ValidationException $e) {
        echo "✓ PASS (Caught ValidationException)\n";
    }

    // Test 5: List
    echo "[TEST 5] Listing currencies... ";
    $list = $service->list($testOrgId);
    assert(count($list) >= 1, 'List should contain at least the test currency');
    echo "✓ PASS (count=" . count($list) . ")\n";

    // Test 6: Delete
    echo "[TEST 6] Deleting currency... ";
    $service->delete((int)$currency->id, $testOrgId);
    try {
        $service->getById((int)$currency->id, $testOrgId);
        echo "✗ FAIL (Should have thrown NotFoundException)\n";
        exit(1);
    } catch (NotFoundException $e) {
        echo "✓ PASS (Confirmed deleted)\n";
    }

    // Clean up
    $db->execute("DELETE FROM erp_currencies WHERE organization_id = :org", ['org' => $testOrgId]);
    echo "\nAll currency tests passed successfully!\n";

} catch (Throwable $e) {
    echo "ERROR during tests: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
