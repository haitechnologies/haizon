<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Core\Database;
use App\Model\Department;
use App\Repository\DepartmentRepository;
use App\Repository\UserRepository;
use App\Service\DepartmentService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

echo "==================================================\n";
echo "PSR-4 Department Architecture Integration Tests\n";
echo "==================================================\n\n";

try {
    $db = new Database();
    $deptRepo = new DepartmentRepository($db);
    $userRepo = new UserRepository($db);
    $deptService = new DepartmentService($deptRepo, $userRepo);

    $testOrgId = 999;
    $testUserId = 101;

    // Clean any prior leftovers
    $db->execute("DELETE FROM erp_department WHERE organization_id = :org", ['org' => $testOrgId]);

    // Test 1: Create a department via Service
    echo "[TEST 1] Creating department via Service... ";
    $name1 = "PSR Test Dept A";
    $dept1 = $deptService->create($name1, $testOrgId, $testUserId);
    if ($dept1->id !== null && $dept1->department === $name1 && $dept1->organizationId === $testOrgId) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Failed to create department correctly.");
    }

    // Test 2: Fetch department from Repository
    echo "[TEST 2] Fetching department via Repository... ";
    $fetched = $deptRepo->find((int)$dept1->id);
    if ($fetched !== null && $fetched->department === $name1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Failed to fetch department.");
    }

    // Test 3: Test duplicate validation exception
    echo "[TEST 3] Testing duplicate department validation... ";
    try {
        $deptService->create($name1, $testOrgId, $testUserId);
        echo "✗ FAIL (Allowed duplicate)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['department']) && strpos($errors['department'], 'already exists') !== false) {
            echo "✓ PASS (Caught expected ValidationException)\n";
        } else {
            echo "✗ FAIL (Wrong exception message: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }

    // Test 4: Update via Service
    echo "[TEST 4] Testing update via Service... ";
    $updatedName1 = "PSR Test Dept A (Updated)";
    $deptService->update((int)$dept1->id, $updatedName1, true);
    
    $fetchedUpdated = $deptRepo->find((int)$dept1->id);
    if ($fetchedUpdated && $fetchedUpdated->department === $updatedName1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Update did not persist.");
    }

    // Test 5: Delete via Service
    echo "[TEST 5] Testing delete via Service... ";
    $deptService->delete((int)$dept1->id);
    
    $fetchedDeleted = $deptRepo->find((int)$dept1->id);
    if ($fetchedDeleted === null) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Delete did not remove record.");
    }

    // Clean up
    $db->execute("DELETE FROM erp_department WHERE organization_id = :org", ['org' => $testOrgId]);
    echo "\nAll department tests passed successfully!\n";

} catch (Throwable $e) {
    echo "ERROR during tests: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
