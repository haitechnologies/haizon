<?php

// Compatibility endpoint for a legacy shipping route.
// Route old view page requests into the shipping invoice editor in haipulse.
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    header('Location: shipping_invoices.php?action=edit_shipping_invoices&id=' . $id);
    exit;
}

header('Location: listing_shipping_invoices.php');
exit;
