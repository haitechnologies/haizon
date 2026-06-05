<?php
/**
 * Listing Page Style Validator
 *
 * ⚠️ DEPRECATED: Use validate_listing_pages.php instead (unified validator)
 * 
 * This validator only checks style governance.
 * The unified validator checks both DataTable config AND style governance.
 *
 * Purpose:
 * - Ensure listing pages do not keep inline <style> blocks.
 * - Ensure DataTables pagination/footer CSS is not locally overridden
 *   in listing pages (must stay centralized in assets/css/datatables-unified.css).
 *
 * Usage:
 *   php dashboard/validate_listing_styles.php
 *
 * See: dashboard/VALIDATOR_README.md for unified validator documentation
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$dashboardDir = $root . DIRECTORY_SEPARATOR . 'dashboard';
$pattern = $dashboardDir . DIRECTORY_SEPARATOR . 'listing_*.php';

$files = glob($pattern) ?: [];
$violations = [];
$checked = 0;

$forbiddenCssPatterns = [
    '/\.dataTables_paginate\b/i',
    '/\.dt-footer\b/i',
    '/\.dt-foot-left\b/i',
    '/\.dt-foot-right\b/i',
    '/\.paginate_button\b/i',
];

foreach ($files as $filePath) {
    $base = basename($filePath);

    // Skip examples/backups from strict checks.
    if (stripos($base, 'SECURE_EXAMPLE') !== false) {
        continue;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        $violations[] = [
            'file' => $base,
            'issue' => 'Unable to read file',
        ];
        continue;
    }

    $checked++;

    if (preg_match('/<style\b[^>]*>/i', $content)) {
        $violations[] = [
            'file' => $base,
            'issue' => 'Inline <style> block found (move to assets/css/dashboard-listing-pages.css)',
        ];
    }

    if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $content, $matches)) {
        foreach ($matches[1] as $cssBlock) {
            foreach ($forbiddenCssPatterns as $cssPattern) {
                if (preg_match($cssPattern, $cssBlock)) {
                    $violations[] = [
                        'file' => $base,
                        'issue' => 'Forbidden local DataTables footer/pagination CSS selector found in inline style',
                    ];
                    break 2;
                }
            }
        }
    }
}

echo "Listing Style Validation Report\n";
echo str_repeat('=', 32) . "\n";
echo 'Checked files: ' . $checked . "\n";

echo '\n';

if (empty($violations)) {
    echo "PASS: No inline style blocks or forbidden local DataTables pagination/footer CSS found in listing pages.\n";
    exit(0);
}

echo 'FAIL: Found ' . count($violations) . " issue(s).\n\n";

foreach ($violations as $idx => $item) {
    echo ($idx + 1) . '. ' . $item['file'] . "\n";
    echo '   - ' . $item['issue'] . "\n";
}

exit(1);
