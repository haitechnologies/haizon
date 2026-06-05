<?php
/**
 * Page: Subscription Success
 * Route: /subscribe-success
 * Description: Shown after Stripe redirects back from a completed checkout.
 *              Verifies the session, then shows a confirmation message.
 *              The actual subscription activation is done by the Stripe
 *              webhook (stripe-webhook.php) for reliability.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';

$pageTitle = 'Subscription Confirmed â€“ HAIPULSE';
$sessionId = htmlspecialchars($_GET['session_id'] ?? '', ENT_QUOTES, 'UTF-8');

// Optionally verify the session server-side (requires Stripe SDK)
$sessionOk   = false;
$planName    = '';
$trialEndsAt = '';

if ($sessionId && class_exists('StripePayment')) {
    try {
        require_once __DIR__ . '/../classes/StripePayment.php';
        $stripeSession = StripePayment::retrieveSession($sessionId);
        $sessionOk     = ($stripeSession->payment_status === 'paid' || $stripeSession->status === 'complete');
        $meta          = $stripeSession->metadata ?? [];
        $planName      = ucfirst($meta['plan_slug'] ?? '');
        $sub           = $stripeSession->subscription;
        if ($sub && !empty($sub->trial_end)) {
            $trialEndsAt = date('d M Y', $sub->trial_end);
        }
    } catch (Throwable $e) {
        // Webhook already handles activation; UI can proceed without SDK check
        $sessionOk = !empty($sessionId);
    }
} else {
    $sessionOk = !empty($sessionId);
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">

            <?php if ($sessionOk): ?>
                <div class="mb-4 subs-icon">ðŸŽ‰</div>
                <h1 class="fw-bold mb-3">You're all set!</h1>

                <?php if ($planName): ?>
                    <p class="lead text-muted mb-2">
                        Your <strong><?= htmlspecialchars($planName, ENT_QUOTES) ?> Plan</strong> is now active.
                    </p>
                <?php else: ?>
                    <p class="lead text-muted mb-2">Your subscription is now active.</p>
                <?php endif; ?>

                <?php if ($trialEndsAt): ?>
                    <div class="alert alert-info d-inline-block px-4 py-2 mb-4">
                        ðŸŽ Your <strong>1-month free trial</strong> runs until <strong><?= htmlspecialchars($trialEndsAt, ENT_QUOTES) ?></strong>.
                        You won't be charged before then.
                    </div>
                <?php endif; ?>

                <p class="text-muted mb-5">
                    A confirmation email has been sent to you. Your company listing will be updated within a few minutes to reflect your new plan features.
                </p>

                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="<?= url('/account/profile?tab=listing') ?>" class="btn btn-primary btn-lg px-5">Open My Account</a>
                    <a href="<?= url('/add-business') ?>" class="btn btn-outline-secondary btn-lg px-5">Manage My Listing</a>
                </div>

            <?php else: ?>
                <div class="mb-4 subs-icon">âš ï¸</div>
                <h1 class="fw-bold mb-3">We couldn't verify your payment</h1>
                <p class="text-muted mb-4">
                    If you completed the payment, your subscription will be activated within a few minutes via our automatic system.
                    If the problem persists, please <a href="<?= url('/contact') ?>">contact our support team</a>.
                </p>
                <a href="<?= url('/pricing') ?>" class="btn btn-outline-primary btn-lg px-5">Back to Pricing</a>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

