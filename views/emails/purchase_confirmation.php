<?php
/**
 * Purchase confirmation email template.
 * Variables extracted before include:
 * @var string $displayName
 * @var string $planName
 * @var string|null $amount
 * @var string $currency
 * @var string|null $paymentReference
 * @var string|null $validTill
 * @var int|string|null $minutes
 * @var string $gateway
 * @var string $ctaUrl
 * @var string $supportEmail
 */
?>
<p>Hi <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>,</p>
<p>Thank you for recharging your <?= htmlspecialchars($planName, ENT_QUOTES, 'UTF-8') ?> plan via <?= htmlspecialchars($gateway, ENT_QUOTES, 'UTF-8') ?>. Here are the details we recorded for this purchase:</p>
<ul>
    <?php if ($amount !== null): ?>
        <li><strong>Amount:</strong> <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endif; ?>
    <?php if (!empty($minutes)): ?>
        <li><strong>Minutes added:</strong> <?= htmlspecialchars((string)$minutes, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endif; ?>
    <?php if (!empty($validTill)): ?>
        <li><strong>Valid through:</strong> <?= htmlspecialchars($validTill, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endif; ?>
    <?php if (!empty($paymentReference)): ?>
        <li><strong>Payment reference:</strong> <?= htmlspecialchars($paymentReference, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endif; ?>
</ul>
<p>You can review your subscription or top up again anytime by visiting your plan dashboard.</p>
<p>If something does not look right, reply to this email or reach us at <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></a>.</p>
<p>
    <a href="<?= htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;padding:12px 24px;background:#1e8bf1;color:#fff;text-decoration:none;border-radius:4px;font-weight:600;">
        Manage plan
    </a>
</p>
<p>Team onMilap</p>
