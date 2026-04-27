<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin', 'reseller');

$resellerId = $_SESSION['user_id'];
$resellers = readJson(DATA_PATH . 'resellers.json');
$me = findById($resellers, $resellerId);
$creditHistory = $me['credit_history'] ?? [];
$creditHistory = array_reverse($creditHistory);

renderHead('Wallet');
renderSidebar('reseller', 'wallet');
renderTopbar('Reseller Wallet');
?>

<div class="stats-grid animate-in">
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= number_format($me['credits'] ?? 0) ?></div><div class="stat-label">Available Credits</div></div><div class="stat-icon success">💰</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= count($creditHistory) ?></div><div class="stat-label">Transactions</div></div><div class="stat-icon primary">📋</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-value">
                    <?= number_format(array_sum(array_map(fn($h) => $h['amount'] > 0 ? $h['amount'] : 0, $creditHistory))) ?>
                </div>
                <div class="stat-label">Total Received</div>
            </div>
            <div class="stat-icon info">⬆️</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-value">
                    <?= number_format(abs(array_sum(array_map(fn($h) => $h['amount'] < 0 ? $h['amount'] : 0, $creditHistory)))) ?>
                </div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-icon warning">⬇️</div>
        </div>
    </div>
</div>

<div class="card animate-in">
    <div class="card-header"><span class="card-title">📋 Credit History</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Note</th><th>Balance After</th></tr></thead>
            <tbody>
            <?php if (empty($creditHistory)): ?>
            <tr><td colspan="5"><div class="empty-state"><span class="empty-icon">💳</span><h3>No transactions yet</h3><p>Credits added by admin will appear here.</p></div></td></tr>
            <?php else: foreach ($creditHistory as $h): ?>
            <tr>
                <td class="text-small text-muted"><?= date('M j, Y H:i', strtotime($h['at'])) ?></td>
                <td>
                    <span class="badge <?= $h['amount'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                        <?= $h['amount'] > 0 ? '⬆️ Credit' : '⬇️ Debit' ?>
                    </span>
                </td>
                <td style="font-weight:700;color:<?= $h['amount'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                    <?= $h['amount'] > 0 ? '+' : '' ?><?= number_format($h['amount']) ?>
                </td>
                <td><?= htmlspecialchars($h['note'] ?? '—') ?></td>
                <td class="font-mono"><?= number_format($h['balance_after'] ?? 0) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card animate-in" style="margin-top:16px;">
    <div class="card-header"><span class="card-title">ℹ️ How Credits Work</span></div>
    <div class="card-body">
        <ul style="padding-left:20px;line-height:1.8;color:var(--text-muted);">
            <li>Credits are added by the admin to your account.</li>
            <li>Each new user you create costs credits based on the selected plan.</li>
            <li>Contact admin via support ticket to request credit top-up.</li>
            <li>Credits are non-refundable once used to create a user account.</li>
        </ul>
    </div>
</div>

<?php renderFooter(); ?>
