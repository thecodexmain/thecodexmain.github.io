<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['user']);

$baseUrl = hostingGetBaseUrl();
$user = hostingCurrentUser();
$orders = hostingLoadData('orders');
$tickets = hostingLoadData('tickets');

$myOrders = array_values(array_filter($orders, fn($o) => ($o['user_id'] ?? '') === $user['id']));
$myApproved = array_values(array_filter($myOrders, fn($o) => ($o['status'] ?? '') === 'approved'));
$myOpenTickets = array_values(array_filter($tickets, fn($t) => ($t['user_id'] ?? '') === $user['id'] && ($t['status'] ?? 'open') === 'open'));
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
        <div class="text-muted">Manage your services, requests, and support tickets.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-primary" href="<?php echo $baseUrl; ?>/user/services.php"><i class="bi bi-hdd-stack me-1"></i>My Services</a>
        <a class="btn btn-sm btn-primary" href="<?php echo $baseUrl; ?>/user/buy.php"><i class="bi bi-cart-plus me-1"></i>Buy New Service</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Total Requests</div>
                <div class="display-6 fw-bold"><?php echo count($myOrders); ?></div>
                <a class="small fw-semibold" href="<?php echo $baseUrl; ?>/user/services.php">View orders</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Active Services</div>
                <div class="display-6 fw-bold"><?php echo count($myApproved); ?></div>
                <a class="small fw-semibold" href="<?php echo $baseUrl; ?>/user/services.php">View services</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Open Tickets</div>
                <div class="display-6 fw-bold"><?php echo count($myOpenTickets); ?></div>
                <a class="small fw-semibold" href="<?php echo $baseUrl; ?>/user/tickets.php">Open ticket</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-transparent fw-semibold">Recent requests</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr><th>ID</th><th>Plan</th><th>Status</th><th>Requested</th></tr></thead>
                    <tbody>
                    <?php if (empty($myOrders)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No requests yet.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice(array_reverse($myOrders), 0, 5) as $o): ?>
                            <tr>
                                <td class="text-muted"><?php echo htmlspecialchars($o['id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($o['service_name'] ?? ''); ?></td>
                                <td><span class="badge text-bg-<?php echo ($o['status'] ?? '') === 'approved' ? 'success' : (($o['status'] ?? '') === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars(ucfirst($o['status'] ?? 'pending')); ?></span></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($o['requested_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-transparent fw-semibold">Support</div>
            <div class="card-body">
                <p class="text-muted mb-3">Need help? Open a ticket and we’ll reply here and via email.</p>
                <a class="btn btn-outline-primary w-100" href="<?php echo $baseUrl; ?>/user/tickets.php"><i class="bi bi-ticket-perforated me-1"></i>Go to tickets</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

