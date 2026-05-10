<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!hostingIsLoggedIn()) {
    hostingSetFlash('warning', 'Please sign in to request a service.');
    header('Location: ' . hostingGetBaseUrl() . '/login.php');
    exit;
}
hostingRequireRole(['user']);

$baseUrl = hostingGetBaseUrl();
$services = hostingEnsureDefaultServices();

$selected = $_GET['service'] ?? '';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hostingVerifyCsrf();
    $serviceId = (string)($_POST['service_id'] ?? '');
    $domain = trim((string)($_POST['domain'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));

    $service = hostingFindService($serviceId);
    if (!$service || empty($service['active'])) {
        $error = 'Please select a valid plan.';
    } elseif ($domain !== '' && !preg_match('/^[a-z0-9.-]+\\.[a-z]{2,}$/i', $domain)) {
        $error = 'Please enter a valid domain (example.com).';
    } else {
        $user = hostingCurrentUser();
        $orders = hostingLoadData('orders');
        $orderId = 'O-' . hostingGenerateId();
        $orders[] = [
            'id' => $orderId,
            'user_id' => $user['id'],
            'service_id' => $service['id'],
            'service_name' => $service['name'],
            'status' => 'pending',
            'domain' => $domain,
            'notes' => $notes,
            'requested_at' => hostingNow(),
            'updated_at' => hostingNow(),
            'approved_at' => null,
            'cpanel' => []
        ];
        hostingSaveData('orders', $orders);
        $settings = hostingGetSettings();
        $support = (string)($settings['support_email'] ?? '');
        if ($support) {
            hostingQueueMail($support, 'New hosting order request: ' . $service['name'], "A new order request was submitted.\n\nCustomer: {$user['name']} ({$user['email']})\nPlan: {$service['name']}\nDomain: " . ($domain ?: '-') . "\nOrder: {$orderId}\n\nReview: " . hostingGetBaseUrl() . "/admin/orders.php?id={$orderId}");
        }
        hostingQueueMail($user['email'], 'Order request received: ' . $service['name'], "Hi {$user['name']},\n\nWe received your request for {$service['name']}. An admin will approve it soon and send your cPanel details.\n\nOrder status: pending\n\nThanks,\n" . ($settings['brand_name'] ?? 'CodexHost'));
        hostingDispatchMailQueue(5);
        hostingSetFlash('success', 'Request submitted. You will get an email after admin approval.');
        header('Location: ' . hostingGetBaseUrl() . '/user/services.php');
        exit;
    }
    $selected = $serviceId;
}
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Buy New Service</h1>
        <div class="text-muted">Pick a plan and submit a request for admin approval.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/user/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>After you submit a request, an admin will approve it and send your cPanel details automatically.
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(hostingCsrfToken()); ?>">
            <div class="col-12">
                <label class="form-label fw-semibold">Plan</label>
                <select class="form-select" name="service_id" required>
                    <option value="" <?php echo $selected ? '' : 'selected'; ?> disabled>Select a plan</option>
                    <?php foreach ($services as $s): if (empty($s['active'])) continue; ?>
                        <option value="<?php echo htmlspecialchars($s['id']); ?>" <?php echo ($selected === ($s['id'] ?? '')) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?> — $<?php echo number_format((float)$s['price'], 2); ?>/<?php echo htmlspecialchars($s['billing_cycle']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Domain (optional)</label>
                <input class="form-control" name="domain" placeholder="example.com">
                <div class="form-text">If you already have a domain, enter it here.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Notes (optional)</label>
                <input class="form-control" name="notes" placeholder="Any extra requirements">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit"><i class="bi bi-send-check me-1"></i>Submit request</button>
                <a class="btn btn-outline-secondary ms-2" href="<?php echo $baseUrl; ?>/index.php#plans">Compare plans</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
