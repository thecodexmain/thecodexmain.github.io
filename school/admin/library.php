<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

if (isset($_GET['action']) && $_GET['action']==='delete') {
    $lib = loadData('library');
    $lib = array_values(array_filter($lib, fn($b) => $b['id']!==sanitize($_GET['id'])));
    saveData('library', $lib);
    setFlash('success', 'Book deleted.');
    header('Location: '.$baseUrl.'/admin/library.php'); exit;
}

// Issue/Return
if (isset($_GET['action']) && $_GET['action']==='issue') {
    $lib = loadData('library');
    foreach ($lib as &$b) {
        if ($b['id']===sanitize($_GET['id']) && (int)($b['available']??0) > 0) {
            $b['available'] = (string)((int)$b['available'] - 1); break;
        }
    }
    saveData('library', $lib);
    setFlash('success', 'Book issued.');
    header('Location: '.$baseUrl.'/admin/library.php'); exit;
}
if (isset($_GET['action']) && $_GET['action']==='return') {
    $lib = loadData('library');
    foreach ($lib as &$b) {
        if ($b['id']===sanitize($_GET['id']) && (int)($b['available']??0) < (int)($b['quantity']??0)) {
            $b['available'] = (string)((int)$b['available'] + 1); break;
        }
    }
    saveData('library', $lib);
    setFlash('success', 'Book returned.');
    header('Location: '.$baseUrl.'/admin/library.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = sanitize($_POST['id']         ?? '');
    $title    = sanitize($_POST['title']      ?? '');
    $author   = sanitize($_POST['author']     ?? '');
    $isbn     = sanitize($_POST['isbn']       ?? '');
    $category = sanitize($_POST['category']   ?? '');
    $qty      = (int)sanitize($_POST['quantity']  ?? '1');
    $avail    = (int)sanitize($_POST['available'] ?? $qty);

    $lib = loadData('library');
    if ($id) {
        foreach ($lib as &$b) {
            if ($b['id']===$id) { $b=array_merge($b,compact('title','author','isbn','category')+['quantity'=>(string)$qty,'available'=>(string)$avail]); break; }
        }
        setFlash('success', 'Book updated.');
    } else {
        $lib[] = ['id'=>generateId('LIB'),'title'=>$title,'author'=>$author,'isbn'=>$isbn,'category'=>$category,'quantity'=>(string)$qty,'available'=>(string)$avail,'added_date'=>date('Y-m-d')];
        setFlash('success', 'Book added.');
    }
    saveData('library', $lib);
    header('Location: '.$baseUrl.'/admin/library.php'); exit;
}

$library  = loadData('library');
$search   = sanitize($_GET['search'] ?? '');
$list     = $library;
if ($search) $list = array_filter($list, fn($b) => stripos($b['title']??'',$search)!==false || stripos($b['author']??'',$search)!==false);
$list = array_values($list);

$editBook = null;
if (isset($_GET['edit'])) { foreach ($library as $b) { if ($b['id']===$_GET['edit']) { $editBook=$b; break; } } }

$pageTitle = 'Library';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header"><h2><i class="bi bi-book text-theme"></i> Library Management</h2></div>
    <?php echo renderFlash(); ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><?php echo $editBook?'Edit Book':'Add Book'; ?></div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editBook): ?><input type="hidden" name="id" value="<?php echo htmlspecialchars($editBook['id']); ?>"><?php endif; ?>
                        <div class="mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($editBook['title']??''); ?>" required></div>
                        <div class="mb-3"><label class="form-label">Author</label><input type="text" class="form-control" name="author" value="<?php echo htmlspecialchars($editBook['author']??''); ?>"></div>
                        <div class="mb-3"><label class="form-label">ISBN</label><input type="text" class="form-control" name="isbn" value="<?php echo htmlspecialchars($editBook['isbn']??''); ?>"></div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <?php foreach(['Textbook','Reference','Fiction','Science','History','Other'] as $cat): ?>
                                    <option value="<?php echo $cat;?>" <?php echo ($editBook['category']??'')===$cat?'selected':'';?>><?php echo $cat;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="form-label">Total Qty</label><input type="number" class="form-control" name="quantity" min="1" value="<?php echo htmlspecialchars($editBook['quantity']??'1'); ?>"></div>
                            <div class="col-6"><label class="form-label">Available</label><input type="number" class="form-control" name="available" min="0" value="<?php echo htmlspecialchars($editBook['available']??'1'); ?>"></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo $editBook?'Update':'Add Book'; ?></button>
                        <?php if ($editBook): ?><a href="<?php echo $baseUrl;?>/admin/library.php" class="btn btn-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-body py-2">
                <form method="GET" class="row g-2">
                    <div class="col-md-6"><input type="text" class="form-control" name="search" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search);?>"></div>
                    <div class="col-md-3"><button class="btn btn-outline-primary w-100">Search</button></div>
                    <div class="col-md-3"><a href="<?php echo $baseUrl;?>/admin/library.php" class="btn btn-outline-secondary w-100">Reset</a></div>
                </form>
            </div></div>

            <div class="card">
                <div class="card-header">Books (<?php echo count($list);?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>ISBN</th><th>Available</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($list)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">No books found.</td></tr>
                            <?php else: foreach($list as $b): $avail=(int)($b['available']??0); $qty=(int)($b['quantity']??0); ?>
                                <tr>
                                    <td><div class="fw-semibold"><?php echo htmlspecialchars($b['title']); ?></div></td>
                                    <td><?php echo htmlspecialchars($b['author']??'-'); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($b['category']??''); ?></span></td>
                                    <td><small><?php echo htmlspecialchars($b['isbn']??'-'); ?></small></td>
                                    <td>
                                        <span class="fw-bold <?php echo $avail===0?'text-danger':($avail<3?'text-warning':'text-success'); ?>"><?php echo $avail; ?></span>/<?php echo $qty; ?>
                                    </td>
                                    <td>
                                        <?php if ($avail > 0): ?><a href="?action=issue&id=<?php echo urlencode($b['id']); ?>" class="btn btn-sm btn-outline-warning" title="Issue"><i class="bi bi-book"></i></a><?php endif; ?>
                                        <?php if ($avail < $qty): ?><a href="?action=return&id=<?php echo urlencode($b['id']); ?>" class="btn btn-sm btn-outline-success ms-1" title="Return"><i class="bi bi-arrow-return-left"></i></a><?php endif; ?>
                                        <a href="?edit=<?php echo urlencode($b['id']); ?>" class="btn btn-sm btn-outline-primary ms-1"><i class="bi bi-pencil"></i></a>
                                        <a href="?action=delete&id=<?php echo urlencode($b['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete ms-1"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
