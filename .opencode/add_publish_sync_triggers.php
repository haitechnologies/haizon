<?php
/**
 * Add BEFORE INSERT/UPDATE triggers to keep publish ↔ is_active in sync
 * on all dual-column tables. This allows gradual code migration.
 */

require __DIR__ . '/../config/cli_database.php';

$pdo = new PDO(
    'mysql:host=localhost;dbname=haipulse;charset=utf8mb4',
    'root',
    'hai@30',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Find all tables with both publish and is_active columns
$tables = $pdo->query("
    SELECT c1.TABLE_NAME 
    FROM information_schema.COLUMNS c1
    JOIN information_schema.COLUMNS c2 
      ON c1.TABLE_SCHEMA = c2.TABLE_SCHEMA 
     AND c1.TABLE_NAME = c2.TABLE_NAME
    WHERE c1.TABLE_SCHEMA = 'haipulse' 
      AND c1.COLUMN_NAME = 'publish'
      AND c2.COLUMN_NAME = 'is_active'
")->fetchAll(PDO::FETCH_COLUMN);

echo "Tables with both publish + is_active: " . count($tables) . "\n";

$existing = $pdo->query("
    SELECT TRIGGER_NAME FROM information_schema.TRIGGERS 
    WHERE TRIGGER_SCHEMA = 'haipulse' 
      AND TRIGGER_NAME LIKE 'trg_%_sync_active%'
")->fetchAll(PDO::FETCH_COLUMN);

$existingSet = array_flip($existing);
$created = 0;
$skipped = 0;

foreach ($tables as $table) {
    $insName = "trg_{$table}_sync_active_ins";
    $updName = "trg_{$table}_sync_active_upd";

    if (!isset($existingSet[$insName])) {
        $sql = "
            CREATE TRIGGER `{$insName}` BEFORE INSERT ON `{$table}`
            FOR EACH ROW
            BEGIN
                IF NEW.is_active IS NULL AND NEW.publish IS NOT NULL THEN
                    SET NEW.is_active = NEW.publish;
                END IF;
                IF NEW.publish IS NULL AND NEW.is_active IS NOT NULL THEN
                    SET NEW.publish = NEW.is_active;
                END IF;
            END;
        ";
        try {
            $pdo->exec("DROP TRIGGER IF EXISTS `{$insName}`");
            $pdo->exec($sql);
            $created++;
        } catch (Exception $e) {
            echo "ERROR on {$insName}: " . $e->getMessage() . "\n";
        }
    } else {
        $skipped++;
    }

    if (!isset($existingSet[$updName])) {
        $sql = "
            CREATE TRIGGER `{$updName}` BEFORE UPDATE ON `{$table}`
            FOR EACH ROW
            BEGIN
                IF NEW.is_active = OLD.is_active AND NEW.publish != OLD.publish THEN
                    SET NEW.is_active = NEW.publish;
                END IF;
                IF NEW.publish = OLD.publish AND NEW.is_active != OLD.is_active THEN
                    SET NEW.publish = NEW.is_active;
                END IF;
            END;
        ";
        try {
            $pdo->exec("DROP TRIGGER IF EXISTS `{$updName}`");
            $pdo->exec($sql);
            $created++;
        } catch (Exception $e) {
            echo "ERROR on {$updName}: " . $e->getMessage() . "\n";
        }
    } else {
        $skipped++;
    }
}

echo "Triggers created: {$created}\n";
echo "Triggers already existed: {$skipped}\n";
echo "Done.\n";
