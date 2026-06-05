<?php
// Legacy compatibility shim: older pages include breadcrumb.php for messages.
// Delegate to centralized renderer to avoid duplicate/triple message output.
require_once __DIR__ . '/messages.php';
?>