<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['accountant', 'super_admin', 'admin']);

$baseUrl = getBaseUrl();

// Mark Paid
if (isset($_GET['action']) && $_GET['action'] === 'mark_paid') {
    $fees = loadData('fees');
    foreach ($fees as &$f) {
        if ($f['id'] === sanitize($_GET['id'])) {
            $f['status']     = 'paid';
            $f['paid_date']  = date('Y-m-d');
            $f['receipt_no'] = 'RCP' . date('YmdHis');
            break;
        }
    }
    saveData('fees', $fees);
    setFlash('success', 'Fee marked as paid!');
    header('Location: ' . $baseUrl . '/accountant/fees.php'); exit;
}

// Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $fees = loadData('fees');
    $fees = array_values(array_filter($fees, fn($f) => $f['id'] !== sanitize($_GET['id'])));
    saveData('fees', $fees);
    setFlash('success', 'Fee record deleted.');
    header('Location: ' . $baseUrl . '/accountant/fees.php'); exit;
}

// Add Fee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = sanitize($_POST['student_id'] ?? '');
    $feeType   = sanitize($_POST['fee_type']   ?? '');
    $amount    = sanitize($_POST['amount']      ?? '0');
    $dueDate   = sanitize($_POST['due_date']    ?? '');
    $status    = sanitize($_POST['status']      ?? 'pending');

    $student = getStudentById($studentId);
    $fees    = loadData('fees');
    $fees[]  = [
        'id'           => generateId('FEE'),
        'student_id'   => $studentId,
        'student_name' => $student['name'] ?? '',
        'fee_type'     => $feeType,
        'amount'       => $amount,
        'due_date'     => $dueDate,
        'paid_date'    => $status === 'paid' ? date('Y-m-d') : '',
        'status'       => $status,
        'receipt_no'   => $status === 'paid' ? 'RCP' . date('YmdHis') : ''
    ];
    saveData('fees', $fees);
    setFlash('success', 'Fee record added.');
    header('Location: ' . $baseUrl . '/accountant/fees.php'); exit;
}

$fees     = loadData('fees');
$students = loadData('students');

// Stats
$paid      = array_filter($fees, fn($f) => $f['status'] === 'paid');
$pending   = array_filter($fees, fn($f) => $f['status'] === 'pending');
$overdue   = array_filter($fees, fn($f) => $f['status'] === 'pending' && !empty($f['due_date']) && $f['due_date'] < date('Y-m-d'));
$collectedAmt = array_sum(array_column(array_values($paid),    'amount'));
$pendingAmt   = array_sum(array_column(array_values($pending), 'amount'));

// Filters
$filterStatus = sanitize($_GET['status'] ?? '');
$filterSearch = sanitize($_GET['search'] ?? '');
$filtered     = $fees;
if ($filterStatus === 'overdue') {
    $filtered = array_filter($filtered, fn($f) => $f['status'] === 'pending' && !empty($f['due_date']) && $f['due_date'] < date('Y-m-d'));
} elseif ($filterStatus) {
    $filtered = array_filter($filtered, fn($f) => $f['status'] === $filterStatus);
}
if ($filterSearch) {
    $filtered = array_filter($filtered, fn($f) => stripos($f['student_name'] ?? '', $filterSearch) !== false || stripos($f['fee_type'] ?? '', $filterSearch) !== false);
}
$filtered = array_values($filtered);
usort($filtered, fn($a, $b) => strcmp($b['due_date'] ?? '', $a['due_date'] ?? ''));

$page  = max(1, (int)($_GET['page'] ?? 1));
$paged = paginate($filtered, $page);

$pageTitle = 'Fee Management';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-cash-stack text-theme"></i> Fee Management</h2>
            <p class="text-muted mb-0">Manage student fee records and payments</p>
        </div>
        <a href="<?php echo $baseUrl; ?>/accountant/dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </div>

    <?php echo renderFlash(); ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">COLLECTED</div>
                        <div class="stat-value">₹<?php echo number_format($collectedAmt); ?></div>
                        <small class="text-white-50"><?php echo count($paid); ?> records</small>
                    </div>
                    <i class="bi bi-receipt stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#ffc107,#d39e00)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-dark fw-semibold">PENDING</div>
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
                        <div class="text-white-50 small fw-semibold">TOTAL RECORDS</div>
                        <div class="stat-value"><?php echo count($fees); ?></div>
                        <small class="text-white-50">all records</small>
                    </div>
                    <i class="bi bi-cash-stack stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Add Fee Form -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-plus-circle"></i> Add Fee Record</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Student <span class="text-danger">*</span></label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s['id']); ?>">
                                        <?php echo htmlspecialchars($s['name'] . ' (Class ' . $s['class'] . '-' . $s['section'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fee Type</label>
                            <select class="form-select" name="fee_type">
                                <?php foreach (['Tuition Fee','Library Fee','Sports Fee','Transport Fee','Exam Fee','Lab Fee','Other'] as $t): ?>
                                    <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Add Fee Record</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fee List -->
        <div class="col-lg-8">
            <!-- Filter -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="row g-2">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="search" placeholder="Search student or fee type..." value="<?php echo htmlspecialchars($filterSearch); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $filterStatus==='pending'?'selected':''; ?>>Pending</option>
                                <option value="paid"    <?php echo $filterStatus==='paid'?'selected':''; ?>>Paid</option>
                                <option value="overdue" <?php echo $filterStatus==='overdue'?'selected':''; ?>>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button></div>
                        <div class="col-md-2"><a href="<?php echo $baseUrl; ?>/accountant/fees.php" class="btn btn-outline-secondary w-100">Reset</a></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>Fee Records
                        <?php if ($filterStatus): ?>
                            <span class="badge bg-secondary ms-1"><?php echo ucfirst($filterStatus); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="text-muted small"><?php echo count($filtered); ?> records</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($paged['data'])): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
                            <?php else: foreach ($paged['data'] as $f):
                                $isOverdue = ($f['status'] === 'pending' && !empty($f['due_date']) && $f['due_date'] < date('Y-m-d'));
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['student_name'] ?? $f['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($f['fee_type']); ?></td>
                                    <td class="fw-bold">₹<?php echo number_format($f['amount']); ?></td>
                                    <td class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo formatDate($f['due_date'] ?? ''); ?>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo $isOverdue ? 'overdue' : $f['status']; ?>">
                                            <?php echo $isOverdue ? 'Overdue' : ucfirst($f['status']); ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($f['receipt_no'] ?? '-'); ?></small></td>
                                    <td>
                                        <?php if ($f['status'] === 'pending'): ?>
                                            <a href="?action=mark_paid&id=<?php echo urlencode($f['id']); ?>" class="btn btn-sm btn-outline-success" title="Mark Paid">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo urlencode($f['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete ms-1" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($paged['pages'] > 1): ?>
                <div class="card-footer bg-white">
                    <nav><ul class="pagination mb-0 justify-content-end">
                        <?php for ($p = 1; $p <= $paged['pages']; $p++): ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $p; ?>&status=<?php echo urlencode($filterStatus); ?>&search=<?php echo urlencode($filterSearch); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul></nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
