<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

// Save results
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_results'])) {
    $examId  = sanitize($_POST['exam_id'] ?? '');
    $marks   = $_POST['marks'] ?? [];
    $remarks = $_POST['remarks'] ?? [];

    $exams = loadData('exams');
    $exam  = null;
    foreach ($exams as $e) { if ($e['id']===$examId) { $exam=$e; break; } }

    if (!$exam) { setFlash('error', 'Exam not found.'); header('Location: '.$baseUrl.'/admin/results.php'); exit; }

    $results = loadData('results');
    // Remove existing for this exam
    $results = array_values(array_filter($results, fn($r) => $r['exam_id'] !== $examId));

    foreach ($marks as $studentId => $mark) {
        $mark = (float)$mark;
        $max  = (float)($exam['max_marks'] ?? 100);
        $grade = getGrade($mark, $max);
        $results[] = [
            'id'             => generateId('RES'),
            'exam_id'        => $examId,
            'student_id'     => sanitize($studentId),
            'marks_obtained' => (string)$mark,
            'grade'          => $grade,
            'remarks'        => sanitize($remarks[$studentId] ?? '')
        ];
    }
    saveData('results', $results);
    setFlash('success', 'Results saved!');
    header('Location: ' . $baseUrl . '/admin/results.php?exam_id=' . urlencode($examId)); exit;
}

// Delete result
if (isset($_GET['action']) && $_GET['action']==='delete') {
    $results = loadData('results');
    $results = array_values(array_filter($results, fn($r) => $r['id']!==sanitize($_GET['id'])));
    saveData('results', $results);
    setFlash('success', 'Result deleted.');
    header('Location: '.$baseUrl.'/admin/results.php'); exit;
}

$exams     = loadData('exams');
$students  = loadData('students');
$results   = loadData('results');
$selExamId = sanitize($_GET['exam_id'] ?? '');
$selExam   = null;
if ($selExamId) { foreach ($exams as $e) { if ($e['id']===$selExamId) { $selExam=$e; break; } } }

$examStudents = [];
if ($selExam) {
    $examStudents = array_values(array_filter($students, fn($s) => $s['class']===$selExam['class'] && ($selExam['section']==='' || $selExam['section']===$s['section'])));
    usort($examStudents, fn($a,$b) => (int)($a['roll_number']??0)-(int)($b['roll_number']??0));
}

// Index existing results
$existingResults = [];
foreach ($results as $r) {
    if ($r['exam_id']===$selExamId) $existingResults[$r['student_id']] = $r;
}

$pageTitle = 'Results';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-award text-theme"></i> Exam Results</h2></div>
    <?php echo renderFlash(); ?>

    <!-- Select Exam -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Select Exam</label>
                    <select class="form-select" name="exam_id" onchange="this.form.submit()">
                        <option value="">-- Select Exam --</option>
                        <?php foreach ($exams as $e): ?>
                            <option value="<?php echo htmlspecialchars($e['id']); ?>" <?php echo $selExamId===$e['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($e['name']); ?> — Class <?php echo htmlspecialchars($e['class']); ?> (<?php echo formatDate($e['date']??''); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selExam): ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between">
            <strong><?php echo htmlspecialchars($selExam['name']); ?></strong>
            <span>Class <?php echo htmlspecialchars($selExam['class'].'-'.$selExam['section']); ?> | <?php echo htmlspecialchars($selExam['subject']??''); ?> | Max: <?php echo $selExam['max_marks']; ?> | Pass: <?php echo $selExam['pass_marks']; ?></span>
        </div>
        <div class="card-body">
        <?php if (empty($examStudents)): ?>
            <p class="text-muted text-center py-3">No students found for this exam.</p>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="save_results" value="1">
            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($selExamId); ?>">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Roll</th><th>Student</th><th>Marks (Max: <?php echo $selExam['max_marks']; ?>)</th><th>Grade</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($examStudents as $s):
                        $er     = $existingResults[$s['id']] ?? null;
                        $marks  = $er['marks_obtained'] ?? '';
                        $grade  = $er['grade'] ?? '';
                        $remark = $er['remarks'] ?? '';
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['roll_number']??'-'); ?></td>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><input type="number" class="form-control form-control-sm" name="marks[<?php echo htmlspecialchars($s['id']); ?>]" value="<?php echo htmlspecialchars($marks); ?>" min="0" max="<?php echo $selExam['max_marks']; ?>" style="width:90px"></td>
                            <td><span class="badge bg-<?php echo in_array($grade,['A+','A'])?'success':($grade==='F'?'danger':'primary'); ?>"><?php echo htmlspecialchars($grade); ?></span></td>
                            <td><input type="text" class="form-control form-control-sm" name="remarks[<?php echo htmlspecialchars($s['id']); ?>]" value="<?php echo htmlspecialchars($remark); ?>" placeholder="Optional"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Results</button>
            <button type="button" class="btn btn-outline-secondary ms-2 no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </form>
        <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-info"><i class="bi bi-info-circle"></i> Select an exam to enter or view results.</div>
    <?php endif; ?>

    <!-- All Results Summary -->
    <?php if (!$selExamId): ?>
    <div class="card">
        <div class="card-header">All Exam Results Summary</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Exam</th><th>Subject</th><th>Marks</th><th>Grade</th><th>Remarks</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No results yet.</td></tr>
                    <?php else: foreach ($results as $r):
                        $student = getStudentById($r['student_id']);
                        $exam    = null;
                        foreach ($exams as $e) { if ($e['id']===$r['exam_id']) { $exam=$e; break; } }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name'] ?? $r['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($exam['name'] ?? $r['exam_id']); ?></td>
                            <td><?php echo htmlspecialchars($exam['subject'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($r['marks_obtained']); ?>/<?php echo htmlspecialchars($exam['max_marks']??'-'); ?></td>
                            <td><span class="badge bg-<?php echo in_array($r['grade'],['A+','A'])?'success':($r['grade']==='F'?'danger':'primary'); ?>"><?php echo htmlspecialchars($r['grade']); ?></span></td>
                            <td><?php echo htmlspecialchars($r['remarks']??''); ?></td>
                            <td><a href="?action=delete&id=<?php echo urlencode($r['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
