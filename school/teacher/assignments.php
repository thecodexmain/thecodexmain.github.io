<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['teacher']);
$baseUrl=$baseUrl??getBaseUrl(); $teacherId=$_SESSION['teacher_id']??'';
if(isset($_GET['action'])&&$_GET['action']==='delete'){$a=loadData('assignments');$a=array_values(array_filter($a,fn($x)=>$x['id']!==sanitize($_GET['id'])||$x['teacher_id']!==$teacherId));saveData('assignments',$a);setFlash('success','Deleted.');header('Location: '.$baseUrl.'/teacher/assignments.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){$asgn=loadData('assignments');$asgn[]=['id'=>generateId('ASG'),'title'=>sanitize($_POST['title']??''),'subject'=>sanitize($_POST['subject']??''),'class'=>sanitize($_POST['class']??''),'section'=>sanitize($_POST['section']??''),'teacher_id'=>$teacherId,'due_date'=>sanitize($_POST['due_date']??''),'description'=>sanitize($_POST['description']??''),'created_at'=>date('Y-m-d')];saveData('assignments',$asgn);setFlash('success','Assignment posted.');header('Location: '.$baseUrl.'/teacher/assignments.php');exit;}
$list=array_values(array_filter(loadData('assignments'),fn($a)=>$a['teacher_id']===$teacherId));
usort($list,fn($a,$b)=>strcmp($b['due_date']??'',$a['due_date']??''));
$pageTitle='My Assignments';include __DIR__.'/../includes/header.php';
?>
<div class="wrapper"><?php include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content">
<div class="page-header"><h2><i class="bi bi-file-earmark-text text-theme"></i> My Assignments</h2></div>
<?php echo renderFlash();?>
<div class="row g-3">
<div class="col-lg-4"><div class="card"><div class="card-header">Post Assignment</div><div class="card-body">
<form method="POST">
<div class="mb-2"><label class="form-label small">Title *</label><input type="text" class="form-control" name="title" required></div>
<div class="mb-2"><label class="form-label small">Subject</label><input type="text" class="form-control" name="subject"></div>
<div class="row g-1 mb-2"><div class="col-6"><label class="form-label small">Class</label><select class="form-select form-select-sm" name="class"><?php for($i=1;$i<=12;$i++): ?><option value="<?php echo $i;?>"><?php echo $i;?></option><?php endfor; ?></select></div>
<div class="col-6"><label class="form-label small">Section</label><select class="form-select form-select-sm" name="section"><?php foreach(['A','B','C','D','E'] as $s): ?><option value="<?php echo $s;?>"><?php echo $s;?></option><?php endforeach;?></select></div></div>
<div class="mb-2"><label class="form-label small">Due Date</label><input type="date" class="form-control" name="due_date"></div>
<div class="mb-3"><label class="form-label small">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
<button type="submit" class="btn btn-primary w-100">Post</button></form></div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-body p-0">
<table class="table table-hover mb-0"><thead><tr><th>Title</th><th>Subject</th><th>Class</th><th>Due</th><th>Actions</th></tr></thead><tbody>
<?php if(empty($list)): ?><tr><td colspan="5" class="text-center text-muted py-3">No assignments yet.</td></tr>
<?php else: foreach($list as $a): ?><tr><td><?php echo htmlspecialchars($a['title']);?></td><td><?php echo htmlspecialchars($a['subject']??'');?></td><td><?php echo htmlspecialchars($a['class'].'-'.$a['section']);?></td><td><?php echo formatDate($a['due_date']??'');?></td><td><a href="?action=delete&id=<?php echo urlencode($a['id']);?>" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></a></td></tr>
<?php endforeach; endif;?></tbody></table></div></div></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php';?>
