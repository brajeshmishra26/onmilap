<?php
require_once __DIR__ . '/../api/bootstrap.php';
$plans = subscription_service()->listPlans();
$currentUserId = Registry::load('current_user')->id ?? 0;
$chatBaseUrl = rtrim(Registry::load('config')->site_url ?? '', '/');
$publicBaseUrl = preg_replace('#/chat/?$#', '', $chatBaseUrl);
if ($publicBaseUrl === '') {
    $publicBaseUrl = $chatBaseUrl;
}
if ($publicBaseUrl !== '' && substr($publicBaseUrl, -1) !== '/') {
    $publicBaseUrl .= '/';
}
$apiBaseUrl = $publicBaseUrl.'api/';
$widgetScriptPath = realpath(__DIR__.'/../assets/js/subscription-widget.js');
$widgetScriptVersion = $widgetScriptPath ? (int)filemtime($widgetScriptPath) : time();
?>
<section class="container py-4" id="subscription-widget">
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="currency-select" class="form-label fw-semibold">Choose currency</label>
            <select id="currency-select" class="form-select">
                <option value="INR" selected>Indian Rupee (INR)</option>
                <option value="USD">USD</option>
            </select>
        </div>
    </div>
    <div class="row g-3">
        <?php if (empty($plans)): ?>
            <div class="col-12">
                <div class="alert alert-warning mb-0">
                    <strong>No active plans are configured yet.</strong> Please add at least one plan from the admin dashboard to enable purchases.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($plans as $plan): ?>
                <?php
                $hasInr = isset($plan['price_inr']) && $plan['price_inr'] !== null;
                $displayCurrency = $hasInr ? '₹' : '$';
                $displayAmount = $hasInr ? (float)$plan['price_inr'] : (float)$plan['price_usd'];
                ?>
                <div class="col-md-3">
                    <div class="card shadow-sm plan-card" data-plan="<?= htmlspecialchars($plan['slug']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($plan['name']) ?></h5>
                            <p class="plan-price fs-3 fw-bold mb-0"><?= $displayCurrency . number_format($displayAmount, 2) ?></p>
                            <p class="text-muted plan-validity mb-1">Validity: <?= (int)$plan['validity_days'] ?> day(s)</p>
                            <p class="plan-minutes mb-3">Minutes: <?= (int)$plan['total_minutes'] ?></p>
                            <button type="button" class="btn btn-primary w-100">Recharge now</button>
                            <div id="paypal-container-<?= htmlspecialchars($plan['slug']) ?>" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<script>
    window.subscriptionPlans = <?= json_encode($plans) ?>;
    window.currentUserId = <?= (int) $currentUserId ?>;
    window.razorpayKeyId = "<?= addslashes(Registry::load('config')->RAZORPAY_KEY_ID ?? '') ?>";
    window.subscriptionApiBase = "<?= htmlspecialchars($apiBaseUrl, ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="<?= htmlspecialchars($publicBaseUrl, ENT_QUOTES, 'UTF-8'); ?>assets/js/subscription-widget.js?v=<?= $widgetScriptVersion; ?>"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js" async></script>
