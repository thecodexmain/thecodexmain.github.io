<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';

$students   = loadData('students');
$teachers   = loadData('teachers');
$fees       = loadData('fees');
$attendance = loadData('attendance');
$results    = loadData('results');
$exams      = loadData('exams');

// Class-wise student count
$classCounts = [];
foreach ($students as $s) {
    $key = 'Class ' . $s['class'] . '-' . $s['section'];
    $classCounts[$key] = ($classCounts[$key] ?? 0) + 1;
}
arsort($classCounts);

// Fee stats
$paidFees    = array_filter($fees, fn($f) => $f['status']==='paid');
$pendingFees = array_filter($fees, fn($f) => $f['status']==='pending');
$feeByType   = [];
foreach ($fees as $f) {
    $feeByType[$f['fee_type']] = ($feeByType[$f['fee_type']] ?? 0) + (float)($f['amount'] ?? 0);
}

// Attendance stats per student
$studentAtt = [];
foreach ($attendance as $a) {
    $id = $a['student_id'];
    if (!isset($studentAtt[$id])) $studentAtt[$id] = ['present'=>0,'absent'=>0,'late'=>0,'total'=>0];
    $studentAtt[$id]['total']++;
    $status = strtolower($a['status'] ?? '');
    if ($status==='present') $studentAtt[$id]['present']++;
    elseif ($status==='absent') $studentAtt[$id]['absent']++;
    elseif ($status==='late') $studentAtt[$id]['late']++;
}

// Exam results summary
$examResultSummary = [];
foreach ($results as $r) {
    $eid = $r['exam_id'];
    if (!isset($examResultSummary[$eid])) $examResultSummary[$eid] = ['total'=>0,'pass'=>0,'fail'=>0,'totalMarks'=>0];
    $examResultSummary[$eid]['total']++;
    $examResultSummary[$eid]['totalMarks'] += (float)($r['marks_obtained']??0);
    if ($r['grade'] !== 'F') $examResultSummary[$eid]['pass']++;
    else $examResultSummary[$eid]['fail']++;
}
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between">
        <h2><i class="bi bi-bar-chart text-theme"></i> Reports</h2>
        <button class="btn btn-outline-secondary no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card text-center p-3"><div class="fs-1 fw-bold text-primary"><?php echo count($students); ?></div><div class="text-muted">Total Students</div></div></div>
        <div class="col-md-3"><div class="card text-center p-3"><div class="fs-1 fw-bold text-success"><?php echo count($teachers); ?></div><div class="text-muted">Total Teachers</div></div></div>
        <div class="col-md-3"><div class="card text-center p-3"><div class="fs-1 fw-bold text-warning">₹<?php echo number_format(array_sum(array_column(array_values($paidFees),'amount'))); ?></div><div class="text-muted">Fee Collected</div></div></div>
        <div class="col-md-3"><div class="card text-center p-3"><div class="fs-1 fw-bold text-danger">₹<?php echo number_format(array_sum(array_column(array_values($pendingFees),'amount'))); ?></div><div class="text-muted">Fee Pending</div></div></div>
    </div>

    <div class="row g-3">
        <!-- Class-wise student count -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-people"></i> Students by Class</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Class</th><th>Students</th><th>%</th></tr></thead>
                        <tbody>
                        <?php foreach ($classCounts as $cls => $cnt): $pct = count($students)>0?round($cnt/count($students)*100):0; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cls); ?></td>
                                <td><strong><?php echo $cnt; ?></strong></td>
                                <td>
                                    <div class="progress" style="height:14px">
                                        <div class="progress-bar" style="width:<?php echo $pct; ?>%;background:var(--theme-color)"><?php echo $pct; ?>%</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fee Collection by Type -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-cash-stack"></i> Fee Collection by Type</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Fee Type</th><th>Total</th></tr></thead>
                        <tbody>
                        <?php foreach ($feeByType as $type => $amt): ?>
                            <tr><td><?php echo htmlspecialchars($type); ?></td><td>₹<?php echo number_format($amt); ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($feeByType)): ?><tr><td colspan="2" class="text-muted text-center py-3">No data</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Exam Performance -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-award"></i> Exam Performance</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Exam</th><th>Pass</th><th>Fail</th><th>Avg</th></tr></thead>
                        <tbody>
                        <?php foreach ($examResultSummary as $examId => $s):
                            $exam = null;
                            foreach ($exams as $e) { if ($e['id']===$examId) { $exam=$e; break; } }
                            $avg = $s['total']>0 ? round($s['totalMarks']/$s['total'],1) : 0;
                        ?>
                            <tr>
                                <td><small><?php echo htmlspecialchars($exam['name']??$examId); ?></small></td>
                                <td class="text-success fw-bold"><?php echo $s['pass']; ?></td>
                                <td class="text-danger fw-bold"><?php echo $s['fail']; ?></td>
                                <td><?php echo $avg; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($examResultSummary)): ?><tr><td colspan="4" class="text-muted text-center py-3">No data</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Report -->
    <div class="card mt-3">
        <div class="card-header"><i class="bi bi-calendar-check"></i> Student Attendance Summary</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Class</th><th>Total Days</th><th>Present</th><th>Absent</th><th>Late</th><th>% Present</th></tr></thead>
                    <tbody>
                    <?php if (empty($studentAtt)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No attendance data recorded.</td></tr>
                    <?php else: foreach ($studentAtt as $sid => $att):
                        $student = getStudentById($sid);
                        $pct     = $att['total']>0 ? round($att['present']/$att['total']*100) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']??$sid); ?></td>
                            <td><?php echo htmlspecialchars(($student['class']??'-').'-'.($student['section']??'-')); ?></td>
                            <td><?php echo $att['total']; ?></td>
                            <td class="attendance-present"><?php echo $att['present']; ?></td>
                            <td class="attendance-absent"><?php echo $att['absent']; ?></td>
                            <td class="attendance-late"><?php echo $att['late']; ?></td>
                            <td>
                                <div class="progress" style="height:16px">
                                    <div class="progress-bar bg-<?php echo $pct>=75?'success':($pct>=50?'warning':'danger'); ?>" style="width:<?php echo $pct; ?>%"><?php echo $pct; ?>%</div>
                                </div>
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
