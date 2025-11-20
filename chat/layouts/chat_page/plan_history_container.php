<?php
require_once dirname(__DIR__, 3).'/includes/subscription/bootstrap.php';

$service = subscription_service();
$currentUserId = Registry::load('current_user')->id ?? 0;
$history = $currentUserId ? $service->listRecentSubscriptions((int)$currentUserId, 5) : [];

$chatBaseUrl = rtrim(Registry::load('config')->site_url ?? '', '/');
$publicBaseUrl = preg_replace('#/chat/?$#i', '', $chatBaseUrl);
if ($publicBaseUrl === '') {
    $publicBaseUrl = $chatBaseUrl;
}
if ($publicBaseUrl !== '' && substr($publicBaseUrl, -1) !== '/') {
    $publicBaseUrl .= '/';
}
$subscriptionPageUrl = $publicBaseUrl.'subscription.php';
$activePlanUrl = $subscriptionPageUrl.'?view=active';
$historyPageUrl = $subscriptionPageUrl.'?view=history';
$backToChatUrl = $chatBaseUrl !== '' ? $chatBaseUrl.'/' : './';

include __DIR__.'/subscription_styles.php';
?>
<div class="subscription-page">
    <section class="subscription-hero">
        <div>
            <p class="eyebrow">Plan history</p>
            <h1>Recent transactions</h1>
            <p class="subtitle">
                Showing the last five subscriptions or recharges captured on your account.
            </p>
        </div>
        <div>
            <a class="back-to-chat" href="<?= htmlspecialchars($backToChatUrl, ENT_QUOTES, 'UTF-8'); ?>">
                ← Back to chat
            </a>
            <a class="secondary-btn" href="<?= htmlspecialchars($activePlanUrl, ENT_QUOTES, 'UTF-8'); ?>">
                View active plan
            </a>
            <a class="back-to-chat" href="<?= htmlspecialchars($subscriptionPageUrl, ENT_QUOTES, 'UTF-8'); ?>">
                Recharge now
            </a>
        </div>
    </section>

    <div class="history-panel">
        <?php if (!empty($history)): ?>
            <div class="table-responsive">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Payment ref.</th>
                            <th>Purchased on</th>
                            <th>Valid till</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $index => $entry): ?>
                            <?php
                            $amount = $entry['amount'];
                            $currency = $entry['currency'] ?? 'INR';
                            $amountLabel = $amount !== null ? number_format((float)$amount, 2) : '—';
                            $status = strtolower((string)($entry['status'] ?? 'pending'));
                            $statusSlug = preg_replace('/[^a-z0-9_-]/i', '', $status) ?: 'pending';
                            $statusClass = 'history-status '.$statusSlug;
                            ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($entry['plan_name'] ?? 'Plan', ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <small><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?> · <?= (int)($entry['total_minutes'] ?? 0); ?> min</small>
                                </td>
                                <td><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($amountLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($entry['payment_reference'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($entry['purchased_on'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($entry['valid_till'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="history-empty">
                <p>No subscription payments found yet.</p>
                <p>
                    <a class="back-to-chat" href="<?= htmlspecialchars($subscriptionPageUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        Pick a plan
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
