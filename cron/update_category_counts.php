<?php
/**
 * Update Category Company Counts
 * 
 * This script updates the total_companies column in the hai_categories table
 * Run this script periodically (e.g., via cron every 5-10 minutes) to keep counts accurate
 * 
 * Usage:
 * - Via cron: php update_category_counts.php
 * - Via browser: Not recommended (use cron instead)
 */

require_once __DIR__ . '/../config/database.php';

$mysqli = $mysqli ?? $conn ?? null;
if (!($mysqli instanceof mysqli)) {
    echo "Error: Database connection unavailable.\n";
    exit(1);
}

function tableExists(mysqli $conn, string $table): bool {
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Start timing
$startTime = microtime(true);

echo "==================================================\n";
echo "Updating Category Company Counts\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo "==================================================\n\n";

if (!tableExists($mysqli, DB::COMPANIES)) {
    echo "[SKIP] " . DB::COMPANIES . " table not found. Category company counts are decommissioned.\n";
    exit(0);
}

// Step 1: Get company counts per category
echo "Step 1: Counting companies by category...\n";
$countQuery = "
    SELECT 
        primary_category_id,
        COUNT(*) AS company_count
    FROM `" . DB::COMPANIES . "`
    WHERE publish = 1 AND is_active = 1
    GROUP BY primary_category_id
";

$countResult = $mysqli->query($countQuery);
if (!$countResult) {
    die("Error counting companies: " . $mysqli->error . "\n");
}

$categoryCounts = [];
while ($row = $countResult->fetch_assoc()) {
    $categoryCounts[(int)$row['primary_category_id']] = (int)$row['company_count'];
}

echo "Found " . count($categoryCounts) . " categories with companies\n\n";

// Step 2: Get all categories
echo "Step 2: Fetching all categories...\n";
$categoriesQuery = "SELECT id, name, total_companies FROM `" . DB::CATEGORIES . "`";
$categoriesResult = $mysqli->query($categoriesQuery);
if (!$categoriesResult) {
    die("Error fetching categories: " . $mysqli->error . "\n");
}

$totalCategories = $categoriesResult->num_rows;
echo "Found " . $totalCategories . " total categories\n\n";

// Step 3: Update each category
echo "Step 3: Updating category counts...\n";
$updateStmt = $mysqli->prepare("UPDATE `" . DB::CATEGORIES . "` SET total_companies = ?, updated_at = NOW() WHERE id = ?");
if (!$updateStmt) {
    die("Error preparing update statement: " . $mysqli->error . "\n");
}

$updated = 0;
$unchanged = 0;

while ($category = $categoriesResult->fetch_assoc()) {
    $categoryId = (int)$category['id'];
    $currentCount = (int)$category['total_companies'];
    $newCount = $categoryCounts[$categoryId] ?? 0;
    
    if ($currentCount !== $newCount) {
        $updateStmt->bind_param('ii', $newCount, $categoryId);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows > 0) {
            echo "  ✓ Updated '{$category['name']}': {$currentCount} → {$newCount}\n";
            $updated++;
        }
    } else {
        $unchanged++;
    }
}

$updateStmt->close();

echo "\n";
echo "==================================================\n";
echo "Summary:\n";
echo "  - Categories updated: {$updated}\n";
echo "  - Categories unchanged: {$unchanged}\n";
echo "  - Total categories: {$totalCategories}\n";
echo "  - Execution time: " . round((microtime(true) - $startTime) * 1000, 2) . " ms\n";
echo "==================================================\n";

echo "\n✓ Category counts updated successfully!\n";
