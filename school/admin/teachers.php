<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['super_admin', 'admin']);

$baseUrl = getBaseUrl();

// Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id       = sanitize($_GET['id']);
    $teachers = loadData('teachers');
    $teachers = array_values(array_filter($teachers, fn($t) => $t['id'] !== $id));
    saveData('teachers', $teachers);
    $users = loadData('users');
    $users = array_values(array_filter($users, fn($u) => ($u['teacher_id'] ?? '') !== $id));
    saveData('users', $users);
    setFlash('success', 'Teacher deleted successfully.');
    header('Location: ' . $baseUrl . '/admin/teachers.php');
    exit;
}

$teachers = loadData('teachers');
$search   = sanitize($_GET['search'] ?? '');
if ($search) {
    $teachers = array_filter($teachers, fn($t) => stripos($t['name']??'', $search) !== false || stripos($t['subject']??'', $search) !== false);
    $teachers = array_values($teachers);
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$paged = paginate($teachers, $page);

$pageTitle = 'Teachers';
include __DIR__ . '/../includes/header.php';
?>
<div class="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-person-badge text-theme"></i> Teachers</h2>
            <p class="text-muted mb-0">Total: <?php echo count(loadData('teachers')); ?> teachers</p>
        </div>
        <a href="<?php echo $baseUrl; ?>/admin/add_teacher.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Teacher</a>
    </div>

    <?php echo renderFlash(); ?>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or subject..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo $baseUrl; ?>/admin/teachers.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>ID</th><th>Name</th><th>Subject</th><th>Qualification</th><th>Phone</th><th>Joining Date</th><th>Salary</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($paged['data'])): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">No teachers found.</td></tr>
                    <?php else: foreach ($paged['data'] as $i => $t): ?>
                        <tr>
                            <td><?php echo ($page-1)*10+$i+1; ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($t['id']); ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar bg-success text-white"><?php echo strtoupper(substr($t['name'],0,1)); ?></div>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($t['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($t['email']??''); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($t['subject']??'-'); ?></td>
                            <td><?php echo htmlspecialchars($t['qualification']??'-'); ?></td>
                            <td><?php echo htmlspecialchars($t['phone']??'-'); ?></td>
                            <td><?php echo formatDate($t['joining_date']??''); ?></td>
                            <td>₹<?php echo number_format($t['salary']??0); ?></td>
                            <td><span class="badge status-<?php echo $t['status']??'active'; ?>"><?php echo ucfirst($t['status']??'active'); ?></span></td>
                            <td>
                                <a href="<?php echo $baseUrl; ?>/admin/add_teacher.php?id=<?php echo urlencode($t['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="?action=delete&id=<?php echo urlencode($t['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete ms-1"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($paged['pages'] > 1): ?>
        <div class="card-footer bg-white">
            <nav><ul class="pagination mb-0 justify-content-end">
                <?php for ($p=1; $p<=$paged['pages']; $p++): ?>
                    <li class="page-item <?php echo $p===$page?'active':''; ?>"><a class="page-link" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>"><?php echo $p; ?></a></li>
                <?php endfor; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
