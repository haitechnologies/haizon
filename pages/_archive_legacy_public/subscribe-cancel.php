<?php
/**
 * Page: Subscription Cancelled
 * Route: /subscribe-cancel
 * Description: Shown when user clicks "Back" on Stripe's checkout page.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';

$pageTitle = 'Payment Cancelled â€“ HAIPULSE';
include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="mb-4 subs-icon">â†©ï¸</div>
            <h1 class="fw-bold mb-3">Payment Cancelled</h1>
            <p class="lead text-muted mb-5">
                No charge was made. You can return to the pricing page whenever you're ready.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?= url('/pricing') ?>" class="btn btn-primary btn-lg px-5">View Plans</a>
                <a href="<?= url('/') ?>"        class="btn btn-outline-secondary btn-lg px-5">Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

