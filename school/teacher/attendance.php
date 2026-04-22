<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['teacher']);

$baseUrl   = getBaseUrl();
$teacherId = $_SESSION['teacher_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $date=$_POST['date']??date('Y-m-d'); $class=$_POST['class']??''; $section=$_POST['section']??'';
    $records=$_POST['attendance']??[];
    $att=loadData('attendance');
    $att=array_values(array_filter($att,fn($a)=>!($a['date']===$date&&$a['class']===$class&&$a['section']===$section)));
    foreach($records as $sid=>$status) $att[]=['id'=>generateId('ATT'),'student_id'=>sanitize($sid),'class'=>$class,'section'=>$section,'date'=>$date,'status'=>sanitize($status),'marked_by'=>$_SESSION['user_id']??''];
    saveData('attendance',$att);
    setFlash('success','Attendance saved!');
    header('Location: '.$baseUrl.'/teacher/attendance.php?class='.urlencode($class).'&section='.urlencode($section).'&date='.urlencode($date)); exit;
}

$tt=loadData('timetable');
$myClasses=[];
foreach($tt as $t){ if($t['teacher_id']===$teacherId) $myClasses[$t['class'].'-'.$t['section']]=['class'=>$t['class'],'section'=>$t['section']]; }

$filterClass=sanitize($_GET['class']??''); $filterSection=sanitize($_GET['section']??''); $filterDate=sanitize($_GET['date']??date('Y-m-d'));
$classStudents=[]; $existingAtt=[];
if($filterClass&&$filterSection){
    $classStudents=array_values(array_filter(loadData('students'),fn($s)=>$s['class']===$filterClass&&$s['section']===$filterSection));
    foreach(loadData('attendance') as $a){ if($a['date']===$filterDate&&$a['class']===$filterClass&&$a['section']===$filterSection) $existingAtt[$a['student_id']]=$a['status']; }
}
$pageTitle='Take Attendance'; include __DIR__.'/../includes/header.php';
?>
<div class="wrapper"><?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="main-content">
<div class="page-header"><h2><i class="bi bi-calendar-check text-theme"></i> Take Attendance</h2></div>
<?php echo renderFlash(); ?>
<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2">
<div class="col-md-2"><label class="form-label small">Class-Section</label>
<select class="form-select" name="class" onchange="this.form.submit()">
<option value="">Select</option>
<?php foreach($myClasses as $key=>$c): ?><option value="<?php echo htmlspecialchars($c['class']);?>" <?php echo $filterClass===$c['class']?'selected':'';?>>Class <?php echo htmlspecialchars($c['class']);?></option><?php endforeach; ?>
</select></div>
<div class="col-md-2"><label class="form-label small">Section</label>
<select class="form-select" name="section">
<?php foreach(array_unique(array_column(array_values($myClasses),'section')) as $s): ?><option value="<?php echo htmlspecialchars($s);?>" <?php echo $filterSection===$s?'selected':'';?>><?php echo $s;?></option><?php endforeach; ?>
</select></div>
<div class="col-md-2"><label class="form-label small">Date</label><input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($filterDate);?>"></div>
<div class="col-md-2"><label class="form-label small">&nbsp;</label><button type="submit" class="btn btn-primary d-block w-100">Load</button></div>
</form></div></div>
<?php if($filterClass&&$filterSection&&!empty($classStudents)): ?>
<div class="card"><div class="card-body">
<form method="POST">
<input type="hidden" name="save_attendance" value="1">
<input type="hidden" name="class" value="<?php echo htmlspecialchars($filterClass);?>">
<input type="hidden" name="section" value="<?php echo htmlspecialchars($filterSection);?>">
<input type="hidden" name="date" value="<?php echo htmlspecialchars($filterDate);?>">
<div class="mb-2"><button type="button" class="btn btn-sm btn-outline-success me-2" onclick="document.querySelectorAll('[value=Present]').forEach(r=>r.checked=true)">All Present</button><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.querySelectorAll('[value=Absent]').forEach(r=>r.checked=true)">All Absent</button></div>
<table class="table"><thead><tr><th>Roll</th><th>Name</th><th>Present</th><th>Absent</th><th>Late</th></tr></thead><tbody>
<?php foreach($classStudents as $s): $ex=$existingAtt[$s['id']]??'Present'; ?>
<tr><td><?php echo htmlspecialchars($s['roll_number']??'-');?></td><td><?php echo htmlspecialchars($s['name']);?></td>
<?php foreach(['Present','Absent','Late'] as $st): ?><td><input type="radio" name="attendance[<?php echo htmlspecialchars($s['id']);?>]" value="<?php echo $st;?>" <?php echo $ex===$st?'checked':'';?>></td><?php endforeach; ?>
</tr><?php endforeach; ?>
</tbody></table>
<button type="submit" class="btn btn-primary">Save Attendance</button>
</form></div></div>
<?php elseif($filterClass&&$filterSection): echo '<div class="alert alert-warning">No students found.</div>'; endif; ?>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
