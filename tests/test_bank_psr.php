<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Model\Bank;
use App\Repository\BankRepository;
use App\Service\BankService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

global $db;

echo "==================================================\n";
echo "PSR-4 Bank Architecture Integration Tests\n";
echo "==================================================\n\n";

try {
    $repo = new BankRepository($db);
    $service = new BankService($repo);

    $testOrgId = 999;
    $testUserId = 101;
    $testName = 'Test Bank ' . uniqid();

    // Clean any prior test data
    $db->execute("DELETE FROM erp_banks WHERE bank_name LIKE :name", ['name' => 'Test Bank %']);

    // Test 1: Create
    echo "[TEST 1] Creating bank via Service... ";
    $bank = $service->create([
        'account_name' => $testName,
        'bank_name' => $testName,
        'account_no' => '1234567890',
        'iban' => 'AE123456789012345678901',
        'swift_code' => 'TESTAEAD',
    ], $testOrgId, $testUserId);
    assert($bank->id !== null, 'Bank ID should not be null');
    assert($bank->bankName === $testName, 'Bank name should match');
    echo "✓ PASS (id={$bank->id})\n";

    // Test 2: Fetch by ID
    echo "[TEST 2] Fetching bank via Repository... ";
    $fetched = $repo->find((int)$bank->id, $testOrgId);
    assert($fetched !== null, 'Bank should be fetchable');
    assert($fetched->bankName === $testName, 'Bank name should match');
    echo "✓ PASS\n";

    // Test 3: Update
    echo "[TEST 3] Updating bank... ";
    $updated = $service->update((int)$bank->id, ['bank_name' => $testName . ' Updated'], $testOrgId, $testUserId);
    assert($updated->bankName === $testName . ' Updated', 'Bank name should be updated');
    $refetched = $repo->find((int)$bank->id, $testOrgId);
    assert($refetched !== null && $refetched->bankName === $testName . ' Updated', 'Update should persist');
    echo "✓ PASS\n";

    // Test 4: Validation - empty name
    echo "[TEST 4] Validation on empty bank name... ";
    try {
        $service->create(['bank_name' => '', 'account_no' => ''], $testOrgId, $testUserId);
        echo "✗ FAIL (Allowed invalid data)\n";
        exit(1);
    } catch (ValidationException $e) {
        echo "✓ PASS (Caught ValidationException)\n";
    }

    // Test 5: Delete
    echo "[TEST 5] Deleting bank... ";
    $service->delete((int)$bank->id, $testOrgId);
    try {
        $service->getById((int)$bank->id, $testOrgId);
        echo "✗ FAIL (Should have thrown NotFoundException)\n";
        exit(1);
    } catch (NotFoundException $e) {
        echo "✓ PASS (Confirmed deleted)\n";
    }

    // Clean up
    $db->execute("DELETE FROM erp_banks WHERE bank_name LIKE :name", ['name' => 'Test Bank %']);
    echo "\nAll bank tests passed successfully!\n";

} catch (Throwable $e) {
    echo "ERROR during tests: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
