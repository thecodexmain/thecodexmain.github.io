<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

if (isset($_GET['action']) && $_GET['action']==='delete') {
    $asgn = loadData('assignments');
    $asgn = array_values(array_filter($asgn, fn($a) => $a['id']!==sanitize($_GET['id'])));
    saveData('assignments', $asgn);
    setFlash('success', 'Assignment deleted.');
    header('Location: '.$baseUrl.'/admin/assignments.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = sanitize($_POST['id']          ?? '');
    $title   = sanitize($_POST['title']       ?? '');
    $subject = sanitize($_POST['subject']     ?? '');
    $class   = sanitize($_POST['class']       ?? '');
    $section = sanitize($_POST['section']     ?? '');
    $teacher = sanitize($_POST['teacher_id']  ?? '');
    $due     = sanitize($_POST['due_date']    ?? '');
    $desc    = sanitize($_POST['description'] ?? '');

    $asgn = loadData('assignments');
    if ($id) {
        foreach ($asgn as &$a) {
            if ($a['id']===$id) {
                $a = array_merge($a, compact('title','subject','class','section','due','desc') + ['teacher_id'=>$teacher,'due_date'=>$due,'description'=>$desc]);
                break;
            }
        }
        setFlash('success', 'Assignment updated.');
    } else {
        $asgn[] = ['id'=>generateId('ASG'),'title'=>$title,'subject'=>$subject,'class'=>$class,'section'=>$section,'teacher_id'=>$teacher,'due_date'=>$due,'description'=>$desc,'created_at'=>date('Y-m-d')];
        setFlash('success', 'Assignment added.');
    }
    saveData('assignments', $asgn);
    header('Location: '.$baseUrl.'/admin/assignments.php'); exit;
}

$assignments = loadData('assignments');
$teachers    = loadData('teachers');
$filterClass = sanitize($_GET['class'] ?? '');
$filterSubj  = sanitize($_GET['subject'] ?? '');

$list = $assignments;
if ($filterClass) $list = array_filter($list, fn($a) => ($a['class']??'')===$filterClass);
if ($filterSubj)  $list = array_filter($list, fn($a) => stripos($a['subject']??'',$filterSubj)!==false);
$list = array_values($list);
usort($list, fn($a,$b) => strcmp($b['due_date']??'',$a['due_date']??''));

$editAsgn = null;
if (isset($_GET['edit'])) { foreach ($assignments as $a) { if ($a['id']===$_GET['edit']) { $editAsgn=$a; break; } } }

$pageTitle = 'Assignments';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-file-earmark-text text-theme"></i> Assignments</h2></div>
    <?php echo renderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><?php echo $editAsgn ? 'Edit Assignment' : 'Add Assignment'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editAsgn): ?><input type="hidden" name="id" value="<?php echo htmlspecialchars($editAsgn['id']); ?>"><?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($editAsgn['title']??''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" value="<?php echo htmlspecialchars($editAsgn['subject']??''); ?>">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class">
                                    <option value="">All</option>
                                    <?php for($i=1;$i<=12;$i++): ?><option value="<?php echo $i;?>" <?php echo ($editAsgn['class']??'')==(string)$i?'selected':'';?>><?php echo $i;?></option><?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Section</label>
                                <select class="form-select" name="section">
                                    <option value="">All</option>
                                    <?php foreach(['A','B','C','D','E'] as $s): ?><option value="<?php echo $s;?>" <?php echo ($editAsgn['section']??'')===$s?'selected':'';?>><?php echo $s;?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teacher</label>
                            <select class="form-select" name="teacher_id">
                                <option value="">-- None --</option>
                                <?php foreach($teachers as $t): ?><option value="<?php echo htmlspecialchars($t['id']);?>" <?php echo ($editAsgn['teacher_id']??'')===$t['id']?'selected':'';?>><?php echo htmlspecialchars($t['name']);?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date" value="<?php echo htmlspecialchars($editAsgn['due_date']??''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($editAsgn['description']??''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo $editAsgn?'Update':'Add Assignment'; ?></button>
                        <?php if ($editAsgn): ?><a href="<?php echo $baseUrl;?>/admin/assignments.php" class="btn btn-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-body py-2">
                <form method="GET" class="row g-2">
                    <div class="col-md-3"><select class="form-select" name="class"><option value="">All Classes</option><?php for($i=1;$i<=12;$i++): ?><option value="<?php echo $i;?>" <?php echo $filterClass==(string)$i?'selected':'';?>><?php echo $i;?></option><?php endfor; ?></select></div>
                    <div class="col-md-4"><input type="text" class="form-control" name="subject" placeholder="Filter by subject..." value="<?php echo htmlspecialchars($filterSubj);?>"></div>
                    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
                    <div class="col-md-2"><a href="<?php echo $baseUrl;?>/admin/assignments.php" class="btn btn-outline-secondary w-100">Reset</a></div>
                </form>
            </div></div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Title</th><th>Subject</th><th>Class</th><th>Due Date</th><th>Teacher</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($list)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">No assignments found.</td></tr>
                            <?php else: foreach($list as $a):
                                $teacher = getTeacherById($a['teacher_id']??'');
                                $isPast  = !empty($a['due_date']) && $a['due_date'] < date('Y-m-d');
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($a['title']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars(mb_substr($a['description']??'',0,60)); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($a['subject']??'-'); ?></td>
                                    <td><?php echo htmlspecialchars($a['class']??'All').'-'.htmlspecialchars($a['section']??'All'); ?></td>
                                    <td class="<?php echo $isPast?'text-danger':''; ?>"><?php echo formatDate($a['due_date']??''); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['name']??'-'); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo urlencode($a['id']);?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="?action=delete&id=<?php echo urlencode($a['id']);?>" class="btn btn-sm btn-outline-danger btn-delete ms-1"><i class="bi bi-trash"></i></a>
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
