<?php
/**
 * CSP violation report endpoint.
 * Returns 204 to prevent browser console noise when CSP reports are posted.
 */

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(204);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw !== false && $raw !== '') {
    $maxLen = 8192;
    $payload = substr($raw, 0, $maxLen);
    error_log('[CSP_REPORT] ' . $payload);
}

http_response_code(204);
