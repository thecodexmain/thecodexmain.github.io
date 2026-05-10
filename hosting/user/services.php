<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['user']);

$baseUrl = hostingGetBaseUrl();
$user = hostingCurrentUser();

$orders = hostingLoadData('orders');
$myOrders = array_values(array_filter($orders, fn($o) => ($o['user_id'] ?? '') === $user['id']));
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">My Services</h1>
        <div class="text-muted">Requests and approved services (with cPanel details).</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/user/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <a class="btn btn-sm btn-primary" href="<?php echo $baseUrl; ?>/user/buy.php"><i class="bi bi-cart-plus me-1"></i>Buy New Service</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Order</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Domain</th>
                <th>cPanel</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($myOrders)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No requests yet. <a href="<?php echo $baseUrl; ?>/user/buy.php">Buy a service</a>.</td></tr>
            <?php else: ?>
                <?php foreach (array_reverse($myOrders) as $o): ?>
                    <tr>
                        <td class="text-muted small"><?php echo htmlspecialchars($o['id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($o['service_name'] ?? ''); ?></td>
                        <td><span class="badge text-bg-<?php echo ($o['status'] ?? '') === 'approved' ? 'success' : (($o['status'] ?? '') === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars(ucfirst($o['status'] ?? 'pending')); ?></span></td>
                        <td class="text-muted"><?php echo htmlspecialchars($o['domain'] ?? '-'); ?></td>
                        <td>
                            <?php if (($o['status'] ?? '') === 'approved' && !empty($o['cpanel']['url'])): ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($o['cpanel']['url']); ?>" target="_blank" rel="noreferrer"><i class="bi bi-box-arrow-up-right me-1"></i>Login</a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (($o['status'] ?? '') === 'approved' && !empty($o['cpanel'])): ?>
                        <tr class="table-light">
                            <td colspan="5" class="small">
                                <div class="row g-2">
                                    <div class="col-md-3"><span class="text-muted">URL:</span> <?php echo htmlspecialchars($o['cpanel']['url'] ?? '-'); ?></div>
                                    <div class="col-md-3"><span class="text-muted">Username:</span> <?php echo htmlspecialchars($o['cpanel']['username'] ?? '-'); ?></div>
                                    <div class="col-md-3"><span class="text-muted">Password:</span> <?php echo htmlspecialchars($o['cpanel']['password'] ?? '-'); ?></div>
                                    <div class="col-md-3"><span class="text-muted">Nameservers:</span> <?php echo htmlspecialchars($o['cpanel']['nameservers'] ?? '-'); ?></div>
                                </div>
                                <?php if (!empty($o['admin_note'])): ?>
                                    <div class="mt-2"><span class="text-muted">Admin note:</span> <?php echo nl2br(htmlspecialchars($o['admin_note'] ?? '')); ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php elseif (($o['status'] ?? '') === 'rejected' && !empty($o['rejection_reason'])): ?>
                        <tr class="table-light">
                            <td colspan="5" class="small">
                                <span class="text-muted">Rejection reason:</span> <?php echo nl2br(htmlspecialchars($o['rejection_reason'] ?? '')); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
