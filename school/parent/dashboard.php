<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['parent']);

$pageTitle = 'Parent Dashboard';
include __DIR__ . '/../includes/header.php';

// Parent is linked to a student via student_id in session
$studentId = $_SESSION['student_id'] ?? '';
$student   = getStudentById($studentId);

$fees        = array_values(array_filter(loadData('fees'),       fn($f) => $f['student_id'] === $studentId));
$attendance  = array_values(array_filter(loadData('attendance'), fn($a) => $a['student_id'] === $studentId));
$results     = array_values(array_filter(loadData('results'),    fn($r) => $r['student_id'] === $studentId));
$exams       = loadData('exams');
$allNotices  = loadData('notices');
$notices     = array_slice(array_reverse($allNotices), 0, 5);
$noticeCount = count($allNotices);

$paidFees    = array_filter($fees, fn($f) => $f['status'] === 'paid');
$pendingFees = array_filter($fees, fn($f) => $f['status'] === 'pending');
$pendingAmt  = array_sum(array_column(array_values($pendingFees), 'amount'));

$totalAtt   = count($attendance);
$presentAtt = count(array_filter($attendance, fn($a) => $a['status'] === 'Present'));
$attPct     = $totalAtt > 0 ? round($presentAtt / $totalAtt * 100) : 0;

// Today's timetable
$today  = date('l');
$tt     = loadData('timetable');
$todayTT = array_filter($tt, fn($t) =>
    $t['class']   === ($student['class']   ?? '') &&
    $t['section'] === ($student['section'] ?? '') &&
    $t['day']     === $today
);
usort($todayTT, fn($a, $b) => strcmp($a['start_time'] ?? '', $b['start_time'] ?? ''));

// Recent results (sorted by exam date)
$recentResults = $results;
// join with exam date for sorting
foreach ($recentResults as &$r) {
    foreach ($exams as $e) {
        if ($e['id'] === $r['exam_id']) { $r['_exam_date'] = $e['date'] ?? ''; break; }
    }
    if (!isset($r['_exam_date'])) $r['_exam_date'] = '';
}
unset($r);
usort($recentResults, fn($a, $b) => strcmp($b['_exam_date'], $a['_exam_date']));
$recentResults = array_slice($recentResults, 0, 5);
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-speedometer2 text-theme"></i> Parent Dashboard</h2>
            <p class="text-muted mb-0">
                Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!
                <?php if ($student): ?>
                    Monitoring: <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                    (Class <?php echo htmlspecialchars($student['class'] . '-' . $student['section']); ?>)
                <?php endif; ?>
            </p>
        </div>
        <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo date('l, d F Y'); ?></div>
    </div>

    <?php echo renderFlash(); ?>

    <?php if (!$student): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> No student record linked to your account. Please contact the administrator.
        </div>
    <?php else: ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">ATTENDANCE</div>
                        <div class="stat-value"><?php echo $attPct; ?>%</div>
                        <small class="text-white-50"><?php echo $presentAtt; ?>/<?php echo $totalAtt; ?> days present</small>
                    </div>
                    <i class="bi bi-calendar-check stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#dc3545,#b02a37)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">PENDING FEES</div>
                        <div class="stat-value">₹<?php echo number_format($pendingAmt); ?></div>
                        <small class="text-white-50"><?php echo count($pendingFees); ?> due</small>
                    </div>
                    <i class="bi bi-cash-stack stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">EXAMS TAKEN</div>
                        <div class="stat-value"><?php echo count($results); ?></div>
                        <small class="text-white-50">results recorded</small>
                    </div>
                    <i class="bi bi-award stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#6610f2,#520dc2)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">NOTICES</div>
                        <div class="stat-value"><?php echo $noticeCount; ?></div>
                        <small class="text-white-50">published</small>
                    </div>
                    <i class="bi bi-megaphone stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Student Info -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person-circle text-primary"></i> Student Profile</div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar bg-primary text-white mx-auto mb-2" style="width:60px;height:60px;font-size:1.6rem;border-radius:50%;">
                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                        </div>
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($student['name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($student['id']); ?></small>
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Class</td><td class="fw-semibold"><?php echo htmlspecialchars($student['class'] . ' - ' . $student['section']); ?></td></tr>
                        <tr><td class="text-muted">Roll No.</td><td class="fw-semibold"><?php echo htmlspecialchars($student['roll_number'] ?? '-'); ?></td></tr>
                        <tr><td class="text-muted">Gender</td><td><?php echo htmlspecialchars($student['gender'] ?? '-'); ?></td></tr>
                        <tr><td class="text-muted">DOB</td><td><?php echo formatDate($student['dob'] ?? ''); ?></td></tr>
                        <tr><td class="text-muted">Phone</td><td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td></tr>
                        <tr><td class="text-muted">Email</td><td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td></tr>
                        <tr><td class="text-muted">Status</td><td><span class="badge status-<?php echo $student['status'] ?? 'active'; ?>"><?php echo ucfirst($student['status'] ?? 'active'); ?></span></td></tr>
                    </table>
                </div>
            </div>

            <!-- Pending Fees -->
            <div class="card">
                <div class="card-header"><i class="bi bi-cash-stack text-danger"></i> Pending Fees</div>
                <div class="card-body p-0">
                    <?php if (empty($pendingFees)): ?>
                        <p class="text-muted text-center py-3"><i class="bi bi-check-circle text-success"></i> All fees paid!</p>
                    <?php else: foreach ($pendingFees as $f):
                        $overdue = !empty($f['due_date']) && $f['due_date'] < date('Y-m-d');
                    ?>
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small"><?php echo htmlspecialchars($f['fee_type']); ?></div>
                                <small class="text-<?php echo $overdue ? 'danger' : 'muted'; ?>">Due: <?php echo formatDate($f['due_date'] ?? ''); ?><?php echo $overdue ? ' <strong>(Overdue)</strong>' : ''; ?></small>
                            </div>
                            <span class="badge bg-<?php echo $overdue ? 'danger' : 'warning text-dark'; ?>">₹<?php echo number_format($f['amount']); ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Today's Timetable -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-clock text-primary"></i> Today's Classes (<?php echo $today; ?>)</div>
                <div class="card-body p-0">
                    <?php if (empty($todayTT)): ?>
                        <p class="text-muted text-center py-3">No classes scheduled today.</p>
                    <?php else: foreach ($todayTT as $t): ?>
                        <div class="p-3 border-bottom d-flex align-items-center gap-3">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($t['start_time'] ?? ''); ?></span>
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($t['subject']); ?></div>
                                <small class="text-muted">Period <?php echo $t['period']; ?> &bull; <?php echo htmlspecialchars($t['start_time'] ?? ''); ?>–<?php echo htmlspecialchars($t['end_time'] ?? ''); ?></small>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Recent Results -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-award text-success"></i> Recent Exam Results</div>
                <div class="card-body p-0">
                    <?php if (empty($recentResults)): ?>
                        <p class="text-muted text-center py-3">No results available yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Exam</th><th>Subject</th><th>Marks</th><th>Grade</th></tr></thead>
                                <tbody>
                                <?php foreach ($recentResults as $r):
                                    $exam = null;
                                    foreach ($exams as $e) { if ($e['id'] === $r['exam_id']) { $exam = $e; break; } }
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['name'] ?? $r['exam_id']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['subject'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($r['marks_obtained'] ?? '-'); ?>/<?php echo htmlspecialchars($exam['max_marks'] ?? '-'); ?></td>
                                        <td><span class="badge bg-<?php echo $r['grade']==='F'?'danger':($r['grade']==='A+'||$r['grade']==='A'?'success':'primary'); ?>"><?php echo htmlspecialchars($r['grade'] ?? '-'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notices -->
            <div class="card">
                <div class="card-header"><i class="bi bi-megaphone text-warning"></i> School Notices</div>
                <div class="card-body p-0">
                    <?php if (empty($notices)): ?>
                        <p class="text-muted text-center py-3">No notices.</p>
                    <?php else: foreach ($notices as $n): ?>
                        <div class="p-3 border-bottom priority-<?php echo htmlspecialchars($n['priority'] ?? 'low'); ?>">
                            <div class="fw-semibold small"><?php echo htmlspecialchars($n['title']); ?></div>
                            <div class="text-muted small">
                                <?php echo formatDate($n['created_at'] ?? ''); ?> &bull;
                                <span class="badge bg-<?php echo ($n['priority']==='high')?'danger':(($n['priority']==='medium')?'warning text-dark':'success'); ?>"><?php echo ucfirst($n['priority'] ?? 'low'); ?></span>
                            </div>
                            <?php if (!empty($n['content'])): ?>
                                <p class="text-muted small mt-1 mb-0"><?php echo htmlspecialchars($n['content']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
