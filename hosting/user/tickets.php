<?php
require_once __DIR__ . '/../includes/auth.php';
hostingRequireRole(['user']);

$baseUrl = hostingGetBaseUrl();
?>
<?php require __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Tickets</h1>
        <div class="text-muted">Open a ticket and track replies.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $baseUrl; ?>/user/dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="text-muted">Ticketing is enabled in the next step.</div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

