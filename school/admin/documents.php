<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

if (isset($_GET['action']) && $_GET['action']==='delete') {
    $docs = loadData('documents');
    $docToDelete = null;
    foreach ($docs as $d) { if ($d['id']===sanitize($_GET['id'])) { $docToDelete=$d; break; } }
    if ($docToDelete && !empty($docToDelete['filename'])) {
        $filePath = __DIR__ . '/../uploads/documents/' . basename($docToDelete['filename']);
        if (file_exists($filePath)) @unlink($filePath);
    }
    $docs = array_values(array_filter($docs, fn($d) => $d['id']!==sanitize($_GET['id'])));
    saveData('documents', $docs);
    setFlash('success', 'Document deleted.');
    header('Location: '.$baseUrl.'/admin/documents.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = sanitize($_POST['title']    ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $desc     = sanitize($_POST['description'] ?? '');
    $filename = '';

    if (!empty($_FILES['document']['name'])) {
        $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','png'];
        $ext     = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            setFlash('error', 'File type not allowed.');
            header('Location: '.$baseUrl.'/admin/documents.php'); exit;
        }
        if ($_FILES['document']['size'] > 10 * 1024 * 1024) {
            setFlash('error', 'File too large (max 10MB).');
            header('Location: '.$baseUrl.'/admin/documents.php'); exit;
        }
        $uploadDir = __DIR__ . '/../uploads/documents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = 'doc_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['document']['name']);
        move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $filename);
    }

    $docs   = loadData('documents');
    $docs[] = ['id'=>generateId('DOC'),'title'=>$title,'category'=>$category,'description'=>$desc,'filename'=>$filename,'original_name'=>$_FILES['document']['name']??'','uploaded_by'=>$_SESSION['name']??'','created_at'=>date('Y-m-d')];
    saveData('documents', $docs);
    setFlash('success', 'Document uploaded.');
    header('Location: '.$baseUrl.'/admin/documents.php'); exit;
}

$docs    = loadData('documents');
$search  = sanitize($_GET['search'] ?? '');
$list    = $docs;
if ($search) $list = array_filter($list, fn($d) => stripos($d['title']??'',$search)!==false);
$list    = array_values($list);
usort($list, fn($a,$b) => strcmp($b['created_at']??'',$a['created_at']??''));

$pageTitle = 'Documents';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-folder text-theme"></i> Documents</h2></div>
    <?php echo renderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Upload Document</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" name="title" required></div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <?php foreach(['Circular','Notice','Report','Form','Certificate','Other'] as $c): ?><option value="<?php echo $c;?>"><?php echo $c;?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
                        <div class="mb-3"><label class="form-label">File (Max 10MB)</label><input type="file" class="form-control" name="document" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.png"></div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-cloud-upload"></i> Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-body py-2">
                <form method="GET" class="row g-2">
                    <div class="col-md-6"><input type="text" class="form-control" name="search" placeholder="Search documents..." value="<?php echo htmlspecialchars($search);?>"></div>
                    <div class="col-md-3"><button class="btn btn-outline-primary w-100">Search</button></div>
                    <div class="col-md-3"><a href="<?php echo $baseUrl;?>/admin/documents.php" class="btn btn-outline-secondary w-100">Reset</a></div>
                </form>
            </div></div>

            <div class="card">
                <div class="card-header">Documents (<?php echo count($list); ?>)</div>
                <div class="card-body p-0">
                    <?php if (empty($list)): ?>
                        <p class="text-muted text-center py-4">No documents uploaded yet.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Title</th><th>Category</th><th>File</th><th>Date</th><th>By</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($list as $d): ?>
                                <tr>
                                    <td><div class="fw-semibold"><?php echo htmlspecialchars($d['title']); ?></div><small class="text-muted"><?php echo htmlspecialchars($d['description']??''); ?></small></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($d['category']??''); ?></span></td>
                                    <td>
                                        <?php if (!empty($d['filename'])): ?>
                                            <a href="<?php echo $baseUrl;?>/uploads/documents/<?php echo urlencode($d['filename']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download"></i> <?php echo htmlspecialchars($d['original_name']??'Download'); ?>
                                            </a>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($d['created_at']??''); ?></td>
                                    <td><?php echo htmlspecialchars($d['uploaded_by']??''); ?></td>
                                    <td><a href="?action=delete&id=<?php echo urlencode($d['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
