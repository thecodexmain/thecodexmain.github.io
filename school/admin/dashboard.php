<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';

$students   = loadData('students');
$teachers   = loadData('teachers');
$classes    = loadData('classes');
$fees       = loadData('fees');
$notices    = loadData('notices');
$events     = loadData('events');
$attendance = loadData('attendance');

$totalStudents  = count($students);
$activeStudents = count(array_filter($students, fn($s) => ($s['status'] ?? '') === 'active'));
$totalTeachers  = count($teachers);
$totalClasses   = count($classes);

$pendingFees = array_filter($fees, fn($f) => ($f['status'] ?? '') === 'pending');
$paidFees    = array_filter($fees, fn($f) => ($f['status'] ?? '') === 'paid');
$pendingAmt  = array_sum(array_column($pendingFees, 'amount'));
$collectedAmt= array_sum(array_column($paidFees,    'amount'));

$today = date('Y-m-d');
$todayAtt = array_filter($attendance, fn($a) => ($a['date'] ?? '') === $today);
$presentToday = count(array_filter($todayAtt, fn($a) => ($a['status'] ?? '') === 'Present'));
$attPct = count($todayAtt) > 0 ? round(($presentToday / count($todayAtt)) * 100) : 0;

// Upcoming events
$upcomingEvents = array_filter($events, fn($e) => ($e['date'] ?? '') >= $today);
usort($upcomingEvents, fn($a,$b) => strcmp($a['date'], $b['date']));
$upcomingEvents = array_slice($upcomingEvents, 0, 3);

// Recent notices
$sortedNotices = $notices;
usort($sortedNotices, fn($a,$b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$recentNotices = array_slice($sortedNotices, 0, 3);

// Recent students
$recentStudents = array_slice(array_reverse($students), 0, 5);
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-speedometer2 text-theme"></i> Dashboard</h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>! Here's what's happening today.</p>
        </div>
        <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo date('l, d F Y'); ?></div>
    </div>

    <?php echo renderFlash(); ?>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">TOTAL STUDENTS</div>
                        <div class="stat-value"><?php echo $totalStudents; ?></div>
                        <small class="text-white-50"><?php echo $activeStudents; ?> active</small>
                    </div>
                    <i class="bi bi-people stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">TOTAL TEACHERS</div>
                        <div class="stat-value"><?php echo $totalTeachers; ?></div>
                        <small class="text-white-50">teaching staff</small>
                    </div>
                    <i class="bi bi-person-badge stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#6610f2,#520dc2)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">TOTAL CLASSES</div>
                        <div class="stat-value"><?php echo $totalClasses; ?></div>
                        <small class="text-white-50">sections</small>
                    </div>
                    <i class="bi bi-building stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#fd7e14,#dc6a00)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">PENDING FEES</div>
                        <div class="stat-value">₹<?php echo number_format($pendingAmt); ?></div>
                        <small class="text-white-50"><?php echo count($pendingFees); ?> records</small>
                    </div>
                    <i class="bi bi-cash-stack stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Second row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#0dcaf0,#0aa2c0)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">TODAY ATTENDANCE</div>
                        <div class="stat-value"><?php echo $attPct; ?>%</div>
                        <small class="text-white-50"><?php echo $presentToday; ?>/<?php echo count($todayAtt); ?> present</small>
                    </div>
                    <i class="bi bi-calendar-check stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#dc3545,#b02a37)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">FEE COLLECTED</div>
                        <div class="stat-value">₹<?php echo number_format($collectedAmt); ?></div>
                        <small class="text-white-50"><?php echo count($paidFees); ?> paid</small>
                    </div>
                    <i class="bi bi-receipt stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#20c997,#19a07d)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">NOTICES</div>
                        <div class="stat-value"><?php echo count($notices); ?></div>
                        <small class="text-white-50">published</small>
                    </div>
                    <i class="bi bi-megaphone stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#6c757d,#545b62)">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small fw-semibold">EVENTS</div>
                        <div class="stat-value"><?php echo count($events); ?></div>
                        <small class="text-white-50"><?php echo count($upcomingEvents); ?> upcoming</small>
                    </div>
                    <i class="bi bi-calendar-event stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Recent Students -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people text-primary"></i> Recent Students</span>
                    <a href="<?php echo $baseUrl; ?>/admin/students.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Name</th><th>Class</th><th>Roll No.</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (empty($recentStudents)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No students yet</td></tr>
                            <?php else: foreach ($recentStudents as $s): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar bg-primary text-white"><?php echo strtoupper(substr($s['name'],0,1)); ?></div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($s['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($s['id']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($s['class'] . '-' . $s['section']); ?></td>
                                    <td><?php echo htmlspecialchars($s['roll_number'] ?? '-'); ?></td>
                                    <td><span class="badge status-<?php echo $s['status'] ?? 'active'; ?>"><?php echo ucfirst($s['status'] ?? 'active'); ?></span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div class="col-lg-5">
            <!-- Recent Notices -->
            <div class="card mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-megaphone text-warning"></i> Recent Notices</span>
                    <a href="<?php echo $baseUrl; ?>/admin/notices.php" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentNotices)): ?>
                        <p class="text-muted text-center py-3">No notices</p>
                    <?php else: foreach ($recentNotices as $n): ?>
                        <div class="p-3 border-bottom priority-<?php echo htmlspecialchars($n['priority'] ?? 'low'); ?>">
                            <div class="fw-semibold small"><?php echo htmlspecialchars($n['title']); ?></div>
                            <div class="text-muted small"><?php echo formatDate($n['created_at']); ?> &bull;
                                <span class="badge bg-<?php echo ($n['priority']==='high')?'danger':(($n['priority']==='medium')?'warning text-dark':'success'); ?>"><?php echo ucfirst($n['priority']??'low'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-event text-success"></i> Upcoming Events</span>
                    <a href="<?php echo $baseUrl; ?>/admin/events.php" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($upcomingEvents)): ?>
                        <p class="text-muted text-center py-3">No upcoming events</p>
                    <?php else: foreach ($upcomingEvents as $e): ?>
                        <div class="p-3 border-bottom">
                            <div class="fw-semibold small"><?php echo htmlspecialchars($e['title']); ?></div>
                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($e['venue'] ?? '-'); ?> &bull; <?php echo formatDate($e['date']); ?></small>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div><!-- /.main-content -->
</div><!-- /.wrapper -->
<?php include __DIR__ . '/../includes/footer.php'; ?>
