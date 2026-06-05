<?php
/**
 * Stripe Webhook Handler
 * Route: /stripe-webhook  (POST only, no HTML output)
 *
 * IMPORTANT: This endpoint must be EXCLUDED from CSRF middleware
 * and must read the raw body BEFORE any output buffering flushes.
 *
 * Stripe Dashboard → Developers → Webhooks → Add endpoint:
 *   URL: https://yourdomain.com/stripe-webhook
 *   Events to listen for:
 *     checkout.session.completed
 *     invoice.payment_succeeded
 *     invoice.payment_failed
 *     customer.subscription.deleted
 *
 * Security: Stripe signature verified via STRIPE_WEBHOOK_SECRET.
 */

// No session, no HTML – raw PHP only
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../classes/DB.php';
require_once __DIR__ . '/../classes/BusinessListingPlan.php';
require_once __DIR__ . '/../classes/StripePayment.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify signature and parse event
try {
    $event = StripePayment::constructWebhookEvent($payload, $sigHeader);
} catch (RuntimeException $e) {
    // Webhook secret not configured – log and bail
    error_log('[StripeWebhook] Config error: ' . $e->getMessage());
    http_response_code(500);
    exit('Server configuration error');
} catch (Throwable $e) {
    $exceptionClass = get_class($e);
    if (stripos($exceptionClass, 'SignatureVerificationException') !== false) {
        error_log('[StripeWebhook] Signature mismatch: ' . $e->getMessage());
        http_response_code(400);
        exit('Invalid signature');
    }

    error_log('[StripeWebhook] Event parse error (' . $exceptionClass . '): ' . $e->getMessage());
    http_response_code(400);
    exit('Invalid webhook payload');
}

// ── Route events ─────────────────────────────────────────────────────────────
switch ($event->type) {

    // ── checkout.session.completed ───────────────────────────────────────────
    case 'checkout.session.completed':
        $session        = $event->data->object;
        $stripeSession  = $session->id;
        $customerId     = $session->customer    ?? '';
        $subscriptionId = $session->subscription ?? '';
        $meta           = (array)($session->metadata ?? []);
        $billingCycle   = $meta['billing_cycle'] ?? 'monthly';
        $planSlug       = $meta['plan_slug']     ?? '';

        // Retrieve plan details for trial info
        $plan = null;
        if ($planSlug) {
            $plan = BusinessListingPlan::getPlanBySlug($conn, $planSlug);
        }

        $isAnnual  = ($billingCycle === 'annual');
        $hasTrial  = !empty($plan['has_free_trial']) && $isAnnual;
        $trialDays = (int)($plan['free_trial_days'] ?? 30);

        $activated = BusinessListingPlan::activateSubscription(
            $conn,
            $stripeSession,
            (string)$customerId,
            (string)$subscriptionId,
            $isAnnual,
            $hasTrial,
            $trialDays
        );

        if ($activated) {
            BusinessListingPlan::syncUserSearchTierBySessionId($conn, (string)$stripeSession);
            error_log('[StripeWebhook] Subscription activated for session ' . $stripeSession);
        } else {
            error_log('[StripeWebhook] WARNING: No pending subscription found for session ' . $stripeSession);
        }
        break;

    // ── invoice.payment_succeeded (renewal) ──────────────────────────────────
    case 'invoice.payment_succeeded':
        $invoice        = $event->data->object;
        $subscriptionId = $invoice->subscription ?? '';

        if ($subscriptionId) {
            // Extend subscription end date by 1 month or 1 year based on cycle
            $billingReason = $invoice->billing_reason ?? '';
            if ($billingReason === 'subscription_cycle') {
                // Determine interval from invoice lines
                $lines    = $invoice->lines->data ?? [];
                $interval = 'month';
                foreach ($lines as $line) {
                    $interval = $line->price->recurring->interval ?? 'month';
                    break;
                }
                $extension = $interval === 'year' ? '+1 year' : '+1 month';
                $newEnd    = date('Y-m-d H:i:s', strtotime($extension));

                $stmt = $conn->prepare(
                    "UPDATE `" . DB::LISTING_SUBSCRIPTIONS . "`
                     SET subscription_end_at = ?,
                         next_billing_at     = ?,
                         payment_status      = 'paid',
                         status              = 'active',
                         updated_at          = NOW()
                     WHERE stripe_subscription_id = ? AND status IN ('active','trial')
                     LIMIT 1"
                );
                if ($stmt) {
                    $stmt->bind_param('sss', $newEnd, $newEnd, $subscriptionId);
                    $stmt->execute();
                    $stmt->close();
                    BusinessListingPlan::syncUserSearchTierBySubscriptionId($conn, (string)$subscriptionId);
                }
            }
        }
        break;

    // ── invoice.payment_failed ───────────────────────────────────────────────
    case 'invoice.payment_failed':
        $invoice        = $event->data->object;
        $subscriptionId = $invoice->subscription ?? '';

        if ($subscriptionId) {
            $stmt = $conn->prepare(
                "UPDATE `" . DB::LISTING_SUBSCRIPTIONS . "`
                 SET payment_status = 'failed', updated_at = NOW()
                 WHERE stripe_subscription_id = ?
                 LIMIT 1"
            );
            if ($stmt) {
                $stmt->bind_param('s', $subscriptionId);
                $stmt->execute();
                $stmt->close();
                BusinessListingPlan::syncUserSearchTierBySubscriptionId($conn, (string)$subscriptionId);
            }
            error_log('[StripeWebhook] Payment failed for subscription ' . $subscriptionId);
        }
        break;

    // ── customer.subscription.deleted (cancelled/expired) ────────────────────
    case 'customer.subscription.deleted':
        $sub            = $event->data->object;
        $subscriptionId = $sub->id;

        $stmt = $conn->prepare(
            "UPDATE `" . DB::LISTING_SUBSCRIPTIONS . "`
             SET status        = 'cancelled',
                 cancelled_at  = NOW(),
                 updated_at    = NOW()
             WHERE stripe_subscription_id = ?
             LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param('s', $subscriptionId);
            $stmt->execute();
            $stmt->close();
            BusinessListingPlan::syncUserSearchTierBySubscriptionId($conn, (string)$subscriptionId);
        }
        error_log('[StripeWebhook] Subscription cancelled: ' . $subscriptionId);
        break;

    default:
        // Unhandled event type – log and return 200 so Stripe doesn't retry
        error_log('[StripeWebhook] Unhandled event type: ' . $event->type);
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);
