<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Core\Database;
use App\Model\Designation;
use App\Repository\DesignationRepository;
use App\Service\DesignationService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

echo "==================================================\n";
echo "PSR-4 Designation Architecture Integration Tests\n";
echo "==================================================\n\n";

try {
    $db = new Database();
    $desgRepo = new DesignationRepository($db);
    $desgService = new DesignationService($desgRepo);

    $testOrgId = 999;
    $testUserId = 101;

    // Clean any prior leftovers
    $db->execute("DELETE FROM erp_designations WHERE organization_id = :org", ['org' => $testOrgId]);

    // Test 1: Create a designation via Service
    echo "[TEST 1] Creating designation via Service... ";
    $name1 = "PSR Test Desg A";
    $desg1 = $desgService->create($name1, $testOrgId, $testUserId);
    if ($desg1->id !== null && $desg1->designation === $name1 && $desg1->organizationId === $testOrgId) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Failed to create designation correctly.");
    }

    // Test 2: Verify it exists in db
    echo "[TEST 2] Verifying record exists in database... ";
    $row = $db->fetchOne("SELECT * FROM erp_designations WHERE id = :id", ['id' => $desg1->id]);
    if ($row && $row['designation'] === $name1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Record not found in erp_designations.");
    }

    // Test 3: Test duplicate validation exception
    echo "[TEST 3] Testing duplicate designation validation... ";
    try {
        $desgService->create($name1, $testOrgId, $testUserId);
        echo "✗ FAIL (Allowed duplicate)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['designation']) && strpos($errors['designation'], 'already exists') !== false) {
            echo "✓ PASS (Caught expected ValidationException)\n";
        } else {
            echo "✗ FAIL (Wrong exception message: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }

    // Test 4: Update via Service
    echo "[TEST 4] Testing update via Service... ";
    $updatedName1 = "PSR Test Desg A (Updated)";
    $desgService->update((int)$desg1->id, $updatedName1, true);

    $updatedRow = $db->fetchOne("SELECT designation FROM erp_designations WHERE id = :id", ['id' => $desg1->id]);
    if ($updatedRow && $updatedRow['designation'] === $updatedName1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Update did not save correctly.");
    }

    // Test 5: Delete via Service
    echo "[TEST 5] Testing delete via Service... ";
    $desgService->delete((int)$desg1->id);

    $check = $db->fetchOne("SELECT id FROM erp_designations WHERE id = :id", ['id' => $desg1->id]);
    if (!$check) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Delete did not remove record.");
    }

    // Clean up
    $db->execute("DELETE FROM erp_designations WHERE organization_id = :org", ['org' => $testOrgId]);
    echo "\nAll designation tests passed successfully!\n";

} catch (Throwable $e) {
    echo "ERROR during tests: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
