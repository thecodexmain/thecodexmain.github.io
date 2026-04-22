<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

// Save attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $date    = sanitize($_POST['date']    ?? date('Y-m-d'));
    $class   = sanitize($_POST['class']   ?? '');
    $section = sanitize($_POST['section'] ?? '');
    $records = $_POST['attendance'] ?? [];

    $attendance = loadData('attendance');
    // Remove existing for same class/section/date
    $attendance = array_values(array_filter($attendance, fn($a) => !($a['date']===$date && $a['class']===$class && $a['section']===$section)));

    foreach ($records as $studentId => $status) {
        $attendance[] = [
            'id'         => generateId('ATT'),
            'student_id' => sanitize($studentId),
            'class'      => $class,
            'section'    => $section,
            'date'       => $date,
            'status'     => sanitize($status),
            'marked_by'  => $_SESSION['user_id'] ?? ''
        ];
    }
    saveData('attendance', $attendance);
    setFlash('success', 'Attendance saved for ' . $date);
    header('Location: ' . $baseUrl . '/admin/attendance.php?class=' . urlencode($class) . '&section=' . urlencode($section) . '&date=' . urlencode($date));
    exit;
}

$filterClass   = sanitize($_GET['class']   ?? '');
$filterSection = sanitize($_GET['section'] ?? '');
$filterDate    = sanitize($_GET['date']    ?? date('Y-m-d'));
$viewMode      = $_GET['view'] ?? 'mark';

$allClasses = loadData('classes');
$classGroups = [];
foreach ($allClasses as $c) {
    $classGroups[$c['class']][] = $c['section'];
}

$students   = loadData('students');
$attendance = loadData('attendance');

// Students for selected class/section
$classStudents = [];
if ($filterClass && $filterSection) {
    $classStudents = array_values(array_filter($students, fn($s) => $s['class']===$filterClass && $s['section']===$filterSection));
    // Sort by roll
    usort($classStudents, fn($a,$b) => (int)($a['roll_number']??0) - (int)($b['roll_number']??0));
}

// Existing attendance for today/class
$existingAtt = [];
foreach ($attendance as $a) {
    if ($a['date']===$filterDate && $a['class']===$filterClass && $a['section']===$filterSection) {
        $existingAtt[$a['student_id']] = $a['status'];
    }
}

$pageTitle = 'Attendance';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-calendar-check text-theme"></i> Attendance Management</h2></div>
    <?php echo renderFlash(); ?>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Class</label>
                    <select class="form-select" name="class" onchange="this.form.submit()">
                        <option value="">Select Class</option>
                        <?php foreach (array_keys($classGroups) as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filterClass===$c?'selected':''; ?>><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Section</label>
                    <select class="form-select" name="section">
                        <option value="">Select Section</option>
                        <?php if ($filterClass && isset($classGroups[$filterClass])): foreach ($classGroups[$filterClass] as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $filterSection===$s?'selected':''; ?>><?php echo $s; ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Date</label>
                    <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($filterDate); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">View</label>
                    <select class="form-select" name="view">
                        <option value="mark"   <?php echo $viewMode==='mark'?'selected':''; ?>>Mark Attendance</option>
                        <option value="report" <?php echo $viewMode==='report'?'selected':''; ?>>View Report</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Load</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($filterClass && $filterSection): ?>

    <?php if ($viewMode === 'mark'): ?>
    <!-- Mark Attendance -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <span>Mark Attendance — Class <?php echo htmlspecialchars($filterClass); ?>-<?php echo htmlspecialchars($filterSection); ?> | <?php echo htmlspecialchars($filterDate); ?></span>
            <span class="badge bg-secondary"><?php echo count($classStudents); ?> Students</span>
        </div>
        <div class="card-body">
            <?php if (empty($classStudents)): ?>
                <p class="text-muted text-center py-3">No students found for this class/section.</p>
            <?php else: ?>
            <form method="POST">
                <input type="hidden" name="save_attendance" value="1">
                <input type="hidden" name="class"   value="<?php echo htmlspecialchars($filterClass); ?>">
                <input type="hidden" name="section" value="<?php echo htmlspecialchars($filterSection); ?>">
                <input type="hidden" name="date"    value="<?php echo htmlspecialchars($filterDate); ?>">

                <div class="mb-3 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="setAll('Present')">Mark All Present</button>
                    <button type="button" class="btn btn-sm btn-outline-danger"  onclick="setAll('Absent')">Mark All Absent</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Roll</th><th>Name</th><th>Present</th><th>Absent</th><th>Late</th></tr></thead>
                        <tbody>
                        <?php foreach ($classStudents as $s):
                            $existing = $existingAtt[$s['id']] ?? 'Present';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['roll_number']??'-'); ?></td>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <?php foreach (['Present','Absent','Late'] as $status): ?>
                                <td>
                                    <input type="radio" class="form-check-input att-radio" name="attendance[<?php echo htmlspecialchars($s['id']); ?>]" value="<?php echo $status; ?>" <?php echo $existing===$status?'checked':''; ?>>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Attendance</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php else: // Report view ?>
    <div class="card">
        <div class="card-header">Attendance Report — Class <?php echo htmlspecialchars($filterClass); ?>-<?php echo htmlspecialchars($filterSection); ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Total Days</th><th>Present</th><th>Absent</th><th>Late</th><th>% Present</th></tr></thead>
                    <tbody>
                    <?php foreach ($classStudents as $s):
                        $sAtt = array_filter($attendance, fn($a) => $a['student_id']===$s['id'] && $a['class']===$filterClass && $a['section']===$filterSection);
                        $total   = count($sAtt);
                        $present = count(array_filter($sAtt, fn($a) => $a['status']==='Present'));
                        $absent  = count(array_filter($sAtt, fn($a) => $a['status']==='Absent'));
                        $late    = count(array_filter($sAtt, fn($a) => $a['status']==='Late'));
                        $pct     = $total > 0 ? round($present/$total*100) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo $total; ?></td>
                            <td class="attendance-present"><?php echo $present; ?></td>
                            <td class="attendance-absent"><?php echo $absent; ?></td>
                            <td class="attendance-late"><?php echo $late; ?></td>
                            <td>
                                <div class="progress" style="height:16px">
                                    <div class="progress-bar bg-<?php echo $pct>=75?'success':($pct>=50?'warning':'danger'); ?>" style="width:<?php echo $pct; ?>%"><?php echo $pct; ?>%</div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="alert alert-info"><i class="bi bi-info-circle"></i> Please select a class and section to manage attendance.</div>
    <?php endif; ?>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
function setAll(status) {
    document.querySelectorAll('.att-radio[value="' + status + '"]').forEach(function(r) { r.checked = true; });
}
</script>
