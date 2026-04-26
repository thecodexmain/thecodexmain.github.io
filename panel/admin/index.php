<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/json_db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');
$currentUser = getCurrentUser();
$settings = getSettings();

$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';

// Stats
$users = getUsers();
$resellers = getResellers();
$plans = getPlans();
$scripts = getScripts();
$sites = getSites();
$tickets = getTickets();

$totalUsers = count(array_filter($users, fn($u) => $u['role'] === 'user'));
$activeUsers = count(array_filter($users, fn($u) => $u['role'] === 'user' && $u['status'] === 'active'));
$totalResellers = count($resellers);
$totalSites = count($sites);
$openTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'open'));

// Recent users
$recentUsers = array_slice(array_reverse($users), 0, 5);

// Recent logs
$recentLogs = getLogs(10);

include __DIR__ . '/../includes/header.php';
?>
<div class="panel-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <div class="page-content">
            <!-- Flash messages -->
            <?php $flash = getFlash(); if (!empty($flash['success'])): ?>
            <div class="alert alert-success" data-auto-dismiss="5000"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; if (!empty($flash['error'])): ?>
            <div class="alert alert-danger" data-auto-dismiss="5000"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?= $totalUsers ?></div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> <?= $activeUsers ?> active</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-handshake"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Resellers</div>
                        <div class="stat-value"><?= $totalResellers ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-globe"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Sites Deployed</div>
                        <div class="stat-value"><?= $totalSites ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-code"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Scripts</div>
                        <div class="stat-value"><?= count($scripts) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-ticket-alt"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Open Tickets</div>
                        <div class="stat-value"><?= $openTickets ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-tags"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Active Plans</div>
                        <div class="stat-value"><?= count(array_filter($plans, fn($p) => $p['active'])) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="grid-2">
                <!-- Recent Users -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-users text-primary"></i> Recent Users</div>
                        <a href="/panel/admin/users.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="table-wrapper">
                        <table id="recentUsersTable">
                            <thead><tr><th>User</th><th>Plan</th><th>Status</th><th>Joined</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentUsers as $user): if ($user['role'] !== 'user') continue; ?>
                            <tr>
                                <td>
                                    <div style="font-weight:500;"><?= htmlspecialchars($user['username']) ?></div>
                                    <div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($user['email']) ?></div>
                                </td>
                                <td><span class="badge badge-info"><?= htmlspecialchars($user['plan'] ?? 'basic') ?></span></td>
                                <td><span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>"><?= $user['status'] ?></span></td>
                                <td><?= timeAgo($user['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!array_filter($users, fn($u) => $u['role'] === 'user')): ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding:2rem;">No users yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-list-alt text-primary"></i> Recent Activity</div>
                        <a href="/panel/admin/logs.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div style="max-height:320px;overflow-y:auto;">
                    <?php if (empty($recentLogs)): ?>
                    <div class="empty-state" style="padding:2rem;">
                        <i class="fas fa-inbox"></i>
                        <p>No activity yet.</p>
                    </div>
                    <?php else: foreach ($recentLogs as $log): ?>
                    <div class="notif-item">
                        <div class="notif-icon" style="background:var(--primary-light);color:var(--primary);">
                            <i class="fas fa-<?= $log['type'] === 'auth' ? 'sign-in-alt' : ($log['type'] === 'script' ? 'code' : 'info') ?>"></i>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title"><?= htmlspecialchars($log['action']) ?> <span class="text-muted">by</span> <?= htmlspecialchars($log['user_id']) ?></div>
                            <div class="notif-time"><?= timeAgo($log['created_at']) ?> &bull; <?= htmlspecialchars($log['ip']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-bolt text-primary"></i> Quick Actions</div>
                </div>
                <div class="card-body" style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <a href="/panel/admin/users.php?action=create" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</a>
                    <a href="/panel/admin/scripts.php?action=upload" class="btn btn-success"><i class="fas fa-upload"></i> Upload Script</a>
                    <a href="/panel/admin/plans.php?action=create" class="btn btn-warning"><i class="fas fa-plus"></i> Create Plan</a>
                    <a href="/panel/admin/notifications.php?action=broadcast" class="btn btn-info"><i class="fas fa-broadcast-tower"></i> Broadcast</a>
                    <a href="/panel/admin/backup.php" class="btn btn-secondary"><i class="fas fa-database"></i> Backups</a>
                    <a href="/panel/admin/settings.php" class="btn btn-outline"><i class="fas fa-cog"></i> Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
