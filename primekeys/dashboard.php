<?php
require_once __DIR__ . '/includes/auth.php';
primeRequireLogin();
primeSyncDataState();

$currentUser = primeGetCurrentUser();
$settings = primeGetSettings();
$stats = primeGetDashboardStats($currentUser);
$keys = array_slice(array_reverse(primeFilterOwnedRecords(primeLoadData('keys'), $currentUser)), 0, 5);
$endUsers = array_slice(array_reverse(primeFilterOwnedRecords(primeLoadData('end_users'), $currentUser)), 0, 5);
$logs = primeGetRecentLogs($currentUser, 8);
$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-speedometer2 text-theme"></i> Dashboard</h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>.</p>
        </div>
        <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo date('l, d F Y'); ?></div>
    </div>

    <?php echo primeRenderFlash(); ?>

    <?php if (primePortalLockedFor($currentUser)): ?>
        <div class="alert alert-warning maintenance-banner mb-4">
            <strong>Maintenance mode is enabled.</strong> Owner settings remain visible, but reseller write actions are currently blocked.
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card stat-card" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="small text-white-50 fw-semibold">TOTAL KEYS</div><div class="stat-value"><?php echo $stats['total_keys']; ?></div></div>
                    <i class="bi bi-key stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="small text-white-50 fw-semibold">UNUSED KEYS</div><div class="stat-value"><?php echo $stats['unused_keys']; ?></div></div>
                    <i class="bi bi-unlock stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card" style="background:linear-gradient(135deg,#fd7e14,#dc6a00)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="small text-white-50 fw-semibold">USED KEYS</div><div class="stat-value"><?php echo $stats['used_keys']; ?></div></div>
                    <i class="bi bi-check2-circle stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card" style="background:linear-gradient(135deg,#dc3545,#b02a37)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="small text-white-50 fw-semibold">EXPIRED</div><div class="stat-value"><?php echo $stats['expired_keys']; ?></div></div>
                    <i class="bi bi-hourglass-split stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="status-tile h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Registered Users</span>
                    <span class="badge bg-primary"><?php echo $stats['end_users']; ?></span>
                </div>
                <div class="text-muted small">Users tied to your key inventory.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="status-tile h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Expiring Soon</span>
                    <span class="badge bg-warning text-dark"><?php echo $stats['expiring_users']; ?></span>
                </div>
                <div class="text-muted small">Users expiring in the next 3 days.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="status-tile h-100">
                <?php if (($currentUser['role'] ?? '') === 'owner'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold">Reseller Network</span>
                        <span class="badge bg-dark"><?php echo $stats['resellers']; ?></span>
                    </div>
                    <div class="small text-muted">Combined reseller balance: <strong>₹<?php echo number_format($stats['reseller_balance'], 2); ?></strong></div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold">Current Balance</span>
                        <span class="badge bg-success">₹<?php echo number_format((float)($currentUser['balance'] ?? 0), 2); ?></span>
                    </div>
                    <div class="small text-muted">Live price per day: <strong>₹<?php echo number_format((float)$settings['price_per_day'], 2); ?></strong></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-broadcast"></i> Online Controls</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Brand</span><strong><?php echo htmlspecialchars($settings['brand_name']); ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Mod Name</span><strong><?php echo htmlspecialchars($settings['mod_name']); ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Mod Status</span><strong><?php echo htmlspecialchars($settings['mod_status']); ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Library</span><strong class="text-<?php echo !empty($settings['library_online']) ? 'success' : 'danger'; ?>"><?php echo !empty($settings['library_online']) ? 'Online' : 'Offline'; ?></strong></div>
                    <div class="d-flex justify-content-between mb-3"><span>Referral Mode</span><strong><?php echo ($settings['referral_mode'] ?? 'single_use') === 'unlimited' ? 'Unlimited' : 'Single Use'; ?></strong></div>
                    <div class="small text-muted fw-semibold mb-2">Feature Toggles</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach (($settings['features'] ?? []) as $feature => $enabled): ?>
                            <span class="badge bg-<?php echo $enabled ? 'success' : 'secondary'; ?>"><?php echo ucwords(str_replace('_', ' ', $feature)); ?> <?php echo $enabled ? 'ON' : 'OFF'; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-clock-history"></i> Recent Activity</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>When</th><th>Action</th><th>Message</th></tr></thead>
                            <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">No activity recorded.</td></tr>
                            <?php else: foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(primeFormatDateTime($log['created_at'] ?? '')); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log['action'] ?? 'activity'))); ?></span></td>
                                    <td><?php echo htmlspecialchars($log['message'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-key"></i> Recent Keys</span>
                    <a href="<?php echo primeGetBaseUrl(); ?>/keys.php" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Key</th><th>Type</th><th>Status</th><th>Expiry</th></tr></thead>
                            <tbody>
                            <?php if (empty($keys)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No keys available.</td></tr>
                            <?php else: foreach ($keys as $key): ?>
                                <tr>
                                    <td><span class="code-pill"><?php echo htmlspecialchars($key['key_code']); ?></span></td>
                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key['generator_type'] ?? 'random'))); ?></td>
                                    <td><span class="badge bg-<?php echo primeStatusBadgeClass($key['status'] ?? 'unused'); ?>"><?php echo htmlspecialchars(ucfirst($key['status'] ?? 'unused')); ?></span></td>
                                    <td><?php echo htmlspecialchars(primeFormatDate($key['expires_at'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people"></i> Recent Users</span>
                    <a href="<?php echo primeGetBaseUrl(); ?>/users.php" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>User</th><th>Referral</th><th>Key</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (empty($endUsers)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No registered users yet.</td></tr>
                            <?php else: foreach ($endUsers as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar bg-primary text-white"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="code-pill"><?php echo htmlspecialchars($user['referral_code'] ?? '-'); ?></span></td>
                                    <td><?php echo htmlspecialchars($user['key_id'] ?: '-'); ?></td>
                                    <td><span class="badge bg-<?php echo primeStatusBadgeClass($user['status'] ?? 'active'); ?>"><?php echo htmlspecialchars(ucfirst($user['status'] ?? 'active')); ?></span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
