<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['teacher']);
$teacherId=$_SESSION['teacher_id']??'';
$tt=array_filter(loadData('timetable'),fn($t)=>$t['teacher_id']===$teacherId);
$days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$grid=[];
foreach($tt as $t) $grid[$t['day']][$t['period']]=$t;
$pageTitle='My Timetable';include __DIR__.'/../includes/header.php';
?>
<div class="wrapper"><?php include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content">
<div class="page-header"><h2><i class="bi bi-clock text-theme"></i> My Timetable</h2></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-bordered mb-0" style="min-width:700px">
<thead><tr><th class="timetable-header">Day</th><?php for($p=1;$p<=8;$p++): ?><th class="timetable-header">P<?php echo $p;?></th><?php endfor;?></tr></thead>
<tbody><?php foreach($days as $day): ?>
<tr><td class="fw-bold bg-light"><?php echo $day;?></td>
<?php for($p=1;$p<=8;$p++): $cell=$grid[$day][$p]??null; ?>
<td class="timetable-cell <?php echo $cell?'has-class':'';?>">
<?php if($cell): ?><div class="fw-semibold small"><?php echo htmlspecialchars($cell['subject']);?></div>
<small class="text-muted">Class <?php echo htmlspecialchars($cell['class'].'-'.$cell['section']);?></small>
<div class="text-muted" style="font-size:0.72rem"><?php echo htmlspecialchars($cell['start_time']??'').'-'.htmlspecialchars($cell['end_time']??'');?></div>
<?php endif;?></td>
<?php endfor;?></tr><?php endforeach;?>
</tbody></table></div></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php';?>
