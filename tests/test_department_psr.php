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
echo "PSR-4 Department Architecture and Trigger Tests\n";
echo "==================================================\n\n";

try {
    $db = new Database();
    $deptRepo = new DepartmentRepository($db);
    $userRepo = new UserRepository($db);
    $deptService = new DepartmentService($deptRepo, $userRepo);

    $testOrgId = 999;
    $testUserId = 101;

    // Clean any prior leftovers
    $db->execute("DELETE FROM erp_departments WHERE organization_id = :org", ['org' => $testOrgId]);

    // Test 1: Create a department via Service
    echo "[TEST 1] Creating department via Service... ";
    $name1 = "PSR Test Dept A";
    $dept1 = $deptService->create($name1, $testOrgId, $testUserId);
    if ($dept1->id !== null && $dept1->department === $name1 && $dept1->organizationId === $testOrgId) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Failed to create department correctly.");
    }

    // Test 2: Verify trigger replicated to legacy table erp_departments
    echo "[TEST 2] Verifying trigger replication to erp_departments... ";
    $legacyRow = $db->fetchOne("SELECT * FROM erp_departments WHERE id = :id", ['id' => $dept1->id]);
    if ($legacyRow && $legacyRow['department'] === $name1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Data was not replicated to legacy table.");
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

    // Test 4: Write to legacy table and verify trigger replicates to erp_department
    echo "[TEST 4] Verifying legacy write triggers replication to erp_department... ";
    $name2 = "Legacy Sync Dept";
    $db->execute(
        "INSERT INTO erp_departments (organization_id, department, publish, created_by) 
         VALUES (:org, :dept, 1, :user)",
        ['org' => $testOrgId, 'dept' => $name2, 'user' => $testUserId]
    );
    
    // Find in erp_department
    $newRow = $db->fetchOne("SELECT * FROM erp_department WHERE department = :dept", ['dept' => $name2]);
    if ($newRow && (int)$newRow['organization_id'] === $testOrgId) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Data was not replicated from legacy table to new table.");
    }

    // Test 5: Update via Service and verify replication to legacy
    echo "[TEST 5] Testing update via Service and replication... ";
    $updatedName1 = "PSR Test Dept A (Updated)";
    $deptService->update((int)$dept1->id, $updatedName1, true);
    
    $legacyUpdated = $db->fetchOne("SELECT department FROM erp_departments WHERE id = :id", ['id' => $dept1->id]);
    if ($legacyUpdated && $legacyUpdated['department'] === $updatedName1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Update did not replicate to legacy table.");
    }

    // Test 6: Delete via Service and verify replication to legacy
    echo "[TEST 6] Testing delete via Service and replication... ";
    $deptService->delete((int)$dept1->id);
    
    $legacyCheck = $db->fetchOne("SELECT id FROM erp_departments WHERE id = :id", ['id' => $dept1->id]);
    $newCheck = $db->fetchOne("SELECT id FROM erp_department WHERE id = :id", ['id' => $dept1->id]);
    if (!$legacyCheck && !$newCheck) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Delete did not replicate correctly. Legacy: " . json_encode($legacyCheck) . " New: " . json_encode($newCheck));
    }

    // Clean up
    $db->execute("DELETE FROM erp_departments WHERE organization_id = :org", ['org' => $testOrgId]);
    echo "\nAll tests passed successfully!\n";

} catch (Throwable $e) {
    echo "ERROR during tests: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
