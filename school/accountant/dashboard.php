<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['accountant']);

$pageTitle = 'Accountant Dashboard';
include __DIR__ . '/../includes/header.php';

$baseUrl = getBaseUrl();

$fees    = loadData('fees');
$students= loadData('students');
$today   = date('Y-m-d');

$pendingFees = array_filter($fees, fn($f) => ($f['status'] ?? '') === 'pending');
$paidFees    = array_filter($fees, fn($f) => ($f['status'] ?? '') === 'paid');
$overdueFees = array_filter($fees, fn($f) => ($f['status'] ?? '') === 'overdue');

$pendingAmt  = array_sum(array_column($pendingFees, 'amount'));
$paidAmt     = array_sum(array_column($paidFees, 'amount'));
$overdueAmt  = array_sum(array_column($overdueFees, 'amount'));

$recentFees = $fees;
usort($recentFees, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
$recentFees = array_slice($recentFees, 0, 8);
?>

<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-speedometer2 text-theme"></i> Accountant Dashboard</h2>
            <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>! Track collections and pending fees.</p>
        </div>
        <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo date('l, d F Y'); ?></div>
    </div>

    <?php echo renderFlash(); ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body">
                    <div class="text-white-50 small fw-semibold">COLLECTED</div>
                    <div class="stat-value">₹<?php echo number_format($paidAmt); ?></div>
                    <small class="text-white-50"><?php echo count($paidFees); ?> paid records</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#fd7e14,#dc6a00)">
                <div class="card-body">
                    <div class="text-white-50 small fw-semibold">PENDING</div>
                    <div class="stat-value">₹<?php echo number_format($pendingAmt); ?></div>
                    <small class="text-white-50"><?php echo count($pendingFees); ?> pending records</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#dc3545,#b02a37)">
                <div class="card-body">
                    <div class="text-white-50 small fw-semibold">OVERDUE</div>
                    <div class="stat-value">₹<?php echo number_format($overdueAmt); ?></div>
                    <small class="text-white-50"><?php echo count($overdueFees); ?> overdue records</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                <div class="card-body">
                    <div class="text-white-50 small fw-semibold">STUDENTS</div>
                    <div class="stat-value"><?php echo count($students); ?></div>
                    <small class="text-white-50">total registered</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span><i class="bi bi-receipt text-primary"></i> Recent Fee Activity</span>
            <a href="<?php echo $baseUrl; ?>/accountant/fees.php" class="btn btn-sm btn-outline-primary">Manage Fees</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentFees)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No fee records</td></tr>
                        <?php else: foreach ($recentFees as $f): ?>
                            <tr>
                                <td><?php echo formatDate($f['date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($f['student_id'] ?? '-'); ?></td>
                                <td>₹<?php echo number_format((float)($f['amount'] ?? 0)); ?></td>
                                <td>
                                    <?php $st = $f['status'] ?? 'pending'; ?>
                                    <span class="badge status-<?php echo htmlspecialchars($st); ?>"><?php echo ucfirst($st); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

