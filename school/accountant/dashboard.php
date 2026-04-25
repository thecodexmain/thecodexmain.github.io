<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['accountant']);

$pageTitle = 'Accountant Dashboard';
include __DIR__ . '/../includes/header.php';

$fees     = loadData('fees');
$students = loadData('students');
$notices  = loadData('notices');

$paid    = array_values(array_filter($fees, fn($f) => $f['status'] === 'paid'));
$pending = array_values(array_filter($fees, fn($f) => $f['status'] === 'pending'));
$overdue = array_values(array_filter($fees, fn($f) => $f['status'] === 'pending' && !empty($f['due_date']) && $f['due_date'] < date('Y-m-d')));

$collectedAmt = array_sum(array_column($paid,    'amount'));
$pendingAmt   = array_sum(array_column($pending, 'amount'));

$recentFees   = array_slice(array_reverse($fees), 0, 5);
$recentNotices = array_slice(array_reverse($notices), 0, 3);
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h2><i class="bi bi-speedometer2 text-theme"></i> Accountant Dashboard</h2>
        <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!</p>
    </div>

    <?php echo renderFlash(); ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body">
                    <div class="small text-white-50">COLLECTED</div>
                    <div class="stat-value">₹<?php echo number_format($collectedAmt); ?></div>
                    <small class="text-white-50"><?php echo count($paid); ?> records</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" style="background:linear-gradient(135deg,#ffc107,#d39e00)">
                <div class="card-body">
                    <div class="small text-dark">PENDING</div>
                    <div class="stat-value text-dark">₹<?php echo number_format($pendingAmt); ?></div>
                    <small class="text-dark"><?php echo count($pending); ?> records</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#dc3545,#b02a37)">
                <div class="card-body">
                    <div class="small text-white-50">OVERDUE</div>
                    <div class="stat-value"><?php echo count($overdue); ?></div>
                    <small class="text-white-50">overdue fees</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                <div class="card-body">
                    <div class="small text-white-50">TOTAL STUDENTS</div>
                    <div class="stat-value"><?php echo count($students); ?></div>
                    <small class="text-white-50">enrolled</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <!-- Recent Fee Records -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span><i class="bi bi-cash-stack text-success"></i> Recent Fee Records</span>
                    <a href="<?php echo $baseUrl; ?>/accountant/fees.php" class="btn btn-sm btn-outline-primary">Manage All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentFees)): ?>
                        <p class="text-muted text-center py-3">No fee records found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Student</th><th>Fee Type</th><th>Amount</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($recentFees as $f):
                                    $isOverdue = ($f['status']==='pending' && !empty($f['due_date']) && $f['due_date'] < date('Y-m-d'));
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($f['student_name'] ?? $f['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($f['fee_type']); ?></td>
                                        <td>₹<?php echo number_format($f['amount']); ?></td>
                                        <td>
                                            <span class="badge status-<?php echo $isOverdue ? 'overdue' : $f['status']; ?>">
                                                <?php echo $isOverdue ? 'Overdue' : ucfirst($f['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <!-- Quick Actions -->
            <div class="card mb-3">
                <div class="card-header">Quick Actions</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo $baseUrl; ?>/accountant/fees.php" class="btn btn-outline-success"><i class="bi bi-cash-stack"></i> Manage Fees</a>
                        <a href="<?php echo $baseUrl; ?>/accountant/fees.php?status=pending" class="btn btn-outline-warning"><i class="bi bi-hourglass-split"></i> View Pending Fees</a>
                        <a href="<?php echo $baseUrl; ?>/accountant/fees.php?status=paid" class="btn btn-outline-primary"><i class="bi bi-check-circle"></i> View Paid Fees</a>
                    </div>
                </div>
            </div>

            <!-- Notices -->
            <div class="card">
                <div class="card-header"><i class="bi bi-megaphone text-warning"></i> Recent Notices</div>
                <div class="card-body p-0">
                    <?php if (empty($recentNotices)): ?>
                        <p class="text-muted text-center py-3">No notices.</p>
                    <?php else: foreach ($recentNotices as $n): ?>
                        <div class="p-3 border-bottom priority-<?php echo htmlspecialchars($n['priority'] ?? 'low'); ?>">
                            <div class="fw-semibold small"><?php echo htmlspecialchars($n['title']); ?></div>
                            <small class="text-muted"><?php echo formatDate($n['created_at'] ?? ''); ?></small>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
