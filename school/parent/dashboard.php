<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['parent']);

$pageTitle = 'Parent Dashboard';
include __DIR__ . '/../includes/header.php';

$baseUrl    = getBaseUrl();
$students   = loadData('students');
$attendance = loadData('attendance');
$fees       = loadData('fees');
$notices    = loadData('notices');
$events     = loadData('events');

// In this lightweight demo, parent accounts are not linked to a student record.
// Provide quick overview cards and school-wide updates.
$totalStudents   = count($students);
$pendingFeesAmt  = array_sum(array_column(array_filter($fees, fn($f) => ($f['status'] ?? '') === 'pending'), 'amount'));
$paidFeesAmt     = array_sum(array_column(array_filter($fees, fn($f) => ($f['status'] ?? '') === 'paid'), 'amount'));

$today = date('Y-m-d');
$upcomingEvents = array_filter($events, fn($e) => ($e['date'] ?? '') >= $today);
usort($upcomingEvents, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
$upcomingEvents = array_slice($upcomingEvents, 0, 3);

$sortedNotices = $notices;
usort($sortedNotices, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$recentNotices = array_slice($sortedNotices, 0, 4);

$todayAtt = array_filter($attendance, fn($a) => ($a['date'] ?? '') === $today);
$presentToday = count(array_filter($todayAtt, fn($a) => ($a['status'] ?? '') === 'Present'));
$attPct = count($todayAtt) > 0 ? round(($presentToday / count($todayAtt)) * 100) : 0;
?>

<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-speedometer2 text-theme"></i> Parent Dashboard</h2>
            <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>! Stay updated with school announcements.</p>
        </div>
        <div class="text-muted small"><i class="bi bi-calendar3"></i> <?php echo date('l, d F Y'); ?></div>
    </div>

    <?php echo renderFlash(); ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                <div class="card-body">
                    <div class="text-white-50 small fw-semibold">TOTAL STUDENTS</div>
                    <div class="stat-value"><?php echo $totalStudents; ?></div>
                    <small class="text-white-50">school strength</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#198754,#146c43)">
                <div class="card-body">
                    <div class="text-white-50 small fw-semibold">ATTENDANCE TODAY</div>
                    <div class="stat-value"><?php echo $attPct; ?>%</div>
                    <small class="text-white-50">overall present</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#fd7e14,#dc6a00)">
                <div class="card-body">
                    <div class="text-white-50 small fw-semibold">PENDING FEES</div>
                    <div class="stat-value">₹<?php echo number_format($pendingFeesAmt); ?></div>
                    <small class="text-white-50">school-wide</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card text-white" style="background:linear-gradient(135deg,#6610f2,#520dc2)">
                <div class="card-body">
                    <div class="text-white-50 small fw-semibold">COLLECTED FEES</div>
                    <div class="stat-value">₹<?php echo number_format($paidFeesAmt); ?></div>
                    <small class="text-white-50">school-wide</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-megaphone text-warning"></i> Recent Notices</span>
                    <a href="<?php echo $baseUrl; ?>/student/assignments.php" class="btn btn-sm btn-outline-warning">Student Portal</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentNotices)): ?>
                        <p class="text-muted text-center py-3">No notices</p>
                    <?php else: foreach ($recentNotices as $n): ?>
                        <div class="p-3 border-bottom priority-<?php echo htmlspecialchars($n['priority'] ?? 'low'); ?>">
                            <div class="fw-semibold small"><?php echo htmlspecialchars($n['title']); ?></div>
                            <div class="text-muted small"><?php echo formatDate($n['created_at'] ?? ''); ?>
                                &bull; <span class="badge bg-<?php echo (($n['priority'] ?? 'low') === 'high') ? 'danger' : ((($n['priority'] ?? 'low') === 'medium') ? 'warning text-dark' : 'success'); ?>"><?php echo ucfirst($n['priority'] ?? 'low'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-event text-success"></i> Upcoming Events</span>
                    <a href="<?php echo $baseUrl; ?>/admin/events.php" class="btn btn-sm btn-outline-success">Events</a>
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
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

