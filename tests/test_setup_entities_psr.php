<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Model\SetupGroup;
use App\Service\SetupGroupService;
use App\Service\SetupStatusService;
use App\Service\SetupSourceService;
use App\Service\SetupTagService;
use App\Service\UnitService;
use App\Repository\SetupGroupRepository;
use App\Repository\SetupStatusRepository;
use App\Repository\SetupSourceRepository;
use App\Repository\SetupTagRepository;
use App\Repository\UnitRepository;
use App\Exception\ValidationException;

global $db;

echo "==================================================\n";
echo "PSR-4 Setup Entities Integration Tests\n";
echo "==================================================\n\n";

$testUserId = 101;
$pass = 0;
$fail = 0;

function assert_true(bool $condition, string $label): void {
    global $pass, $fail;
    if ($condition) { $pass++; echo "  ✓ $label\n"; }
    else { $fail++; echo "  ✗ $label\n"; }
}

try {
    // ========================================================================
    // SETUP GROUP
    // ========================================================================
    echo "--- SetupGroupService ---\n";
    $groupRepo = new SetupGroupRepository($db);
    $groupService = new SetupGroupService($groupRepo);

    // Clean
    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v", ['v' => 'Test Group%']);
    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v", ['v' => 'Group Update%']);

    $testGroupName = 'Test Group ' . uniqid();
    $groupId = $groupService->create(['group_name' => $testGroupName, 'description' => 'Test description'], $testUserId);
    assert_true($groupId > 0, 'SetupGroup create returns positive ID');

    $fetchedGroup = $groupService->getById($groupId);
    assert_true($fetchedGroup !== null && $fetchedGroup->groupName === $testGroupName, 'SetupGroup getById matches name');

    $updated = $groupService->update($groupId, ['group_name' => $testGroupName . ' Updated'], $testUserId);
    assert_true($updated === true, 'SetupGroup update returns true');

    $refetchedGroup = $groupService->getById($groupId);
    assert_true($refetchedGroup !== null && $refetchedGroup->groupName === $testGroupName . ' Updated', 'SetupGroup update persists');

    try {
        $groupService->create(['group_name' => ''], $testUserId);
        assert_true(false, 'SetupGroup empty name should throw');
    } catch (ValidationException $e) {
        assert_true(true, 'SetupGroup empty name validation');
    }

    try {
        $groupService->create(['group_name' => $testGroupName . ' Updated'], $testUserId);
        assert_true(false, 'SetupGroup duplicate name should throw');
    } catch (ValidationException $e) {
        assert_true(true, 'SetupGroup duplicate name validation');
    }

    $list = $groupService->list();
    assert_true(count($list) > 0, 'SetupGroup list returns items');

    $deleted = $groupService->delete($groupId);
    assert_true($deleted === true, 'SetupGroup delete returns true');

    $afterDelete = $groupService->getById($groupId);
    assert_true($afterDelete === null, 'SetupGroup getById returns null after delete');

    $deleteNonexistent = $groupService->delete(999999);
    assert_true($deleteNonexistent === false, 'SetupGroup delete nonexistent returns false');

    // Clean
    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v", ['v' => 'Test Group%']);
    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v", ['v' => 'Group Update%']);

    echo "\n";

    // ========================================================================
    // SETUP STATUS
    // ========================================================================
    echo "--- SetupStatusService ---\n";
    $statusRepo = new SetupStatusRepository($db);
    $statusService = new SetupStatusService($statusRepo);

    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v AND type LIKE :t", ['v' => 'Test Status%', 't' => 'lead_status']);

    $statusName = 'Test Status ' . uniqid();
    $statusId = $statusService->create(['status_name' => $statusName, 'status_type' => 'leads'], $testUserId);
    assert_true($statusId > 0, 'SetupStatus create returns positive ID');

    $fetchedStatus = $statusService->getById($statusId);
    assert_true($fetchedStatus !== null && $fetchedStatus->statusName === $statusName, 'SetupStatus getById matches name');
    assert_true($fetchedStatus->statusType === 'lead_status', 'SetupStatus type mapped correctly');

    $updated = $statusService->update($statusId, ['status_name' => $statusName . ' Updated', 'status_type' => 'vendors'], $testUserId);
    assert_true($updated === true, 'SetupStatus update returns true');

    $refetchedStatus = $statusService->getById($statusId);
    assert_true($refetchedStatus !== null && $refetchedStatus->statusName === $statusName . ' Updated', 'SetupStatus update persists');
    assert_true($refetchedStatus->statusType === 'vendor_status', 'SetupStatus type updated correctly');

    try {
        $statusService->create(['status_name' => '', 'status_type' => 'leads'], $testUserId);
        assert_true(false, 'SetupStatus empty name should throw');
    } catch (ValidationException $e) {
        assert_true(true, 'SetupStatus empty name validation');
    }

    try {
        $statusService->create(['status_name' => 'Valid', 'status_type' => ''], $testUserId);
        assert_true(false, 'SetupStatus empty type should throw');
    } catch (ValidationException $e) {
        assert_true(true, 'SetupStatus empty type validation');
    }

    $listByType = $statusService->list('lead_status');
    assert_true(is_array($listByType), 'SetupStatus list by type returns array');

    $deleted = $statusService->delete($statusId);
    assert_true($deleted === true, 'SetupStatus delete returns true');

    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v AND type LIKE :t", ['v' => 'Test Status%', 't' => 'lead_status']);

    echo "\n";

    // ========================================================================
    // SETUP SOURCE
    // ========================================================================
    echo "--- SetupSourceService ---\n";
    $sourceRepo = new SetupSourceRepository($db);
    $sourceService = new SetupSourceService($sourceRepo);

    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v AND type LIKE :t", ['v' => 'Test Source%', 't' => 'lead_source']);

    $sourceName = 'Test Source ' . uniqid();
    $sourceId = $sourceService->create(['source_name' => $sourceName, 'source_type' => 'leads'], $testUserId);
    assert_true($sourceId > 0, 'SetupSource create returns positive ID');

    $fetchedSource = $sourceService->getById($sourceId);
    assert_true($fetchedSource !== null && $fetchedSource->sourceName === $sourceName, 'SetupSource getById matches name');
    assert_true($fetchedSource->sourceType === 'lead_source', 'SetupSource type mapped correctly');

    $updated = $sourceService->update($sourceId, ['source_name' => $sourceName . ' Updated', 'source_type' => 'customers'], $testUserId);
    assert_true($updated === true, 'SetupSource update returns true');
    assert_true($sourceService->getById($sourceId)->sourceType === 'customer_source', 'SetupSource type updated correctly');

    try {
        $sourceService->create(['source_name' => $sourceName . ' Updated', 'source_type' => 'customers'], $testUserId);
        assert_true(false, 'SetupSource duplicate should throw');
    } catch (ValidationException $e) {
        assert_true(true, 'SetupSource duplicate validation');
    }

    $deleted = $sourceService->delete($sourceId);
    assert_true($deleted === true, 'SetupSource delete returns true');

    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v AND type LIKE :t", ['v' => 'Test Source%', 't' => 'lead_source']);

    echo "\n";

    // ========================================================================
    // SETUP TAG
    // ========================================================================
    echo "--- SetupTagService ---\n";
    $tagRepo = new SetupTagRepository($db);
    $tagService = new SetupTagService($tagRepo);

    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v AND type LIKE :t", ['v' => 'Test Tag%', 't' => 'lead_tag']);

    $tagName = 'Test Tag ' . uniqid();
    $tagId = $tagService->create(['tag_name' => $tagName, 'tag_type' => 'leads'], $testUserId);
    assert_true($tagId > 0, 'SetupTag create returns positive ID');

    $fetchedTag = $tagService->getById($tagId);
    assert_true($fetchedTag !== null && $fetchedTag->tagName === $tagName, 'SetupTag getById matches name');
    assert_true($fetchedTag->tagType === 'lead_tag', 'SetupTag type mapped correctly');

    $updated = $tagService->update($tagId, ['tag_name' => $tagName . ' Updated', 'tag_type' => 'jobs'], $testUserId);
    assert_true($updated === true, 'SetupTag update returns true');
    assert_true($tagService->getById($tagId)->tagType === 'job_tag', 'SetupTag type updated correctly');

    $deleted = $tagService->delete($tagId);
    assert_true($deleted === true, 'SetupTag delete returns true');

    $db->execute("DELETE FROM erp_taxonomies WHERE value LIKE :v AND type LIKE :t", ['v' => 'Test Tag%', 't' => 'lead_tag']);

    echo "\n";

    // ========================================================================
    // UNIT
    // ========================================================================
    echo "--- UnitService ---\n";
    $unitRepo = new UnitRepository($db);
    $unitService = new UnitService($unitRepo);

    $db->execute("DELETE FROM erp_units WHERE unit LIKE :v", ['v' => 'Test Unit%']);

    $unitName = 'Test Unit ' . uniqid();
    $unitId = $unitService->create(['unit_name' => $unitName], $testUserId);
    assert_true($unitId > 0, 'Unit create returns positive ID');

    $fetchedUnit = $unitService->getById($unitId);
    assert_true($fetchedUnit !== null && $fetchedUnit->unitName === $unitName, 'Unit getById matches name');

    $updated = $unitService->update($unitId, ['unit_name' => $unitName . ' Updated'], $testUserId);
    assert_true($updated === true, 'Unit update returns true');

    $refetchedUnit = $unitService->getById($unitId);
    assert_true($refetchedUnit !== null && $refetchedUnit->unitName === $unitName . ' Updated', 'Unit update persists');

    try {
        $unitService->create(['unit_name' => ''], $testUserId);
        assert_true(false, 'Unit empty name should throw');
    } catch (ValidationException $e) {
        assert_true(true, 'Unit empty name validation');
    }

    $list = $unitService->list();
    assert_true(is_array($list), 'Unit list returns array');

    $deleted = $unitService->delete($unitId);
    assert_true($deleted === true, 'Unit delete returns true');

    $afterDelete = $unitService->getById($unitId);
    assert_true($afterDelete === null, 'Unit getById returns null after delete');

    $deleteNonexistent = $unitService->delete(999999);
    assert_true($deleteNonexistent === false, 'Unit delete nonexistent returns false');

    $db->execute("DELETE FROM erp_units WHERE unit LIKE :v", ['v' => 'Test Unit%']);

    echo "\n";

    // ========================================================================
    // SUMMARY
    // ========================================================================
    echo "==================================================\n";
    echo "Results: $pass passed, $fail failed\n";
    echo "==================================================\n";

    if ($fail > 0) {
        exit(1);
    }
    echo "All setup entity tests passed!\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
