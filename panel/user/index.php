<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('user');

$userId = $_SESSION['user_id'];
$users = readJson(DATA_PATH . 'users.json');
$me = findById($users, $userId);
$stats = getDashboardStats('user', $userId);
$plan = getUserPlan($userId);
$sites = $me['sites'] ?? [];

$expiryInfo = '';
$expiryClass = 'success';
if (!empty($me['expiry'])) {
    $days = (int)((strtotime($me['expiry']) - time()) / 86400);
    if ($days < 0) { $expiryInfo = 'Expired'; $expiryClass = 'danger'; }
    elseif ($days === 0) { $expiryInfo = 'Expires today'; $expiryClass = 'danger'; }
    elseif ($days <= 7) { $expiryInfo = "Expires in {$days} days"; $expiryClass = 'warning'; }
    else { $expiryInfo = date('M j, Y', strtotime($me['expiry'])); }
}

renderHead('Dashboard');
renderSidebar('user', 'dashboard');
renderTopbar('My Dashboard');
?>

<?php if (!empty($me['expiry']) && strtotime($me['expiry']) < time()): ?>
<div class="alert alert-danger">⚠️ Your account has expired. Please contact support to renew.</div>
<?php elseif (!empty($expiryInfo) && $expiryClass !== 'success'): ?>
<div class="alert alert-<?= $expiryClass ?>">⚠️ <?= $expiryInfo ?> — Contact support to renew.</div>
<?php endif; ?>

<div class="stats-grid animate-in">
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= count($sites) ?> / <?= $plan['sites_limit'] ?? '∞' ?></div><div class="stat-label">Sites Used</div></div><div class="stat-icon primary">🌐</div></div>
        <div class="text-small text-muted">
            <?php if (($plan['sites_limit'] ?? 0) > 0): ?>
            <div class="progress-bar-wrap"><div class="progress-bar" style="width:<?= min(100, round(count($sites) / max(1, $plan['sites_limit']) * 100)) ?>%"></div></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= humanFileSize($stats['storage_used'] ?? 0) ?> / <?= ($plan['storage_mb'] ?? 0) >= 1000 ? round(($plan['storage_mb'] ?? 0)/1024, 1).'GB' : ($plan['storage_mb'] ?? '∞').'MB' ?></div><div class="stat-label">Storage Used</div></div><div class="stat-icon info">💾</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= $stats['open_tickets'] ?></div><div class="stat-label">Open Tickets</div></div><div class="stat-icon warning">🎫</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= $stats['total_links'] ?? 0 ?></div><div class="stat-label">Short Links</div></div><div class="stat-icon success">🔗</div></div>
    </div>
</div>

<div class="row">
    <div class="col">
        <div class="card animate-in">
            <div class="card-header"><span class="card-title">My Sites</span><a href="<?= BASE_URL ?>user/sites.php" class="btn btn-sm btn-primary">➕ New Site</a></div>
            <?php if (empty($sites)): ?>
            <div class="empty-state" style="padding:24px;"><span class="empty-icon">🌐</span><h3>No sites yet</h3><p>Deploy your first site to get started.</p><a href="<?= BASE_URL ?>user/sites.php" class="btn btn-primary">Create Site</a></div>
            <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Site</th><th>Script</th><th>Status</th><th>Storage</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($sites, 0, 5) as $s): ?>
                <tr>
                    <td><div style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></div><div class="text-small text-muted"><?= date('M j', strtotime($s['created_at'])) ?></div></td>
                    <td class="text-small"><?= htmlspecialchars($s['script_name'] ?? '—') ?></td>
                    <td><?= getStatusBadge($s['status'] ?? 'active') ?></td>
                    <td class="text-small"><?= humanFileSize($s['storage_bytes'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col" style="max-width:360px;">
        <div class="card animate-in">
            <div class="card-header"><span class="card-title">📋 Plan Info</span></div>
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span>Plan</span><strong><?= ucfirst($me['plan'] ?? 'basic') ?></strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span>Sites Allowed</span><strong><?= $plan['sites_limit'] ?? '∞' ?></strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span>Storage</span><strong><?= ($plan['storage_mb'] ?? 0) >= 1000 ? round(($plan['storage_mb'] ?? 0)/1024, 1).'GB' : ($plan['storage_mb'] ?? '∞').'MB' ?></strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span>Short Links</span><strong><?= $plan['links_limit'] ?? '∞' ?></strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span>Expiry</span><strong class="text-<?= $expiryClass ?>"><?= $expiryInfo ?: 'Lifetime' ?></strong></div>
            </div>
        </div>
        <div class="card animate-in" style="margin-top:16px;">
            <div class="card-header"><span class="card-title">Quick Actions</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:8px;">
                <a href="<?= BASE_URL ?>user/sites.php" class="btn btn-secondary">🌐 My Sites</a>
                <a href="<?= BASE_URL ?>user/tickets.php?new=1" class="btn btn-secondary">🎫 Open Ticket</a>
                <a href="<?= BASE_URL ?>user/links.php" class="btn btn-secondary">🔗 Short Links</a>
                <a href="<?= BASE_URL ?>user/backup.php" class="btn btn-secondary">💾 Backups</a>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
