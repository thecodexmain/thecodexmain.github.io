<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin', 'reseller');

$userId = $_SESSION['user_id'];
$stats = getDashboardStats('reseller', $userId);
$resellers = readJson(DATA_PATH . 'resellers.json');
$me = findById($resellers, $userId);
$users = readJson(DATA_PATH . 'users.json');
$myUsers = array_filter($users, fn($u) => ($u['reseller_id'] ?? '') === $userId);

renderHead('Dashboard');
renderSidebar('reseller', 'dashboard');
renderTopbar('Reseller Dashboard');
?>
<div class="stats-grid animate-in">
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= $stats['my_users'] ?></div><div class="stat-label">My Users</div></div><div class="stat-icon primary">👥</div></div>
        <div class="text-small text-muted"><?= $stats['active_users'] ?> active</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= $stats['open_tickets'] ?></div><div class="stat-label">Open Tickets</div></div><div class="stat-icon warning">🎫</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><div><div class="stat-value"><?= number_format($stats['credits']) ?></div><div class="stat-label">Credits</div></div><div class="stat-icon success">💰</div></div>
    </div>
</div>

<div class="row">
    <div class="col">
        <div class="card animate-in">
            <div class="card-header"><span class="card-title">My Users</span><a href="<?= BASE_URL ?>reseller/users.php" class="btn btn-sm btn-secondary">Manage</a></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>User</th><th>Plan</th><th>Status</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php if (empty($myUsers)): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding:20px;">No users yet</td></tr>
                    <?php else: foreach (array_slice(array_values($myUsers), 0, 5) as $u): ?>
                    <tr>
                        <td><div style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></div><div class="text-small text-muted"><?= htmlspecialchars($u['email']) ?></div></td>
                        <td><?= ucfirst($u['plan'] ?? 'basic') ?></td>
                        <td><?= getStatusBadge($u['status'] ?? 'active') ?></td>
                        <td class="text-small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col" style="max-width:360px;">
        <div class="card animate-in">
            <div class="card-header"><span class="card-title">💰 Wallet</span><a href="<?= BASE_URL ?>reseller/wallet.php" class="btn btn-sm btn-secondary">Details</a></div>
            <div class="card-body" style="text-align:center;">
                <div style="font-size:48px;font-weight:800;color:var(--primary);"><?= number_format($stats['credits']) ?></div>
                <div style="color:var(--text-muted);font-size:14px;margin-bottom:16px;">Available Credits</div>
                <a href="<?= BASE_URL ?>reseller/users.php?action=create" class="btn btn-primary btn-block">➕ Create User</a>
            </div>
        </div>
        <div class="card animate-in" style="margin-top:16px;">
            <div class="card-header"><span class="card-title">Quick Actions</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:8px;">
                <a href="<?= BASE_URL ?>reseller/users.php" class="btn btn-secondary">👥 Manage Users</a>
                <a href="<?= BASE_URL ?>reseller/tickets.php" class="btn btn-secondary">🎫 View Tickets</a>
                <a href="<?= BASE_URL ?>reseller/wallet.php" class="btn btn-secondary">💰 Wallet</a>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
