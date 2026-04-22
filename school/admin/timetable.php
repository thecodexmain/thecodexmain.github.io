<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

// Delete
if (isset($_GET['action']) && $_GET['action']==='delete') {
    $tt = loadData('timetable');
    $tt = array_values(array_filter($tt, fn($t) => $t['id']!==sanitize($_GET['id'])));
    saveData('timetable', $tt);
    setFlash('success', 'Period deleted.');
    header('Location: '.$baseUrl.'/admin/timetable.php?class='.urlencode($_GET['class']??'').'&section='.urlencode($_GET['section']??'')); exit;
}

// Add / Edit period
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = sanitize($_POST['id']         ?? '');
    $class     = sanitize($_POST['class']      ?? '');
    $section   = sanitize($_POST['section']    ?? '');
    $day       = sanitize($_POST['day']        ?? '');
    $period    = sanitize($_POST['period']     ?? '');
    $subject   = sanitize($_POST['subject']    ?? '');
    $teacherId = sanitize($_POST['teacher_id'] ?? '');
    $startTime = sanitize($_POST['start_time'] ?? '');
    $endTime   = sanitize($_POST['end_time']   ?? '');

    $tt = loadData('timetable');
    if ($id) {
        foreach ($tt as &$t) {
            if ($t['id']===$id) {
                $t = array_merge($t, compact('class','section','day','period','subject','teacher_id','start_time','end_time') + ['teacher_id'=>$teacherId,'start_time'=>$startTime,'end_time'=>$endTime]);
                break;
            }
        }
        setFlash('success', 'Period updated.');
    } else {
        $tt[] = ['id'=>generateId('TT'),'class'=>$class,'section'=>$section,'day'=>$day,'period'=>$period,'subject'=>$subject,'teacher_id'=>$teacherId,'start_time'=>$startTime,'end_time'=>$endTime];
        setFlash('success', 'Period added.');
    }
    saveData('timetable', $tt);
    header('Location: '.$baseUrl.'/admin/timetable.php?class='.urlencode($class).'&section='.urlencode($section)); exit;
}

$filterClass   = sanitize($_GET['class']   ?? '');
$filterSection = sanitize($_GET['section'] ?? '');
$teachers      = loadData('teachers');
$tt            = loadData('timetable');
$classes       = loadData('classes');

$days    = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$periods = range(1, 8);

// Build grid
$grid = [];
if ($filterClass && $filterSection) {
    $filtered = array_filter($tt, fn($t) => $t['class']===$filterClass && $t['section']===$filterSection);
    foreach ($filtered as $t) {
        $grid[$t['day']][$t['period']] = $t;
    }
}

$editEntry = null;
if (isset($_GET['edit'])) {
    foreach ($tt as $t) { if ($t['id']===$_GET['edit']) { $editEntry=$t; break; } }
}

$pageTitle = 'Timetable';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-clock text-theme"></i> Timetable Management</h2></div>
    <?php echo renderFlash(); ?>

    <!-- Class selector -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Class</label>
                    <select class="form-select" name="class" onchange="this.form.submit()">
                        <option value="">Select Class</option>
                        <?php for($i=1;$i<=12;$i++): ?>
                            <option value="<?php echo $i;?>" <?php echo $filterClass==(string)$i?'selected':'';?>><?php echo $i;?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <select class="form-select" name="section">
                        <option value="">Select Section</option>
                        <?php foreach(['A','B','C','D','E'] as $s): ?>
                            <option value="<?php echo $s;?>" <?php echo $filterSection===$s?'selected':'';?>><?php echo $s;?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">Load</button></div>
            </form>
        </div>
    </div>

    <?php if ($filterClass && $filterSection): ?>
    <div class="row g-3">
        <!-- Grid -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">Class <?php echo htmlspecialchars($filterClass); ?>-<?php echo htmlspecialchars($filterSection); ?> Timetable</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" style="min-width:700px">
                            <thead>
                                <tr>
                                    <th class="timetable-header" style="width:100px">Day / Period</th>
                                    <?php foreach ($periods as $p): ?>
                                        <th class="timetable-header">Period <?php echo $p; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($days as $day): ?>
                                <tr>
                                    <td class="fw-bold bg-light"><?php echo $day; ?></td>
                                    <?php foreach ($periods as $p): ?>
                                        <?php $cell = $grid[$day][$p] ?? null; ?>
                                        <td class="timetable-cell <?php echo $cell?'has-class':''; ?>">
                                            <?php if ($cell): ?>
                                                <div class="fw-semibold small"><?php echo htmlspecialchars($cell['subject']); ?></div>
                                                <?php $t=getTeacherById($cell['teacher_id']??''); ?>
                                                <?php if ($t): ?><div class="text-muted" style="font-size:0.75rem"><?php echo htmlspecialchars($t['name']); ?></div><?php endif; ?>
                                                <div class="text-muted" style="font-size:0.72rem"><?php echo htmlspecialchars($cell['start_time']??''); ?>-<?php echo htmlspecialchars($cell['end_time']??''); ?></div>
                                                <div class="mt-1">
                                                    <a href="?edit=<?php echo urlencode($cell['id']); ?>&class=<?php echo urlencode($filterClass); ?>&section=<?php echo urlencode($filterSection); ?>" class="btn btn-xs btn-outline-primary p-0 px-1" style="font-size:0.7rem"><i class="bi bi-pencil"></i></a>
                                                    <a href="?action=delete&id=<?php echo urlencode($cell['id']); ?>&class=<?php echo urlencode($filterClass); ?>&section=<?php echo urlencode($filterSection); ?>" class="btn btn-xs btn-outline-danger p-0 px-1 ms-1 btn-delete" style="font-size:0.7rem"><i class="bi bi-trash"></i></a>
                                                </div>
                                            <?php else: ?>
                                                <a href="#addPeriod" class="btn btn-sm btn-outline-secondary w-100 py-1" onclick="prefill('<?php echo $day;?>','<?php echo $p;?>')" style="font-size:0.75rem;min-height:50px">+</a>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit form -->
        <div class="col-lg-3">
            <div class="card" id="addPeriod">
                <div class="card-header"><?php echo $editEntry ? 'Edit Period' : 'Add Period'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editEntry): ?><input type="hidden" name="id" value="<?php echo htmlspecialchars($editEntry['id']); ?>"><?php endif; ?>
                        <input type="hidden" name="class"   value="<?php echo htmlspecialchars($filterClass); ?>">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($filterSection); ?>">
                        <div class="mb-2">
                            <label class="form-label small">Day</label>
                            <select class="form-select form-select-sm" name="day" id="ttDay" required>
                                <?php foreach($days as $d): ?>
                                    <option value="<?php echo $d;?>" <?php echo ($editEntry['day']??'')===$d?'selected':'';?>><?php echo $d;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Period</label>
                            <select class="form-select form-select-sm" name="period" id="ttPeriod" required>
                                <?php foreach($periods as $p): ?>
                                    <option value="<?php echo $p;?>" <?php echo ($editEntry['period']??'')==(string)$p?'selected':'';?>><?php echo $p;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Subject</label>
                            <input type="text" class="form-control form-control-sm" name="subject" value="<?php echo htmlspecialchars($editEntry['subject']??''); ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Teacher</label>
                            <select class="form-select form-select-sm" name="teacher_id">
                                <option value="">-- None --</option>
                                <?php foreach($teachers as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t['id']); ?>" <?php echo ($editEntry['teacher_id']??'')===$t['id']?'selected':''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Start</label>
                            <input type="time" class="form-control form-control-sm" name="start_time" value="<?php echo htmlspecialchars($editEntry['start_time']??'09:00'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">End</label>
                            <input type="time" class="form-control form-control-sm" name="end_time" value="<?php echo htmlspecialchars($editEntry['end_time']??'09:45'); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo $editEntry?'Update':'Add Period'; ?></button>
                        <?php if ($editEntry): ?><a href="?class=<?php echo urlencode($filterClass);?>&section=<?php echo urlencode($filterSection);?>" class="btn btn-secondary btn-sm w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-info"><i class="bi bi-info-circle"></i> Select a class and section to view or edit its timetable.</div>
    <?php endif; ?>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
function prefill(day, period) {
    document.getElementById('ttDay').value    = day;
    document.getElementById('ttPeriod').value = period;
}
</script>
