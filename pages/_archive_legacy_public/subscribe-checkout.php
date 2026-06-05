<?php
/**
 * Page: Subscribe Checkout
 * Route: /subscribe-checkout
 * Description: Creates a Stripe Checkout Session and redirects
 *              the user to Stripe's hosted payment page.
 *
 * Required query params:
 *   ?plan=silver|gold|platinum  (plan slug)
 *   &cycle=monthly|annual
 *
 * Security:
 *   - Requires frontend user to be logged in
 *   - CSRF token validated for POST
 *   - Plan slug and cycle are whitelisted, never echoed raw
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/BusinessListingPlan.php';
require_once __DIR__ . '/../classes/StripePayment.php';

// ── Auth check ──────────────────────────────────────────────────────────────
if (empty($_SESSION['frontend_user_id'])) {
    header('Location: ' . url('/login?redirect=' . rawurlencode('/pricing')));
    exit;
}
$userId = (int)$_SESSION['frontend_user_id'];

// ── Input validation ─────────────────────────────────────────────────────────
$allowedSlugs  = ['silver', 'gold', 'platinum'];
$allowedCycles = ['monthly', 'annual'];

$planSlug     = $_GET['plan']  ?? '';
$billingCycle = $_GET['cycle'] ?? 'annual';

if (!in_array($planSlug, $allowedSlugs, true)) {
    header('Location: ' . url('/pricing'));
    exit;
}
if (!in_array($billingCycle, $allowedCycles, true)) {
    $billingCycle = 'annual';
}

// ── Fetch plan details ───────────────────────────────────────────────────────
$plan = BusinessListingPlan::getPlanBySlug($conn, $planSlug);
if (!$plan) {
    header('Location: ' . url('/pricing'));
    exit;
}

// ── Check company association ────────────────────────────────────────────────
// The user must have at least one company linked (to attach subscription to)
$stmt = $conn->prepare(
    "SELECT id FROM `" . DB::COMPANIES . "` WHERE owner_user_id = ? AND is_active = 1 LIMIT 1"
);
$companyId = 0;
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($companyId);
    $stmt->fetch();
    $stmt->close();
}

if (!$companyId) {
    // No company yet – redirect to add-business first
    header('Location: ' . url('/add-business?next=pricing&plan=' . $planSlug . '&cycle=' . $billingCycle));
    exit;
}

// ── Amount ───────────────────────────────────────────────────────────────────
$amount = $billingCycle === 'annual'
    ? (float)$plan['annual_price']
    : (float)$plan['monthly_price'];

// ── Create Stripe Checkout Session ───────────────────────────────────────────
$successUrl = url('/subscribe-success?session_id={CHECKOUT_SESSION_ID}');
$cancelUrl  = url('/pricing');

try {
    $session = StripePayment::createCheckoutSession(
        $plan,
        $billingCycle,
        (int)$companyId,
        $userId,
        $successUrl,
        $cancelUrl
    );

    // Store the pending subscription record
    BusinessListingPlan::createPendingSubscription(
        $conn,
        (int)$companyId,
        (int)$plan['id'],
        $billingCycle,
        $session->id,
        $amount
    );

    // Redirect to Stripe hosted checkout page
    header('Location: ' . $session->url, true, 303);
    exit;

} catch (RuntimeException $e) {
    // Stripe not yet configured – show setup message
    http_response_code(503);
    $pageTitle = 'Payment Unavailable';
    include __DIR__ . '/../includes/layout/header.php';
    echo '<div class="container py-5 text-center">'
       . '<h2>Online Payment Coming Soon</h2>'
       . '<p class="text-muted">We are finalising our payment integration. Please '
       . '<a href="' . url('/contact') . '">contact us</a> to arrange your subscription manually.</p>'
       . '</div>';
    include __DIR__ . '/../includes/layout/footer.php';
    exit;

} catch (\Stripe\Exception\ApiErrorException $e) {
    // Log and show friendly error
    error_log('[Stripe checkout error] ' . $e->getMessage());
    http_response_code(500);
    $pageTitle = 'Payment Error';
    include __DIR__ . '/../includes/layout/header.php';
    echo '<div class="container py-5 text-center">'
       . '<h2>Something went wrong</h2>'
       . '<p class="text-muted">We could not initiate your payment. Please try again or '
       . '<a href="' . url('/contact') . '">contact support</a>.</p>'
       . '<a href="' . url('/pricing') . '" class="btn btn-primary">Back to Pricing</a>'
       . '</div>';
    include __DIR__ . '/../includes/layout/footer.php';
    exit;
}
