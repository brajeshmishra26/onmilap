<?php
$widgetPath = dirname(__DIR__, 3) . '/views/subscription_widget.php';
$backToChatUrl = Registry::load('config')->site_url;
include __DIR__ . '/subscription_styles.php';

$widgetMarkup = '';
if (is_file($widgetPath)) {
    ob_start();
    include $widgetPath;
    $widgetMarkup = trim((string)ob_get_clean());
}

$strings = Registry::load('strings');
$heroTitle = isset($strings->subscription) ? $strings->subscription : 'Recharge plans';
$heroSubtitle = isset($strings->subscription_subtitle)
    ? $strings->subscription_subtitle
    : 'Pick a plan, pay securely via Razorpay or PayPal, and start chatting right away.';
$backLabel = isset($strings->back_to_chat) ? $strings->back_to_chat : 'Back to chat';
$ctaLabel = isset($strings->recharge_now) ? $strings->recharge_now : 'Recharge now';
$helpLabel = isset($strings->contact_support) ? $strings->contact_support : 'Contact support';
$supportEmail = Registry::load('settings')->system_email_address ?? 'support@example.com';
?>

<div class="subscription-page">
    <section class="subscription-hero">
        <div>
            <p class="eyebrow">onMilap</p>
            <h1><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="subtitle"><?= htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end;">
            <a class="back-to-chat" href="<?= htmlspecialchars($backToChatUrl, ENT_QUOTES, 'UTF-8'); ?>">
                ← <?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a class="secondary-btn" href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?>">
                <?= htmlspecialchars($helpLabel, ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a class="back-to-chat" href="#plans">
                <?= htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </section>

    <div class="subscription-widget-wrapper" id="plans">
        <?php if ($widgetMarkup !== ''): ?>
            <?= $widgetMarkup; ?>
        <?php else: ?>
            <div class="history-empty">
                <p>Unable to load subscription widget. Please ensure <code>views/subscription_widget.php</code> exists.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
