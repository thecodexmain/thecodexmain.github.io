<?php
require_once __DIR__ . '/../includes/layout.php';
hostingRequireRole('admin');
$admin = hostingGetCurrentUser();
$requests = loadHostingData('service_requests');
$services = loadHostingData('services');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = trim($_POST['request_id'] ?? '');

    foreach ($requests as &$req) {
        if (($req['id'] ?? '') === $requestId && ($req['status'] ?? '') === 'pending') {
            if ($action === 'approve') {
                $cpanelUrl = hostingSafeUrl($_POST['cpanel_url'] ?? '');
                $cpanelUser = trim($_POST['cpanel_user'] ?? '');
                $cpanelPass = trim($_POST['cpanel_pass'] ?? '');
                $nameservers = trim($_POST['nameservers'] ?? '');

                if ($cpanelUser === '' || $cpanelPass === '') {
                    hostingSetFlash('danger', 'cPanel username and password are required for approval.');
                    header('Location: ' . hostingGetBaseUrl() . '/admin/requests.php');
                    exit;
                }

                $req['status'] = 'approved';
                $req['processed_at'] = date('Y-m-d H:i:s');
                $req['approved_by'] = $admin['name'];
                $req['cpanel_url'] = $cpanelUrl;
                $req['cpanel_user'] = $cpanelUser;
                $req['cpanel_pass'] = $cpanelPass;
                $req['nameservers'] = $nameservers;

                $services[] = [
                    'id' => hostingGenerateId('SRV-'),
                    'request_id' => $req['id'],
                    'user_id' => $req['user_id'],
                    'user_name' => $req['user_name'],
                    'user_email' => $req['user_email'],
                    'plan_name' => $req['plan_name'],
                    'domain' => $req['domain'],
                    'cpanel_url' => $cpanelUrl,
                    'cpanel_user' => $cpanelUser,
                    'cpanel_pass' => $cpanelPass,
                    'nameservers' => $nameservers,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $message = "Hi {$req['user_name']},\n\nYour hosting request {$req['id']} has been approved.\n\nDomain: {$req['domain']}\nPlan: {$req['plan_name']}\n\ncPanel URL: {$cpanelUrl}\nUsername: {$cpanelUser}\nPassword: {$cpanelPass}\nNameservers: {$nameservers}\n\nRegards,\nCodexHost Team";
                hostingSendAutoMail($req['user_email'], 'Hosting request approved with cPanel details', $message, ['event' => 'request_approved', 'request_id' => $req['id']]);
                hostingSetFlash('success', "Request {$req['id']} approved and service activated.");
            }

            if ($action === 'reject') {
                $req['status'] = 'rejected';
                $req['processed_at'] = date('Y-m-d H:i:s');
                $req['approved_by'] = $admin['name'];
                hostingSendAutoMail($req['user_email'], 'Hosting request update', "Hi {$req['user_name']},\n\nYour request {$req['id']} was reviewed and is currently rejected. Please contact support for details.", ['event' => 'request_rejected', 'request_id' => $req['id']]);
                hostingSetFlash('warning', "Request {$req['id']} rejected.");
            }
            break;
        }
    }
    unset($req);

    saveHostingData('service_requests', $requests);
    saveHostingData('services', $services);
    header('Location: ' . hostingGetBaseUrl() . '/admin/requests.php');
    exit;
}

hostingLayoutStart('Manage Requests - CodexHost');
?>
<h2 class="fw-bold mb-4">Service Requests</h2>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Request</th><th>User</th><th>Plan/Domain</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No requests available.</td></tr>
            <?php else: foreach (array_reverse($requests) as $r): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?php echo hostingSanitize($r['id']); ?></div>
                        <small class="text-muted"><?php echo hostingSanitize($r['created_at']); ?></small>
                    </td>
                    <td><?php echo hostingSanitize($r['user_name']); ?><br><small class="text-muted"><?php echo hostingSanitize($r['user_email']); ?></small></td>
                    <td><?php echo hostingSanitize($r['plan_name']); ?><br><small class="text-muted"><?php echo hostingSanitize($r['domain']); ?></small></td>
                    <td><span class="badge bg-<?php echo ($r['status'] === 'approved') ? 'success' : (($r['status'] === 'rejected') ? 'danger' : 'warning text-dark'); ?>"><?php echo ucfirst(hostingSanitize($r['status'])); ?></span></td>
                    <td>
                        <?php if (($r['status'] ?? '') === 'pending'): ?>
                            <form method="POST" class="mb-2">
                                <input type="hidden" name="request_id" value="<?php echo hostingSanitize($r['id']); ?>">
                                <input type="hidden" name="action" value="approve">
                                <input class="form-control form-control-sm mb-1" name="cpanel_url" placeholder="https://cpanel.example.com:2083">
                                <input class="form-control form-control-sm mb-1" name="cpanel_user" placeholder="cPanel Username" required>
                                <input class="form-control form-control-sm mb-1" name="cpanel_pass" placeholder="cPanel Password" required>
                                <input class="form-control form-control-sm mb-1" name="nameservers" placeholder="ns1.example.com, ns2.example.com">
                                <button class="btn btn-success btn-sm w-100">Approve & Assign</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="request_id" value="<?php echo hostingSanitize($r['id']); ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn btn-outline-danger btn-sm w-100">Reject</button>
                            </form>
                        <?php else: ?>
                            <small class="text-muted">Processed by <?php echo hostingSanitize($r['approved_by'] ?? 'admin'); ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
