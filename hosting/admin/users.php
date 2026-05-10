<?php
require_once __DIR__ . '/../includes/layout.php';
hostingRequireRole('admin');
$users = array_values(array_filter(loadHostingData('users'), fn($u) => ($u['role'] ?? '') === 'user'));
hostingLayoutStart('Manage Users - CodexHost');
?>
<h2 class="fw-bold mb-4">Users</h2>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">No users found.</td></tr>
                <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo hostingSanitize($u['name']); ?></td>
                        <td><?php echo hostingSanitize($u['email']); ?></td>
                        <td><?php echo hostingSanitize($u['created_at'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
