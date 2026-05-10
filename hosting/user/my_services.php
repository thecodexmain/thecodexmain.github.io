<?php
require_once __DIR__ . '/../includes/layout.php';
hostingRequireRole('user');
$user = hostingGetCurrentUser();
$services = array_values(array_filter(loadHostingData('services'), fn($s) => ($s['user_id'] ?? '') === $user['id']));
hostingLayoutStart('My Services - CodexHost');
?>
<h2 class="fw-bold mb-4">My Services</h2>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Plan</th><th>Domain</th><th>Status</th><th>cPanel URL</th><th>Assigned On</th></tr></thead>
            <tbody>
            <?php if (empty($services)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No active services. <a href="<?php echo hostingGetBaseUrl(); ?>/user/buy_service.php">Buy new service</a>.</td></tr>
            <?php else: foreach (array_reverse($services) as $s): ?>
                <tr>
                    <td><?php echo hostingSanitize($s['plan_name']); ?></td>
                    <td><?php echo hostingSanitize($s['domain']); ?></td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td>
                        <?php $cpanelUrl = hostingSafeUrl($s['cpanel_url'] ?? ''); ?>
                        <?php if ($cpanelUrl !== ''): ?>
                            <a href="<?php echo hostingSanitize($cpanelUrl); ?>" target="_blank" rel="noopener">Open cPanel</a>
                        <?php else: ?>-
                        <?php endif; ?>
                    </td>
                    <td><?php echo hostingSanitize($s['created_at']); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
