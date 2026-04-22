<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

// Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $exams = loadData('exams');
    $exams = array_values(array_filter($exams, fn($e) => $e['id'] !== sanitize($_GET['id'])));
    saveData('exams', $exams);
    setFlash('success', 'Exam deleted.');
    header('Location: ' . $baseUrl . '/admin/exams.php'); exit;
}

// Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = sanitize($_POST['id']        ?? '');
    $name     = sanitize($_POST['name']      ?? '');
    $class    = sanitize($_POST['class']     ?? '');
    $section  = sanitize($_POST['section']   ?? '');
    $subject  = sanitize($_POST['subject']   ?? '');
    $date     = sanitize($_POST['date']      ?? '');
    $maxM     = sanitize($_POST['max_marks'] ?? '100');
    $passM    = sanitize($_POST['pass_marks']?? '35');
    $status   = sanitize($_POST['status']    ?? 'upcoming');

    $exams = loadData('exams');
    if ($id) {
        foreach ($exams as &$e) {
            if ($e['id'] === $id) {
                $e = array_merge($e, compact('name','class','section','subject','date','status') + ['max_marks'=>$maxM,'pass_marks'=>$passM]);
                break;
            }
        }
        setFlash('success', 'Exam updated.');
    } else {
        $exams[] = ['id'=>generateId('EXM'),'name'=>$name,'class'=>$class,'section'=>$section,'subject'=>$subject,'date'=>$date,'max_marks'=>$maxM,'pass_marks'=>$passM,'status'=>$status];
        setFlash('success', 'Exam added.');
    }
    saveData('exams', $exams);
    header('Location: ' . $baseUrl . '/admin/exams.php'); exit;
}

$exams   = loadData('exams');
$classes = loadData('classes');
usort($exams, fn($a,$b) => strcmp($b['date']??'', $a['date']??''));

$editExam = null;
if (isset($_GET['edit'])) {
    foreach ($exams as $e) { if ($e['id'] === $_GET['edit']) { $editExam = $e; break; } }
}

$pageTitle = 'Exams';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-journal-text text-theme"></i> Exam Management</h2></div>
    <?php echo renderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><?php echo $editExam ? 'Edit Exam' : 'Schedule Exam'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editExam): ?><input type="hidden" name="id" value="<?php echo htmlspecialchars($editExam['id']); ?>"><?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Exam Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($editExam['name']??''); ?>" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class">
                                    <option value="">All</option>
                                    <?php for($i=1;$i<=12;$i++): ?>
                                        <option value="<?php echo $i;?>" <?php echo ($editExam['class']??'')==$i?'selected':'';?>><?php echo $i;?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Section</label>
                                <select class="form-select" name="section">
                                    <option value="">All</option>
                                    <?php foreach(['A','B','C','D','E'] as $s): ?>
                                        <option value="<?php echo $s;?>" <?php echo ($editExam['section']??'')===$s?'selected':'';?>><?php echo $s;?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" value="<?php echo htmlspecialchars($editExam['subject']??''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($editExam['date']??''); ?>">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Max Marks</label>
                                <input type="number" class="form-control" name="max_marks" value="<?php echo htmlspecialchars($editExam['max_marks']??'100'); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Pass Marks</label>
                                <input type="number" class="form-control" name="pass_marks" value="<?php echo htmlspecialchars($editExam['pass_marks']??'35'); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach(['upcoming'=>'Upcoming','ongoing'=>'Ongoing','completed'=>'Completed'] as $v=>$l): ?>
                                    <option value="<?php echo $v;?>" <?php echo ($editExam['status']??'upcoming')===$v?'selected':'';?>><?php echo $l;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo $editExam ? 'Update' : 'Add Exam'; ?></button>
                        <?php if ($editExam): ?><a href="<?php echo $baseUrl;?>/admin/exams.php" class="btn btn-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">All Exams (<?php echo count($exams); ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Name</th><th>Class</th><th>Subject</th><th>Date</th><th>Max Marks</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($exams)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">No exams scheduled.</td></tr>
                            <?php else: foreach ($exams as $e): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($e['name']); ?></td>
                                    <td><?php echo htmlspecialchars($e['class']??'-').'-'.htmlspecialchars($e['section']??'-'); ?></td>
                                    <td><?php echo htmlspecialchars($e['subject']??'-'); ?></td>
                                    <td><?php echo formatDate($e['date']??''); ?></td>
                                    <td><?php echo htmlspecialchars($e['max_marks']??'-'); ?> (Pass: <?php echo htmlspecialchars($e['pass_marks']??'-'); ?>)</td>
                                    <td><span class="badge bg-<?php echo $e['status']==='completed'?'success':($e['status']==='ongoing'?'warning text-dark':'primary'); ?>"><?php echo ucfirst($e['status']??''); ?></span></td>
                                    <td>
                                        <a href="<?php echo $baseUrl;?>/admin/results.php?exam_id=<?php echo urlencode($e['id']); ?>" class="btn btn-sm btn-outline-success" title="Enter Results"><i class="bi bi-award"></i></a>
                                        <a href="?edit=<?php echo urlencode($e['id']); ?>" class="btn btn-sm btn-outline-primary ms-1"><i class="bi bi-pencil"></i></a>
                                        <a href="?action=delete&id=<?php echo urlencode($e['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete ms-1"><i class="bi bi-trash"></i></a>
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
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
