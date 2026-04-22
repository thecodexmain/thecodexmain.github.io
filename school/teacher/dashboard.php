<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['teacher']);

$pageTitle = 'Teacher Dashboard';
include __DIR__ . '/../includes/header.php';

$teacherId  = $_SESSION['teacher_id'] ?? '';
$timetable  = loadData('timetable');
$assignments= loadData('assignments');
$notices    = loadData('notices');
$teachers   = loadData('teachers');

$today    = date('l'); // Day name
$todayTT  = array_filter($timetable, fn($t) => $t['teacher_id']===$teacherId && $t['day']===$today);
usort($todayTT, fn($a,$b) => strcmp($a['start_time']??'',$b['start_time']??''));

$myAssignments = array_filter($assignments, fn($a) => $a['teacher_id']===$teacherId);
usort($myAssignments, fn($a,$b) => strcmp($b['created_at']??'',$a['created_at']??''));
$myAssignments = array_slice($myAssignments, 0, 5);

$recentNotices = array_slice(array_reverse($notices), 0, 3);

// My classes
$myClasses = array_unique(array_map(fn($t) => $t['class'].'-'.$t['section'], array_filter($timetable, fn($t) => $t['teacher_id']===$teacherId)));
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h2><i class="bi bi-speedometer2 text-theme"></i> Teacher Dashboard</h2>
        <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!</p>
    </div>

    <?php echo renderFlash(); ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card stat-card text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)"><div class="card-body"><div class="small text-white-50">MY CLASSES</div><div class="stat-value"><?php echo count($myClasses); ?></div></div></div></div>
        <div class="col-md-3"><div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)"><div class="card-body"><div class="small text-white-50">TODAY'S PERIODS</div><div class="stat-value"><?php echo count($todayTT); ?></div></div></div></div>
        <div class="col-md-3"><div class="card stat-card text-white" style="background:linear-gradient(135deg,#fd7e14,#dc6a00)"><div class="card-body"><div class="small text-white-50">ASSIGNMENTS</div><div class="stat-value"><?php echo count(array_filter($assignments,fn($a)=>$a['teacher_id']===$teacherId)); ?></div></div></div></div>
        <div class="col-md-3"><div class="card stat-card text-white" style="background:linear-gradient(135deg,#6610f2,#520dc2)"><div class="card-body"><div class="small text-white-50">NOTICES</div><div class="stat-value"><?php echo count($notices); ?></div></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <!-- Today's Schedule -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span><i class="bi bi-clock text-primary"></i> Today's Schedule (<?php echo $today; ?>)</span>
                    <a href="<?php echo $baseUrl; ?>/teacher/timetable.php" class="btn btn-sm btn-outline-primary">Full Timetable</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($todayTT)): ?>
                        <p class="text-muted text-center py-3">No classes scheduled today.</p>
                    <?php else: foreach ($todayTT as $t): ?>
                        <div class="p-3 border-bottom d-flex align-items-center gap-3">
                            <div class="badge bg-primary fs-6 px-3 py-2"><?php echo htmlspecialchars($t['start_time']??''); ?></div>
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($t['subject']); ?></div>
                                <small class="text-muted">Class <?php echo htmlspecialchars($t['class'].'-'.$t['section']); ?> &bull; Period <?php echo $t['period']; ?> &bull; <?php echo htmlspecialchars($t['start_time']??''); ?>-<?php echo htmlspecialchars($t['end_time']??''); ?></small>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- My Assignments -->
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <span><i class="bi bi-file-earmark-text text-warning"></i> My Assignments</span>
                    <a href="<?php echo $baseUrl; ?>/teacher/assignments.php" class="btn btn-sm btn-outline-warning">Manage</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myAssignments)): ?>
                        <p class="text-muted text-center py-3">No assignments posted yet.</p>
                    <?php else: foreach ($myAssignments as $a): ?>
                        <div class="p-3 border-bottom">
                            <div class="fw-semibold"><?php echo htmlspecialchars($a['title']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($a['subject']); ?> | Class <?php echo htmlspecialchars($a['class'].'-'.$a['section']); ?> | Due: <?php echo formatDate($a['due_date']??''); ?></small>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <!-- Quick Actions -->
            <div class="card mb-3">
                <div class="card-header">Quick Actions</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo $baseUrl; ?>/teacher/attendance.php" class="btn btn-outline-success"><i class="bi bi-calendar-check"></i> Mark Attendance</a>
                        <a href="<?php echo $baseUrl; ?>/teacher/assignments.php" class="btn btn-outline-primary"><i class="bi bi-file-earmark-plus"></i> Post Assignment</a>
                        <a href="<?php echo $baseUrl; ?>/teacher/timetable.php" class="btn btn-outline-info"><i class="bi bi-clock"></i> View Timetable</a>
                    </div>
                </div>
            </div>

            <!-- Notices -->
            <div class="card">
                <div class="card-header"><i class="bi bi-megaphone text-warning"></i> Recent Notices</div>
                <div class="card-body p-0">
                    <?php if (empty($recentNotices)): ?>
                        <p class="text-muted text-center py-3">No notices.</p>
                    <?php else: foreach ($recentNotices as $n): ?>
                        <div class="p-3 border-bottom priority-<?php echo htmlspecialchars($n['priority']??'low'); ?>">
                            <div class="fw-semibold small"><?php echo htmlspecialchars($n['title']); ?></div>
                            <small class="text-muted"><?php echo formatDate($n['created_at']??''); ?></small>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
