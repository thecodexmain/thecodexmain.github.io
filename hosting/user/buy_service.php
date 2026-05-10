<?php
require_once __DIR__ . '/../includes/layout.php';
hostingRequireRole('user');
$user = hostingGetCurrentUser();
$plans = loadHostingData('service_plans');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = trim($_POST['plan_id'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $billingCycle = trim($_POST['billing_cycle'] ?? 'monthly');
    $notes = trim($_POST['notes'] ?? '');

    $selectedPlan = null;
    foreach ($plans as $p) {
        if (($p['id'] ?? '') === $planId) {
            $selectedPlan = $p;
            break;
        }
    }

    if (!$selectedPlan) {
        hostingSetFlash('danger', 'Please select a valid hosting plan.');
    } elseif ($domain === '') {
        hostingSetFlash('danger', 'Domain is required.');
    } elseif (!preg_match('/^(?=.{1,253}$)(?!-)(?:[a-zA-Z0-9-]{1,63}\\.)+[a-zA-Z]{2,63}$/', $domain)) {
        hostingSetFlash('danger', 'Please provide a valid domain name (example.com).');
    } else {
        $requests = loadHostingData('service_requests');
        $request = [
            'id' => hostingGenerateId('REQ-'),
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'plan_id' => $selectedPlan['id'],
            'plan_name' => $selectedPlan['name'],
            'domain' => $domain,
            'billing_cycle' => in_array($billingCycle, ['monthly', 'quarterly', 'yearly'], true) ? $billingCycle : 'monthly',
            'notes' => $notes,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $requests[] = $request;
        saveHostingData('service_requests', $requests);

        hostingSendAutoMail($user['email'], 'Service request received', "Hi {$user['name']},\n\nYour request {$request['id']} for {$request['plan_name']} ({$request['domain']}) has been received.\nStatus: Pending admin approval.", ['event' => 'request_submitted', 'request_id' => $request['id']]);
        foreach (hostingAdminEmails() as $adminEmail) {
            hostingSendAutoMail($adminEmail, 'New hosting service request', "New request {$request['id']} from {$user['name']} ({$user['email']}) for {$request['plan_name']} on {$request['domain']}.", ['event' => 'admin_new_request', 'request_id' => $request['id']]);
        }

        hostingSetFlash('success', 'Your request was submitted and admin has been notified.');
        header('Location: ' . hostingGetBaseUrl() . '/user/dashboard.php');
        exit;
    }
}

hostingLayoutStart('Buy New Service - CodexHost');
?>
<h2 class="fw-bold mb-4">Buy New Service</h2>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Hosting Plan</label>
                        <select class="form-select" name="plan_id" required>
                            <option value="">Select a plan</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?php echo hostingSanitize($plan['id']); ?>"><?php echo hostingSanitize($plan['name']); ?> - $<?php echo number_format((float)$plan['price_monthly'], 2); ?>/mo</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Domain Name</label><input class="form-control" name="domain" placeholder="example.com" required></div>
                    <div class="mb-3">
                        <label class="form-label">Billing Cycle</label>
                        <select class="form-select" name="billing_cycle" required>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="4" placeholder="Optional setup notes..."></textarea></div>
                    <button class="btn btn-primary">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="fw-bold">What happens next?</h5>
                <ol class="mb-0">
                    <li>Admin reviews your request.</li>
                    <li>Request is approved and service is provisioned.</li>
                    <li>You receive cPanel details by automatic email.</li>
                    <li>Service appears in your “My Services” dashboard.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<?php hostingLayoutEnd(); ?>
