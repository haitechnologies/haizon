<?php
/**
 * QR Code Generator Endpoint
 * 
 * Generates PNG QR codes from a provided code parameter.
 * Usage: generate.php?code=YOUR_TEXT
 */

// ── Error Logging Bootstrap ──────────────────────────────────────────
require_once __DIR__ . '/admin_elements/error_logger.php';
if (function_exists('custom_error_handler')) {
    set_error_handler('custom_error_handler');
}
if (function_exists('custom_exception_handler')) {
    set_exception_handler('custom_exception_handler');
}
if (function_exists('handle_fatal_error')) {
    register_shutdown_function('handle_fatal_error');
}
// ─────────────────────────────────────────────────────────────────────

header("Content-Type: image/png");
require "../vendor/autoload.php";

use Endroid\QrCode\QrCode;

if (!isset($_GET['code']) || trim((string)$_GET['code']) === '') {
	if (!headers_sent()) {
		http_response_code(400);
		header('Content-Type: text/plain; charset=utf-8');
	}
	echo 'Missing code parameter.';
	exit;
}

try {
    $code = trim((string)$_GET['code']);
    $qrCode = new QrCode($code);
    echo $qrCode->writeString();

    // file_put_contents('dummy1.png', $qrCode->writeString());
    $safeFileName = preg_replace('/[^A-Za-z0-9_-]/', '_', $code);
    if ($safeFileName !== '') {
        file_put_contents($safeFileName . '.png', $qrCode->writeString());
    }
} catch (Throwable $e) {
    if (function_exists('log_error')) {
        log_error('[generate.php] QR generation failed: ' . $e->getMessage(), 'ERROR', __FILE__, __LINE__);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'QR code generation failed.';
}
