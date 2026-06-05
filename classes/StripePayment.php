<?php
/**
 * StripePayment
 *
 * Thin wrapper around Stripe PHP SDK for business listing subscriptions.
 * 
 * Setup requirements:
 *  1. composer require stripe/stripe-php
 *  2. Add to .env:
 *       STRIPE_SECRET_KEY=sk_live_xxx  (or sk_test_xxx for development)
 *       STRIPE_PUBLISHABLE_KEY=pk_live_xxx
 *       STRIPE_WEBHOOK_SECRET=whsec_xxx
 *  3. Create Products + Prices in Stripe Dashboard, then save the
 *     price IDs into hai_listing_plans.stripe_monthly_price_id /
 *     stripe_annual_price_id columns.
 */

class StripePayment
{
    private static bool $initialised = false;

    /**
     * Boot the Stripe SDK once per request.
     * Throws RuntimeException if the key is missing.
     */
    public static function init(): void
    {
        if (self::$initialised) {
            return;
        }

        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new RuntimeException(
                'Stripe SDK not found. Run: composer require stripe/stripe-php'
            );
        }
        require_once $autoload;

        $secret = $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?? '';
        if (empty($secret)) {
            throw new RuntimeException('STRIPE_SECRET_KEY is not set in environment.');
        }

        \Stripe\Stripe::setApiKey($secret);
        \Stripe\Stripe::setAppInfo('HAIPULSE Business Directory', '1.0.0');
        self::$initialised = true;
    }

    /**
     * Return the publishable key (safe to expose to frontend JS).
     */
    public static function publishableKey(): string
    {
        return $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? getenv('STRIPE_PUBLISHABLE_KEY') ?? '';
    }

    // -----------------------------------------------------------------
    // Checkout Session
    // -----------------------------------------------------------------

    /**
     * Create a Stripe Checkout Session for a listing plan subscription.
     *
     * @param  array  $plan          Row from hai_listing_plans
     * @param  string $billingCycle  'monthly' | 'annual'
     * @param  int    $companyId     ID of the company being subscribed
     * @param  int    $userId        Frontend user initiating the purchase
     * @param  string $successUrl    Full URL to redirect after payment (include ?session_id={CHECKOUT_SESSION_ID})
     * @param  string $cancelUrl     Full URL to redirect if user cancels
     * @return \Stripe\Checkout\Session
     * @throws \Stripe\Exception\ApiErrorException
     */
    public static function createCheckoutSession(
        array  $plan,
        string $billingCycle,
        int    $companyId,
        int    $userId,
        string $successUrl,
        string $cancelUrl
    ): \Stripe\Checkout\Session {
        self::init();

        $priceId = $billingCycle === 'annual'
            ? ($plan['stripe_annual_price_id'] ?? '')
            : ($plan['stripe_monthly_price_id'] ?? '');

        if (empty($priceId)) {
            throw new RuntimeException(
                "Stripe price ID is not configured for plan '{$plan['plan_slug']}' ({$billingCycle})."
            );
        }

        $params = [
            'mode'               => 'subscription',
            'line_items'         => [['price' => $priceId, 'quantity' => 1]],
            'success_url'        => $successUrl,
            'cancel_url'         => $cancelUrl,
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'required',
            'metadata' => [
                'company_id'    => (string)$companyId,
                'user_id'       => (string)$userId,
                'plan_slug'     => $plan['plan_slug'],
                'billing_cycle' => $billingCycle,
            ],
        ];

        // Enable free trial if the plan supports it and this is an annual cycle
        if ($billingCycle === 'annual' && !empty($plan['has_free_trial']) && !empty($plan['free_trial_days'])) {
            $params['subscription_data'] = [
                'trial_period_days' => (int)$plan['free_trial_days'],
                'metadata' => $params['metadata'],
            ];
        }

        return \Stripe\Checkout\Session::create($params);
    }

    /**
     * Retrieve a Checkout Session by ID (used on success page to confirm).
     */
    public static function retrieveSession(string $sessionId): \Stripe\Checkout\Session
    {
        self::init();
        return \Stripe\Checkout\Session::retrieve([
            'id'     => $sessionId,
            'expand' => ['subscription', 'subscription.trial_end'],
        ]);
    }

    // -----------------------------------------------------------------
    // Webhook verification
    // -----------------------------------------------------------------

    /**
     * Parse and verify an incoming Stripe webhook request.
     * Returns the verified Event or throws SignatureVerificationException.
     *
     * Usage in stripe-webhook.php:
     *   $event = StripePayment::constructWebhookEvent(
     *       file_get_contents('php://input'),
     *       $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''
     *   );
     *
     * @throws \Stripe\Exception\SignatureVerificationException
     * @throws RuntimeException if webhook secret is not configured
     */
    public static function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        self::init();

        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET') ?? '';
        if (empty($secret)) {
            throw new RuntimeException('STRIPE_WEBHOOK_SECRET is not set in environment.');
        }

        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
    }

    // -----------------------------------------------------------------
    // Subscription management (portal)
    // -----------------------------------------------------------------

    /**
     * Create a Stripe Customer Portal session so users can self-manage
     * their subscription (update card, cancel, etc.).
     *
     * @param  string $stripeCustomerId  cus_xxx from hai_listing_subscriptions
     * @param  string $returnUrl         Page to return to after portal
     * @return string  URL to redirect the user to
     */
    public static function createPortalSession(string $stripeCustomerId, string $returnUrl): string
    {
        self::init();
        $session = \Stripe\BillingPortal\Session::create([
            'customer'   => $stripeCustomerId,
            'return_url' => $returnUrl,
        ]);
        return $session->url;
    }

    // -----------------------------------------------------------------
    // Price calculation helpers (no Stripe API call needed)
    // -----------------------------------------------------------------

    /**
     * Convert AED amount to Stripe minor units (fils). 
     * Stripe requires integers in the smallest currency unit.
     * AED is a 2-decimal currency â†’ multiply by 100.
     */
    public static function toStripeAmount(float $aed): int
    {
        return (int)round($aed * 100);
    }
}

