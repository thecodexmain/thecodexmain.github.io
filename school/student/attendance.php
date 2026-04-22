<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);
$studentId=$_SESSION['student_id']??'';
$att=array_values(array_filter(loadData('attendance'),fn($a)=>$a['student_id']===$studentId));
usort($att,fn($a,$b)=>strcmp($b['date']??'',$a['date']??''));
$total=count($att);$present=count(array_filter($att,fn($a)=>$a['status']==='Present'));$absent=count(array_filter($att,fn($a)=>$a['status']==='Absent'));$late=count(array_filter($att,fn($a)=>$a['status']==='Late'));
$pct=$total>0?round($present/$total*100):0;
$pageTitle='My Attendance';include __DIR__.'/../includes/header.php';
?>
<div class="wrapper"><?php include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content">
<div class="page-header"><h2><i class="bi bi-calendar-check text-theme"></i> My Attendance</h2></div>
<div class="row g-3 mb-4">
<div class="col-md-3"><div class="card text-center p-3"><div class="fs-1 fw-bold text-primary"><?php echo $pct;?>%</div><div class="text-muted">Attendance %</div></div></div>
<div class="col-md-3"><div class="card text-center p-3"><div class="fs-1 fw-bold text-success"><?php echo $present;?></div><div class="text-muted">Present</div></div></div>
<div class="col-md-3"><div class="card text-center p-3"><div class="fs-1 fw-bold text-danger"><?php echo $absent;?></div><div class="text-muted">Absent</div></div></div>
<div class="col-md-3"><div class="card text-center p-3"><div class="fs-1 fw-bold text-warning"><?php echo $late;?></div><div class="text-muted">Late</div></div></div>
</div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Date</th><th>Status</th></tr></thead><tbody>
<?php if(empty($att)): ?><tr><td colspan="2" class="text-center text-muted py-4">No attendance records.</td></tr>
<?php else: foreach($att as $a): ?><tr><td><?php echo formatDate($a['date']??'');?></td>
<td><span class="fw-bold attendance-<?php echo strtolower($a['status']??'');?>"><?php echo htmlspecialchars($a['status']??'');?></span></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php';?>
