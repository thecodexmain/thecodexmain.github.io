<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['student']);
$studentId=$_SESSION['student_id']??'';
$fees=array_values(array_filter(loadData('fees'),fn($f)=>$f['student_id']===$studentId));
$paid=array_filter($fees,fn($f)=>$f['status']==='paid');
$pending=array_filter($fees,fn($f)=>$f['status']==='pending');
$pageTitle='My Fees';include __DIR__.'/../includes/header.php';
?>
<div class="wrapper"><?php include __DIR__.'/../includes/sidebar.php';?>
<div class="main-content">
<div class="page-header"><h2><i class="bi bi-cash-stack text-theme"></i> My Fees</h2></div>
<div class="row g-3 mb-4">
<div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-success">₹<?php echo number_format(array_sum(array_column(array_values($paid),'amount')));?></div><div class="text-muted">Paid</div></div></div>
<div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-danger">₹<?php echo number_format(array_sum(array_column(array_values($pending),'amount')));?></div><div class="text-muted">Pending</div></div></div>
<div class="col-md-4"><div class="card text-center p-3"><div class="fs-2 fw-bold text-primary"><?php echo count($fees);?></div><div class="text-muted">Total Records</div></div></div>
</div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Fee Type</th><th>Amount</th><th>Due Date</th><th>Paid Date</th><th>Status</th><th>Receipt</th></tr></thead><tbody>
<?php if(empty($fees)): ?><tr><td colspan="6" class="text-center text-muted py-4">No fee records.</td></tr>
<?php else: foreach($fees as $f): ?><tr>
<td><?php echo htmlspecialchars($f['fee_type']);?></td><td>₹<?php echo number_format($f['amount']);?></td>
<td><?php echo formatDate($f['due_date']??'');?></td><td><?php echo $f['paid_date']?formatDate($f['paid_date']):'-';?></td>
<td><span class="badge status-<?php echo $f['status'];?>"><?php echo ucfirst($f['status']);?></span></td>
<td><?php echo htmlspecialchars($f['receipt_no']??'-');?></td></tr>
<?php endforeach;endif;?></tbody></table></div></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php';?>
