<?php
require_once __DIR__ . '/../includes/layout.php';
hostingRequireRole('user');
$user = hostingGetCurrentUser();
$requests = array_values(array_filter(loadHostingData('service_requests'), fn($r) => ($r['user_id'] ?? '') === $user['id']));
$services = array_values(array_filter(loadHostingData('services'), fn($s) => ($s['user_id'] ?? '') === $user['id']));
$tickets = array_values(array_filter(loadHostingData('tickets'), fn($t) => ($t['user_id'] ?? '') === $user['id']));
hostingLayoutStart('User Dashboard - CodexHost');
?>
<h2 class="fw-bold mb-4">Client Dashboard</h2>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card kpi-card"><div class="card-body"><div class="text-muted">Active Services</div><div class="display-6 fw-bold"><?php echo count($services); ?></div></div></div></div>
    <div class="col-md-4"><div class="card kpi-card"><div class="card-body"><div class="text-muted">Pending Requests</div><div class="display-6 fw-bold"><?php echo count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'pending')); ?></div></div></div></div>
    <div class="col-md-4"><div class="card kpi-card"><div class="card-body"><div class="text-muted">Open Tickets</div><div class="display-6 fw-bold"><?php echo count(array_filter($tickets, fn($t) => ($t['status'] ?? '') !== 'closed')); ?></div></div></div></div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Recent Service Requests</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Plan</th><th>Domain</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">No requests yet. <a href="<?php echo hostingGetBaseUrl(); ?>/user/buy_service.php">Buy your first service</a>.</td></tr>
            <?php else: foreach (array_reverse($requests) as $r): ?>
                <tr>
                    <td><?php echo hostingSanitize($r['plan_name']); ?></td>
                    <td><?php echo hostingSanitize($r['domain']); ?></td>
                    <td><span class="badge bg-<?php echo ($r['status'] === 'approved') ? 'success' : (($r['status'] === 'rejected') ? 'danger' : 'warning text-dark'); ?>"><?php echo ucfirst(hostingSanitize($r['status'])); ?></span></td>
                    <td><?php echo hostingSanitize($r['created_at']); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
