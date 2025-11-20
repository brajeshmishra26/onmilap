<?php
/**
 * Session summary email template.
 * Variables extracted into scope before include.
 * @var string $displayName
 * @var int $dailyRemaining
 * @var int $totalRemaining
 * @var string $expiryDate
 * @var string $ctaUrl
 * @var string $supportEmail
 */
?>
<p>Hi <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>,</p>
<p>You wrapped up a chat session with our AI expert. Here is the current balance on your subscription:</p>
<ul>
    <li><strong>Remaining minutes today:</strong> <?= (int) $dailyRemaining ?></li>
    <li><strong>Remaining overall minutes:</strong> <?= (int) $totalRemaining ?></li>
    <li><strong>Plan expires on:</strong> <?= htmlspecialchars($expiryDate, ENT_QUOTES, 'UTF-8') ?></li>
</ul>
<p>Need more time? You can top up instantly using the link below.</p>
<p>If you believe this activity looks incorrect, reply to this email or contact us at <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></a>.</p>
<p>Thanks for being with onMilap!</p>
<p>
    <a href="<?= htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;padding:12px 24px;background:#1e8bf1;color:#fff;text-decoration:none;border-radius:4px;">
        Recharge minutes
    </a>
</p>
