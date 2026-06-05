<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Core\Database;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Exception\ValidationException;
use App\Exception\NotFoundException;

echo "==================================================\n";
echo "PSR User Module Integration Tests\n";
echo "==================================================\n\n";

try {
    $db = new Database();
    $userRepo = new UserRepository($db);
    $userService = new UserService($userRepo);

    $testCreatedBy = 12345;

    // Clean up any old test user except user ID 1
    $db->execute("DELETE FROM `erp_users` WHERE email = :email", ['email' => 'integration.user@test.com']);
    $db->execute("DELETE FROM `erp_users` WHERE email = :email", ['email' => 'integration.user.updated@test.com']);

    // Test 1: Create User
    echo "[TEST 1] Creating user via UserService... ";
    $userData = [
        'email' => 'integration.user@test.com',
        'password' => 'secure123',
        'role_id' => 3,
        'full_name' => 'Integration Test Employee',
        'contact1' => '+971501234567',
        'dob' => '15-08-1990',
        'can_access_system' => true,
        'is_active' => true
    ];
    $user = $userService->create($userData, $testCreatedBy);
    if ($user->id !== null && $user->email === 'integration.user@test.com' && $user->fullName === 'Integration Test Employee') {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to create user correctly.");
    }

    // Test 2: Validation of Mandatory Fields
    echo "[TEST 2] Verifying mandatory field validations... ";
    try {
        $userService->create([
            'email' => '',
            'password' => '',
            'role_id' => 0,
            'full_name' => '',
            'contact1' => ''
        ], $testCreatedBy);
        echo "✗ FAIL (Allowed empty mandatory fields)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['role_id']) && isset($errors['full_name']) && isset($errors['email']) && isset($errors['password']) && isset($errors['contact1'])) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL (Unexpected validation errors: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }

    // Test 3: Validation of Duplicate Email
    echo "[TEST 3] Verifying duplicate email validation... ";
    try {
        $userService->create($userData, $testCreatedBy);
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

    // Test 4: Fetch User by ID
    echo "[TEST 4] Fetching user via UserService... ";
    $fetched = $userService->getById((int)$user->id);
    if ($fetched->id === $user->id && $fetched->fullName === $user->fullName) {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to fetch user.");
    }

    // Test 5: Update User
    echo "[TEST 5] Updating user via UserService... ";
    $updateData = [
        'email' => 'integration.user.updated@test.com',
        'role_id' => 3,
        'full_name' => 'Integration Test Employee (Updated)',
        'contact1' => '+971507654321',
        'dob' => '20-10-1992',
        'can_access_system' => false,
        'is_active' => false
    ];
    $updated = $userService->update((int)$user->id, $updateData);
    if ($updated->fullName === 'Integration Test Employee (Updated)' && $updated->email === 'integration.user.updated@test.com') {
        echo "✓ PASS\n";
    } else {
        throw new \Exception("Failed to update user.");
    }

    // Test 6: Deletion Restrictions
    echo "[TEST 6] Verifying super admin deletion restriction... ";
    try {
        $userService->delete(1);
        echo "✗ FAIL (Allowed deleting system admin user id 1)\n";
        exit(1);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        if (isset($errors['user']) && strpos($errors['user'], 'System Admin user cannot be deleted') !== false) {
            echo "✓ PASS\n";
        } else {
            echo "✗ FAIL (Unexpected validation message: " . json_encode($errors) . ")\n";
            exit(1);
        }
    }

    // Test 7: Delete User
    echo "[TEST 7] Deleting user via UserService... ";
    $userService->delete((int)$user->id);
    try {
        $userService->getById((int)$user->id);
        echo "✗ FAIL (User still exists after delete)\n";
        exit(1);
    } catch (NotFoundException $e) {
        echo "✓ PASS\n";
    }

    echo "\nAll User integration tests passed successfully!\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
