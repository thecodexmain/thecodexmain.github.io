<?php
require_once __DIR__ . '/../includes/layout.php';
hostingRequireRole('admin');
$users = loadHostingData('users');
$requests = loadHostingData('service_requests');
$services = loadHostingData('services');
$tickets = loadHostingData('tickets');
hostingLayoutStart('Admin Dashboard - CodexHost');
?>
<h2 class="fw-bold mb-4">Admin Dashboard</h2>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card kpi-card"><div class="card-body"><div class="text-muted">Users</div><div class="display-6 fw-bold"><?php echo count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'user')); ?></div></div></div></div>
    <div class="col-md-3"><div class="card kpi-card"><div class="card-body"><div class="text-muted">Pending Requests</div><div class="display-6 fw-bold"><?php echo count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'pending')); ?></div></div></div></div>
    <div class="col-md-3"><div class="card kpi-card"><div class="card-body"><div class="text-muted">Active Services</div><div class="display-6 fw-bold"><?php echo count($services); ?></div></div></div></div>
    <div class="col-md-3"><div class="card kpi-card"><div class="card-body"><div class="text-muted">Open Tickets</div><div class="display-6 fw-bold"><?php echo count(array_filter($tickets, fn($t) => ($t['status'] ?? '') !== 'closed')); ?></div></div></div></div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Recent Service Requests</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>ID</th><th>User</th><th>Plan</th><th>Domain</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No requests yet.</td></tr>
                <?php else: foreach (array_slice(array_reverse($requests), 0, 8) as $r): ?>
                    <tr>
                        <td><?php echo hostingSanitize($r['id']); ?></td>
                        <td><?php echo hostingSanitize($r['user_name']); ?></td>
                        <td><?php echo hostingSanitize($r['plan_name']); ?></td>
                        <td><?php echo hostingSanitize($r['domain']); ?></td>
                        <td><?php echo hostingSanitize(ucfirst($r['status'])); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
