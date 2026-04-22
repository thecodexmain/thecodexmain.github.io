<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);
$studentId=$_SESSION['student_id']??'';
$student=getStudentById($studentId);
$notices=array_slice(array_reverse(loadData('notices')),0,3);
$today=date('l');
$tt=loadData('timetable');
$todayTT=array_filter($tt,fn($t)=>$t['class']===($student['class']??'')&&$t['section']===($student['section']??'')&&$t['day']===$today);
usort($todayTT,fn($a,$b)=>strcmp($a['start_time']??'',$b['start_time']??''));
$assignments=array_values(array_filter(loadData('assignments'),fn($a)=>$a['class']===($student['class']??'')&&($a['section']===($student['section']??'')||$a['section']==='')&&($a['due_date']??'')>=date('Y-m-d')));
$att=array_filter(loadData('attendance'),fn($a)=>$a['student_id']===$studentId);
$total=count($att); $present=count(array_filter($att,fn($a)=>$a['status']==='Present'));
$attPct=$total>0?round($present/$total*100):0;
$pageTitle='My Dashboard';include __DIR__.'/../includes/header.php';
?>
<div class="wrapper"><?php include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content">
<div class="page-header"><h2><i class="bi bi-speedometer2 text-theme"></i> Dashboard</h2><p class="text-muted">Welcome, <?php echo htmlspecialchars($currentUser['name']);?>! Class <?php echo htmlspecialchars(($student['class']??'-').'-'.($student['section']??'-'));?></p></div>
<?php echo renderFlash();?>
<div class="row g-3 mb-4">
<div class="col-md-3"><div class="card stat-card text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)"><div class="card-body"><div class="small text-white-50">ATTENDANCE</div><div class="stat-value"><?php echo $attPct;?>%</div><small class="text-white-50"><?php echo $present;?>/<?php echo $total;?> days</small></div></div></div>
<div class="col-md-3"><div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)"><div class="card-body"><div class="small text-white-50">TODAY PERIODS</div><div class="stat-value"><?php echo count($todayTT);?></div></div></div></div>
<div class="col-md-3"><div class="card stat-card text-white" style="background:linear-gradient(135deg,#fd7e14,#dc6a00)"><div class="card-body"><div class="small text-white-50">PENDING ASSIGNMENTS</div><div class="stat-value"><?php echo count($assignments);?></div></div></div></div>
<div class="col-md-3"><div class="card stat-card text-white" style="background:linear-gradient(135deg,#6610f2,#520dc2)"><div class="card-body"><div class="small text-white-50">NOTICES</div><div class="stat-value"><?php echo count(loadData('notices'));?></div></div></div></div>
</div>
<div class="row g-3">
<div class="col-lg-6"><div class="card"><div class="card-header"><i class="bi bi-clock"></i> Today's Timetable (<?php echo $today;?>)</div><div class="card-body p-0">
<?php if(empty($todayTT)): ?><p class="text-muted text-center py-3">No classes today.</p>
<?php else: foreach($todayTT as $t): ?><div class="p-3 border-bottom d-flex gap-3"><span class="badge bg-primary"><?php echo htmlspecialchars($t['start_time']??'');?></span><div><div class="fw-semibold"><?php echo htmlspecialchars($t['subject']);?></div><small class="text-muted">Period <?php echo $t['period'];?></small></div></div>
<?php endforeach; endif;?></div></div></div>
<div class="col-lg-6">
<div class="card mb-3"><div class="card-header"><i class="bi bi-megaphone text-warning"></i> Notices</div><div class="card-body p-0">
<?php if(empty($notices)): ?><p class="text-muted text-center py-3">No notices.</p>
<?php else: foreach($notices as $n): ?><div class="p-3 border-bottom priority-<?php echo htmlspecialchars($n['priority']??'low');?>"><div class="fw-semibold small"><?php echo htmlspecialchars($n['title']);?></div><small class="text-muted"><?php echo formatDate($n['created_at']??'');?></small></div>
<?php endforeach;endif;?></div></div>
<div class="card"><div class="card-header"><i class="bi bi-file-earmark-text text-info"></i> Pending Assignments</div><div class="card-body p-0">
<?php if(empty($assignments)): ?><p class="text-muted text-center py-3">No pending assignments.</p>
<?php else: foreach(array_slice($assignments,0,4) as $a): ?><div class="p-3 border-bottom"><div class="fw-semibold small"><?php echo htmlspecialchars($a['title']);?></div><small class="text-muted"><?php echo htmlspecialchars($a['subject']??'');?> | Due: <?php echo formatDate($a['due_date']??'');?></small></div>
<?php endforeach;endif;?></div></div>
</div></div></div></div>
<?php include __DIR__.'/../includes/footer.php';?>
