<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

if (isset($_GET['action']) && $_GET['action']==='delete') {
    $events = loadData('events');
    $events = array_values(array_filter($events, fn($e) => $e['id']!==sanitize($_GET['id'])));
    saveData('events', $events);
    setFlash('success', 'Event deleted.');
    header('Location: '.$baseUrl.'/admin/events.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = sanitize($_POST['id']          ?? '');
    $title = sanitize($_POST['title']       ?? '');
    $desc  = sanitize($_POST['description'] ?? '');
    $date  = sanitize($_POST['date']        ?? '');
    $venue = sanitize($_POST['venue']       ?? '');
    $cat   = sanitize($_POST['category']    ?? '');

    $events = loadData('events');
    if ($id) {
        foreach ($events as &$e) {
            if ($e['id']===$id) { $e=array_merge($e,compact('title','desc','date','venue','cat')+['description'=>$desc,'category'=>$cat]); break; }
        }
        setFlash('success', 'Event updated.');
    } else {
        $events[] = ['id'=>generateId('EVT'),'title'=>$title,'description'=>$desc,'date'=>$date,'venue'=>$venue,'category'=>$cat,'created_by'=>$_SESSION['username']??'admin','created_at'=>date('Y-m-d')];
        setFlash('success', 'Event added.');
    }
    saveData('events', $events);
    header('Location: '.$baseUrl.'/admin/events.php'); exit;
}

$events = loadData('events');
usort($events, fn($a,$b) => strcmp($a['date']??'',$b['date']??''));

$today    = date('Y-m-d');
$upcoming = array_filter($events, fn($e) => ($e['date']??'') >= $today);
$past     = array_filter($events, fn($e) => ($e['date']??'') < $today);

$editEvent = null;
if (isset($_GET['edit'])) { foreach ($events as $e) { if ($e['id']===$_GET['edit']) { $editEvent=$e; break; } } }

$pageTitle = 'Events';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-calendar-event text-theme"></i> Events</h2></div>
    <?php echo renderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><?php echo $editEvent?'Edit Event':'Add Event'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editEvent): ?><input type="hidden" name="id" value="<?php echo htmlspecialchars($editEvent['id']); ?>"><?php endif; ?>
                        <div class="mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($editEvent['title']??''); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($editEvent['description']??''); ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($editEvent['date']??''); ?>"></div>
                        <div class="mb-3"><label class="form-label">Venue</label><input type="text" class="form-control" name="venue" value="<?php echo htmlspecialchars($editEvent['venue']??''); ?>"></div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <?php foreach(['Academic','Sports','Cultural','Meeting','Holiday','Other'] as $c): ?>
                                    <option value="<?php echo $c;?>" <?php echo ($editEvent['category']??'')===$c?'selected':'';?>><?php echo $c;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo $editEvent?'Update':'Add Event'; ?></button>
                        <?php if ($editEvent): ?><a href="<?php echo $baseUrl;?>/admin/events.php" class="btn btn-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Upcoming -->
            <h5 class="fw-semibold mb-3"><i class="bi bi-calendar-event text-success"></i> Upcoming Events (<?php echo count($upcoming); ?>)</h5>
            <?php if (empty($upcoming)): ?>
                <div class="alert alert-info">No upcoming events.</div>
            <?php else: foreach ($upcoming as $e): ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($e['title']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($e['description']??''); ?></small>
                                <div class="mt-1">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($e['category']??''); ?></span>
                                    <small class="text-muted ms-2"><i class="bi bi-calendar3"></i> <?php echo formatDate($e['date']??''); ?></small>
                                    <small class="text-muted ms-2"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($e['venue']??''); ?></small>
                                </div>
                            </div>
                            <div class="ms-3 d-flex gap-1">
                                <a href="?edit=<?php echo urlencode($e['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="?action=delete&id=<?php echo urlencode($e['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>

            <?php if (!empty($past)): ?>
            <h5 class="fw-semibold mt-4 mb-3 text-muted"><i class="bi bi-calendar-x"></i> Past Events</h5>
            <?php foreach ($past as $e): ?>
                <div class="card mb-2 border-0 bg-light">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-semibold text-muted"><?php echo htmlspecialchars($e['title']); ?></span>
                                <small class="text-muted ms-2"><?php echo formatDate($e['date']??''); ?></small>
                            </div>
                            <a href="?action=delete&id=<?php echo urlencode($e['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
