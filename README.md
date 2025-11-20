# onmilap project update

## Subscription module quickstart
- Include `views/subscription_widget.php` inside the desired page (e.g. `subscription.php`) after loading `api/bootstrap.php`. The widget auto-populates available plans and bootstraps Razorpay/PayPal flows through `/api/subscriptions/purchase.php`.
- Frontend logic lives in `assets/js/subscription-widget.js`. It fetches live USD→INR rates from `/api/subscriptions/exchange_rate.php`, toggles currencies, and opens the configured gateway checkout. Ensure Razorpay SDK is loaded globally when INR plans are enabled.
- Backend services are initialized via `includes/subscription/bootstrap.php`, exposing the `subscription_service()` helper for controllers, cron jobs, and API endpoints.

## New API surface
- `POST /api/subscriptions/purchase.php` – creates/extends subscriptions and returns payment metadata.
- `POST /api/subscriptions/webhook_razorpay.php` & `webhook_paypal.php` – capture gateway callbacks and activate minutes.
- `POST /api/sessions/end.php` – deducts session minutes, emits summary email, and returns fresh balances.
- `GET /api/subscriptions/exchange_rate.php` – exposes the cached USD→INR rate; set `EXCHANGE_RATE_API_KEY` inside `config.php` to enable live quoting.
- `cron/subscription_daily_reset.php` – resets daily quotas, expires plans, and should be scheduled nightly via `php cron/subscription_daily_reset.php`.

## Email + templates
- Session wrap-up emails now render through `views/emails/session_summary.php`, keeping the HTML separate from business logic. Customize the copy there while the base MJML template in `chat/fns/mailer/template.php` handles branding.
- `SubscriptionService::queueSessionSummaryEmail()` injects the template output and CTA automatically after each `api/sessions/end.php` call.

## Testing
- Basic service coverage lives in `tests/subscription/SubscriptionServiceTest.php`. Run it with `php tests/subscription/SubscriptionServiceTest.php` to spin up an in-memory SQLite database, exercise `purchasePlan()`/`deductMinutesOnSessionEnd()`, and verify that balances plus email notifications behave correctly.
- The suite is framework-agnostic; if you prefer PHPUnit, you can still require this script inside a PHPUnit test case and assert on its helper methods.

## Key assumptions
- Gateway selection is currency-driven: INR → Razorpay, everything else → PayPal. Future gateways can hook into `SubscriptionService::purchasePlan()` without schema changes.
- Exchange-rate API failures gracefully fall back to the last cached quote (15-minute TTL). If no API key is configured, the widget simply keeps USD pricing.
- Session minutes are rounded using PHP's default `round()` behavior before decrementing quotas. Calls shorter than one minute are rejected by the API to match the product spec.
- Emails rely on `Registry::load('settings')->system_email_address`; keep that populated in the admin panel for proper support links.
- Tests mock the mailer function instead of sending real messages, and they only cover core flows. Portal/Latte rendering, Razorpay/PayPal verification, and cron execution still need integration testing on staging.