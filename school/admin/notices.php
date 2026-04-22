<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

if (isset($_GET['action']) && $_GET['action']==='delete') {
    $notices = loadData('notices');
    $notices = array_values(array_filter($notices, fn($n) => $n['id']!==sanitize($_GET['id'])));
    saveData('notices', $notices);
    setFlash('success', 'Notice deleted.');
    header('Location: '.$baseUrl.'/admin/notices.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = sanitize($_POST['id']       ?? '');
    $title    = sanitize($_POST['title']    ?? '');
    $content  = sanitize($_POST['content']  ?? '');
    $target   = sanitize($_POST['target']   ?? 'all');
    $priority = sanitize($_POST['priority'] ?? 'medium');

    $notices = loadData('notices');
    if ($id) {
        foreach ($notices as &$n) {
            if ($n['id']===$id) { $n=array_merge($n,compact('title','content','target','priority')); break; }
        }
        setFlash('success', 'Notice updated.');
    } else {
        $notices[] = ['id'=>generateId('NOT'),'title'=>$title,'content'=>$content,'target'=>$target,'created_by'=>$_SESSION['username']??'admin','created_at'=>date('Y-m-d'),'priority'=>$priority];
        setFlash('success', 'Notice published.');
    }
    saveData('notices', $notices);
    header('Location: '.$baseUrl.'/admin/notices.php'); exit;
}

$notices = loadData('notices');
usort($notices, fn($a,$b) => strcmp($b['created_at']??'',$a['created_at']??''));

$editNotice = null;
if (isset($_GET['edit'])) { foreach ($notices as $n) { if ($n['id']===$_GET['edit']) { $editNotice=$n; break; } } }

$pageTitle = 'Notices';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-megaphone text-theme"></i> Notices</h2></div>
    <?php echo renderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><?php echo $editNotice?'Edit Notice':'Publish Notice'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editNotice): ?><input type="hidden" name="id" value="<?php echo htmlspecialchars($editNotice['id']); ?>"><?php endif; ?>
                        <div class="mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($editNotice['title']??''); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Content <span class="text-danger">*</span></label><textarea class="form-control" name="content" rows="4" required><?php echo htmlspecialchars($editNotice['content']??''); ?></textarea></div>
                        <div class="mb-3">
                            <label class="form-label">Target Audience</label>
                            <select class="form-select" name="target">
                                <?php foreach(['all'=>'Everyone','students'=>'Students Only','teachers'=>'Teachers Only','parents'=>'Parents Only'] as $v=>$l): ?>
                                    <option value="<?php echo $v;?>" <?php echo ($editNotice['target']??'all')===$v?'selected':'';?>><?php echo $l;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <?php foreach(['high'=>'High','medium'=>'Medium','low'=>'Low'] as $v=>$l): ?>
                                    <option value="<?php echo $v;?>" <?php echo ($editNotice['priority']??'medium')===$v?'selected':'';?>><?php echo $l;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo $editNotice?'Update Notice':'Publish Notice'; ?></button>
                        <?php if ($editNotice): ?><a href="<?php echo $baseUrl;?>/admin/notices.php" class="btn btn-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if (empty($notices)): ?>
                <div class="alert alert-info">No notices published yet.</div>
            <?php else: foreach ($notices as $n): ?>
                <div class="card mb-3 priority-<?php echo htmlspecialchars($n['priority']??'low'); ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($n['title']); ?></h6>
                                <p class="mb-1"><?php echo htmlspecialchars($n['content']); ?></p>
                                <small class="text-muted">
                                    <i class="bi bi-calendar3"></i> <?php echo formatDate($n['created_at']??''); ?>
                                    &bull; <i class="bi bi-people"></i> <?php echo ucfirst($n['target']??'all'); ?>
                                    &bull; <span class="badge bg-<?php echo ($n['priority']==='high')?'danger':(($n['priority']==='medium')?'warning text-dark':'success'); ?>"><?php echo ucfirst($n['priority']??''); ?></span>
                                </small>
                            </div>
                            <div class="ms-3 d-flex gap-1">
                                <a href="?edit=<?php echo urlencode($n['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="?action=delete&id=<?php echo urlencode($n['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
