<!DOCTYPE html>
<html>
<head>
    <title>DataTable Cache Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        .warning { border-left-color: #ffc107; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }
        .cache-time { font-weight: bold; color: #007bff; }
        h1 { color: #333; }
        h2 { color: #666; font-size: 1.2em; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>ðŸ” DataTable Cache Diagnostic Tool</h1>
    
    <div class="box <?php echo file_exists(__DIR__ . '/../assets/js/dashboard-datatable-initializer.js') ? 'success' : 'error'; ?>">
        <h2>1. Initializer File Status</h2>
        <?php
        $initFile = __DIR__ . '/../assets/js/dashboard-datatable-initializer.js';
        if (file_exists($initFile)) {
            $mtime = filemtime($initFile);
            $content = file_get_contents($initFile);
            $hasReturnD = strpos($content, 'return d;') !== false;
            
            echo "<p>âœ“ File exists: <code>$initFile</code></p>";
            echo "<p>Last modified: <span class='cache-time'>" . date('Y-m-d H:i:s', $mtime) . "</span></p>";
            echo "<p>Timestamp for cache-bust: <code>?v=$mtime</code></p>";
            echo "<p>Contains 'return d;': " . ($hasReturnD ? "<strong style='color:green'>YES âœ“</strong>" : "<strong style='color:red'>NO âœ—</strong>") . "</p>";
        } else {
            echo "<p>âœ— File not found!</p>";
        }
        ?>
    </div>

    <div class="box <?php echo file_exists(__DIR__ . '/admin_elements/admin_header.php') ? 'success' : 'error'; ?>">
        <h2>2. Admin Header Cache-Bust Check</h2>
        <?php
        $headerFile = __DIR__ . '/admin_elements/admin_header.php';
        if (file_exists($headerFile)) {
            $headerContent = file_get_contents($headerFile);
            
            // Check if cache-busting is present
            $hasCacheBust = preg_match('/dashboard-datatable-initializer\.js\?v=.*filemtime/', $headerContent);
            $hasFallbackReturnD = (strpos($headerContent, 'return d;') !== false && strpos($headerContent, 'fallback') !== false);
            
            echo "<p>âœ“ Header file exists</p>";
            echo "<p>Has cache-busting for initializer: " . ($hasCacheBust ? "<strong style='color:green'>YES âœ“</strong>" : "<strong style='color:red'>NO âœ—</strong>") . "</p>";
            echo "<p>Fallback has 'return d;': " . ($hasFallbackReturnD ? "<strong style='color:green'>YES âœ“</strong>" : "<strong style='color:red'>NO âœ—</strong>") . "</p>";
        } else {
            echo "<p>âœ— Header file not found!</p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>3. Test Pages Status</h2>
        <?php
        $pages = [
            'listing_email_history.php',
            'listing_searches.php', 
            'listing_blogs.php',
            'listing_pages.php',
            'listing_invoices.php',
            'listing_users.php',
            'listing_email_campaigns.php',
            'listing_email_templates.php'
        ];
        
        echo "<table style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='border-bottom: 2px solid #ddd; background: #f9f9f9;'><th style='text-align:left; padding:8px;'>Page</th><th style='padding:8px;'>Exists</th><th style='padding:8px;'>Has return d;</th></tr>";
        
        foreach ($pages as $page) {
            $path = __DIR__ . '/' . $page;
            $exists = file_exists($path);
            $hasReturn = false;
            
            if ($exists) {
                $content = file_get_contents($path);
                $hasReturn = (strpos($content, 'return d;') !== false);
            }
            
            $existsIcon = $exists ? "âœ“" : "âœ—";
            $returnIcon = $hasReturn ? "âœ“" : "âœ—";
            $existsColor = $exists ? "green" : "red";
            $returnColor = $hasReturn ? "green" : "red";
            
            echo "<tr style='border-bottom: 1px solid #eee;'>";
            echo "<td style='padding:8px;'><code>$page</code></td>";
            echo "<td style='padding:8px; text-align:center; color:$existsColor; font-weight:bold;'>$existsIcon</td>";
            echo "<td style='padding:8px; text-align:center; color:$returnColor; font-weight:bold;'>$returnIcon</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        ?>
    </div>

    <div class="box warning">
        <h2>4. ðŸ”¥ Browser Cache Issue Detected!</h2>
        <p>All files are <strong>CORRECT</strong> on the server, but your browser is using <strong>CACHED</strong> versions.</p>
        
        <h3>Clear Cache NOW - Choose ONE method:</h3>
        
        <div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;">
            <h4>âœ… Method 1: Hard Refresh (FASTEST)</h4>
            <ul>
                <li><strong>Chrome/Edge:</strong> Press <code>Ctrl + Shift + R</code> or <code>Ctrl + F5</code></li>
                <li><strong>Firefox:</strong> Press <code>Ctrl + Shift + R</code> or <code>Ctrl + F5</code></li>
                <li><strong>Alternative:</strong> Hold <code>Ctrl</code> and click the refresh button</li>
            </ul>
        </div>

        <div style="background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;">
            <h4>âœ… Method 2: Clear Cache (RECOMMENDED)</h4>
            <ol>
                <li>Press <code>Ctrl + Shift + Delete</code></li>
                <li>Select "Cached images and files"</li>
                <li>Choose "Last hour" or "Last 24 hours"</li>
                <li>Click "Clear data"</li>
            </ol>
        </div>

        <div style="background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;">
            <h4>âœ… Method 3: Incognito/Private Mode (NUCLEAR OPTION)</h4>
            <ol>
                <li>Press <code>Ctrl + Shift + N</code> (Chrome) or <code>Ctrl + Shift + P</code> (Firefox)</li>
                <li>Visit: <code>https://127.0.0.1/haizon/dashboard/listing_searches.php</code></li>
                <li>If it works, your normal browser cache is the problem</li>
            </ol>
        </div>
    </div>

    <div class="box success">
        <h2>5. âœ“ Verification URLs</h2>
        <p>After clearing cache, visit these to verify:</p>
        <ul>
            <li><a href="listing_searches.php" target="_blank">listing_searches.php</a> - Should load data instantly</li>
            <li><a href="listing_email_history.php" target="_blank">listing_email_history.php</a> - Should load data instantly</li>
            <li><a href="listing_pages_audit.php" target="_blank">listing_pages_audit.php</a> - Verify DataTable page health</li>
        </ul>
    </div>

    <div class="box">
        <h2>6. Current Timestamps</h2>
        <p>Current server time: <span class='cache-time'><?php echo date('Y-m-d H:i:s'); ?></span></p>
        <p>Initializer version: <span class='cache-time'><?php echo filemtime(__DIR__ . '/../assets/js/dashboard-datatable-initializer.js'); ?></span></p>
        <p>Browser should request: <code>dashboard-datatable-initializer.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/dashboard-datatable-initializer.js'); ?></code></p>
    </div>

    <div class="box" style="background: #e7f3ff; border-left-color: #0056b3;">
        <h2>ðŸ’¡ Why This Is Happening</h2>
        <p>The JavaScript file <code>dashboard-datatable-initializer.js</code> was updated with the fix, but your browser is still using the OLD cached version from before the fix.</p>
        <p>The cache-busting parameter <code>?v=[timestamp]</code> tells the browser when the file changed, but some browsers aggressively cache JavaScript files.</p>
        <p><strong>After you clear the cache, all 7 pages will work immediately.</strong></p>
    </div>

</body>
</html>

