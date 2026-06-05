<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Core\Database;
use App\Model\LeaveType;
use App\Model\LeaveRequest;
use App\Repository\LeaveTypeRepository;
use App\Repository\LeaveRequestRepository;
use App\Repository\UserRepository;
use App\Service\LeaveTypeService;
use App\Service\LeaveRequestService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

echo "==================================================\n";
echo "PSR-4 Leave Management Module Integration Tests\n";
echo "==================================================\n\n";

try {
    $db = new Database();
    $typeRepo = new LeaveTypeRepository($db);
    $requestRepo = new LeaveRequestRepository($db);
    $userRepo = new UserRepository($db);

    $typeService = new LeaveTypeService($typeRepo, $requestRepo);
    $requestService = new LeaveRequestService($requestRepo, $typeRepo, $userRepo);

    $org1 = 881;
    $org2 = 882;
    $employeeId = 3; // Use a valid employee ID from seed data if possible, or create one.
    // Let's check if user with ID 3 exists. If not, we will get/create one.
    $user = $userRepo->find(3);
    if ($user === null) {
        // Find any existing user
        $allUsers = $db->fetchAll("SELECT id FROM erp_users LIMIT 1");
        if (!empty($allUsers)) {
            $employeeId = (int)$allUsers[0]['id'];
        } else {
            // Create user
            $db->execute("INSERT INTO erp_users (email, full_name, password, role_id) VALUES ('test_leave@haipulse.com', 'Leave Tester', 'password', 3)");
            $employeeId = (int)$db->getLastInsertId();
        }
    }

    // Clean up test records
    $db->execute("DELETE FROM erp_leave_requests WHERE organization_id IN (:org1, :org2)", ['org1' => $org1, 'org2' => $org2]);
    $db->execute("DELETE FROM erp_leave_types WHERE organization_id IN (:org1, :org2)", ['org1' => $org1, 'org2' => $org2]);

    // Test 1: Create a leave type via Service
    echo "[TEST 1] Creating leave type via Service... ";
    $type1Name = "PSR Annual Leave";
    $type1 = $typeService->create($type1Name, 30, true, $org1);
    if ($type1->id !== null && $type1->leaveType === $type1Name && $type1->maxPerYear === 30 && $type1->paid === true && $type1->organizationId === $org1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Failed to create leave type correctly.");
    }

    // Test 2: Duplicate leave type validation
    echo "[TEST 2] Testing duplicate leave type validation... ";
    try {
        $typeService->create($type1Name, 15, true, $org1);
        echo "✗ FAIL (Allowed duplicate)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['leave_type']) && strpos($errors['leave_type'], 'already exists') !== false) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL (Wrong validation message: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }

    // Test 3: Updating a leave type
    echo "[TEST 3] Updating leave type... ";
    $type1Updated = $typeService->update((int)$type1->id, "PSR Paid Annual Leave", 35, true, $org1);
    if ($type1Updated->leaveType === "PSR Paid Annual Leave" && $type1Updated->maxPerYear === 35) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Failed to update leave type.");
    }

    // Test 4: Create a leave request via Service
    echo "[TEST 4] Creating leave request via Service... ";
    $reqData = [
        'employee_id' => $employeeId,
        'leave_type_id' => $type1->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-05',
        'total_days' => 5.0,
        'reason' => 'Summer vacation',
        'status' => 'pending',
    ];
    $req1 = $requestService->create($reqData, $org1);
    if ($req1->id !== null && $req1->employeeId === $employeeId && $req1->totalDays === 5.0 && $req1->organizationId === $org1) {
        echo "✓ PASS\n";
    } else {
        throw new Exception("Failed to create leave request correctly.");
    }

    // Test 5: Verify validation of dates and total days
    echo "[TEST 5] Testing leave request validations... ";
    // Chronological date validation
    try {
        $badData = $reqData;
        $badData['start_date'] = '2026-07-05';
        $badData['end_date'] = '2026-07-01';
        $requestService->create($badData, $org1);
        echo "✗ FAIL (Allowed end date before start date)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['end_date']) && strpos($errors['end_date'], 'cannot be before') !== false) {
            // Good
        } else {
            echo "✗ FAIL (Wrong exception message: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }
    // Non-existent leave type for organization
    try {
        $badData = $reqData;
        $badData['leave_type_id'] = 99999;
        $requestService->create($badData, $org1);
        echo "✗ FAIL (Allowed non-existent leave type)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['leave_type_id'])) {
            // Good
        } else {
            echo "✗ FAIL (Missing type validation: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }
    echo "✓ PASS\n";

    // Test 6: Tenant isolation validation
    echo "[TEST 6] Testing tenant isolation constraints... ";
    // Org2 trying to access Org1 leave type
    try {
        $typeService->getById((int)$type1->id, $org2);
        echo "✗ FAIL (Allowed cross-tenant read of leave type)\n";
        exit(1);
    } catch (NotFoundException $e) {
        // Good
    }
    // Org2 trying to access Org1 leave request
    try {
        $requestService->getById((int)$req1->id, $org2);
        echo "✗ FAIL (Allowed cross-tenant read of leave request)\n";
        exit(1);
    } catch (NotFoundException $e) {
        // Good
    }
    // Org2 trying to update Org1 leave request
    try {
        $requestService->update((int)$req1->id, $reqData, $org2);
        echo "✗ FAIL (Allowed cross-tenant update of leave request)\n";
        exit(1);
    } catch (NotFoundException $e) {
        // Good
    }
    echo "✓ PASS\n";

    // Test 7: Deleting leave type check constraint (in-use type cannot be deleted)
    echo "[TEST 7] Testing deletion block on in-use leave types... ";
    try {
        $typeService->delete((int)$type1->id, $org1);
        echo "✗ FAIL (Allowed deleting in-use leave type)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['leave_type']) && strpos($errors['leave_type'], 'associated') !== false) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL (Wrong error on delete: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }

    // Test 8: Deleting leave request and then leave type
    echo "[TEST 8] Testing request deletion followed by type deletion... ";
    $requestService->delete((int)$req1->id, $org1);
    
    // Check if request is deleted
    try {
        $requestService->getById((int)$req1->id, $org1);
        echo "✗ FAIL (Request still exists after delete)\n";
        exit(1);
    } catch (NotFoundException $e) {
        // Good
    }

    // Delete leave type now that request is gone
    $typeService->delete((int)$type1->id, $org1);
    try {
        $typeService->getById((int)$type1->id, $org1);
        echo "✗ FAIL (Leave type still exists after delete)\n";
        exit(1);
    } catch (NotFoundException $e) {
        // Good
    }
    echo "✓ PASS\n";

    // Clean up database
    $db->execute("DELETE FROM erp_leave_requests WHERE organization_id IN (:org1, :org2)", ['org1' => $org1, 'org2' => $org2]);
    $db->execute("DELETE FROM erp_leave_types WHERE organization_id IN (:org1, :org2)", ['org1' => $org1, 'org2' => $org2]);

    echo "\nAll Leave Management integration tests passed successfully!\n";

} catch (Throwable $e) {
    echo "ERROR during tests: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
