<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$stats = getDashboardStats('admin');
$users = readJson(DATA_PATH . 'users.json');
$logs = readJson(DATA_PATH . 'logs.json');
$recentUsers = array_slice(array_reverse($users), 0, 5);
$recentLogs = array_slice(array_reverse($logs), 0, 10);

renderHead('Dashboard');
renderSidebar('admin', 'dashboard');
renderTopbar('Dashboard');
?>
<div class="stats-grid animate-in">
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-value"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-icon primary">👥</div>
        </div>
        <div class="text-small text-muted"><?= $stats['active_users'] ?> active</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-value"><?= $stats['total_resellers'] ?></div>
                <div class="stat-label">Resellers</div>
            </div>
            <div class="stat-icon success">🤝</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-value"><?= $stats['total_sites'] ?></div>
                <div class="stat-label">Hosted Sites</div>
            </div>
            <div class="stat-icon info">🌐</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-value"><?= $stats['open_tickets'] ?></div>
                <div class="stat-label">Open Tickets</div>
            </div>
            <div class="stat-icon warning">🎫</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div>
                <div class="stat-value"><?= $stats['total_scripts'] ?></div>
                <div class="stat-label">Scripts</div>
            </div>
            <div class="stat-icon danger">🚀</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col">
        <div class="card animate-in">
            <div class="card-header">
                <span class="card-title">Recent Users</span>
                <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>User</th><th>Role</th><th>Plan</th><th>Status</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentUsers)): ?>
                    <tr><td colspan="5" class="text-center text-muted" style="padding:20px;">No users found</td></tr>
                    <?php else: foreach ($recentUsers as $u): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></div>
                            <div class="text-small text-muted"><?= htmlspecialchars($u['email']) ?></div>
                        </td>
                        <td><span class="badge-status badge-info"><?= ucfirst($u['role']) ?></span></td>
                        <td><?= ucfirst($u['plan'] ?? 'basic') ?></td>
                        <td><?= getStatusBadge($u['status'] ?? 'active') ?></td>
                        <td class="text-muted text-small"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card animate-in">
            <div class="card-header">
                <span class="card-title">Recent Activity</span>
                <a href="<?= BASE_URL ?>admin/logs.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <div style="max-height:320px;overflow-y:auto;">
            <?php if (empty($recentLogs)): ?>
            <div style="padding:20px;text-align:center;color:var(--text-muted);">No logs yet</div>
            <?php else: foreach ($recentLogs as $log): ?>
            <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:flex-start;">
                <div style="width:8px;height:8px;border-radius:50%;background:<?= $log['level']==='error'?'var(--danger)':($log['level']==='warning'?'var(--warning)':'var(--success)') ?>;margin-top:6px;flex-shrink:0;"></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($log['action']) ?></div>
                    <div class="text-small text-muted truncate"><?= htmlspecialchars($log['user'] . ': ' . $log['details']) ?></div>
                    <div class="text-small text-muted"><?= timeAgo($log['timestamp']) ?> · <?= htmlspecialchars($log['ip']) ?></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card animate-in">
    <div class="card-header"><span class="card-title">Quick Actions</span></div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <a href="<?= BASE_URL ?>admin/users.php?action=create" class="btn btn-primary">➕ Add User</a>
            <a href="<?= BASE_URL ?>admin/scripts.php?action=upload" class="btn btn-success">📤 Upload Script</a>
            <a href="<?= BASE_URL ?>admin/tickets.php" class="btn btn-warning">🎫 View Tickets</a>
            <a href="<?= BASE_URL ?>admin/settings.php" class="btn btn-secondary">⚙️ Settings</a>
            <a href="<?= BASE_URL ?>admin/backup.php" class="btn btn-secondary">💾 Backups</a>
            <a href="<?= BASE_URL ?>admin/maintenance.php" class="btn btn-secondary">🔧 Maintenance</a>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
