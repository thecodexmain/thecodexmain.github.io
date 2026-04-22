<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

// Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $classes = loadData('classes');
    $classes = array_values(array_filter($classes, fn($c) => $c['id'] !== sanitize($_GET['id'])));
    saveData('classes', $classes);
    setFlash('success', 'Class deleted.');
    header('Location: ' . $baseUrl . '/admin/classes.php'); exit;
}

// Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = sanitize($_POST['id']         ?? '');
    $class   = sanitize($_POST['class']      ?? '');
    $section = sanitize($_POST['section']    ?? '');
    $teacher = sanitize($_POST['teacher_id'] ?? '');
    $cap     = sanitize($_POST['capacity']   ?? '40');

    if (empty($class) || empty($section)) {
        setFlash('error', 'Class and Section are required.');
        header('Location: ' . $baseUrl . '/admin/classes.php'); exit;
    }

    $classes = loadData('classes');
    if ($id) {
        foreach ($classes as &$c) {
            if ($c['id'] === $id) {
                $c['class']=$class; $c['section']=$section; $c['teacher_id']=$teacher; $c['capacity']=(int)$cap;
                break;
            }
        }
        setFlash('success', 'Class updated.');
    } else {
        // Check duplicate
        $dup = array_filter($classes, fn($c) => $c['class']===$class && $c['section']===$section);
        if (!empty($dup)) {
            setFlash('error', "Class $class Section $section already exists.");
            header('Location: ' . $baseUrl . '/admin/classes.php'); exit;
        }
        $classes[] = ['id'=>(string)(count($classes)+1),'class'=>$class,'section'=>$section,'teacher_id'=>$teacher,'capacity'=>(int)$cap];
        setFlash('success', 'Class added.');
    }
    saveData('classes', $classes);
    header('Location: ' . $baseUrl . '/admin/classes.php'); exit;
}

$classes  = loadData('classes');
$teachers = loadData('teachers');
$students = loadData('students');

// Count students per class-section
$studentCount = [];
foreach ($students as $s) {
    $key = $s['class'] . '|' . $s['section'];
    $studentCount[$key] = ($studentCount[$key] ?? 0) + 1;
}

$editClass = null;
if (isset($_GET['edit'])) {
    foreach ($classes as $c) { if ($c['id'] === $_GET['edit']) { $editClass = $c; break; } }
}

$pageTitle = 'Classes';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-building text-theme"></i> Classes & Sections</h2></div>
    <?php echo renderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><?php echo $editClass ? 'Edit Class' : 'Add New Class'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editClass): ?><input type="hidden" name="id" value="<?php echo htmlspecialchars($editClass['id']); ?>"><?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select class="form-select" name="class" required>
                                <option value="">Select</option>
                                <?php for ($i=1;$i<=12;$i++): ?>
                                    <option value="<?php echo $i;?>" <?php echo ($editClass['class']??'')==$i?'selected':'';?>><?php echo $i;?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section <span class="text-danger">*</span></label>
                            <select class="form-select" name="section" required>
                                <option value="">Select</option>
                                <?php foreach(['A','B','C','D','E'] as $s): ?>
                                    <option value="<?php echo $s;?>" <?php echo ($editClass['section']??'')===$s?'selected':'';?>><?php echo $s;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class Teacher</label>
                            <select class="form-select" name="teacher_id">
                                <option value="">-- None --</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t['id']); ?>" <?php echo ($editClass['teacher_id']??'')===$t['id']?'selected':''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" name="capacity" value="<?php echo htmlspecialchars($editClass['capacity']??40); ?>" min="1" max="100">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo $editClass ? 'Update' : 'Add Class'; ?></button>
                        <?php if ($editClass): ?><a href="<?php echo $baseUrl;?>/admin/classes.php" class="btn btn-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">All Classes (<?php echo count($classes); ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Class</th><th>Section</th><th>Class Teacher</th><th>Students</th><th>Capacity</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($classes)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">No classes yet.</td></tr>
                            <?php else: foreach ($classes as $c):
                                $teacher = getTeacherById($c['teacher_id'] ?? '');
                                $cnt     = $studentCount[$c['class'].'|'.$c['section']] ?? 0;
                                $pct     = $c['capacity'] > 0 ? round($cnt/$c['capacity']*100) : 0;
                            ?>
                                <tr>
                                    <td><strong>Class <?php echo htmlspecialchars($c['class']); ?></strong></td>
                                    <td>Section <?php echo htmlspecialchars($c['section']); ?></td>
                                    <td><?php echo $teacher ? htmlspecialchars($teacher['name']) : '<span class="text-muted">Not assigned</span>'; ?></td>
                                    <td>
                                        <?php echo $cnt; ?>/<?php echo $c['capacity']; ?>
                                        <div class="progress mt-1" style="height:4px">
                                            <div class="progress-bar" style="width:<?php echo $pct; ?>%;background:var(--theme-color)"></div>
                                        </div>
                                    </td>
                                    <td><?php echo $c['capacity']; ?></td>
                                    <td>
                                        <a href="?edit=<?php echo urlencode($c['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="?action=delete&id=<?php echo urlencode($c['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete ms-1"><i class="bi bi-trash"></i></a>
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
