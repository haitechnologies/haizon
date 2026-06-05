<?php
/**
 * HS Codes Sitemap Generator
 * Route: /sitemap-hs-codes.xml
 * 
 * Generates XML sitemap for all 13,449 HS code pages
 * Helps Google discover and index trade intelligence pages
 */

// Disable error display for clean XML output
ini_set('display_errors', 0);
error_reporting(0);

// Set XML content type
header('Content-Type: application/xml; charset=utf-8');

// Get base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$basePath = !empty($GLOBALS['basePath']) ? $GLOBALS['basePath'] : (dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '');
$baseUrl = $protocol . $host . $basePath;

// Load database
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../classes/DB.php';

$sitemapRoot = trim(getSystemSetting('sitemap_root', ''));
$seoCanonicalUrl = trim(getSystemSetting('seo_canonical_url', ''));
if ($sitemapRoot !== '') {
    $baseUrl = rtrim($sitemapRoot, '/');
} elseif ($seoCanonicalUrl !== '') {
    $baseUrl = rtrim($seoCanonicalUrl, '/');
}

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    
    <!-- Trade Portal Landing Page -->
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/trade'); ?></loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    
    <!-- HS Codes Browser Page -->
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/trade/hs-codes'); ?></loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
    <?php foreach ([2, 4, 6, 8, 10] as $level): ?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/trade/hs-codes/level/' . $level); ?></loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority><?php echo $level >= 8 ? '0.7' : '0.6'; ?></priority>
    </url>
    <?php endforeach; ?>
    
    <?php
    // Get HS codes from database
    try {
        $query = "SELECT code, created_at, level FROM `" . DB::HS_CODES . "` WHERE is_active = 1 ORDER BY code ASC";
        $result = $mysqli->query($query);
        
        // Debug logging
        error_log("Sitemap query executed. Result type: " . gettype($result));
        if (!$result) {
            error_log("Sitemap query FAILED: " . $mysqli->error);
        } else {
            error_log("Sitemap query SUCCESS. Row count: " . $result->num_rows);
        }
        
        if ($result) {
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $count++;
                $lastmod = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : date('Y-m-d');
                
                // Priority based on level
                $level = (int)$row['level'];
                if ($level == 10) {
                    $priority = '0.7';
                } elseif ($level == 8) {
                    $priority = '0.6';
                } elseif ($level == 6) {
                    $priority = '0.5';
                } elseif ($level == 4) {
                    $priority = '0.4';
                } elseif ($level == 2) {
                    $priority = '0.3';
                } else {
                    $priority = '0.5';
                }
                
                echo "    <url>\n";
                echo "        <loc>" . htmlspecialchars($baseUrl . '/trade/hs-code/' . urlencode($row['code'])) . "</loc>\n";
                echo "        <lastmod>" . $lastmod . "</lastmod>\n";
                echo "        <changefreq>monthly</changefreq>\n";
                echo "        <priority>" . $priority . "</priority>\n";
                echo "    </url>\n";
            }
            
            error_log("HS Codes sitemap generated: {$count} URLs");
        }
    } catch (Exception $e) {
        error_log("HS Codes sitemap error: " . $e->getMessage());
    }
    ?>
    
</urlset>
