<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/SimpleCaptcha.php';

startFrontendSession();

$context = trim((string)($_GET['context'] ?? 'contact_form'));
if ($context === '' || !preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $context)) {
    $context = 'contact_form';
}

if (isset($_GET['refresh']) && $_GET['refresh'] === '1') {
    SimpleCaptcha::refreshChallenge($context);
} else {
    SimpleCaptcha::ensureChallenge($context);
}

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo SimpleCaptcha::renderSvg($context);
