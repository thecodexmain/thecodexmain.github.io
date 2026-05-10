<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['admin']);

$baseUrl = hostingGetBaseUrl();
$users = hostingLoadData('users');
$orders = hostingLoadData('orders');
$tickets = hostingLoadData('tickets');

$pendingOrders = array_values(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'pending'));
$openTickets = array_values(array_filter($tickets, fn($t) => ($t['status'] ?? 'open') === 'open'));
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Admin Dashboard</h1>
        <div class="text-muted">Approve requests, manage users, and respond to tickets.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-primary" href="<?php echo $baseUrl; ?>/admin/orders.php"><i class="bi bi-clipboard-check me-1"></i>Pending Orders</a>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo $baseUrl; ?>/admin/tickets.php"><i class="bi bi-ticket-perforated me-1"></i>Tickets</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Users</div>
                <div class="display-6 fw-bold"><?php echo count($users); ?></div>
                <a class="small fw-semibold" href="<?php echo $baseUrl; ?>/admin/users.php">Manage users</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Pending Orders</div>
                <div class="display-6 fw-bold"><?php echo count($pendingOrders); ?></div>
                <a class="small fw-semibold" href="<?php echo $baseUrl; ?>/admin/orders.php">Review requests</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Open Tickets</div>
                <div class="display-6 fw-bold"><?php echo count($openTickets); ?></div>
                <a class="small fw-semibold" href="<?php echo $baseUrl; ?>/admin/tickets.php">Respond</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-transparent fw-semibold">Quick links</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="<?php echo $baseUrl; ?>/admin/services.php"><i class="bi bi-bag me-2"></i>Manage services</a>
                <a class="list-group-item list-group-item-action" href="<?php echo $baseUrl; ?>/admin/users.php"><i class="bi bi-people me-2"></i>Manage users</a>
                <a class="list-group-item list-group-item-action" href="<?php echo $baseUrl; ?>/admin/settings.php"><i class="bi bi-gear me-2"></i>Mail & site settings</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-transparent fw-semibold">Approval workflow</div>
            <div class="card-body text-muted">
                <ol class="mb-0">
                    <li>User requests a plan</li>
                    <li>Admin approves and enters cPanel details</li>
                    <li>System sends credentials via email and displays in user dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

