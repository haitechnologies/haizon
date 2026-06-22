<?php

declare(strict_types=1);

$root = __DIR__ . '/..';
$excludeDirs = ['vendor', 'node_modules', 'tcpdf', 'assets/plugins', 'fullcalendar', 'assets/scss', 'assets/fonts', 'assets/iconfonts', 'assets/images', 'assets/video', 'assets/switcher', 'images', 'data', 'backups', 'logs', 'scratch', 'tests'];

function scanFiles(string $dir, array $extensions): array
{
    global $excludeDirs;
    $results = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $extensions, true)) continue;
        $relPath = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
        $skip = false;
        foreach ($excludeDirs as $ex) {
            if (strpos($relPath, $ex . '/') === 0) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;
        $results[] = [
            'path' => $relPath,
            'size' => $file->getSize(),
            'lines' => count(file($file->getPathname())),
        ];
    }
    return $results;
}

$phpFiles = scanFiles($root, ['php']);
$jsFiles = scanFiles($root, ['js']);
$cssFiles = scanFiles($root, ['css']);
$mdFiles = scanFiles($root, ['md']);
$jsonFiles = scanFiles($root, ['json']);

function stats(array $files): array
{
    $totalSize = 0;
    $totalLines = 0;
    foreach ($files as $f) {
        $totalSize += $f['size'];
        $totalLines += $f['lines'];
    }
    return ['count' => count($files), 'size_kb' => round($totalSize / 1024, 1), 'lines' => $totalLines];
}

$phpStats = stats($phpFiles);
$jsStats = stats($jsFiles);
$cssStats = stats($cssFiles);
$mdStats = stats($mdFiles);
$jsonStats = stats($jsonFiles);

echo "=== FILE TYPE SUMMARY ===" . PHP_EOL;
echo str_pad('Type', 10) . str_pad('Files', 8) . str_pad('Size (KB)', 12) . 'Lines' . PHP_EOL;
echo str_pad('.php', 10) . str_pad($phpStats['count'], 8) . str_pad($phpStats['size_kb'], 12) . $phpStats['lines'] . PHP_EOL;
echo str_pad('.js', 10) . str_pad($jsStats['count'], 8) . str_pad($jsStats['size_kb'], 12) . $jsStats['lines'] . PHP_EOL;
echo str_pad('.css', 10) . str_pad($cssStats['count'], 8) . str_pad($cssStats['size_kb'], 12) . $cssStats['lines'] . PHP_EOL;
echo str_pad('.md', 10) . str_pad($mdStats['count'], 8) . str_pad($mdStats['size_kb'], 12) . $mdStats['lines'] . PHP_EOL;
echo str_pad('.json', 10) . str_pad($jsonStats['count'], 8) . str_pad($jsonStats['size_kb'], 12) . $jsonStats['lines'] . PHP_EOL;
echo PHP_EOL;

echo "=== TOP 30 LARGEST PHP FILES ===" . PHP_EOL;
usort($phpFiles, fn($a, $b) => $b['size'] <=> $a['size']);
for ($i = 0; $i < 30; $i++) {
    $f = $phpFiles[$i];
    echo str_pad(round($f['size'] / 1024, 1) . ' KB', 12) . str_pad($f['lines'] . ' lines', 12) . $f['path'] . PHP_EOL;
}
echo PHP_EOL;

echo "=== TOP 15 LARGEST JS FILES ===" . PHP_EOL;
usort($jsFiles, fn($a, $b) => $b['size'] <=> $a['size']);
for ($i = 0; $i < min(15, count($jsFiles)); $i++) {
    $f = $jsFiles[$i];
    echo str_pad(round($f['size'] / 1024, 1) . ' KB', 12) . str_pad($f['lines'] . ' lines', 12) . $f['path'] . PHP_EOL;
}
echo PHP_EOL;

echo "=== TOP 15 LARGEST CSS FILES ===" . PHP_EOL;
usort($cssFiles, fn($a, $b) => $b['size'] <=> $a['size']);
for ($i = 0; $i < min(15, count($cssFiles)); $i++) {
    $f = $cssFiles[$i];
    echo str_pad(round($f['size'] / 1024, 1) . ' KB', 12) . str_pad($f['lines'] . ' lines', 12) . $f['path'] . PHP_EOL;
}
echo PHP_EOL;

echo "=== MARKDOWN FILES ===" . PHP_EOL;
usort($mdFiles, fn($a, $b) => $b['size'] <=> $a['size']);
foreach ($mdFiles as $f) {
    echo str_pad(round($f['size'] / 1024, 1) . ' KB', 12) . str_pad($f['lines'] . ' lines', 12) . $f['path'] . PHP_EOL;
}
echo PHP_EOL;

echo "=== JSON FILES ===" . PHP_EOL;
usort($jsonFiles, fn($a, $b) => $b['size'] <=> $a['size']);
foreach ($jsonFiles as $f) {
    echo str_pad(round($f['size'] / 1024, 1) . ' KB', 12) . str_pad($f['lines'] . ' lines', 12) . $f['path'] . PHP_EOL;
}
echo PHP_EOL;

// Check for dashboard listing page patterns
$listingFiles = glob($root . '/dashboard/listing_*.php');
$avgLines = 0;
$maxLines = 0;
$maxFile = '';
foreach ($listingFiles as $lf) {
    $lines = count(file($lf));
    $avgLines += $lines;
    if ($lines > $maxLines) {
        $maxLines = $lines;
        $maxFile = basename($lf);
    }
}
$avgLines = round($avgLines / count($listingFiles));
echo "=== LISTING PAGE STATS ===" . PHP_EOL;
echo "Total listing files: " . count($listingFiles) . PHP_EOL;
echo "Average lines per listing: $avgLines" . PHP_EOL;
echo "Largest listing: $maxFile ($maxLines lines)" . PHP_EOL;
echo PHP_EOL;

// Check for repetitive patterns in listing files
$patternCounts = [];
foreach ($listingFiles as $lf) {
    $content = file_get_contents($lf);
    $lines = count(file($lf));
    if ($lines > 100) {
        $patternCounts[basename($lf)] = $lines;
    }
}
arsort($patternCounts);
echo "=== LISTING FILES OVER 100 LINES ===" . PHP_EOL;
foreach ($patternCounts as $name => $lines) {
    echo str_pad($lines . ' lines', 12) . $name . PHP_EOL;
}
echo PHP_EOL;

// Check for hardcoded strings / inline HTML in listing files
echo "=== INLINE HTML/CSS IN LISTING FILES (sample check) ===" . PHP_EOL;
$inlineCount = 0;
foreach ($listingFiles as $lf) {
    $content = file_get_contents($lf);
    $matches = [];
    preg_match_all('/style=["\'][^"\']+["\']/', $content, $matches);
    if (!empty($matches[0])) {
        $inlineCount++;
        if ($inlineCount <= 5) {
            echo basename($lf) . ': ' . count($matches[0]) . ' inline styles' . PHP_EOL;
        }
    }
}
echo "Total listing files with inline styles: $inlineCount" . PHP_EOL;
echo PHP_EOL;

// Check page_help_config.php size
$helpConfig = $root . '/config/page_help_config.php';
echo "=== PAGE HELP CONFIG ===" . PHP_EOL;
echo "Size: " . round(filesize($helpConfig) / 1024, 1) . " KB" . PHP_EOL;
echo "Lines: " . count(file($helpConfig)) . PHP_EOL;
echo PHP_EOL;

// Check for DataTable classes size
$dtDir = $root . '/src/DataTable';
$dtFiles = glob($dtDir . '/*.php');
$dtTotal = 0;
$dtMax = 0;
$dtMaxFile = '';
foreach ($dtFiles as $df) {
    $size = filesize($df);
    $dtTotal += $size;
    if ($size > $dtMax) {
        $dtMax = $size;
        $dtMaxFile = basename($df);
    }
}
echo "=== DATATABLE CLASSES ===" . PHP_EOL;
echo "Total DataTable files: " . count($dtFiles) . PHP_EOL;
echo "Total size: " . round($dtTotal / 1024, 1) . " KB" . PHP_EOL;
echo "Largest: $dtMaxFile (" . round($dtMax / 1024, 1) . " KB)" . PHP_EOL;
echo PHP_EOL;

// Check Model classes
$modelDir = $root . '/src/Model';
$modelFiles = glob($modelDir . '/*.php');
$modelTotal = 0;
foreach ($modelFiles as $mf) {
    $modelTotal += filesize($mf);
}
echo "=== MODEL CLASSES ===" . PHP_EOL;
echo "Total Model files: " . count($modelFiles) . PHP_EOL;
echo "Total size: " . round($modelTotal / 1024, 1) . " KB" . PHP_EOL;
echo PHP_EOL;

// Grand total
$totalSize = $phpStats['size_kb'] + $jsStats['size_kb'] + $cssStats['size_kb'] + $mdStats['size_kb'] + $jsonStats['size_kb'];
$totalLines = $phpStats['lines'] + $jsStats['lines'] + $cssStats['lines'] + $mdStats['lines'] + $jsonStats['lines'];
echo "=== GRAND TOTAL (excl. vendor/assets/plugins) ===" . PHP_EOL;
echo "Total size: " . round($totalSize, 1) . " KB (" . round($totalSize / 1024, 1) . " MB)" . PHP_EOL;
echo "Total lines: " . number_format($totalLines) . PHP_EOL;
echo "Total files: " . ($phpStats['count'] + $jsStats['count'] + $cssStats['count'] + $mdStats['count'] + $jsonStats['count']) . PHP_EOL;
