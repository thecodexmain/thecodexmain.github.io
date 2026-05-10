<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['admin']);
$baseUrl = hostingGetBaseUrl();
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Orders</h1>
        <div class="text-muted">Approve requests and provide cPanel details.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/admin/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card">
    <div class="card-body text-muted">Order approval is enabled in a later step.</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

