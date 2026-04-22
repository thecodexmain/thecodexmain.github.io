<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);
$studentId=$_SESSION['student_id']??''; $student=getStudentById($studentId);
$list=array_values(array_filter(loadData('assignments'),fn($a)=>$a['class']===($student['class']??'')&&($a['section']===($student['section']??'')||$a['section']==='All'||$a['section']===''))); 
usort($list,fn($a,$b)=>strcmp($a['due_date']??'',$b['due_date']??''));
$pageTitle='Assignments';include __DIR__.'/../includes/header.php';
?>
<div class="wrapper"><?php include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content">
<div class="page-header"><h2><i class="bi bi-file-earmark-text text-theme"></i> My Assignments</h2></div>
<?php if(empty($list)): ?><div class="alert alert-info">No assignments for your class.</div>
<?php else: foreach($list as $a): $isPast=!empty($a['due_date'])&&$a['due_date']<date('Y-m-d'); ?>
<div class="card mb-2 <?php echo $isPast?'border-danger':'';?>"><div class="card-body">
<div class="d-flex justify-content-between">
<div><h6 class="fw-bold mb-1"><?php echo htmlspecialchars($a['title']);?></h6>
<p class="mb-1 small"><?php echo htmlspecialchars($a['description']??'');?></p>
<small class="text-muted"><i class="bi bi-book"></i> <?php echo htmlspecialchars($a['subject']??'');?></small></div>
<div class="text-end"><span class="badge bg-<?php echo $isPast?'danger':'primary';?>">Due: <?php echo formatDate($a['due_date']??'');?></span></div>
</div></div></div>
<?php endforeach;endif;?></div></div>
<?php include __DIR__.'/../includes/footer.php';?>
