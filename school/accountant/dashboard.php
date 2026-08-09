<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['accountant']);

$pageTitle = 'Accountant Dashboard';
include __DIR__ . '/../includes/header.php';

$fees     = loadData('fees');
$students = loadData('students');

$paid    = array_filter($fees, fn($f) => $f['status'] === 'paid');
$pending = array_filter($fees, fn($f) => $f['status'] === 'pending');
$overdue = array_filter($fees, fn($f) => $f['status'] === 'pending' && !empty($f['due_date']) && $f['due_date'] < date('Y-m-d'));

$collectedAmt = array_sum(array_column(array_values($paid),    'amount'));
$pendingAmt   = array_sum(array_column(array_values($pending), 'amount'));
$totalAmt     = array_sum(array_column($fees, 'amount'));

// Fee type breakdown
$feeByType = [];
foreach ($fees as $f) {
    $feeByType[$f['fee_type']] = ($feeByType[$f['fee_type']] ?? 0) + (float)($f['amount'] ?? 0);
}
arsort($feeByType);

// Recent transactions
$recentPaid = array_values($paid);
usort($recentPaid, fn($a, $b) => strcmp($b['paid_date'] ?? '', $a['paid_date'] ?? ''));
$recentPaid = array_slice($recentPaid, 0, 8);

// Upcoming due
$dueSoon = array_values(array_filter($pending, fn($f) => !empty($f['due_date']) && $f['due_date'] >= date('Y-m-d') && $f['due_date'] <= date('Y-m-d', strtotime('+7 days'))));
usort($dueSoon, fn($a, $b) => strcmp($a['due_date'] ?? '', $b['due_date'] ?? ''));
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-speedometer2 text-theme"></i> Accountant Dashboard</h2>
            <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>! Here's the financial overview.</p>
        </div>
        <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo date('l, d F Y'); ?></div>
    </div>

    <?php echo renderFlash(); ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">FEE COLLECTED</div>
                        <div class="stat-value">₹<?php echo number_format($collectedAmt); ?></div>
                        <small class="text-white-50"><?php echo count($paid); ?> payments</small>
                    </div>
                    <i class="bi bi-receipt stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#ffc107,#d39e00)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-dark small fw-semibold">PENDING FEES</div>
                        <div class="stat-value text-dark">₹<?php echo number_format($pendingAmt); ?></div>
                        <small class="text-dark"><?php echo count($pending); ?> records</small>
                    </div>
                    <i class="bi bi-hourglass-split stat-icon text-dark"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#dc3545,#b02a37)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">OVERDUE</div>
                        <div class="stat-value"><?php echo count($overdue); ?></div>
                        <small class="text-white-50">overdue payments</small>
                    </div>
                    <i class="bi bi-exclamation-triangle stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">TOTAL BILLED</div>
                        <div class="stat-value">₹<?php echo number_format($totalAmt); ?></div>
                        <small class="text-white-50"><?php echo count($fees); ?> records</small>
                    </div>
                    <i class="bi bi-cash-stack stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Recent Paid -->
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-check-circle text-success"></i> Recent Payments</span>
                    <a href="<?php echo $baseUrl; ?>/accountant/fees.php" class="btn btn-sm btn-outline-success">Manage Fees</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Student</th><th>Fee Type</th><th>Amount</th><th>Paid Date</th><th>Receipt</th></tr></thead>
                            <tbody>
                            <?php if (empty($recentPaid)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No payments recorded yet.</td></tr>
                            <?php else: foreach ($recentPaid as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['student_name'] ?? $f['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($f['fee_type']); ?></td>
                                    <td class="text-success fw-bold">₹<?php echo number_format($f['amount']); ?></td>
                                    <td><?php echo formatDate($f['paid_date'] ?? ''); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($f['receipt_no'] ?? '-'); ?></small></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Overdue Fees -->
            <?php if (!empty($overdue)): ?>
            <div class="card">
                <div class="card-header bg-white"><i class="bi bi-exclamation-triangle text-danger"></i> Overdue Payments (<?php echo count($overdue); ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Student</th><th>Fee Type</th><th>Amount</th><th>Due Date</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach (array_slice(array_values($overdue), 0, 5) as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['student_name'] ?? $f['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($f['fee_type']); ?></td>
                                    <td class="text-danger fw-bold">₹<?php echo number_format($f['amount']); ?></td>
                                    <td class="text-danger"><?php echo formatDate($f['due_date'] ?? ''); ?></td>
                                    <td>
                                        <a href="<?php echo $baseUrl; ?>/accountant/fees.php?action=mark_paid&id=<?php echo urlencode($f['id']); ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i> Mark Paid</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <!-- Fee Breakdown -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-pie-chart text-primary"></i> Fee Collection by Type</div>
                <div class="card-body p-0">
                    <?php if (empty($feeByType)): ?>
                        <p class="text-muted text-center py-3">No data.</p>
                    <?php else: foreach ($feeByType as $type => $amt): ?>
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="small"><?php echo htmlspecialchars($type); ?></span>
                            <span class="fw-bold">₹<?php echo number_format($amt); ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Due Soon -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-clock-history text-warning"></i> Due in Next 7 Days</div>
                <div class="card-body p-0">
                    <?php if (empty($dueSoon)): ?>
                        <p class="text-muted text-center py-3">No fees due in the next 7 days.</p>
                    <?php else: foreach ($dueSoon as $f): ?>
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small"><?php echo htmlspecialchars($f['student_name'] ?? $f['student_id']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($f['fee_type']); ?> | Due: <?php echo formatDate($f['due_date'] ?? ''); ?></small>
                            </div>
                            <span class="badge bg-warning text-dark">₹<?php echo number_format($f['amount']); ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">Quick Actions</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo $baseUrl; ?>/accountant/fees.php" class="btn btn-outline-primary"><i class="bi bi-cash-stack"></i> Manage Fee Records</a>
                        <a href="<?php echo $baseUrl; ?>/accountant/fees.php?status=pending" class="btn btn-outline-warning"><i class="bi bi-hourglass-split"></i> View Pending Fees</a>
                        <a href="<?php echo $baseUrl; ?>/accountant/fees.php?status=overdue" class="btn btn-outline-danger"><i class="bi bi-exclamation-triangle"></i> View Overdue Fees</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
