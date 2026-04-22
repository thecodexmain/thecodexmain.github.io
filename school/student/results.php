<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);
$studentId=$_SESSION['student_id']??'';
$results=array_values(array_filter(loadData('results'),fn($r)=>$r['student_id']===$studentId));
$exams=loadData('exams');
$pageTitle='My Results';include __DIR__.'/../includes/header.php';
?>
<div class="wrapper"><?php include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content">
<div class="page-header"><h2><i class="bi bi-award text-theme"></i> My Results</h2></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Exam</th><th>Subject</th><th>Date</th><th>Marks</th><th>Grade</th><th>Remarks</th></tr></thead><tbody>
<?php if(empty($results)): ?><tr><td colspan="6" class="text-center text-muted py-4">No results yet.</td></tr>
<?php else: foreach($results as $r): $exam=null; foreach($exams as $e){if($e['id']===$r['exam_id']){$exam=$e;break;}} ?>
<tr><td><?php echo htmlspecialchars($exam['name']??$r['exam_id']);?></td><td><?php echo htmlspecialchars($exam['subject']??'-');?></td>
<td><?php echo formatDate($exam['date']??'');?></td>
<td><?php echo htmlspecialchars($r['marks_obtained']);?>/<?php echo htmlspecialchars($exam['max_marks']??'');?></td>
<td><span class="badge bg-<?php echo in_array($r['grade'],['A+','A'])?'success':($r['grade']==='F'?'danger':'primary');?>"><?php echo htmlspecialchars($r['grade']);?></span></td>
<td><?php echo htmlspecialchars($r['remarks']??'');?></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php';?>
