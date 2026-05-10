<?php
require_once __DIR__ . '/../includes/auth.php';

if (!hostingIsLoggedIn()) {
    hostingSetFlash('warning', 'Please sign in to request a service.');
    header('Location: ' . hostingGetBaseUrl() . '/login.php');
    exit;
}
hostingRequireRole(['user']);

$baseUrl = hostingGetBaseUrl();
$services = hostingEnsureDefaultServices();

$selected = $_GET['service'] ?? '';
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

<div class="card">
    <div class="card-body">
        <div class="text-muted">Ordering flow is enabled in the next step.</div>
        <div class="small">For now you can browse plans on the home page.</div>
        <a class="btn btn-primary mt-3" href="<?php echo $baseUrl; ?>/index.php#plans"><i class="bi bi-bag-check me-1"></i>Browse plans</a>
        <?php if ($selected): ?>
            <div class="small text-muted mt-2">Selected plan: <span class="fw-semibold"><?php echo htmlspecialchars($selected); ?></span></div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

