<?php
/**
 * Legacy Route Redirect: HS Code Finder
 * Route: /trade/hs-code-finder
 * Canonical: /trade/hs-codes
 */

require_once __DIR__ . '/../includes/helpers.php';

$target = url('/trade/hs-codes');
$queryString = isset($_SERVER['QUERY_STRING']) ? trim((string)$_SERVER['QUERY_STRING']) : '';
if ($queryString !== '') {
    $target .= '?' . $queryString;
}

header('Location: ' . $target, true, 301);
exit;
