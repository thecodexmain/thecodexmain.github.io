<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
hostingRequireRole(['admin']);
$baseUrl = hostingGetBaseUrl();

$orders = hostingLoadData('orders');
$services = hostingEnsureDefaultServices();
$users = hostingLoadData('users');

function hostingOrderBadge(string $status): array {
    return match ($status) {
        'approved' => ['success', 'Approved'],
        'rejected' => ['danger', 'Rejected'],
        default => ['warning', 'Pending'],
    };
}

$orderId = (string)($_GET['id'] ?? '');
$current = null;
if ($orderId !== '') {
    foreach ($orders as $o) {
        if (($o['id'] ?? '') === $orderId) { $current = $o; break; }
    }
    if (!$current) {
        hostingSetFlash('error', 'Order not found.');
        header('Location: ' . hostingGetBaseUrl() . '/admin/orders.php');
        exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $action = (string)($_POST['action'] ?? '');
    $id = (string)($_POST['order_id'] ?? '');

    $idx = null;
    foreach ($orders as $i => $o) {
        if (($o['id'] ?? '') === $id) { $idx = $i; break; }
    }
    if ($idx === null) {
        $error = 'Order not found.';
    } else {
        if ($action === 'approve') {
            $url = trim((string)($_POST['cpanel_url'] ?? ''));
            $username = trim((string)($_POST['cpanel_username'] ?? ''));
            $password = trim((string)($_POST['cpanel_password'] ?? ''));
            $nameservers = trim((string)($_POST['cpanel_nameservers'] ?? ''));
            $note = trim((string)($_POST['admin_note'] ?? ''));

            if ($url === '' || $username === '' || $password === '') {
                $error = 'cPanel URL, username, and password are required to approve.';
            } elseif (!preg_match('#^https?://#i', $url)) {
                $error = 'cPanel URL must start with http:// or https://';
            } else {
                $orders[$idx]['status'] = 'approved';
                $orders[$idx]['approved_at'] = hostingNow();
                $orders[$idx]['updated_at'] = hostingNow();
                $orders[$idx]['admin_note'] = $note;
                $orders[$idx]['cpanel'] = [
                    'url' => $url,
                    'username' => $username,
                    'password' => $password,
                    'nameservers' => $nameservers,
                ];
                hostingSaveData('orders', $orders);
                $target = hostingFindUser((string)($orders[$idx]['user_id'] ?? ''));
                if ($target && !empty($target['email'])) {
                    $settings = hostingGetSettings();
                    $body = "Hi " . ($target['name'] ?? 'there') . ",\n\nYour order has been approved.\n\nPlan: " . ($orders[$idx]['service_name'] ?? '') . "\nOrder: " . ($orders[$idx]['id'] ?? '') . "\n\ncPanel URL: {$url}\nUsername: {$username}\nPassword: {$password}\nNameservers: " . ($nameservers ?: '-') . "\n\n" . ($note ? ("Admin note:\n{$note}\n\n") : "") . "Login anytime from your dashboard.\n\nThanks,\n" . ($settings['brand_name'] ?? 'CodexHost');
                    hostingQueueMail((string)$target['email'], 'Your hosting is ready: ' . ($orders[$idx]['service_name'] ?? ''), $body);
                    hostingDispatchMailQueue(5);
                }
                hostingSetFlash('success', 'Order approved and cPanel details saved.');
                header('Location: ' . hostingGetBaseUrl() . '/admin/orders.php?id=' . urlencode($id));
                exit;
            }
        }

        if ($action === 'reject') {
            $reason = trim((string)($_POST['reason'] ?? ''));
            if ($reason === '') $reason = 'Rejected by admin.';
            $orders[$idx]['status'] = 'rejected';
            $orders[$idx]['updated_at'] = hostingNow();
            $orders[$idx]['rejection_reason'] = $reason;
            hostingSaveData('orders', $orders);
            $target = hostingFindUser((string)($orders[$idx]['user_id'] ?? ''));
            if ($target && !empty($target['email'])) {
                $settings = hostingGetSettings();
                $body = "Hi " . ($target['name'] ?? 'there') . ",\n\nYour order was rejected.\n\nPlan: " . ($orders[$idx]['service_name'] ?? '') . "\nOrder: " . ($orders[$idx]['id'] ?? '') . "\nReason: {$reason}\n\nIf you have questions, open a ticket from your dashboard.\n\nThanks,\n" . ($settings['brand_name'] ?? 'CodexHost');
                hostingQueueMail((string)$target['email'], 'Order update: ' . ($orders[$idx]['service_name'] ?? ''), $body);
                hostingDispatchMailQueue(5);
            }
            hostingSetFlash('success', 'Order rejected.');
            header('Location: ' . hostingGetBaseUrl() . '/admin/orders.php?id=' . urlencode($id));
            exit;
        }
    }
}
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Orders</h1>
        <div class="text-muted">Approve requests and provide cPanel details.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($current): ?>
    <?php
    $badge = hostingOrderBadge((string)($current['status'] ?? 'pending'));
    $u = null;
    foreach ($users as $user) { if (($user['id'] ?? '') === ($current['user_id'] ?? '')) { $u = $user; break; } }
    ?>
    <div class="card mb-3">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <div class="fw-semibold">
                Order <?php echo htmlspecialchars($current['id'] ?? ''); ?>
                <span class="badge ms-2 text-bg-<?php echo $badge[0]; ?>"><?php echo htmlspecialchars($badge[1]); ?></span>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/orders.php"><i class="bi bi-list me-1"></i>All orders</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small">Customer</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($u['name'] ?? ($current['user_id'] ?? '')); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($u['email'] ?? ''); ?></div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Plan</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($current['service_name'] ?? ''); ?></div>
                    <div class="text-muted small">Requested: <?php echo htmlspecialchars($current['requested_at'] ?? ''); ?></div>
                </div>
                <div class="col-12">
                    <div class="text-muted small">Domain</div>
                    <div><?php echo htmlspecialchars($current['domain'] ?? '-'); ?></div>
                </div>
                <?php if (!empty($current['notes'])): ?>
                    <div class="col-12">
                        <div class="text-muted small">Notes</div>
                        <div><?php echo nl2br(htmlspecialchars($current['notes'] ?? '')); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (($current['status'] ?? 'pending') === 'pending'): ?>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-transparent fw-semibold"><i class="bi bi-check2-circle me-1 text-success"></i>Approve and add cPanel details</div>
                    <div class="card-body">
                        <form method="post" action="" class="row g-2">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($current['id'] ?? ''); ?>">
                            <div class="col-12">
                                <label class="form-label fw-semibold">cPanel URL</label>
                                <input class="form-control" name="cpanel_url" placeholder="https://server.example.com:2083" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Username</label>
                                <input class="form-control" name="cpanel_username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <input class="form-control" name="cpanel_password" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nameservers (optional)</label>
                                <input class="form-control" name="cpanel_nameservers" placeholder="ns1.example.com, ns2.example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Admin note (optional)</label>
                                <textarea class="form-control" name="admin_note" rows="3" placeholder="Welcome message / setup instructions"></textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-success" type="submit"><i class="bi bi-check2-circle me-1"></i>Approve</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header bg-transparent fw-semibold"><i class="bi bi-x-circle me-1 text-danger"></i>Reject request</div>
                    <div class="card-body">
                        <form method="post" action="" class="row g-2">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($current['id'] ?? ''); ?>">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Reason</label>
                                <textarea class="form-control" name="reason" rows="4" placeholder="Why is this rejected?"></textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-outline-danger" type="submit"><i class="bi bi-x-circle me-1"></i>Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-transparent fw-semibold">cPanel details</div>
            <div class="card-body small">
                <?php if (($current['status'] ?? '') === 'approved'): ?>
                    <div class="row g-2">
                        <div class="col-md-3"><span class="text-muted">URL:</span> <?php echo htmlspecialchars($current['cpanel']['url'] ?? '-'); ?></div>
                        <div class="col-md-3"><span class="text-muted">Username:</span> <?php echo htmlspecialchars($current['cpanel']['username'] ?? '-'); ?></div>
                        <div class="col-md-3"><span class="text-muted">Password:</span> <?php echo htmlspecialchars($current['cpanel']['password'] ?? '-'); ?></div>
                        <div class="col-md-3"><span class="text-muted">Nameservers:</span> <?php echo htmlspecialchars($current['cpanel']['nameservers'] ?? '-'); ?></div>
                    </div>
                    <?php if (!empty($current['admin_note'])): ?>
                        <hr>
                        <div><span class="text-muted">Admin note:</span> <?php echo nl2br(htmlspecialchars($current['admin_note'] ?? '')); ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-muted">Rejected: <?php echo htmlspecialchars($current['rejection_reason'] ?? ''); ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?php
    usort($orders, function ($a, $b) {
        $pa = ($a['status'] ?? 'pending') === 'pending' ? 0 : 1;
        $pb = ($b['status'] ?? 'pending') === 'pending' ? 0 : 1;
        if ($pa !== $pb) return $pa <=> $pb;
        return strcmp((string)($b['requested_at'] ?? ''), (string)($a['requested_at'] ?? ''));
    });
    ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Order</th><th>Customer</th><th>Plan</th><th>Status</th><th>Requested</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <?php
                        $badge = hostingOrderBadge((string)($o['status'] ?? 'pending'));
                        $uName = '';
                        foreach ($users as $u) { if (($u['id'] ?? '') === ($o['user_id'] ?? '')) { $uName = $u['name'] ?? ''; break; } }
                        ?>
                        <tr>
                            <td class="text-muted small"><?php echo htmlspecialchars($o['id'] ?? ''); ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($uName ?: ($o['user_id'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($o['service_name'] ?? ''); ?></td>
                            <td><span class="badge text-bg-<?php echo $badge[0]; ?>"><?php echo htmlspecialchars($badge[1]); ?></span></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($o['requested_at'] ?? ''); ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo $baseUrl; ?>/admin/orders.php?id=<?php echo urlencode($o['id'] ?? ''); ?>">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
